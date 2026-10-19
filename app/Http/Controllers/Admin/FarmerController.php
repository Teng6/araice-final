<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexAdminFarmersRequest;
use App\Http\Requests\UpdateAdminFarmerRequest;
use App\Models\FarmerProfile;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function index(IndexAdminFarmersRequest $request): Response
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $municipality = $filters['municipality'] ?? null;

        $farmers = FarmerProfile::query()
            ->with('user:id,email,deactivated_at')
            ->withCount('scans')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
            }))
            ->when($municipality, fn ($query) => $query->where('municipality', $municipality))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (FarmerProfile $farmer): array => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'email' => $farmer->user->email,
                'barangay' => $farmer->barangay,
                'municipality' => $farmer->municipality->value,
                'contact_number' => $farmer->contact_number,
                'plot_count' => $farmer->plot_count,
                'scan_count' => $farmer->scans_count,
                'is_deactivated' => $farmer->user->isDeactivated(),
            ]);

        return Inertia::render('admin/farmers/index', [
            'farmers' => $farmers,
            'filters' => [
                'search' => $search,
                'municipality' => $municipality,
            ],
            'municipalities' => array_column(MunicipalityEnum::cases(), 'value'),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function show(FarmerProfile $farmer): Response
    {
        Gate::authorize('view', $farmer);

        $farmer->load([
            'user:id,name,email,deactivated_at,created_at',
            'scans' => fn (HasMany $query) => $query
                ->with('disease:id,name')
                ->orderByDesc('scan_date')
                ->limit(5),
        ])->loadCount('scans');

        return Inertia::render('admin/farmers/show', [
            'farmer' => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'email' => $farmer->user->email,
                'barangay' => $farmer->barangay,
                'municipality' => $farmer->municipality->value,
                'contact_number' => $farmer->contact_number,
                'plot_count' => $farmer->plot_count,
                'farm_lat' => $farmer->farm_lat,
                'farm_long' => $farmer->farm_long,
                'registered_at' => $farmer->user->created_at,
                'is_deactivated' => $farmer->user->isDeactivated(),
                'scan_count' => $farmer->scans_count,
                'latest_scans' => $farmer->scans->map(fn ($scan): array => [
                    'id' => $scan->id,
                    'disease' => $scan->disease?->name,
                    'scan_date' => $scan->scan_date,
                ])->all(),
            ],
            'flash' => ['success' => session('success')],
        ]);
    }

    public function edit(FarmerProfile $farmer): Response
    {
        Gate::authorize('update', $farmer);

        $farmer->load('user:id,email');

        return Inertia::render('admin/farmers/edit', [
            'farmer' => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'email' => $farmer->user->email,
                'barangay' => $farmer->barangay,
                'municipality' => $farmer->municipality->value,
                'contact_number' => $farmer->contact_number,
                'plot_count' => $farmer->plot_count,
                'farm_lat' => $farmer->farm_lat,
                'farm_long' => $farmer->farm_long,
            ],
            'municipalities' => array_column(MunicipalityEnum::cases(), 'value'),
        ]);
    }

    public function update(UpdateAdminFarmerRequest $request, FarmerProfile $farmer): RedirectResponse
    {
        $validated = $request->validated();
        $user = $farmer->user;

        DB::transaction(function () use ($farmer, $user, $validated): void {
            $emailChanged = $user->email !== $validated['email'];
            $user->name = $validated['full_name'];
            $user->email = $validated['email'];

            if ($emailChanged && config('auth.require_email_verification')) {
                $user->email_verified_at = null;
            }

            $user->save();
            $farmer->update([
                'full_name' => $validated['full_name'],
                'barangay' => $validated['barangay'],
                'municipality' => $validated['municipality'],
                'contact_number' => $validated['contact_number'],
                'farm_lat' => $validated['farm_lat'],
                'farm_long' => $validated['farm_long'],
                'plot_count' => $validated['plot_count'] ?? null,
            ]);
        });

        return to_route('admin.farmers.show', $farmer)
            ->with('success', 'Farmer profile updated.');
    }
}
