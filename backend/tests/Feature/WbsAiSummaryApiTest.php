<?php

use App\Models\MasterProject;
use App\Models\WbsAiSummary;
use App\Models\WbsItem;
use App\Models\WbsProgressEntry;
use App\Services\GoogleSheetsService;
use App\Services\WbsAiSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function wbsAiSummaryValidJson(): array
{
    return [
        'executive_summary' => 'Ringkasan valid',
        'recommendations' => [[
            'priority' => 'high',
            'title' => 'Lanjutkan',
            'detail' => 'Tindak lanjut segera.',
        ]],
        'risks' => ['Risiko kecil'],
    ];
}

function wbsAiSummaryProject(array $attributes = []): MasterProject
{
    return MasterProject::create([
        'project_code' => 'AI-WBS',
        'project_name' => 'AI WBS Test',
        'spreadsheet_id' => 'ai-summary-spreadsheet',
        'is_active' => true,
        'progress_revenue_year' => 2026,
        ...$attributes,
    ]);
}

function wbsAiSummaryItem(MasterProject $project, string $wbsNo, array $attributes = []): WbsItem
{
    return WbsItem::create([
        'master_project_id' => $project->id,
        'wbs_no' => $wbsNo,
        'parent_wbs_no' => null,
        'level' => 1,
        'activity_name' => 'Activity '.$wbsNo,
        'bobot_plan' => 100,
        'plan_start_period' => 'Jan W1',
        'plan_end_period' => 'Jan W4',
        'actual_start_period' => 'Jan W1',
        'actual_end_period' => 'Jan W2',
        'status' => 'on_track',
        'order_index' => 0,
        ...$attributes,
    ]);
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 08:00:00', 'Asia/Jakarta'));
    Http::preventStrayRequests();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('generate ai summary parses fenced json and stores source ai', function () {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => "```json\n{\n  \"executive_summary\": \"Ringkasan ok\",\n  \"recommendations\": [{\"priority\": \"high\", \"title\": \"Lanjutkan\", \"detail\": \"Tindak lanjut segera\"}],\n  \"risks\": [\"Ada risk kecil\"]\n}\n```"],
            ]],
        ], 200),
    ]);

    $project = wbsAiSummaryProject();
    wbsAiSummaryItem($project, '1.1', ['bobot_plan' => 40, 'status' => 'on_track']);
    WbsProgressEntry::create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 1,
        'percent' => 40,
    ]);

    $service = app(WbsAiSummaryService::class);
    $result = $service->generateForProject($project, true);

    expect($result['source'])->toBe('ai')
        ->and($result['content']['executive_summary'])->toBe('Ringkasan ok')
        ->and(WbsAiSummary::where('master_project_id', $project->id)->count())->toBe(1);
});

test('ai request requires Indonesian output and sends localized status labels', function () {
    $capturedRequest = null;
    Http::fake(function ($request) use (&$capturedRequest) {
        $capturedRequest = $request->data();

        return Http::response([
            'choices' => [[
                'message' => ['content' => json_encode(wbsAiSummaryValidJson())],
            ]],
        ], 200);
    });

    $project = wbsAiSummaryProject();
    wbsAiSummaryItem($project, '1.1', ['plan_end_period' => 'Jan W4']);
    wbsAiSummaryItem($project, '1.2', ['plan_end_period' => 'Jan W1']);
    wbsAiSummaryItem($project, '1.3', ['plan_end_period' => 'Jan W1']);

    foreach ([
        ['wbs_no' => '1.1', 'percent' => 40],
        ['wbs_no' => '1.2', 'percent' => 40],
        ['wbs_no' => '1.3', 'percent' => 95],
    ] as $progress) {
        WbsProgressEntry::create([
            'master_project_id' => $project->id,
            'wbs_no' => $progress['wbs_no'],
            'year' => 2026,
            'month' => 1,
            'week' => 2,
            'percent' => $progress['percent'],
        ]);
    }

    app(WbsAiSummaryService::class)->generateForProject($project, true);

    $systemMessage = $capturedRequest['messages'][0]['content'];
    $userMessage = $capturedRequest['messages'][1]['content'];
    $repeatedInstruction = 'Instruksi bahasa wajib: Semua nilai teks pada executive_summary, recommendations[].title, recommendations[].detail, dan risks[] WAJIB ditulis dalam bahasa Indonesia. Jangan menyalin istilah bahasa Inggris, kecuali nama WBS. Hanya kunci JSON dan nilai priority high|medium|low yang tetap dalam bahasa Inggris.';

    expect($systemMessage)
        ->toContain('WAJIB ditulis dalam bahasa Indonesia')
        ->toContain('Jangan menyalin istilah bahasa Inggris')
        ->toContain('Terlambat')
        ->toContain('Kritis')
        ->toContain('Pekerjaan berjalan sesuai rencana');

    expect(substr($userMessage, -strlen($repeatedInstruction)))->toBe($repeatedInstruction)
        ->and($userMessage)->toContain('"status":"On Track"')
        ->toContain('"status":"Terlambat"')
        ->toContain('"status":"Kritis"');
});

test('invalid json response returns 502 and keeps previous summary', function () {
    $project = wbsAiSummaryProject();
    WbsAiSummary::create([
        'master_project_id' => $project->id,
        'summary_date' => '2026-01-15',
        'content' => ['executive_summary' => 'Versi lama'],
        'source' => 'manual',
        'generated_at' => now(),
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => '```json\n{not-valid-json\n```'],
            ]],
        ], 200),
    ]);

    $response = $this->postJson("/api/projects/{$project->id}/wbs/ai-summary");

    $response->assertStatus(502)
        ->assertJsonPath('status', 'error');

    expect(WbsAiSummary::where('master_project_id', $project->id)->first()->content['executive_summary'])
        ->toBe('Versi lama');
});

test('timeout during ai generation returns 502', function () {
    $project = wbsAiSummaryProject();

    Http::fake([
        '*/chat/completions' => fn () => throw new ConnectionException('timeout'),
    ]);

    $response = $this->postJson("/api/projects/{$project->id}/wbs/ai-summary");

    $response->assertStatus(502)
        ->assertJsonPath('status', 'error');
});

test('generate ai summary still succeeds with sync warning when sync fails', function () {
    $project = wbsAiSummaryProject();
    wbsAiSummaryItem($project, '1.1', ['bobot_plan' => 40, 'status' => 'on_track']);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => json_encode(wbsAiSummaryValidJson())],
            ]],
        ], 200),
    ]);

    $sheets = Mockery::mock(GoogleSheetsService::class);
    $sheets->shouldReceive('syncWbsData')->with($project)->andThrow(new RuntimeException('sync failed'));
    $this->app->instance(GoogleSheetsService::class, $sheets);

    $response = $this->postJson("/api/projects/{$project->id}/wbs/ai-summary");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.sync_warning', 'Google Sheets sync failed; using latest saved WBS data.');

    expect($response->json('data.source'))->toBe('ai');
});

test('concurrent ai summary requests return 409 while a lock is held', function () {
    $project = wbsAiSummaryProject();
    $lock = Cache::lock("wbs-ai-summary-project:{$project->id}", 10);
    $lock->get();

    $response = $this->postJson("/api/projects/{$project->id}/wbs/ai-summary");

    $response->assertStatus(409)
        ->assertJsonPath('status', 'error');

    $lock->release();
});

test('generate ai summary command skips manual summaries unless forced', function () {
    $project = wbsAiSummaryProject();
    wbsAiSummaryItem($project, '1.1', ['plan_end_period' => 'Jan W4', 'bobot_plan' => 100]);
    WbsAiSummary::create([
        'master_project_id' => $project->id,
        'summary_date' => '2026-01-15',
        'content' => ['executive_summary' => 'Manual lama'],
        'source' => 'manual',
        'generated_at' => now(),
    ]);

    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [[
                'message' => ['content' => json_encode(wbsAiSummaryValidJson())],
            ]],
        ], 200),
    ]);

    $this->artisan('wbs:generate-ai-summary', ['--project' => $project->id])->assertSuccessful();

    expect(WbsAiSummary::where('master_project_id', $project->id)->where('source', 'ai')->count())->toBe(0)
        ->and(WbsAiSummary::where('master_project_id', $project->id)->first()->source)->toBe('manual');

    $this->artisan('wbs:generate-ai-summary', ['--project' => $project->id, '--force' => true])->assertSuccessful();

    expect(WbsAiSummary::where('master_project_id', $project->id)->where('source', 'ai')->count())->toBe(1)
        ->and(WbsAiSummary::where('master_project_id', $project->id)->latest('generated_at')->first()->source)->toBe('ai');
});

test('generate ai summary command continues when one project fails', function () {
    $failingProject = wbsAiSummaryProject(['project_name' => 'Failing Preview', 'project_code' => 'AI-WBS-FAIL']);
    $goodProject = wbsAiSummaryProject(['project_name' => 'Healthy Preview', 'project_code' => 'AI-WBS-GOOD']);

    wbsAiSummaryItem($failingProject, '1.1', ['bobot_plan' => 50]);
    wbsAiSummaryItem($goodProject, '1.1', ['bobot_plan' => 50]);

    Http::fake(function ($request) use ($failingProject) {
        $payload = $request->data();

        if (($payload['project_id'] ?? null) == $failingProject->id) {
            return Http::response(['error' => 'bad'], 500);
        }

        return Http::response([
            'choices' => [[
                'message' => ['content' => json_encode(wbsAiSummaryValidJson())],
            ]],
        ], 200);
    });

    $this->artisan('wbs:generate-ai-summary')->assertSuccessful();

    expect(WbsAiSummary::where('master_project_id', $goodProject->id)->exists())->toBeTrue();
});

test('get ai summary returns payload when it exists and null when missing', function () {
    $projectWithSummary = wbsAiSummaryProject(['project_name' => 'Has Summary', 'project_code' => 'AI-WBS-EXISTS']);
    WbsAiSummary::create([
        'master_project_id' => $projectWithSummary->id,
        'summary_date' => '2026-01-15',
        'content' => wbsAiSummaryValidJson(),
        'source' => 'manual',
        'generated_at' => Carbon::parse('2026-01-15 08:15:00', 'Asia/Jakarta'),
    ]);

    $existsResponse = $this->getJson("/api/projects/{$projectWithSummary->id}/wbs/ai-summary");
    $existsResponse->assertStatus(200)
        ->assertJsonPath('data.content.executive_summary', 'Ringkasan valid')
        ->assertJsonPath('data.source', 'manual')
        ->assertJsonPath('data.summary_date', '2026-01-15');

    expect($existsResponse->json('data.generated_at'))->not->toBeNull();

    $projectWithoutSummary = wbsAiSummaryProject(['project_name' => 'No Summary', 'project_code' => 'AI-WBS-EMPTY']);
    $missingResponse = $this->getJson("/api/projects/{$projectWithoutSummary->id}/wbs/ai-summary");
    $missingResponse->assertStatus(200)
        ->assertJsonPath('data', null);
});

test('week summary includes active leaves for the current week and excludes out-of-range ones', function () {
    $project = wbsAiSummaryProject();

    wbsAiSummaryItem($project, '1.1', [
        'actual_start_period' => 'Jan W1',
        'plan_end_period' => 'Jan W4',
        'bobot_plan' => 50,
    ]);
    WbsProgressEntry::create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.1',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 80,
    ]);

    wbsAiSummaryItem($project, '1.2', [
        'actual_start_period' => 'Feb W1',
        'plan_end_period' => null,
        'bobot_plan' => 20,
    ]);
    WbsProgressEntry::create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.2',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 25,
    ]);

    wbsAiSummaryItem($project, '1.3', [
        'actual_start_period' => 'Jan W1',
        'plan_end_period' => 'Jan W1',
        'bobot_plan' => 15,
    ]);
    WbsProgressEntry::create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.3',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 60,
    ]);

    wbsAiSummaryItem($project, '1.4', [
        'actual_start_period' => 'Jan W2',
        'plan_end_period' => 'Jan W2',
        'bobot_plan' => 15,
    ]);
    WbsProgressEntry::create([
        'master_project_id' => $project->id,
        'wbs_no' => '1.4',
        'year' => 2026,
        'month' => 1,
        'week' => 2,
        'percent' => 0,
    ]);

    $response = $this->getJson("/api/projects/{$project->id}/wbs/week-summary");

    $response->assertStatus(200);

    $items = collect($response->json('data.items'));
    expect($items->pluck('wbs_no')->all())->toContain('1.1', '1.3', '1.4')
        ->and($items->pluck('wbs_no')->all())->not->toContain('1.2');
});

test('missing project returns 404 for ai summary and week summary endpoints', function () {
    $this->getJson('/api/projects/999999/wbs/ai-summary')->assertStatus(404);
    $this->postJson('/api/projects/999999/wbs/ai-summary')->assertStatus(404);
    $this->putJson('/api/projects/999999/wbs/ai-summary', ['content' => wbsAiSummaryValidJson()])->assertStatus(404);
    $this->getJson('/api/projects/999999/wbs/week-summary')->assertStatus(404);
});

test('manual update rejects invalid structure and stores source manual', function () {
    $project = wbsAiSummaryProject();

    $response = $this->putJson("/api/projects/{$project->id}/wbs/ai-summary", [
        'content' => ['bad' => 'structure'],
    ]);

    $response->assertStatus(422);
});

test('generate ai summary keeps prior content on api failure and returns 502', function () {
    $project = wbsAiSummaryProject();
    WbsAiSummary::create([
        'master_project_id' => $project->id,
        'summary_date' => '2026-01-15',
        'content' => ['executive_summary' => 'Versi lama'],
        'source' => 'manual',
        'generated_at' => now(),
    ]);

    Http::fake([
        '*/chat/completions' => Http::response(['error' => 'bad'], 500),
    ]);

    $response = $this->postJson("/api/projects/{$project->id}/wbs/ai-summary");

    $response->assertStatus(502)
        ->assertJsonPath('status', 'error');

    expect(WbsAiSummary::where('master_project_id', $project->id)->first()->content['executive_summary'])
        ->toBe('Versi lama');
});

test('duplicate daily ai summary updates same row instead of creating another', function () {
    $project = wbsAiSummaryProject();

    $existing = WbsAiSummary::query()
        ->where('master_project_id', $project->id)
        ->whereDate('summary_date', '2026-01-15')
        ->first();

    if ($existing) {
        $existing->fill([
            'content' => ['executive_summary' => 'lama'],
            'source' => 'ai',
            'generated_at' => now(),
        ]);
        $existing->save();
    } else {
        WbsAiSummary::create([
            'master_project_id' => $project->id,
            'summary_date' => '2026-01-15',
            'content' => ['executive_summary' => 'lama'],
            'source' => 'ai',
            'generated_at' => now(),
        ]);
    }

    $existing = WbsAiSummary::query()
        ->where('master_project_id', $project->id)
        ->whereDate('summary_date', '2026-01-15')
        ->first();

    $existing->fill([
        'content' => ['executive_summary' => 'baru'],
        'source' => 'manual',
        'generated_at' => now(),
    ]);
    $existing->save();

    expect(WbsAiSummary::where('master_project_id', $project->id)->count())->toBe(1)
        ->and(WbsAiSummary::first()->content['executive_summary'])->toBe('baru');
});
