<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\InternSheetController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\ProgressRevenueController;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\WbsController;
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

// Project Dashboard & Sync
Route::get('/projects', [ProjectDashboardController::class, 'index']);
Route::get('/projects/{id}/dashboard', [ProjectDashboardController::class, 'show']);
Route::post('/projects/{id}/sync', [ProjectDashboardController::class, 'sync']);

// Progress & Revenue
Route::get('/projects/{id}/progress-revenue', [ProgressRevenueController::class, 'show']);

// WBS & Gantt
Route::get('/projects/{id}/wbs', [WbsController::class, 'show']);
Route::get('/projects/{id}/wbs/weekly', [WbsController::class, 'weekly']);
Route::get('/projects/{id}/wbs/week-summary', [WbsController::class, 'weekSummary']);
Route::get('/projects/{id}/wbs/ai-summary', [WbsController::class, 'getAiSummary']);
Route::post('/projects/{id}/wbs/ai-summary', [WbsController::class, 'generateAiSummary']);
Route::put('/projects/{id}/wbs/ai-summary', [WbsController::class, 'updateAiSummary']);
Route::put('/projects/{id}/wbs/{wbs_no}/progress', [WbsController::class, 'updateProgress']);
Route::post('/projects/{id}/wbs/sync', [WbsController::class, 'sync']);

// Cronjob Sync Route
Route::get('/cron/sync-sheets', function (Request $request, GoogleSheetsService $sheets) {
    $secret = env('CRON_SECRET');

    // Validasi secret jika CRON_SECRET di-set di .env
    if ($secret && $request->query('secret') !== $secret) {
        return response()->json(['message' => 'Unauthorized access'], 401);
    }

    try {
        $projects = MasterProject::where('is_active', true)->get();

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
