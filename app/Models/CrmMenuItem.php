<?php

namespace App\Models;

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

    public function group(): BelongsTo
    {
        return $this->belongsTo(CrmMenuGroup::class, 'group_key', 'menu_key');
    }
}
