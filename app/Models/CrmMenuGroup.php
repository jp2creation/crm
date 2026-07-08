<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function items(): HasMany
    {
        return $this->hasMany(CrmMenuItem::class, 'group_key', 'menu_key');
    }
}
