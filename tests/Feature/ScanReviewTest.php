<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\ScanTypeEnum;
use App\Enums\SeverityEnum;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Models\User;

function unlinkedScan(Disease $disease, array $attributes = []): Scan
{
    return Scan::factory()->create([
        'farmer_id' => null,
        'disease_id' => $disease->id,
        'scan_type' => ScanTypeEnum::Leaf,
        'status' => ScanStatusEnum::Completed,
        'outbreak_id' => null,
        ...$attributes,
    ]);
}

test('a farmer cannot access either scan review route', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $scan = unlinkedScan(Disease::factory()->create());

    $this->actingAs($farmer)
        ->get(route('scans.unlinked'))
        ->assertForbidden();

    $this->actingAs($farmer)
        ->post(route('scans.link-outbreak', $scan), ['outbreak_id' => 1])
        ->assertForbidden();
});

test('the review list contains only eligible unlinked leaf scans and active outbreaks', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $otherDisease = Disease::factory()->create();

    $eligible = unlinkedScan($disease);
    Scan::factory()->create([
        'disease_id' => $disease->id,
        'scan_type' => ScanTypeEnum::Leaf,
        'status' => ScanStatusEnum::Completed,
        'outbreak_id' => null,
    ]);
    unlinkedScan($disease, ['scan_type' => ScanTypeEnum::Grain]);
    unlinkedScan($disease, ['status' => ScanStatusEnum::Failed]);
    unlinkedScan($disease, ['outbreak_id' => Outbreak::factory()->create([
        'disease_id' => $disease->id,
        'status' => OutbreakStatusEnum::Resolved,
    ])->id]);
    Scan::factory()->create([
        'farmer_id' => null,
        'disease_id' => null,
        'scan_type' => ScanTypeEnum::Leaf,
        'status' => ScanStatusEnum::Completed,
        'outbreak_id' => null,
    ]);

    $active = Outbreak::factory()->create(['disease_id' => $disease->id]);
    Outbreak::factory()->create([
        'disease_id' => $otherDisease->id,
        'status' => OutbreakStatusEnum::Resolved,
    ]);

    $this->actingAs($user)
        ->get(route('scans.unlinked'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('scans/unlinked')
            ->has('scans.data', 1)
            ->where('scans.data.0.id', $eligible->id)
            ->has('outbreaks', 1)
            ->where('outbreaks.0.id', $active->id)
            ->where('outbreaks.0.disease_id', $disease->id)
            ->where('outbreaks.0.disease', $disease->name));
});

test('reviewers can link an eligible scan to an active outbreak of the same disease', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $scan = unlinkedScan($disease);
    $outbreak = Outbreak::factory()->create(['disease_id' => $disease->id]);

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $scan), ['outbreak_id' => $outbreak->id])
        ->assertRedirect();

    expect($scan->fresh()->outbreak_id)->toBe($outbreak->id);
});

test('a scan cannot be linked to an outbreak for another disease', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $scan = unlinkedScan(Disease::factory()->create());
    $outbreak = Outbreak::factory()->create();

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $scan), ['outbreak_id' => $outbreak->id])
        ->assertSessionHasErrors('outbreak_id');

    expect($scan->fresh()->outbreak_id)->toBeNull();
});

test('a scan cannot be linked to a resolved outbreak', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $scan = unlinkedScan($disease);
    $outbreak = Outbreak::factory()->create([
        'disease_id' => $disease->id,
        'status' => OutbreakStatusEnum::Resolved,
    ]);

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $scan), ['outbreak_id' => $outbreak->id])
        ->assertSessionHasErrors('outbreak_id');

    expect($scan->fresh()->outbreak_id)->toBeNull();
});

test('a scan that already has a farmer or outbreak cannot be linked', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $outbreak = Outbreak::factory()->create(['disease_id' => $disease->id]);
    $farmerScan = Scan::factory()->create([
        'disease_id' => $disease->id,
        'outbreak_id' => null,
    ]);
    $linkedScan = unlinkedScan($disease, ['outbreak_id' => $outbreak->id]);
    $targetOutbreak = Outbreak::factory()->create(['disease_id' => $disease->id]);

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $farmerScan), ['outbreak_id' => $targetOutbreak->id])
        ->assertSessionHasErrors('outbreak_id');

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $linkedScan), ['outbreak_id' => $targetOutbreak->id])
        ->assertSessionHasErrors('outbreak_id');
});

test('linking a scan does not change alerts or outbreak severity', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);
    $disease = Disease::factory()->create();
    $scan = unlinkedScan($disease);
    $outbreak = Outbreak::factory()->create([
        'disease_id' => $disease->id,
        'municipality' => MunicipalityEnum::Orion,
    ]);
    $alert = Alert::factory()->create([
        'outbreak_id' => $outbreak->id,
        'severity' => SeverityEnum::High,
    ]);

    $this->actingAs($user)
        ->post(route('scans.link-outbreak', $scan), ['outbreak_id' => $outbreak->id])
        ->assertRedirect();

    expect($scan->fresh()->outbreak_id)->toBe($outbreak->id)
        ->and(Alert::count())->toBe(1)
        ->and($alert->fresh()->severity)->toBe(SeverityEnum::High)
        ->and($outbreak->fresh()->alerts()->count())->toBe(1);
});
