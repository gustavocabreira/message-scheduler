<?php

declare(strict_types=1);

namespace App\Http\Requests\ScheduledMessage;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduledMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider_connection_id' => ['required', 'integer', 'exists:provider_connections,id'],
            'contact_id' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
