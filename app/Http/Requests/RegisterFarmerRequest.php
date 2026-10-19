<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use LogicException;

class RegisterFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'barangay' => ['required', 'string', 'max:255'],
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => ['required', 'string', 'max:20'],
            'farm_lat' => ['required', 'numeric', 'between:14.3,14.95'],
            'farm_long' => ['required', 'numeric', 'between:120.2,120.7'],
            'plot_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'farm_lat.between' => 'Latitude must be within Bataan (between 14.3 and 14.95).',
            'farm_long.between' => 'Longitude must be within Bataan (between 120.2 and 120.7).',
        ];
    }

    /**
     * @return array{name: string, email: string, password: string, barangay: string, municipality: string, contact_number: string, farm_lat: float, farm_long: float, plot_count: int|null}
     */
    public function registrationData(): array
    {
        $validated = $this->validated();

        if (
            ! is_string($validated['name'] ?? null)
            || ! is_string($validated['email'] ?? null)
            || ! is_string($validated['password'] ?? null)
            || ! is_string($validated['barangay'] ?? null)
            || ! is_string($validated['municipality'] ?? null)
            || ! is_string($validated['contact_number'] ?? null)
            || ! is_numeric($validated['farm_lat'] ?? null)
            || ! is_numeric($validated['farm_long'] ?? null)
            || (isset($validated['plot_count']) && filter_var($validated['plot_count'], FILTER_VALIDATE_INT) === false)
        ) {
            throw new LogicException('Validated farmer registration data has an unexpected shape.');
        }

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'barangay' => $validated['barangay'],
            'municipality' => $validated['municipality'],
            'contact_number' => $validated['contact_number'],
            'farm_lat' => (float) $validated['farm_lat'],
            'farm_long' => (float) $validated['farm_long'],
            'plot_count' => isset($validated['plot_count']) ? (int) $validated['plot_count'] : null,
        ];
    }
}
