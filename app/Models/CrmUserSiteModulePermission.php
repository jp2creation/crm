<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $module_id
 * @property int $permission_id
 * @property int $site_id
 * @property int $user_id
 * @property-read CrmModule|null $module
 * @property-read CrmPermission|null $permission
 * @property-read CrmSite|null $site
 * @property-read CrmUser|null $user
 */
class CrmUserSiteModulePermission extends Model
{
    protected $table = 'crm_user_site_module_permissions';

    protected $fillable = [
        'user_id',
        'site_id',
        'module_id',
        'permission_id',
    ];

    /**
     * @return BelongsTo<CrmUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'user_id');
    }

    /**
     * @return BelongsTo<CrmSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    /**
     * @return BelongsTo<CrmModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CrmModule::class, 'module_id');
    }

    /**
     * @return BelongsTo<CrmPermission, $this>
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(CrmPermission::class, 'permission_id');
    }
}
