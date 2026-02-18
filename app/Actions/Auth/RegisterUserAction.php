<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterData;
use App\Models\User;

class RegisterUserAction
{
    public function execute(RegisterData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'timezone' => $data->timezone,
        ]);
    }
}
