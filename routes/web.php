<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'FitNEase Operations Service',
        'status' => 'running',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});

// Health check endpoint for Docker health checks
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'fitnease-operations',
        'timestamp' => now()
    ]);
});
