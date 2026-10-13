<?php

use App\Models\MasterProject;
use App\Models\WbsItem;
use App\Models\WbsProgressEntry;
use App\Models\WbsWeeklyValue;
use App\Services\GoogleSheetsService;
use Carbon\Carbon;

function wbsApiProject(array $attributes = []): MasterProject
{
    return MasterProject::create([
        'project_code' => 'WBS-API',
        'project_name' => 'WBS API Test',
        'spreadsheet_id' => 'test-spreadsheet-id',
        'progress_revenue_year' => 2026,
        ...$attributes,
    ]);
}

function wbsApiMonthLabel(int $month): string
{
    return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$month - 1];
}

function wbsApiItem(MasterProject $project, string $wbsNo, int $level, array $weeks = [], array $attributes = []): WbsItem
{
    return WbsItem::create([
        'master_project_id' => $project->id,
        'wbs_no' => $wbsNo,
        'parent_wbs_no' => $level === 1 ? null : implode('.', array_slice(explode('.', $wbsNo), 0, -1)),
        'level' => $level,
        'milestone' => 'MS1',
        'activity_name' => 'Activity '.$wbsNo,
        'pic' => 'Andi',
        'divisi' => 'ENG',
        'nilai_kegiatan' => 1000,
        'bobot_plan' => 0.10,
        'bobot_real' => 0.08,
        'plan_start_period' => 'Jan W1',
        'plan_end_period' => 'Jan W1',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W1',
        'durasi' => '1 minggu',
        'status' => 'on_track',
        'order_index' => 0,
        ...$attributes,
    ]);
}

function wbsApiWeekly(WbsItem $item, string $type, int $month, int $week, ?float $value): WbsWeeklyValue
{
    return WbsWeeklyValue::create([
        'progress_wbs_item_id' => $item->id,
        'type' => $type,
        'year' => 2026,
        'month' => $month,
        'week' => $week,
        'period_label' => wbsApiMonthLabel($month).' W'.$week,
        'value' => $value,
    ]);
}

function wbsApiFakeService(): GoogleSheetsService
{
    return new class extends GoogleSheetsService
    {
        public function __construct() {}

        public function wbsWeekPeriods(int $year = 2026): array
        {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $periods = [];

            for ($offset = 0; $offset < 48; $offset++) {
                $month = intdiv($offset, 4) + 1;
                $week = ($offset % 4) + 1;

                $periods[] = [
                    'column' => 8 + $offset,
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                    'label' => $labels[$month - 1].' W'.$week,
                    'key' => $year.'-'.$month.'-'.$week,
                    'ordinal' => ($year * 100) + (($month - 1) * 4) + $week,
                ];
            }

            return $periods;
        }

        public function syncWbsData(MasterProject $project): array
        {
            return [
                'status' => 'success',
                'sheet_title' => '2. Progress & Revenue',
                'wbs_count' => 7,
            ];
        }
    };
}

beforeEach(function () {
    $this->app->instance(GoogleSheetsService::class, wbsApiFakeService());
});

test('wbs endpoint returns items ordered by order_index without weekly values', function () {
    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], ['order_index' => 2]);
    wbsApiItem($project, '1', 1, [], ['order_index' => 0]);
    wbsApiItem($project, '1.1', 2, [], ['order_index' => 1]);
    wbsApiWeekly(WbsItem::where('wbs_no', '1')->firstOrFail(), 'plan', 1, 1, 30.0);

    $response = $this->getJson("/api/projects/{$project->id}/wbs");

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.project.id', $project->id)
        ->assertJsonPath('data.project.project_code', 'WBS-API')
        ->assertJsonPath('data.year', 2026);

    $wbsNos = array_column($response->json('data.items'), 'wbs_no');
    expect($wbsNos)->toBe(['1', '1.1', '1.1.1']);

    $root = $response->json('data.items.0');

    expect($root)->toHaveKeys([
        'id', 'wbs_no', 'parent_wbs_no', 'level', 'milestone', 'activity_name', 'pic', 'divisi',
        'nilai_kegiatan', 'bobot_plan', 'bobot_real',
        'plan_start_period', 'plan_end_period', 'actual_start_period', 'actual_end_period', 'actual_source',
        'durasi', 'status', 'order_index',
    ]);
    expect($root)->not->toHaveKey('weekly_values');

    expect($root['parent_wbs_no'])->toBeNull()
        ->and($root['level'])->toBe(1)
        ->and($root['nilai_kegiatan'])->toEqual(1000)
        ->and($root['bobot_plan'])->toEqualWithDelta(0.10, 0.0001);
});

test('summary counts leaf items only and progres is bobot_real over bobot_plan', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();

    wbsApiItem($project, '1', 1, [], ['bobot_plan' => 99.0, 'bobot_real' => 99.0, 'status' => 'critical']);
    wbsApiItem($project, '1.1', 2, [], ['bobot_plan' => 99.0, 'bobot_real' => 99.0, 'status' => 'overdue']);
    wbsApiItem($project, '1.1.1', 3, [], ['bobot_plan' => 0.40, 'bobot_real' => 0.30, 'status' => 'on_track', 'actual_start_period' => null, 'actual_end_period' => null]);
    wbsApiItem($project, '1.1.2', 3, [], ['bobot_plan' => 0.10, 'bobot_real' => 0.10, 'status' => 'overdue', 'actual_start_period' => null, 'actual_end_period' => null]);

    $summary = $this->getJson("/api/projects/{$project->id}/wbs")->json('data.summary');

    expect($summary['total_items'])->toBe(2)
        ->and($summary['total_not_started'])->toBe(2)
        ->and($summary['total_on_track'])->toBe(0)
        ->and($summary['total_overdue'])->toBe(0)
        ->and($summary['total_critical'])->toBe(0)
        ->and($summary['total_bobot_plan'])->toEqualWithDelta(0.5, 0.0001)
        ->and($summary['total_bobot_real'])->toEqualWithDelta(0.0, 0.0001)
        ->and($summary['progres'])->toEqualWithDelta(0.0, 0.0001);
});

test('summary rolls up and counts a level two leaf by its actual children', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1', 1, [], ['bobot_plan' => 99.0, 'bobot_real' => 99.0]);
    wbsApiItem($project, '1.1', 2, [], [
        'bobot_plan' => 0.40,
        'bobot_real' => 0.30,
        'actual_start_period' => null,
        'actual_end_period' => null,
    ]);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 50,
    ]);

    $response = $this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk();
    $summary = $response->json('data.summary');
    $items = collect($response->json('data.items'));

    expect($summary['total_items'])->toBe(1)
        ->and($summary['total_bobot_plan'])->toEqualWithDelta(0.40, 0.0001)
        ->and($summary['total_bobot_real'])->toEqualWithDelta(0.20, 0.0001)
        ->and($items->firstWhere('wbs_no', '1')['bobot_plan'])->toEqual(0.4)
        ->and($items->firstWhere('wbs_no', '1.1')['latest_progress_percent'])->toEqual(50.0);
});

test('summary uses bobot_real derived from web progress instead of the stored value', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], ['bobot_plan' => 10.0, 'bobot_real' => 80.0]);
    wbsApiItem($project, '1.1.2', 3, [], ['bobot_plan' => 20.0, 'bobot_real' => 90.0]);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 50,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.2',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 100,
    ]);

    $summary = $this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.summary');

    expect($summary['total_bobot_plan'])->toEqualWithDelta(30.0, 0.0001)
        ->and($summary['total_bobot_real'])->toEqualWithDelta(25.0, 0.0001)
        ->and($summary['progres'])->toEqualWithDelta(25 / 30, 0.0001);
});

test('sheet-only actual finish determines on-track overdue and critical status', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-25 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], [
        'plan_end_period' => 'Jan W2',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W2',
    ]);
    wbsApiItem($project, '1.1.2', 3, [], [
        'plan_end_period' => 'Jan W1',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W2',
    ]);
    wbsApiItem($project, '1.1.3', 3, [], [
        'plan_end_period' => 'Jan W1',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W3',
    ]);

    $items = collect($this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.items'));
    $onTrack = $items->firstWhere('wbs_no', '1.1.1');
    $overdue = $items->firstWhere('wbs_no', '1.1.2');
    $critical = $items->firstWhere('wbs_no', '1.1.3');

    expect($onTrack['status'])->toBe('on_track')
        ->and($onTrack['latest_progress_percent'])->toBeNull()
        ->and($onTrack['bobot_real'])->toEqualWithDelta(0.0, 0.0001)
        ->and($onTrack['actual_start_week'])->toBe(1)
        ->and($onTrack['actual_finish_week'])->toBe(2)
        ->and($onTrack['actual_source'])->toBe('sheet')
        ->and($overdue['status'])->toBe('overdue')
        ->and($critical['status'])->toBe('critical');
});

test('sheet actual start without finish uses current ordinal lateness', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-25 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], [
        'plan_end_period' => 'Jan W3',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => null,
    ]);
    wbsApiItem($project, '1.1.2', 3, [], [
        'plan_end_period' => 'Jan W2',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => null,
    ]);

    $items = collect($this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.items'));

    expect($items->firstWhere('wbs_no', '1.1.1')['status'])->toBe('overdue')
        ->and($items->firstWhere('wbs_no', '1.1.2')['status'])->toBe('critical')
        ->and($items->firstWhere('wbs_no', '1.1.1')['actual_finish_week'])->toBeNull();
});

test('incomplete web progress ignores sheet finish and start uses the earliest source', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-25 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], [
        'plan_end_period' => 'Jan W4',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W4',
    ]);
    wbsApiItem($project, '1.1.2', 3, [], [
        'plan_end_period' => 'Jan W4',
        'actual_start_period' => 'Jan W3',
        'actual_end_period' => 'Jan W4',
    ]);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 40,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.2',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 25,
    ]);

    $items = collect($this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.items'));
    $sheetStart = $items->firstWhere('wbs_no', '1.1.1');
    $webStart = $items->firstWhere('wbs_no', '1.1.2');

    expect($sheetStart['actual_start_week'])->toBe(1)
        ->and($sheetStart['actual_finish_week'])->toBeNull()
        ->and($sheetStart['actual_source'])->toBe('sheet')
        ->and($sheetStart['latest_progress_percent'])->toEqualWithDelta(40.0, 0.0001)
        ->and($webStart['actual_start_week'])->toBe(1)
        ->and($webStart['actual_finish_week'])->toBeNull()
        ->and($webStart['actual_source'])->toBe('web')
        ->and($webStart['latest_progress_percent'])->toEqualWithDelta(25.0, 0.0001);
});

test('web completion determines finish and combines source with earlier sheet start', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-25 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], [
        'plan_end_period' => 'Jan W4',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W4',
    ]);

    foreach ([2 => 50, 3 => 100] as $week => $percent) {
        WbsProgressEntry::query()->create([
            'master_project_id' => $project->id,
            'wbs_no' => '1.1.1',
            'year' => 2026,
            'month' => 1,
            'week' => $week,
            'percent' => $percent,
        ]);
    }

    $item = collect($this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.items'))
        ->firstWhere('wbs_no', '1.1.1');

    expect($item['actual_start_week'])->toBe(1)
        ->and($item['actual_finish_week'])->toBe(3)
        ->and($item['actual_source'])->toBe('mixed')
        ->and($item['latest_progress_percent'])->toEqualWithDelta(100.0, 0.0001);
});

test('progres is zero when leaf bobot_plan is zero', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1.1.1', 3, [], ['bobot_plan' => 0, 'bobot_real' => 0.5, 'actual_start_period' => null, 'actual_end_period' => null]);
    wbsApiItem($project, '1.1.2', 3, [], ['bobot_plan' => 0, 'bobot_real' => 0.5, 'actual_start_period' => null, 'actual_end_period' => null]);

    $summary = $this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data.summary');

    expect($summary['progres'])->toEqual(0)
        ->and($summary['total_bobot_plan'])->toEqual(0)
        ->and($summary['total_bobot_real'])->toEqualWithDelta(0.0, 0.0001);
});

test('project without wbs returns empty items and zeroed summary', function () {
    $project = wbsApiProject();

    $data = $this->getJson("/api/projects/{$project->id}/wbs")
        ->assertOk()
        ->json('data');

    expect($data['items'])->toBe([])
        ->and($data['summary']['total_items'])->toBe(0)
        ->and($data['summary']['total_on_track'])->toBe(0)
        ->and($data['summary']['total_overdue'])->toBe(0)
        ->and($data['summary']['total_critical'])->toBe(0)
        ->and($data['summary']['total_bobot_plan'])->toEqual(0)
        ->and($data['summary']['total_bobot_real'])->toEqual(0)
        ->and($data['summary']['progres'])->toEqual(0);
});

test('all wbs endpoints return 404 for a missing project', function () {
    foreach (['wbs', 'wbs/weekly'] as $path) {
        $this->getJson("/api/projects/999/{$path}")
            ->assertNotFound()
            ->assertExactJson([
                'status' => 'error',
                'message' => 'MasterProject with ID [999] not found.',
            ]);
    }

    $this->postJson('/api/projects/999/wbs/sync')
        ->assertNotFound()
        ->assertExactJson([
            'status' => 'error',
            'message' => 'MasterProject with ID [999] not found.',
        ]);
});

test('weekly endpoint keeps zero as zero float and scopes to one project', function () {
    $mine = wbsApiProject();
    $other = wbsApiProject(['project_code' => 'OTHER']);

    $item = wbsApiItem($mine, '1.1.1', 3, [], ['order_index' => 0]);
    $otherItem = wbsApiItem($other, '9.9.9', 3, [], ['order_index' => 0]);

    wbsApiWeekly($item, 'plan', 1, 1, 10.0);
    wbsApiWeekly($item, 'actual', 1, 1, 0.0);
    wbsApiWeekly($item, 'actual', 1, 2, null);
    wbsApiWeekly($item, 'plan', 2, 1, 5.0);
    wbsApiWeekly($otherItem, 'plan', 1, 1, 777.0);

    $data = $this->getJson("/api/projects/{$mine->id}/wbs/weekly")->json('data');

    expect($data['year'])->toBe(2026)
        ->and($data['periods'])->toHaveCount(48);

    $zeroRow = collect($data['values'])->first(fn ($r) => $r['type'] === 'actual' && $r['week'] === 1);
    $nullRow = collect($data['values'])->first(fn ($r) => $r['week'] === 2);

    expect($zeroRow['value'])->toBe(0)
        ->and($nullRow['value'])->toBeNull()
        ->and(collect($data['values'])->pluck('wbs_no')->unique()->values()->all())->toBe(['1.1.1']);

    $keys = collect($data['values'])->map(fn ($r) => $r['type'].'-'.$r['month'].'-'.$r['week'])->all();
    expect($keys)->toBe(['actual-1-1', 'actual-1-2', 'plan-1-1', 'plan-2-1']);
});

test('month filter narrows periods and values, invalid months are ignored', function () {
    $project = wbsApiProject();
    $item = wbsApiItem($project, '1.1.1', 3);

    wbsApiWeekly($item, 'plan', 1, 1, 10.0);
    wbsApiWeekly($item, 'plan', 2, 1, 20.0);

    $january = $this->getJson("/api/projects/{$project->id}/wbs/weekly?month=1")->json('data');

    expect($january['periods'])->toHaveCount(4)
        ->and(collect($january['periods'])->pluck('label')->all())->toBe(['Jan W1', 'Jan W2', 'Jan W3', 'Jan W4'])
        ->and($january['values'])->toHaveCount(1)
        ->and($january['values'][0]['month'])->toBe(1);

    foreach (['abc', '0', '99', ''] as $invalid) {
        $data = $this->getJson("/api/projects/{$project->id}/wbs/weekly?month={$invalid}")->json('data');

        expect($data['periods'])->toHaveCount(48)
            ->and($data['values'])->toHaveCount(2);
    }
});

test('leaf status and progression fields are calculated at read time from project progress entries', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-20 08:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();

    wbsApiItem($project, '1.1.1', 3, [], [
        'bobot_plan' => 20.0,
        'plan_end_period' => 'Jan W4',
        'actual_start_period' => null,
        'actual_end_period' => null,
    ]);
    wbsApiItem($project, '1.1.2', 3, [], ['bobot_plan' => 30.0, 'plan_end_period' => 'Jan W4']);
    wbsApiItem($project, '1.1.3', 3, [], ['bobot_plan' => 40.0, 'plan_end_period' => 'Jan W2']);
    wbsApiItem($project, '1.1.4', 3, [], ['bobot_plan' => 50.0, 'plan_end_period' => 'Jan W2']);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.2',
        'year' => 2026,
        'month' => 1,
        'week' => 4,
        'percent' => 100,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.3',
        'year' => 2026,
        'month' => 1,
        'week' => 3,
        'percent' => 100,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.4',
        'year' => 2026,
        'month' => 1,
        'week' => 4,
        'percent' => 100,
    ]);

    $response = $this->getJson("/api/projects/{$project->id}/wbs");

    $items = collect($response->json('data.items'));
    $notStartedItem = $items->firstWhere('wbs_no', '1.1.1');
    $onTrackItem = $items->firstWhere('wbs_no', '1.1.2');
    $overdueItem = $items->firstWhere('wbs_no', '1.1.3');
    $criticalItem = $items->firstWhere('wbs_no', '1.1.4');

    expect($notStartedItem['latest_progress_percent'])->toBeNull()
        ->and($notStartedItem['status'])->toBe('not_started')
        ->and($notStartedItem['actual_start_week'])->toBeNull()
        ->and($notStartedItem['actual_source'])->toBeNull()
        ->and($onTrackItem['latest_progress_percent'])->toEqualWithDelta(100.0, 0.0001)
        ->and($onTrackItem['bobot_real'])->toEqualWithDelta(30.0, 0.0001)
        ->and($onTrackItem['actual_finish_week'])->toBe(4)
        ->and($onTrackItem['status'])->toBe('on_track')
        ->and($overdueItem['status'])->toBe('overdue')
        ->and($criticalItem['status'])->toBe('critical');

    $summary = $response->json('data.summary');
    expect($summary['total_not_started'])->toBe(1)
        ->and($summary['total_on_track'])->toBe(1)
        ->and($summary['total_overdue'])->toBe(1)
        ->and($summary['total_critical'])->toBe(1);
});

test('the one-week and two-week lateness boundaries are handled separately', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-25 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();

    wbsApiItem($project, '1.1.1', 3, [], ['bobot_plan' => 10.0, 'plan_end_period' => 'Jan W1']);
    wbsApiItem($project, '1.1.2', 3, [], ['bobot_plan' => 10.0, 'plan_end_period' => 'Jan W1']);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 100,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.2',
        'year' => 2026,
        'month' => 1,
        'week' => 3,
        'percent' => 100,
    ]);

    $items = collect($this->getJson("/api/projects/{$project->id}/wbs")->json('data.items'));

    expect($items->firstWhere('wbs_no', '1.1.1')['status'])->toBe('overdue')
        ->and($items->firstWhere('wbs_no', '1.1.2')['status'])->toBe('critical');
});

test('parent items aggregate direct child progress and produced status null', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-05 09:00:00', 'Asia/Jakarta'));

    $project = wbsApiProject();
    wbsApiItem($project, '1', 1, [], ['bobot_plan' => 0.0, 'status' => null]);
    wbsApiItem($project, '1.1', 2, [], ['bobot_plan' => 10.0]);
    wbsApiItem($project, '1.2', 2, [], ['bobot_plan' => 20.0]);
    wbsApiItem($project, '1.1.1', 3, [], [
        'bobot_plan' => 10.0,
        'plan_end_period' => 'Feb W1',
        'actual_start_period' => null,
        'actual_end_period' => null,
    ]);
    wbsApiItem($project, '1.2.1', 3, [], [
        'bobot_plan' => 20.0,
        'plan_end_period' => 'Feb W2',
        'actual_start_period' => null,
        'actual_end_period' => null,
    ]);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 2,
        'week' => 1,
        'percent' => 100,
    ]);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.2.1',
        'year' => 2026,
        'month' => 2,
        'week' => 2,
        'percent' => 100,
    ]);

    $items = collect($this->getJson("/api/projects/{$project->id}/wbs")->json('data.items'));
    $levelOne = $items->firstWhere('wbs_no', '1');
    $levelTwo = $items->firstWhere('wbs_no', '1.1');

    expect($levelTwo['status'])->toBeNull()
        ->and($levelTwo['progress_sum'])->toEqualWithDelta(100.0, 0.0001)
        ->and($levelTwo['bobot_plan'])->toEqualWithDelta(10.0, 0.0001)
        ->and($levelTwo['actual_start_week'])->toBe(5)
        ->and($levelTwo['actual_finish_week'])->toBe(5)
        ->and($levelTwo['actual_source'])->toBe('web')
        ->and($levelOne['status'])->toBeNull()
        ->and($levelOne['progress_sum'])->toEqualWithDelta(200.0, 0.0001)
        ->and($levelOne['bobot_plan'])->toEqualWithDelta(30.0, 0.0001)
        ->and($levelOne['actual_start_week'])->toBe(5)
        ->and($levelOne['actual_finish_week'])->toBe(6)
        ->and($levelOne['actual_source'])->toBe('web');
});

test('weekly endpoint appends the derived progress series without mutating the plan and actual values', function () {
    $project = wbsApiProject();
    $item = wbsApiItem($project, '1.1.1', 3, [], ['order_index' => 0]);

    wbsApiWeekly($item, 'plan', 1, 1, 10.0);
    wbsApiWeekly($item, 'actual', 1, 1, 5.0);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 75,
    ]);

    $data = $this->getJson("/api/projects/{$project->id}/wbs/weekly")->json('data');
    $progressRow = collect($data['values'])->first(fn ($row) => $row['type'] === 'progress');

    expect($progressRow['wbs_no'])->toBe('1.1.1')
        ->and($progressRow['type'])->toBe('progress')
        ->and($progressRow['year'])->toBe(2026)
        ->and($progressRow['month'])->toBe(1)
        ->and($progressRow['week'])->toBe(2)
        ->and($progressRow['value'])->toEqualWithDelta(75.0, 0.0001)
        ->and(collect($data['values'])->pluck('type')->unique()->values()->all())->toContain('progress');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('sync endpoint forwards the service result without touching google', function () {
    $project = wbsApiProject();

    $this->postJson("/api/projects/{$project->id}/wbs/sync")
        ->assertOk()
        ->assertExactJson([
            'status' => 'success',
            'data' => [
                'status' => 'success',
                'sheet_title' => '2. Progress & Revenue',
                'wbs_count' => 7,
            ],
        ]);
});
