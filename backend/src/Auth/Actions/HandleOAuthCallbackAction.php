<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Two\User as SocialiteUser;

final class HandleOAuthCallbackAction
{
    public function handle(SocialiteUser $socialiteUser): User
    {
        $user = User::updateOrCreate(
            ['huggy_id' => (string) $socialiteUser->getId()],
            [
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'huggy_access_token' => $socialiteUser->token,
                'huggy_refresh_token' => $socialiteUser->refreshToken,
                'huggy_token_expires_at' => $socialiteUser->expiresIn
                    ? now()->addSeconds((int) $socialiteUser->expiresIn)
                    : null,
            ]
        );

        Auth::login($user, remember: true);

        return $user;
    }
}
