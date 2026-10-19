<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\User;

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mobile Test Farmer',
        'email' => 'mobile-farmer-'.fake()->unique()->safeEmail(),
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'barangay' => 'San Vicente',
        'municipality' => MunicipalityEnum::Orion->value,
        'contact_number' => '09123456789',
        'farm_lat' => 14.6216,
        'farm_long' => 120.5772,
    ], $overrides);
}

test('mobile registration creates a farmer profile and returns a usable token', function () {
    $payload = registrationPayload();

    $response = $this->postJson('/api/register', $payload);

    $response->assertCreated()
        ->assertJsonPath('user.name', $payload['name'])
        ->assertJsonPath('user.email', $payload['email'])
        ->assertJsonPath('user.role', UserRole::Farmer->value);

    $user = User::query()->where('email', $payload['email'])->firstOrFail();
    $this->assertDatabaseHas('farmer_profiles', [
        'user_id' => $user->id,
        'full_name' => $payload['name'],
        'municipality' => MunicipalityEnum::Orion->value,
        'barangay' => $payload['barangay'],
    ]);
    expect($user->role)->toBe(UserRole::Farmer)
        ->and($user->farmerProfile)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($response->json('token'))->toBeString()->not->toBeEmpty();

    $this->withToken($response->json('token'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('role', UserRole::Farmer->value);
});

test('mobile registration ignores a client-sent role', function () {
    $payload = registrationPayload(['role' => UserRole::Admin->value]);

    $this->postJson('/api/register', $payload)
        ->assertCreated()
        ->assertJsonPath('user.role', UserRole::Farmer->value);

    expect(User::query()->where('email', $payload['email'])->firstOrFail()->role)
        ->toBe(UserRole::Farmer);
});

test('mobile registration accepts an optional plot count and rejects invalid values', function () {
    $payload = registrationPayload(['plot_count' => 12]);

    $response = $this->postJson('/api/register', $payload)
        ->assertCreated();

    $user = User::query()->where('email', $payload['email'])->firstOrFail();
    $this->assertDatabaseHas('farmer_profiles', [
        'user_id' => $user->id,
        'plot_count' => 12,
    ]);

    $this->withToken($response->json('token'))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('farmer_profile.plot_count', 12);

    $this->postJson('/api/register', registrationPayload(['plot_count' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plot_count']);

    $this->postJson('/api/register', registrationPayload(['plot_count' => 'text']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plot_count']);
});

test('mobile registration validates duplicate email, municipality, coordinates, and password confirmation', function () {
    User::factory()->create(['email' => 'existing@example.test']);

    $this->postJson('/api/register', registrationPayload([
        'email' => 'existing@example.test',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['email']);

    $this->postJson('/api/register', registrationPayload([
        'municipality' => 'not-a-municipality',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['municipality']);

    $this->postJson('/api/register', registrationPayload([
        'farm_lat' => 15.0,
    ]))->assertUnprocessable()->assertJsonValidationErrors(['farm_lat']);

    $this->postJson('/api/register', registrationPayload([
        'farm_long' => 121.0,
    ]))->assertUnprocessable()->assertJsonValidationErrors(['farm_long']);

    $this->postJson('/api/register', registrationPayload([
        'password_confirmation' => 'different-password',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

test('mobile registration is limited to five requests per minute', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/register', registrationPayload())->assertCreated();
    }

    $this->postJson('/api/register', registrationPayload())->assertTooManyRequests();
});
