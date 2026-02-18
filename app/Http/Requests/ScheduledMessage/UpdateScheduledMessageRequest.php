<?php

declare(strict_types=1);

namespace App\Http\Requests\ScheduledMessage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduledMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contact_id' => ['sometimes', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string', 'max:10000'],
            'scheduled_at' => ['sometimes', 'date', 'after:now'],
        ];
    }
}
