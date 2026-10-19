<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'barangay' => ['sometimes', 'required', 'string', 'max:255'],
            'municipality' => ['sometimes', 'required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => ['sometimes', 'required', 'string', 'max:20'],
            'farm_lat' => ['sometimes', 'required', 'numeric', 'between:14.3,14.95'],
            'farm_long' => ['sometimes', 'required', 'numeric', 'between:120.2,120.7'],
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
}
