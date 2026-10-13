<?php

use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Log;

function progressRevenueFixture(): array
{
    return require __DIR__.'/../Fixtures/progress_revenue_sheet.php';
}

function progressRevenueService(): GoogleSheetsService
{
    return new GoogleSheetsService;
}

test('profile is resolved by normalized label, not by cell address', function () {
    $profile = progressRevenueService()->parseProgressRevenueProfile(progressRevenueFixture());

    // Extra whitespace and mixed case in the sheet still match.
    expect($profile['project_name'])->toBe('Refurbishment KRI Sigma')
        ->and($profile['project_code'])->toBe('1LC-522UD019')
        ->and($profile['client_name'])->toBe('PT LEN Industri')
        ->and($profile['project_lead_name'])->toBe('SETIAJI')
        ->and($profile['report_owner_name'])->toBe('KURNIAWAN')
        ->and($profile['contract_value'])->toBe(125800000000.0);
});

test('profile percentages stay fractions and D8 is never read', function () {
    $profile = progressRevenueService()->parseProgressRevenueProfile(progressRevenueFixture());

    expect($profile['lkp_prev_year'])->toBe(0.1234)
        ->and($profile['progress_achieved'])->toBe(0.1500)
        ->and($profile['annual_deviation_target'])->toBe(-0.0021)
        ->and($profile['target_progress'])->toBe(0.9800)
        ->and($profile['target_lkp'])->toBe(0.9750);

    // D8 holds "SHOULD BE IGNORED D8" and must never leak into the profile.
    expect(json_encode($profile))->not->toContain('SHOULD BE IGNORED');
});

test('weekly header is located dynamically and parsed from cell values', function () {
    $periods = progressRevenueService()->parseProgressRevenueWeekHeaders(progressRevenueFixture());

    // Sheet has headers through Feb W4 only; missing months (Mar..Dec + their W1-W4) are synthesized.
    expect($periods)->toHaveCount(49);

    // Baseline comes first, from the "Des 2025" cell.
    expect($periods[0])->toMatchArray([
        'label' => 'Des 2025',
        'year' => 2025,
        'month' => 12,
        'week' => null,
        'is_baseline' => true,
    ]);

    // Synthesized periods: Mar W1..Dec W4 appear after the present weeks, with column => null.
    $decW1 = null;
    foreach ($periods as $i => $p) {
        if ($p['label'] === 'Mar W1') {
            $decW1 = $i;
            break;
        }
    }
    expect($decW1 !== null)->toBe(true);
    expect($periods[$decW1])->toMatchArray([
        'label' => 'Mar W1',
        'month' => 3,
        'week' => 1,
        'year' => 2026,
        'is_baseline' => false,
        'column' => null,
    ]);

    $decW4 = null;
    foreach ($periods as $i => $p) {
        if ($p['label'] === 'Dec W4') {
            $decW4 = $i;
            break;
        }
    }
    expect($decW4 !== null)->toBe(true);
    expect($periods[$decW4])->toMatchArray([
        'label' => 'Dec W4',
        'month' => 12,
        'week' => 4,
        'year' => 2026,
        'is_baseline' => false,
        'column' => null,
    ]);

    // All present weeks come before synthesized ones.
    expect($periods[1])->toMatchArray(['label' => 'Jan W1', 'month' => 1, 'week' => 1, 'year' => 2026, 'is_baseline' => false])
        ->and($periods[4])->toMatchArray(['label' => 'Jan W4', 'month' => 1, 'week' => 4])
        ->and($periods[5])->toMatchArray(['label' => 'Feb W1', 'month' => 2, 'week' => 1])
        ->and($periods[8])->toMatchArray(['label' => 'Feb W4', 'month' => 2, 'week' => 4]);

    // Baseline is never mistaken for a 2026 period.
    expect(collect($periods)->where('is_baseline', true)->pluck('year')->all())->toBe([2025]);
});

test('indicators are matched by label regardless of row order', function () {
    $service = progressRevenueService();
    $rows = progressRevenueFixture();
    $periods = $service->parseProgressRevenueWeekHeaders($rows);

    // Sheet order 18-24 deliberately out of canonical order; parser must not be row-dependent.
    $indicators = $service->parseProgressRevenueIndicators($rows, $periods, 16);

    expect(array_keys($indicators))->toBe([
        'deviation_vs_rkap',
        'rkap_weekly_plan',
        'rkap_weekly_cumulative',
        'project_weekly_plan',
        'project_weekly_cumulative',
        'project_weekly_actual',
        'project_actual_cumulative',
    ]);
});

test('WBS rows from row 22 onward are never parsed', function () {
    $service = progressRevenueService();
    $rows = progressRevenueFixture();
    $periods = $service->parseProgressRevenueWeekHeaders($rows);
    $indicators = $service->parseProgressRevenueIndicators($rows, $periods, 16);

    $serialized = json_encode($indicators);

    // These live on rows 22-23 inside the WBS block.
    expect($serialized)->not->toContain('Pekerjaan Pondasi')
        ->and($serialized)->not->toContain('Bobot');
});

test('blank cells become null, zero stays zero, negatives are preserved', function () {
    $service = progressRevenueService();
    $rows = progressRevenueFixture();
    $periods = $service->parseProgressRevenueWeekHeaders($rows);
    $indicators = $service->parseProgressRevenueIndicators($rows, $periods, 16);

    $rkap = $indicators['rkap_weekly_plan']['values'];

    expect($rkap['Des 2025'])->toBe(0.005)
        ->and($rkap['Jan W1'])->toBe(0.0050)
        // Blank cell in the fixture.
        ->and($rkap['Feb W2'])->toBeNull()
        // Explicit zero must not become null.
        ->and($indicators['deviation_vs_rkap']['values']['Jan W4'])->toBe(0.0)
        // Negative percentage preserved as-is.
        ->and($indicators['deviation_vs_rkap']['values']['Feb W1'])->toBe(-0.003)
        // Blank on an indicator row that sits below the WBS block.
        ->and($indicators['project_actual_cumulative']['values']['Jan W3'])->toBeNull();
});

test('a missing indicator warns and does not stop the remaining indicators', function () {
    Log::shouldReceive('warning')
        ->atLeast()->once()
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'Missing indicator labels')
            && in_array('deviation_vs_rkap', $context['missing_indicator_keys'] ?? [], true));

    Log::shouldReceive('info')->zeroOrMoreTimes();

    $service = progressRevenueService();
    $rows = progressRevenueFixture();

    // Drop the Deviasi row (sheet row 18, index 17).
    unset($rows[17]);

    $periods = $service->parseProgressRevenueWeekHeaders($rows);
    $indicators = $service->parseProgressRevenueIndicators($rows, $periods, 16);

    expect($indicators)->not->toHaveKey('deviation_vs_rkap')
        ->and($indicators)->toHaveCount(6)
        ->and($indicators['rkap_weekly_plan']['values']['Jan W1'])->toBe(0.0050);
});

test('missing profile labels warn without failing the whole parse', function () {
    Log::shouldReceive('warning')
        ->atLeast()->once()
        ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'Missing profile labels')
            && in_array('target_lkp', $context['missing_fields'] ?? [], true));

    $rows = progressRevenueFixture();
    unset($rows[12]); // "Target % LKP Des 2026"

    $profile = progressRevenueService()->parseProgressRevenueProfile($rows);

    expect($profile['target_lkp'])->toBeNull()
        ->and($profile['target_progress'])->toBe(0.9800)
        ->and($profile['client_name'])->toBe('PT LEN Industri');
});

test('cleanProgressValue keeps fractions, zero and nulls untouched', function () {
    $service = progressRevenueService();

    expect($service->cleanProgressValue(0.018))->toBe(0.018)
        ->and($service->cleanProgressValue('0.0005'))->toBe(0.0005)
        ->and($service->cleanProgressValue(0))->toBe(0.0)
        ->and($service->cleanProgressValue(-0.0125))->toBe(-0.0125)
        ->and($service->cleanProgressValue(''))->toBeNull()
        ->and($service->cleanProgressValue(null))->toBeNull()
        ->and($service->cleanProgressValue('  '))->toBeNull()
        ->and($service->cleanProgressValue('-'))->toBeNull()
        ->and($service->cleanProgressValue('n/a'))->toBeNull();
});
