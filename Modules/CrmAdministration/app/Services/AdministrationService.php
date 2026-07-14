<?php

namespace Modules\CrmAdministration\Services;

use App\Models\CrmMenuGroup;
use App\Models\CrmMenuItem;
use App\Models\CrmModule;
use App\Models\CrmPage;
use App\Models\CrmPermission;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmUserSiteModulePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Services\CrmAccessService;
use Modules\CrmCore\Services\CrmActivityLogger;
use Modules\CrmCore\Support\CrmReferenceCache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdministrationService
{
    private const DEFAULT_PROFILE_PHOTO = '/assets/logo/logomark.png';

    public function __construct(
        private readonly CrmActivityLogger $activity,
        private readonly CrmAccessService $access,
    ) {}

    public function ensureDefaults(): void
    {
        DB::transaction(function (): void {
            foreach ($this->permissionSeed() as [$name, $label, $group, $sortOrder]) {
                CrmPermission::query()->updateOrCreate(
                    ['name' => $name],
                    [
                        'label' => $label,
                        'group_label' => $group,
                        'sort_order' => $sortOrder,
                    ],
                );
            }

            $legacyPermissionId = CrmPermission::query()
                ->where('name', 'reservations.manage_sites')
                ->value('id');

            if ($legacyPermissionId) {
                DB::table('crm_user_permissions')->where('permission_id', $legacyPermissionId)->delete();
                CrmPermission::query()->whereKey($legacyPermissionId)->delete();
            }

            foreach ($this->moduleSeed() as [$name, $slug, $description, $routePath, $sortOrder, $active]) {
                CrmModule::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => $description,
                        'route_path' => $routePath,
                        'active' => $active,
                        'sort_order' => $sortOrder,
                    ],
                );
            }

            CrmModule::query()
                ->where('slug', 'reservations')
                ->whereNull('menu_badge')
                ->update(['menu_badge' => 'Martin', 'show_menu_badge' => true]);

            foreach ($this->menuGroupSeed() as [$menuKey, $title, $sortOrder, $active]) {
                CrmMenuGroup::query()->updateOrCreate(
                    ['menu_key' => $menuKey],
                    ['title' => $title, 'active' => $active, 'sort_order' => $sortOrder],
                );
            }

            $this->deleteObsoleteMenuEntries();

            foreach ($this->staticMenuItemSeed() as $item) {
                $this->ensureMenuItem($item);
            }

            CrmModule::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name', 'slug', 'active', 'sort_order'])
                ->each(function (CrmModule $module): void {
                    $this->ensureMenuItem([
                        'module:'.$module->slug,
                        $this->defaultModuleMenuGroup($module->slug),
                        $module->name,
                        CrmModule::defaultIconKey($module->slug),
                        (int) $module->sort_order,
                    ]);

                    if (! $module->active) {
                        CrmMenuItem::query()
                            ->where('item_key', 'module:'.$module->slug)
                            ->update(['active' => false]);
                    }
                });

            $this->syncPagesMenuGroupVisibility();

            $siteIds = $this->ensureDefaultSites();
            $this->ensureDefaultUsers($siteIds);
            $this->ensureDefaultProfilePhotos();
        });
    }

    public function actorForUser(User $user): CrmUser
    {
        $actor = CrmUser::query()
            ->with(['modules:id,slug,active', 'permissions:id,name,label,sort_order', 'sites:id'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (! $actor) {
            $this->fail('Utilisateur CRM introuvable', 404);
        }

        return $actor;
    }

    public function profile(CrmUser $actor): array
    {
        return ['ok' => true, 'profile' => $this->profilePayload($actor)];
    }

    public function saveProfile(CrmUser $actor, array $data): array
    {
        $profile = $this->profilePayload($actor);
        $canEditIdentity = (bool) $profile['canEditIdentity'];

        $firstName = $canEditIdentity
            ? trim((string) ($data['firstName'] ?? $data['first_name'] ?? $profile['firstName']))
            : $profile['firstName'];
        $lastName = $canEditIdentity
            ? trim((string) ($data['lastName'] ?? $data['last_name'] ?? $profile['lastName']))
            : $profile['lastName'];
        $email = trim((string) ($data['email'] ?? $profile['email']));
        $bio = trim((string) ($data['bio'] ?? $profile['bio']));
        $photoUrl = (string) $profile['photoUrl'];
        $photoDataUrl = (string) ($data['photoDataUrl'] ?? $data['photo_data_url'] ?? '');

        if ($firstName === '') {
            $this->fail('Prenom obligatoire', 400);
        }

        if (mb_strlen($firstName) > 80 || mb_strlen($lastName) > 80) {
            $this->fail('Prenom ou nom trop long', 400);
        }

        if ($email !== '' && (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) {
            $this->fail('Adresse e-mail invalide', 400);
        }

        if (mb_strlen($bio) > 255) {
            $this->fail('Bio trop longue', 400);
        }

        if ($photoDataUrl !== '') {
            $photoUrl = $this->saveDataImage($photoDataUrl, 'profiles') ?: $photoUrl;
        }

        $actor->forceFill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'bio' => $bio,
            'photo_url' => $photoUrl,
        ])->save();

        $this->log($actor, 'modification profil', $email);

        return ['ok' => true, 'profile' => $this->profilePayload($actor->refresh())];
    }

    public function bootstrap(CrmUser $actor): array
    {
        $this->requireAny($actor, [
            'platform.manage_users',
            'platform.manage_modules',
            'platform.manage_sites',
            'platform.manage_roles',
        ]);

        $sites = CrmSite::query()
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();
        $modules = CrmModule::query()
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $menuGroups = CrmMenuGroup::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
        $menuItems = CrmMenuItem::query()
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
        $permissions = CrmPermission::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $pages = CrmPage::query()
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
        $users = CrmUser::query()
            ->with(['sites:id', 'modules:id', 'permissions:id,name', 'siteModulePermissions:id,user_id,site_id,module_id,permission_id'])
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return [
            'ok' => true,
            'actor' => $this->actorRow($actor),
            'roles' => $this->roleProfiles(),
            'sites' => $sites->map(fn (CrmSite $site): array => $this->siteRow($site))->values()->all(),
            'modules' => $modules->map(fn (CrmModule $module): array => $this->moduleRow($module))->values()->all(),
            'menuGroups' => $menuGroups->map(fn (CrmMenuGroup $group): array => $this->menuGroupRow($group))->values()->all(),
            'menuItems' => $menuItems->map(fn (CrmMenuItem $item): array => $this->menuItemRow($item))->values()->all(),
            'permissions' => $permissions->map(fn (CrmPermission $permission): array => $this->permissionRow($permission))->values()->all(),
            'pages' => $pages->map(fn (CrmPage $page): array => $this->pageRow($page))->values()->all(),
            'users' => $users->map(fn (CrmUser $user): array => $this->userRow($user))->values()->all(),
        ];
    }

    public function saveMenuSettings(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['platform.manage_modules']);

        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        DB::transaction(function () use ($groups, $items): void {
            $groupKeys = CrmMenuGroup::query()->pluck('menu_key')->mapWithKeys(fn (string $key): array => [$key => true])->all();
            $itemKeys = CrmMenuItem::query()->pluck('item_key')->mapWithKeys(fn (string $key): array => [$key => true])->all();

            foreach ($groups as $groupData) {
                $menuKey = trim((string) ($groupData['menuKey'] ?? $groupData['menu_key'] ?? ''));
                if (! isset($groupKeys[$menuKey])) {
                    continue;
                }

                $title = trim((string) ($groupData['title'] ?? ''));
                if ($title === '') {
                    $this->fail('Titre de groupe obligatoire', 400);
                }
                if (mb_strlen($title) > 120) {
                    $this->fail('Titre de groupe trop long', 400);
                }

                CrmMenuGroup::query()
                    ->where('menu_key', $menuKey)
                    ->update([
                        'title' => $title,
                        'active' => $this->boolean($groupData['active'] ?? null, true),
                        'sort_order' => (int) ($groupData['sortOrder'] ?? $groupData['sort_order'] ?? 100),
                        'updated_at' => now(),
                    ]);
            }

            foreach ($items as $itemData) {
                $itemKey = trim((string) ($itemData['itemKey'] ?? $itemData['item_key'] ?? ''));
                if (! isset($itemKeys[$itemKey])) {
                    continue;
                }

                $groupKey = trim((string) ($itemData['groupKey'] ?? $itemData['group_key'] ?? ''));
                if (! isset($groupKeys[$groupKey])) {
                    $this->fail('Groupe de menu invalide', 400);
                }

                $iconKey = trim((string) ($itemData['iconKey'] ?? $itemData['icon_key'] ?? ''));
                if ($iconKey !== '' && ! preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $iconKey)) {
                    $this->fail('Icone de menu invalide', 400);
                }

                $label = trim((string) ($itemData['label'] ?? ''));
                if ($label === '') {
                    $this->fail('Titre de lien obligatoire', 400);
                }
                if (mb_strlen($label) > 160) {
                    $this->fail('Titre de lien trop long', 400);
                }

                CrmMenuItem::query()
                    ->where('item_key', $itemKey)
                    ->update([
                        'group_key' => $groupKey,
                        'icon_key' => $iconKey,
                        'label' => $label,
                        'active' => $this->boolean($itemData['active'] ?? null, true),
                        'sort_order' => (int) ($itemData['sortOrder'] ?? $itemData['sort_order'] ?? 100),
                        'updated_at' => now(),
                    ]);
            }
        });

        CrmReferenceCache::forgetModules();
        $this->log($actor, 'modification menu', 'configuration menu lateral');

        return ['ok' => true];
    }

    public function pagesBootstrap(CrmUser $actor): array
    {
        $this->requireAny($actor, ['pages.manage', 'platform.manage_modules']);

        $pages = CrmPage::query()
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return ['ok' => true, 'pages' => $pages->map(fn (CrmPage $page): array => $this->pageRow($page))->values()->all()];
    }

    public function savePage(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['pages.manage', 'platform.manage_modules']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $title = trim((string) ($data['title'] ?? ''));
            $excerpt = trim((string) ($data['excerpt'] ?? ''));
            $content = trim((string) ($data['content'] ?? ''));
            $iconKey = $this->iconKey((string) ($data['iconKey'] ?? $data['icon_key'] ?? 'article'));

            if ($title === '') {
                $this->fail('Titre de page obligatoire', 400);
            }
            if (mb_strlen($title) > 160) {
                $this->fail('Titre de page trop long', 400);
            }
            if (mb_strlen($excerpt) > 255) {
                $this->fail('Resume trop long', 400);
            }
            if ($content === '') {
                $this->fail('Contenu obligatoire', 400);
            }

            $page = $id > 0 ? CrmPage::query()->lockForUpdate()->find($id) : new CrmPage;
            if ($id > 0 && ! $page) {
                $this->fail('Page introuvable', 404);
            }

            $page->fill([
                'title' => $title,
                'slug' => CrmPage::uniqueSlug((string) ($data['slug'] ?? $title), $id ?: null),
                'excerpt' => $excerpt,
                'content' => $content,
                'icon_key' => $iconKey,
                'active' => $this->boolean($data['active'] ?? null, true),
                'show_in_menu' => $this->boolean($data['showInMenu'] ?? $data['show_in_menu'] ?? null, true),
                'sort_order' => (int) ($data['sortOrder'] ?? $data['sort_order'] ?? 100),
            ])->save();

            $this->log($actor, $id > 0 ? 'modification page CRM' : 'creation page CRM', $title);

            return ['ok' => true, 'page' => $this->pageRow($page->refresh())];
        });
    }

    public function deletePage(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['pages.manage', 'platform.manage_modules']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);
            if ($id <= 0) {
                $this->fail('Page invalide', 400);
            }

            $page = CrmPage::query()->lockForUpdate()->find($id);
            if (! $page) {
                $this->fail('Page introuvable', 404);
            }

            $title = $page->title;
            $page->delete();
            $this->log($actor, 'suppression page CRM', $title);

            return ['ok' => true, 'id' => $id];
        });
    }

    public function saveSite(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['platform.manage_sites', 'platform.manage_modules']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                $this->fail('Nom du site obligatoire', 400);
            }
            if (mb_strlen($name) > 120) {
                $this->fail('Nom du site trop long', 400);
            }

            $site = $id > 0 ? CrmSite::query()->lockForUpdate()->find($id) : new CrmSite;
            if ($id > 0 && ! $site) {
                $this->fail('Site introuvable', 404);
            }

            $morningStart = $this->normalizeTime($data, 'morningStart', 'morning_start', $site?->morning_start ?: '07:30');
            $morningEnd = $this->normalizeTime($data, 'morningEnd', 'morning_end', $site?->morning_end ?: '12:00');
            $afternoonStart = $this->normalizeTime($data, 'afternoonStart', 'afternoon_start', $site?->afternoon_start ?: '13:30');
            $afternoonEnd = $this->normalizeTime($data, 'afternoonEnd', 'afternoon_end', $site?->afternoon_end ?: '17:30');

            if (
                $this->minutes($morningStart) >= $this->minutes($morningEnd)
                || $this->minutes($afternoonStart) >= $this->minutes($afternoonEnd)
                || $this->minutes($morningEnd) > $this->minutes($afternoonStart)
            ) {
                $this->fail('Plages horaires incoherentes', 400);
            }

            $site->fill([
                'name' => $name,
                'active' => $this->boolean($data['active'] ?? null, true),
                'morning_start' => $morningStart,
                'morning_end' => $morningEnd,
                'afternoon_start' => $afternoonStart,
                'afternoon_end' => $afternoonEnd,
            ])->save();

            $this->log($actor, $id > 0 ? 'modification site' : 'creation site', $name);

            return ['ok' => true, 'id' => $site->id];
        });
    }

    public function deleteSite(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['platform.manage_sites']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = (int) ($data['id'] ?? 0);
            if ($id <= 0) {
                $this->fail('Site invalide', 400);
            }

            $site = CrmSite::query()->with(['vehicles', 'reservations', 'equipmentItems', 'equipmentRentals'])->find($id);
            if (! $site) {
                $this->fail('Site introuvable', 404);
            }

            $used = $site->vehicles->isNotEmpty()
                || $site->reservations->isNotEmpty()
                || $site->equipmentItems->isNotEmpty()
                || $site->equipmentRentals->isNotEmpty();
            $name = $site->name;

            if ($used) {
                $site->delete();
            } else {
                $site->forceDelete();
            }

            $this->log($actor, $used ? 'archivage site' : 'suppression site', $name);

            return ['ok' => true, 'id' => $id, 'archived' => $used];
        });
    }

    public function saveModule(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['platform.manage_modules']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $name = trim((string) ($data['name'] ?? ''));
            $slugSource = trim((string) ($data['slug'] ?? '')) ?: $name;
            $routePath = trim((string) ($data['routePath'] ?? $data['route_path'] ?? ''));
            $menuBadge = trim((string) ($data['menuBadge'] ?? $data['menu_badge'] ?? ''));

            if ($name === '') {
                $this->fail('Nom du module obligatoire', 400);
            }
            if (mb_strlen($menuBadge) > 40) {
                $this->fail('Badge menu trop long', 400);
            }

            $module = $id > 0 ? CrmModule::query()->lockForUpdate()->find($id) : new CrmModule;
            if ($id > 0 && ! $module) {
                $this->fail('Module introuvable', 404);
            }

            $slug = CrmModule::uniqueSlug($slugSource, $id ?: null);
            if ($routePath === '') {
                $routePath = '/'.$slug;
            }

            $module->fill([
                'name' => $name,
                'slug' => $slug,
                'description' => trim((string) ($data['description'] ?? '')),
                'route_path' => $routePath,
                'menu_badge' => $menuBadge !== '' ? $menuBadge : null,
                'show_menu_badge' => $this->boolean($data['showMenuBadge'] ?? $data['show_menu_badge'] ?? null, false),
                'active' => $this->boolean($data['active'] ?? null, true),
                'sort_order' => (int) ($data['sortOrder'] ?? $data['sort_order'] ?? 100),
            ])->save();

            $this->log($actor, $id > 0 ? 'modification module' : 'creation module', $name);

            return ['ok' => true, 'id' => $module->id];
        });
    }

    public function saveUser(CrmUser $actor, array $data): array
    {
        $this->requireAny($actor, ['platform.manage_users', 'platform.manage_roles']);

        return DB::transaction(function () use ($actor, $data): array {
            $id = max(0, (int) ($data['id'] ?? 0));
            $name = trim((string) ($data['name'] ?? ''));
            $role = trim((string) ($data['role'] ?? 'user'));

            if ($name === '') {
                $this->fail('Nom utilisateur obligatoire', 400);
            }

            if (! in_array($role, ['admin', 'responsable', 'user', 'blocked'], true)) {
                $role = 'user';
            }

            $primarySiteId = max(0, (int) ($data['primarySiteId'] ?? $data['primary_site_id'] ?? $data['siteId'] ?? $data['site_id'] ?? 0));
            $siteIds = $this->validIds($primarySiteId > 0 ? [$primarySiteId] : ($data['siteIds'] ?? []), CrmSite::class);
            $moduleIds = $this->validIds($data['moduleIds'] ?? [], CrmModule::class);
            $permissionIds = $this->validIds($data['permissionIds'] ?? [], CrmPermission::class);
            $accessRules = $this->accessRulesFromPayload($data['accessRules'] ?? []);

            if ($role === 'blocked') {
                $moduleIds = [];
                $permissionIds = [];
                $accessRules = [];
            }

            if ($siteIds === []) {
                $firstSiteId = CrmSite::query()
                    ->where('active', true)
                    ->orderBy('id')
                    ->value('id');

                $siteIds = $firstSiteId ? [(int) $firstSiteId] : [];
            }

            $user = $id > 0 ? CrmUser::query()->lockForUpdate()->find($id) : new CrmUser;
            if ($id > 0 && ! $user) {
                $this->fail('Utilisateur introuvable', 404);
            }

            $user->fill([
                'name' => $name,
                'role' => $role,
                'active' => $this->boolean($data['active'] ?? null, true),
                'photo_url' => trim((string) $user->photo_url) ?: self::DEFAULT_PROFILE_PHOTO,
            ])->save();

            $this->syncSites($user, $siteIds);
            $user->modules()->sync($moduleIds);
            $user->permissions()->sync($permissionIds);
            $this->syncAccessRules($user, $accessRules);

            $this->log($actor, $id > 0 ? 'modification utilisateur' : 'creation utilisateur', $name);

            return ['ok' => true, 'id' => $user->id];
        });
    }

    private function ensureDefaultSites(): array
    {
        $existing = CrmSite::query()
            ->orderBy('id')
            ->get(['id', 'name']);

        if ($existing->isNotEmpty()) {
            return $existing->pluck('id', 'name')->map(fn ($id): int => (int) $id)->all();
        }

        $siteIds = [];
        foreach (['Palissy', 'Bordeaux', 'Pessac', 'Glotin', 'Pastel'] as $name) {
            $site = CrmSite::query()->create([
                'name' => $name,
                'active' => true,
                'morning_start' => '07:30:00',
                'morning_end' => '12:00:00',
                'afternoon_start' => '13:30:00',
                'afternoon_end' => '17:30:00',
            ]);
            $siteIds[$name] = $site->id;
        }

        return $siteIds;
    }

    private function ensureDefaultUsers(array $siteIds): void
    {
        if ($siteIds === []) {
            return;
        }

        $defaultSiteId = (int) ($siteIds['Palissy'] ?? reset($siteIds));
        $profiles = collect($this->roleProfiles())->keyBy('key');
        $users = [
            ['J-Philippe', 'admin', array_values($siteIds)],
            ['Christophe L', 'user', [$defaultSiteId]],
            ['Remi G', 'user', [$defaultSiteId]],
            ['Samy I', 'user', [$defaultSiteId]],
            ['Philippe P', 'responsable', [$defaultSiteId]],
            ['Jeremy L', 'blocked', [$defaultSiteId]],
        ];

        foreach ($users as [$name, $role, $sites]) {
            $user = CrmUser::query()->firstOrCreate(
                ['name' => $name],
                ['role' => $role, 'active' => true, 'photo_url' => self::DEFAULT_PROFILE_PHOTO],
            );

            if (! trim((string) $user->photo_url)) {
                $user->forceFill(['photo_url' => self::DEFAULT_PROFILE_PHOTO])->save();
            }

            $profile = $profiles->get($role, $profiles->get('user'));

            if ($role === 'blocked') {
                $user->modules()->sync([]);
                $user->permissions()->sync([]);

                continue;
            }

            if ($user->sites()->count() === 0) {
                $this->syncSites($user, array_map('intval', $sites));
            }

            $moduleIds = CrmModule::query()
                ->whereIn('slug', $profile['moduleSlugs'])
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $permissionIds = CrmPermission::query()
                ->whereIn('name', $profile['permissions'])
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($user->modules()->count() === 0) {
                $user->modules()->sync($moduleIds);
            } elseif ($role === 'admin') {
                $user->modules()->syncWithoutDetaching($moduleIds);
            }

            if ($user->permissions()->count() === 0) {
                $user->permissions()->sync($permissionIds);
            } elseif ($role === 'admin') {
                $user->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }

    private function ensureDefaultProfilePhotos(): void
    {
        CrmUser::query()
            ->whereNull('photo_url')
            ->orWhere('photo_url', '')
            ->update(['photo_url' => self::DEFAULT_PROFILE_PHOTO]);
    }

    private function ensureMenuItem(array $item): void
    {
        [$itemKey, $groupKey, $label, $iconKey, $sortOrder] = $item;

        $menuItem = CrmMenuItem::query()->firstOrNew(['item_key' => $itemKey]);
        $menuItem->fill([
            'group_key' => $menuItem->exists ? $menuItem->group_key : $groupKey,
            'icon_key' => $menuItem->icon_key ?: $iconKey,
            'label' => $menuItem->exists ? $menuItem->label : $label,
            'active' => $menuItem->exists ? $menuItem->active : true,
            'sort_order' => $menuItem->exists ? $menuItem->sort_order : $sortOrder,
        ]);
        $menuItem->saveQuietly();
    }

    private function deleteObsoleteMenuEntries(): void
    {
        $prefixes = ['dashboard:', 'app:', 'feature:', 'auth:', 'page:', 'form:', 'table:', 'chart:'];

        CrmMenuItem::query()
            ->where(function ($query) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('item_key', 'like', $prefix.'%');
                }
            })
            ->delete();

        CrmMenuGroup::query()
            ->whereIn('menu_key', ['dashboards', 'authentication', 'forms', 'tables', 'charts'])
            ->delete();
    }

    private function defaultModuleMenuGroup(string $slug): string
    {
        if ($slug === 'dashboard') {
            return 'home';
        }

        if (in_array($slug, ['controle-caisse', 'remise-cheques', 'addvance'], true)) {
            return 'accounting';
        }

        return in_array($slug, ['reservations', 'locations-materiel', 'tapis-romus', 'documents-promo', 'documents-fiches-techniques', 'documents-procedures'], true)
            ? 'apps'
            : 'internal';
    }

    private function syncPagesMenuGroupVisibility(): void
    {
        CrmMenuGroup::query()
            ->where('menu_key', 'pages')
            ->update([
                'active' => CrmMenuItem::query()
                    ->where('group_key', 'pages')
                    ->where('active', true)
                    ->exists(),
            ]);
    }

    private function permissionSeed(): array
    {
        return [
            ['reservations.view', 'Voir les reservations', 'Reservations', 10],
            ['reservations.create', 'Creer une reservation', 'Reservations', 20],
            ['reservations.update_own', 'Modifier ses reservations', 'Reservations', 30],
            ['reservations.update_any', 'Modifier toutes les reservations', 'Reservations', 40],
            ['reservations.delete_own', 'Supprimer ses reservations', 'Reservations', 50],
            ['reservations.delete_any', 'Supprimer toutes les reservations', 'Reservations', 60],
            ['reservations.manage_vehicles', 'Gerer les vehicules du site', 'Reservations', 70],
            ['equipment_rentals.view', 'Voir les locations materiel', 'Location materiel', 80],
            ['equipment_rentals.create', 'Creer une location materiel', 'Location materiel', 90],
            ['equipment_rentals.update_own', 'Modifier ses locations materiel', 'Location materiel', 100],
            ['equipment_rentals.update_any', 'Modifier toutes les locations materiel', 'Location materiel', 110],
            ['equipment_rentals.delete_own', 'Supprimer ses locations materiel', 'Location materiel', 120],
            ['equipment_rentals.delete_any', 'Supprimer toutes les locations materiel', 'Location materiel', 130],
            ['equipment_rentals.manage_items', 'Gerer le materiel de location', 'Location materiel', 140],
            ['conges.view', 'Voir les conges', 'Conges', 145],
            ['conges.manage', 'Gerer les conges', 'Conges', 146],
            ['controle_caisse.view', 'Voir le controle caisse', 'Controle caisse', 147],
            ['controle_caisse.manage', 'Gerer le controle caisse', 'Controle caisse', 148],
            ['check_remittances.view', 'Voir les remises de chèques', 'Remise de chèques', 149],
            ['check_remittances.manage', 'Gérer les remises de chèques', 'Remise de chèques', 150],
            ['platform.manage_sites', 'Gerer les sites', 'Administration', 160],
            ['platform.manage_users', 'Gerer les utilisateurs', 'Administration', 170],
            ['platform.manage_roles', 'Gerer les roles', 'Administration', 180],
            ['platform.manage_modules', 'Gerer les modules', 'Administration', 190],
            ['pages.view', 'Voir les pages CRM', 'Pages CRM', 200],
            ['pages.manage', 'Gerer les pages CRM', 'Pages CRM', 210],
        ];
    }

    private function roleProfiles(): array
    {
        return [
            [
                'key' => 'user',
                'label' => 'Employe',
                'description' => 'Reservation et location sur les sites rattaches, suppression de ses propres demandes.',
                'permissions' => ['reservations.view', 'reservations.create', 'reservations.update_own', 'reservations.delete_own', 'equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.delete_own', 'conges.view', 'controle_caisse.view'],
                'moduleSlugs' => ['dashboard', 'reservations', 'locations-materiel', 'conges', 'controle-caisse', 'addvance'],
            ],
            [
                'key' => 'responsable',
                'label' => 'Responsable site',
                'description' => 'Gestion des reservations, vehicules et locations materiel des sites rattaches.',
                'permissions' => ['reservations.view', 'reservations.create', 'reservations.update_own', 'reservations.update_any', 'reservations.delete_own', 'reservations.delete_any', 'reservations.manage_vehicles', 'equipment_rentals.view', 'equipment_rentals.create', 'equipment_rentals.update_own', 'equipment_rentals.update_any', 'equipment_rentals.delete_own', 'equipment_rentals.delete_any', 'equipment_rentals.manage_items', 'conges.view', 'conges.manage', 'controle_caisse.view', 'controle_caisse.manage', 'check_remittances.view', 'check_remittances.manage'],
                'moduleSlugs' => ['dashboard', 'reservations', 'locations-materiel', 'conges', 'controle-caisse', 'remise-cheques', 'addvance'],
            ],
            [
                'key' => 'admin',
                'label' => 'Administrateur',
                'description' => 'Acces global aux sites, modules, utilisateurs, roles et permissions.',
                'permissions' => array_map(fn (array $permission): string => $permission[0], $this->permissionSeed()),
                'moduleSlugs' => ['dashboard', 'reservations', 'locations-materiel', 'pages-crm', 'administration', 'conges', 'controle-caisse', 'remise-cheques', 'addvance', 'tapis-romus'],
            ],
            [
                'key' => 'blocked',
                'label' => 'Sans acces',
                'description' => 'Aucun module ni action disponible.',
                'permissions' => [],
                'moduleSlugs' => [],
            ],
        ];
    }

    private function moduleSeed(): array
    {
        return [
            ['Tableau de bord', 'dashboard', 'Synthese et acces rapides du CRM', '/dashboard/crm', 0, true],
            ['Réservations véhicules', 'reservations', 'Planning et réservations des véhicules', '/reservations', 10, true],
            ['Location matériel', 'locations-materiel', 'Planning et locations du matériel interne', '/locations-materiel', 15, true],
            ['Pages CRM', 'pages-crm', 'Pages internes modifiables depuis le CRM', '/pages-crm', 18, true],
            ['Administration', 'administration', 'Gestion des sites, modules, utilisateurs et rôles', '/administration', 20, true],
            ['Congés', 'conges', 'Planning et gestion des congés', '/conges', 24, true],
            ['Contrôle caisse', 'controle-caisse', 'Contrôle journalier de caisse, reports, écarts et justificatifs', '/controle-caisse', 25, true],
            ['Remise de chèques', 'remise-cheques', 'Remises de chèques, photos, contrôle des montants et impression PDF', '/remise-cheques', 27, true],
            ['Addvance', 'addvance', 'Accès externe Addvance Solutions', 'https://martinsols.addvancesolutions.fr', 28, true],
            ['Planning', 'planning', 'Planning interne par site', '/planning', 30, false],
            ['Documents internes', 'documents', 'Procédures et documents partagés', '/documents', 40, false],
            ['Demandes internes', 'demandes', 'Demandes et validations internes', '/demandes', 50, false],
            ['Tapis ROMUS', 'tapis-romus', 'Bon de commande et mesures tapis ROMUS', '/tapis-romus', 60, true],
        ];
    }

    private function menuGroupSeed(): array
    {
        return [
            ['home', 'Accueil', 0, true],
            ['apps', 'Applications CRM', 10, true],
            ['accounting', 'Comptabilité', 18, true],
            ['internal', 'Administration', 20, true],
            ['pages', 'Pages internes', 30, false],
        ];
    }

    private function staticMenuItemSeed(): array
    {
        return [];
    }

    private function actorRow(CrmUser $actor): array
    {
        $actor->loadMissing(['permissions:id,name,sort_order', 'modules:id,slug,active', 'sites:id']);

        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'firstName' => $actor->first_name,
            'lastName' => $actor->last_name,
            'email' => $actor->email,
            'bio' => $actor->bio,
            'photoUrl' => trim((string) $actor->photo_url) ?: self::DEFAULT_PROFILE_PHOTO,
            'role' => $actor->role,
            'active' => (bool) $actor->active,
            'permissions' => $this->permissionNames($actor),
        ];
    }

    private function siteRow(CrmSite $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'slug' => $site->slug,
            'active' => (bool) $site->active,
            'hours' => [
                'morningStart' => $this->time5($site->morning_start, '07:30'),
                'morningEnd' => $this->time5($site->morning_end, '12:00'),
                'afternoonStart' => $this->time5($site->afternoon_start, '13:30'),
                'afternoonEnd' => $this->time5($site->afternoon_end, '17:30'),
            ],
        ];
    }

    private function moduleRow(CrmModule $module): array
    {
        return [
            'id' => $module->id,
            'name' => $module->name,
            'slug' => $module->slug,
            'description' => $module->description ?? '',
            'routePath' => $module->route_path ?? '',
            'menuBadge' => $module->menu_badge ?? '',
            'showMenuBadge' => (bool) $module->show_menu_badge,
            'active' => (bool) $module->active,
            'sortOrder' => (int) $module->sort_order,
        ];
    }

    private function menuGroupRow(CrmMenuGroup $group): array
    {
        return [
            'id' => $group->id,
            'menuKey' => $group->menu_key,
            'title' => $group->title,
            'active' => (bool) $group->active,
            'sortOrder' => (int) $group->sort_order,
        ];
    }

    private function menuItemRow(CrmMenuItem $item): array
    {
        return [
            'id' => $item->id,
            'itemKey' => $item->item_key,
            'groupKey' => $item->group_key,
            'iconKey' => $item->icon_key ?? '',
            'label' => $item->label,
            'active' => (bool) $item->active,
            'sortOrder' => (int) $item->sort_order,
        ];
    }

    private function permissionRow(CrmPermission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'label' => $permission->label,
            'group' => $permission->group_label,
            'sortOrder' => (int) $permission->sort_order,
        ];
    }

    private function pageRow(CrmPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'excerpt' => $page->excerpt ?? '',
            'content' => $page->content ?? '',
            'iconKey' => $page->icon_key ?: 'article',
            'active' => (bool) $page->active,
            'showInMenu' => (bool) $page->show_in_menu,
            'sortOrder' => (int) $page->sort_order,
            'routePath' => $page->route_path,
        ];
    }

    private function userRow(CrmUser $user): array
    {
        $user->loadMissing(['sites:id', 'modules:id', 'permissions:id,name,sort_order', 'siteModulePermissions:id,user_id,site_id,module_id,permission_id']);
        $siteIds = $user->sites->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'active' => (bool) $user->active,
            'primarySiteId' => $this->primarySiteId($user, $siteIds),
            'siteIds' => $siteIds,
            'moduleIds' => $user->modules->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all(),
            'permissionIds' => $user->permissions->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all(),
            'permissions' => $this->permissionNames($user),
            'effectiveSiteIds' => $this->access->siteIds($user),
            'effectiveModuleIds' => $this->access->moduleIds($user),
            'effectivePermissions' => $this->access->permissionNames($user),
            'accessRules' => $this->access->accessRules($user),
        ];
    }

    private function profilePayload(CrmUser $user): array
    {
        $user->loadMissing('permissions:id,name,sort_order');

        $firstName = trim((string) $user->first_name);
        $lastName = trim((string) $user->last_name);
        $rawName = trim((string) $user->name);

        if ($firstName === '' && $rawName !== '') {
            if ($rawName === 'J-Philippe') {
                $firstName = 'Jean-Philippe';
            } else {
                $parts = preg_split('/\s+/', $rawName, 2);
                $firstName = trim((string) ($parts[0] ?? ''));
                if ($lastName === '') {
                    $lastName = trim((string) ($parts[1] ?? ''));
                }
            }
        }

        $displayName = trim($firstName.' '.$lastName);
        if ($displayName === '') {
            $displayName = $rawName !== '' ? $rawName : 'Jean-Philippe';
        }

        return [
            'id' => $user->id,
            'name' => $rawName,
            'displayName' => $displayName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => trim((string) $user->email) ?: 'contact@jp2creation.fr',
            'bio' => trim((string) $user->bio) ?: ($user->role === 'admin' ? 'Administrateur CRM Martin Sols' : ''),
            'photoUrl' => trim((string) $user->photo_url) ?: self::DEFAULT_PROFILE_PHOTO,
            'role' => $user->role,
            'canEditIdentity' => $user->role === 'admin' || $this->hasPermission($user, 'platform.manage_users'),
        ];
    }

    private function hasPermission(CrmUser $actor, string $permission): bool
    {
        return in_array($permission, $this->permissionNames($actor), true);
    }

    private function requireAny(CrmUser $actor, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($actor, $permission)) {
                return;
            }
        }

        $this->fail('Droit administration insuffisant', 403);
    }

    private function permissionNames(CrmUser $user): array
    {
        $user->loadMissing('permissions:id,name,sort_order');

        return $user->permissions
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function primarySiteId(CrmUser $user, array $siteIds): ?int
    {
        $defaultSite = $user->sites->first(
            fn (CrmSite $site): bool => (bool) ($site->pivot?->is_default ?? false),
        );

        return $defaultSite ? (int) $defaultSite->id : ($siteIds[0] ?? null);
    }

    private function syncSites(CrmUser $user, array $siteIds): void
    {
        $sync = [];
        foreach (array_values($siteIds) as $index => $siteId) {
            $sync[(int) $siteId] = ['is_default' => $index === 0];
        }

        $user->sites()->sync($sync);
    }

    /**
     * @param  class-string  $model
     */
    private function validIds(mixed $ids, string $model): array
    {
        $ids = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
        if ($ids === []) {
            return [];
        }

        return $model::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function saveDataImage(string $dataUrl, string $folder): string
    {
        if ($dataUrl === '') {
            return '';
        }

        if (! preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $dataUrl, $matches)) {
            $this->fail('Photo invalide', 400);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], true);

        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            $this->fail('Photo invalide ou trop lourde', 400);
        }

        $safeFolder = trim(preg_replace('/[^a-z0-9_-]+/i', '', $folder) ?: 'profiles', '/');
        $uploadDir = public_path('assets/uploads/'.$safeFolder);

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            $this->fail('Dossier upload indisponible', 500);
        }

        $filename = uniqid('photo_', true).'.'.$extension;
        if (file_put_contents($uploadDir.'/'.$filename, $binary) === false) {
            $this->fail('Enregistrement photo impossible', 500);
        }

        return '/assets/uploads/'.$safeFolder.'/'.$filename;
    }

    private function normalizeTime(array $data, string $camelKey, string $snakeKey, string $default): string
    {
        $value = trim((string) ($data[$camelKey] ?? $data[$snakeKey] ?? ($data['hours'][$camelKey] ?? $default)));
        $value = substr($value, 0, 5);

        if (! preg_match('/^([0-2][0-9]):([0-5][0-9])$/', $value, $matches)) {
            $this->fail('Horaire invalide', 400);
        }

        $hour = (int) $matches[1];
        if ($hour > 23) {
            $this->fail('Horaire invalide', 400);
        }

        return sprintf('%02d:%02d:00', $hour, (int) $matches[2]);
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hour * 60) + $minute;
    }

    private function time5(mixed $value, string $default): string
    {
        $value = trim((string) $value);

        return preg_match('/^([0-2][0-9]:[0-5][0-9])/', $value, $matches) ? $matches[1] : $default;
    }

    private function iconKey(string $value): string
    {
        $value = trim($value);

        return preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $value) ? $value : 'article';
    }

    private function boolean(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<int, array{site_id: int, module_id: int, permission_id: int}>
     */
    private function accessRulesFromPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $validSiteIds = CrmSite::query()->pluck('id')->mapWithKeys(fn ($id): array => [(int) $id => true])->all();
        $validModuleIds = CrmModule::query()->pluck('id')->mapWithKeys(fn ($id): array => [(int) $id => true])->all();
        $validPermissionIds = CrmPermission::query()->pluck('id')->mapWithKeys(fn ($id): array => [(int) $id => true])->all();

        $rules = [];

        foreach ($payload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $siteId = (int) ($entry['siteId'] ?? $entry['site_id'] ?? 0);
            $moduleId = (int) ($entry['moduleId'] ?? $entry['module_id'] ?? 0);

            if (! isset($validSiteIds[$siteId], $validModuleIds[$moduleId])) {
                continue;
            }

            $permissionIds = [];
            if (isset($entry['permissionIds']) && is_array($entry['permissionIds'])) {
                $permissionIds = $entry['permissionIds'];
            } elseif (isset($entry['permission_ids']) && is_array($entry['permission_ids'])) {
                $permissionIds = $entry['permission_ids'];
            } else {
                $permissionIds = [$entry['permissionId'] ?? $entry['permission_id'] ?? 0];
            }

            foreach ($permissionIds as $permissionId) {
                $permissionId = (int) $permissionId;
                if (! isset($validPermissionIds[$permissionId])) {
                    continue;
                }

                $rules["{$siteId}:{$moduleId}:{$permissionId}"] = [
                    'site_id' => $siteId,
                    'module_id' => $moduleId,
                    'permission_id' => $permissionId,
                ];
            }
        }

        return array_values($rules);
    }

    /**
     * @param  array<int, array{site_id: int, module_id: int, permission_id: int}>  $rules
     */
    private function syncAccessRules(CrmUser $user, array $rules): void
    {
        CrmUserSiteModulePermission::query()
            ->where('user_id', $user->id)
            ->delete();

        if ($rules === []) {
            return;
        }

        $now = now();
        CrmUserSiteModulePermission::query()->insert(array_map(
            fn (array $rule): array => [
                'user_id' => (int) $user->id,
                'site_id' => $rule['site_id'],
                'module_id' => $rule['module_id'],
                'permission_id' => $rule['permission_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $rules,
        ));
    }

    private function log(CrmUser $actor, string $action, string $details = ''): void
    {
        $this->activity->log($actor, $action, $details);
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
