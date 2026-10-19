<?php

use App\Enums\UserRole;
use App\Models\User;

test('an admin can change staff and admin roles in either direction', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $staff = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $staff), ['role' => UserRole::Admin->value])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Account role updated.');

    expect($staff->fresh()->role)->toBe(UserRole::Admin);

    $this->patch(route('admin.users.role', $staff), ['role' => UserRole::LguStaff->value])
        ->assertRedirect(route('admin.users.index'));

    expect($staff->fresh()->role)->toBe(UserRole::LguStaff);
});

test('admins cannot change farmer or own roles and invalid roles are rejected', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);
    $staff = User::factory()->create(['role' => UserRole::LguStaff]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.role', $farmer), ['role' => UserRole::Admin->value])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('role');

    $this->patch(route('admin.users.role', $admin), ['role' => UserRole::LguStaff->value])
        ->assertForbidden();

    $this->from(route('admin.users.index'))
        ->patch(route('admin.users.role', $staff), ['role' => UserRole::Farmer->value])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('role');

    expect($farmer->fresh()->role)->toBe(UserRole::Farmer)
        ->and($admin->fresh()->role)->toBe(UserRole::Admin)
        ->and($staff->fresh()->role)->toBe(UserRole::LguStaff);
});

test('non-admin users cannot change roles', function () {
    $staff = User::factory()->create(['role' => UserRole::LguStaff]);
    $target = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($staff)
        ->patch(route('admin.users.role', $target), ['role' => UserRole::LguStaff->value])
        ->assertForbidden();
});

test('an admin can deactivate and reactivate any other user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = User::factory()->create(['role' => UserRole::Farmer]);

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $farmer))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Account deactivated.');

    expect($farmer->fresh()->isDeactivated())->toBeTrue();

    $this->patch(route('admin.users.reactivate', $farmer))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'Account reactivated.');

    expect($farmer->fresh()->isDeactivated())->toBeFalse();
});

test('admins cannot deactivate or reactivate themselves', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $admin))
        ->assertForbidden();

    $this->patch(route('admin.users.reactivate', $admin))
        ->assertForbidden();

    expect($admin->fresh()->isDeactivated())->toBeFalse();
});

test('non-admin users cannot deactivate or reactivate accounts', function () {
    $staff = User::factory()->create(['role' => UserRole::LguStaff]);
    $target = User::factory()->create();

    $this->actingAs($staff)
        ->patch(route('admin.users.deactivate', $target))
        ->assertForbidden();

    $this->patch(route('admin.users.reactivate', $target))
        ->assertForbidden();

    expect($target->fresh()->isDeactivated())->toBeFalse();
});

test('deactivating an account deletes all of its Sanctum tokens', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();
    $target->createToken('phone');
    $target->createToken('tablet');

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $target))
        ->assertRedirect(route('admin.users.index'));

    expect($target->tokens()->count())->toBe(0);
});

test('a deactivated account cannot log in on the web or through the API', function () {
    $user = User::factory()->create(['deactivated_at' => now()]);
    $message = 'Your account has been deactivated. Please contact an administrator.';

    $this->from(route('login'))
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => $message]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJson(['message' => $message]);
});

test('a deactivated user with a valid Sanctum token is forbidden from API routes', function () {
    $user = User::factory()->create(['deactivated_at' => now()]);
    $token = $user->createToken('old-token')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertForbidden()
        ->assertJson([
            'message' => 'Your account has been deactivated. Please contact an administrator.',
        ]);
});

test('a deactivated user with a web session is logged out on their next request', function () {
    $user = User::factory()->create();
    $user->deactivated_at = now();
    $user->save();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your account has been deactivated. Please contact an administrator.');

    $this->assertGuest();
});

test('a reactivated user can log in again without their old token being restored', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create();
    $user->createToken('old-device');

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $user))
        ->assertRedirect(route('admin.users.index'));

    expect($user->tokens()->count())->toBe(0);

    $this->patch(route('admin.users.reactivate', $user))
        ->assertRedirect(route('admin.users.index'));

    $this->post('/logout')->assertRedirect('/');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->tokens()->count())->toBe(0);
});
