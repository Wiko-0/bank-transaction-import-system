<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Transaction;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Validator;

class TransactionImportService
{
    /**
     * Parse the file, validate records, and save them to the database.
     */
    public function import($file): Import
    {
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath());

        // Match file extension to the correct parser method
        $records = match ($extension) {
            'json' => $this->parseJson($content),
            'xml'  => $this->parseXml($content),
            'csv'  => $this->parseCsv($file->getRealPath()),
            default => []
        };

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
            // Validate each individual row/record
            $validator = Validator::make($record, [
                'transaction_id'   => 'required|string',
                'account_number'   => 'required|string|regex:/^[A-Z]{2}[0-9]{12,30}$/', // Standard IBAN regex format
                'transaction_date' => 'required|date',
                'amount'           => 'required|numeric|gt:0',
                'currency'         => 'required|alpha|size:3',
            ]);

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

    private function parseJson($content): array
    {
        return json_decode($content, true) ?? [];
    }

    private function parseXml($content): array
    {
        $xml = simplexml_load_string($content);
        if (!$xml) return [];
        
        $result = [];
        foreach ($xml->transaction as $tx) {
            $result[] = [
                'transaction_id' => (string)$tx->transaction_id,
                'account_number' => (string)$tx->account_number,
                'transaction_date' => (string)$tx->transaction_date,
                'amount' => (float)$tx->amount,
                'currency' => (string)$tx->currency,
            ];
        }
        return $result;
    }

    private function parseCsv($path): array
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