<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

final class HandleOAuthCallbackAction
{
    public function __construct(
        private readonly DownloadUserAvatarAction $downloadAvatar,
    ) {}

    public function handle(SocialiteUser $socialiteUser): User
    {
        $huggyId = (string) $socialiteUser->getId();

        $raw = $socialiteUser->getRaw();
        $photo = $raw['photo'] ?? null;
        $avatarUrl = is_array($photo) ? ($photo['source'] ?? null) : (is_string($photo) ? $photo : null);

        $avatarPath = $avatarUrl
            ? $this->downloadAvatar->handle($avatarUrl, (string) $socialiteUser->getName())
            : null;

        return User::query()->updateOrCreate(
            ['huggy_id' => $huggyId],
            [
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'huggy_access_token' => $socialiteUser->token,
                'huggy_refresh_token' => $socialiteUser->refreshToken,
                'huggy_token_expires_at' => $socialiteUser->expiresIn
                    ? now()->addSeconds((int) $socialiteUser->expiresIn)
                    : null,
                'avatar_path' => $avatarPath,
            ]
        );
    }
}
