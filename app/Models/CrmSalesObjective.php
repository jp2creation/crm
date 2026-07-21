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
 * @property string|null $notes
 * @property Carbon|null $period_end
 * @property Carbon|null $period_start
 * @property float $target_revenue
 * @property float $target_margin
 * @property int $target_visits
 * @property-read CrmSite $site
 * @property-read CrmUser|null $representative
 */
class CrmSalesObjective extends Model
{
    protected $table = 'crm_sales_objectives';

    protected $fillable = [
        'site_id',
        'representative_user_id',
        'period_start',
        'period_end',
        'target_revenue',
        'target_margin',
        'target_visits',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'target_revenue' => 'float',
            'target_margin' => 'float',
            'target_visits' => 'integer',
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
