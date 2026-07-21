type CrmModuleKey =
  | 'accountSettings'
  | 'cashControl'
  | 'checkRemittances'
  | 'dashboard'
  | 'depositRequests'
  | 'documents'
  | 'leaves'
  | 'pages'
  | 'sales'
  | 'salesTours'
  | 'teams';

type CrmModuleRoute = {
  key: CrmModuleKey;
  paths?: string[];
  prefix?: string;
};

const moduleLoaders: Record<CrmModuleKey, () => Promise<unknown>> = {
  accountSettings: () => import('../../../../Modules/CrmCore/resources/assets/crm-account-settings.js'),
  cashControl: () => import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
  checkRemittances: () => import('../../../../Modules/CrmCheckRemittances/resources/assets/crm-remise-cheques.js'),
  dashboard: () => import('../../../../Modules/CrmCore/resources/assets/crm-dashboard.js'),
  depositRequests: () => import('../../../../Modules/CrmDepositRequests/resources/assets/crm-demandes-acompte.js'),
  documents: () => import('../../../../Modules/CrmDocuments/resources/assets/crm-documents.js'),
  leaves: () => import('../../../../Modules/CrmLeaves/resources/assets/crm-conges.js'),
  pages: () => import('../../../../Modules/CrmPages/resources/assets/crm-pages.js'),
  sales: () => import('../../../../Modules/CrmSales/resources/assets/crm-pilotage-commercial.js'),
  salesTours: () => import('../../../../Modules/CrmSalesTours/resources/assets/crm-tournees-representants.js'),
  teams: () => import('../../../../Modules/CrmTeams/resources/assets/crm-equipes.js'),
};

const moduleRoutes: CrmModuleRoute[] = [
  { key: 'dashboard', paths: ['/', '/dashboard/crm'] },
  { key: 'leaves', paths: ['/conges'] },
  { key: 'sales', paths: ['/pilotage-commercial'] },
  { key: 'salesTours', paths: ['/rapport-visite', '/tournees-representants'] },
  { key: 'documents', prefix: '/documents/' },
  { key: 'teams', paths: ['/equipes'] },
  { key: 'cashControl', paths: ['/controle-caisse'] },
  { key: 'depositRequests', paths: ['/demandes-acompte'] },
  { key: 'checkRemittances', paths: ['/remise-cheques'], prefix: '/remise-cheques/' },
  { key: 'pages', paths: ['/pages-crm'], prefix: '/pages-crm/' },
  { key: 'accountSettings', paths: ['/pages/account-settings'] },
];

const loadedModules = new Set<CrmModuleKey>();
const loadingModules = new Map<CrmModuleKey, Promise<unknown>>();

function normalizedPath(): string {
  return window.location.pathname.replace(/\/+$/, '') || '/';
}

function moduleKeysForCurrentPath(): CrmModuleKey[] {
  const path = normalizedPath();

  return moduleRoutes
    .filter((route) => (route.paths || []).includes(path) || (route.prefix ? path.startsWith(route.prefix) : false))
    .map((route) => route.key);
}

function loadModule(key: CrmModuleKey): Promise<unknown> {
  if (loadedModules.has(key)) {
    return Promise.resolve();
  }

  const existing = loadingModules.get(key);

  if (existing) {
    return existing;
  }

  const loading = moduleLoaders[key]()
    .then((module) => {
      loadedModules.add(key);
      return module;
    })
    .finally(() => {
      loadingModules.delete(key);
    });

  loadingModules.set(key, loading);

  return loading;
}

function idle(callback: () => void): void {
  const idleCallback = (window as Window & {
    requestIdleCallback?: (callback: () => void, options?: { timeout: number }) => number;
  }).requestIdleCallback;

  if (typeof idleCallback === 'function') {
    idleCallback(callback, { timeout: 2500 });
    return;
  }

  globalThis.setTimeout(callback, 900);
}

export async function loadCurrentCrmModuleOverlay(): Promise<void> {
  const keys = moduleKeysForCurrentPath();

  if (keys.length === 0) {
    return;
  }

  await Promise.all(keys.map(loadModule));
}

export function preloadRemainingCrmModuleOverlays(): void {
  if (window.__martinSolsCrmModulesLoaded) {
    return;
  }

  window.__martinSolsCrmModulesLoaded = true;

  idle(() => {
    Promise.all((Object.keys(moduleLoaders) as CrmModuleKey[]).map(loadModule)).catch((error: unknown) => {
      window.dispatchEvent(new CustomEvent('crm:module-error', { detail: { error } }));
    });
  });
}

export async function loadCrmModuleOverlays(): Promise<void> {
  await Promise.all((Object.keys(moduleLoaders) as CrmModuleKey[]).map(loadModule));
}

export async function loadCrmShellOverlays(): Promise<void> {
  await Promise.all([
    import('../../../../Modules/CrmCore/resources/assets/crm-active-site.js'),
    import('../../../../Modules/CrmCore/resources/assets/crm-text-fixes.js'),
  ]);
}

export async function loadBrandMorphLoader(): Promise<void> {
  await import('../../../../Modules/CrmCore/resources/assets/brand-morph-loader.js');
  await import('../../../../Modules/CrmCore/resources/assets/brand-morph-loader-app.js');
}
