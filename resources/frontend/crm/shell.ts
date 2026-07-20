import './styles/shell.css';
import { installCrmApiClient } from './api/client';
import { installCsrfFetch } from './api/csrf';
import { installCrmShellGlobals, readCrmShellConfig } from './config';
import { installLegacyStylesheets, loadLegacyAdminex } from './legacy/adminex';
import { installLegacyLogoutBridge } from './legacy/logout-bridge';
import { revealBrandLoaderElement } from './loader';
import { installMobileEmbedBridge } from './mobile/embed-bridge';
import { installMobileFallbackNavigation } from './mobile/fallback-nav';
import { installMobileAppSettings } from './mobile/settings';
import { installCrmModuleHostGuard } from './modules/hosts';
import { loadBrandMorphLoader, loadCrmModuleOverlays, loadCrmShellOverlays } from './modules/register';
import { installFallbackNavigation } from './router/menu';
import { applyStoredTheme } from './theme';

const crmShellConfig = readCrmShellConfig();

applyStoredTheme(crmShellConfig.themeStorageKey);
installCrmShellGlobals(crmShellConfig);
installLegacyStylesheets();
revealBrandLoaderElement();
installLegacyLogoutBridge(crmShellConfig);
installFallbackNavigation();

await loadBrandMorphLoader();

installCsrfFetch();
installCrmApiClient();
installMobileAppSettings();
installMobileEmbedBridge();
installMobileFallbackNavigation();
installCrmModuleHostGuard();

const shellLoaderKey = 'crm:shell';

window.CrmLoader?.begin?.(shellLoaderKey, {
  delay: 0,
  timeout: 0,
  timeoutMessage: 'Le CRM met trop de temps a charger.',
});

try {
  await loadCrmShellOverlays();
  await loadLegacyAdminex();
  await loadCrmModuleOverlays();
  window.dispatchEvent(new CustomEvent('crm:module-ready', { detail: { key: shellLoaderKey } }));
} catch (error) {
  window.CrmLoader?.fail?.(shellLoaderKey, error instanceof Error ? error : new Error('Chargement du CRM impossible.'));
  throw error;
} finally {
  window.CrmLoader?.end?.(shellLoaderKey);
}
