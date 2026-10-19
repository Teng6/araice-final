<?php

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\TreatmentTypeEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

test('municipality endpoint returns all enum slugs and display labels without database-backed data', function () {
    Sanctum::actingAs(User::factory()->create());
    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->getJson('/api/municipalities')
        ->assertOk()
        ->assertJsonCount(12, 'data');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect(array_keys(collect($response->json('data'))->keyBy('slug')->all()))
        ->toEqualCanonicalizing(array_column(MunicipalityEnum::cases(), 'value'))
        ->and(collect($response->json('data'))->firstWhere('slug', 'dinalupihan'))
        ->toBe(['slug' => 'dinalupihan', 'label' => 'Dinalupihan'])
        ->and($queries)->toBeEmpty();
});

test('disease list and detail provide encyclopedia data and title-sorted treatments', function () {
    $disease = Disease::factory()->create([
        'name' => 'Leaf Spot',
        'description' => 'A fungal disease.',
        'causes' => 'Fungus',
        'symptoms' => 'Leaf lesions',
        'history' => 'Known in rice crops.',
        'prevention_tips' => 'Use clean seed.',
        'sources' => 'Agricultural guide',
        'image_path' => 'images/leaf-spot.jpg',
    ]);
    $zebra = Treatment::factory()->for($disease)->create([
        'title' => 'Zebra treatment',
        'description' => 'Apply as directed.',
        'type' => TreatmentTypeEnum::Chemical,
    ]);
    $alpha = Treatment::factory()->for($disease)->create([
        'title' => 'Alpha treatment',
        'description' => 'Use resistant seed.',
        'type' => TreatmentTypeEnum::Cultural,
    ]);
    foreach (UserRole::cases() as $role) {
        $this->app['auth']->forgetGuards();
        $token = User::factory()->create(['role' => $role])
            ->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/diseases')
            ->assertOk()
            ->assertJsonPath('data.0.id', $disease->id)
            ->assertJsonPath('data.0.name', 'Leaf Spot')
            ->assertJsonPath('data.0.description', 'A fungal disease.')
            ->assertJsonPath('data.0.image_url', url('/images/leaf-spot.jpg'))
            ->assertJsonMissingPath('data.0.causes');

        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->getJson("/api/diseases/{$disease->id}")
            ->assertOk()
            ->assertJsonPath('data.causes', 'Fungus')
            ->assertJsonPath('data.symptoms', 'Leaf lesions')
            ->assertJsonPath('data.history', 'Known in rice crops.')
            ->assertJsonPath('data.prevention_tips', 'Use clean seed.')
            ->assertJsonPath('data.sources', 'Agricultural guide')
            ->assertJsonPath('data.treatments.0', [
                'id' => $alpha->id,
                'title' => 'Alpha treatment',
                'description' => 'Use resistant seed.',
                'type' => 'cultural',
            ])
            ->assertJsonPath('data.treatments.1', [
                'id' => $zebra->id,
                'title' => 'Zebra treatment',
                'description' => 'Apply as directed.',
                'type' => 'chemical',
            ]);
    }
});

test('scan list accepts each staff filter and paginates with a default and bounded page size', function () {
    $disease = Disease::factory()->create();
    $targetFarmer = FarmerProfile::factory()->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $targetScan = Scan::factory()->for($targetFarmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed,
    ]);

    foreach ([
        'farmer_id' => $targetFarmer->id,
        'municipality' => MunicipalityEnum::Balanga->value,
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed->value,
    ] as $filter => $value) {
        $user = User::factory()->create(['role' => UserRole::LguStaff]);

        $this->withToken($user->createToken('mobile')->plainTextToken)
            ->getJson('/api/scans?'.http_build_query([$filter => $value, 'per_page' => 1]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $targetScan->id)
            ->assertJsonPath('per_page', 1);
    }

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->withToken($admin->createToken('mobile')->plainTextToken)
        ->getJson('/api/scans')
        ->assertOk()
        ->assertJsonPath('per_page', 15);

    $this->getJson('/api/scans?per_page=100')
        ->assertOk()
        ->assertJsonPath('per_page', 100);

    $this->getJson("/api/scans?farmer_id={$targetFarmer->id}")
        ->assertOk()
        ->assertJsonPath('data.0.id', $targetScan->id);
});

test('scan list validates page size and staff filter values', function () {
    $user = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->withToken($user->createToken('mobile')->plainTextToken)
        ->getJson('/api/scans?per_page=101&farmer_id=0&municipality=unknown&disease_id=0&status=unknown')
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'per_page',
            'farmer_id',
            'municipality',
            'disease_id',
            'status',
        ]);
});

test('farmer scan lists are scoped to their own data and staff filters are forbidden', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create([
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $otherFarmer = FarmerProfile::factory()->create();
    $ownScan = Scan::factory()->for($farmer, 'farmer')->create();
    Scan::factory()->for($otherFarmer, 'farmer')->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/scans')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownScan->id);

    foreach ([
        'farmer_id' => $farmer->id,
        'municipality' => MunicipalityEnum::Balanga->value,
        'disease_id' => $ownScan->disease_id,
        'status' => ScanStatusEnum::Completed->value,
    ] as $filter => $value) {
        $this->app['auth']->forgetGuards();
        $this->withToken($token)
            ->getJson('/api/scans?'.http_build_query([$filter => $value]))
            ->assertForbidden();
    }
});

test('scan list and detail include absolute image URLs and the three highest raw predictions', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $farmer = FarmerProfile::factory()->for($user)->create();
    $scan = Scan::factory()->for($farmer, 'farmer')->create([
        'image_url' => 'scans/leaf.jpg',
        'raw_predictions' => [
            'data' => [
                'predictions' => [
                    ['label' => 'Low', 'confidence' => 0.2],
                    ['class_name' => 'Top', 'confidence' => 0.95],
                    ['label' => 'Mid', 'confidence' => 0.7],
                    ['label' => 'Second', 'confidence' => 0.8],
                ],
            ],
        ],
    ]);
    $token = $user->createToken('mobile')->plainTextToken;
    $expectedTopThree = [
        ['label' => 'Top', 'confidence' => 0.95],
        ['label' => 'Second', 'confidence' => 0.8],
        ['label' => 'Mid', 'confidence' => 0.7],
    ];

    $this->withToken($token)
        ->getJson('/api/scans')
        ->assertOk()
        ->assertJsonPath('data.0.image_url', url('/storage/scans/leaf.jpg'))
        ->assertJsonPath('data.0.created_at', $scan->created_at->toJSON())
        ->assertJsonPath('data.0.top_3', $expectedTopThree);

    $this->getJson("/api/scans/{$scan->id}")
        ->assertOk()
        ->assertJsonPath('image_url', url('/storage/scans/leaf.jpg'))
        ->assertJsonPath('created_at', $scan->created_at->toJSON())
        ->assertJsonPath('top_3', $expectedTopThree);
});

test('all new read-only endpoints require authentication', function () {
    $disease = Disease::factory()->create();
    $scan = Scan::factory()->create();

    $this->getJson('/api/municipalities')->assertUnauthorized();
    $this->getJson('/api/diseases')->assertUnauthorized();
    $this->getJson("/api/diseases/{$disease->id}")->assertUnauthorized();
    $this->getJson('/api/scans')->assertUnauthorized();
    $this->getJson("/api/scans/{$scan->id}")->assertUnauthorized();

    foreach ([
        'farmer_id' => $scan->farmer_id,
        'municipality' => MunicipalityEnum::Balanga->value,
        'disease_id' => $disease->id,
        'status' => ScanStatusEnum::Completed->value,
        'per_page' => 1,
    ] as $filter => $value) {
        $this->getJson('/api/scans?'.http_build_query([$filter => $value]))
            ->assertUnauthorized();
    }
});
