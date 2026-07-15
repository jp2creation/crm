(function () {
  if (window.__martinSolsPwaReady) {
    return;
  }

  window.__martinSolsPwaReady = true;

  var installPrompt = null;
  var installButtonId = 'crm-pwa-install-button';
  var installStyleId = 'crm-pwa-install-style';

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

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
      return;
    }

    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .then(function (registration) {
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
    if (!installPrompt || isStandalone() || !document.body) {
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
  }

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
    refreshInstallButton: renderInstallButton
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();
