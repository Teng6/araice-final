<?php

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\Disease;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function adminFarmerPayload(FarmerProfile $farmer, array $overrides = []): array
{
    return array_merge([
        'full_name' => $farmer->full_name,
        'email' => $farmer->user->email,
        'barangay' => $farmer->barangay,
        'municipality' => $farmer->municipality->value,
        'contact_number' => $farmer->contact_number,
        'plot_count' => $farmer->plot_count,
        'farm_lat' => $farmer->farm_lat,
        'farm_long' => $farmer->farm_long,
    ], $overrides);
}

test('an admin can list farmers and filter by name, email, and municipality', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $nameMatch = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create([
        'full_name' => 'Searchable Farmer',
    ]);
    $emailMatch = FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Orion)->create([
        'user_id' => User::factory()->create([
            'role' => UserRole::Farmer,
            'email' => 'find-this-farmer@example.test',
        ])->id,
        'full_name' => 'Different Name',
    ]);
    FarmerProfile::factory()->inMunicipality(MunicipalityEnum::Balanga)->create([
        'full_name' => 'Another Farmer',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.farmers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/farmers/index')
            ->has('farmers.data', 3)
            ->where('farmers.data', fn ($farmers): bool => collect($farmers)->contains(
                fn ($farmer): bool => $farmer['id'] === $nameMatch->id
                    && $farmer['email'] === $nameMatch->user->email
                    && array_key_exists('scan_count', $farmer)
                    && array_key_exists('is_deactivated', $farmer),
            )));

    $this->actingAs($admin)
        ->get(route('admin.farmers.index', ['search' => 'Searchable Farmer', 'municipality' => 'orion']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('farmers.data', 1)
            ->where('farmers.data.0.id', $nameMatch->id)
            ->where('filters.search', 'Searchable Farmer')
            ->where('filters.municipality', 'orion'));

    $this->actingAs($admin)
        ->get(route('admin.farmers.index', ['search' => 'find-this-farmer@example.test']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('farmers.data', 1)
            ->where('farmers.data.0.id', $emailMatch->id));
});

test('an admin can view a farmer and the latest scans', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->create([
        'full_name' => 'Detailed Farmer',
        'plot_count' => 4,
        'farm_lat' => 14.6,
        'farm_long' => 120.5,
    ]);
    $farmer->user->forceFill(['deactivated_at' => now()])->save();
    $disease = Disease::factory()->create(['name' => 'Leaf blight']);
    $olderScan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'scan_date' => now()->subDays(2),
    ]);
    $latestScan = Scan::factory()->for($farmer, 'farmer')->create([
        'disease_id' => $disease->id,
        'scan_date' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.farmers.show', $farmer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/farmers/show')
            ->where('farmer.full_name', 'Detailed Farmer')
            ->where('farmer.email', $farmer->user->email)
            ->where('farmer.barangay', $farmer->barangay)
            ->where('farmer.municipality', $farmer->municipality->value)
            ->where('farmer.contact_number', $farmer->contact_number)
            ->where('farmer.plot_count', 4)
            ->where('farmer.farm_lat', 14.6)
            ->where('farmer.farm_long', 120.5)
            ->where('farmer.registered_at', $farmer->user->created_at->toISOString())
            ->where('farmer.is_deactivated', true)
            ->where('farmer.scan_count', 2)
            ->has('farmer.latest_scans', 2)
            ->where('farmer.latest_scans.0.id', $latestScan->id)
            ->where('farmer.latest_scans.0.disease', 'Leaf blight')
            ->where('farmer.latest_scans.1.id', $olderScan->id));
});

test('an admin can edit a farmer without changing the farmer role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->create(['plot_count' => 3]);

    $this->actingAs($admin)
        ->get(route('admin.farmers.edit', $farmer))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/farmers/edit')
            ->where('farmer.id', $farmer->id)
            ->where('farmer.plot_count', 3));

    $this->actingAs($admin)
        ->put(route('admin.farmers.update', $farmer), adminFarmerPayload($farmer, [
            'full_name' => 'Admin Updated Farmer',
            'email' => 'admin-updated-farmer@example.test',
            'barangay' => 'San Vicente',
            'municipality' => MunicipalityEnum::Orion->value,
            'contact_number' => '09123456789',
            'plot_count' => 8,
            'farm_lat' => 14.7,
            'farm_long' => 120.6,
            'role' => UserRole::Admin->value,
        ]))
        ->assertRedirect(route('admin.farmers.show', $farmer))
        ->assertSessionHas('success', 'Farmer profile updated.');

    $farmer->refresh();
    $farmer->user->refresh();
    expect($farmer->full_name)->toBe('Admin Updated Farmer')
        ->and($farmer->user->name)->toBe('Admin Updated Farmer')
        ->and($farmer->user->email)->toBe('admin-updated-farmer@example.test')
        ->and($farmer->barangay)->toBe('San Vicente')
        ->and($farmer->municipality)->toBe(MunicipalityEnum::Orion)
        ->and($farmer->contact_number)->toBe('09123456789')
        ->and($farmer->plot_count)->toBe(8)
        ->and((float) $farmer->farm_lat)->toBe(14.7)
        ->and((float) $farmer->farm_long)->toBe(120.6)
        ->and($farmer->user->role)->toBe(UserRole::Farmer);
});

test('admin farmer updates validate unique email and registration field constraints', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $farmer = FarmerProfile::factory()->create();
    $anotherUser = User::factory()->create(['email' => 'already-used@example.test']);
    $url = route('admin.farmers.update', $farmer);

    $this->actingAs($admin)
        ->from(route('admin.farmers.edit', $farmer))
        ->put($url, adminFarmerPayload($farmer, [
            'email' => $anotherUser->email,
            'municipality' => 'not-a-municipality',
            'farm_lat' => 15,
            'farm_long' => 121,
            'plot_count' => 0,
        ]))
        ->assertRedirect(route('admin.farmers.edit', $farmer))
        ->assertSessionHasErrors(['email', 'municipality', 'farm_lat', 'farm_long', 'plot_count']);

    $this->actingAs($admin)
        ->from(route('admin.farmers.edit', $farmer))
        ->put($url, adminFarmerPayload($farmer, ['plot_count' => 'text']))
        ->assertRedirect(route('admin.farmers.edit', $farmer))
        ->assertSessionHasErrors(['plot_count']);

    $this->actingAs($admin)
        ->put($url, adminFarmerPayload($farmer))
        ->assertRedirect(route('admin.farmers.show', $farmer));
});

test('LGU staff and farmers are forbidden from all admin farmer pages and updates', function () {
    $farmer = FarmerProfile::factory()->create();
    $routes = [
        fn () => route('admin.farmers.index'),
        fn () => route('admin.farmers.show', $farmer),
        fn () => route('admin.farmers.edit', $farmer),
    ];

    foreach ([UserRole::LguStaff, UserRole::Farmer] as $role) {
        $user = User::factory()->create(['role' => $role]);

        foreach ($routes as $route) {
            $this->actingAs($user)->get($route())->assertForbidden();
        }

        $this->actingAs($user)
            ->put(route('admin.farmers.update', $farmer), adminFarmerPayload($farmer))
            ->assertForbidden();
    }
});
