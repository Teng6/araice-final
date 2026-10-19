<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\RegisterFarmerRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\CreateFarmerRegistration;
use App\Services\UpdateUserPassword;
use App\Services\UpdateUserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(
        RegisterFarmerRequest $request,
        CreateFarmerRegistration $createFarmer,
    ): JsonResponse {
        $user = $createFarmer->create($request->registrationData());

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->userData($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if ($user->isDeactivated()) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact an administrator.',
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->userData($user),
        ]);
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json((new UserResource($request->user()))->resolve());
    }

    public function updateProfile(
        ProfileUpdateRequest $request,
        UpdateUserProfile $updateUserProfile,
    ): JsonResponse {
        $user = $updateUserProfile->update($request->user(), $request->validated());

        return response()->json((new UserResource($user))->resolve());
    }

    public function updatePassword(
        UpdatePasswordRequest $request,
        UpdateUserPassword $updateUserPassword,
    ): JsonResponse {
        $updateUserPassword->update(
            $request->user(),
            $request->validated('password'),
            revokeOtherTokens: true,
        );

        return response()->json(['message' => 'Password updated successfully.']);
    }
}
