<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DownloadUserAvatarAction
{
    public function handle(string $avatarUrl, string $name): string
    {
        $urlPath = (string) parse_url($avatarUrl, PHP_URL_PATH);
        $extension = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'jpg';

        $path = 'avatars/'.Str::slug($name).'.'.$extension;

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $response = Http::get($avatarUrl);

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }
}
