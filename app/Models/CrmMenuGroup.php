<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CrmCore\Support\CrmReferenceCache;

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

    public function items(): HasMany
    {
        return $this->hasMany(CrmMenuItem::class, 'group_key', 'menu_key');
    }
}
