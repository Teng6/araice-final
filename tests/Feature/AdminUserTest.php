<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('an admin can view the users list', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Balanga)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/index')
            ->has('users.data', 2)
            ->where('users.data', fn ($users): bool => collect($users)->contains(
                fn ($user): bool => $user['id'] === $farmer->user_id
                    && $user['role'] === UserRole::Farmer->value
                    && $user['municipality'] === MunicipalityEnum::Balanga->value,
            )));
});

test('LGU staff and farmers are forbidden from the users list', function () {
    foreach ([UserRole::LguStaff, UserRole::Farmer] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
});

test('guests are redirected to login from the users list', function () {
    $this->get(route('admin.users.index'))
        ->assertRedirect(route('login'));
});

test('the users list searches by name or email and retains filters in pagination links', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    User::factory()->count(14)->create([
        'name' => 'Searchable Name',
        'role' => UserRole::Admin,
    ]);
    User::factory()->create([
        'name' => 'A Unrelated Name',
        'email' => 'searchable@example.test',
        'role' => UserRole::Admin,
    ]);
    User::factory()->create([
        'name' => 'Searchable Extra',
        'role' => UserRole::Admin,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'searchable', 'role' => 'admin']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 15)
            ->where('filters.search', 'searchable')
            ->where('filters.role', 'admin')
            ->where('users.data', fn ($users): bool => collect($users)->contains(
                fn ($user): bool => $user['email'] === 'searchable@example.test',
            ))
            ->where('users.links', fn ($links): bool => collect($links)
                ->pluck('url')
                ->contains(fn (?string $url): bool => $url !== null
                    && str_contains($url, 'page=2')
                    && str_contains($url, 'search=searchable')
                    && str_contains($url, 'role=admin'))));
});

test('the users list filters by role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->create(['role' => UserRole::LguStaff]);
    FarmerProfile::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['role' => 'farmer']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.role', UserRole::Farmer->value)
            ->where('filters.role', 'farmer'));
});

test('an admin can open the add account form', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/create'));
});

test('an admin can create LGU staff and admin accounts', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    foreach ([UserRole::LguStaff, UserRole::Admin] as $role) {
        $email = fake()->unique()->safeEmail();
        $password = 'Strong-Password-123!';

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Created '.$role->value,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'role' => $role->value,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Account created.');

        $created = User::query()->where('email', $email)->firstOrFail();
        $this->get(route('admin.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.success', 'Account created.'));

        expect($created->role)->toBe($role)
            ->and(Hash::check($password, $created->password))->toBeTrue()
            ->and($created->farmerProfile()->exists())->toBeFalse();
    }
});

test('LGU staff and farmers are forbidden from creating accounts', function () {
    foreach ([UserRole::LguStaff, UserRole::Farmer] as $role) {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('admin.users.create'))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.users.store'), [
                'name' => 'New User',
                'email' => fake()->unique()->safeEmail(),
                'password' => 'Strong-Password-123!',
                'password_confirmation' => 'Strong-Password-123!',
                'role' => UserRole::LguStaff->value,
            ])
            ->assertForbidden();
    }
});

test('duplicate account emails are rejected', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $existing = User::factory()->create(['email' => 'existing@example.test']);

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            'name' => 'Another User',
            'email' => $existing->email,
            'password' => 'Strong-Password-123!',
            'password_confirmation' => 'Strong-Password-123!',
            'role' => UserRole::LguStaff->value,
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('email');
});

test('weak and unconfirmed account passwords are rejected', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $attributes = [
        'name' => 'New User',
        'email' => 'new-user@example.test',
        'role' => UserRole::LguStaff->value,
    ];

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            ...$attributes,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('password');

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            ...$attributes,
            'password' => 'Strong-Password-123!',
            'password_confirmation' => 'Different-Password-123!',
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('password');
});

test('the farmer role cannot be assigned through admin account creation', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            'name' => 'Farmer Account',
            'email' => 'farmer@example.test',
            'password' => 'Strong-Password-123!',
            'password_confirmation' => 'Strong-Password-123!',
            'role' => UserRole::Farmer->value,
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('role');
});
