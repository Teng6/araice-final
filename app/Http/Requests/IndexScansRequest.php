<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Enums\ScanStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexScansRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'farmer_id' => ['sometimes', 'integer', 'exists:farmer_profiles,id'],
            'municipality' => ['sometimes', 'string', Rule::enum(MunicipalityEnum::class)],
            'disease_id' => ['sometimes', 'integer', 'exists:diseases,id'],
            'status' => ['sometimes', 'string', Rule::enum(ScanStatusEnum::class)],
        ];
    }
}
