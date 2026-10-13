<?php

use App\Support\WbsDateHelper;

test('maps dates to W1-W4 using the procurement convention', function () {
    expect(WbsDateHelper::resolveWeekInfo('2026-01-05'))->toMatchArray([
        'year' => 2026,
        'month' => 1,
        'week' => 1,
    ]);

    expect(WbsDateHelper::resolveWeekInfo('2026-01-12'))->toMatchArray([
        'year' => 2026,
        'month' => 1,
        'week' => 2,
    ]);

    expect(WbsDateHelper::resolveWeekInfo('2026-01-20'))->toMatchArray([
        'year' => 2026,
        'month' => 1,
        'week' => 3,
    ]);

    expect(WbsDateHelper::resolveWeekInfo('2026-01-28'))->toMatchArray([
        'year' => 2026,
        'month' => 1,
        'week' => 4,
    ]);
});

test('converts input dates using the Asia/Jakarta timezone', function () {
    $date = new DateTimeImmutable('2026-03-31T16:30:00', new DateTimeZone('Asia/Jakarta'));

    expect(WbsDateHelper::resolveWeekInfo($date))->toMatchArray([
        'year' => 2026,
        'month' => 3,
        'week' => 4,
    ]);
});
