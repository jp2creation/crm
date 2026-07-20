<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CrmCore\Services\UploadedCrmFileCleaner;

class CrmCashMovement extends Model
{
    protected $table = 'crm_cash_movements';

    public const TYPE_CASH_IN = 'cash_in';

    public const TYPE_CASH_OUT = 'cash_out';

    protected $fillable = [
        'cash_register_day_id',
        'type',
        'label',
        'amount',
        'occurred_on',
        'sort_order',
        'justification_path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'occurred_on' => 'date',
            'sort_order' => 'integer',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (CrmCashMovement $movement): void {
            if ($movement->wasChanged('justification_path')) {
                app(UploadedCrmFileCleaner::class)->deletePublicUpload($movement->getOriginal('justification_path'));
            }
        });

        static::deleted(function (CrmCashMovement $movement): void {
            app(UploadedCrmFileCleaner::class)->deletePublicUpload($movement->getAttribute('justification_path'));
        });
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_CASH_IN => 'Entree caisse',
            self::TYPE_CASH_OUT => 'Sortie caisse',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(CrmCashRegisterDay::class, 'cash_register_day_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'uploaded_by');
    }
}
