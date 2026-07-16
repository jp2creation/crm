<?php

namespace Tests\Feature;

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
        $this->assertStringContainsString("CACHE_VERSION = 'martin-sols-crm-v2026071603'", $serviceWorker);
        $this->assertStringContainsString('cache.add(url).catch(() => null)', $serviceWorker);
        $this->assertStringContainsString("event.data.type === 'SKIP_WAITING'", $serviceWorker);
        $this->assertStringContainsString("event.data.type === 'GET_VERSION'", $serviceWorker);
        $this->assertStringContainsString("notifyClients('CRM_SW_ACTIVATED')", $serviceWorker);
        $this->assertStringContainsString("notifyClients('CRM_SW_VERSION')", $serviceWorker);
        $this->assertStringContainsString("fetch(request, { cache: 'no-store' })", $serviceWorker);
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

    public function test_public_pwa_script_exposes_chrome_install_prompt_handling(): void
    {
        $sourceScript = (string) file_get_contents(base_path('Modules/CrmCore/resources/assets/crm-pwa.js'));
        $publicScript = (string) file_get_contents(public_path('modules/crm-core/crm-pwa.js'));

        $this->assertSame($sourceScript, $publicScript);
        $this->assertStringContainsString('beforeinstallprompt', $publicScript);
        $this->assertStringContainsString('promptEvent.prompt()', $publicScript);
        $this->assertStringContainsString('window.MartinSolsPwa', $publicScript);
        $this->assertStringContainsString("updateViaCache: 'none'", $publicScript);
        $this->assertStringContainsString("controllerchange", $publicScript);
        $this->assertStringContainsString("updatefound", $publicScript);
        $this->assertStringContainsString('hadServiceWorkerController = Boolean(navigator.serviceWorker.controller)', $publicScript);
        $this->assertStringContainsString('window.location.reload()', $publicScript);
        $this->assertStringContainsString('checkForUpdates: checkForUpdates', $publicScript);
        $this->assertStringContainsString("document.visibilityState === 'visible'", $publicScript);
        $this->assertStringContainsString('background:#fff;color:#95002e', $publicScript);
        $this->assertStringContainsString('button.textContent = "Installer"', $publicScript);
        $this->assertStringNotContainsString('left:16px;right:16px;bottom:86px', $publicScript);
    }
}
