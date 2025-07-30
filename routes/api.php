<?php
// routes/api.php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PolygonController;
use App\Http\Controllers\API\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::apiResource('polygons', PolygonController::class);
    Route::apiResource('forest-polygons', ForestPolygonController::class)
    ->parameters(['forest-polygons' => 'uuid']);

    Route::get('reports', [ReportController::class, 'generate']);
    Route::post('forest-polygons/bulk-import', [ForestPolygonController::class, 'bulkImport']);
    Route::get('forest-polygons-export', [ForestPolygonController::class, 'export']);
    Route::get('forest-polygons-statistics', [ForestPolygonController::class, 'statistics']);
});
Route::get('/test', function () {
    return response()->json(['message' => 'API routes are working!']);

});

Route::post('/test-login', function (Request $request) {
    return response()->json([
        'message' => 'Login endpoint working',
        'data' => $request->all()
    ]);
});
