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
 * @property string|null $external_id
 * @property string $number
 * @property string $customer_name
 * @property string|null $customer_reference
 * @property Carbon|null $due_date
 * @property Carbon|null $issue_date
 * @property string $status
 * @property float $subtotal
 * @property float $total
 * @property float $margin
 * @property float $commission_base
 * @property Carbon|null $synced_at
 * @property-read CrmSite $site
 * @property-read CrmUser|null $representative
 */
class CrmSalesInvoice extends Model
{
    protected $table = 'crm_sales_invoices';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'site_id',
        'representative_user_id',
        'external_id',
        'number',
        'customer_name',
        'customer_reference',
        'issue_date',
        'due_date',
        'status',
        'subtotal',
        'total',
        'margin',
        'commission_base',
        'raw_data',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'float',
            'total' => 'float',
            'margin' => 'float',
            'commission_base' => 'float',
            'raw_data' => 'array',
            'synced_at' => 'datetime',
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

    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }
}
