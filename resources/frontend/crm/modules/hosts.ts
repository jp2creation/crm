type CrmHostRoute = {
  adminexOnly?: boolean;
  className: string;
  id: string;
  label: string;
  paths?: string[];
  prefix?: string;
};

const hostRoutes: CrmHostRoute[] = [
  {
    className: 'crm-dashboard-module-host',
    id: 'crm-dashboard-module',
    label: 'Chargement du tableau de bord...',
    paths: ['/', '/dashboard/crm'],
  },
  {
    adminexOnly: true,
    className: '',
    id: 'crm-adminex-reservations-route',
    label: '',
    paths: ['/reservations', '/locations-materiel'],
  },
  {
    className: 'crm-leaves-module-host',
    id: 'crm-leaves-module',
    label: 'Chargement des conges...',
    paths: ['/conges'],
  },
  {
    className: 'crm-sales-tours-module-host',
    id: 'crm-sales-tours-module',
    label: 'Chargement du rapport de visite...',
    paths: ['/rapport-visite', '/tournees-representants'],
  },
  {
    className: 'crm-documents-module-host',
    id: 'crm-documents-module',
    label: 'Chargement des documents...',
    prefix: '/documents/',
  },
  {
    className: 'crm-teams-module-host',
    id: 'crm-teams-module',
    label: "Chargement de l'equipe...",
    paths: ['/equipes'],
  },
  {
    className: 'crm-cash-control-module-host',
    id: 'crm-cash-control-module',
    label: 'Chargement controle caisse...',
    paths: ['/controle-caisse'],
  },
  {
    className: 'crm-deposit-requests-module-host',
    id: 'crm-deposit-requests-module',
    label: "Chargement des demandes d'acompte...",
    paths: ['/demandes-acompte'],
  },
  {
    className: 'crm-check-remittance-module-host',
    id: 'crm-check-remittance-module',
    label: 'Chargement des remises de cheques...',
    paths: ['/remise-cheques'],
    prefix: '/remise-cheques/',
  },
  {
    className: 'crm-pages-module-host',
    id: 'crm-pages-root',
    label: 'Chargement des pages CRM...',
    paths: ['/pages-crm'],
    prefix: '/pages-crm/',
  },
];

const refreshStoragePrefix = 'crm:route-host-hard-refresh:v2:';
const maxRefreshAttempts = 2;
let rootObserver: MutationObserver | null = null;
let rootObserverTimer: number | null = null;

function normalizedPath(): string {
  return window.location.pathname.replace(/\/+$/, '') || '/';
}

function routeForCurrentPath(): CrmHostRoute | null {
  const path = normalizedPath();

  return hostRoutes.find((route) => {
    return (route.paths || []).includes(path) || (route.prefix ? path.startsWith(route.prefix) : false);
  }) || null;
}

function removeInactiveModuleHosts(activeRoute: CrmHostRoute | null): void {
  hostRoutes.forEach((route) => {
    if (route.adminexOnly || route.id === activeRoute?.id) {
      return;
    }

    document.getElementById(route.id)?.remove();
  });
}

function pageLooksLikeAdminex404(): boolean {
  const root = document.getElementById('root');
  const text = root?.textContent || '';

  return /\b404\b/.test(text) && /page non trouv|not found/i.test(text);
}

async function clearCrmRuntimeCaches(): Promise<void> {
  if (typeof caches !== 'undefined') {
    const keys = await caches.keys();

    await Promise.all(
      keys
        .filter((key) => key.startsWith('martin-sols-crm-'))
        .map((key) => caches.delete(key)),
    );
  }

  if (navigator.serviceWorker) {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(registrations.map((registration) => registration.update().catch(() => undefined)));
    navigator.serviceWorker.controller?.postMessage({ type: 'SKIP_WAITING' });
  }
}

function refreshedRouteUrl(): string {
  const url = new URL(window.location.href);

  url.searchParams.set('_crm_refresh', String(Date.now()));

  return `${url.pathname}${url.search}${url.hash}`;
}

function refreshStaleRouteOnce(): boolean {
  if (!pageLooksLikeAdminex404()) {
    return false;
  }

  const key = `${refreshStoragePrefix}${normalizedPath()}`;

  try {
    const attempt = Number(sessionStorage.getItem(key) || '0');

    if (attempt >= maxRefreshAttempts) {
      return false;
    }

    sessionStorage.setItem(key, String(attempt + 1));
  } catch {
    return false;
  }

  clearCrmRuntimeCaches()
    .catch(() => undefined)
    .finally(() => {
      window.location.replace(refreshedRouteUrl());
    });

  return true;
}

function ensureHost(): void {
  const route = routeForCurrentPath();

  document.documentElement.classList.toggle('crm-known-module-route', Boolean(route));
  removeInactiveModuleHosts(route);

  if (!route || document.getElementById(route.id)) {
    return;
  }

  if (route.adminexOnly) {
    refreshStaleRouteOnce();
    return;
  }

  if (refreshStaleRouteOnce()) {
    return;
  }
}

function scheduleEnsureHost(): void {
  window.setTimeout(ensureHost, 0);
  window.setTimeout(ensureHost, 120);
  window.setTimeout(ensureHost, 450);
  window.setTimeout(ensureHost, 1100);
}

function installRootObserver(): void {
  if (rootObserver) {
    return;
  }

  const target = document.getElementById('root') || document.documentElement;

  rootObserver = new MutationObserver(() => {
    if (rootObserverTimer) {
      window.clearTimeout(rootObserverTimer);
    }

    rootObserverTimer = window.setTimeout(() => {
      rootObserverTimer = null;
      ensureHost();
    }, 80);
  });

  rootObserver.observe(target, { childList: true, subtree: true });
}

export function installCrmModuleHostGuard(): void {
  scheduleEnsureHost();
  installRootObserver();

  document.addEventListener('DOMContentLoaded', scheduleEnsureHost, { once: true });
  document.addEventListener('DOMContentLoaded', installRootObserver, { once: true });
  window.addEventListener('load', scheduleEnsureHost);
  window.addEventListener('popstate', scheduleEnsureHost);
  window.addEventListener('crm:navigation', scheduleEnsureHost);
  window.addEventListener('crm:route-changed', scheduleEnsureHost);
}
