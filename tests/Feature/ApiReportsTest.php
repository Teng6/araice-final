<?php

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Report;
use App\Models\Scan;
use App\Models\User;

test('LGU and admin can list create and read reports', function () {
    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $existingReport = Report::create([
            'generated_by' => $user->id,
            'municipality' => MunicipalityEnum::Balanga,
            'range_start' => '2026-10-01',
            'range_end' => '2026-10-03',
            'summary_data' => [
                'total_scans' => 2,
                'confirmed_cases' => 1,
                'per_disease' => [['disease' => 'Leaf Spot', 'count' => 1]],
                'top_disease' => 'Leaf Spot',
            ],
        ]);
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/reports')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $existingReport->id,
                'generated_by' => ['id' => $user->id, 'name' => $user->name],
                'municipality' => MunicipalityEnum::Balanga->value,
            ]);

        $response = $this->withToken($token)
            ->postJson('/api/reports', [
                'municipality' => MunicipalityEnum::Balanga->value,
                'range_start' => '2026-10-01',
                'range_end' => '2026-10-03',
            ])
            ->assertCreated()
            ->assertJsonPath('data.generated_by.id', $user->id)
            ->assertJsonPath('data.generated_by.name', $user->name)
            ->assertJsonPath('data.municipality', MunicipalityEnum::Balanga->value)
            ->assertJsonPath('data.range_start', '2026-10-01')
            ->assertJsonPath('data.range_end', '2026-10-03')
            ->assertJsonPath('data.summary_data.total_scans', 0)
            ->assertJsonPath('data.summary_data.confirmed_cases', 0)
            ->assertJsonPath('data.summary_data.per_disease', [])
            ->assertJsonPath('data.summary_data.top_disease', null);

        $reportId = $response->json('data.id');
        $this->withToken($token)
            ->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonPath('data.id', $reportId)
            ->assertJsonPath('data.summary_data.total_scans', 0)
            ->assertJsonPath('data.summary_data.confirmed_cases', 0)
            ->assertJsonPath('data.summary_data.per_disease', [])
            ->assertJsonPath('data.summary_data.top_disease', null);

        $this->app['auth']->forgetGuards();
    }
});

test('farmers are forbidden from every report endpoint', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $report = Report::create([
        'generated_by' => User::factory()->create(['role' => UserRole::Admin])->id,
        'municipality' => MunicipalityEnum::Orion,
        'range_start' => '2026-10-01',
        'range_end' => '2026-10-02',
        'summary_data' => [],
    ]);
    $token = $farmer->createToken('mobile')->plainTextToken;

    $this->withToken($token)->getJson('/api/reports')->assertForbidden();
    $this->withToken($token)->postJson('/api/reports', [
        'municipality' => MunicipalityEnum::Balanga->value,
        'range_start' => '2026-10-01',
        'range_end' => '2026-10-02',
    ])->assertForbidden();
    $this->withToken($token)->getJson("/api/reports/{$report->id}")->assertForbidden();
});

test('all report endpoints reject unauthenticated requests', function () {
    $report = Report::create([
        'generated_by' => User::factory()->create(['role' => UserRole::Admin])->id,
        'municipality' => MunicipalityEnum::Orion,
        'range_start' => '2026-10-01',
        'range_end' => '2026-10-02',
        'summary_data' => [],
    ]);

    $this->getJson('/api/reports')->assertUnauthorized();
    $this->postJson('/api/reports', [])->assertUnauthorized();
    $this->getJson("/api/reports/{$report->id}")->assertUnauthorized();
});

test('report creation validates municipality dates and optional disease', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->postJson('/api/reports', [
            'municipality' => 'unknown',
            'range_start' => 'not-a-date',
            'range_end' => 'not-a-date',
            'disease_id' => 999999,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'municipality',
            'range_start',
            'range_end',
            'disease_id',
        ]);
});

test('report pagination defaults to fifteen and accepts requested page sizes', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $reports = collect(range(1, 16))->map(fn () => Report::create([
        'generated_by' => $user->id,
        'municipality' => MunicipalityEnum::Balanga,
        'range_start' => '2026-10-01',
        'range_end' => '2026-10-02',
        'summary_data' => [],
    ]));
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/reports')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('meta.per_page', 15);

    $this->getJson('/api/reports?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $reports->last()->id)
        ->assertJsonPath('meta.per_page', 1);

    $this->getJson('/api/reports?per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

test('report range end includes scans late on its final date and optional disease limits the summary', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $farmer = FarmerProfile::factory()->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $disease = Disease::factory()->create(['name' => 'Leaf Spot']);
    Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed,
        'scan_date' => '2026-10-03 23:59:59',
    ]);
    Scan::factory()->for($farmer, 'farmer')->create([
        'status' => ScanStatusEnum::Completed,
        'scan_date' => '2026-10-04 00:00:00',
    ]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->postJson('/api/reports', [
            'municipality' => MunicipalityEnum::Balanga->value,
            'range_start' => '2026-10-01',
            'range_end' => '2026-10-03',
            'disease_id' => $disease->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.summary_data.total_scans', 1)
        ->assertJsonPath('data.summary_data.confirmed_cases', 1)
        ->assertJsonPath('data.summary_data.per_disease.0.disease', 'Leaf Spot')
        ->assertJsonPath('data.summary_data.per_disease.0.count', 1)
        ->assertJsonPath('data.summary_data.top_disease', 'Leaf Spot');
});

test('report detail returns 404 for a missing report', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson('/api/reports/999999')
        ->assertNotFound();
});
