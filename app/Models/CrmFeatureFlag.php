<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\CrmCore\Services\CrmFeatureFlagService;
use Modules\CrmCore\Support\CrmReferenceCache;

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
            static::flushFeatureCaches();
        });

        static::deleted(function (): void {
            static::flushFeatureCaches();
        });
    }

    private static function flushFeatureCaches(): void
    {
        Cache::forget(CrmFeatureFlagService::CACHE_KEY);
        CrmReferenceCache::forgetModules();
    }
}
