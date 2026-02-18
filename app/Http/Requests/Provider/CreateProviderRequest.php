<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use App\Enums\ProviderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider_type' => ['required', 'string', new Enum(ProviderType::class)],
            'credentials' => ['required', 'string'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
