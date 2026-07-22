import { isLegacyAdminexRoute } from '../legacy/adminex';
import { crmHostRouteForPath, type CrmHostRoute } from '../modules/hosts';
import type { CrmFallbackNavigation, CrmMenuGroup, CrmMenuItem, CrmModule } from '../types/global';

type CrmProfile = {
  displayName?: string;
  email?: string;
  firstName?: string;
  lastName?: string;
  name?: string;
  photoUrl?: string;
  role?: string;
};

const nativeShellSelector = '[data-crm-native-shell]';
let installed = false;
let profileLoaded = false;

function rootElement(): HTMLElement | null {
  return document.getElementById('root');
}

function normalizedPath(value = window.location.pathname): string {
  return value.replace(/\/+$/, '') || '/';
}

function esc(value: unknown): string {
  return String(value ?? '').replace(/[&<>"']/g, (char) => {
    const entities: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };

    return entities[char] || char;
  });
}

function logoUrl(): string {
  return window.MartinSolsCrmAssets?.logoUrl || '/assets/logo/martin-sols-logo.png';
}

function navigation(): CrmFallbackNavigation {
  return (
    window.CRM_NAV_FALLBACK || {
      menuGroups: [],
      menuItems: [],
      modules: [],
    }
  );
}

function routeByItemKey(): Record<string, string> {
  const routes: Record<string, string> = {};

  navigation()
    .modules.filter((module: CrmModule) => module && module.active !== false)
    .forEach((module: CrmModule) => {
      routes[`module:${module.slug}`] = module.routePath || `/${module.slug}`;
    });

  return routes;
}

function isActivePath(path: string): boolean {
  if (/^https?:\/\//.test(path)) {
    return false;
  }

  const current = normalizedPath();
  const target = normalizedPath(path);

  if (target === '/dashboard/crm' && current === '/') {
    return true;
  }

  return current === target || current.startsWith(`${target}/`);
}

function isAccountSettingsPath(): boolean {
  return normalizedPath() === '/pages/account-settings';
}

function initials(value: string): string {
  const letters = value
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return letters || 'MS';
}

function iconFor(item: CrmMenuItem): string {
  const icons: Record<string, string> = {
    article: 'DC',
    banknote: 'AC',
    calendar: 'CA',
    creditCard: 'CB',
    dashboard: 'DB',
    package: 'LM',
    settings: 'AD',
    table: 'TR',
    truck: 'RV',
    users: 'EQ',
  };

  return icons[item.iconKey] || initials(item.label || item.itemKey);
}

function menuGroupsHtml(): string {
  const routes = routeByItemKey();
  const groups = navigation()
    .menuGroups.filter((group: CrmMenuGroup) => group && group.active !== false)
    .sort((a: CrmMenuGroup, b: CrmMenuGroup) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0));

  return groups
    .map((group: CrmMenuGroup) => {
      const items = navigation()
        .menuItems.filter((item: CrmMenuItem) => item && item.active !== false && item.groupKey === group.menuKey)
        .sort((a: CrmMenuItem, b: CrmMenuItem) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0))
        .map((item: CrmMenuItem) => menuItemHtml(item, routes[item.itemKey] || '#'))
        .join('');

      if (!items) {
        return '';
      }

      return [
        '<section class="crm-native-nav-group">',
        `<p class="crm-native-nav-title">${esc(group.title || group.menuKey || 'CRM')}</p>`,
        items,
        '</section>',
      ].join('');
    })
    .join('');
}

function menuItemHtml(item: CrmMenuItem, path: string): string {
  const external = /^https?:\/\//.test(path);
  const active = isActivePath(path);
  const target = external ? ' target="_blank" rel="noopener noreferrer"' : '';

  return [
    `<a class="crm-native-nav-link${active ? ' is-active' : ''}" href="${esc(path)}"${target}>`,
    `<span class="crm-native-nav-icon">${esc(iconFor(item))}</span>`,
    `<span class="crm-native-nav-label">${esc(item.label || 'CRM')}</span>`,
    '</a>',
  ].join('');
}

function hostHtml(route: CrmHostRoute | null): string {
  if (isAccountSettingsPath()) {
    return '';
  }

  if (!route) {
    return [
      '<section class="crm-native-empty">',
      '<h1>Page non disponible</h1>',
      '<p>Cette page n’est pas encore reliée à la nouvelle coque CRM.</p>',
      '<a class="crm-native-button" href="/dashboard/crm">Tableau de bord</a>',
      '</section>',
    ].join('');
  }

  return `<div id="${esc(route.id)}" class="${esc(route.className)}" aria-label="${esc(route.label)}"></div>`;
}

function routeNeedsHost(route: CrmHostRoute | null): route is CrmHostRoute {
  return Boolean(route);
}

function profileInitials(profile?: CrmProfile): string {
  return initials(profile?.displayName || profile?.name || 'Jean-Philippe');
}

function profileName(profile?: CrmProfile): string {
  return profile?.displayName || profile?.name || 'Jean-Philippe';
}

function profileRole(profile?: CrmProfile): string {
  const role = profile?.role || 'admin';

  return role === 'admin' ? 'Admin' : role.charAt(0).toUpperCase() + role.slice(1);
}

function profilePhoto(profile?: CrmProfile): string {
  return profile?.photoUrl || '/assets/logo/logomark.png';
}

function headerHtml(profile?: CrmProfile): string {
  return [
    '<header class="layout-header crm-native-header">',
    '<div class="layout-container layout-page crm-native-header-inner">',
    '<button class="crm-native-menu-button" type="button" data-crm-native-sidebar-toggle aria-label="Ouvrir le menu">',
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>',
    '</button>',
    '<a class="crm-native-mobile-brand" href="/dashboard/crm">',
    `<img src="${esc(logoUrl())}" alt="Martin Sols">`,
    '</a>',
    '<div class="ms-auto crm-native-header-actions">',
    '<div class="header-actions-divider" aria-hidden="true"></div>',
    '<a class="header-icon-btn crm-native-bell" href="/pages/account-settings" aria-label="Notifications">',
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
    '<span></span>',
    '</a>',
    '<a class="crm-native-user" href="/pages/account-settings" aria-label="Paramètres du compte">',
    '<span class="crm-native-user-text">',
    `<strong data-crm-native-profile-name>${esc(profileName(profile))}</strong>`,
    `<small data-crm-native-profile-role>${esc(profileRole(profile))}</small>`,
    '</span>',
    '<span class="crm-native-avatar">',
    `<img data-crm-native-profile-photo src="${esc(profilePhoto(profile))}" alt="${esc(profileName(profile))}">`,
    `<b data-crm-native-profile-initials>${esc(profileInitials(profile))}</b>`,
    '</span>',
    '</a>',
    '</div>',
    '</div>',
    '</header>',
  ].join('');
}

function shellHtml(route: CrmHostRoute | null): string {
  return [
    '<div class="crm-native-shell" data-crm-native-shell>',
    '<aside class="layout-sidebar crm-native-sidebar" aria-label="Menu CRM">',
    '<a class="crm-native-brand" href="/dashboard/crm">',
    `<img src="${esc(logoUrl())}" alt="Martin Sols">`,
    '</a>',
    `<nav class="crm-native-nav">${menuGroupsHtml()}</nav>`,
    '<button class="crm-native-logout" type="button" data-crm-native-logout>Se déconnecter</button>',
    '</aside>',
    '<button class="crm-native-backdrop" type="button" data-crm-native-sidebar-close aria-label="Fermer le menu"></button>',
    '<div class="crm-native-body">',
    headerHtml(),
    '<main class="crm-native-main">',
    '<div class="layout-container layout-page crm-native-content">',
    hostHtml(route),
    '</div>',
    '</main>',
    '</div>',
    '</div>',
  ].join('');
}

function updateActiveLinks(): void {
  document.querySelectorAll<HTMLAnchorElement>('.crm-native-nav-link').forEach((link) => {
    link.classList.toggle('is-active', isActivePath(link.getAttribute('href') || '#'));
  });
}

function ensureHost(route: CrmHostRoute | null): void {
  const content = document.querySelector<HTMLElement>('.crm-native-content');

  if (!content) {
    return;
  }

  if (routeNeedsHost(route)) {
    if (!document.getElementById(route.id)) {
      content.innerHTML = hostHtml(route);
    }

    return;
  }

  if (isAccountSettingsPath()) {
    if (content.querySelector('.crm-native-empty')) {
      content.innerHTML = '';
    }

    return;
  }

  if (!content.querySelector('.crm-native-empty')) {
    content.innerHTML = hostHtml(null);
  }
}

function setNativeShellClasses(active: boolean): void {
  document.documentElement.classList.toggle('crm-native-shell-active', active);
  document.body.classList.toggle('crm-native-shell-active', active);
}

function renderNativeShell(): void {
  const root = rootElement();

  if (!root || isLegacyAdminexRoute()) {
    setNativeShellClasses(false);
    return;
  }

  const route = crmHostRouteForPath(window.location.pathname);

  if (!root.querySelector(nativeShellSelector)) {
    root.innerHTML = shellHtml(route);
  } else {
    ensureHost(route);
    updateActiveLinks();
  }

  setNativeShellClasses(true);
  document.documentElement.classList.add('crm-known-module-route');
}

async function loadProfile(): Promise<void> {
  if (profileLoaded || isLegacyAdminexRoute()) {
    return;
  }

  profileLoaded = true;

  try {
    const response = await fetch('/api/administration?action=profile', {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();

    if (!response.ok || payload.ok === false || !payload.profile) {
      return;
    }

    hydrateProfile(payload.profile as CrmProfile);
  } catch {
    // The profile is decorative in the shell; module access still decides auth.
  }
}

function hydrateProfile(profile: CrmProfile): void {
  const photo = document.querySelector<HTMLImageElement>('[data-crm-native-profile-photo]');
  const initialsNode = document.querySelector<HTMLElement>('[data-crm-native-profile-initials]');
  const nameNode = document.querySelector<HTMLElement>('[data-crm-native-profile-name]');
  const roleNode = document.querySelector<HTMLElement>('[data-crm-native-profile-role]');

  if (photo) {
    photo.src = profilePhoto(profile);
    photo.alt = profileName(profile);
  }

  if (initialsNode) {
    initialsNode.textContent = profileInitials(profile);
  }

  if (nameNode) {
    nameNode.textContent = profileName(profile);
  }

  if (roleNode) {
    roleNode.textContent = profileRole(profile);
  }
}

function closeSidebar(): void {
  document.body.classList.remove('crm-native-sidebar-open');
}

function toggleSidebar(): void {
  document.body.classList.toggle('crm-native-sidebar-open');
}

function installEvents(): void {
  if (installed) {
    return;
  }

  installed = true;

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target instanceof Element ? event.target : null;

      if (target?.closest('[data-crm-native-sidebar-toggle]')) {
        event.preventDefault();
        toggleSidebar();
        return;
      }

      if (target?.closest('[data-crm-native-sidebar-close]')) {
        event.preventDefault();
        closeSidebar();
        return;
      }

      if (target?.closest('[data-crm-native-logout]')) {
        event.preventDefault();
        window.MartinSolsCrmLogout?.();
      }
    },
    true,
  );

  window.addEventListener('popstate', () => {
    renderNativeShell();
    closeSidebar();
  });

  window.addEventListener('crm:navigation', () => {
    renderNativeShell();
    closeSidebar();
  });

  window.addEventListener('crm:route-changed', () => {
    renderNativeShell();
    closeSidebar();
  });
}

export function installNativeCrmShell(): void {
  installEvents();
  renderNativeShell();

  if (document.readyState === 'loading') {
    document.addEventListener(
      'DOMContentLoaded',
      () => {
        renderNativeShell();
        void loadProfile();
      },
      { once: true },
    );
  } else {
    void loadProfile();
  }

  window.setTimeout(renderNativeShell, 80);
  window.setTimeout(renderNativeShell, 300);
}
