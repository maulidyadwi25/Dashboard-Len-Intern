<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Durasi cache kurs mata uang dalam detik (1 jam)
     */
    protected int $cacheTtl = 3600;

    /**
     * Kurs default (fallback) ke IDR jika koneksi CDN API bermasalah/offline
     */
    protected array $fallbackRates = [
        'IDR' => 1,
        'USD' => 17600,
        'EUR' => 20400,
        'SGD' => 13900,
    ];

    /**
     * Ambil data kurs 4 mata uang (IDR, EUR, USD, SGD) dari CDN API dengan Caching
     */
    public function getExchangeRates(): array
    {
        return Cache::remember('cdn_currency_exchange_rates_v2', $this->cacheTtl, function () {
            try {
                // Memanggil CDN API resmi fawazahmed0 currency-api via jsdelivr (USD base)
                $url = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json';
                $response = Http::timeout(4)->get($url);

                if ($response->successful()) {
                    $json = $response->json();
                    $rates = $json['usd'] ?? [];

                    $usdToIdr = (float)($rates['idr'] ?? $this->fallbackRates['USD']);
                    $usdToEur = (float)($rates['eur'] ?? 0.86);
                    $usdToSgd = (float)($rates['sgd'] ?? 1.26);

                    // Menghitung konversi ke IDR untuk EUR dan SGD
                    $eurToIdr = $usdToEur > 0 ? ($usdToIdr / $usdToEur) : $this->fallbackRates['EUR'];
                    $sgdToIdr = $usdToSgd > 0 ? ($usdToIdr / $usdToSgd) : $this->fallbackRates['SGD'];

                    return [
                        'success' => true,
                        'source'  => 'cdn.jsdelivr.net (@fawazahmed0/currency-api)',
                        'date'    => $json['date'] ?? date('Y-m-d'),
                        'to_idr'  => [
                            'IDR' => 1,
                            'USD' => round($usdToIdr, 2),
                            'EUR' => round($eurToIdr, 2),
                            'SGD' => round($sgdToIdr, 2),
                        ],
                        'from_idr' => [
                            'IDR' => 1,
                            'USD' => $usdToIdr > 0 ? round(1 / $usdToIdr, 8) : 0,
                            'EUR' => $eurToIdr > 0 ? round(1 / $eurToIdr, 8) : 0,
                            'SGD' => $sgdToIdr > 0 ? round(1 / $sgdToIdr, 8) : 0,
                        ],
                        'symbols' => [
                            'IDR' => 'Rp',
                            'USD' => '$',
                            'EUR' => '€',
                            'SGD' => 'S$',
                        ],
                        'labels' => [
                            'IDR' => 'Indonesian Rupiah',
                            'USD' => 'US Dollar',
                            'EUR' => 'Euro',
                            'SGD' => 'Singapore Dollar',
                        ]
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Currency CDN API timeout/failed, fallback used: ' . $e->getMessage());
            }

            // Fallback jika API eksternal gagal diakses
            return [
                'success' => false,
                'source'  => 'fallback',
                'date'    => date('Y-m-d'),
                'to_idr'  => $this->fallbackRates,
                'from_idr' => [
                    'IDR' => 1,
                    'USD' => round(1 / $this->fallbackRates['USD'], 8),
                    'EUR' => round(1 / $this->fallbackRates['EUR'], 8),
                    'SGD' => round(1 / $this->fallbackRates['SGD'], 8),
                ],
                'symbols' => [
                    'IDR' => 'Rp',
                    'USD' => '$',
                    'EUR' => '€',
                    'SGD' => 'S$',
                ],
                'labels' => [
                    'IDR' => 'Indonesian Rupiah',
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'SGD' => 'Singapore Dollar',
                ]
            ];
        });
    }
}