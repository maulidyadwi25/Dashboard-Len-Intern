<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\AiRecommendationController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('/projects', [ProjectController::class, 'getProjects']);
Route::get('/project-monitoring', [ProjectController::class, 'getMonitoringData']);
Route::get('/procurement-monitoring', [ProjectController::class, 'getProcurementData']);
Route::match(['get', 'post'], '/sheets/sync', [ProjectController::class, 'syncSheets']);
Route::get('/database/stats', [ProjectController::class, 'getDatabaseStats']);
Route::post('/description', [ProjectController::class, 'updateDescription']);
Route::post('/procurements/update', [ProjectController::class, 'updateProcurement']);
Route::post('/procurements/termin', [ProjectController::class, 'updateProcurementTermin']);
Route::post('/ai/recommendation', [AiRecommendationController::class, 'recommendation']);
Route::get('/recommendations', [RecommendationController::class, 'show']);
Route::post('/recommendations', [RecommendationController::class, 'save']);
Route::delete('/recommendations', [RecommendationController::class, 'destroy']);
Route::delete('/procurements', [ProjectController::class, 'destroyProcurement']);