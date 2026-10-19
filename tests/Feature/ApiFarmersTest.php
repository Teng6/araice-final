<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;

test('LGU and admin can list farmer profiles with only public directory fields', function () {
    $farmer = FarmerProfile::factory()->for(User::factory()->create([
        'role' => UserRole::Farmer,
        'email' => 'directory-farmer@example.test',
    ]))->create([
        'full_name' => 'A Farmer',
        'municipality' => MunicipalityEnum::Balanga,
        'farm_lat' => 14.676,
        'farm_long' => 120.536,
    ]);

    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->app['auth']->forgetGuards();

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/farmers')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50)
            ->assertJsonPath('data.0', [
                'id' => $farmer->id,
                'full_name' => 'A Farmer',
                'barangay' => $farmer->barangay,
                'municipality' => MunicipalityEnum::Balanga->value,
                'contact_number' => $farmer->contact_number,
            ])
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.farm_lat')
            ->assertJsonMissingPath('data.0.farm_long');
    }
});

test('farmers are forbidden and unauthenticated users cannot list farmer profiles', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($farmer)->create();

    $this->getJson('/api/farmers')->assertUnauthorized();
    $this->withToken($farmer->createToken('mobile')->plainTextToken)
        ->getJson('/api/farmers')
        ->assertForbidden();
});

test('farmer directory search matches names and results are sorted by name', function () {
    FarmerProfile::factory()->create(['full_name' => 'Zelda Smith']);
    FarmerProfile::factory()->create(['full_name' => 'Alice Smith']);
    FarmerProfile::factory()->create(['full_name' => 'Other Farmer']);
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($lgu->createToken('mobile')->plainTextToken)
        ->getJson('/api/farmers?search=Smith&per_page=2')
        ->assertOk()
        ->assertJsonPath('data.0.full_name', 'Alice Smith')
        ->assertJsonPath('data.1.full_name', 'Zelda Smith')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 2);
});

test('farmer directory filters by municipality slug and excludes deactivated accounts', function () {
    FarmerProfile::factory()->create([
        'full_name' => 'Active Balanga Farmer',
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    FarmerProfile::factory()->create([
        'full_name' => 'Orion Farmer',
        'municipality' => MunicipalityEnum::Orion,
    ]);
    $deactivatedUser = User::factory()->create([
        'role' => UserRole::Farmer,
        'deactivated_at' => now(),
    ]);
    FarmerProfile::factory()->for($deactivatedUser)->create([
        'full_name' => 'Deactivated Balanga Farmer',
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($lgu->createToken('mobile')->plainTextToken)
        ->getJson('/api/farmers?municipality=balanga')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.full_name', 'Active Balanga Farmer');
});

test('farmer directory validates municipality and page size', function () {
    $lgu = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($lgu->createToken('mobile')->plainTextToken)
        ->getJson('/api/farmers?municipality=not-a-municipality&per_page=101')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['municipality', 'per_page']);
});
