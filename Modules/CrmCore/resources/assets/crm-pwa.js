(function () {
  if (window.__martinSolsPwaReady) {
    return;
  }

  window.__martinSolsPwaReady = true;

  var installPrompt = null;
  var installButtonId = 'crm-pwa-install-button';
  var installStyleId = 'crm-pwa-install-style';
  var updateReloading = false;
  var hadServiceWorkerController = false;
  var installAllowedPaths = ['/', '/login', '/dashboard/crm'];

  function dispatch(name, detail) {
    try {
      window.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
    } catch (error) {
      // Optional browser event; never block the CRM UI.
    }
  }

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true;
  }

  function currentPath() {
    return window.location.pathname.replace(/\/+$/, '') || '/';
  }

  function isMobileShell() {
    return document.body && (
      document.body.classList.contains('crm-mobile-app')
      || document.body.classList.contains('crm-mobile-embed')
    );
  }

  function isInstallButtonAllowedPath() {
    return installAllowedPaths.indexOf(currentPath()) !== -1;
  }

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
      return;
    }

    hadServiceWorkerController = Boolean(navigator.serviceWorker.controller);

    navigator.serviceWorker.addEventListener('controllerchange', reloadForUpdatedWorker);
    navigator.serviceWorker.addEventListener('message', handleServiceWorkerMessage);

    navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' })
      .then(function (registration) {
        watchRegistration(registration);

        if (registration.waiting) {
          registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }

        registration.update().catch(function () {});

        return navigator.serviceWorker.ready;
      })
      .then(function () {
        dispatch('crm:pwa-ready');
      })
      .catch(function () {
        // PWA installability is optional; the CRM must continue normally.
      });
  }

  function watchRegistration(registration) {
    registration.addEventListener('updatefound', function () {
      var worker = registration.installing;

      if (!worker) {
        return;
      }

      worker.addEventListener('statechange', function () {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) {
          worker.postMessage({ type: 'SKIP_WAITING' });
          dispatch('crm:pwa-update-found');
        }
      });
    });
  }

  function handleServiceWorkerMessage(event) {
    if (!event.data || event.data.type !== 'CRM_SW_ACTIVATED') {
      return;
    }

    dispatch('crm:pwa-updated', { version: event.data.version || null });
  }

  function reloadForUpdatedWorker() {
    if (!hadServiceWorkerController) {
      hadServiceWorkerController = true;
      return;
    }

    if (updateReloading) {
      return;
    }

    updateReloading = true;
    window.setTimeout(function () {
      window.location.reload();
    }, 250);
  }

  function checkForUpdates() {
    if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) {
      return;
    }

    navigator.serviceWorker.getRegistration('/')
      .then(function (registration) {
        if (registration) {
          registration.update().catch(function () {});
        }
      })
      .catch(function () {});
  }

  function ensureInstallStyle() {
    if (document.getElementById(installStyleId)) {
      return;
    }

    var style = document.createElement('style');
    style.id = installStyleId;
    style.textContent = ''
      + '#crm-pwa-install-button{position:fixed;right:14px;bottom:14px;z-index:9999;display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(149,0,46,.18);border-radius:999px;background:#fff;color:#95002e;padding:7px 10px;font:800 12px/1.2 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;box-shadow:0 10px 24px rgba(15,23,42,.12);cursor:pointer;opacity:.94;}'
      + '#crm-pwa-install-button::before{content:"+";display:grid;place-items:center;width:16px;height:16px;border-radius:999px;background:rgba(149,0,46,.1);font-size:14px;line-height:1;}'
      + '#crm-pwa-install-button:hover{border-color:rgba(149,0,46,.32);background:#fff7fa;opacity:1;}'
      + '#crm-pwa-install-button:focus-visible{outline:3px solid rgba(149,0,46,.18);outline-offset:3px;}'
      + '@media (max-width: 640px){#crm-pwa-install-button{right:12px;bottom:74px;padding:7px 9px;font-size:11.5px;box-shadow:0 8px 20px rgba(15,23,42,.12);}}';

    document.head.appendChild(style);
  }

  function removeInstallButton() {
    var button = document.getElementById(installButtonId);

    if (button) {
      button.remove();
    }
  }

  function renderInstallButton() {
    if (!installPrompt || isStandalone() || isMobileShell() || !isInstallButtonAllowedPath() || !document.body) {
      removeInstallButton();
      return;
    }

    ensureInstallStyle();

    var button = document.getElementById(installButtonId);

    if (!button) {
      button = document.createElement('button');
      button.id = installButtonId;
      button.type = 'button';
      button.addEventListener('click', promptInstall);
      document.body.appendChild(button);
    }

    button.textContent = "Installer";
    button.title = "Installer l'application";
    button.setAttribute('aria-label', "Installer l'application");
    button.hidden = false;
  }

  function promptInstall() {
    if (!installPrompt) {
      return Promise.resolve(null);
    }

    var promptEvent = installPrompt;
    installPrompt = null;
    removeInstallButton();
    promptEvent.prompt();

    return promptEvent.userChoice
      .then(function (choice) {
        dispatch('crm:pwa-install-choice', choice);
        return choice;
      })
      .catch(function () {
        return null;
      });
  }

  function boot() {
    registerServiceWorker();
    renderInstallButton();
    window.setTimeout(checkForUpdates, 3000);
  }

  function routeChanged() {
    renderInstallButton();
  }

  window.addEventListener('focus', checkForUpdates);
  window.addEventListener('popstate', routeChanged);

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      checkForUpdates();
    }
  });

  window.setInterval(checkForUpdates, 15 * 60 * 1000);

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    installPrompt = event;
    renderInstallButton();
    dispatch('crm:pwa-install-available');
  });

  window.addEventListener('appinstalled', function () {
    installPrompt = null;
    removeInstallButton();
    dispatch('crm:pwa-installed');
  });

  window.MartinSolsPwa = {
    canInstall: function () {
      return Boolean(installPrompt);
    },
    install: promptInstall,
    refreshInstallButton: renderInstallButton,
    checkForUpdates: checkForUpdates
  };

  ['pushState', 'replaceState'].forEach(function (method) {
    var original = history[method];

    history[method] = function patchedPwaInstallHistoryState() {
      var result = original.apply(this, arguments);
      window.setTimeout(routeChanged, 0);

      return result;
    };
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
