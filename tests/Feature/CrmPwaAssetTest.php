<?php

namespace Tests\Feature;

use App\Support\CrmAsset;
use Tests\TestCase;

class CrmPwaAssetTest extends TestCase
{
    public function test_manifest_is_served_as_an_installable_pwa_manifest(): void
    {
        $response = $this->get('/manifest.json');

        $response->assertOk();
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('Content-Type'));

        $manifest = json_decode((string) file_get_contents(public_path('manifest.json')), true);

        $this->assertSame('Martin Sols CRM', $manifest['name']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertContains('business', $manifest['categories']);
        $this->assertNotEmpty(collect($manifest['icons'])->firstWhere('sizes', '192x192'));
        $this->assertNotEmpty(collect($manifest['icons'])->firstWhere('sizes', '512x512'));
    }

    public function test_service_worker_is_allowed_from_the_root_and_can_skip_waiting(): void
    {
        $response = $this->get('/sw.js');

        $response->assertOk();
        $this->assertSame('/', $response->headers->get('Service-Worker-Allowed'));

        $serviceWorker = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("CACHE_VERSION = 'martin-sols-crm-", $serviceWorker);
        $this->assertStringContainsString("CACHE_VERSION = 'martin-sols-crm-v202607201750'", $serviceWorker);
        $this->assertStringContainsString('cache.add(url).catch(() => null)', $serviceWorker);
        $this->assertStringContainsString("event.data.type === 'SKIP_WAITING'", $serviceWorker);
        $this->assertStringContainsString("event.data.type === 'GET_VERSION'", $serviceWorker);
        $this->assertStringContainsString("notifyClients('CRM_SW_ACTIVATED')", $serviceWorker);
        $this->assertStringContainsString("notifyClients('CRM_SW_VERSION')", $serviceWorker);
        $this->assertStringContainsString("fetch(request, { cache: 'no-store' })", $serviceWorker);
        $this->assertStringContainsString("self.addEventListener('push'", $serviceWorker);
        $this->assertStringContainsString("self.registration.showNotification(payload.title || 'Martin Sols CRM'", $serviceWorker);
        $this->assertStringContainsString("self.addEventListener('notificationclick'", $serviceWorker);
        $this->assertStringContainsString('CRM_NOTIFICATION_CLICKED', $serviceWorker);
    }

    public function test_static_pwa_files_have_apache_headers_for_shared_hosting(): void
    {
        $htaccess = (string) file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('<Files "manifest.json">', $htaccess);
        $this->assertStringContainsString('ForceType application/manifest+json', $htaccess);
        $this->assertStringContainsString('<Files "sw.js">', $htaccess);
        $this->assertStringContainsString('Service-Worker-Allowed "/"', $htaccess);
        $this->assertStringContainsString('no-cache, no-store, must-revalidate', $htaccess);
    }

    public function test_lazy_chunks_import_the_current_entry_asset_version(): void
    {
        $currentImport = 'index-CqSzWeas.js?v=202607201900';
        $legacyVersions = [
            '2026071404',
            '2026071602',
            '2026071603',
            '2026071702',
            '2026071704',
            '2026071705',
            '2026071706',
            '2026071707',
            '2026071708',
            '2026071709',
            '2026071710',
            '2026071711',
            '2026071712',
            '2026071713',
            '202607191940',
            '202607192230',
            '202607200930',
            '1783861909',
        ];
        $chunksWithCurrentImport = 0;

        foreach (glob(public_path('assets/*.js')) ?: [] as $assetPath) {
            $asset = (string) file_get_contents($assetPath);

            foreach ($legacyVersions as $legacyVersion) {
                $this->assertStringNotContainsString('?v='.$legacyVersion, $asset, basename($assetPath));
            }

            if (str_contains($asset, $currentImport)) {
                $chunksWithCurrentImport++;
            }
        }

        $this->assertGreaterThanOrEqual(20, $chunksWithCurrentImport);
    }

    public function test_crm_shell_entry_uses_current_api_routes_when_legacy_is_disabled(): void
    {
        $asset = (string) file_get_contents(public_path('assets/index-CqSzWeas.js'));

        $this->assertStringContainsString('/api/administration', $asset);
        $this->assertStringNotContainsString('/api/administration.php', $asset);
        $this->assertStringContainsString('path:`dashboard/crm`,element:(0,z.jsx)(`div`,{id:`crm-dashboard-module`', $asset);
        $this->assertStringNotContainsString('path:`dashboard/crm`,element:$(ly)', $asset);
    }

    public function test_login_page_loads_the_pwa_boot_script_once(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'modules/crm-core/crm-pwa.js'));
        $this->assertStringContainsString('rel="manifest"', $html);
    }

    public function test_mobile_embed_does_not_load_the_pwa_boot_script(): void
    {
        $html = $this->get('/login?mobile_embed=1')->assertOk()->getContent();

        $this->assertStringNotContainsString('modules/crm-core/crm-pwa.js', $html);
    }

    public function test_crm_assets_use_configured_version_before_file_mtime(): void
    {
        config(['crm.assets.version' => 'deploy-test']);

        $this->assertStringEndsWith(
            '/modules/crm-core/crm-pwa.js?v=deploy-test',
            CrmAsset::url('modules/crm-core/crm-pwa.js'),
        );
    }

    public function test_legacy_public_js_entry_uses_its_lazy_import_version(): void
    {
        config(['crm.assets.version' => 'deploy-test']);

        $this->assertStringEndsWith(
            '/assets/index-CqSzWeas.js?v=202607201900',
            CrmAsset::url('assets/index-CqSzWeas.js'),
        );
    }

    public function test_crm_assets_fallback_to_deployed_revision(): void
    {
        config(['crm.assets.version' => null]);

        $path = base_path('.deployed-revision');
        $previous = is_file($path) ? (string) file_get_contents($path) : null;

        file_put_contents($path, "deploy-fallback\n");

        try {
            $this->assertStringEndsWith(
                '/modules/crm-core/crm-pwa.js?v=deploy-fallback',
                CrmAsset::url('modules/crm-core/crm-pwa.js'),
            );
        } finally {
            if ($previous === null) {
                @unlink($path);
            } else {
                file_put_contents($path, $previous);
            }
        }
    }

    public function test_public_pwa_script_exposes_chrome_install_prompt_handling(): void
    {
        $sourceScript = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/crm-pwa.js'));
        $publicScript = (string) file_get_contents(public_path('modules/crm-core/crm-pwa.js'));

        $this->assertSame($sourceScript, $publicScript);
        $this->assertStringContainsString('beforeinstallprompt', $publicScript);
        $this->assertStringContainsString('promptEvent.prompt()', $publicScript);
        $this->assertStringContainsString('window.MartinSolsPwa', $publicScript);
        $this->assertStringContainsString("updateViaCache: 'none'", $publicScript);
        $this->assertStringContainsString('controllerchange', $publicScript);
        $this->assertStringContainsString('updatefound', $publicScript);
        $this->assertStringContainsString('hadServiceWorkerController = Boolean(navigator.serviceWorker.controller)', $publicScript);
        $this->assertStringContainsString('window.location.reload()', $publicScript);
        $this->assertStringContainsString('checkForUpdates: checkForUpdates', $publicScript);
        $this->assertStringContainsString("installAllowedPaths = ['/', '/login', '/dashboard/crm']", $publicScript);
        $this->assertStringContainsString('isInstallButtonAllowedPath', $publicScript);
        $this->assertStringContainsString('isMobileShell', $publicScript);
        $this->assertStringContainsString("window.addEventListener('crm:navigation', routeChanged)", $publicScript);
        $this->assertStringNotContainsString('patchedPwaInstallHistoryState', $publicScript);
        $this->assertStringContainsString("document.visibilityState === 'visible'", $publicScript);
        $this->assertStringContainsString('grid-template-columns:3rem 1px minmax(0,1fr) 1.45rem', $publicScript);
        $this->assertStringContainsString('crm-pwa-install-icon', $publicScript);
        $this->assertStringContainsString('Installer l&apos;application', $publicScript);
        $this->assertStringContainsString('crm-pwa-install-chevron', $publicScript);
        $this->assertStringNotContainsString('left:16px;right:16px;bottom:86px', $publicScript);
    }

    public function test_public_active_site_switcher_keeps_loading_state_visible(): void
    {
        $sourceScript = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/crm-active-site.js'));
        $publicScript = (string) file_get_contents(public_path('modules/crm-core/crm-active-site.js'));

        $this->assertSame($sourceScript, $publicScript);
        $this->assertStringContainsString("'.crm-active-site-select:disabled{cursor:wait;opacity:.72}'", $publicScript);
        $this->assertStringContainsString('!state.sites.length && !state.loading', $publicScript);
        $this->assertStringContainsString("select.innerHTML = '<option value=\"\">Chargement...</option>'", $publicScript);
        $this->assertStringContainsString('select.disabled = true', $publicScript);
        $this->assertStringContainsString('select.disabled = false', $publicScript);
        $this->assertStringContainsString('window.setInterval(function ()', $publicScript);
        $this->assertStringContainsString('attempts >= 40', $publicScript);
        $this->assertStringNotContainsString('new MutationObserver', $publicScript);
    }

    public function test_crm_shell_loads_the_brand_morph_loader(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="brand-morph-loader"'));
        $this->assertStringContainsString('class="brand-morph-loader"', $html);
        $this->assertStringContainsString('data-brand-loader-error', $html);
        $this->assertStringContainsString("loader.classList.add('is-visible')", $html);
        $this->assertStringContainsString('modules/crm-core/brand-morph-loader.css', $html);
        $this->assertStringContainsString('modules/crm-core/brand-morph-loader.js', $html);
        $this->assertStringContainsString('modules/crm-core/brand-morph-loader-app.js', $html);
    }

    public function test_public_brand_morph_loader_assets_match_the_source_files(): void
    {
        $sourceCss = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/brand-morph-loader.css'));
        $publicCss = (string) file_get_contents(public_path('modules/crm-core/brand-morph-loader.css'));
        $sourceScript = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/brand-morph-loader.js'));
        $publicScript = (string) file_get_contents(public_path('modules/crm-core/brand-morph-loader.js'));
        $sourceAppScript = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/brand-morph-loader-app.js'));
        $publicAppScript = (string) file_get_contents(public_path('modules/crm-core/brand-morph-loader-app.js'));

        $this->assertSame($sourceCss, $publicCss);
        $this->assertSame($sourceScript, $publicScript);
        $this->assertSame($sourceAppScript, $publicAppScript);
        $this->assertStringContainsString('--loader-size: 78px', $publicCss);
        $this->assertStringContainsString('--logo-thickness: 24%', $publicCss);
        $this->assertStringContainsString('@keyframes morph-top', $publicCss);
        $this->assertStringContainsString('border-radius: 7px', $publicCss);
        $this->assertStringContainsString('.brand-morph-loader.is-error', $publicCss);
        $this->assertStringContainsString('window.BrandMorphLoader', $publicScript);
        $this->assertStringContainsString('window.CrmLoader = window.BrandMorphLoader', $publicScript);
        $this->assertStringContainsString('activeOperations', $publicScript);
        $this->assertStringContainsString('crmNavigationHistoryState', $publicScript);
        $this->assertStringContainsString("window.dispatchEvent(new CustomEvent('crm:navigation'", $publicScript);
        $this->assertStringContainsString("window.addEventListener('crm:module-error'", $publicScript);
        $this->assertStringContainsString('if (event.defaultPrevented) return', $publicScript);
        $this->assertStringContainsString('[data-no-loader], [data-ajax], [data-crm-ajax-form]', $publicScript);
        $this->assertStringContainsString('forceHide', $publicScript);
        $this->assertStringContainsString('(display-mode: standalone)', $publicAppScript);
        $this->assertStringContainsString('minimumStartupMs = isStandalone ? 950 : 520', $publicAppScript);
        $this->assertStringContainsString('maxStartupWait = isStandalone ? 14000 : 10000', $publicAppScript);
        $this->assertStringContainsString('minimumRouteMs = isStandalone ? 650 : 420', $publicAppScript);
        $this->assertStringContainsString('maxRouteWait = isStandalone ? 18000 : 12000', $publicAppScript);
        $this->assertStringContainsString("beginOperation(routeKey, 0, 'Le module met trop de temps a charger.')", $publicAppScript);
        $this->assertStringContainsString("beginOperation(startupKey, 0, 'Le CRM met trop de temps a charger.')", $publicAppScript);
        $this->assertStringContainsString('hasBlockingLoader', $publicAppScript);
        $this->assertStringContainsString('if (hasLoadingText)', $publicAppScript);
        $this->assertStringContainsString('isSpinnerCandidate', $publicAppScript);
        $this->assertStringContainsString('MutationObserver', $publicAppScript);
        $this->assertStringContainsString('#brand-morph-loader.is-visible ~ #root{opacity:0!important', $publicAppScript);
        $this->assertStringContainsString('#brand-morph-loader.is-visible ~ #root [class~="animate-spin"]', $publicAppScript);
        $this->assertStringContainsString('#brand-morph-loader.is-visible ~ #root [class*="loading"]', $publicAppScript);
        $this->assertStringContainsString("window.addEventListener('crm:navigation', startRouteMonitor)", $publicAppScript);
        $this->assertStringNotContainsString('patchedBrandLoaderHistoryState', $publicAppScript);
        $this->assertStringContainsString('brand-morph-loader-app-style', $publicAppScript);
        $this->assertStringContainsString('backdrop-filter:none', $publicAppScript);
    }

    public function test_module_overlays_do_not_patch_browser_history_locally(): void
    {
        $allowed = [
            base_path('Modules/CrmCore/resources/assets/brand-morph-loader.js'),
        ];

        foreach (glob(base_path('Modules/*/resources/assets/*.js')) ?: [] as $assetPath) {
            if (in_array($assetPath, $allowed, true)) {
                continue;
            }

            $asset = (string) file_get_contents($assetPath);

            $this->assertStringNotContainsString('history[method] =', $asset, $assetPath);
            $this->assertStringNotContainsString('history.pushState =', $asset, $assetPath);
            $this->assertStringNotContainsString('history.replaceState =', $asset, $assetPath);
        }
    }
}
