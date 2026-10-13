<?php

use App\Services\GoogleSheetsService;

function wbsParserService(): GoogleSheetsService
{
    return new GoogleSheetsService;
}

/**
 * Build a WBS row with columns A..H plus 48 week cells starting at I.
 *
 * @param  array<int, float|int|string>  $weeksByOffset
 * @return array<int, mixed>
 */
function wbsSheetRow(
    mixed $no,
    mixed $milestone,
    mixed $activity,
    mixed $pic,
    mixed $divisi,
    mixed $nilai,
    mixed $bobot,
    mixed $ap,
    array $weeksByOffset = []
): array {
    $row = array_fill(0, 56, '');
    $row[0] = $no;
    $row[1] = $milestone;
    $row[2] = $activity;
    $row[3] = $pic;
    $row[4] = $divisi;
    $row[5] = $nilai;
    $row[6] = $bobot;
    $row[7] = $ap;

    foreach ($weeksByOffset as $offset => $value) {
        $row[8 + (int) $offset] = $value;
    }

    return $row;
}

function sampleWbsSheet(): array
{
    return [
        ['NO', 'Milestone', 'RINCIAN KEGIATAN (WBS)', 'PIC', 'DIVISI', 'NILAI KEGIATAN', '% BOBOT KEGIATAN', 'A/P'],
        [],
        wbsSheetRow('1', 'MS1', 'Pekerjaan Utama', 'Andi', 'ENG', 1000, 0.99, 'P', [0 => 99]),
        wbsSheetRow('1', 'MS1', 'Pekerjaan Utama', 'Andi', 'ENG', 1000, 0.99, 'A', [0 => 99]),
        wbsSheetRow('1.1', 'MS1', 'Pondasi', 'Budi', 'ENG', 500, 0.80, 'P', [0 => 80]),
        wbsSheetRow('1.1', 'MS1', 'Pondasi', 'Budi', 'ENG', 500, 0.80, 'A', [0 => 80]),
        wbsSheetRow('1.1.1', 'MS1', 'Galian', 'Citra', 'ENG', 200, 0.10, 'P', [0 => 10, 1 => 5]),
        wbsSheetRow('1.1.1', 'MS1', 'Galian', 'Citra', 'ENG', 200, 0.08, 'A', [0 => 10, 1 => 0]),
        wbsSheetRow('1.1.2', 'MS1', 'Urugan', 'Dedi', 'ENG', 300, 0.20, 'P', [0 => 20]),
        wbsSheetRow('1.1.2', 'MS1', 'Urugan', 'Dedi', 'ENG', 300, 0.15, 'A', [0 => 20]),
        wbsSheetRow('header', 'skip', 'skip', '', '', '', '', 'P'),
        wbsSheetRow('', '', '', '', '', '', '', ''),
        wbsSheetRow('2', 'MS2', 'Electrical', 'Eka', 'ELN', 400, 0.05, 'P', [4 => 5]),
        wbsSheetRow('2.1', 'MS2', 'Kabel', 'Eka', 'ELN', 400, 0.05, 'P', [4 => 5]),
        wbsSheetRow('2.1', 'MS2', 'Kabel', 'Eka', 'ELN', 400, 0.05, 'A', [5 => 5]),
        wbsSheetRow('3', 'MS3', 'Critical Path', 'Fani', 'QA', 100, 0.01, 'P', [0 => 1]),
        wbsSheetRow('3', 'MS3', 'Critical Path', 'Fani', 'QA', 100, 0.01, 'A', [3 => 1]),
    ];
}

test('wbs numbers resolve level and parent from segments', function () {
    $service = wbsParserService();

    expect($service->parseWbsNumber('1'))->toMatchArray(['wbs_no' => '1', 'parent_wbs_no' => null, 'level' => 1])
        ->and($service->parseWbsNumber('2.1'))->toMatchArray(['wbs_no' => '2.1', 'parent_wbs_no' => '2', 'level' => 2])
        ->and($service->parseWbsNumber('2.1.2'))->toMatchArray(['wbs_no' => '2.1.2', 'parent_wbs_no' => '2.1', 'level' => 3])
        ->and($service->parseWbsNumber('NO'))->toBeNull()
        ->and($service->parseWbsNumber(''))->toBeNull();
});

test('plan and actual rows with the same wbs_no become one item', function () {
    $items = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->keyBy('wbs_no');

    expect($items)->toHaveCount(7)
        ->and($items['1.1.1']['bobot_plan'])->toBe(36.5854)
        ->and($items['1.1.1']['bobot_real'])->toBe(0.0)
        ->and($items['1.1.1']['activity_name'])->toBe('Galian')
        ->and($items['1.1.1']['pic'])->toBe('Citra');
});

test('leaf weekly empty cells are skipped and zero is kept', function () {
    $item = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->firstWhere('wbs_no', '1.1.1');
    $plan = collect($item['weekly_values'])->where('type', 'plan')->keyBy('period_label');
    $actual = collect($item['weekly_values'])->where('type', 'actual')->keyBy('period_label');

    expect($plan)->toHaveCount(2)
        ->and($plan['Jan W1']['value'])->toBe(10.0)
        ->and($plan['Jan W2']['value'])->toBe(5.0)
        ->and($actual['Jan W2']['value'])->toBe(0.0)
        ->and($actual->has('Jan W3'))->toBeFalse();
});

test('parent bobot is rolled up from descendants instead of the spreadsheet parent cell', function () {
    $items = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->keyBy('wbs_no');

    expect($items['1.1']['bobot_plan'])->toBe(85.3659)
        ->and($items['1.1']['bobot_real'])->toBe(0.0)
        ->and($items['1']['bobot_plan'])->toBe(85.3659)
        ->and($items['1']['bobot_real'])->toBe(0.0);
});

test('leaf bobot is normalized from plan weekly sums and ignores actual and column g values', function () {
    $rows = [
        wbsSheetRow('1', 'MS1', 'Parent', '', '', '', 999, 'P', [0 => 100]),
        wbsSheetRow('1.1', 'MS1', 'One', '', '', '', 888, 'P', [0 => 0.5]),
        wbsSheetRow('1.1', 'MS1', 'One', '', '', '', 777, 'A', [0 => 500]),
        wbsSheetRow('1.2', 'MS1', 'Two', '', '', '', 666, 'P', [0 => 0.3]),
        wbsSheetRow('1.3', 'MS1', 'Three', '', '', '', 555, 'P', [0 => 0.2]),
    ];

    $items = collect(wbsParserService()->parseWbsRows($rows))->keyBy('wbs_no');

    expect($items['1.1']['bobot_plan'])->toBe(50.0)
        ->and($items['1.2']['bobot_plan'])->toBe(30.0)
        ->and($items['1.3']['bobot_plan'])->toBe(20.0)
        ->and($items['1']['bobot_plan'])->toBe(100.0);
});

test('blank plan weekly cells are ignored and zero plan values remain valid', function () {
    $rows = [
        wbsSheetRow('1.1', 'MS1', 'Blank and value', '', '', '', 100, 'P', [0 => '', 1 => 2]),
        wbsSheetRow('1.2', 'MS1', 'Explicit zero', '', '', '', 0, 'P', [0 => 0]),
    ];

    $items = collect(wbsParserService()->parseWbsRows($rows))->keyBy('wbs_no');

    expect($items['1.1']['bobot_plan'])->toBe(100.0)
        ->and($items['1.2']['bobot_plan'])->toBe(0.0)
        ->and(collect($items['1.1']['weekly_values'])->where('type', 'plan')->pluck('value')->all())->toBe([2.0])
        ->and(collect($items['1.2']['weekly_values'])->where('type', 'plan')->pluck('value')->all())->toBe([0.0]);
});

test('all leaf plan weekly sums of zero produce zero bobot without error', function () {
    $rows = [
        wbsSheetRow('1.1', 'MS1', 'Zero one', '', '', '', 75, 'P', [0 => 0]),
        wbsSheetRow('1.2', 'MS1', 'Zero two', '', '', '', 25, 'P', [0 => '']),
    ];

    $items = collect(wbsParserService()->parseWbsRows($rows))->keyBy('wbs_no');

    expect($items['1.1']['bobot_plan'])->toBe(0.0)
        ->and($items['1.2']['bobot_plan'])->toBe(0.0);
});

test('adding a leaf renormalizes existing leaf bobot', function () {
    $service = wbsParserService();
    $twoLeaves = [
        wbsSheetRow('1.1', 'MS1', 'One', '', '', '', '', 'P', [0 => 1]),
        wbsSheetRow('1.2', 'MS1', 'Two', '', '', '', '', 'P', [0 => 1]),
    ];
    $threeLeaves = [
        ...$twoLeaves,
        wbsSheetRow('1.3', 'MS1', 'Three', '', '', '', '', 'P', [0 => 2]),
    ];

    $before = collect($service->parseWbsRows($twoLeaves))->keyBy('wbs_no');
    $after = collect($service->parseWbsRows($threeLeaves))->keyBy('wbs_no');

    expect($before['1.1']['bobot_plan'])->toBe(50.0)
        ->and($after['1.1']['bobot_plan'])->toBe(25.0)
        ->and($after['1.2']['bobot_plan'])->toBe(25.0)
        ->and($after['1.3']['bobot_plan'])->toBe(50.0);
});

test('parent weekly values are the sum of descendants', function () {
    $items = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->keyBy('wbs_no');
    $level2Plan = collect($items['1.1']['weekly_values'])->firstWhere('period_label', 'Jan W1');
    $level1Plan = collect($items['1']['weekly_values'])->where('type', 'plan')->firstWhere('period_label', 'Jan W1');

    expect($level2Plan['value'])->toBe(30.0)
        ->and($level1Plan['value'])->toBe(30.0);
});

test('leaf and parent schedules use earliest and latest valued weeks', function () {
    $items = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->keyBy('wbs_no');

    expect($items['1.1.1']['plan_start_period'])->toBe('Jan W1')
        ->and($items['1.1.1']['plan_end_period'])->toBe('Jan W2')
        ->and($items['1.1']['plan_start_period'])->toBe('Jan W1')
        ->and($items['1.1']['plan_end_period'])->toBe('Jan W2')
        ->and($items['2.1']['plan_start_period'])->toBe('Feb W1')
        ->and($items['2.1']['actual_start_period'])->toBe('Feb W2')
        ->and($items['2']['plan_start_period'])->toBe('Feb W1')
        ->and($items['2']['actual_end_period'])->toBe('Feb W2');
});

test('status uses weekly ordinals not string comparison', function () {
    $service = wbsParserService();
    $items = collect($service->parseWbsRows(sampleWbsSheet()))->keyBy('wbs_no');

    expect($items['1.1.1']['status'])->toBe('on_track')
        ->and($items['2.1']['status'])->toBe('overdue')
        ->and($items['3']['status'])->toBe('critical')
        ->and($service->resolveWbsStatus('Jan W4', 'Jan W4', 'Feb W1', 'Feb W1'))->toBe('overdue')
        ->and($service->resolveWbsStatus('Jan W4', 'Jan W4', 'Feb W2', 'Feb W2'))->toBe('critical');
});

test('column I maps to January W1 and column BD maps to December W4', function () {
    $periods = wbsParserService()->wbsWeekPeriods(2026);

    expect($periods)->toHaveCount(48)
        ->and($periods[0])->toMatchArray(['column' => 8, 'month' => 1, 'week' => 1, 'label' => 'Jan W1'])
        ->and($periods[47])->toMatchArray(['column' => 55, 'month' => 12, 'week' => 4, 'label' => 'Dec W4']);
});

test('header and invalid rows are ignored', function () {
    $wbsNos = collect(wbsParserService()->parseWbsRows(sampleWbsSheet()))->pluck('wbs_no')->all();

    expect($wbsNos)->not->toContain('NO')
        ->and($wbsNos)->not->toContain('header')
        ->and($wbsNos)->toContain('1.1.1');
});
