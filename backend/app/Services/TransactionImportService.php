<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Transaction;
use App\Models\ImportLog;
use App\Services\Strategies\JsonParseStrategy;
use App\Services\Strategies\XmlParseStrategy;
use App\Services\Strategies\CsvParseStrategy;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionImportService
{
    public function import($file): Import
    {
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        
        $strategy = $this->getParserStrategy($extension);
        $payload = is_string($file) ? $file : $file->getRealPath();
        
        $records = $strategy->parse($payload);

        $import = Import::create([
            'file_name' => $fileName,
            'total_records' => 0,
            'status' => 'failed'
        ]);

        $successCount = 0;
        $failedCount = 0;
        
        $validChunk = [];
        $chunkSize = 1000;
        $seenInChunk = []; // Zapobiega zakleszczeniom na poziomie bazy danych zapisuje id i sprawdza duplikaty

        foreach ($records as $record) {
            $validator = $this->validateRecord($record);

            if ($validator->fails()) {
                $failedCount++;
                ImportLog::create([
                    'import_id' => $import->id,
                    'transaction_id' => $record['transaction_id'] ?? 'UNKNOWN',
                    'error_message' => implode(', ', $validator->errors()->all())
                ]);
                continue;
            }

            $txId = $record['transaction_id'];

            if (isset($seenInChunk[$txId])) {
                $failedCount++;
                ImportLog::create([
                    'import_id' => $import->id,
                    'transaction_id' => $txId,
                    'error_message' => 'Duplicate transaction_id detected within the same insert chunk.'
                ]);
                continue;
            }

            $seenInChunk[$txId] = true;

            $validChunk[] = [
                'import_id'        => $import->id,
                'transaction_id'   => $txId,
                'account_number'   => $record['account_number'],
                'transaction_date' => $record['transaction_date'],
                'amount'           => $record['amount'],
                'currency'         => strtoupper($record['currency']),
                'created_at'       => now()->toDateTimeString(),
                'updated_at'       => now()->toDateTimeString(),
            ];
            
            $successCount++;

            if (count($validChunk) === $chunkSize) {
                $this->saveChunkWithTransaction($validChunk);
                $validChunk = [];  
                $seenInChunk = []; 
            }
        }

        if (!empty($validChunk)) {
            $this->saveChunkWithTransaction($validChunk);
        }

        $totalRecordsProcessed = $successCount + $failedCount;

        if ($totalRecordsProcessed === 0) {
            ImportLog::create([
                'import_id' => $import->id,
                'error_message' => 'The file is empty or has an invalid format.'
            ]);
            $import->update(['status' => 'failed']);
            return $import;
        }

        $status = 'failed';
        if ($successCount === $totalRecordsProcessed) {
            $status = 'success';
        } elseif ($successCount > 0) {
            $status = 'partial';
        }

        $import->update([
            'total_records'      => $totalRecordsProcessed,
            'successful_records' => $successCount,
            'failed_records'     => $failedCount,
            'status'             => $status
        ]);

        return $import;
    }

    private function saveChunkWithTransaction(array $chunkData): void
    {
        DB::transaction(function () use ($chunkData) {
            Transaction::upsert(
                $chunkData, 
                ['import_id', 'transaction_id'], 
                ['account_number', 'transaction_date', 'amount', 'currency', 'updated_at']
            );
        });
    }

    private function getParserStrategy(string $extension)
    {
        return match ($extension) {
            'json'  => new JsonParseStrategy(),
            'xml'   => new XmlParseStrategy(),
            'csv'   => new CsvParseStrategy(),
            default => throw new Exception("Unsupported file extension layout: {$extension}")
        };
    }

    private function validateRecord(array $record): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($record, $this->getTransactionRules());
    }

    private function getTransactionRules(): array
    {
        return [
            'transaction_id'   => 'required|string',
            'account_number'   => 'required|string|regex:/^[A-Z]{2}[0-9]{12,30}$/',
            'transaction_date' => 'required|date',
            'amount'           => 'required|numeric|gt:0',
            'currency'         => [
                'required',
                new \Illuminate\Validation\Rules\Enum(\App\Enums\CurrencyType::class)
            ],
        ];
    }
}