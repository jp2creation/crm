<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLeaveEmployee extends Model
{
    protected $table = 'crm_leave_employees';

    protected $fillable = [
        'crm_user_id',
        'name',
        'slug',
        'color',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function crmUser(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CrmLeaveEntry::class, 'employee_id');
    }
}
