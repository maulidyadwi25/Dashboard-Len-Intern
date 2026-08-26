<?php

use App\Http\Controllers\InternSheetController;
use Illuminate\Support\Facades\Route;

// Endpoint: GET http://localhost:8000/api/budget-dashboard
Route::get('/budget-dashboard', [InternSheetController::class, 'index']);

Route::get('/currency/rates', [CurrencyController::class, 'getRates']);