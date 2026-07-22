<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CachedBillingStat extends Model
{
    protected $fillable = [
        'site_id',
        'date',
        'client_id',
        'client_name',
        'client_status',
        'product_family',
        'total_amount',
        'invoice_count',
        'quantity',
        'last_invoice_date',
        'raw_payload',
    ];

    /**
     * @return BelongsTo<CrmSite, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'last_invoice_date' => 'date',
            'site_id' => 'integer',
            'total_amount' => 'decimal:2',
            'invoice_count' => 'integer',
            'quantity' => 'decimal:3',
            'raw_payload' => 'array',
        ];
    }
}
