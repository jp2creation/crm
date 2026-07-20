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

const storageKey = 'martin-sols.crm.mobile-app.settings';

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

function mountSettingsMarkup(): void {
  if (!document.body.classList.contains('crm-mobile-app')) {
    return;
  }

  if (document.querySelector('[data-crm-mobile-settings]')) {
    return;
  }

  const wrapper = document.createElement('div');

  wrapper.innerHTML = `
    <button class="crm-mobile-app-settings-trigger" type="button" data-crm-mobile-settings-toggle aria-label="Paramètres de l'app">
      <span></span><span></span><span></span>
    </button>
    <div class="crm-mobile-app-settings" data-crm-mobile-settings hidden>
      <button class="crm-mobile-app-settings-backdrop" type="button" data-crm-mobile-settings-close aria-label="Fermer"></button>
      <section class="crm-mobile-app-settings-panel" role="dialog" aria-modal="true" aria-label="Paramètres de l'app">
        <div class="crm-mobile-app-settings-header">
          <div>
            <p>Application</p>
            <h2>Paramètres</h2>
          </div>
          <button class="crm-mobile-app-settings-close" type="button" data-crm-mobile-settings-close aria-label="Fermer">&times;</button>
        </div>

        <div class="crm-mobile-app-settings-switches">
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
            <strong>App mobile</strong>
          </div>
          <div>
            <span>WebView</span>
            <strong data-crm-mobile-platform>Android</strong>
          </div>
        </div>

        <p class="crm-mobile-app-settings-error" data-crm-mobile-settings-error></p>

        <div class="crm-mobile-app-settings-actions">
          <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-test-location>Tester localisation</button>
          <button class="crm-mobile-app-settings-primary" type="button" data-crm-mobile-settings-close>Fermer</button>
        </div>
      </section>
    </div>
  `;

  document.body.append(...Array.from(wrapper.childNodes));
}

export function installMobileAppSettings(): void {
  mountSettingsMarkup();

  const modal = document.querySelector<HTMLElement>('[data-crm-mobile-settings]');
  const toggle = document.querySelector<HTMLButtonElement>('[data-crm-mobile-settings-toggle]');
  const closeButtons = document.querySelectorAll<HTMLButtonElement>('[data-crm-mobile-settings-close]');
  const locationEnabled = document.querySelector<HTMLInputElement>('[data-crm-mobile-location-enabled]');
  const locationAccuracy = document.querySelector<HTMLInputElement>('[data-crm-mobile-location-accuracy]');
  const networkStatus = document.querySelector<HTMLElement>('[data-crm-mobile-network-status]');
  const locationStatus = document.querySelector<HTMLElement>('[data-crm-mobile-location-status]');
  const platformStatus = document.querySelector<HTMLElement>('[data-crm-mobile-platform]');
  const errorBox = document.querySelector<HTMLElement>('[data-crm-mobile-settings-error]');
  const testLocation = document.querySelector<HTMLButtonElement>('[data-crm-mobile-test-location]');

  if (!modal || !toggle || !locationEnabled || !locationAccuracy) {
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

    if (testLocation) {
      testLocation.disabled = locationLoading || !settings.locationEnabled;
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

  toggle.addEventListener('click', () => {
    modal.hidden = false;
    renderSettings();
  });

  closeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      modal.hidden = true;
    });
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

  window.addEventListener('online', renderSettings);
  window.addEventListener('offline', renderSettings);

  renderSettings();
}
