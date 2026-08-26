<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Cache;

class SyncExchangeRates extends Command
{
    protected $signature = 'rates:sync';
    protected $description = 'Sinkronisasi nilai kurs valas harian';

    public function handle(CurrencyService $service)
    {
        $this->info('Memulai sinkronisasi kurs...');
        
        Cache::forget('app_currency_rates_v1');
        $result = $service->fetchFreshRates();

        $this->info("Sumber Data : " . ($result['source'] ?? 'Unknown'));
        $this->info("Waktu Update: " . ($result['updated_at'] ?? '-'));
        
        if (isset($result['rates']['USD']) && $result['rates']['USD'] > 0) {
            $this->table(
                ['Currency', 'Kurs ke IDR'],
                [
                    ['USD', 'Rp ' . number_format(1 / $result['rates']['USD'], 2, ',', '.')],
                    ['EUR', 'Rp ' . number_format(1 / $result['rates']['EUR'], 2, ',', '.')],
                    ['SGD', 'Rp ' . number_format(1 / $result['rates']['SGD'], 2, ',', '.')],
                ]
            );
        } else {
            $this->warn('Hanya mata uang IDR yang tersedia saat ini.');
        }

        return Command::SUCCESS;
    }
}