<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class DownloadUserAvatarAction
{
    public function handle(string $avatarUrl): string
    {
        $urlPath = (string) parse_url($avatarUrl, PHP_URL_PATH);
        $filename = pathinfo($urlPath, PATHINFO_BASENAME) ?: 'avatar.jpg';

        $path = 'avatars/'.$filename;

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $response = Http::get($avatarUrl);

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }
}
