<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmSalesVisit extends Model
{
    protected $table = 'crm_sales_visits';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_DONE = 'done';

    public const STATUS_MISSED = 'missed';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'tour_id',
        'site_id',
        'representative_user_id',
        'customer_name',
        'customer_reference',
        'address',
        'postal_code',
        'city',
        'contact_name',
        'contact_phone',
        'contact_email',
        'planned_at',
        'duration_minutes',
        'visit_type',
        'priority',
        'status',
        'objective',
        'result',
        'next_action',
        'next_action_date',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_at' => 'datetime',
            'next_action_date' => 'date',
            'duration_minutes' => 'integer',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(CrmSalesTour::class, 'tour_id');
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
