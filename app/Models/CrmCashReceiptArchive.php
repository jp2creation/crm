<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCashReceiptArchive extends Model
{
    protected $table = 'crm_cash_receipt_archives';

    protected $fillable = [
        'original_receipt_id',
        'cash_register_day_id',
        'site_id',
        'cash_date',
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
        'archived_at',
        'original_created_at',
        'original_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'cash_date' => 'date',
            'occurred_on' => 'date',
            'invoice_total' => 'float',
            'cash_amount' => 'float',
            'card_amount' => 'float',
            'check_amount' => 'float',
            'transfer_amount' => 'float',
            'control_amount' => 'float',
            'sort_order' => 'integer',
            'archived_at' => 'datetime',
            'original_created_at' => 'datetime',
            'original_updated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }
}
