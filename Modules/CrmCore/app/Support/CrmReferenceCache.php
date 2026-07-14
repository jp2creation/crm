<?php

namespace Modules\CrmCore\Support;

use Illuminate\Support\Facades\Cache;

final class CrmReferenceCache
{
    public const ACTIVE_SITE_ROWS = 'crm:sites:active:rows:v1';

    public const ACTIVE_MODULE_ROWS = 'crm:modules:active:rows:v1';

    public const PERMISSION_ROWS = 'crm:permissions:rows:v1';

    public const ACTIVE_VEHICLE_ROWS = 'crm:vehicles:active:rows:v1';

    public const ACTIVE_EQUIPMENT_CATEGORY_ROWS = 'crm:equipment-categories:active:rows:v1';

    public const ACTIVE_EQUIPMENT_ITEM_ROWS = 'crm:equipment-items:active:rows:v1';

    public static function forgetSites(): void
    {
        Cache::forget(self::ACTIVE_SITE_ROWS);
    }

    public static function forgetModules(): void
    {
        Cache::forget(self::ACTIVE_MODULE_ROWS);
    }

    public static function forgetPermissions(): void
    {
        Cache::forget(self::PERMISSION_ROWS);
    }

    public static function forgetVehicles(): void
    {
        Cache::forget(self::ACTIVE_VEHICLE_ROWS);
    }

    public static function forgetEquipmentCategories(): void
    {
        Cache::forget(self::ACTIVE_EQUIPMENT_CATEGORY_ROWS);
    }

    public static function forgetEquipmentItems(): void
    {
        Cache::forget(self::ACTIVE_EQUIPMENT_ITEM_ROWS);
    }
}
