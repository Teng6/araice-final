<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatusEnum;
use App\Enums\ScanTypeEnum;
use App\Enums\UserRole;
use App\Models\RiceVariety;
use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Scan::class);

        $varieties = RiceVariety::select('id', 'name')->get();

        return Inertia::render('scans/create', ['varieties' => $varieties]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $isLgu = $user->role === UserRole::LguStaff;

        $rules = [
            'image' => ['required', 'image', 'max:5120'],
            'scan_type' => ['required', Rule::enum(ScanTypeEnum::class)],
            'variety_id' => ['nullable', 'exists:rice_varieties,id'],
        ];

        if ($isLgu) {
            $rules['farmer_id'] = ['required', 'exists:farmer_profiles,id'];
            $rules['gps_lat'] = ['required', 'numeric'];
            $rules['gps_long'] = ['required', 'numeric'];
        }

        $validated = $request->validate($rules);

        if ($isLgu) {
            $farmerId = $validated['farmer_id'];
            $gpsLat = $validated['gps_lat'];
            $gpsLong = $validated['gps_long'];
        } else {
            $profile = $user->farmerProfile;
            $farmerId = $profile->id;
            $gpsLat = $profile->farm_lat;
            $gpsLong = $profile->farm_long;
        }

        $path = $request->file('image')->store('scans', 'public');

        $scan = Scan::create([
            'farmer_id' => $farmerId,
            'uploaded_by' => $user->id,
            'scan_type' => $validated['scan_type'],
            'variety_id' => $validated['variety_id'] ?? null,
            'image_url' => $path,
            'status' => ScanStatusEnum::Pending,
            'gps_lat' => $gpsLat,
            'gps_long' => $gpsLong,
        ]);

        return to_route('scans.show', $scan);
    }

    public function show(Scan $scan): Response
    {
        Gate::authorize('view', $scan);

        $scan->load(['disease', 'variety']);

        return Inertia::render('scans/show', ['scan' => $scan]);
    }
}
