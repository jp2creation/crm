import type { CrmShellConfig } from '../config';

function isLegacyLogoutUrl(value: string | null | undefined, legacyLogoutPath: string): boolean {
  if (!value) {
    return false;
  }

  try {
    const url = new URL(value, window.location.href);
    const path = url.pathname.replace(/\/+$/, '') || '/';

    return url.origin === window.location.origin && path === legacyLogoutPath;
  } catch {
    return false;
  }
}

function submitLogoutFallback(config: CrmShellConfig): void {
  const form = document.createElement('form');
  const token = document.createElement('input');

  form.method = 'POST';
  form.action = config.logout.logoutUrl;
  form.style.display = 'none';

  token.type = 'hidden';
  token.name = '_token';
  token.value = config.csrfToken;

  form.appendChild(token);
  document.body.appendChild(form);
  form.submit();
}

export function installLegacyLogoutBridge(config: CrmShellConfig): void {
  let logoutInProgress = false;

  const logoutToCrmLogin = () => {
    if (logoutInProgress) {
      return;
    }

    logoutInProgress = true;

    fetch(config.logout.logoutUrl, {
      credentials: 'same-origin',
      headers: {
        Accept: 'text/html,application/xhtml+xml',
        'X-CSRF-TOKEN': config.csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      method: 'POST',
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error('Logout failed');
        }

        window.location.replace(config.logout.loginUrl);
      })
      .catch(() => {
        submitLogoutFallback(config);
      });
  };

  document.addEventListener(
    'click',
    (event) => {
      const target = event.target;
      const link = target instanceof Element ? target.closest<HTMLAnchorElement>('a[href]') : null;

      if (!link || !isLegacyLogoutUrl(link.href, config.logout.legacyLogoutPath)) {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      logoutToCrmLogin();
    },
    true,
  );

  (['pushState', 'replaceState'] as const).forEach((method) => {
    const original = history[method];

    history[method] = function patchedLegacyLogoutRoute(
      this: History,
      data: unknown,
      unused: string,
      url?: string | URL | null,
    ) {
      if (isLegacyLogoutUrl(url?.toString(), config.logout.legacyLogoutPath)) {
        logoutToCrmLogin();
        return;
      }

      return original.call(this, data, unused, url);
    };
  });

  window.MartinSolsCrmLogout = logoutToCrmLogin;
}
