function configuredAsset(key: keyof NonNullable<Window['MartinSolsCrmAssets']>, fallback: string): string {
  return window.MartinSolsCrmAssets?.[key] || fallback;
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

function appendModuleScript(src: string): Promise<void> {
  const existing = document.querySelector<HTMLScriptElement>('script[data-crm-legacy-adminex]');

  if (existing?.dataset.loaded === 'true') {
    return Promise.resolve();
  }

  if (window.__martinSolsCrmLegacyAdminexLoading) {
    return window.__martinSolsCrmLegacyAdminexLoading;
  }

  window.__martinSolsCrmLegacyAdminexLoading = new Promise((resolve, reject) => {
    const script = existing || document.createElement('script');

    script.type = 'module';
    script.crossOrigin = 'anonymous';
    script.src = src;
    script.dataset.crmLegacyAdminex = 'true';

    script.addEventListener(
      'load',
      () => {
        script.dataset.loaded = 'true';
        resolve();
      },
      { once: true },
    );

    script.addEventListener(
      'error',
      () => {
        reject(new Error('Impossible de charger Adminex legacy.'));
      },
      { once: true },
    );

    if (!existing) {
      document.head.appendChild(script);
    }
  });

  return window.__martinSolsCrmLegacyAdminexLoading;
}

export function installLegacyStylesheets(): void {
  ensureStylesheet(
    configuredAsset('legacyAdminexStylesheet', '/assets/index-CVBlw941.css'),
    'legacy-adminex',
    'before-shell',
  );
  ensureStylesheet(
    configuredAsset('brandMorphLoaderStylesheet', '/modules/crm-core/brand-morph-loader.css'),
    'brand-loader',
  );
}

export function loadLegacyAdminex(): Promise<void> {
  return appendModuleScript(configuredAsset('legacyAdminexScript', '/assets/index-CqSzWeas.js'));
}
