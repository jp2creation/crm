<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $site_id
 * @property int|null $representative_user_id
 * @property int|null $invoice_id
 * @property string|null $notes
 * @property Carbon|null $paid_at
 * @property Carbon|null $period_end
 * @property Carbon|null $period_start
 * @property string $status
 * @property float $amount
 * @property-read CrmSite $site
 * @property-read CrmUser|null $representative
 * @property-read CrmSalesInvoice|null $invoice
 */
class CrmSalesCommission extends Model
{
    protected $table = 'crm_sales_commissions';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACQUIRED = 'acquired';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'site_id',
        'representative_user_id',
        'invoice_id',
        'period_start',
        'period_end',
        'amount',
        'status',
        'paid_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'representative_user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CrmSalesInvoice::class, 'invoice_id');
    }

    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }
}
