<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListTenantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
