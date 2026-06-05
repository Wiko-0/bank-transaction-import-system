<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API routes for managing bank transaction imports
Route::get('/imports', [ImportController::class, 'index']);
Route::post('/imports', [ImportController::class, 'store']);
Route::get('/imports/{id}', [ImportController::class, 'show']);