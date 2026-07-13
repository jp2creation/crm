<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmCheckRemittanceLine extends Model
{
    protected $table = 'crm_check_remittance_lines';

    protected $fillable = [
        'check_remittance_id',
        'payer_name',
        'invoice_number',
        'check_number',
        'bank_name',
        'check_date',
        'amount',
        'photo_path',
        'original_name',
        'mime_type',
        'size',
        'ocr_text',
        'ocr_confidence',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'check_date' => 'date',
            'amount' => 'float',
            'size' => 'integer',
            'ocr_confidence' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(CrmCheckRemittance::class, 'check_remittance_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'updated_by');
    }
}
