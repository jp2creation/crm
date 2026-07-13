<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCashReceipt extends Model
{
    protected $table = 'crm_cash_receipts';

    protected $fillable = [
        'cash_register_day_id',
        'invoice_number',
        'customer_name',
        'occurred_on',
        'invoice_total',
        'cash_amount',
        'card_amount',
        'check_amount',
        'transfer_amount',
        'control_amount',
        'payment_note',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'invoice_total' => 'float',
            'cash_amount' => 'float',
            'card_amount' => 'float',
            'check_amount' => 'float',
            'transfer_amount' => 'float',
            'control_amount' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(CrmCashRegisterDay::class, 'cash_register_day_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'updated_by');
    }
}
