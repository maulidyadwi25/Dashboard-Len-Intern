<?php

use App\Models\MasterProject;
use App\Models\WbsItem;
use App\Models\WbsWeeklyValue;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\DB;

function syncSheetRow(mixed $no, mixed $milestone, mixed $activity, mixed $ap, array $weeks = []): array
{
    $row = array_fill(0, 56, '');
    $row[0] = $no;
    $row[1] = $milestone;
    $row[2] = $activity;
    $row[3] = 'PIC';
    $row[4] = 'ENG';
    $row[5] = 1000;
    $row[6] = 0.10;
    $row[7] = $ap;

    foreach ($weeks as $offset => $value) {
        $row[8 + (int) $offset] = $value;
    }

    return $row;
}

function syncSampleSheet(): array
{
    return [
        ['NO', 'Milestone', 'RINCIAN KEGIATAN (WBS)', 'PIC', 'DIVISI', 'NILAI KEGIATAN', '% BOBOT KEGIATAN', 'A/P'],
        [],
        syncSheetRow('1', 'MS1', 'Pekerjaan Utama', 'P', [0 => 30]),
        syncSheetRow('1', 'MS1', 'Pekerjaan Utama', 'A', [0 => 28]),
        syncSheetRow('1.1', 'MS1', 'Pondasi', 'P', [0 => 30]),
        syncSheetRow('1.1', 'MS1', 'Pondasi', 'A', [0 => 25]),
        syncSheetRow('1.1.1', 'MS1', 'Galian', 'P', [0 => 10, 1 => 5]),
        syncSheetRow('1.1.1', 'MS1', 'Galian', 'A', [0 => 10, 1 => 0]),
        syncSheetRow('1.1.2', 'MS1', 'Urugan', 'P', [0 => 20]),
        syncSheetRow('1.1.2', 'MS1', 'Urugan', 'A', [0 => 20]),
    ];
}

function syncProject(): MasterProject
{
    return MasterProject::create([
        'project_code' => 'WBS-TEST',
        'project_name' => 'WBS Sync Test',
        'spreadsheet_id' => 'test-spreadsheet-id',
        'progress_revenue_year' => 2026,
    ]);
}

function syncService(array $rows): GoogleSheetsService
{
    return new class($rows) extends GoogleSheetsService
    {
        public function __construct(private readonly array $fixtureRows) {}

        protected function resolveSheetTitle(string $spreadsheetId, array $preferredSheetTitles): ?string
        {
            return '2. Progress & Revenue';
        }

        public function fetchSpreadsheetRange(string $spreadsheetId, string $title, string $valueRenderOption, string $range = 'A1:BZ200'): array
        {
            return $this->fixtureRows;
        }

        protected function progressRevenueYear(MasterProject $project): int
        {
            return 2026;
        }
    };
}

test('sqlite has foreign keys enabled so cascade is really exercised', function () {
    $pragma = DB::select('PRAGMA foreign_keys');

    expect((int) $pragma[0]->foreign_keys)->toBe(1);
});

test('syncWbsData persists hierarchy, rollup, and zero-versus-empty cells', function () {
    $project = syncProject();

    $result = syncService(syncSampleSheet())->syncWbsData($project);

    expect($result['status'])->not->toBe('failed')
        ->and(WbsItem::count())->toBe(4);

    $item = WbsItem::query()->where('wbs_no', '1.1.1')->firstOrFail();
    $sibling = WbsItem::query()->where('wbs_no', '1.1.2')->firstOrFail();
    $parent = WbsItem::query()->where('wbs_no', '1.1')->firstOrFail();
    $root = WbsItem::query()->where('wbs_no', '1')->firstOrFail();

    expect($item->parent_wbs_no)->toBe('1.1')
        ->and($item->level)->toBe(3)
        ->and($parent->parent_wbs_no)->toBe('1')
        ->and($root->parent_wbs_no)->toBeNull()
        ->and($root->level)->toBe(1)
        ->and((float) $item->bobot_plan)->toBe(42.8571)
        ->and((float) $sibling->bobot_plan)->toBe(57.1429)
        ->and((float) $parent->bobot_plan)->toBe(100.0)
        ->and((float) $root->bobot_plan)->toBe(100.0);

    $level2 = WbsWeeklyValue::query()
        ->where('progress_wbs_item_id', $parent->id)
        ->where('type', 'plan')->where('month', 1)->where('week', 1)
        ->value('value');

    $level1 = WbsWeeklyValue::query()
        ->where('progress_wbs_item_id', $root->id)
        ->where('type', 'plan')->where('month', 1)->where('week', 1)
        ->value('value');

    expect((float) $level2)->toBe(30.0)
        ->and((float) $level1)->toBe(30.0);

    $zero = WbsWeeklyValue::query()
        ->where('progress_wbs_item_id', $item->id)
        ->where('type', 'actual')->where('month', 1)->where('week', 2)
        ->first();

    $empty = WbsWeeklyValue::query()
        ->where('progress_wbs_item_id', $item->id)
        ->where('type', 'actual')->where('month', 1)->where('week', 3)
        ->exists();

    expect($zero)->not->toBeNull()
        ->and((float) $zero->value)->toBe(0.0)
        ->and($empty)->toBeFalse();
});

test('sync is idempotent and leaves no orphan weekly values', function () {
    $project = syncProject();
    $service = syncService(syncSampleSheet());

    $service->syncWbsData($project);
    $firstItems = WbsItem::count();
    $firstWeekly = WbsWeeklyValue::count();
    $firstWeights = WbsItem::query()->orderBy('wbs_no')->pluck('bobot_plan', 'wbs_no')->all();

    $service->syncWbsData($project);
    $secondWeights = WbsItem::query()->orderBy('wbs_no')->pluck('bobot_plan', 'wbs_no')->all();

    $orphans = WbsWeeklyValue::query()
        ->whereNotIn('progress_wbs_item_id', DB::table('progress_wbs_items')->select('id'))
        ->count();

    expect(WbsItem::count())->toBe($firstItems)
        ->and(WbsWeeklyValue::count())->toBe($firstWeekly)
        ->and($secondWeights)->toBe($firstWeights)
        ->and($orphans)->toBe(0);
});
