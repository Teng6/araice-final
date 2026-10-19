<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeactivateUserRequest;
use App\Http\Requests\IndexUsersRequest;
use App\Http\Requests\ReactivateUserRequest;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/create');
    }

    public function index(IndexUsersRequest $request): Response
    {
        $filters = $request->validated();
        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? null;

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'deactivated_at', 'created_at'])
            ->with('farmerProfile:user_id,municipality')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_deactivated' => $user->isDeactivated(),
                'municipality' => $user->role === UserRole::Farmer
                    ? $user->farmerProfile?->municipality?->value
                    : null,
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->role = UserRole::from($validated['role']);
        $user->save();

        return to_route('admin.users.index')
            ->with('success', 'Account created.');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('changeRole', $user);

        $user->role = UserRole::from($request->validated('role'));
        $user->save();

        return to_route('admin.users.index')
            ->with('success', 'Account role updated.');
    }

    public function deactivate(DeactivateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        DB::transaction(function () use ($user): void {
            $user->deactivated_at = now();
            $user->save();
            $user->tokens()->delete();
        });

        return to_route('admin.users.index')
            ->with('success', 'Account deactivated.');
    }

    public function reactivate(ReactivateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $user->deactivated_at = null;
        $user->save();

        return to_route('admin.users.index')
            ->with('success', 'Account reactivated.');
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        DB::transaction(function () use ($request, $user): void {
            $user->password = Hash::make($request->validated('password'));
            $user->remember_token = Str::random(60);
            $user->save();
            $user->tokens()->delete();
        });

        return to_route('admin.users.index')
            ->with('success', "Password reset for {$user->name}.");
    }
}
