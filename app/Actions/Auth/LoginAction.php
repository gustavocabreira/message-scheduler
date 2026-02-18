<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\LoginData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /**
     * @throws AuthenticationException
     */
    public function execute(LoginData $data): string
    {
        $user = User::where('email', $data->email)->first();

        if (! $user instanceof User || ! Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        return $user->createToken($data->deviceName)->plainTextToken;
    }
}
