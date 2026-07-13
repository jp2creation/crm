<?php

namespace App\Models;

use App\Support\CrmReferenceCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMenuItem extends Model
{
    protected $table = 'crm_menu_items';

    protected $fillable = [
        'item_key',
        'group_key',
        'icon_key',
        'label',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            CrmReferenceCache::forgetModules();
        });

        static::deleted(function (): void {
            CrmReferenceCache::forgetModules();
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CrmMenuGroup::class, 'group_key', 'menu_key');
    }
}
