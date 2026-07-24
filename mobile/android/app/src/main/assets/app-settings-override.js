(function () {
  if (window.MartinSolsAndroidSettingsOverride) {
    return;
  }

  window.MartinSolsAndroidSettingsOverride = true;

  var storageKey = 'martin-sols-native-settings';
  var lastLocation = null;
  var locationLoading = false;

  function bridge() {
    return window.MartinSolsNativeApp || null;
  }

  function readSettings() {
    try {
      return Object.assign({
        locationEnabled: false,
        highAccuracyLocation: true,
      }, JSON.parse(localStorage.getItem(storageKey) || '{}'));
    } catch (error) {
      return {
        locationEnabled: false,
        highAccuracyLocation: true,
      };
    }
  }

  var settings = readSettings();

  function writeSettings() {
    localStorage.setItem(storageKey, JSON.stringify(settings));
  }

  function icon(name) {
    var icons = {
      back: '<path d="m15 18-6-6 6-6"></path>',
      code: '<path d="M7 8h10M7 12h10M7 16h6"></path><rect x="4" y="4" width="16" height="16" rx="4"></rect>',
      device: '<rect x="7" y="2.75" width="10" height="18.5" rx="2.5"></rect><path d="M10.5 18h3"></path>',
      face: '<circle cx="12" cy="12" r="8"></circle><path d="M9 10h.01M15 10h.01M9.5 15a4 4 0 0 0 5 0"></path>',
      fingerprint: '<path d="M8.5 11.5a3.5 3.5 0 0 1 7 0c0 2.8-1.1 4.9-2.7 6.7"></path><path d="M6.5 14.8c.3-2.9.2-4.5 1.4-6a5.3 5.3 0 0 1 8.4-.1c1.3 1.7 1.4 3.2 1.1 5.8"></path><path d="M11.5 12c0 3-.7 5-2 6.8M14 12.3c0 2.4-.6 4.1-1.7 5.4"></path>',
      key: '<path d="M15 7a4 4 0 1 1-1.2 2.85L6 17.65V21H2v-4h3.35l7.8-7.8A4 4 0 0 1 15 7Z"></path><path d="M17 7h.01"></path>',
      location: '<path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"></path><circle cx="12" cy="10" r="2"></circle>',
      lock: '<rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>',
      refresh: '<path d="M20 11a8 8 0 0 0-14.4-4.8L4 8"></path><path d="M4 4v4h4"></path><path d="M4 13a8 8 0 0 0 14.4 4.8L20 16"></path><path d="M20 20v-4h-4"></path>',
      shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path>',
      wifi: '<path d="M5 12.5a10 10 0 0 1 14 0"></path><path d="M8.5 16a5 5 0 0 1 7 0"></path><path d="M12 19h.01"></path>',
    };

    return '<svg viewBox="0 0 24 24" aria-hidden="true">' + (icons[name] || icons.device) + '</svg>';
  }

  function installStyles() {
    if (document.getElementById('martin-sols-native-settings-style')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'martin-sols-native-settings-style';
    style.textContent = [
      '.ms-native-settings[hidden]{display:none!important}',
      'body.ms-native-settings-open{overflow:hidden!important}',
      '.ms-native-settings{position:fixed;inset:0;z-index:2147483647;overflow:hidden;background:#f4f7fb;color:#1d354f;font-family:"DM Sans",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}',
      '.ms-native-settings-panel{width:100%;height:100dvh;min-height:100dvh;overflow:auto;background:#f4f7fb}',
      '.ms-native-settings-header,.ms-native-settings-content{width:min(100%,560px);margin:0 auto}',
      '.ms-native-settings-header{display:grid;gap:22px;padding:max(28px,calc(env(safe-area-inset-top) + 18px)) 24px 18px}',
      '.ms-native-settings-back{display:inline-flex;align-items:center;gap:6px;justify-self:start;min-height:32px;padding:0;border:0;background:transparent;color:#95002e;font:inherit;font-size:1rem;font-weight:850}',
      '.ms-native-settings-header h2{margin:0;color:#1d354f;font-size:1.72rem;font-weight:950;letter-spacing:0;line-height:1.12}',
      '.ms-native-settings-content{display:grid;gap:18px;padding:0 24px max(28px,calc(env(safe-area-inset-bottom) + 18px))}',
      '.ms-native-settings-quick-card,.ms-native-settings-card{border:1px solid rgb(226 232 240 / .72);border-radius:8px;background:#fff;box-shadow:0 12px 28px rgb(15 23 42 / .045)}',
      '.ms-native-settings-quick-card{display:grid;padding:12px 18px}',
      '.ms-native-settings-card{display:grid;padding:18px 18px 12px}',
      '.ms-native-settings-heading{display:grid;grid-template-columns:42px minmax(0,1fr) auto;align-items:start;gap:12px;padding-bottom:12px}',
      '.ms-native-settings-heading h3,.ms-native-settings-heading p{margin:0}',
      '.ms-native-settings-heading h3{color:#1d354f;font-size:1.02rem;font-weight:950;line-height:1.2}',
      '.ms-native-settings-heading p{margin-top:8px;color:#4a5c70;font-size:.9rem;font-weight:650;line-height:1.45}',
      '.ms-native-settings-icon,.ms-native-settings-method-icon{display:grid;width:36px;height:36px;place-items:center;border-radius:8px}',
      '.ms-native-settings-icon svg,.ms-native-settings-method-icon svg,.ms-native-settings-back svg{width:1.12rem;height:1.12rem;fill:none;stroke:currentColor;stroke-width:2.15;stroke-linecap:round;stroke-linejoin:round}',
      '.ms-native-settings-back svg{width:1.18rem;height:1.18rem}',
      '.ms-native-settings-icon.is-red,.ms-native-settings-method-icon{background:rgb(149 0 46 / .08);color:#95002e}',
      '.ms-native-settings-icon.is-yellow{background:rgb(245 178 18 / .13);color:#bf8000}',
      '.ms-native-settings-icon.is-blue{background:rgb(14 165 233 / .1);color:#0284c7}',
      '.ms-native-settings-icon.is-green{background:rgb(34 197 94 / .1);color:#16a34a}',
      '.ms-native-settings-pill{align-self:start;padding:7px 10px;border-radius:999px;background:#f1f5f9;color:#5d7287;font-size:.72rem;font-weight:900;line-height:1;white-space:nowrap}',
      '.ms-native-settings-pill.is-ready{background:#dcfce7;color:#166534}',
      '.ms-native-settings-mini-action{align-self:start;min-height:34px;padding:7px 11px;border:0;border-radius:8px;background:#95002e;color:#fff;font:inherit;font-size:.76rem;font-weight:900;white-space:nowrap}',
      '.ms-native-settings-mini-action:disabled{opacity:.5}',
      '.ms-native-settings-methods{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;padding:0 0 12px}',
      '.ms-native-settings-methods>div{min-width:0;padding:12px 9px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}',
      '.ms-native-settings-method-icon{margin-bottom:10px}',
      '.ms-native-settings-methods strong,.ms-native-settings-methods small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}',
      '.ms-native-settings-methods strong{color:#1d354f;font-size:.85rem;font-weight:900;line-height:1.2}',
      '.ms-native-settings-methods small{margin-top:4px;color:#5d7287;font-size:.72rem;font-weight:800;line-height:1.2}',
      '.ms-native-settings-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:12px;min-height:60px;width:100%;padding:12px 0;border:0;border-top:1px solid rgb(226 232 240 / .82);background:transparent;color:#1d354f;font:inherit;text-align:left}',
      '.ms-native-settings-quick-card .ms-native-settings-row:first-child,.ms-native-settings-heading+.ms-native-settings-row{border-top:0}',
      '.ms-native-settings-copy{display:grid;min-width:0;gap:4px}',
      '.ms-native-settings-copy strong{color:#1d354f;font-size:1rem;font-weight:900;line-height:1.18}',
      '.ms-native-settings-copy small{overflow-wrap:anywhere;color:#5d7287;font-size:.8rem;font-weight:720;line-height:1.3}',
      '.ms-native-settings-row em{justify-self:end;color:#5d7287;font-size:.78rem;font-style:normal;font-weight:850;line-height:1.2;text-align:right}',
      '.ms-native-settings-row.is-button{cursor:pointer}',
      '.ms-native-settings-row.is-button em{color:#95002e;font-size:1.4rem;font-weight:500}',
      '.ms-native-settings-row:disabled{cursor:not-allowed;opacity:.5}',
      '.ms-native-settings-switch{cursor:pointer}',
      '.ms-native-settings-switch input{position:absolute;opacity:0;pointer-events:none}',
      '.ms-native-settings-switch i{position:relative;flex:none;width:50px;height:30px;border-radius:999px;background:#cbd5e1;transition:background-color .16s ease}',
      '.ms-native-settings-switch i:after{content:"";position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:999px;background:#fff;box-shadow:0 3px 8px rgb(15 23 42 / .18);transition:transform .16s ease}',
      '.ms-native-settings-switch input:checked+i{background:#95002e}',
      '.ms-native-settings-switch input:checked+i:after{transform:translateX(20px)}',
      '.ms-native-settings-error{display:none;margin:0;padding:9px 11px;border:1px solid #fecaca;border-radius:8px;background:#fef2f2;color:#b91c1c;font-size:.86rem;font-weight:800;line-height:1.35}',
      '.ms-native-settings-error.is-visible{display:block}',
      '@media (max-width:380px){.ms-native-settings-header,.ms-native-settings-content{padding-right:18px;padding-left:18px}.ms-native-settings-methods{grid-template-columns:1fr}.ms-native-settings-heading{grid-template-columns:42px minmax(0,1fr)}.ms-native-settings-heading>.ms-native-settings-pill,.ms-native-settings-heading>.ms-native-settings-mini-action{grid-column:2;justify-self:start}}',
    ].join('');

    document.head.appendChild(style);
  }

  function ensurePanel() {
    installStyles();

    var panel = document.querySelector('[data-martin-sols-native-settings]');

    if (panel) {
      return panel;
    }

    var wrapper = document.createElement('div');
    wrapper.innerHTML = [
      '<div class="ms-native-settings" data-martin-sols-native-settings hidden>',
      '<section class="ms-native-settings-panel" role="dialog" aria-modal="true" aria-label="Paramètres de l app">',
      '<header class="ms-native-settings-header">',
      '<button class="ms-native-settings-back" type="button" data-ms-native-close aria-label="Fermer les paramètres de l app">' + icon('back') + '<span>Retour</span></button>',
      '<h2>Paramétrage de l’application</h2>',
      '</header>',
      '<div class="ms-native-settings-content">',
      '<section class="ms-native-settings-quick-card" aria-label="Autorisations rapides">',
      switchRow('Autoriser la localisation', 'data-ms-native-location-enabled'),
      switchRow('Activer la haute précision', 'data-ms-native-location-accuracy'),
      '</section>',
      card('is-red', 'shield', 'Sécurité de l’app', 'Déverrouillage par empreinte, visage ou code de l’appareil.', '<span class="ms-native-settings-pill" data-ms-native-auth-summary>À configurer</span>', securityMethods() + actionRow('Paramétrer la sécurité de l’appareil', 'Ouvrir l’écran de sécurité du téléphone', 'data-ms-native-device-security')),
      card('is-yellow', 'lock', 'Code app Martin Sols', '<span data-ms-native-app-code-detail>Créer un code app de 4 à 8 chiffres.</span>', '<button class="ms-native-settings-mini-action" type="button" data-ms-native-set-app-code><span data-ms-native-set-app-code-label>Définir</span></button>', staticRow('Protection par code app', 'Sécurise l’ouverture rapide si le téléphone n’a pas encore de verrouillage.', 'data-ms-native-app-code-status', 'Non défini') + actionRow('Supprimer le code app', 'Retirer le code local Martin Sols', 'data-ms-native-clear-app-code')),
      card('is-blue', 'fingerprint', 'Connexion rapide', 'Retrouver le CRM sans retaper le mot de passe.', '<span class="ms-native-settings-pill" data-ms-native-auth-section-status>Non configurée</span>', staticRow('Session enregistrée', 'État de la connexion rapide protégée.', 'data-ms-native-auth-status', 'Non configurée') + actionRow('Supprimer la connexion rapide', 'Effacer la session enregistrée sur cet appareil', 'data-ms-native-clear-auth')),
      card('is-green', 'location', 'Localisation terrain', 'Tester la position utilisée par les futurs modules CRM.', '<span class="ms-native-settings-pill" data-ms-native-location-status>Désactivée</span>', actionRow('Tester la localisation', 'Vérifier l’accès GPS de l’application', 'data-ms-native-test-location')),
      card('is-red', 'refresh', 'Mises à jour', '<span data-ms-native-update-status>Contrôle automatique actif</span>', '', actionRow('Rechercher une mise à jour', 'Vérifier la version disponible sur GitHub', 'data-ms-native-check-update')),
      card('is-blue', 'device', 'Informations', 'Version, plateforme et état réseau.', '', staticInfo('Version', 'data-ms-native-app-version', 'App mobile') + staticInfo('Plateforme', 'data-ms-native-platform', 'Android WebView') + staticInfo('Réseau', 'data-ms-native-network-status', 'En ligne') + staticInfo('Réglages à venir', '', 'Les futurs paramètres seront ajoutés ici', 'Prévu')),
      '<p class="ms-native-settings-error" data-ms-native-error></p>',
      '</div>',
      '</section>',
      '</div>',
    ].join('');

    document.body.appendChild(wrapper.firstChild);
    bindPanel();

    return document.querySelector('[data-martin-sols-native-settings]');
  }

  function switchRow(label, attr) {
    return '<label class="ms-native-settings-row ms-native-settings-switch"><span class="ms-native-settings-copy"><strong>' + label + '</strong></span><input ' + attr + ' type="checkbox"><i aria-hidden="true"></i></label>';
  }

  function card(colorClass, iconName, title, description, action, body) {
    return '<section class="ms-native-settings-card"><div class="ms-native-settings-heading"><span class="ms-native-settings-icon ' + colorClass + '">' + icon(iconName) + '</span><div><h3>' + title + '</h3><p>' + description + '</p></div>' + action + '</div>' + body + '</section>';
  }

  function securityMethods() {
    return '<div class="ms-native-settings-methods" aria-label="Méthodes de sécurité">' +
      method('fingerprint', 'Empreinte', 'data-ms-native-fingerprint-status') +
      method('face', 'Visage', 'data-ms-native-face-status') +
      method('key', 'Code appareil', 'data-ms-native-device-code-status') +
      '</div>';
  }

  function method(iconName, title, attr) {
    return '<div><span class="ms-native-settings-method-icon">' + icon(iconName) + '</span><strong>' + title + '</strong><small ' + attr + '>À configurer</small></div>';
  }

  function actionRow(title, description, attr) {
    return '<button class="ms-native-settings-row is-button" type="button" ' + attr + '><span class="ms-native-settings-copy"><strong>' + title + '</strong><small>' + description + '</small></span><em>›</em></button>';
  }

  function staticRow(title, description, attr, fallback) {
    return '<div class="ms-native-settings-row"><span class="ms-native-settings-copy"><strong>' + title + '</strong><small>' + description + '</small></span><em ' + attr + '>' + fallback + '</em></div>';
  }

  function staticInfo(title, attr, fallback, value) {
    var valueHtml = value ? '<em>' + value + '</em>' : '';
    var smallAttr = attr ? ' ' + attr : '';

    return '<div class="ms-native-settings-row"><span class="ms-native-settings-copy"><strong>' + title + '</strong><small' + smallAttr + '>' + fallback + '</small></span>' + valueHtml + '</div>';
  }

  function query(selector) {
    return document.querySelector(selector);
  }

  function setText(selector, value) {
    var element = query(selector);

    if (element) {
      element.textContent = value;
    }
  }

  function getAuthStatus() {
    try {
      var nativeBridge = bridge();

      if (!nativeBridge || !nativeBridge.getMobileAuthStatus) {
        return {};
      }

      return JSON.parse(nativeBridge.getMobileAuthStatus() || '{}');
    } catch (error) {
      return {};
    }
  }

  function authLabel(status) {
    if (status.hasSession && status.label) {
      return status.label;
    }

    if (status.available) {
      return status.label || 'Disponible';
    }

    return status.message || status.label || 'Non configurée';
  }

  function sessionLabel(status) {
    if (status.hasSession) {
      return 'Active';
    }

    if (status.available) {
      return 'À activer';
    }

    return status.label || 'Non configurée';
  }

  function locationLabel() {
    if (locationLoading) {
      return 'Recherche...';
    }

    if (lastLocation) {
      return [
        lastLocation.latitude.toFixed(5),
        lastLocation.longitude.toFixed(5),
        Math.round(lastLocation.accuracy) + ' m',
      ].join(' / ');
    }

    return settings.locationEnabled ? 'Activée' : 'Désactivée';
  }

  function renderPanel() {
    var locationEnabled = query('[data-ms-native-location-enabled]');
    var locationAccuracy = query('[data-ms-native-location-accuracy]');
    var nativeBridge = bridge();
    var status = getAuthStatus();
    var deviceReady = Boolean(status.deviceSecure);
    var hasAppCode = Boolean(status.appCodeConfigured);
    var hasQuickLogin = Boolean(status.hasSession);
    var methodLabel = status.available === false && status.label ? status.label : deviceReady ? 'Configuré' : 'À configurer';
    var summaryReady = hasQuickLogin || hasAppCode || deviceReady;
    var summary = summaryReady ? 'Protégé' : status.available === false && status.label ? status.label : 'À configurer';
    var authSummary = query('[data-ms-native-auth-summary]');
    var authSectionStatus = query('[data-ms-native-auth-section-status]');

    if (locationEnabled) {
      locationEnabled.checked = settings.locationEnabled;
    }

    if (locationAccuracy) {
      locationAccuracy.checked = settings.highAccuracyLocation;
    }

    setText('[data-ms-native-network-status]', navigator.onLine ? 'En ligne' : 'Hors ligne');
    setText('[data-ms-native-location-status]', locationLabel());
    setText('[data-ms-native-platform]', 'Android WebView');
    setText('[data-ms-native-app-version]', appVersionLabel());
    setText('[data-ms-native-auth-status]', authLabel(status));
    setText('[data-ms-native-app-code-status]', hasAppCode ? 'Défini' : 'Non défini');
    setText('[data-ms-native-app-code-detail]', hasAppCode ? 'Code app Martin Sols actif' : 'Créer un code app de 4 à 8 chiffres.');
    setText('[data-ms-native-fingerprint-status]', methodLabel);
    setText('[data-ms-native-face-status]', methodLabel);
    setText('[data-ms-native-device-code-status]', methodLabel);

    if (authSummary) {
      authSummary.textContent = summary;
      authSummary.classList.toggle('is-ready', summaryReady);
    }

    if (authSectionStatus) {
      authSectionStatus.textContent = sessionLabel(status);
      authSectionStatus.classList.toggle('is-ready', hasQuickLogin);
    }

    setButtonDisabled('[data-ms-native-test-location]', locationLoading || !settings.locationEnabled);
    setButtonDisabled('[data-ms-native-check-update]', !nativeBridge || !nativeBridge.checkForUpdates);
    setButtonDisabled('[data-ms-native-device-security]', !nativeBridge || !nativeBridge.openDeviceSecuritySettings);
    setButtonDisabled('[data-ms-native-set-app-code]', !nativeBridge || !nativeBridge.setAppCode);
    setButtonDisabled('[data-ms-native-clear-app-code]', !hasAppCode || !nativeBridge || !nativeBridge.clearAppCode);
    setButtonDisabled('[data-ms-native-clear-auth]', !hasQuickLogin || !nativeBridge || !nativeBridge.clearMobileSession);
    setText('[data-ms-native-set-app-code-label]', hasAppCode ? 'Modifier' : 'Définir');
  }

  function appVersionLabel() {
    var nativeBridge = bridge();

    if (!nativeBridge) {
      return 'App mobile';
    }

    try {
      return nativeBridge.getVersionName() + ' (' + nativeBridge.getVersionCode() + ')';
    } catch (error) {
      return 'App mobile';
    }
  }

  function setButtonDisabled(selector, disabled) {
    var button = query(selector);

    if (button) {
      button.disabled = Boolean(disabled);
    }
  }

  function showError(message) {
    var errorBox = query('[data-ms-native-error]');

    if (!errorBox) {
      return;
    }

    errorBox.textContent = message || '';
    errorBox.classList.toggle('is-visible', Boolean(message));
  }

  function requestLocation() {
    if (!navigator.geolocation) {
      showError('Localisation indisponible sur ce navigateur.');
      return;
    }

    locationLoading = true;
    showError('');
    renderPanel();

    navigator.geolocation.getCurrentPosition(function (position) {
      lastLocation = {
        accuracy: position.coords.accuracy,
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
      };
      locationLoading = false;
      renderPanel();
    }, function (error) {
      locationLoading = false;
      showError(error.message || 'Localisation indisponible.');
      renderPanel();
    }, {
      enableHighAccuracy: settings.highAccuracyLocation,
      maximumAge: 60000,
      timeout: 12000,
    });
  }

  function bindPanel() {
    var locationEnabled = query('[data-ms-native-location-enabled]');
    var locationAccuracy = query('[data-ms-native-location-accuracy]');
    var closeButton = query('[data-ms-native-close]');
    var testLocationButton = query('[data-ms-native-test-location]');
    var checkUpdateButton = query('[data-ms-native-check-update]');
    var deviceSecurityButton = query('[data-ms-native-device-security]');
    var setAppCodeButton = query('[data-ms-native-set-app-code]');
    var clearAppCodeButton = query('[data-ms-native-clear-app-code]');
    var clearAuthButton = query('[data-ms-native-clear-auth]');

    if (closeButton) {
      closeButton.addEventListener('click', closePanel);
    }

    if (testLocationButton) {
      testLocationButton.addEventListener('click', requestLocation);
    }

    if (checkUpdateButton) {
      checkUpdateButton.addEventListener('click', function () {
        var nativeBridge = bridge();

        showError('');

        if (nativeBridge && nativeBridge.checkForUpdates) {
          nativeBridge.checkForUpdates();
        }
      });
    }

    if (deviceSecurityButton) {
      deviceSecurityButton.addEventListener('click', function () {
        var nativeBridge = bridge();

        if (nativeBridge && nativeBridge.openDeviceSecuritySettings) {
          nativeBridge.openDeviceSecuritySettings();
        }
      });
    }

    if (setAppCodeButton) {
      setAppCodeButton.addEventListener('click', function () {
        var nativeBridge = bridge();

        if (nativeBridge && nativeBridge.setAppCode) {
          nativeBridge.setAppCode();
        }
      });
    }

    if (clearAppCodeButton) {
      clearAppCodeButton.addEventListener('click', function () {
        var nativeBridge = bridge();

        if (nativeBridge && nativeBridge.clearAppCode) {
          nativeBridge.clearAppCode();
        }

        showError('');
        renderPanel();
      });
    }

    if (clearAuthButton) {
      clearAuthButton.addEventListener('click', function () {
        var nativeBridge = bridge();

        if (nativeBridge && nativeBridge.clearMobileSession) {
          nativeBridge.clearMobileSession();
        }

        showError('');
        renderPanel();
      });
    }

    if (locationEnabled) {
      locationEnabled.addEventListener('change', function () {
        settings.locationEnabled = Boolean(locationEnabled.checked);
        writeSettings();
        showError('');

        if (settings.locationEnabled) {
          requestLocation();
          return;
        }

        lastLocation = null;
        renderPanel();
      });
    }

    if (locationAccuracy) {
      locationAccuracy.addEventListener('change', function () {
        settings.highAccuracyLocation = Boolean(locationAccuracy.checked);
        writeSettings();
        showError('');
        renderPanel();
      });
    }
  }

  function openPanel() {
    var panel = ensurePanel();

    if (!panel) {
      return;
    }

    panel.hidden = false;
    document.body.classList.add('ms-native-settings-open');
    showError('');
    renderPanel();
  }

  function closePanel() {
    var panel = query('[data-martin-sols-native-settings]');

    if (panel) {
      panel.hidden = true;
    }

    document.body.classList.remove('ms-native-settings-open');
  }

  document.addEventListener('click', function (event) {
    var target = event.target;

    if (!target || !target.closest) {
      return;
    }

    if (!target.closest('[data-crm-mobile-settings-toggle]')) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    openPanel();
  }, true);

  document.addEventListener('keydown', function (event) {
    var panel = query('[data-martin-sols-native-settings]');

    if (event.key === 'Escape' && panel && !panel.hidden) {
      closePanel();
    }
  });

  window.addEventListener('online', renderPanel);
  window.addEventListener('offline', renderPanel);
  window.addEventListener('martin-sols:native-auth-status-changed', renderPanel);
})();
