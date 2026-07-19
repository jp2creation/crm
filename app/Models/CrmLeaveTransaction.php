<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeaveTransaction extends Model
{
    protected $table = 'crm_leave_transactions';

    protected $fillable = [
        'entry_id',
        'employee_id',
        'type',
        'year',
        'amount_days',
        'balance_after',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount_days' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(CrmLeaveEmployee::class, 'employee_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(CrmLeaveEntry::class, 'entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
