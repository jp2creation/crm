<?php

namespace App\Support;

final class CrmAsset
{
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        $version = config('crm.assets.version');

        if (! $version) {
            $fullPath = public_path($path);
            $version = is_file($fullPath) ? (string) filemtime($fullPath) : null;
        }

        if (! $version) {
            return asset($path);
        }

        return asset($path).'?v='.rawurlencode((string) $version);
    }
}
