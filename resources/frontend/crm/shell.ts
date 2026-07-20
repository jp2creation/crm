import './styles/shell.css';
import { installCrmApiClient } from './api/client';
import { installCsrfFetch } from './api/csrf';
import { installLegacyStylesheets, loadLegacyAdminex } from './legacy/adminex';
import { installMobileEmbedBridge } from './mobile/embed-bridge';
import { installMobileFallbackNavigation } from './mobile/fallback-nav';
import { installMobileAppSettings } from './mobile/settings';
import { loadBrandMorphLoader, loadCrmModuleOverlays, loadCrmShellOverlays } from './modules/register';
import { installFallbackNavigation } from './router/menu';

installLegacyStylesheets();
installFallbackNavigation();
installCsrfFetch();
installCrmApiClient();
installMobileAppSettings();
installMobileEmbedBridge();
installMobileFallbackNavigation();

await loadCrmShellOverlays();
await loadLegacyAdminex();
await loadCrmModuleOverlays();
await loadBrandMorphLoader();
