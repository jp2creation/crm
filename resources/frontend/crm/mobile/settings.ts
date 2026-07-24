type MobileLocation = {
  accuracy: number;
  latitude: number;
  longitude: number;
  updatedAt: string;
};

type MobileSettings = {
  highAccuracyLocation: boolean;
  locationEnabled: boolean;
};

type MobileAuthStatus = {
  appCodeConfigured?: boolean;
  available?: boolean;
  configured?: boolean;
  deviceSecure?: boolean;
  hasSession?: boolean;
  label?: string;
  message?: string;
  ok?: boolean;
};

type NativeAppBridge = {
  clearMobileSession?: () => void;
  authenticateSavedMobileSession?: (requestId: string) => void;
  clearAppCode?: () => void;
  checkForUpdates?: () => void;
  getMobileAuthStatus?: () => string;
  getVersionCode?: () => string;
  getVersionName?: () => string;
  openDeviceSecuritySettings?: () => void;
  saveMobileSession?: (payload: string) => string;
  setAppCode?: () => void;
};

const storageKey = 'martin-sols.crm.mobile-app.settings';
let settingsInstalled = false;

function readSettings(): MobileSettings {
  try {
    const stored = JSON.parse(localStorage.getItem(storageKey) || '{}') as Partial<MobileSettings>;

    return {
      highAccuracyLocation: Boolean(stored.highAccuracyLocation),
      locationEnabled: Boolean(stored.locationEnabled),
    };
  } catch {
    return {
      highAccuracyLocation: false,
      locationEnabled: false,
    };
  }
}

function dispatchLocation(settings: MobileSettings, lastLocation: MobileLocation | null): void {
  window.dispatchEvent(
    new CustomEvent('martin-sols:mobile-location', {
      detail: {
        enabled: settings.locationEnabled,
        last: lastLocation,
      },
    }),
  );
}

function isMobileApp(): boolean {
  return (
    window.MartinSolsCrmConfig?.mobile.app === true ||
    Boolean(window.MartinSolsNativeApp) ||
    document.body.classList.contains('crm-mobile-app')
  );
}

function nativeBridge(): NativeAppBridge | undefined {
  return window.MartinSolsNativeApp;
}

function appVersionLabel(): string {
  try {
    const versionName = nativeBridge()?.getVersionName?.() || '';
    const versionCode = nativeBridge()?.getVersionCode?.() || '';

    if (versionName && versionCode) {
      return `${versionName} (${versionCode})`;
    }

    return versionName || 'App mobile';
  } catch {
    return 'App mobile';
  }
}

function mobileAuthStatus(): MobileAuthStatus {
  try {
    const rawStatus = nativeBridge()?.getMobileAuthStatus?.();

    return rawStatus ? (JSON.parse(rawStatus) as MobileAuthStatus) : {};
  } catch {
    return {};
  }
}

function mobileAuthLabel(status: MobileAuthStatus): string {
  if (status.hasSession && status.label) {
    return status.label;
  }

  if (status.available) {
    return status.label || 'Disponible';
  }

  return status.message || 'Non configurée';
}

function mobileAuthSessionLabel(status: MobileAuthStatus): string {
  if (status.hasSession) {
    return 'Active';
  }

  if (status.available) {
    return 'À activer au prochain login';
  }

  return 'Non configurée';
}

function mountSettingsMarkup(): boolean {
  if (!isMobileApp()) {
    return false;
  }

  if (document.querySelector('[data-crm-mobile-settings]')) {
    return true;
  }

  const wrapper = document.createElement('div');

  wrapper.innerHTML = `
    <div class="crm-mobile-app-settings" data-crm-mobile-settings hidden>
      <section class="crm-mobile-app-settings-panel" role="dialog" aria-modal="true" aria-label="Paramètres de l'app">
        <header class="crm-mobile-app-settings-header">
          <button class="crm-mobile-app-settings-back" type="button" data-crm-mobile-settings-close aria-label="Fermer les paramètres de l'app">
            <span aria-hidden="true">&larr;</span>
          </button>
          <div>
            <p>Application mobile</p>
            <h2>Paramètres de l’app</h2>
          </div>
        </header>

        <div class="crm-mobile-app-settings-content">
          <div class="crm-mobile-app-settings-switches" aria-label="Préférences">
            <label class="crm-mobile-app-settings-switch">
              <span>Localisation</span>
              <input data-crm-mobile-location-enabled type="checkbox">
              <i aria-hidden="true"></i>
            </label>
            <label class="crm-mobile-app-settings-switch">
              <span>Haute précision</span>
              <input data-crm-mobile-location-accuracy type="checkbox">
              <i aria-hidden="true"></i>
            </label>
          </div>

          <div class="crm-mobile-app-settings-status">
            <div>
              <span>Réseau</span>
              <strong data-crm-mobile-network-status>En ligne</strong>
            </div>
            <div>
              <span>Localisation</span>
              <strong data-crm-mobile-location-status>Désactivée</strong>
            </div>
            <div>
              <span>Version</span>
              <strong data-crm-mobile-app-version>App mobile</strong>
            </div>
          <div>
            <span>WebView</span>
            <strong data-crm-mobile-platform>Android</strong>
          </div>
          <div>
            <span>Connexion rapide</span>
            <strong data-crm-mobile-auth-status>Non configurée</strong>
          </div>
          <div>
            <span>Code app</span>
            <strong data-crm-mobile-app-code-status>Non défini</strong>
          </div>
        </div>

          <div class="crm-mobile-app-settings-section">
            <div class="crm-mobile-app-settings-section-head">
              <span>Mises à jour</span>
              <strong data-crm-mobile-update-status>Contrôle automatique actif</strong>
            </div>
            <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-check-update>Rechercher une mise à jour</button>
          </div>

          <div class="crm-mobile-app-settings-section">
            <div class="crm-mobile-app-settings-section-head">
              <span>Sécurité</span>
              <strong data-crm-mobile-auth-section-status>Non configurée</strong>
            </div>
            <div class="crm-mobile-app-settings-button-grid">
              <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-device-security>Empreinte / visage / code Android</button>
              <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-set-app-code>Définir un code app</button>
              <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-clear-app-code>Supprimer le code app</button>
              <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-clear-auth>Supprimer la connexion rapide</button>
            </div>
          </div>

          <p class="crm-mobile-app-settings-error" data-crm-mobile-settings-error></p>

          <div class="crm-mobile-app-settings-actions">
            <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-test-location>Tester localisation</button>
            <button class="crm-mobile-app-settings-primary" type="button" data-crm-mobile-settings-close>Fermer</button>
          </div>
        </div>
      </section>
    </div>
  `;

  document.body.append(...Array.from(wrapper.childNodes));

  return true;
}

export function installMobileAppSettings(): void {
  if (!mountSettingsMarkup()) {
    return;
  }

  const modal = document.querySelector<HTMLElement>('[data-crm-mobile-settings]');
  const closeButtons = document.querySelectorAll<HTMLButtonElement>('[data-crm-mobile-settings-close]');
  const locationEnabled = document.querySelector<HTMLInputElement>('[data-crm-mobile-location-enabled]');
  const locationAccuracy = document.querySelector<HTMLInputElement>('[data-crm-mobile-location-accuracy]');
  const networkStatus = document.querySelector<HTMLElement>('[data-crm-mobile-network-status]');
  const locationStatus = document.querySelector<HTMLElement>('[data-crm-mobile-location-status]');
  const platformStatus = document.querySelector<HTMLElement>('[data-crm-mobile-platform]');
  const appVersion = document.querySelector<HTMLElement>('[data-crm-mobile-app-version]');
  const authStatus = document.querySelector<HTMLElement>('[data-crm-mobile-auth-status]');
  const appCodeStatus = document.querySelector<HTMLElement>('[data-crm-mobile-app-code-status]');
  const authSectionStatus = document.querySelector<HTMLElement>('[data-crm-mobile-auth-section-status]');
  const updateStatus = document.querySelector<HTMLElement>('[data-crm-mobile-update-status]');
  const errorBox = document.querySelector<HTMLElement>('[data-crm-mobile-settings-error]');
  const testLocation = document.querySelector<HTMLButtonElement>('[data-crm-mobile-test-location]');
  const checkUpdate = document.querySelector<HTMLButtonElement>('[data-crm-mobile-check-update]');
  const clearAuth = document.querySelector<HTMLButtonElement>('[data-crm-mobile-clear-auth]');
  const deviceSecurity = document.querySelector<HTMLButtonElement>('[data-crm-mobile-device-security]');
  const setAppCode = document.querySelector<HTMLButtonElement>('[data-crm-mobile-set-app-code]');
  const clearAppCode = document.querySelector<HTMLButtonElement>('[data-crm-mobile-clear-app-code]');

  if (!modal || !locationEnabled || !locationAccuracy) {
    return;
  }

  let lastLocation: MobileLocation | null = null;
  let locationLoading = false;
  const settings = readSettings();

  const writeSettings = () => {
    localStorage.setItem(storageKey, JSON.stringify(settings));
  };

  const showError = (message: string) => {
    if (!errorBox) {
      return;
    }

    errorBox.textContent = message || '';
    errorBox.classList.toggle('is-visible', Boolean(message));
  };

  const locationLabel = () => {
    if (locationLoading) {
      return 'Recherche...';
    }

    if (lastLocation) {
      return [
        lastLocation.latitude.toFixed(5),
        lastLocation.longitude.toFixed(5),
        `${Math.round(lastLocation.accuracy)} m`,
      ].join(' / ');
    }

    return settings.locationEnabled ? 'Activée' : 'Désactivée';
  };

  const renderSettings = () => {
    locationEnabled.checked = settings.locationEnabled;
    locationAccuracy.checked = settings.highAccuracyLocation;

    if (networkStatus) {
      networkStatus.textContent = navigator.onLine ? 'En ligne' : 'Hors ligne';
    }

    if (locationStatus) {
      locationStatus.textContent = locationLabel();
    }

    if (platformStatus) {
      platformStatus.textContent = navigator.userAgent;
    }

    if (appVersion) {
      appVersion.textContent = appVersionLabel();
    }

    const currentAuthStatus = mobileAuthStatus();

    if (authStatus) {
      authStatus.textContent = mobileAuthLabel(currentAuthStatus);
    }

    if (appCodeStatus) {
      appCodeStatus.textContent = currentAuthStatus.appCodeConfigured ? 'Défini' : 'Non défini';
    }

    if (authSectionStatus) {
      authSectionStatus.textContent = mobileAuthSessionLabel(currentAuthStatus);
    }

    if (testLocation) {
      testLocation.disabled = locationLoading || !settings.locationEnabled;
    }

    if (clearAuth) {
      clearAuth.disabled = !currentAuthStatus.hasSession;
    }

    if (deviceSecurity) {
      deviceSecurity.disabled = !nativeBridge()?.openDeviceSecuritySettings;
    }

    if (setAppCode) {
      setAppCode.disabled = !nativeBridge()?.setAppCode;
      setAppCode.textContent = currentAuthStatus.appCodeConfigured ? 'Modifier le code app' : 'Définir un code app';
    }

    if (clearAppCode) {
      clearAppCode.disabled = !currentAuthStatus.appCodeConfigured || !nativeBridge()?.clearAppCode;
    }
  };

  const publishLocation = () => dispatchLocation(settings, lastLocation);

  const requestLocation = () => {
    if (!navigator.geolocation) {
      showError('Localisation indisponible sur ce navigateur.');
      return;
    }

    locationLoading = true;
    showError('');
    renderSettings();

    navigator.geolocation.getCurrentPosition(
      (position) => {
        lastLocation = {
          accuracy: position.coords.accuracy,
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
          updatedAt: new Date(position.timestamp).toISOString(),
        };
        locationLoading = false;
        publishLocation();
        renderSettings();
      },
      (error) => {
        locationLoading = false;
        showError(error.message || 'Localisation indisponible.');
        renderSettings();
      },
      {
        enableHighAccuracy: settings.highAccuracyLocation,
        maximumAge: 60000,
        timeout: 12000,
      },
    );
  };

  const openSettings = () => {
    modal.hidden = false;
    document.body.classList.add('crm-mobile-app-settings-open');
    showError('');
    renderSettings();
  };

  const closeSettings = () => {
    modal.hidden = true;
    document.body.classList.remove('crm-mobile-app-settings-open');
  };

  const requestUpdateCheck = () => {
    showError('');

    if (!nativeBridge()?.checkForUpdates) {
      showError('Recherche de mise à jour disponible uniquement dans l’app installée.');
      return;
    }

    if (updateStatus) {
      updateStatus.textContent = 'Recherche lancée...';
    }

    nativeBridge()?.checkForUpdates?.();
  };

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeSettings);
  });

  locationEnabled.addEventListener('change', () => {
    settings.locationEnabled = locationEnabled.checked;
    writeSettings();
    showError('');
    renderSettings();

    if (settings.locationEnabled) {
      requestLocation();
      return;
    }

    lastLocation = null;
    publishLocation();
  });

  locationAccuracy.addEventListener('change', () => {
    settings.highAccuracyLocation = locationAccuracy.checked;
    writeSettings();
    showError('');
    renderSettings();
  });

  testLocation?.addEventListener('click', requestLocation);
  checkUpdate?.addEventListener('click', requestUpdateCheck);
  deviceSecurity?.addEventListener('click', () => {
    nativeBridge()?.openDeviceSecuritySettings?.();
  });
  setAppCode?.addEventListener('click', () => {
    nativeBridge()?.setAppCode?.();
  });
  clearAppCode?.addEventListener('click', () => {
    nativeBridge()?.clearAppCode?.();
    showError('');
    renderSettings();
  });
  clearAuth?.addEventListener('click', () => {
    nativeBridge()?.clearMobileSession?.();
    showError('');
    renderSettings();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || modal.hidden) {
      return;
    }

    event.preventDefault();
    closeSettings();
  });

  window.addEventListener('online', renderSettings);
  window.addEventListener('offline', renderSettings);
  window.addEventListener('martin-sols:native-auth-status-changed', renderSettings);

  if (!settingsInstalled) {
    settingsInstalled = true;

    document.addEventListener(
      'click',
      (event) => {
        const target = event.target instanceof Element ? event.target : null;

        if (!target?.closest('[data-crm-mobile-settings-toggle]')) {
          return;
        }

        event.preventDefault();
        openSettings();
      },
      true,
    );
  }

  window.MartinSolsMobileApp = {
    checkForUpdates: requestUpdateCheck,
    openSettings,
    requestLocation,
  };

  renderSettings();
}
