<?php

namespace App\Support;

final class CrmAsset
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $manifest = null;

    /**
     * @var array<string, string|null>
     */
    private static array $importVersions = [];

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        $manifestUrl = self::manifestUrl($path);

        if ($manifestUrl) {
            return $manifestUrl;
        }

        $version = config('crm.assets.version');

        if (! $version) {
            $version = self::importVersion($path);
        }

        if (! $version) {
            $version = self::fileVersion($path);
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

    private static function fileVersion(string $path): ?string
    {
        $fullPath = public_path($path);

        return is_file($fullPath) ? (string) filemtime($fullPath) : null;
    }

    private static function importVersion(string $path): ?string
    {
        $directory = trim(str_replace('\\', '/', dirname($path)), '.');

        if ($directory === '') {
            return null;
        }

        $basename = basename($path);
        $cacheKey = $directory.'/'.$basename;

        if (array_key_exists($cacheKey, self::$importVersions)) {
            return self::$importVersions[$cacheKey];
        }

        $assetDirectory = public_path($directory);

        if (! is_dir($assetDirectory)) {
            return self::$importVersions[$cacheKey] = null;
        }

        $pattern = '#[\\\'"]\\./'.preg_quote($basename, '#').'\\?v=([^\\\'"]+)#';

        foreach (glob($assetDirectory.'/*.js') ?: [] as $assetPath) {
            if (! is_file($assetPath) || basename($assetPath) === $basename) {
                continue;
            }

            $contents = file_get_contents($assetPath);

            if (! is_string($contents) || $contents === '') {
                continue;
            }

            if (preg_match($pattern, $contents, $matches) === 1) {
                return self::$importVersions[$cacheKey] = $matches[1];
            }
        }

        return self::$importVersions[$cacheKey] = null;
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
