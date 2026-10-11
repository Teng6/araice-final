<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Services\OutbreakDetectionService;
use App\Services\ViTService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ScanController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize('create', Scan::class);

        return Inertia::render('scans/create', [
            'farmers' => $request->user()->role === UserRole::LguStaff
                ? FarmerProfile::orderBy('full_name')->get(['id', 'full_name', 'barangay'])
                : [],
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Scan::class);

        $user = $request->user();

        $query = Scan::with('disease:id,name')->latest();

        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;
            abort_unless($profileId !== null, 403);

            $query->where('farmer_id', $profileId);
        } else {
            $query->with('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/index', [
            'scans' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, ViTService $vit, OutbreakDetectionService $outbreaks): RedirectResponse
    {

        set_time_limit(180);

        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $isLgu = $user->role === UserRole::LguStaff;

        $rules = [
            'image' => ['required', 'image', 'max:5120'],
            'gps_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'gps_long' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        if ($isLgu) {
            $rules['farmer_id'] = ['required', 'exists:farmer_profiles,id'];
        }

        $validated = $request->validate($rules);

        $profile = $isLgu
            ? FarmerProfile::findOrFail((int) $validated['farmer_id'])
            : $user->farmerProfile;
        $farmerId = $profile->id;
        $gpsLat = $validated['gps_lat'] ?? $profile->farm_lat;
        $gpsLong = $validated['gps_long'] ?? $profile->farm_long;

        $path = $request->file('image')->store('scans', 'public');
        abort_unless(is_string($path), 500, 'Failed to store image.');

        $scan = Scan::create([
            'farmer_id' => $farmerId,
            'uploaded_by_id' => $user->id,
            'image_url' => $path,
            'status' => ScanStatusEnum::Processing,
            'gps_lat' => $gpsLat,
            'gps_long' => $gpsLong,
        ]);

        try {
            $fullPath = Storage::disk('public')->path($path);
            $result = $vit->predict($fullPath);

            $scan->fill([
                'disease_id' => $result['disease']?->id,
                'confidence_score' => $result['confidence'],
                'raw_predictions' => $result['raw'],
                'status' => ScanStatusEnum::Completed,
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $scan->update(['status' => ScanStatusEnum::Failed]);
        }

        if ($scan->status === ScanStatusEnum::Completed) {
            try {
                $outbreaks->handle($scan);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return to_route('scans.show', $scan);
    }

    public function show(Request $request, Scan $scan): Response
    {
        Gate::authorize('view', $scan);

        $scan->load('disease.treatments');

        if ($request->user()->role !== UserRole::Farmer) {
            $scan->load('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/show', ['scan' => $scan]);
    }
}
