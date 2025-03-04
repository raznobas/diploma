<?php

use App\Http\Controllers\LeadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/lead/store', [LeadController::class, 'storeApi'])
    ->middleware('auth:sanctum');

