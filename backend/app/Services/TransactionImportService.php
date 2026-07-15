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

    public function import($file, ?int $importId = null): Import
    {
        // informacie o pliku
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
    
        $strategy = $this->getParserStrategy($extension);
        $payload = is_string($file) ? $file : $file->getRealPath();
        
        $records = $strategy->parse($payload);

        // czy ten proces ma już zarezerwowany rekord w bazie
        $import = null;
        if ($importId) {
            $import = Import::find($importId);
        }

        //  jeśli to pierwsze uruchomienie tworzy nowy rekord rejestrujący import
        if (!$import) {
            $import = Import::create([
                'file_name' => $fileName,
                'total_records' => 0,
                'status' => 'failed'
            ]);
        }

        //  Przekazanie odczytanych rekordów i obiektu importu do przetwarzania w pętli-- Wywołanie metody processRecords
        [$successCount, $failedCount] = $this->processRecords($records, $import);

        // podsumowanie po zakończeniu mielenia pliku
        $totalRecordsProcessed = $successCount + $failedCount;

        // jeśli pusty plik
        if ($totalRecordsProcessed === 0) {
            $this->logError($import->id, 'SYSTEM', 'The file is empty or has an invalid format.');
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


    private function processRecords(array $records, Import $import): array
    {
        $successCount = 0;
        $failedCount = 0;
        $validChunk = [];
        $chunkSize = 1000;
        $seenInChunk = [];

        foreach ($records as $record) {
            // Walidacja zgodności pól--- Wywołanie metody validateRecord()
            $validator = $this->validateRecord($record);

            // Obsługa rekordu niespełniającego kryteriów walidacji Laravela
            if ($validator->fails()) {
                $failedCount++;
                // Wywołanie metody logError()]w celu zapisu usterki dla panelu bocznego 
                $this->logError(
                    $import->id, 
                    $record['transaction_id'] ?? 'UNKNOWN', 
                    implode(', ', $validator->errors()->all())
                );
                continue;
            }

            $txId = $record['transaction_id'];

            // Ochrona przed zdublowanymi transakcjami wewnątrz nadsyłanego pliku 
            if (isset($seenInChunk[$txId])) {
                $failedCount++;
                $this->logError(
                    $import->id, 
                    $txId, 
                    'Duplicate transaction_id detected within the same insert chunk.'
                );
                continue;
            }

            $seenInChunk[$txId] = true;

            //  Przekształcenie danych z pliku na strukturę zgodną z kolumnami bazy danych-- Wywołanie metody mapTransactionData()
            $validChunk[] = $this->mapTransactionData($import->id, $txId, $record);
            $successCount++;

            if (count($validChunk) === $chunkSize) {
                // -Wywołanie metody saveChunkWithTransaction()
                $this->saveChunkWithTransaction($validChunk);
                $validChunk = []; 
                $seenInChunk = [];
            }
        }

        if (!empty($validChunk)) {
            $this->saveChunkWithTransaction($validChunk);
        }

        return [$successCount, $failedCount];
    }


    //Wewnątrz pętli processRecords() dla każdego w pełni poprawnego rekordu, tuż przed zapisaniem do paczki
    private function mapTransactionData(int $importId, string $txId, array $record): array
    {
        return [
            'import_id'        => $importId,
            'transaction_id'   => $txId,
            'account_number'   => $record['account_number'],
            'transaction_date' => $record['transaction_date'],
            'amount'           => $record['amount'],
            'currency'         => strtoupper($record['currency']), // Bezpieczne podbicie do wielkich liter
            'created_at'       => now()->toDateTimeString(),
            'updated_at'       => now()->toDateTimeString(),
        ];
    }

    /**
     * W pętli processRecords(), gdy rekord nie przechodzi walidacji.
     * W pętli processRecords(), gdy wykryto duplikat ID transakcji.
     * W metodzie import(), gdy przesłany plik okaże się pusty.
     */
    private function logError(int $importId, string $transactionId, string $message): void
    {
        ImportLog::create([
            'import_id'      => $importId,
            'transaction_id' => $transactionId,
            'error_message'  => $message
        ]);
    }

  
    //Wewnątrz processRecords() co każde 1000 poprawnych rekordów oraz na sam koniec pętli.
    private function saveChunkWithTransaction(array $chunkData): void
    {
        //Jeśli jeden zapis w paczce się wysypie baza wycofa 1000 rekordów 
        DB::transaction(function () use ($chunkData) {
            Transaction::upsert(
                $chunkData, 
                ['import_id', 'transaction_id'],
                ['account_number', 'transaction_date', 'amount', 'currency', 'updated_at'] // kolumny do aktualizacji przy dublu
            );
        });
    }


    //Na początku metody import() w celu wykrycia sposobu odczytu pliku.
    private function getParserStrategy(string $extension)
    {
        return match ($extension) {
            'json'  => new JsonParseStrategy(),
            'xml'   => new XmlParseStrategy(),
            'csv'   => new CsvParseStrategy(),
            default => throw new Exception("Unsupported file extension layout: {$extension}")
        };
    }


    //Na początku każdego obrotu pętli w processRecords() w celu weryfikacji pól transakcji.
    private function validateRecord(array $record): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($record, $this->getTransactionRules());
    }

    // Wywoływana automatycznie przez validateRecord() przy sprawdzaniu każdego rekordu.
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