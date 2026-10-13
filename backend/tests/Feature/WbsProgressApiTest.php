<?php

use App\Models\MasterProject;
use App\Models\WbsItem;
use App\Models\WbsProgressEntry;
use Carbon\Carbon;

function wbsProgressProject(array $attributes = []): MasterProject
{
    return MasterProject::create([
        'project_code' => 'WBS-PROGRESS',
        'project_name' => 'WBS Progress Test',
        'spreadsheet_id' => 'test-spreadsheet-id',
        'progress_revenue_year' => 2026,
        ...$attributes,
    ]);
}

function wbsProgressLeafItem(MasterProject $project, string $wbsNo, int $level = 3, array $attributes = []): WbsItem
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

test('leaf progress can be saved and mapped to the correct Asia/Jakarta week', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    $response = $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 55.5,
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.project_id', $project->id)
        ->assertJsonPath('data.wbs_no', '1.1.1')
        ->assertJsonPath('data.year', 2026)
        ->assertJsonPath('data.month', 1)
        ->assertJsonPath('data.week', 2)
        ->assertJsonPath('data.percent', 55.5);

    $entry = WbsProgressEntry::query()->firstOrFail();
    expect($entry->wbs_no)->toBe('1.1.1')
        ->and($entry->year)->toBe(2026)
        ->and($entry->month)->toBe(1)
        ->and($entry->week)->toBe(2)
        ->and((float) $entry->percent)->toBe(55.5);
});

test('same week input overwrites the previous value instead of creating duplicates', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => 20]);
    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => 80]);

    $entries = WbsProgressEntry::query()
        ->where('master_project_id', $project->id)
        ->where('wbs_no', '1.1.1')
        ->get();

    expect($entries)->toHaveCount(1)
        ->and((float) $entries->first()->percent)->toBe(80.0);
});

test('lower progress is accepted and still overwrites the same week', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => 80]);
    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => 45]);

    $entry = WbsProgressEntry::query()->where('master_project_id', $project->id)
        ->where('wbs_no', '1.1.1')
        ->firstOrFail();

    expect((float) $entry->percent)->toBe(45.0);
});

test('project not found returns 404 with existing format', function () {
    $this->putJson('/api/projects/999/wbs/1.1.1/progress', ['percent' => 20])
        ->assertNotFound()
        ->assertExactJson([
            'status' => 'error',
            'message' => 'MasterProject with ID [999] not found.',
        ]);
});

test('wbs item not found for project returns 404', function () {
    $project = wbsProgressProject();

    $this->putJson("/api/projects/{$project->id}/wbs/9.9.9/progress", ['percent' => 20])
        ->assertNotFound()
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', "WBS item [9.9.9] not found for project [{$project->id}].");
});

test('non-leaf wbs item cannot receive progress input', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1', 1);
    wbsProgressLeafItem($project, '1.1', 2);

    $this->putJson("/api/projects/{$project->id}/wbs/1/progress", ['percent' => 50])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'WBS item [1] is not a leaf item and cannot receive progress input.');
});

test('level two leaf wbs item can receive progress input', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1', 1);
    wbsProgressLeafItem($project, '1.1', 2);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1/progress", ['percent' => 50])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.wbs_no', '1.1')
        ->assertJsonPath('data.percent', 50);

    expect(WbsProgressEntry::query()->where('wbs_no', '1.1')->exists())->toBeTrue();
});

test('percent must be numeric and between 0 and 100', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    foreach (['abc', -1, 101, '999'] as $value) {
        $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => $value])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Percent must be a numeric value between 0 and 100.');
    }
});

test('100 percent entry blocks additional input for the same leaf item', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 4,
        'percent' => 100,
    ]);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", ['percent' => 50])
        ->assertStatus(422)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'WBS item [1.1.1] already has a 100% progress entry and cannot receive additional input.');
});

test('leaf wbs number with dots is recognized correctly and stored without losing the dotted path', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-31 15:00:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.2.3', 3);

    $this->putJson("/api/projects/{$project->id}/wbs/1.2.3/progress", ['percent' => 66.66])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.wbs_no', '1.2.3')
        ->assertJsonPath('data.month', 3)
        ->assertJsonPath('data.week', 4);

    $entry = WbsProgressEntry::query()->where('wbs_no', '1.2.3')->firstOrFail();
    expect((float) $entry->percent)->toBe(66.66);
});

afterEach(function () {
    Carbon::setTestNow();
});
