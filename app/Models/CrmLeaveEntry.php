<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeaveEntry extends Model
{
    protected $table = 'crm_leave_entries';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'type',
        'period',
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
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(CrmLeaveEmployee::class, 'employee_id');
    }
}
