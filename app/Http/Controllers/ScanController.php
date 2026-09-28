<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatusEnum;
use App\Enums\ScanTypeEnum;
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

        return Inertia::render('scans/create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Scan::class);

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'],
            'scan_type' => ['required', Rule::enum(ScanTypeEnum::class)],
            'variety_id' => ['nullable', 'exists:rice_varieties,id'],
        ]);

        $user = $request->user();
        $profile = $user->farmerProfile;

        $path = $request->file('image')->store('scans', 'public');

        $scan = Scan::create([
            'farmer_id' => $profile->id,
            'uploaded_by' => $user->id,
            'scan_type' => $validated['scan_type'],
            'variety_id' => $validated['variety_id'] ?? null,
            'image_url' => $path,
            'status' => ScanStatusEnum::Pending,
            'gps_lat' => $profile->farm_lat,
            'gps_long' => $profile->farm_long,
        ]);

        return to_route('scans.show', $scan);
    }

    public function show(Scan $scan): Response
    {
        Gate::authorize('view', Scan::class);

        $scan->load(['disease', 'variety']);

        return Inertia::render('scans/show', ['scan' => $scan]);
    }
}
