<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Models\FarmerProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminFarmersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAdminList', FarmerProfile::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'municipality' => ['nullable', Rule::enum(MunicipalityEnum::class)],
        ];
    }
}
