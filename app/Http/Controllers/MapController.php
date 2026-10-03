<?php

namespace App\Http\Controllers;

use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\Outbreak;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $outbreaks = Outbreak::query()
            ->with('disease:id,name')
            ->where('status', OutbreakStatusEnum::Active)
            ->when(
                $user->role === UserRole::Farmer,
                fn ($query) => $query->where('municipality', $user->farmerProfile?->municipality),
            )
            ->get()
            ->map(fn (Outbreak $outbreak) => [
                'id' => $outbreak->id,
                'disease' => $outbreak->disease->name,
                'municipality' => $outbreak->municipality->value,
                'created_at' => $outbreak->created_at?->toDateString(),
            ]);

        return Inertia::render('map/index', ['outbreaks' => $outbreaks]);
    }
}
