<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexOutbreaksRequest;
use App\Http\Resources\Api\OutbreakResource;
use App\Models\Outbreak;
use App\Services\CloseOutbreak;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OutbreakController extends Controller
{
    public function index(IndexOutbreaksRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $outbreaks = $this->query()
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status),
            )
            ->when(
                $filters['municipality'] ?? null,
                fn (Builder $query, string $municipality) => $query->where('municipality', $municipality),
            )
            ->when(
                $filters['disease_id'] ?? null,
                fn (Builder $query, int $diseaseId) => $query->where('disease_id', $diseaseId),
            )
            ->latest()
            ->paginate($filters['per_page'] ?? 15);

        return OutbreakResource::collection($outbreaks);
    }

    public function show(Outbreak $outbreak): OutbreakResource
    {
        Gate::authorize('viewAny', Outbreak::class);

        $outbreak = $this->query()
            ->with([
                'alerts' => fn ($query) => $query
                    ->select(['id', 'outbreak_id', 'message', 'severity', 'created_at'])
                    ->latest('id'),
                'scans' => fn ($query) => $query
                    ->select(['id', 'outbreak_id', 'farmer_id', 'disease_id', 'created_at'])
                    ->with([
                        'farmer:id,full_name',
                        'disease:id,name',
                    ])
                    ->latest('id'),
            ])
            ->findOrFail($outbreak->id);

        return new OutbreakResource($outbreak);
    }

    public function close(Request $request, Outbreak $outbreak, CloseOutbreak $closeOutbreak): JsonResponse
    {
        Gate::authorize('close', $outbreak);

        $closed = $closeOutbreak->close($request->user(), $outbreak);
        abort_if($closed === null, 422, 'Outbreak is already resolved.');

        return (new OutbreakResource(
            $this->query()->findOrFail($closed->id),
        ))->response();
    }

    /**
     * @return Builder<Outbreak>
     */
    private function query(): Builder
    {
        return Outbreak::query()
            ->with([
                'disease:id,name',
                'closedBy:id,name',
                'alerts' => fn ($query) => $query
                    ->select(['id', 'outbreak_id', 'severity']),
            ])
            ->withCount('scans')
            ->withCount([
                'scans as farmers_count' => fn (Builder $query) => $query
                    ->select(DB::raw('count(distinct farmer_id)')),
            ]);
    }
}
