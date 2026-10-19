<?php

namespace App\Http\Requests;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Models\Outbreak;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOutbreaksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Outbreak::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::enum(OutbreakStatusEnum::class)],
            'municipality' => ['sometimes', 'string', Rule::enum(MunicipalityEnum::class)],
            'disease_id' => ['sometimes', 'integer', 'exists:diseases,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
