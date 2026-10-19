<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $farmer = $this->route('farmer');

        return $farmer instanceof FarmerProfile
            && ($this->user()?->can('update', $farmer) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $farmer = $this->route('farmer');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($farmer instanceof FarmerProfile ? $farmer->user_id : null),
            ],
            'barangay' => ['required', 'string', 'max:255'],
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => ['required', 'string', 'max:20'],
            'farm_lat' => ['required', 'numeric', 'between:14.3,14.95'],
            'farm_long' => ['required', 'numeric', 'between:120.2,120.7'],
            'plot_count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
