<?php

namespace App\Support;

/**
 * @deprecated Moved to \Modules\CrmCore\Support\CrmReferenceCache.
 */
final class CrmReferenceCache
{
    public const ACTIVE_SITE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_SITE_ROWS;

    public const ACTIVE_MODULE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_MODULE_ROWS;

    public const PERMISSION_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::PERMISSION_ROWS;

    public const ACTIVE_VEHICLE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_VEHICLE_ROWS;

    public const ACTIVE_EQUIPMENT_CATEGORY_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_EQUIPMENT_CATEGORY_ROWS;

    public const ACTIVE_EQUIPMENT_ITEM_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_EQUIPMENT_ITEM_ROWS;

    public static function forgetSites(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetSites();
    }

    public static function forgetModules(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetModules();
    }

    public static function forgetPermissions(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetPermissions();
    }

    public static function forgetVehicles(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetVehicles();
    }

    public static function forgetEquipmentCategories(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetEquipmentCategories();
    }

    public static function forgetEquipmentItems(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetEquipmentItems();
    }
}
