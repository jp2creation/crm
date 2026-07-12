<?php

namespace App\Providers;

use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmMenuGroup;
use App\Models\CrmMenuItem;
use App\Models\CrmModule;
use App\Models\CrmPage;
use App\Models\CrmPermission;
use App\Models\CrmReservation;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmVehicle;
use App\Models\User;
use App\Policies\FilamentAdminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => FilamentAdminPolicy::class,
        Role::class => FilamentAdminPolicy::class,
        CrmEquipmentCategory::class => FilamentAdminPolicy::class,
        CrmEquipmentItem::class => FilamentAdminPolicy::class,
        CrmEquipmentRental::class => FilamentAdminPolicy::class,
        CrmMenuGroup::class => FilamentAdminPolicy::class,
        CrmMenuItem::class => FilamentAdminPolicy::class,
        CrmModule::class => FilamentAdminPolicy::class,
        CrmPage::class => FilamentAdminPolicy::class,
        CrmPermission::class => FilamentAdminPolicy::class,
        CrmReservation::class => FilamentAdminPolicy::class,
        CrmSite::class => FilamentAdminPolicy::class,
        CrmUser::class => FilamentAdminPolicy::class,
        CrmVehicle::class => FilamentAdminPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
