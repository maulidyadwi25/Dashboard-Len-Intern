<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\InternSheetController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProjectDashboardController;
use App\Models\MasterProject;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint: GET / POST http://localhost:8000/api/budget-dashboard
Route::get('/budget-dashboard', [InternSheetController::class, 'index']);
Route::post('/budget-dashboard', [InternSheetController::class, 'update']);

Route::get('/currency/rates', [CurrencyController::class, 'getRates']);

// Endpoint Procurement
Route::get('/procurement', [ProcurementController::class, 'index']);
Route::post('/procurement/sync', [ProcurementController::class, 'sync']);
Route::patch('/procurement/{id}/start-date', [ProcurementController::class, 'updateStartDate']);
Route::patch('/procurement/{id}/start-date', [ProcurementController::class, 'updateStartDate']);

// 3-Tier Multi-Spreadsheet Project Dashboard
Route::get('/projects/{id}/dashboard', [ProjectDashboardController::class, 'show']);
Route::post('/projects/{id}/sync', [ProjectDashboardController::class, 'sync']);

// Cronjob Sync Route
Route::get('/cron/sync-sheets', function (Request $request, GoogleSheetsService $sheets) {
    $secret = env('CRON_SECRET');

    // Validasi secret jika CRON_SECRET di-set di .env
    if ($secret && $request->query('secret') !== $secret) {
        return response()->json(['message' => 'Unauthorized access'], 401);
    }

    try {
        // Ambil semua proyek yang memiliki spreadsheet aktif di Tier 2
        $projects = MasterProject::whereHas('spreadsheets', function ($q) {
            $q->where('is_active', true);
        })->get();

        if ($projects->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tidak ada project aktif yang perlu di-sync.',
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        foreach ($projects as $project) {
            $sheets->syncProject($project);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Google Sheets successfully synced to database!',
            'synced_projects' => $projects->count(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});
