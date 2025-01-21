<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/lead/store', [LeadController::class, 'api_store'])
    ->middleware('auth:sanctum');

Route::post('/events/call', [CallController::class, 'handleCallEvent']);
Route::post('/events/summary', [CallController::class, 'handleCallSummary']);
