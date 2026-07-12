<?php

namespace App\Support;

final class CrmAsset
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $manifest = null;

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        $manifestUrl = self::manifestUrl($path);

        if ($manifestUrl) {
            return $manifestUrl;
        }

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

    private static function manifestUrl(string $path): ?string
    {
        $manifest = self::manifest();
        $entry = $manifest[$path] ?? null;

        if (is_array($entry) && isset($entry['file']) && is_string($entry['file'])) {
            return asset('build/'.$entry['file']);
        }

        foreach ($manifest as $entry) {
            if (! is_array($entry) || ! isset($entry['file']) || ! is_string($entry['file'])) {
                continue;
            }

            if ($entry['file'] === $path || 'build/'.$entry['file'] === $path) {
                return asset('build/'.$entry['file']);
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = public_path('build/manifest.json');

        if (! is_file($path)) {
            return self::$manifest = [];
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        return self::$manifest = is_array($manifest) ? $manifest : [];
    }
}
