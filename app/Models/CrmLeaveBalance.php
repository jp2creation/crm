<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeaveBalance extends Model
{
    protected $table = 'crm_leave_balances';

    protected $fillable = [
        'employee_id',
        'type',
        'year',
        'entitled_days',
        'carried_over_days',
        'used_days',
        'pending_days',
        'remaining_days',
        'recalculated_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled_days' => 'float',
            'carried_over_days' => 'float',
            'used_days' => 'float',
            'pending_days' => 'float',
            'remaining_days' => 'float',
            'recalculated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(CrmLeaveEmployee::class, 'employee_id');
    }
}
