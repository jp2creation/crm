<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CrmCore\Support\CrmReferenceCache;

class CrmPermission extends Model
{
    protected $table = 'crm_permissions';

    protected $fillable = [
        'name',
        'label',
        'group_label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            CrmReferenceCache::forgetPermissions();
        });

        static::deleted(function (): void {
            CrmReferenceCache::forgetPermissions();
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_permissions', 'permission_id', 'user_id');
    }

    public function siteModulePermissions(): HasMany
    {
        return $this->hasMany(CrmUserSiteModulePermission::class, 'permission_id');
    }
}
