<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\TransactionImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProcessTransactionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    protected $importId;
    protected $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(int $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job (Działa w tle jako asynchroniczny Worker).
     */
    public function handle(TransactionImportService $importService): void
    {
        $importRecord = Import::find($this->importId);
        if (!$importRecord) {
            return;
        }

        //Aktualizuje status w bazie, że właśnie mieli plik
        //$importRecord->update(['status' => 'processing']);

        $fullPath = Storage::path($this->filePath);

        if (!file_exists($fullPath)) {
            $importRecord->update(['status' => 'failed']);
            return;
        }

        // Ponieważ nasz serwis oczekuje obiektu pliku z Laravela (żeby wyciągnąć getRealPath() itp.),
        // tworzymy sztuczny obiekt UploadedFile na podstawie bezpiecznie zapisanego pliku na dysku.
        $fakeUploadedFile = new UploadedFile(
            $fullPath,
            $importRecord->file_name,
            null,
            null,
            true
        );

        try {
            // Odpala proces strumieniowy z yield i chunkami
            $importService->import($fakeUploadedFile, $this->importId);
        } catch (\Throwable $e) {
            // W razie błędu w tle, oznacza import jako failed
            $importRecord->update(['status' => 'failed']);
            throw $e; // Rzuca błąd dalej, żeby Laravel oznaczył Job jako 'failed' w bazie
        } finally {
            // usuwa plik z dysku
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }
}