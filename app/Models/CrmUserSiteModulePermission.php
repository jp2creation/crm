<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmUserSiteModulePermission extends Model
{
    protected $table = 'crm_user_site_module_permissions';

    protected $fillable = [
        'user_id',
        'site_id',
        'module_id',
        'permission_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CrmModule::class, 'module_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(CrmPermission::class, 'permission_id');
    }
}
