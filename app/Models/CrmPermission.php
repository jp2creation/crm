<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CrmCore\Support\CrmReferenceCache;

/**
 * @property int $id
 * @property string $group_label
 * @property string $label
 * @property string $name
 * @property int $sort_order
 * @property-read Collection<int, CrmUser> $users
 * @property-read Collection<int, CrmUserSiteModulePermission> $siteModulePermissions
 */
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

    /**
     * @return BelongsToMany<CrmUser, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_permissions', 'permission_id', 'user_id');
    }

    /**
     * @return HasMany<CrmUserSiteModulePermission, $this>
     */
    public function siteModulePermissions(): HasMany
    {
        return $this->hasMany(CrmUserSiteModulePermission::class, 'permission_id');
    }
}
