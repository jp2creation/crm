<?php

namespace App\Support;

/**
 * @deprecated Moved to \Modules\CrmCore\Support\CrmReferenceCache.
 */
final class CrmReferenceCache
{
    public const ACTIVE_SITE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_SITE_ROWS;

    public const ACTIVE_SITE_IDS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_SITE_IDS;

    public const ACTIVE_MODULE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_MODULE_ROWS;

    public const ACTIVE_MODULE_LOOKUP = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_MODULE_LOOKUP;

    public const PERMISSION_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::PERMISSION_ROWS;

    public const PERMISSION_ID_LOOKUP = \Modules\CrmCore\Support\CrmReferenceCache::PERMISSION_ID_LOOKUP;

    public const ACTIVE_USER_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_USER_ROWS;

    public const ACTIVE_VEHICLE_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_VEHICLE_ROWS;

    public const ACTIVE_EQUIPMENT_CATEGORY_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_EQUIPMENT_CATEGORY_ROWS;

    public const ACTIVE_EQUIPMENT_ITEM_ROWS = \Modules\CrmCore\Support\CrmReferenceCache::ACTIVE_EQUIPMENT_ITEM_ROWS;

    public static function activeSiteRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeSiteRows();
    }

    public static function activeSiteIds(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeSiteIds();
    }

    public static function activeSiteExists(int $siteId): bool
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeSiteExists($siteId);
    }

    public static function activeMenuModuleRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeMenuModuleRows();
    }

    public static function activeModuleLookup(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeModuleLookup();
    }

    public static function activeModule(string $slug): ?array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeModule($slug);
    }

    public static function permissionRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::permissionRows();
    }

    public static function permissionIdLookup(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::permissionIdLookup();
    }

    public static function permissionId(string $name): ?int
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::permissionId($name);
    }

    public static function permissionIds(array $names): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::permissionIds($names);
    }

    public static function activeUserRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeUserRows();
    }

    public static function activeVehicleRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeVehicleRows();
    }

    public static function activeEquipmentCategoryRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeEquipmentCategoryRows();
    }

    public static function activeEquipmentItemRows(): array
    {
        return \Modules\CrmCore\Support\CrmReferenceCache::activeEquipmentItemRows();
    }

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

    public static function forgetUsers(): void
    {
        \Modules\CrmCore\Support\CrmReferenceCache::forgetUsers();
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
