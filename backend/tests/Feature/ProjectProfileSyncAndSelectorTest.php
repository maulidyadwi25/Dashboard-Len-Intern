<?php

use App\Models\MasterProject;
use App\Models\ProjectProfile;
use App\Services\GoogleSheetsService;

function profileSyncProject(array $attributes = []): MasterProject
{
    return MasterProject::create(array_merge([
        'project_code' => 'INIT-CODE',
        'project_name' => 'Initial Master Project',
        'client_name' => 'Initial Client',
        'leader_name' => 'Initial Pimpro',
        'net_contract_value' => 5000000000.0,
        'spreadsheet_id' => 'profile-test-sheet-id',
        'is_active' => true,
    ], $attributes));
}

function profileSyncService(array $rows): GoogleSheetsService
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

test('syncProgressRevenue persists project_code and client_name to progress_revenue profile and syncs to master_projects', function () {
    $project = profileSyncProject([
        'project_code' => 'OLD-CODE',
        'client_name' => 'OLD-CLIENT',
    ]);

    $procurementProfile = ProjectProfile::create([
        'project_id' => $project->id,
        'type' => 'procurement',
        'project_name' => 'Procurement Project',
        'nilai_kontrak' => 150000000000,
    ]);
    $budgetProfile = ProjectProfile::create([
        'project_id' => $project->id,
        'type' => 'budget_control',
        'project_name' => 'Budget Project',
        'nilai_kontrak' => 0,
    ]);

    $rows = [
        ['Nama Client', '', 'PT LEN Industri'],
        ['Kode Proyek', '', 'PRJ-2026-NEW'],
        ['Nama Proyek', '', 'New Refurbishment Project'],
        ['Nilai Kontrak', '', '250000000000'],
        ['Nama Pimpro', '', 'SETIAJI'],
        ['Penanggungjawab Report', '', 'KURNIAWAN'],
    ];

    $service = profileSyncService($rows);
    $service->syncProgressRevenue($project);

    $profile = ProjectProfile::where('project_id', $project->id)
        ->where('type', 'progress_revenue')
        ->first();

    expect($profile)->not->toBeNull()
        ->and($profile->project_code)->toBe('PRJ-2026-NEW')
        ->and($profile->client_name)->toBe('PT LEN Industri')
        ->and($profile->project_name)->toBe('New Refurbishment Project')
        ->and($profile->nama_pimpro)->toBe('SETIAJI')
        ->and($profile->nama_PA)->toBe('KURNIAWAN');

    $project->refresh();
    expect($project->project_code)->toBe('PRJ-2026-NEW')
        ->and($project->client_name)->toBe('PT LEN Industri');

    expect($procurementProfile->fresh()->project_code)->toBe('PRJ-2026-NEW')
        ->and((float) $procurementProfile->fresh()->nilai_kontrak)->toBe(150000000000.0)
        ->and($budgetProfile->fresh()->project_code)->toBe('PRJ-2026-NEW')
        ->and((float) $budgetProfile->fresh()->nilai_kontrak)->toBe(250000000000.0);
});

test('syncProgressRevenue does not overwrite master project_code or client_name with empty values', function () {
    $project = profileSyncProject([
        'project_code' => 'KEEP-CODE',
        'client_name' => 'KEEP-CLIENT',
    ]);

    $rows = [
        ['Nama Proyek', '', 'Project Without Code and Client'],
        ['Nilai Kontrak', '', '10000000000'],
    ];

    $service = profileSyncService($rows);
    $service->syncProgressRevenue($project);

    $project->refresh();
    expect($project->project_code)->toBe('KEEP-CODE')
        ->and($project->client_name)->toBe('KEEP-CLIENT');
});

test('GET /api/projects returns single entry per project with coalesced progress_revenue profile fields without duplicates', function () {
    $project1 = profileSyncProject([
        'project_code' => 'MASTER-CODE-1',
        'project_name' => 'Master Name 1',
        'client_name' => 'Master Client 1',
        'leader_name' => 'Master Pimpro 1',
    ]);

    ProjectProfile::create([
        'project_id' => $project1->id,
        'type' => 'progress_revenue',
        'project_code' => 'PR-CODE-1',
        'project_name' => 'PR Name 1',
        'client_name' => 'PR Client 1',
        'nama_pimpro' => 'PR Pimpro 1',
        'nama_PA' => 'PR Analyst 1',
        'nilai_kontrak' => 1000000,
    ]);

    ProjectProfile::create([
        'project_id' => $project1->id,
        'type' => 'budget_control',
        'project_name' => 'Budget Name 1',
        'nama_pimpro' => 'Budget Pimpro 1',
        'nama_PA' => 'Budget Analyst 1',
        'nilai_kontrak' => 500000,
    ]);

    $project2 = profileSyncProject([
        'project_code' => 'MASTER-CODE-2',
        'project_name' => 'Master Name 2',
        'client_name' => 'Master Client 2',
        'leader_name' => 'Master Pimpro 2',
    ]);

    $response = $this->getJson('/api/projects');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2);

    $entry1 = collect($data)->firstWhere('id', $project1->id);
    expect($entry1)->toMatchArray([
        'id' => $project1->id,
        'project_code' => 'PR-CODE-1',
        'project_name' => 'PR Name 1',
        'client_name' => 'PR Client 1',
        'pimpro' => 'PR Pimpro 1',
        'analyst' => 'PR Analyst 1',
    ]);

    $entry2 = collect($data)->firstWhere('id', $project2->id);
    expect($entry2)->toMatchArray([
        'id' => $project2->id,
        'project_code' => 'MASTER-CODE-2',
        'project_name' => 'Master Name 2',
        'client_name' => 'Master Client 2',
        'pimpro' => 'Master Pimpro 2',
        'analyst' => null,
    ]);
});

test('ProgressRevenueResource and ProjectDashboardResource return new fields with fallback', function () {
    $project = profileSyncProject([
        'project_code' => 'PC-FALLBACK',
        'client_name' => 'CLIENT-FALLBACK',
        'leader_name' => 'PIMPRO-FALLBACK',
    ]);

    ProjectProfile::create([
        'project_id' => $project->id,
        'type' => 'progress_revenue',
        'project_name' => 'Profile Project Name',
        'project_code' => 'PC-PROFILE',
        'client_name' => 'CLIENT-PROFILE',
        'nama_pimpro' => 'PIMPRO-PROFILE',
        'nama_PA' => 'ANALYST-PROFILE',
        'nilai_kontrak' => 50000000,
    ]);

    // Test ProgressRevenue endpoint/resource
    $prResponse = $this->getJson("/api/projects/{$project->id}/progress-revenue");
    $prResponse->assertStatus(200)
        ->assertJsonPath('data.project.project_code', 'PC-PROFILE')
        ->assertJsonPath('data.project.client_name', 'CLIENT-PROFILE')
        ->assertJsonPath('data.project.project_lead_name', 'PIMPRO-PROFILE')
        ->assertJsonPath('data.project.pimpro', 'PIMPRO-PROFILE')
        ->assertJsonPath('data.project.report_owner_name', 'ANALYST-PROFILE')
        ->assertJsonPath('data.project.analyst', 'ANALYST-PROFILE');

    // Test ProjectDashboard endpoint/resource
    $dashResponse = $this->getJson("/api/projects/{$project->id}/dashboard");
    $dashResponse->assertStatus(200)
        ->assertJsonPath('data.project_code', 'PC-PROFILE')
        ->assertJsonPath('data.client_name', 'CLIENT-PROFILE')
        ->assertJsonPath('data.pimpro', 'PIMPRO-PROFILE')
        ->assertJsonPath('data.analyst', 'ANALYST-PROFILE')
        ->assertJsonPath('data.metadata.kode_proyek', 'PC-PROFILE')
        ->assertJsonPath('data.metadata.nama_client', 'CLIENT-PROFILE')
        ->assertJsonPath('data.metadata.nama_pimpro', 'PIMPRO-PROFILE')
        ->assertJsonPath('data.metadata.nama_pa', 'ANALYST-PROFILE');
});
