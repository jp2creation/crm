const deadLegacyExactPaths = new Set([
  '/auth/register',
  '/auth/forgot-password',
  '/pages/pricing',
  '/pages/gallery',
  '/pages/faq',
  '/pages/typography',
]);

const deadLegacyPrefixes = ['/app', '/auth-card', '/charts', '/features', '/forms', '/tables'];

const crmDashboardPaths = new Set(['/', '/dashboard', '/dashboard/crm']);
const crmPagesPaths = new Set(['/pages/account-settings']);
const hiddenHeaderLabels = new Set(['applications', 'composants']);
const neutralizedTitle = 'Ancien lien de template desactive';

let mutationFrame = 0;

function normalizedPath(pathname: string): string {
  return pathname.replace(/\/+$/, '') || '/';
}

function normalizedLabel(value: string): string {
  return value
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .trim()
    .toLowerCase();
}

function isPlainLeftClick(event: MouseEvent, link: HTMLAnchorElement): boolean {
  return (
    !event.defaultPrevented &&
    event.button === 0 &&
    !event.metaKey &&
    !event.ctrlKey &&
    !event.shiftKey &&
    !event.altKey &&
    link.target !== '_blank' &&
    !link.hasAttribute('download')
  );
}

function isDeadLegacyTemplatePath(pathname: string): boolean {
  const path = normalizedPath(pathname);

  if (crmDashboardPaths.has(path) || crmPagesPaths.has(path)) {
    return false;
  }

  if (deadLegacyExactPaths.has(path)) {
    return true;
  }

  if (path.startsWith('/dashboard/') && !path.startsWith('/dashboard/crm')) {
    return true;
  }

  return deadLegacyPrefixes.some((prefix) => path === prefix || path.startsWith(`${prefix}/`));
}

function isDeadLegacyTemplateUrl(url: URL): boolean {
  return url.origin === window.location.origin && isDeadLegacyTemplatePath(url.pathname);
}

function injectStyles(): void {
  if (document.getElementById('crm-dead-legacy-link-styles')) {
    return;
  }

  const style = document.createElement('style');

  style.id = 'crm-dead-legacy-link-styles';
  style.textContent = `
    a[data-crm-dead-legacy-link="true"] {
      cursor: default !important;
      opacity: 0.42 !important;
      filter: grayscale(0.35);
      pointer-events: auto;
    }

    a[data-crm-dead-legacy-link-hidden="true"],
    button[data-crm-dead-legacy-menu-hidden="true"] {
      display: none !important;
    }
  `;

  document.head.appendChild(style);
}

function neutralizeLink(link: HTMLAnchorElement, hide = false): void {
  link.dataset.crmDeadLegacyLink = 'true';
  link.setAttribute('aria-disabled', 'true');
  link.setAttribute('tabindex', '-1');
  link.title = neutralizedTitle;

  if (hide) {
    link.dataset.crmDeadLegacyLinkHidden = 'true';
  }
}

function shouldHideHeaderLink(link: HTMLAnchorElement, path: string): boolean {
  return (
    Boolean(link.closest('.layout-header')) &&
    (path === '/pages/pricing' ||
      path === '/app/blog/create' ||
      path.startsWith('/app/') ||
      path.startsWith('/features/') ||
      path.startsWith('/forms/') ||
      path.startsWith('/tables/') ||
      path.startsWith('/charts/'))
  );
}

function neutralizeDeadLinks(root: ParentNode = document): void {
  root.querySelectorAll<HTMLAnchorElement>('a[href]').forEach((link) => {
    const url = new URL(link.href, window.location.href);

    if (!isDeadLegacyTemplateUrl(url)) {
      return;
    }

    const path = normalizedPath(url.pathname);
    neutralizeLink(link, shouldHideHeaderLink(link, path));
  });
}

function hideLegacyMegaMenus(root: ParentNode = document): void {
  root.querySelectorAll<HTMLButtonElement>('.layout-header button.header-nav-link').forEach((button) => {
    const label = normalizedLabel(button.textContent || '');

    if (!hiddenHeaderLabels.has(label)) {
      return;
    }

    button.dataset.crmDeadLegacyMenuHidden = 'true';
    button.setAttribute('aria-hidden', 'true');
    button.setAttribute('tabindex', '-1');
  });
}

function sweepLegacyTemplateChrome(root: ParentNode = document): void {
  injectStyles();
  neutralizeDeadLinks(root);
  hideLegacyMegaMenus(root);
}

function scheduleSweep(root: ParentNode = document): void {
  if (mutationFrame) {
    return;
  }

  mutationFrame = window.requestAnimationFrame(() => {
    mutationFrame = 0;
    sweepLegacyTemplateChrome(root);
  });
}

export function installDeadLegacyLinkGuard(): void {
  if (window.__martinSolsCrmDeadLegacyLinksInstalled) {
    return;
  }

  window.__martinSolsCrmDeadLegacyLinksInstalled = true;

  sweepLegacyTemplateChrome();

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const link = target?.closest<HTMLAnchorElement>('a[href]');

      if (!link || !isPlainLeftClick(event, link)) {
        return;
      }

      const url = new URL(link.href, window.location.href);

      if (!isDeadLegacyTemplateUrl(url)) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      window.CrmLoader?.hide?.('crm:legacy-dead-link');
    },
    true,
  );

  new MutationObserver((mutations) => {
    const root = mutations.find((mutation) => mutation.target instanceof Element)?.target;
    scheduleSweep(root instanceof Element ? root.ownerDocument : document);
  }).observe(document.documentElement, {
    childList: true,
    subtree: true,
  });
}
