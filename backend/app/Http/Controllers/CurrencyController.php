<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function getRates(CurrencyService $currencyService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $currencyService->getRates(),
        ]);
    }
}