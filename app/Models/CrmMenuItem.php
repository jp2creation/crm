<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CrmCore\Support\CrmReferenceCache;

/**
 * @property int $id
 * @property bool $active
 * @property string $group_key
 * @property string $icon_key
 * @property string $item_key
 * @property string $label
 * @property int $sort_order
 * @property-read CrmMenuGroup|null $group
 */
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

    /**
     * @return BelongsTo<CrmMenuGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CrmMenuGroup::class, 'group_key', 'menu_key');
    }
}
