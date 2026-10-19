<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexFarmersRequest;
use App\Http\Resources\Api\FarmerProfileResource;
use App\Models\FarmerProfile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FarmerController extends Controller
{
    public function index(IndexFarmersRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $filters = $request->validated();
        $farmers = FarmerProfile::query()
            ->withActiveUser()
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where('full_name', 'like', '%'.$search.'%'),
            )
            ->when(
                $filters['municipality'] ?? null,
                fn ($query, string $municipality) => $query->where('municipality', $municipality),
            )
            ->orderBy('full_name')
            ->paginate($filters['per_page'] ?? 50);

        return FarmerProfileResource::collection($farmers);
    }
}
