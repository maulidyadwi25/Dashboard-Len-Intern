<?php

use App\Services\CurrencyService;

it('returns only the IDR baseline when BI response is unavailable', function () {
    $service = new CurrencyService();

    expect($service->getRates())->toBe([
        'IDR' => 1,
    ]);
});
