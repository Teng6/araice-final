<?php

namespace App\Http\Controllers\Auth;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFarmerRequest;
use App\Services\CreateFarmerRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

    public function store(RegisterFarmerRequest $request, CreateFarmerRegistration $createFarmer): RedirectResponse
    {
        $user = $createFarmer->create($request->registrationData());

        if (config('auth.require_email_verification')) {
            event(new Registered($user));
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
