<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\UpdateUserPassword;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdatePasswordRequest $request, UpdateUserPassword $updateUserPassword): RedirectResponse
    {
        $updateUserPassword->update(
            $request->user(),
            $request->validated('password'),
        );

        return back();
    }
}
