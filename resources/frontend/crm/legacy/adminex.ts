function configuredAsset(key: keyof NonNullable<Window['MartinSolsCrmAssets']>, fallback: string): string {
  return window.MartinSolsCrmAssets?.[key] || fallback;
}

const legacyAdminexPaths = new Set(['/reservations', '/locations-materiel']);

function normalizedPath(value: string): string {
  return value.replace(/\/+$/, '') || '/';
}

function shouldHardNavigateToLegacyAdminex(event: MouseEvent, link: HTMLAnchorElement, url: URL): boolean {
  return !event.defaultPrevented
    && event.button === 0
    && !event.metaKey
    && !event.ctrlKey
    && !event.shiftKey
    && !event.altKey
    && link.target !== '_blank'
    && !link.hasAttribute('download')
    && !link.hasAttribute('data-no-loader')
    && url.origin === window.location.origin
    && legacyAdminexPaths.has(normalizedPath(url.pathname))
    && !legacyAdminexPaths.has(normalizedPath(window.location.pathname));
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

export function installLegacyAdminexNavigationBridge(): void {
  if (window.__martinSolsCrmLegacyAdminexNavigationBridge) {
    return;
  }

  window.__martinSolsCrmLegacyAdminexNavigationBridge = true;

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const link = target?.closest<HTMLAnchorElement>('a[href]');

      if (!link) {
        return;
      }

      const url = new URL(link.href, window.location.href);

      if (!shouldHardNavigateToLegacyAdminex(event, link, url)) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      window.CrmLoader?.show?.(0);
      window.location.assign(url.href);
    },
    true,
  );
}

export function loadLegacyAdminex(): Promise<void> {
  return appendModuleScript(configuredAsset('legacyAdminexScript', '/assets/index-CqSzWeas.js'));
}
