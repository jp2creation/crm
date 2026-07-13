<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCashCountLine extends Model
{
    protected $table = 'crm_cash_count_lines';

    protected $fillable = [
        'cash_register_day_id',
        'kind',
        'denomination',
        'previous_quantity',
        'current_quantity',
        'deposit_quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'denomination' => 'float',
            'previous_quantity' => 'integer',
            'current_quantity' => 'integer',
            'deposit_quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(CrmCashRegisterDay::class, 'cash_register_day_id');
    }
}
