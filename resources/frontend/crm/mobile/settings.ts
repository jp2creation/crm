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

export function installMobileAppSettings(): void {
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
