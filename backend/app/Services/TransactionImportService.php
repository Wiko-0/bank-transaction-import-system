<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Transaction;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Validator;
use Exception;

class TransactionImportService
{
    /**
     * Main control method to coordinate file checking, mapping, and database injection.
     */
    public function import($file): Import
    {
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        // Strategic routing to the correct protected parser based on extension configuration
        $records = match ($extension) {
            'json'  => $this->parseJsonStrategy($filePath),
            'xml'   => $this->parseXmlStrategy($filePath),
            'csv'   => $this->parseCsvStrategy($filePath),
            default => throw new Exception("Unsupported file format exception: {$extension}")
        };

        // Initialize the import batch master entity
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
            // Apply granular validation constraints per data row
            $validator = Validator::make($record, [
                'transaction_id'   => 'required|string',
                'account_number'   => 'required|string|regex:/^[A-Z]{2}[0-9]{12,30}$/',
                'transaction_date' => 'required|date',
                'amount'           => 'required|numeric|gt:0',
                'currency'         => 'required|alpha|size:3',
            ]);

            // Operational error logging branch
            if ($validator->fails()) {
                $failedCount++;
                ImportLog::create([
                    'import_id' => $import->id,
                    'transaction_id' => $record['transaction_id'] ?? 'UNKNOWN',
                    'error_message' => implode(', ', $validator->errors()->all())
                ]);
                continue;
            }

            // Successful transactional row persistence injection
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

        // Evaluate batch processing thresholds for status outcome
        $status = 'failed';
        if ($successCount === count($records)) {
            $status = 'success';
        } elseif ($successCount > 0) {
            $status = 'partial';
        }

        // Persist final metrics data back to the batch header row
        $import->update([
            'successful_records' => $successCount,
            'failed_records' => $failedCount,
            'status' => $status
        ]);

        return $import;
    }

    /*** Protected Parsing Strategy JSON */
    protected function parseJsonStrategy(string $path): array
    {
        $content = file_get_contents($path);
        return json_decode($content, true) ?? [];
    }

    /*** Protected Parsing Strategy XML*/
    protected function parseXmlStrategy(string $path): array
    {
        $content = file_get_contents($path);
        $xml = simplexml_load_string($content);
        if (!$xml) return [];
        
        $result = [];
        foreach ($xml->transaction as $tx) {
            $result[] = [
                'transaction_id'   => (string)$tx->transaction_id,
                'account_number'   => (string)$tx->account_number,
                'transaction_date' => (string)$tx->transaction_date,
                'amount'           => (float)$tx->amount,
                'currency'         => (string)$tx->currency,
            ];
        }
        return $result;
    }

    /*** Protected Parsing Strategy CSV*/
    protected function parseCsvStrategy(string $path): array
    {
        $result = [];
        if (($handle = fopen($path, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($header) == count($data)) {
                    $result[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }
        return $result;
    }
}