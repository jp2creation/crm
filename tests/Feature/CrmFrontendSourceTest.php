<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmFrontendSourceTest extends TestCase
{
    public function test_crm_shell_is_loaded_from_versioned_vite_source(): void
    {
        $blade = (string) file_get_contents(resource_path('views/crm.blade.php'));
        $viteConfig = (string) file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("@vite(config('crm_frontend.vite_entries'))", $blade);
        $this->assertStringContainsString('id="crm-shell-config"', $blade);
        $this->assertStringContainsString('<div id="root"></div>', $blade);
        $this->assertStringContainsString('meta name="csrf-token"', $blade);
        $this->assertStringNotContainsString('legacyAdminexScript', $blade);
        $this->assertStringNotContainsString('resources/frontend/adminex/src/main.tsx', $viteConfig);
        $this->assertStringNotContainsString('resources/frontend/adminex/src', $viteConfig);
        $this->assertStringNotContainsString('adminex-ui', $viteConfig);
        $this->assertStringNotContainsString('data-crm-api-csrf', $blade);
        $this->assertStringNotContainsString('data-crm-frontend-assets', $blade);
        $this->assertStringNotContainsString('data-crm-logout-bridge', $blade);
        $this->assertStringNotContainsString('logoutToCrmLogin', $blade);
        $this->assertStringNotContainsString('history[method] =', $blade);
        $this->assertStringNotContainsString('partials.brand-morph-loader', $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-core/crm-dashboard.js')", $blade);
        $this->assertStringNotContainsString("CrmAsset::url('modules/crm-leaves/crm-conges.js')", $blade);
        $this->assertStringNotContainsString('crm-mobile-app-settings-trigger', $blade);
        $this->assertStringNotContainsString('data-crm-mobile-app-settings', $blade);
        $this->assertStringNotContainsString('data-crm-mobile-fallback-nav', $blade);
        $this->assertStringNotContainsString('window.CRM_NAV_FALLBACK =', $blade);

        $this->assertFileExists(resource_path('frontend/crm/shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/api/client.ts'));
        $this->assertFileExists(resource_path('frontend/crm/config.ts'));
        $this->assertFileExists(resource_path('frontend/crm/legacy/logout-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/layout/native-shell.ts'));
        $this->assertFileExists(resource_path('frontend/crm/loader.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/embed-bridge.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $this->assertFileExists(resource_path('frontend/crm/mobile/settings.ts'));
        $this->assertFileExists(resource_path('frontend/crm/modules/hosts.ts'));
        $this->assertFileExists(resource_path('frontend/crm/router/menu.ts'));
        $this->assertFileExists(resource_path('frontend/crm/styles/shell.css'));

        $shell = (string) file_get_contents(resource_path('frontend/crm/shell.ts'));
        $nativeShell = (string) file_get_contents(resource_path('frontend/crm/layout/native-shell.ts'));
        $legacyAdminex = (string) file_get_contents(resource_path('frontend/crm/legacy/adminex.ts'));
        $hosts = (string) file_get_contents(resource_path('frontend/crm/modules/hosts.ts'));
        $modules = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));
        $legacyReactBridge = (string) file_get_contents(resource_path('frontend/crm/legacy/react-components.ts'));
        $legacyAdminexBundle = (string) file_get_contents(resource_path('frontend/static/assets/index-CqSzWeas.js'));

        $this->assertStringContainsString('installCrmModuleHostGuard', $shell);
        $this->assertStringContainsString('installNativeCrmShell', $shell);
        $this->assertStringContainsString('installCurrentCrmModuleRouteLoader', $shell);
        $this->assertStringContainsString('loadCurrentCrmModuleOverlay', $shell);
        $this->assertStringContainsString('preloadRemainingCrmModuleOverlays', $shell);
        $this->assertStringNotContainsString('loadLegacyAdminex()', $shell);
        $this->assertStringContainsString('loadCurrentCrmModuleOverlay()', $shell);
        $this->assertStringContainsString('data-crm-native-shell', $nativeShell);
        $this->assertStringContainsString('layout-sidebar crm-native-sidebar', $nativeShell);
        $this->assertStringContainsString('layout-header crm-native-header', $nativeShell);
        $this->assertStringContainsString("fetch('/api/administration?action=profile'", $nativeShell);
        $this->assertStringContainsString('profile.navigation', $nativeShell);
        $this->assertStringContainsString('window.CRM_NAV_FALLBACK = profile.navigation', $nativeShell);
        $this->assertStringContainsString('data-crm-native-submenu-toggle', $nativeShell);
        $this->assertStringContainsString('iconForKey', $nativeShell);
        $this->assertStringContainsString("new Set(['home', 'apps', 'accounting', 'internal'])", $nativeShell);
        $this->assertStringContainsString("commercial: 'dashboard'", $nativeShell);
        $this->assertStringContainsString('isLegacyAdminexRoute()', $legacyAdminex);
        $this->assertStringContainsString('return false;', $legacyAdminex);
        $this->assertStringNotContainsString('appendModuleScript', $legacyAdminex);
        $this->assertStringNotContainsString('/reservations', $legacyAdminex);
        $this->assertStringNotContainsString('/locations-materiel', $legacyAdminex);
        $this->assertStringContainsString("id: 'crm-sales-tours-module'", $hosts);
        $this->assertStringContainsString("paths: ['/rapport-visite', '/tournees-representants']", $hosts);
        $this->assertStringContainsString("id: 'crm-reservations-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-equipment-rentals-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-administration-module'", $hosts);
        $this->assertStringContainsString("id: 'crm-tapis-romus-module'", $hosts);
        $this->assertStringNotContainsString('adminexOnly', $hosts);
        $this->assertStringContainsString("prefix: '/documents/'", $hosts);
        $this->assertStringContainsString('refreshStaleRouteOnce', $hosts);
        $this->assertStringContainsString('refreshMissingHostOnce', $hosts);
        $this->assertStringContainsString('scheduleMissingHostRefresh', $hosts);
        $this->assertStringContainsString('clearCrmRuntimeCaches', $hosts);
        $this->assertStringContainsString("const refreshStoragePrefix = 'crm:route-host-hard-refresh:v2:'", $hosts);
        $this->assertStringContainsString("const missingHostRefreshStoragePrefix = 'crm:route-host-missing-refresh:v1:'", $hosts);
        $this->assertStringContainsString("url.searchParams.set('_crm_refresh'", $hosts);
        $this->assertStringContainsString('window.location.replace(refreshedRouteUrl())', $hosts);
        $this->assertStringContainsString('installRootObserver', $hosts);
        $this->assertStringContainsString('new MutationObserver', $hosts);
        $this->assertStringNotContainsString('replaceChildren(host)', $hosts);
        $this->assertStringNotContainsString('document.createElement', $hosts);
        $this->assertStringContainsString('moduleKeysForCurrentPath', $modules);
        $this->assertStringContainsString('loadCurrentRouteModules', $modules);
        $this->assertStringContainsString("window.addEventListener('crm:navigation', loadCurrentRouteModules)", $modules);
        $this->assertStringContainsString('requestIdleCallback', $modules);
        $this->assertStringContainsString('transitionalReactModules', $modules);
        $this->assertStringContainsString('!transitionalReactModules.has(key)', $modules);
        $this->assertStringContainsString("cashControl: () => import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js')", $modules);
        $this->assertStringContainsString('mountLegacyReactComponent', $modules);
        $this->assertStringContainsString("hostId: 'crm-reservations-module'", $modules);
        $this->assertStringContainsString("hostId: 'crm-equipment-rentals-module'", $modules);
        $this->assertStringContainsString("hostId: 'crm-administration-module'", $modules);
        $this->assertStringContainsString("hostId: 'crm-tapis-romus-module'", $modules);
        $this->assertStringContainsString('__crmReact', $legacyReactBridge);
        $this->assertStringContainsString('__crmReactDomClient', $legacyReactBridge);
        $this->assertStringContainsString('j as __crmReact', $legacyAdminexBundle);
        $this->assertStringContainsString('oi as __crmReactDomClient', $legacyAdminexBundle);
        $this->assertStringContainsString('tb=null;Xh();export', $legacyAdminexBundle);
        $this->assertStringNotContainsString('tb=ji([{path:`/`,', $legacyAdminexBundle);
        $this->assertStringNotContainsString('document.getElementById(`root`)).render', $legacyAdminexBundle);

        $mobileFallback = (string) file_get_contents(resource_path('frontend/crm/mobile/fallback-nav.ts'));
        $shellCss = (string) file_get_contents(resource_path('frontend/crm/styles/shell.css'));

        $this->assertStringContainsString("window.matchMedia('(max-width: 767.98px)')", $mobileFallback);
        $this->assertStringContainsString('shouldUseFallbackNavigation', $mobileFallback);
        $this->assertStringContainsString("document.body.classList.contains('crm-mobile-app')", $mobileFallback);
        $this->assertStringContainsString("document.body.classList.contains('crm-mobile-embed')", $mobileFallback);
        $this->assertStringContainsString('new MutationObserver', $mobileFallback);
        $this->assertStringNotContainsString("if (!document.body.classList.contains('crm-mobile-app'))", $mobileFallback);
        $this->assertStringContainsString('body.crm-mobile-fallback-nav-browser', $shellCss);
    }

    public function test_transitional_adminex_runtime_is_available_without_router_boot(): void
    {
        $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        $legacyRuntime = (string) file_get_contents(resource_path('frontend/static/assets/index-CqSzWeas.js'));

        $this->assertFileExists(resource_path('frontend/static/assets/legacy-adminex.css'));
        $this->assertFileExists(resource_path('frontend/static/assets/reservations-CSr_CND1.js'));
        $this->assertFileExists(resource_path('frontend/static/assets/equipment-rentals-Codex2.js'));
        $this->assertFileExists(resource_path('frontend/static/assets/administration-DvgVMvBV.js'));
        $this->assertFileExists(resource_path('frontend/static/assets/tapis-romus-CVLfrJx-.js'));
        $this->assertStringContainsString('j as __crmReact', $legacyRuntime);
        $this->assertStringContainsString('oi as __crmReactDomClient', $legacyRuntime);
        $this->assertStringContainsString('tb=null;Xh();export', $legacyRuntime);
        $this->assertStringNotContainsString('tb=ji([{path:`/`,', $legacyRuntime);
        $this->assertStringNotContainsString('createRoot)(document.getElementById(`root`))', $legacyRuntime);
        $this->assertArrayHasKey('react', $package['dependencies']);
        $this->assertArrayHasKey('react-dom', $package['dependencies']);
        $this->assertArrayHasKey('@vitejs/plugin-react', $package['devDependencies']);
        $this->assertArrayHasKey('typescript', $package['devDependencies']);
        $this->assertArrayHasKey('eslint', $package['devDependencies']);
        $this->assertArrayHasKey('prettier', $package['devDependencies']);
        $this->assertArrayHasKey('vitest', $package['devDependencies']);
    }

    public function test_transitional_static_adminex_chunks_do_not_reference_missing_local_chunks(): void
    {
        $assetsDir = resource_path('frontend/static/assets');
        $missing = [];
        $importPattern = <<<'REGEX'
/import\((["'`])\.\/([^"'`?]+)(?:\?[^"'`]*)?\1\)/
REGEX;
        $fromPattern = <<<'REGEX'
/from(["'`])\.\/([^"'`?]+)(?:\?[^"'`]*)?\1/
REGEX;

        foreach (glob($assetsDir.'/*.js') ?: [] as $assetPath) {
            $contents = (string) file_get_contents($assetPath);
            preg_match_all($importPattern, $contents, $importMatches, PREG_SET_ORDER);
            preg_match_all($fromPattern, $contents, $fromMatches, PREG_SET_ORDER);

            foreach (array_merge($importMatches, $fromMatches) as $match) {
                $dependency = $match[2] ?? null;

                if ($dependency && ! file_exists($assetsDir.'/'.$dependency)) {
                    $missing[] = basename($assetPath).' -> '.$dependency;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_deployment_archive_includes_vite_build_output(): void
    {
        $script = (string) file_get_contents(base_path('scripts/deploy-planethoster.sh'));
        $gitignore = (string) file_get_contents(base_path('.gitignore'));

        $this->assertStringContainsString('tar -rf "$LOCAL_ARCHIVE_TAR" public/build', $script);
        $this->assertStringContainsString('if [ -d public/build ]; then', $script);
        $this->assertStringNotContainsString('if [ "$CRM_DEPLOY_BUILD" != "0" ] && [ -d public/build ]; then', $script);
        $this->assertStringContainsString('Manifest Vite absent: ${RELEASE_DIR}/public/build/manifest.json', $script);
        $this->assertStringContainsString('php artisan crm:publish-static-assets --force --clean', $script);
        $this->assertStringContainsString('php artisan crm:publish-module-assets --force', $script);
        $this->assertStringContainsString('gzip -c "$LOCAL_ARCHIVE_TAR" > "$LOCAL_ARCHIVE"', $script);
        $this->assertStringContainsString('RELEASES_DIR="${CRM_DEPLOY_ROOT}/releases"', $script);
        $this->assertStringContainsString('SHARED_DIR="${CRM_DEPLOY_ROOT}/shared"', $script);
        $this->assertStringContainsString('CURRENT_LINK="${CRM_DEPLOY_ROOT}/current"', $script);
        $this->assertStringContainsString('mv -Tf "$NEXT_LINK" "$CURRENT_LINK"', $script);
        $this->assertStringContainsString('rollback_current', $script);
        $this->assertStringContainsString('curl -fsS --max-time 10 "$health_url"', $script);
        $this->assertStringContainsString('php artisan horizon:terminate || true', $script);
        $this->assertStringContainsString('cleanup_old_releases', $script);
        $this->assertStringContainsString("--exclude='storage/redis'", $script);
        $this->assertStringContainsString("--exclude='storage/framework/cache'", $script);
        $this->assertStringContainsString('/public/assets', $gitignore);
        $this->assertStringContainsString('/public/modules', $gitignore);
    }

    public function test_dom_ready_sensitive_crm_modules_boot_even_when_loaded_late(): void
    {
        foreach ([
            base_path('Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
            base_path('Modules/CrmCore/resources/assets/crm-account-settings.js'),
            base_path('Modules/CrmCore/resources/assets/crm-active-site.js'),
            base_path('Modules/CrmCore/resources/assets/crm-dashboard.js'),
            base_path('Modules/CrmCore/resources/assets/crm-text-fixes.js'),
        ] as $assetPath) {
            $asset = (string) file_get_contents($assetPath);

            $this->assertStringContainsString('document.readyState ===', $asset, $assetPath);
        }
    }
}
