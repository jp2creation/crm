<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCashRegisterDay extends Model
{
    protected $table = 'crm_cash_register_days';

    public const STATUS_OK = 'ok';

    public const STATUS_ANOMALY = 'anomaly';

    public const STATUS_REVIEW = 'review';

    protected $fillable = [
        'site_id',
        'cash_date',
        'opening_balance',
        'invoice_total',
        'cash_sales',
        'card_sales',
        'check_sales',
        'transfer_sales',
        'counted_cash',
        'bank_counted',
        'check_counted',
        'transfer_counted',
        'card_counted',
        'invoice_errors_count',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cash_date' => 'date',
            'opening_balance' => 'float',
            'invoice_total' => 'float',
            'cash_sales' => 'float',
            'card_sales' => 'float',
            'check_sales' => 'float',
            'transfer_sales' => 'float',
            'counted_cash' => 'float',
            'bank_counted' => 'float',
            'check_counted' => 'float',
            'transfer_counted' => 'float',
            'card_counted' => 'float',
            'invoice_errors_count' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    /**
     * @return HasMany<CrmCashMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CrmCashMovement::class, 'cash_register_day_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<CrmCashReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(CrmCashReceipt::class, 'cash_register_day_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<CrmCashCountLine, $this>
     */
    public function cashCountLines(): HasMany
    {
        return $this->hasMany(CrmCashCountLine::class, 'cash_register_day_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'updated_by');
    }

    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }
}
