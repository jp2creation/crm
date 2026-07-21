<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCheckRemittance extends Model
{
    protected $table = 'crm_check_remittances';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_DEPOSITED = 'deposited';

    protected $fillable = [
        'site_id',
        'remittance_date',
        'reference',
        'bank_name',
        'status',
        'check_count',
        'total_amount',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'remittance_date' => 'date',
            'check_count' => 'integer',
            'total_amount' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    /**
     * @return HasMany<CrmCheckRemittanceLine, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(CrmCheckRemittanceLine::class, 'check_remittance_id')
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
