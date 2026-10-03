<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\SeverityEnum;
use App\Models\Alert;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use App\Services\OutbreakDetectionService;

function leafScan(Disease $disease, MunicipalityEnum $municipality, ?FarmerProfile $farmer = null, array $attributes = []): Scan
{
    $farmer ??= FarmerProfile::factory()->inMunicipality($municipality)->create();

    return Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'uploaded_by_id' => $farmer->user_id,
        ...$attributes,
    ]);
}

function detect(Scan ...$scans): void
{
    foreach ($scans as $scan) {
        app(OutbreakDetectionService::class)->handle($scan);
    }
}

test('two distinct farmers do not trigger an outbreak', function () {
    $disease = Disease::factory()->create();

    detect(
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
    );

    expect(Outbreak::count())->toBe(0)
        ->and(Alert::count())->toBe(0);
});

test('three distinct farmers trigger an outbreak and a low alert', function () {
    $disease = Disease::factory()->create();

    $scans = [
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
    ];

    detect(...$scans);

    $outbreak = Outbreak::sole();

    expect($outbreak->status)->toBe(OutbreakStatusEnum::Active)
        ->and($outbreak->municipality)->toBe(MunicipalityEnum::Orion)
        ->and($outbreak->disease_id)->toBe($disease->id)
        ->and(Alert::sole()->severity)->toBe(SeverityEnum::Low);

    foreach ($scans as $scan) {
        expect($scan->fresh()->outbreak_id)->toBe($outbreak->id);
    }
});

test('the same farmer scanning twice counts once', function () {
    $disease = Disease::factory()->create();
    $farmer = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();

    detect(
        leafScan($disease, MunicipalityEnum::Orion, $farmer),
        leafScan($disease, MunicipalityEnum::Orion, $farmer),
        leafScan($disease, MunicipalityEnum::Orion),
    );

    expect(Outbreak::count())->toBe(0);
});

test('farmers in different municipalities are not combined', function () {
    $disease = Disease::factory()->create();

    detect(
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orani),
    );

    expect(Outbreak::count())->toBe(0);
});

test('scans older than seven days are not counted', function () {
    $disease = Disease::factory()->create();

    detect(
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion, attributes: ['scan_date' => now()->subDays(8)]),
    );

    expect(Outbreak::count())->toBe(0);
});

test('unlinked scans are excluded from the count', function () {
    $disease = Disease::factory()->create();

    $unlinked = Scan::factory()->create([
        'farmer_id' => null,
        'disease_id' => $disease->id,
    ]);

    detect(
        $unlinked,
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
    );

    expect(Outbreak::count())->toBe(0)
        ->and($unlinked->fresh()->outbreak_id)->toBeNull();
});

test('alert recipients are a snapshot of the municipality at firing time', function () {
    $disease = Disease::factory()->create();

    $bystander = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();
    $elsewhere = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orani)->create();

    detect(
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
    );

    $alert = Alert::sole();
    $recipientIds = $alert->users()->pluck('users.id');

    expect($recipientIds)->toHaveCount(4)
        ->and($recipientIds)->toContain($bystander->user_id)
        ->and($recipientIds)->not->toContain($elsewhere->user_id);

    FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create();

    expect($alert->users()->count())->toBe(4);
});

test('a new alert fires only when severity moves up a tier', function () {
    $disease = Disease::factory()->create();
    $scans = [];

    foreach (range(1, 4) as $i) {
        $scans[] = $scan = leafScan($disease, MunicipalityEnum::Orion);
        detect($scan);
    }

    expect(Alert::count())->toBe(1);

    $scans[] = $fifth = leafScan($disease, MunicipalityEnum::Orion);
    detect($fifth);

    $outbreak = Outbreak::sole();

    expect(Alert::count())->toBe(2)
        ->and($outbreak->alerts()->latest('id')->first()->severity)->toBe(SeverityEnum::Medium);

    foreach ($scans as $scan) {
        expect($scan->fresh()->outbreak_id)->toBe($outbreak->id);
    }
});

test('a resolved outbreak is never reused', function () {
    $disease = Disease::factory()->create();

    $old = Outbreak::factory()->create([
        'disease_id' => $disease->id,
        'municipality' => MunicipalityEnum::Orion,
        'status' => OutbreakStatusEnum::Resolved,
    ]);

    $scans = [
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
        leafScan($disease, MunicipalityEnum::Orion),
    ];

    detect(...$scans);

    expect(Outbreak::count())->toBe(2)
        ->and($scans[0]->fresh()->outbreak_id)->not->toBe($old->id);
});
