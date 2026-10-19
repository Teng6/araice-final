<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an admin can reset another users password and the new password works for web and API login', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create([
        'role' => UserRole::Farmer,
        'remember_token' => 'old-remember-token',
    ]);
    $token = $target->createToken('android');
    $newPassword = 'New-Strong-Password-123!';

    $this->actingAs($admin)
        ->patch(route('admin.users.password', $target), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', "Password reset for {$target->name}.")
        ->assertSessionMissing('password')
        ->assertSessionMissing('password_confirmation');

    $target->refresh();

    expect(Hash::check($newPassword, $target->password))->toBeTrue()
        ->and($target->remember_token)->not->toBe('old-remember-token')
        ->and($target->tokens()->count())->toBe(0);

    $this->assertAuthenticatedAs($admin);

    $this->post('/logout')->assertRedirect('/');

    $this->post('/login', [
        'email' => $target->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->post('/login', [
        'email' => $target->email,
        'password' => $newPassword,
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($target);

    $this->post('/logout');

    $this->postJson('/api/login', [
        'email' => $target->email,
        'password' => $newPassword,
    ])->assertOk()
        ->assertJsonPath('user.id', $target->id);
});

test('an admin cannot reset their own password', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $originalPassword = $admin->password;

    $this->actingAs($admin)
        ->patch(route('admin.users.password', $admin), [
            'password' => 'New-Strong-Password-123!',
            'password_confirmation' => 'New-Strong-Password-123!',
        ])
        ->assertForbidden();

    expect($admin->fresh()->password)->toBe($originalPassword);
});

test('short and unconfirmed reset passwords are rejected', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create();
    $originalPassword = $target->password;

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.password', $target), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('password');

    $this->from(route('admin.users.index'))
        ->patch(route('admin.users.password', $target), [
            'password' => 'New-Strong-Password-123!',
            'password_confirmation' => 'Different-Strong-Password-123!',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('password');

    expect($target->fresh()->password)->toBe($originalPassword);
});

test('LGU staff and farmers cannot reset passwords', function () {
    $target = User::factory()->create(['role' => UserRole::Admin]);

    foreach ([UserRole::LguStaff, UserRole::Farmer] as $role) {
        $actor = User::factory()->create(['role' => $role]);

        $this->actingAs($actor)
            ->patch(route('admin.users.password', $target), [
                'password' => 'New-Strong-Password-123!',
                'password_confirmation' => 'New-Strong-Password-123!',
            ])
            ->assertForbidden();
    }
});

test('an admin can reset a deactivated users password without reactivating them', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $target = User::factory()->create(['deactivated_at' => now()]);
    $newPassword = 'New-Strong-Password-123!';

    $this->actingAs($admin)
        ->patch(route('admin.users.password', $target), [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
        ->assertRedirect(route('admin.users.index'));

    $target->refresh();

    expect($target->isDeactivated())->toBeTrue()
        ->and(Hash::check($newPassword, $target->password))->toBeTrue();
});
