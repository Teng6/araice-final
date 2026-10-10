<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the map shows the highest alert severity for each outbreak', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $outbreakWithAlerts = Outbreak::factory()->create();
    $outbreakWithoutAlerts = Outbreak::factory()->create();

    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::Low]);
    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::High]);
    Alert::factory()->for($outbreakWithAlerts)->create(['severity' => SeverityEnum::Medium]);

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 2)
            ->where('outbreaks.0.id', $outbreakWithAlerts->id)
            ->where('outbreaks.0.severity', 'high')
            ->where('outbreaks.1.id', $outbreakWithoutAlerts->id)
            ->where('outbreaks.1.severity', 'low')
            ->where('ownMunicipality', null)
            ->has('farms', 0));
});

test('the map identifies the signed-in farmer municipality', function () {
    $profile = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create(['farm_lat' => 14.52, 'farm_long' => 120.48]);

    $this->actingAs($profile->user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->where('ownMunicipality', 'orion')
            ->has('farms', 1)
            ->where('farms.0.id', $profile->id)
            ->where('farms.0.name', $profile->full_name)
            ->where('farms.0.lat', 14.52)
            ->where('farms.0.lng', 120.48));
});

test('the map does not identify a former farmer municipality for staff', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Pilar)
        ->create(['user_id' => $user->id]);
    $user->forceFill(['role' => UserRole::LguStaff])->save();

    $this->actingAs($user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->where('ownMunicipality', null));
});

test('farmers see only active outbreaks and their own farm pin', function () {
    $ownFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orion)
        ->create(['farm_lat' => 14.52, 'farm_long' => 120.48]);
    $otherFarm = FarmerProfile::factory()
        ->inMunicipality(MunicipalityEnum::Orani)
        ->create(['farm_lat' => 14.62, 'farm_long' => 120.58]);
    $ownActiveOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Active,
    ]);
    $otherActiveOutbreak = Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orani,
        'status' => OutbreakStatusEnum::Active,
    ]);
    Outbreak::factory()->create([
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Resolved,
    ]);
    Scan::factory()->for($otherFarm, 'farmer')->create([
        'outbreak_id' => $otherActiveOutbreak->id,
    ]);

    $this->actingAs($ownFarm->user)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 1)
            ->where('outbreaks.0.id', $ownActiveOutbreak->id)
            ->where('ownMunicipality', 'orion')
            ->has('farms', 1)
            ->where('farms.0.id', $ownFarm->id));
});

test('the map shows only farms with scans linked to active outbreaks for staff', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activeFarm = FarmerProfile::factory()->create([
        'farm_lat' => 14.52,
        'farm_long' => 120.48,
    ]);
    $resolvedFarm = FarmerProfile::factory()->create();
    $unlinkedFarm = FarmerProfile::factory()->create();
    FarmerProfile::factory()->create();
    $activeOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Active]);
    $resolvedOutbreak = Outbreak::factory()->create(['status' => OutbreakStatusEnum::Resolved]);

    Scan::factory()->for($activeFarm, 'farmer')->create([
        'outbreak_id' => $activeOutbreak->id,
    ]);
    Scan::factory()->for($resolvedFarm, 'farmer')->create([
        'outbreak_id' => $resolvedOutbreak->id,
    ]);
    Scan::factory()->for($unlinkedFarm, 'farmer')->create();

    $this->actingAs($admin)
        ->get(route('map.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('map/index')
            ->has('outbreaks', 1)
            ->where('outbreaks.0.id', $activeOutbreak->id)
            ->has('farms', 1)
            ->where('farms.0.id', $activeFarm->id)
            ->where('farms.0.lat', 14.52)
            ->where('farms.0.lng', 120.48));
});
