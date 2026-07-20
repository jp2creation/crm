type BridgePayload = Record<string, unknown>;

function send(type: string, payload: BridgePayload = {}): void {
  try {
    window.parent.postMessage(
      {
        payload,
        source: 'martin-sols-crm',
        type,
      },
      '*',
    );
  } catch {
    // Mobile bridge is optional.
  }
}

function currentPath(): string {
  return window.location.pathname + window.location.search + window.location.hash;
}

function notifyNavigation(): void {
  send('crm:navigation', {
    path: currentPath(),
    title: document.title || '',
  });
}

function installExternalLinkBridge(): void {
  document.addEventListener(
    'click',
    (event) => {
      const target = event.target;
      const link = target instanceof Element ? target.closest<HTMLAnchorElement>('a[href]') : null;

      if (!link) {
        return;
      }

      let url: URL;

      try {
        url = new URL(link.href, window.location.href);
      } catch {
        return;
      }

      if (link.target !== '_blank' && url.origin === window.location.origin) {
        return;
      }

      event.preventDefault();

      if (url.origin === window.location.origin) {
        window.location.href = url.href;
        return;
      }

      send('crm:external-url', {
        title: (link.textContent || url.hostname).trim(),
        url: url.href,
      });
    },
    true,
  );
}

function installMobileMessageBridge(): void {
  window.addEventListener('message', (event) => {
    const message = event.data as { payload?: unknown; source?: string; type?: string } | null;

    if (!message || message.source !== 'martin-sols-mobile') {
      return;
    }

    if (message.type === 'mobile:location-update') {
      window.dispatchEvent(
        new CustomEvent('martin-sols:mobile-location', {
          detail: message.payload || {},
        }),
      );
    }
  });
}

function installNavigationNotifications(): void {
  window.addEventListener('crm:navigation', notifyNavigation);
  window.addEventListener('popstate', notifyNavigation);
  window.addEventListener('load', notifyNavigation);

  if (document.readyState !== 'loading') {
    notifyNavigation();
    return;
  }

  document.addEventListener('DOMContentLoaded', notifyNavigation, { once: true });
}

export function installMobileEmbedBridge(): void {
  if (!document.body.classList.contains('crm-mobile-embed') || document.body.classList.contains('crm-mobile-app')) {
    return;
  }

  installExternalLinkBridge();
  installMobileMessageBridge();
  installNavigationNotifications();

  window.MartinSolsMobileApp = {
    requestLocation: () => {
      send('crm:location-request', { path: currentPath() });
    },
  };
}
