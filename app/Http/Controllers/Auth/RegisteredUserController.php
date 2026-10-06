<?php

namespace App\Http\Controllers\Auth;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'municipalities' => array_column(MunicipalityEnum::cases(), 'value'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'barangay' => 'required|string|max:255',
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => 'required|string|max:20',
            'farm_lat' => 'required|numeric|between:14.3,14.95',
            'farm_long' => 'required|numeric|between:120.2,120.7',
        ],
            [
                'farm_lat.between' => 'Latitude must be within Bataan (between 14.3 and 14.95).',
                'farm_long.between' => 'Longitude must be within Bataan (between 120.2 and 120.7).',
            ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            if (! config('auth.require_email_verification')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->farmerProfile()->create([
                'full_name' => $validated['name'],
                'barangay' => $validated['barangay'],
                'municipality' => $validated['municipality'],
                'contact_number' => $validated['contact_number'],
                'farm_lat' => $validated['farm_lat'],
                'farm_long' => $validated['farm_long'],
            ]);

            return $user;
        });
        if (config('auth.require_email_verification')) {
            event(new Registered($user));
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
