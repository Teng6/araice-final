<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('farmers LGU staff and admins can update their own profile and password', function () {
    foreach (UserRole::cases() as $role) {
        $this->app['auth']->forgetGuards();
        $user = User::factory()->create(['role' => $role]);
        if ($role === UserRole::Farmer) {
            FarmerProfile::factory()->for($user)->create();
        }

        $token = $user->createToken('mobile')->plainTextToken;
        $payload = [
            'name' => "Updated {$role->value}",
            'email' => "updated-{$role->value}@example.test",
            'role' => UserRole::Admin->value,
            'deactivated_at' => now()->toISOString(),
        ];

        if ($role === UserRole::Farmer) {
            $payload += [
                'barangay' => 'San Vicente',
                'municipality' => MunicipalityEnum::Orion->value,
                'contact_number' => '09123456789',
                'farm_lat' => 14.7,
                'farm_long' => 120.6,
            ];
        }

        $updated = $this->withToken($token)
            ->patchJson('/api/user', $payload)
            ->assertOk()
            ->assertJsonPath('name', $payload['name'])
            ->assertJsonPath('email', $payload['email'])
            ->assertJsonPath('role', $role->value);

        $user->refresh();
        expect($user->role)->toBe($role)
            ->and($user->deactivated_at)->toBeNull();

        $this->app['auth']->forgetGuards();
        $currentPassword = $user->createToken('password-change-current')->plainTextToken;
        $this->withToken($currentPassword)
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertOk()
            ->assertExactJson(['message' => 'Password updated successfully.']);

        expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();

        $this->app['auth']->forgetGuards();
        $this->withToken($currentPassword)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
});

test('farmer profile update changes the editable farm fields and response matches user endpoint', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    $profile = FarmerProfile::factory()->for($user)->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $payload = [
        'name' => 'Updated Farm Owner',
        'email' => $user->email,
        'barangay' => 'San Vicente',
        'municipality' => MunicipalityEnum::Orion->value,
        'contact_number' => '09123456789',
        'farm_lat' => 14.7,
        'farm_long' => 120.6,
    ];

    $updated = $this->withToken($token)
        ->patchJson('/api/user', $payload)
        ->assertOk()
        ->assertJsonPath('farmer_profile.municipality', MunicipalityEnum::Orion->value)
        ->assertJsonPath('farmer_profile.barangay', 'San Vicente')
        ->assertJsonPath('farmer_profile.contact_number', '09123456789')
        ->assertJsonPath('farmer_profile.farm_lat', 14.7)
        ->assertJsonPath('farmer_profile.farm_long', 120.6);

    $profile->refresh();
    expect($profile->full_name)->toBe('Updated Farm Owner')
        ->and($profile->barangay)->toBe('San Vicente')
        ->and($profile->municipality)->toBe(MunicipalityEnum::Orion)
        ->and($profile->contact_number)->toBe('09123456789');

    $this->app['auth']->forgetGuards();
    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertExactJson($updated->json());
});

test('profile update validates duplicate email and coordinates outside Bataan', function () {
    $user = User::factory()->create(['role' => UserRole::Farmer]);
    FarmerProfile::factory()->for($user)->create();
    $anotherUser = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/user', [
            'name' => $user->name,
            'email' => $anotherUser->email,
            'farm_lat' => 15,
            'farm_long' => 121,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'farm_lat', 'farm_long']);
});

test('password update validates current password and confirmation', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'incorrect',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    $this->putJson('/api/user/password', [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'DifferentPassword123!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('password change revokes other tokens and accepts only the new password at login', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('current')->plainTextToken;
    $otherToken = $user->createToken('other')->plainTextToken;

    $this->withToken($currentToken)
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertOk();

    $this->app['auth']->forgetGuards();
    $this->withToken($otherToken)->getJson('/api/user')->assertUnauthorized();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnauthorized();

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'NewPassword123!',
    ])->assertOk();
});

test('profile and password endpoints require authentication', function () {
    $this->patchJson('/api/user', [
        'name' => 'No User',
        'email' => 'no-user@example.test',
    ])->assertUnauthorized();

    $this->putJson('/api/user/password', [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertUnauthorized();
});
