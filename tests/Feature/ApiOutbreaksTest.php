<?php

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\Outbreak;
use App\Models\User;

test('LGU staff and admins can list and view outbreaks', function () {
    $outbreak = Outbreak::factory()->create();

    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $token = $user->createToken('outbreaks')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/outbreaks')
            ->assertOk()
            ->assertJsonFragment(['id' => $outbreak->id]);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson("/api/outbreaks/{$outbreak->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $outbreak->id);

        $this->app['auth']->forgetGuards();
    }
});

test('LGU staff and admins can close outbreaks and are recorded as the closer', function () {
    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $user = User::factory()->create(['role' => $role]);
        $outbreak = Outbreak::factory()->create();
        $token = $user->createToken('outbreaks')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/outbreaks/{$outbreak->id}/close")
            ->assertOk()
            ->assertJsonPath('data.id', $outbreak->id)
            ->assertJsonPath('data.closed_by.id', $user->id)
            ->assertJsonPath('data.status', OutbreakStatusEnum::Resolved->value);

        $outbreak->refresh();

        expect($outbreak->status)->toBe(OutbreakStatusEnum::Resolved)
            ->and($outbreak->closed_by)->toBe($user->id)
            ->and($outbreak->closed_at)->not->toBeNull()
            ->and($response->json('data.closed_at'))->not->toBeNull();

        $this->app['auth']->forgetGuards();
    }
});

test('outbreak listing supports status municipality disease and page size filters', function () {
    $disease = Disease::factory()->create();
    $otherDisease = Disease::factory()->create();
    $matching = Outbreak::factory()->for($disease)->create([
        'status' => OutbreakStatusEnum::Active,
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $statusExcluded = Outbreak::factory()->for($disease)->create([
        'status' => OutbreakStatusEnum::Resolved,
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    $municipalityExcluded = Outbreak::factory()->for($disease)->create([
        'status' => OutbreakStatusEnum::Active,
        'municipality' => MunicipalityEnum::Orion,
    ]);
    $diseaseExcluded = Outbreak::factory()->for($otherDisease)->create([
        'status' => OutbreakStatusEnum::Active,
        'municipality' => MunicipalityEnum::Balanga,
    ]);
    Outbreak::factory()->count(2)->create();
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $token = $user->createToken('outbreaks')->plainTextToken;

    $activeResponse = $this->withToken($token)
        ->getJson('/api/outbreaks?status=active')
        ->assertOk();

    expect(collect($activeResponse->json('data'))->pluck('id'))->toContain($matching->id)
        ->not->toContain($statusExcluded->id);

    $this->app['auth']->forgetGuards();

    $municipalityResponse = $this->withToken($token)
        ->getJson('/api/outbreaks?municipality=balanga')
        ->assertOk();

    expect(collect($municipalityResponse->json('data'))->pluck('id'))->toContain($matching->id)
        ->not->toContain($municipalityExcluded->id);

    $this->app['auth']->forgetGuards();

    $diseaseResponse = $this->withToken($token)
        ->getJson("/api/outbreaks?disease_id={$disease->id}")
        ->assertOk();

    expect(collect($diseaseResponse->json('data'))->pluck('id'))->toContain($matching->id)
        ->not->toContain($diseaseExcluded->id);

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/outbreaks?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 6);
});

test('farmers cannot access outbreak API endpoints', function () {
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $outbreak = Outbreak::factory()->create();
    $token = $farmer->createToken('outbreaks')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/outbreaks')
        ->assertForbidden();

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson("/api/outbreaks/{$outbreak->id}")
        ->assertForbidden();

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->postJson("/api/outbreaks/{$outbreak->id}/close")
        ->assertForbidden();
});

test('outbreak API endpoints require authentication', function () {
    $outbreak = Outbreak::factory()->create();

    $this->getJson('/api/outbreaks')->assertUnauthorized();
    $this->getJson("/api/outbreaks/{$outbreak->id}")->assertUnauthorized();
    $this->postJson("/api/outbreaks/{$outbreak->id}/close")->assertUnauthorized();
});

test('closing an outbreak twice returns unprocessable entity', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $outbreak = Outbreak::factory()->create();
    $token = $admin->createToken('outbreaks')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/outbreaks/{$outbreak->id}/close")
        ->assertOk();

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->postJson("/api/outbreaks/{$outbreak->id}/close")
        ->assertUnprocessable();
});

test('missing outbreaks return not found for show and close requests', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $missingOutbreakId = 999999;
    $token = $admin->createToken('outbreaks')->plainTextToken;

    $this->withToken($token)
        ->getJson("/api/outbreaks/{$missingOutbreakId}")
        ->assertNotFound();

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->postJson("/api/outbreaks/{$missingOutbreakId}/close")
        ->assertNotFound();
});
