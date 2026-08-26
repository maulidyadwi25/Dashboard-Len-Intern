<?php

namespace App\Http\Controllers;

use App\Services\CurrencyService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\JsonResponse;

class InternSheetController extends Controller
{
    /**
     * Endpoint API untuk data dashboard monitoring & kurs valas
     */
    public function index(GoogleSheetsService $sheets, CurrencyService $currency): JsonResponse
    {
        try {
            $data = $sheets->getProjectDashboard();
            $rates = $currency->getRates();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data dashboard berhasil diambil',
                'data'    => [
                    'sheetData'     => $data,
                    'exchangeRates' => $rates,
                ],
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat data dashboard: ' . $e->getMessage(),
                'data'    => null,
            ], 500);
        }
    }
}