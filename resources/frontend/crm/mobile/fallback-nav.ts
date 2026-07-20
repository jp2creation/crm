import type { CrmMenuGroup, CrmMenuItem, CrmModule } from '../types/global';

const fallbackId = 'crm-mobile-fallback-nav';
const drawerId = 'crm-mobile-fallback-drawer';
type HistoryStateArgs = [data: unknown, unused: string, url?: string | URL | null];

function logoUrl(): string {
  return window.MartinSolsCrmAssets?.logoUrl || '/assets/logo/martin-sols-logo.png';
}

function headerIsVisible(): boolean {
  const header = document.querySelector<HTMLElement>('.layout-header');

  if (!header) {
    return false;
  }

  const style = window.getComputedStyle(header);
  const rect = header.getBoundingClientRect();

  return (
    style.display !== 'none' &&
    style.visibility !== 'hidden' &&
    Number(style.opacity || 1) !== 0 &&
    rect.height > 24 &&
    rect.bottom > 0
  );
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

function initials(label: string): string {
  const value = label
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return value || 'MS';
}

function normalizedPath(value: string): string {
  try {
    const url = new URL(value || '/', window.location.origin);

    if (url.origin !== window.location.origin) {
      return url.href;
    }

    return url.pathname.replace(/\/+$/, '') || '/';
  } catch {
    return '/';
  }
}

function isActive(path: string): boolean {
  const current = window.location.pathname.replace(/\/+$/, '') || '/';
  const target = normalizedPath(path);

  if (/^https?:\/\//.test(target)) {
    return false;
  }

  if (target === '/dashboard/crm' && current === '/') {
    return true;
  }

  return current === target || current.startsWith(`${target}/`);
}

function menuData(): { items: { label: string; path: string }[]; title: string }[] {
  const fallback = window.CRM_NAV_FALLBACK;
  const modules = Array.isArray(fallback?.modules) ? fallback.modules : [];
  const routeByKey: Record<string, string> = {};

  modules.forEach((module: CrmModule) => {
    routeByKey[`module:${module.slug}`] = module.routePath || `/${module.slug}`;
  });

  return (Array.isArray(fallback?.menuGroups) ? fallback.menuGroups : [])
    .filter((group: CrmMenuGroup) => group && group.active !== false)
    .sort((a: CrmMenuGroup, b: CrmMenuGroup) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0))
    .map((group: CrmMenuGroup) => {
      const items = (Array.isArray(fallback?.menuItems) ? fallback.menuItems : [])
        .filter((item: CrmMenuItem) => item && item.active !== false && item.groupKey === group.menuKey)
        .sort((a: CrmMenuItem, b: CrmMenuItem) => Number(a.sortOrder || 0) - Number(b.sortOrder || 0))
        .map((item: CrmMenuItem) => ({
          label: item.label || 'CRM',
          path: routeByKey[item.itemKey] || '#',
        }));

      return {
        items,
        title: group.title || group.menuKey || 'CRM',
      };
    })
    .filter((group) => group.items.length > 0);
}

function menuIcon(): string {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>';
}

function closeIcon(): string {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
}

function drawerHtml(): string {
  const groups = menuData()
    .map((group) => {
      const links = group.items
        .map((item) => {
          const active = isActive(item.path);

          return [
            `<a class="crm-mobile-fallback-link${active ? ' is-active' : ''}" href="${esc(item.path)}">`,
            `<span class="crm-mobile-fallback-initials">${esc(initials(item.label))}</span>`,
            `<span>${esc(item.label)}</span>`,
            '</a>',
          ].join('');
        })
        .join('');

      return [
        '<section class="crm-mobile-fallback-group">',
        `<p class="crm-mobile-fallback-group-title">${esc(group.title)}</p>`,
        links,
        '</section>',
      ].join('');
    })
    .join('');

  return [
    `<div id="${drawerId}" class="crm-mobile-fallback-drawer" hidden>`,
    '<button class="crm-mobile-fallback-backdrop" type="button" data-crm-mobile-fallback-close aria-label="Fermer"></button>',
    '<aside class="crm-mobile-fallback-panel" aria-label="Menu CRM">',
    '<div class="crm-mobile-fallback-panel-header">',
    `<img src="${esc(logoUrl())}" alt="Martin Sols">`,
    `<button class="crm-mobile-fallback-close" type="button" data-crm-mobile-fallback-close aria-label="Fermer">${closeIcon()}</button>`,
    '</div>',
    `<nav class="crm-mobile-fallback-nav">${groups}</nav>`,
    '</aside>',
    '</div>',
  ].join('');
}

function openDrawer(): void {
  const drawer = document.getElementById(drawerId);

  if (drawer) {
    drawer.hidden = false;
  }
}

function closeDrawer(): void {
  const drawer = document.getElementById(drawerId);

  if (drawer) {
    drawer.hidden = true;
  }
}

function removeFallback(): void {
  document.getElementById(fallbackId)?.remove();
  document.getElementById(drawerId)?.remove();
  document.body.classList.remove('crm-mobile-fallback-nav-active');
}

function ensureFallback(): void {
  if (headerIsVisible()) {
    removeFallback();
    return;
  }

  if (document.getElementById(fallbackId)) {
    return;
  }

  document.body.insertAdjacentHTML(
    'afterbegin',
    [
      `<header id="${fallbackId}" class="crm-mobile-fallback-header">`,
      `<button class="crm-mobile-fallback-menu-button" type="button" data-crm-mobile-fallback-open aria-label="Menu CRM">${menuIcon()}</button>`,
      '<a class="crm-mobile-fallback-brand" href="/dashboard/crm?mobile_app=1">',
      `<img src="${esc(logoUrl())}" alt="Martin Sols">`,
      '</a>',
      '<span style="width:42px;height:42px" aria-hidden="true"></span>',
      '</header>',
      drawerHtml(),
    ].join(''),
  );

  document.body.classList.add('crm-mobile-fallback-nav-active');
  document.querySelector('[data-crm-mobile-fallback-open]')?.addEventListener('click', openDrawer);
  document.querySelectorAll('[data-crm-mobile-fallback-close]').forEach((button) => {
    button.addEventListener('click', closeDrawer);
  });
}

function scheduleChecks(): void {
  window.setTimeout(ensureFallback, 900);
  window.setTimeout(ensureFallback, 1800);
  window.setTimeout(ensureFallback, 3200);
}

function patchHistoryForFallback(): void {
  (['pushState', 'replaceState'] as const).forEach((method) => {
    const original = history[method].bind(history);

    history[method] = ((...args: HistoryStateArgs) => {
      const result = original(...args);

      closeDrawer();
      scheduleChecks();

      return result;
    }) as History[typeof method];
  });
}

export function installMobileFallbackNavigation(): void {
  if (!document.body.classList.contains('crm-mobile-app')) {
    return;
  }

  document.addEventListener('DOMContentLoaded', scheduleChecks, { once: true });
  window.addEventListener('load', scheduleChecks);
  window.addEventListener('popstate', scheduleChecks);
  patchHistoryForFallback();

  if (document.readyState !== 'loading') {
    scheduleChecks();
  }
}
