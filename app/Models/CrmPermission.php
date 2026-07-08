<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_permissions', 'permission_id', 'user_id');
    }
}
