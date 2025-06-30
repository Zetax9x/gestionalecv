<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes per il gestionale (future implementazioni)
Route::prefix('v1')->group(function () {
    // Dashboard stats
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard/stats', function () {
            return response()->json(['message' => 'API in sviluppo']);
        });
    });
});