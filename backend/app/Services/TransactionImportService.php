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
    /**
     * Parse the file, validate records, and save them to the database.
     */
    public function import($file): Import
    {
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        
        // resolve the correct parsing strategy class using a protected method
        $strategy = $this->getParserStrategy($extension);
        
        // CSV needs a file path
        $payload = ($extension === 'csv') ? $file->getRealPath() : file_get_contents($file->getRealPath());
        
        // Execute the strategy
        $records = $strategy->parse($payload);

        // Create an initial import record in the database
        $import = Import::create([
            'file_name' => $fileName,
            'total_records' => count($records),
            'status' => 'failed'
        ]);

        if (empty($records)) {
            ImportLog::create([
                'import_id' => $import->id,
                'error_message' => 'The file is empty or has an invalid format.'
            ]);
            return $import;
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($records as $record) {
            // Validate each individual row/record usando rozbitą metodę chronioną
            $validator = $this->validateRecord($record);

            // If validation fails, log the error and skip to the next row
            if ($validator->fails()) {
                $failedCount++;
                ImportLog::create([
                    'import_id' => $import->id,
                    'transaction_id' => $record['transaction_id'] ?? 'UNKNOWN',
                    'error_message' => implode(', ', $validator->errors()->all())
                ]);
                continue;
            }

            // If validation passes, save the valid transaction
            Transaction::create([
                'import_id'        => $import->id,
                'transaction_id'   => $record['transaction_id'],
                'account_number'   => $record['account_number'],
                'transaction_date' => $record['transaction_date'],
                'amount'           => $record['amount'],
                'currency'         => strtoupper($record['currency']),
            ]);
            $successCount++;
        }

        // Determine the final status of the file import
        $status = 'failed';
        if ($successCount === count($records)) {
            $status = 'success';
        } elseif ($successCount > 0) {
            $status = 'partial';
        }

        // Update the import record with final counters and status
        $import->update([
            'successful_records' => $successCount,
            'failed_records' => $failedCount,
            'status' => $status
        ]);

        return $import;
    }

    /**
     * Strategy Factory
     */
    protected function getParserStrategy(string $extension)
    {
        return match ($extension) {
            'json'  => new JsonParseStrategy(),
            'xml'   => new XmlParseStrategy(),
            'csv'   => new CsvParseStrategy(),
            default => throw new Exception("Unsupported file extension layout: {$extension}")
        };
    }

    /**
     * Validator
     */
    protected function validateRecord(array $record): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($record, $this->getTransactionRules());
    }

    /**
     * Protected Validation Rules
     */
    protected function getTransactionRules(): array
    {
        return [
            'transaction_id'   => 'required|string',
            'account_number'   => 'required|string|regex:/^[A-Z]{2}[0-9]{12,30}$/', // Standard IBAN regex format
            'transaction_date' => 'required|date',
            'amount'           => 'required|numeric|gt:0',
            'currency'         => [
                'required',
                new \Illuminate\Validation\Rules\Enum(\App\Enums\CurrencyType::class) // użycie Enuma 
            ],
        ];
    }
}