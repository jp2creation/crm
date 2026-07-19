<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeaveStatusHistory extends Model
{
    protected $table = 'crm_leave_status_histories';

    protected $fillable = [
        'entry_id',
        'employee_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'changed_by');
    }
}
