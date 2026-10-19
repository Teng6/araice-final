<?php

namespace App\Http\Controllers;

use App\Models\Outbreak;
use App\Services\CloseOutbreak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OutbreakController extends Controller
{
    public function close(Request $request, Outbreak $outbreak, CloseOutbreak $closeOutbreak): RedirectResponse
    {
        Gate::authorize('close', $outbreak);

        abort_if(
            $closeOutbreak->close($request->user(), $outbreak) === null,
            409,
            'Outbreak is already resolved.',
        );

        return back();
    }

    public function index(): Response
    {
        Gate::authorize('viewAny', Outbreak::class);

        $outbreaks = Outbreak::query()
            ->with([
                'disease:id,name',
                'closedBy:id,name',
                'alerts' => fn ($q) => $q->latest('id'),
            ])
            ->withCount('scans')
            ->latest()
            ->get()
            ->map(fn (Outbreak $o) => [
                'id' => $o->id,
                'disease' => $o->disease->name,
                'municipality' => $o->municipality,
                'status' => $o->status,
                'severity' => $o->alerts->first()?->severity,
                'scans_count' => $o->scans_count,
                'created_at' => $o->created_at,
                'closed_at' => $o->closed_at,
                'closed_by' => $o->closedBy?->name,
            ]);

        return Inertia::render('outbreaks/index', [
            'outbreaks' => $outbreaks,
        ]);
    }
}
