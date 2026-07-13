<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Jobs\ProcessTransactionImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    protected $importService;

    // Usunęcie wstrzykiwania serwisu z konstruktora, bo teraz to Job będzie go używał
    public function __construct() {}

    /**
     * GET /api/imports - Get a list of all file imports.
     */
    public function index(): JsonResponse
    {
        $imports = Import::with('logs')->orderBy('created_at', 'desc')->get();
        return response()->json($imports);
    }

    /**
     * POST /api/imports - Upload a file and queue it for asynchronous processing.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json,xml,txt'
        ]);

        try {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();

            // Zapisuje plik strumieniowo na dysku serwera w prywatnym katalogu
            $storedPath = $file->storeAs('imports', uniqid() . '_' . $fileName);

            //Tworzy rekord importu ze statusem 'pending
            $import = Import::create([
                'file_name' => $fileName,
                'total_records' => 0,
                'status' => 'failed'
            ]);

            //Wrzuca zadanie na kolejkę
            ProcessTransactionImport::dispatch($import->id, $storedPath);

            //zwracamy status 202
            return response()->json($import, Response::HTTP_ACCEPTED);

        } catch (\Throwable $e) {
            return response()->json([
                'error_real_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * GET /api/imports/{id} - Get specific import details including error logs.
     */
    public function show($id): JsonResponse
    {
        $import = Import::with('logs')->findOrFail($id);
        return response()->json($import);
    }
}