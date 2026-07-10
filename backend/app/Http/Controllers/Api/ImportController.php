<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Services\TransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    protected $importService;

    // Inject the import service into the controller
    public function __construct(TransactionImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * GET /api/imports - Get a list of all file imports.
     */
    public function index(): JsonResponse
    {
        //return response()->json(Import::orderBy('created_at', 'desc')->get());
        $imports = Import::with('logs')->orderBy('created_at', 'desc')->get();

        return response()->json($imports);
    }

    /**
     * POST /api/imports - Upload and process a bank transaction file.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json,xml,txt'
        ]);

        $import = $this->importService->import($request->file('file'));

        return response()->json($import, 201);
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
