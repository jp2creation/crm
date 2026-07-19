<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardMetric extends Model
{
    protected $table = 'dashboard_metrics';

    protected $fillable = [
        'site_id',
        'metric_date',
        'reservations_today',
        'monthly_revenue',
        'pending_leaves',
        'equipment_available',
        'equipment_total',
        'reservation_trend',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'reservations_today' => 'integer',
            'monthly_revenue' => 'float',
            'pending_leaves' => 'integer',
            'equipment_available' => 'integer',
            'equipment_total' => 'integer',
            'reservation_trend' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    public static function currentForSite(int $siteId, CarbonInterface|string $date): ?self
    {
        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return static::query()
            ->forSite($siteId)
            ->whereDate('metric_date', $dateString)
            ->first();
    }
}
