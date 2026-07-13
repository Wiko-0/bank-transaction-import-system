<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Services\TransactionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(TransactionImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index(): JsonResponse
    {
        $imports = Import::with('logs')->orderBy('created_at', 'desc')->get();
        return response()->json($imports);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json,xml,txt'
        ]);

        try {
            set_time_limit(0);

            $import = $this->importService->import($request->file('file'));

            $import->refresh();

            return response()->json($import, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return response()->json([
                'error_real_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $import = Import::with('logs')->findOrFail($id);
        return response()->json($import);
    }
}