<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeArchive(Builder $query, CarbonInterface|string|null $cutoff = null): Builder
    {
        $cutoffDate = $cutoff instanceof CarbonInterface
            ? $cutoff->toDateString()
            : (string) ($cutoff ?: now()->subYears((int) config('crm.cash_control.archive_after_years', 3))->toDateString());

        return $query->whereDate('occurred_on', '<', $cutoffDate);
    }

    public function scopeArchiveCandidates(Builder $query, CarbonInterface|string|null $cutoff = null): Builder
    {
        return $this->scopeArchive($query, $cutoff);
    }
}
