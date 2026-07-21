<?php

namespace Modules\CrmCore\Support;

final class CrmModuleStandard
{
    /**
     * @return array<int, string>
     */
    public static function requiredManifestKeys(): array
    {
        return [
            'name',
            'alias',
            'priority',
            'providers',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function requiredPaths(bool $coreModule = false): array
    {
        $paths = [
            'module.json',
            'routes/web.php',
            'app/Providers',
            'database/migrations',
        ];

        if (! $coreModule) {
            $paths[] = 'app/Services';
        }

        return $paths;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function requiredPathGroups(bool $coreModule = false): array
    {
        $groups = [
            ['module.json'],
            ['routes/web.php'],
            ['app/Providers'],
            ['database/migrations'],
        ];

        if (! $coreModule) {
            $groups[] = ['app/Services'];
        }

        return $groups;
    }

    /**
     * @return array<int, string>
     */
    public static function recommendedPaths(): array
    {
        return [
            'app/Data',
            'app/Events',
            'app/Http/Controllers',
            'app/Listeners',
            'database/migrations',
            'resources/assets',
            'tests/Feature',
        ];
    }
}
