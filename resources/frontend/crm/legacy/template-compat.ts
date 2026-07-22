function configuredAsset(key: keyof NonNullable<Window['MartinSolsCrmAssets']>, fallback: string): string {
  return window.MartinSolsCrmAssets?.[key] || fallback;
}

export function isLegacyTemplateRoute(): boolean {
  return false;
}

function shellStylesheet(): HTMLLinkElement | HTMLScriptElement | null {
  return document.querySelector(
    'link[rel="stylesheet"][href*="/build/assets/shell-"], script[type="module"][src*="/build/assets/shell-"]',
  );
}

function ensureStylesheet(href: string, marker: string, placement: 'append' | 'before-shell' = 'append'): void {
  if (!href || document.querySelector(`link[data-crm-frontend="${marker}"]`)) {
    return;
  }

  const link = document.createElement('link');

  link.rel = 'stylesheet';
  link.href = href;
  link.dataset.crmFrontend = marker;

  const before = placement === 'before-shell' ? shellStylesheet() : null;

  if (before) {
    document.head.insertBefore(link, before);
    return;
  }

  document.head.appendChild(link);
}

export function installLegacyStylesheets(): void {
  ensureStylesheet(
    configuredAsset('brandMorphLoaderStylesheet', '/modules/crm-core/brand-morph-loader.css'),
    'brand-loader',
  );
}

export function installLegacyTemplateNavigationBridge(): void {
  window.__martinSolsCrmLegacyTemplateNavigationBridge = true;
}
