<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CrmCore\Support\CrmReferenceCache;

/**
 * @property int $id
 * @property bool $active
 * @property string $menu_key
 * @property int $sort_order
 * @property string $title
 * @property-read Collection<int, CrmMenuItem> $items
 */
class CrmMenuGroup extends Model
{
    protected $table = 'crm_menu_groups';

    protected $fillable = [
        'menu_key',
        'title',
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
     * @return HasMany<CrmMenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CrmMenuItem::class, 'group_key', 'menu_key');
    }
}
