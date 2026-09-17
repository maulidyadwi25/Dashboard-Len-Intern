<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/projects', [ProjectController::class, 'getProjects']);
Route::get('/project-monitoring', [ProjectController::class, 'getMonitoringData']);
Route::get('/procurement-monitoring', [ProjectController::class, 'getProcurementData']);
Route::match(['get', 'post'], '/sheets/sync', [ProjectController::class, 'syncSheets']);
Route::get('/database/stats', [ProjectController::class, 'getDatabaseStats']);
Route::post('/description', [ProjectController::class, 'updateDescription']);
Route::post('/procurements/update', [ProjectController::class, 'updateProcurement']);