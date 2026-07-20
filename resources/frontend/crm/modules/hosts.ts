type CrmHostRoute = {
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

const refreshStoragePrefix = 'crm:route-host-refresh:';

function normalizedPath(): string {
  return window.location.pathname.replace(/\/+$/, '') || '/';
}

function routeForCurrentPath(): CrmHostRoute | null {
  const path = normalizedPath();

  return hostRoutes.find((route) => {
    return (route.paths || []).includes(path) || (route.prefix ? path.startsWith(route.prefix) : false);
  }) || null;
}

function pageLooksLikeAdminex404(): boolean {
  const root = document.getElementById('root');
  const text = root?.textContent || '';

  return /\b404\b/.test(text) && /page non trouv|not found/i.test(text);
}

function refreshStaleRouteOnce(): boolean {
  if (!pageLooksLikeAdminex404()) {
    return false;
  }

  const key = `${refreshStoragePrefix}${normalizedPath()}`;

  try {
    if (sessionStorage.getItem(key)) {
      return false;
    }

    sessionStorage.setItem(key, '1');
  } catch {
    return false;
  }

  navigator.serviceWorker?.getRegistration('/')?.then((registration) => {
    registration?.update().catch(() => undefined);
  }).catch(() => undefined);

  window.location.reload();

  return true;
}

function outletElement(): HTMLElement | null {
  const explicit = document.querySelector<HTMLElement>('[data-crm-module-outlet]');

  if (explicit) {
    return explicit;
  }

  return (
    document.querySelector<HTMLElement>('main .layout-container.layout-page')
    || document.querySelector<HTMLElement>('.layout-container.layout-page')
    || document.querySelector<HTMLElement>('main')
    || document.getElementById('root')
  );
}

function ensureHost(): void {
  const route = routeForCurrentPath();

  document.documentElement.classList.toggle('crm-known-module-route', Boolean(route));

  if (!route || document.getElementById(route.id)) {
    return;
  }

  if (refreshStaleRouteOnce()) {
    return;
  }

  const outlet = outletElement();

  if (!outlet) {
    return;
  }

  const host = document.createElement('div');

  host.id = route.id;
  host.className = route.className;
  host.dataset.crmModuleOutlet = 'true';
  host.setAttribute('aria-busy', 'true');
  host.textContent = route.label;

  outlet.replaceChildren(host);
  window.dispatchEvent(new Event('crm:route-changed'));
}

function scheduleEnsureHost(): void {
  window.setTimeout(ensureHost, 0);
  window.setTimeout(ensureHost, 120);
  window.setTimeout(ensureHost, 450);
  window.setTimeout(ensureHost, 1100);
}

export function installCrmModuleHostGuard(): void {
  scheduleEnsureHost();

  document.addEventListener('DOMContentLoaded', scheduleEnsureHost, { once: true });
  window.addEventListener('load', scheduleEnsureHost);
  window.addEventListener('popstate', scheduleEnsureHost);
  window.addEventListener('crm:navigation', scheduleEnsureHost);
  window.addEventListener('crm:route-changed', scheduleEnsureHost);
}
