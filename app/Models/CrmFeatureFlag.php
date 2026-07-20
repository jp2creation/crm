<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\CrmCore\Services\CrmFeatureFlagService;
use Modules\CrmCore\Support\CrmReferenceCache;

/**
 * @property int $id
 * @property string|null $description
 * @property bool $enabled
 * @property Carbon|null $ends_at
 * @property string $flag_key
 * @property string $name
 * @property array<string, mixed>|null $payload
 * @property string $scope
 * @property Carbon|null $starts_at
 */
class CrmFeatureFlag extends Model
{
    protected $table = 'crm_feature_flags';

    protected $fillable = [
        'flag_key',
        'scope',
        'name',
        'description',
        'enabled',
        'payload',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'payload' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::flushFeatureCaches();
        });

        static::deleted(function (): void {
            self::flushFeatureCaches();
        });
    }

    private static function flushFeatureCaches(): void
    {
        Cache::forget(CrmFeatureFlagService::CACHE_KEY);
        CrmReferenceCache::forgetModules();
    }
}
