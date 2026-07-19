<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLeaveEntry extends Model
{
    protected $table = 'crm_leave_entries';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'type',
        'period',
        'duration_days',
        'status',
        'notes',
        'source',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(CrmLeaveEmployee::class, 'employee_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CrmLeaveTransaction::class, 'entry_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CrmLeaveStatusHistory::class, 'entry_id');
    }
}
