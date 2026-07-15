<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDepositRequest extends Model
{
    protected $table = 'crm_deposit_requests';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VALIDATED = 'validated';

    protected $fillable = [
        'site_id',
        'request_date',
        'requester_user_id',
        'requester_name',
        'document_number',
        'client_name',
        'amount',
        'status',
        'validated_at',
        'validated_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'amount' => 'float',
            'validated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'requester_user_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'validated_by');
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
