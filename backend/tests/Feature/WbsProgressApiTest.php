<?php

use App\Models\MasterProject;
use App\Models\User;
use App\Models\WbsItem;
use App\Models\WbsProgressChangeLog;
use App\Models\WbsProgressChangeRequest;
use App\Models\WbsProgressEntry;
use Carbon\Carbon;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => User::ROLE_VP]));
});

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

test('vice president can lower a completed entry', function () {
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
        ->assertOk()
        ->assertJsonPath('data.percent', 50);

    expect((float) WbsProgressEntry::query()
        ->where('master_project_id', $project->id)
        ->where('wbs_no', '1.1.1')
        ->latest('id')
        ->firstOrFail()
        ->percent)->toBe(50.0);
});

test('project analyst can increase without a comment but cannot lower the latest progress', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 40,
    ]);
    $pa = User::factory()->create(['role' => User::ROLE_PA]);
    $this->actingAs($pa);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 50,
    ])->assertOk()
        ->assertJsonPath('data.percent', 50);

    $logWithoutComment = WbsProgressChangeLog::query()->latest('id')->firstOrFail();
    expect($logWithoutComment->comment)->toBeNull();

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 45,
    ])->assertUnprocessable()
        ->assertJsonPath('errors.percent.0', 'Progres baru harus lebih besar dari progres terbaru.');

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 60,
        'comment' => 'Progress lapangan meningkat.',
    ])->assertOk()
        ->assertJsonPath('data.percent', 60);

    $log = WbsProgressChangeLog::query()->firstOrFail();
    expect($log->user_id)->toBe($pa->id)
        ->and($log->actor_role)->toBe(User::ROLE_PA)
        ->and($log->wbs_no)->toBe('1.1.1')
        ->and((float) $log->previous_percent)->toBe(40.0)
        ->and((float) $log->new_percent)->toBe(50.0);

    $latestLog = WbsProgressChangeLog::query()->latest('id')->firstOrFail();
    expect((float) $latestLog->new_percent)->toBe(60.0)
        ->and($latestLog->comment)->toBe('Progress lapangan meningkat.');
});

test('project analyst cannot start progress at zero', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);
    $this->actingAs(User::factory()->create(['role' => User::ROLE_PA]));

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 0,
        'comment' => 'Mulai.',
    ])->assertUnprocessable();
});

test('project analyst can queue a decrease request for vice president approval', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);
    WbsProgressEntry::query()->create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 70,
    ]);

    $pa = User::factory()->create(['name' => 'Project Analyst', 'role' => User::ROLE_PA]);
    $this->actingAs($pa);

    $this->postJson("/api/projects/{$project->id}/wbs/1.1.1/progress-change-requests", [
        'percent' => 45,
        'comment' => 'Koreksi hasil verifikasi lapangan.',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.previous_percent', 70)
        ->assertJsonPath('data.requested_percent', 45);

    $secondPa = User::factory()->create(['role' => User::ROLE_PA]);
    $this->actingAs($secondPa)
        ->postJson("/api/projects/{$project->id}/wbs/1.1.1/progress-change-requests", [
            'percent' => 50,
            'comment' => 'Pengajuan kedua.',
        ])->assertConflict()
        ->assertJsonPath('message', 'Pengajuan perubahan untuk task ini masih menunggu persetujuan VP.');

    expect(WbsProgressChangeRequest::query()->count())->toBe(1)
        ->and((float) WbsProgressEntry::query()->firstOrFail()->percent)->toBe(70.0)
        ->and(WbsProgressChangeLog::query()->count())->toBe(0);

    $vp = User::factory()->create(['role' => User::ROLE_VP]);
    $this->actingAs($vp);
    $this->getJson("/api/projects/{$project->id}/wbs/progress-change-requests")
        ->assertOk()
        ->assertJsonPath('data.0.requester_name', 'Project Analyst')
        ->assertJsonPath('data.0.comment', 'Koreksi hasil verifikasi lapangan.');

    $changeRequest = WbsProgressChangeRequest::query()->firstOrFail();
    $this->patchJson("/api/projects/{$project->id}/wbs/progress-change-requests/{$changeRequest->id}", [
        'decision' => 'approved',
    ])->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect((float) WbsProgressEntry::query()->latest('id')->firstOrFail()->percent)->toBe(45.0)
        ->and(WbsProgressChangeLog::query()->count())->toBe(1)
        ->and(WbsProgressChangeLog::query()->firstOrFail()->actor_role)->toBe(User::ROLE_VP);
});

test('project analyst notification endpoint only returns their own requests and statuses', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3, ['activity_name' => 'Install equipment']);
    $pa = User::factory()->create(['role' => User::ROLE_PA]);
    $anotherPa = User::factory()->create(['role' => User::ROLE_PA]);

    foreach ([
        ['user' => $pa, 'status' => 'pending', 'wbs' => '1.1.1'],
        ['user' => $pa, 'status' => 'approved', 'wbs' => '1.1.1'],
        ['user' => $anotherPa, 'status' => 'rejected', 'wbs' => '1.1.1'],
    ] as $requestData) {
        WbsProgressChangeRequest::query()->create([
            'master_project_id' => $project->id,
            'requested_by' => $requestData['user']->id,
            'requester_name' => $requestData['user']->name,
            'wbs_no' => $requestData['wbs'],
            'year' => 2026,
            'month' => 1,
            'week' => 2,
            'previous_percent' => 70,
            'requested_percent' => 45,
            'comment' => 'Koreksi progres.',
            'status' => $requestData['status'],
            'reviewed_by' => $requestData['status'] === 'pending' ? null : User::factory()->create(['role' => User::ROLE_VP])->id,
            'reviewed_at' => $requestData['status'] === 'pending' ? null : now(),
        ]);
    }

    $this->actingAs($pa)
        ->getJson("/api/projects/{$project->id}/wbs/my-progress-change-requests")
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.counts.pending', 1)
        ->assertJsonPath('meta.counts.approved', 1)
        ->assertJsonPath('meta.counts.rejected', 0)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonPath('data.1.status', 'approved')
        ->assertJsonPath('data.0.activity_name', 'Install equipment');
});

test('vice president cannot access project analyst personal request notifications', function () {
    $project = wbsProgressProject();

    $this->getJson("/api/projects/{$project->id}/wbs/my-progress-change-requests")
        ->assertForbidden();
});

test('vice president can set zero and every update is audited', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:30:00', 'Asia/Jakarta'));

    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3);

    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 80,
    ])->assertOk();
    $this->putJson("/api/projects/{$project->id}/wbs/1.1.1/progress", [
        'percent' => 0,
    ])->assertOk();

    expect(WbsProgressChangeLog::query()->count())->toBe(2)
        ->and((float) WbsProgressChangeLog::query()->latest('id')->firstOrFail()->previous_percent)
        ->toBe(80.0)
        ->and((float) WbsProgressChangeLog::query()->latest('id')->firstOrFail()->new_percent)
        ->toBe(0.0);
});

test('vice president can see project progress changes with task and actor details', function () {
    $project = wbsProgressProject();
    wbsProgressLeafItem($project, '1.1.1', 3, ['activity_name' => 'Install equipment']);
    $pa = User::factory()->create(['name' => 'Project Analyst', 'role' => User::ROLE_PA]);

    WbsProgressChangeLog::query()->create([
        'master_project_id' => $project->id,
        'user_id' => $pa->id,
        'actor_name' => $pa->name,
        'actor_role' => $pa->role,
        'wbs_no' => '1.1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'previous_percent' => 20,
        'new_percent' => 40,
        'comment' => 'Pekerjaan lapangan bertambah.',
    ]);

    $this->getJson("/api/projects/{$project->id}/wbs/progress-changes")
        ->assertOk()
        ->assertJsonPath('data.0.wbs_no', '1.1.1')
        ->assertJsonPath('data.0.activity_name', 'Install equipment')
        ->assertJsonPath('data.0.actor_name', 'Project Analyst')
        ->assertJsonPath('data.0.actor_role', User::ROLE_PA)
        ->assertJsonPath('data.0.previous_percent', 20)
        ->assertJsonPath('data.0.new_percent', 40)
        ->assertJsonPath('data.0.comment', 'Pekerjaan lapangan bertambah.')
        ->assertJsonPath('data.0.year', 2026)
        ->assertJsonPath('data.0.week', 2)
        ->assertJsonPath('meta.total', 1);
});

test('project analyst cannot access project progress change history', function () {
    $project = wbsProgressProject();
    $this->actingAs(User::factory()->create(['role' => User::ROLE_PA]));

    $this->getJson("/api/projects/{$project->id}/wbs/progress-changes")
        ->assertForbidden();
});

test('progress change history returns not found for a missing project', function () {
    $this->getJson('/api/projects/999/wbs/progress-changes')
        ->assertNotFound()
        ->assertJsonPath('message', 'MasterProject with ID [999] not found.');
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
