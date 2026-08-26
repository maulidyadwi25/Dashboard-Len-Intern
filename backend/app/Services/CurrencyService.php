<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    private const CACHE_KEY = 'app_currency_rates_v1';
    private const LAST_GOOD_KEY = 'app_currency_last_good_rate';

    public function getRates(): array
    {
        return Cache::remember(self::CACHE_KEY, 14400, function () {
            return $this->fetchFreshRates();
        });
    }

    public function fetchFreshRates(): array
    {
        // Tier 1: FloatRates (Mendekati ~17.700)
        $floatRates = $this->fetchFromFloatRates();
        if (!empty($floatRates) && isset($floatRates['USD'])) {
            $data = [
                'source' => 'FloatRates (Live Spot Market ~17.7k)',
                'updated_at' => now()->toIso8601String(),
                'rates' => $floatRates,
            ];
            Cache::forever(self::LAST_GOOD_KEY, $data);
            return $data;
        }

        // Tier 2: CDN Currency API (Cadangan 1)
        $cdnRates = $this->fetchFromCdnApi();
        if (!empty($cdnRates) && isset($cdnRates['USD'])) {
            $data = [
                'source' => 'CDN Currency API',
                'updated_at' => now()->toIso8601String(),
                'rates' => $cdnRates,
            ];
            Cache::forever(self::LAST_GOOD_KEY, $data);
            return $data;
        }

        // Tier 3: European Central Bank via Frankfurter (Cadangan 2)
        $ecbRates = $this->fetchFromFrankfurterEcb();
        if (!empty($ecbRates) && isset($ecbRates['USD'])) {
            $data = [
                'source' => 'European Central Bank (Frankfurter)',
                'updated_at' => now()->toIso8601String(),
                'rates' => $ecbRates,
            ];
            Cache::forever(self::LAST_GOOD_KEY, $data);
            return $data;
        }

        // Tier 4: Last Known Good
        $lastGood = Cache::get(self::LAST_GOOD_KEY);
        if ($lastGood) {
            $lastGood['stale'] = true;
            return $lastGood;
        }

        // Tier 5: Safe Fallback
        return [
            'source' => 'Base IDR Only',
            'updated_at' => now()->toIso8601String(),
            'rates' => ['IDR' => 1],
            'is_fallback' => true,
        ];
    }

    private function fetchFromCdnApi(): ?array
    {
        try {
            $usdRes = Http::timeout(3)->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json');
            $eurRes = Http::timeout(3)->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/eur.json');
            $sgdRes = Http::timeout(3)->get('https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/sgd.json');

            if ($usdRes->successful() && $eurRes->successful()) {
                $usdToIdr = (float) ($usdRes->json()['usd']['idr'] ?? 0);
                $eurToIdr = (float) ($eurRes->json()['eur']['idr'] ?? 0);
                $sgdToIdr = (float) ($sgdRes->json()['sgd']['idr'] ?? 0);

                if ($usdToIdr > 0) {
                    return [
                        'IDR' => 1,
                        'USD' => 1 / $usdToIdr,
                        'EUR' => $eurToIdr > 0 ? (1 / $eurToIdr) : 0,
                        'SGD' => $sgdToIdr > 0 ? (1 / $sgdToIdr) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // fail silently to fallback
        }

        return null;
    }
    /**
     * Mengambil kurs resmi European Central Bank via Frankfurter
     */
    private function fetchFromFrankfurterEcb(): ?array
    {
        try {
            // Ambil kurs EUR dan USD ke IDR sekaligus
            $resEur = Http::timeout(3)->get('https://api.frankfurter.app/latest?from=EUR&to=IDR,USD,SGD');
            
            if ($resEur->successful()) {
                $rates = $resEur->json()['rates'] ?? [];
                $eurToIdr = (float)($rates['IDR'] ?? 0);
                $eurToUsd = (float)($rates['USD'] ?? 0);
                $eurToSgd = (float)($rates['SGD'] ?? 0);

                if ($eurToIdr > 0 && $eurToUsd > 0) {
                    $usdToIdr = $eurToIdr / $eurToUsd;
                    $sgdToIdr = $eurToSgd > 0 ? ($eurToIdr / $eurToSgd) : 0;

                    return [
                        'IDR' => 1,
                        'USD' => 1 / $usdToIdr,
                        'EUR' => 1 / $eurToIdr,
                        'SGD' => $sgdToIdr > 0 ? (1 / $sgdToIdr) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // fail silently to next tier
        }

        return null;
    }

    /**
     * Mengambil kurs live dari ExchangeRate-API V4
     */
    private function fetchFromExchangeRateApi(): ?array
    {
        try {
            $usdRes = Http::timeout(3)->get('https://api.exchangerate-api.com/v4/latest/USD');
            
            if ($usdRes->successful()) {
                $rates = $usdRes->json()['rates'] ?? [];
                $usdToIdr = (float)($rates['IDR'] ?? 0);
                $usdToEur = (float)($rates['EUR'] ?? 0);
                $usdToSgd = (float)($rates['SGD'] ?? 0);

                if ($usdToIdr > 0) {
                    $eurToIdr = $usdToEur > 0 ? ($usdToIdr / $usdToEur) : 0;
                    $sgdToIdr = $usdToSgd > 0 ? ($usdToIdr / $usdToSgd) : 0;

                    return [
                        'IDR' => 1,
                        'USD' => 1 / $usdToIdr,
                        'EUR' => $eurToIdr > 0 ? (1 / $eurToIdr) : 0,
                        'SGD' => $sgdToIdr > 0 ? (1 / $sgdToIdr) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // fail silently to next tier
        }

        return null;
    }

    private function fetchFromBankIndonesia(): ?array
    {
        try {
            $xmlPayload = '<?xml version="1.0" encoding="utf-8"?>'
                . '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
                . '<soap:Body><getSubKursLokal3 xmlns="http://tempuri.org/" /></soap:Body>'
                . '</soap:Envelope>';

            $response = Http::withoutVerifying()
                ->timeout(4)
                ->withHeaders([
                    'User-Agent'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction'   => '"http://tempuri.org/getSubKursLokal3"',
                ])
                ->withBody($xmlPayload, 'text/xml')
                ->post('https://www.bi.go.id/biwebservice/wskursbi.asmx');

            if ($response->successful() && !str_contains($response->body(), '<!DOCTYPE HTML')) {
                return $this->parseBiXml($response->body());
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    private function parseBiXml(string $xmlBody): array
    {
        $rates = ['IDR' => 1];
        preg_match_all('/<Table[^>]*>(.*?)<\/Table>/s', $xmlBody, $tables);

        foreach ($tables[1] as $table) {
            preg_match('/<mts_subkurslokal>(.*?)<\/mts_subkurslokal>/', $table, $code);
            preg_match('/<beli_subkurslokal>(.*?)<\/beli_subkurslokal>/', $table, $beli);
            preg_match('/<jual_subkurslokal>(.*?)<\/jual_subkurslokal>/', $table, $jual);

            $currency = trim($code[1] ?? '');
            $buy = (float)str_replace(',', '', $beli[1] ?? 0);
            $sell = (float)str_replace(',', '', $jual[1] ?? 0);

            if (in_array($currency, ['USD', 'EUR', 'SGD']) && ($buy + $sell) > 0) {
                $rates[$currency] = 1 / (($buy + $sell) / 2);
            }
        }

        return $rates;
    }

    /**
     * Mengambil kurs dari FloatRates (Hasil: ~17.700 IDR / USD)
     */
    private function fetchFromFloatRates(): ?array
    {
        try {
            $response = Http::timeout(3)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get('https://www.floatrates.com/daily/usd.json');

            if ($response->successful()) {
                $data = $response->json();
                
                $usdToIdr = (float)($data['idr']['rate'] ?? 0);
                $usdToEur = (float)($data['eur']['rate'] ?? 0);
                $usdToSgd = (float)($data['sgd']['rate'] ?? 0);

                if ($usdToIdr > 0) {
                    $eurToIdr = $usdToEur > 0 ? ($usdToIdr / $usdToEur) : 0;
                    $sgdToIdr = $usdToSgd > 0 ? ($usdToIdr / $usdToSgd) : 0;

                    return [
                        'IDR' => 1,
                        'USD' => 1 / $usdToIdr,
                        'EUR' => $eurToIdr > 0 ? (1 / $eurToIdr) : 0,
                        'SGD' => $sgdToIdr > 0 ? (1 / $sgdToIdr) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // fail silently to next tier
        }

        return null;
    }
}