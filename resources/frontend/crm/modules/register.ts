type CrmModuleKey =
  | 'accountSettings'
  | 'administration'
  | 'cashControl'
  | 'checkRemittances'
  | 'dashboard'
  | 'depositRequests'
  | 'documents'
  | 'equipmentRentals'
  | 'leaves'
  | 'pages'
  | 'reservations'
  | 'sales'
  | 'salesTours'
  | 'tapisRomus'
  | 'teams';

type CrmModuleRoute = {
  key: CrmModuleKey;
  paths?: string[];
  prefix?: string;
};

type LegacyReactComponentOptions = {
  componentExport: string;
  hostId: string;
  loader: () => Promise<Record<string, unknown>>;
};

function mountLegacyReactComponent(options: LegacyReactComponentOptions): Promise<void> {
  return import('../legacy/react-components').then((module) => module.mountLegacyReactComponent(options));
}

function loadLegacyAsset(filename: string): Promise<Record<string, unknown>> {
  return import(/* @vite-ignore */ `/assets/${filename}?v=202607201920`) as Promise<Record<string, unknown>>;
}

const moduleLoaders: Record<CrmModuleKey, () => Promise<unknown>> = {
  accountSettings: () => import('../../../../Modules/CrmCore/resources/assets/crm-account-settings.js'),
  administration: () =>
    mountLegacyReactComponent({
      componentExport: 'AdministrationPage',
      hostId: 'crm-administration-module',
      loader: () => loadLegacyAsset('administration-DvgVMvBV.js'),
    }),
  cashControl: () => import('../../../../Modules/CrmCashControl/resources/assets/crm-controle-caisse.js'),
  checkRemittances: () => import('../../../../Modules/CrmCheckRemittances/resources/assets/crm-remise-cheques.js'),
  dashboard: () => import('../../../../Modules/CrmCore/resources/assets/crm-dashboard.js'),
  depositRequests: () => import('../../../../Modules/CrmDepositRequests/resources/assets/crm-demandes-acompte.js'),
  documents: () => import('../../../../Modules/CrmDocuments/resources/assets/crm-documents.js'),
  equipmentRentals: () =>
    mountLegacyReactComponent({
      componentExport: 'EquipmentRentalsPage',
      hostId: 'crm-equipment-rentals-module',
      loader: () => loadLegacyAsset('equipment-rentals-Codex2.js'),
    }),
  leaves: () => import('../../../../Modules/CrmLeaves/resources/assets/crm-conges.js'),
  pages: () => import('../../../../Modules/CrmPages/resources/assets/crm-pages.js'),
  reservations: () =>
    mountLegacyReactComponent({
      componentExport: 'ReservationsPage',
      hostId: 'crm-reservations-module',
      loader: () => loadLegacyAsset('reservations-CSr_CND1.js'),
    }),
  sales: () => import('../../../../Modules/CrmSales/resources/assets/crm-pilotage-commercial.js'),
  salesTours: () => import('../../../../Modules/CrmSalesTours/resources/assets/crm-tournees-representants.js'),
  tapisRomus: () =>
    mountLegacyReactComponent({
      componentExport: 'TapisRomusPage',
      hostId: 'crm-tapis-romus-module',
      loader: () => loadLegacyAsset('tapis-romus-CVLfrJx-.js'),
    }),
  teams: () => import('../../../../Modules/CrmTeams/resources/assets/crm-equipes.js'),
};

const moduleRoutes: CrmModuleRoute[] = [
  { key: 'dashboard', paths: ['/', '/dashboard/crm'] },
  { key: 'reservations', paths: ['/reservations'], prefix: '/reservations/' },
  { key: 'equipmentRentals', paths: ['/locations-materiel'], prefix: '/locations-materiel/' },
  { key: 'administration', paths: ['/administration'], prefix: '/administration/' },
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
  { key: 'tapisRomus', paths: ['/tapis-romus'], prefix: '/tapis-romus/' },
];

const loadedModules = new Set<CrmModuleKey>();
const loadingModules = new Map<CrmModuleKey, Promise<unknown>>();
const transitionalReactModules = new Set<CrmModuleKey>([
  'administration',
  'equipmentRentals',
  'reservations',
  'tapisRomus',
]);
let currentRouteLoadTimer: number | null = null;

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
  const idleCallback = (
    window as Window & {
      requestIdleCallback?: (callback: () => void, options?: { timeout: number }) => number;
    }
  ).requestIdleCallback;

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

function loadCurrentRouteModules(): void {
  if (currentRouteLoadTimer) {
    window.clearTimeout(currentRouteLoadTimer);
  }

  currentRouteLoadTimer = window.setTimeout(() => {
    currentRouteLoadTimer = null;

    const keys = moduleKeysForCurrentPath();

    if (keys.length === 0) {
      return;
    }

    const key = `crm:route-module:${normalizedPath()}`;

    window.dispatchEvent(
      new CustomEvent('crm:module-loading', {
        detail: {
          key,
          delay: 0,
          timeout: 0,
          timeoutMessage: 'Le module met trop de temps a charger.',
        },
      }),
    );

    loadCurrentCrmModuleOverlay()
      .then(() => {
        window.dispatchEvent(new CustomEvent('crm:module-ready', { detail: { key } }));
      })
      .catch((error: unknown) => {
        window.dispatchEvent(new CustomEvent('crm:module-error', { detail: { key, error } }));
      });
  }, 0);
}

export function installCurrentCrmModuleRouteLoader(): void {
  if (window.__martinSolsCrmRouteModuleLoaderInstalled) {
    return;
  }

  window.__martinSolsCrmRouteModuleLoaderInstalled = true;

  window.addEventListener('popstate', loadCurrentRouteModules);
  window.addEventListener('crm:navigation', loadCurrentRouteModules);
  window.addEventListener('crm:route-changed', loadCurrentRouteModules);
}

export function preloadRemainingCrmModuleOverlays(): void {
  if (window.__martinSolsCrmModulesLoaded) {
    return;
  }

  window.__martinSolsCrmModulesLoaded = true;

  idle(() => {
    const preloadableModules = (Object.keys(moduleLoaders) as CrmModuleKey[]).filter(
      (key) => !transitionalReactModules.has(key),
    );

    Promise.all(preloadableModules.map(loadModule)).catch((error: unknown) => {
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
