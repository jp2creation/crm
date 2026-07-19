import './styles.css';

import { App as CapacitorApp, type AppInfo } from '@capacitor/app';
import { Capacitor, CapacitorHttp, type HttpHeaders, type PermissionState } from '@capacitor/core';
import { Device, type DeviceInfo } from '@capacitor/device';
import { Geolocation, type PermissionStatus as GeolocationPermissionStatus } from '@capacitor/geolocation';
import { Keyboard, KeyboardResize, KeyboardStyle } from '@capacitor/keyboard';
import { Network, type ConnectionStatus } from '@capacitor/network';
import { Preferences } from '@capacitor/preferences';
import { StatusBar, Style } from '@capacitor/status-bar';

type MobileSite = {
  id: number;
  name: string;
  slug?: string;
  isDefault?: boolean;
};

type MobileModule = {
  id: number;
  name: string;
  slug: string;
  routePath?: string;
};

type MobileMenuItem = {
  key: string;
  groupKey: string;
  iconKey?: string;
  label: string;
  slug: string;
  routePath?: string;
};

type MobileMenuGroup = {
  key: string;
  title: string;
  items: MobileMenuItem[];
};

type MobileUser = {
  id: number;
  name: string;
  email?: string;
  role: string;
  photoUrl?: string | null;
  sites?: MobileSite[];
  defaultSiteId?: number | null;
  siteIds?: number[];
  modules: MobileModule[];
  menu?: MobileMenuGroup[];
  permissions: string[];
};

type LoginResult = {
  ok: boolean;
  token?: string;
  user?: MobileUser;
  error?: string;
};

type MeResult = {
  ok: boolean;
  user?: MobileUser;
  error?: string;
};

type WebSessionResult = {
  ok: boolean;
  url?: string;
  error?: string;
};

type AppSettings = {
  crmUrl: string;
  locationEnabled: boolean;
  highAccuracyLocation: boolean;
  rememberLastModule: boolean;
  showBrowserControls: boolean;
};

type LocationSnapshot = {
  latitude: number;
  longitude: number;
  accuracy: number;
  updatedAt: string;
};

type LocationState = {
  permission: PermissionState | 'unknown';
  coarsePermission: PermissionState | 'unknown';
  loading: boolean;
  error: string;
  last: LocationSnapshot | null;
};

type BrowserEntry = {
  routePath: string;
  label: string;
  external: boolean;
  pushedAt: number;
};

type FrameMessage = {
  source?: string;
  type?: string;
  payload?: {
    path?: string;
    url?: string;
    title?: string;
  };
};

type AppState = {
  apiBase: string;
  settings: AppSettings;
  token: string;
  user: MobileUser | null;
  selectedSiteId: number | null;
  activeSlug: string;
  frameUrl: string;
  menuOpen: boolean;
  settingsOpen: boolean;
  settingsSaving: boolean;
  settingsError: string;
  loading: boolean;
  moduleLoading: boolean;
  startup: boolean;
  error: string;
  browserHistory: BrowserEntry[];
  browserIndex: number;
  appInfo: AppInfo | null;
  deviceInfo: DeviceInfo | null;
  network: ConnectionStatus;
  location: LocationState;
};

const appRoot = document.querySelector<HTMLDivElement>('#app');

if (!appRoot) {
  throw new Error('App root missing');
}

const app: HTMLDivElement = appRoot;
const defaultApiBase = normalizeUrl(import.meta.env.VITE_CRM_URL || import.meta.env.VITE_API_BASE_URL || 'https://crm.jp2.fr');
const defaultSettings = createDefaultSettings(defaultApiBase);

const storage = {
  token: 'martin-sols.mobile.token',
  user: 'martin-sols.mobile.user',
  siteId: 'martin-sols.mobile.siteId',
  activeSlug: 'martin-sols.mobile.activeSlug',
  settings: 'martin-sols.mobile.settings',
  initialPermissionsPrompted: 'martin-sols.mobile.initialPermissionsPrompted',
};

const state: AppState = {
  apiBase: defaultApiBase,
  settings: defaultSettings,
  token: localStorage.getItem(storage.token) || '',
  user: readStoredUser(),
  selectedSiteId: readStoredNumber(storage.siteId),
  activeSlug: localStorage.getItem(storage.activeSlug) || '',
  frameUrl: '',
  menuOpen: false,
  settingsOpen: false,
  settingsSaving: false,
  settingsError: '',
  loading: false,
  moduleLoading: false,
  startup: true,
  error: '',
  browserHistory: [],
  browserIndex: -1,
  appInfo: null,
  deviceInfo: null,
  network: fallbackNetworkStatus(),
  location: {
    permission: 'unknown',
    coarsePermission: 'unknown',
    loading: false,
    error: '',
    last: null,
  },
};

window.addEventListener('message', handleFrameMessage);
void boot();

async function boot(): Promise<void> {
  renderStartup();

  await configurePreferences();
  await loadSettings();
  await setupNativeRuntime();

  const minimumStartup = delay(isNativeApp() ? 980 : 640);
  let shouldOpenCrm = false;

  if (!state.token) {
    await minimumStartup;
    state.startup = false;
    render();
    return;
  }

  renderLoadingSession();

  try {
    const result = await apiFetch<MeResult>('/api/mobile/me');

    if (!result.ok || !result.user) {
      clearSession();
    } else {
      state.user = result.user;
      persistUser(result.user);
      ensureSelection();
      shouldOpenCrm = true;
    }
  } catch {
    state.error = 'Impossible de verifier la session mobile.';
    clearSession();
  }

  await minimumStartup;
  state.startup = false;

  if (shouldOpenCrm) {
    const opened = await openCrmWebApp();

    if (opened) {
      return;
    }
  }

  render();
}

async function configurePreferences(): Promise<void> {
  try {
    await Preferences.configure({ group: 'martin-sols-mobile' });
  } catch {
    // Preferences still work on the web fallback with localStorage.
  }
}

async function loadSettings(): Promise<void> {
  const raw = await readPreference(storage.settings);
  const settings = parseStoredSettings(raw);

  state.settings = settings;
  state.apiBase = settings.crmUrl;
}

async function setupNativeRuntime(): Promise<void> {
  await Promise.all([
    loadAppInfo(),
    loadDeviceInfo(),
    loadNetworkStatus(),
    syncLocationPermission(),
  ]);

  if (isNativeApp()) {
    void StatusBar.setStyle({ style: Style.Light }).catch(() => undefined);
    void StatusBar.setBackgroundColor({ color: '#f4f4f5' }).catch(() => undefined);
    void StatusBar.setOverlaysWebView({ overlay: false }).catch(() => undefined);
    void Keyboard.setResizeMode({ mode: KeyboardResize.Body }).catch(() => undefined);
    void Keyboard.setStyle({ style: KeyboardStyle.Light }).catch(() => undefined);
    void CapacitorApp.addListener('backButton', () => {
      if (state.settingsOpen) {
        closeSettings();
        return;
      }

      if (state.menuOpen) {
        state.menuOpen = false;
        renderShell();
        return;
      }

      if (canGoBack()) {
        void navigateBrowserHistory(-1);
        return;
      }

      void CapacitorApp.minimizeApp().catch(() => CapacitorApp.exitApp());
    }).catch(() => undefined);
  }

  void Network.addListener('networkStatusChange', (status) => {
    state.network = status;
    updateBrowserChrome();
  }).catch(() => undefined);

  window.addEventListener('online', () => {
    state.network = fallbackNetworkStatus();
    updateBrowserChrome();
  });

  window.addEventListener('offline', () => {
    state.network = fallbackNetworkStatus();
    updateBrowserChrome();
  });

  if (state.settings.locationEnabled && hasLocationGrant()) {
    void refreshLocation('silent');
  }

  await requestInitialAppPermissions();
}

async function loadAppInfo(): Promise<void> {
  try {
    state.appInfo = await CapacitorApp.getInfo();
  } catch {
    state.appInfo = null;
  }
}

async function loadDeviceInfo(): Promise<void> {
  try {
    state.deviceInfo = await Device.getInfo();
  } catch {
    state.deviceInfo = null;
  }
}

async function loadNetworkStatus(): Promise<void> {
  try {
    state.network = await Network.getStatus();
  } catch {
    state.network = fallbackNetworkStatus();
  }
}

async function syncLocationPermission(): Promise<void> {
  try {
    applyLocationPermissions(await Geolocation.checkPermissions());
  } catch {
    state.location.permission = 'unknown';
    state.location.coarsePermission = 'unknown';
  }
}

function applyLocationPermissions(status: GeolocationPermissionStatus): void {
  state.location.permission = status.location;
  state.location.coarsePermission = status.coarseLocation;
}

async function requestInitialAppPermissions(): Promise<void> {
  if (!isNativeApp()) {
    return;
  }

  const alreadyPrompted = await readPreference(storage.initialPermissionsPrompted);

  if (alreadyPrompted === '1') {
    return;
  }

  try {
    let permissions = await Geolocation.checkPermissions();

    if (!permissionStatusAllowsLocation(permissions)) {
      permissions = await Geolocation.requestPermissions({
        permissions: ['location', 'coarseLocation'],
      });
    }

    applyLocationPermissions(permissions);

    if (permissionStatusAllowsLocation(permissions)) {
      state.settings = {
        ...state.settings,
        locationEnabled: true,
        highAccuracyLocation: permissions.location === 'granted' || state.settings.highAccuracyLocation,
      };
      await persistSettings();
      void refreshLocation('silent', state.settings.highAccuracyLocation, false);
    }
  } catch {
    // The user can still enable location later from the app settings.
  } finally {
    await writePreference(storage.initialPermissionsPrompted, '1');
  }
}

function readStoredUser(): MobileUser | null {
  const raw = localStorage.getItem(storage.user);

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw) as MobileUser;
  } catch {
    localStorage.removeItem(storage.user);
    return null;
  }
}

function readStoredNumber(key: string): number | null {
  const raw = localStorage.getItem(key);
  const value = raw ? Number(raw) : NaN;

  return Number.isFinite(value) && value > 0 ? value : null;
}

function persistSession(result: LoginResult): void {
  if (!result.token || !result.user) {
    return;
  }

  state.token = result.token;
  state.user = result.user;
  state.error = '';
  state.frameUrl = '';
  state.browserHistory = [];
  state.browserIndex = -1;
  localStorage.setItem(storage.token, result.token);
  persistUser(result.user);
  ensureSelection();
}

function persistUser(user: MobileUser): void {
  localStorage.setItem(storage.user, JSON.stringify(user));
}

function clearSession(): void {
  state.token = '';
  state.user = null;
  state.frameUrl = '';
  state.activeSlug = '';
  state.selectedSiteId = null;
  state.browserHistory = [];
  state.browserIndex = -1;
  localStorage.removeItem(storage.token);
  localStorage.removeItem(storage.user);
  localStorage.removeItem(storage.siteId);
  localStorage.removeItem(storage.activeSlug);
}

function createDefaultSettings(apiBase: string): AppSettings {
  return {
    crmUrl: apiBase,
    locationEnabled: false,
    highAccuracyLocation: false,
    rememberLastModule: true,
    showBrowserControls: true,
  };
}

function parseStoredSettings(raw: string | null): AppSettings {
  if (!raw) {
    return defaultSettings;
  }

  try {
    const value = JSON.parse(raw) as Partial<AppSettings>;

    return {
      ...defaultSettings,
      ...value,
      crmUrl: normalizeUrl(value.crmUrl || defaultSettings.crmUrl),
      locationEnabled: Boolean(value.locationEnabled),
      highAccuracyLocation: Boolean(value.highAccuracyLocation),
      rememberLastModule: value.rememberLastModule !== false,
      showBrowserControls: value.showBrowserControls !== false,
    };
  } catch {
    return defaultSettings;
  }
}

async function persistSettings(): Promise<void> {
  await writePreference(storage.settings, JSON.stringify(state.settings));
}

async function readPreference(key: string): Promise<string | null> {
  try {
    const result = await Preferences.get({ key });

    if (result.value !== null) {
      return result.value;
    }
  } catch {
    // Fall through to localStorage.
  }

  return localStorage.getItem(key);
}

async function writePreference(key: string, value: string): Promise<void> {
  localStorage.setItem(key, value);

  try {
    await Preferences.set({ key, value });
  } catch {
    // localStorage keeps the web fallback working.
  }
}

function normalizeUrl(value: string): string {
  let trimmed = value.trim();

  if (!trimmed) {
    return 'https://crm.jp2.fr';
  }

  if (!/^https?:\/\//i.test(trimmed)) {
    trimmed = `https://${trimmed}`;
  }

  try {
    const url = new URL(trimmed);

    return url.origin;
  } catch {
    return 'https://crm.jp2.fr';
  }
}

function render(): void {
  if (state.startup) {
    renderStartup();
    return;
  }

  if (!state.token || !state.user) {
    renderLogin();
    return;
  }

  renderLoadingSession();
}

function renderStartup(): void {
  app.innerHTML = `
    <main class="startup-screen">
      ${brandMorphLoaderMarkup(true)}
      <div class="startup-copy" aria-hidden="true">
        <img src="${escapeHtml(logoUrl())}" alt="">
        <span>Martin Sols</span>
      </div>
    </main>
  `;
}

function renderLoadingSession(): void {
  app.innerHTML = `
    <main class="session-loading-screen">
      ${brandMorphLoaderMarkup(true)}
    </main>
  `;
}

function renderLogin(): void {
  app.innerHTML = `
    <main class="auth-screen">
      <button class="auth-settings-button" type="button" data-open-settings aria-label="Parametres de l'app">
        <span></span><span></span><span></span>
      </button>

      <section class="auth-layout">
        <div class="auth-brand-card auth-logo-card">
          <img class="auth-logo" src="${escapeHtml(logoUrl())}" alt="Martin Sols">
        </div>

        <form id="login-form" class="auth-panel">
          <div class="form-header">
            <p class="eyebrow">Connexion</p>
            <h2>Identifiez-vous</h2>
          </div>

          <label>
            <span>Email</span>
            <input name="email" type="email" autocomplete="email" required>
          </label>

          <label>
            <span>Mot de passe</span>
            <input name="password" type="password" autocomplete="current-password" required>
          </label>

          ${state.error ? `<p class="form-error">${escapeHtml(state.error)}</p>` : ''}

          <button class="primary-button" type="submit" ${state.loading ? 'disabled' : ''}>
            ${state.loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>
      </section>

      ${renderSettingsModal()}
    </main>
  `;

  app.querySelector<HTMLFormElement>('#login-form')?.addEventListener('submit', (event) => {
    event.preventDefault();
    void handleLogin(event.currentTarget as HTMLFormElement);
  });

  bindSettingsEvents();
}

async function handleLogin(form: HTMLFormElement): Promise<void> {
  const formData = new FormData(form);
  let openedCrm = false;

  state.loading = true;
  state.error = '';
  renderLogin();

  try {
    const result = await apiFetch<LoginResult>('/api/mobile/token', {
      method: 'POST',
      body: JSON.stringify({
        email: String(formData.get('email') || ''),
        password: String(formData.get('password') || ''),
        device_name: defaultDeviceName(),
      }),
    });

    if (!result.ok || !result.token || !result.user) {
      state.error = result.error || 'Connexion impossible.';
      return;
    }

    persistSession(result);
    openedCrm = await openCrmWebApp();
  } catch {
    state.error = state.network.connected ? 'Impossible de contacter le CRM.' : 'Connexion reseau indisponible.';
  } finally {
    state.loading = false;

    if (!openedCrm) {
      render();
    }
  }
}

async function openCrmWebApp(): Promise<boolean> {
  state.loading = true;
  state.moduleLoading = true;
  state.error = '';
  renderLoadingSession();

  try {
    const result = await apiFetch<WebSessionResult>('/api/mobile/web-session', {
      method: 'POST',
      body: JSON.stringify({
        redirectPath: '/dashboard/crm?mobile_app=1',
        siteId: state.selectedSiteId,
        embed: false,
      }),
    });

    if (!result.ok || !result.url) {
      state.error = result.error || 'Impossible d ouvrir le CRM.';
      state.moduleLoading = false;
      clearSession();
      return false;
    }

    window.location.replace(result.url);
    return true;
  } catch {
    state.error = state.network.connected ? 'Impossible de preparer la session CRM.' : 'Connexion reseau indisponible.';
    state.moduleLoading = false;
    clearSession();
    return false;
  } finally {
    state.loading = false;
  }
}

function renderWebViewShell(): void {
  app.innerHTML = `
    <div class="crm-webview-shell">
      <iframe
        class="crm-webview-frame"
        src="${escapeHtml(state.frameUrl)}"
        title="CRM Martin Sols"
        allow="geolocation; camera; microphone; clipboard-read; clipboard-write; fullscreen; payment"
        allowfullscreen
      ></iframe>

      ${state.moduleLoading ? renderCrmWebViewLoader() : ''}
      ${state.error ? `<p class="crm-webview-error">${escapeHtml(state.error)}</p>` : ''}

      <button class="crm-app-settings-button" type="button" data-open-settings aria-label="Parametres de l'app">
        <span></span><span></span><span></span>
      </button>

      ${renderSettingsModal()}
    </div>
  `;

  app.querySelector<HTMLIFrameElement>('.crm-webview-frame')?.addEventListener('load', () => {
    state.moduleLoading = false;
    app.querySelector('.crm-webview-loader')?.remove();
    postLocationToFrame();
  });

  bindSettingsEvents();
  postLocationToFrame();
}

function renderCrmWebViewLoader(): string {
  return `
    <div class="crm-webview-loader">
      ${brandMorphLoaderMarkup(true)}
    </div>
  `;
}

function renderShell(): void {
  const user = state.user;

  if (!user) {
    renderLogin();
    return;
  }

  const activeItem = activeMenuItem();
  const currentEntry = currentBrowserEntry();
  const title = activeItem?.label || currentEntry?.label || 'CRM';
  const subtitle = activeItem?.groupKey ? groupTitle(activeItem.groupKey) : currentEntry?.external ? 'Navigateur integre' : 'Module';
  const siteOptions = sites().map((site) => `
    <option value="${site.id}" ${site.id === state.selectedSiteId ? 'selected' : ''}>${escapeHtml(site.name)}</option>
  `).join('');

  app.innerHTML = `
    <div class="native-shell ${state.menuOpen ? 'is-menu-open' : ''}">
      <div class="native-backdrop" data-close-menu></div>

      <aside class="native-sidebar" aria-label="Menu Martin Sols">
        <div class="native-sidebar-brand">
          <img src="${escapeHtml(logoUrl())}" alt="Martin Sols">
        </div>
        <nav class="native-menu">
          ${renderMenuGroups()}
        </nav>
        <div class="native-sidebar-actions">
          <button class="native-sidebar-action" type="button" data-open-settings>Parametres app</button>
          <button class="native-sidebar-action" type="button" data-logout>Se deconnecter</button>
        </div>
      </aside>

      <section class="native-main">
        <header class="native-topbar">
          <button class="native-icon-button" type="button" data-toggle-menu aria-label="Ouvrir le menu">
            <span></span><span></span><span></span>
          </button>

          <label class="native-site-select">
            <span>Site</span>
            <select data-site-select ${siteOptions ? '' : 'disabled'}>
              ${siteOptions || '<option>Aucun site</option>'}
            </select>
          </label>

          <span class="native-status-pill ${state.network.connected ? '' : 'is-offline'}" data-network-status>
            ${escapeHtml(networkLabel(state.network))}
          </span>

          <button class="native-topbar-button" type="button" data-open-settings aria-label="Parametres de l'app">
            &#9881;
          </button>

          <div class="native-user">
            <div class="native-user-copy">
              <strong>${escapeHtml(firstName(user.name))}</strong>
              <span>${escapeHtml(roleLabel(user.role))}</span>
            </div>
            <div class="native-avatar">${escapeHtml(initials(user.name))}</div>
          </div>
        </header>

        <main class="native-content">
          <div class="native-module-title">
            <div class="native-module-copy">
              <p class="eyebrow" data-module-subtitle>${escapeHtml(subtitle)}</p>
              <h1 data-module-title>${escapeHtml(title)}</h1>
              ${currentEntry?.external ? `<span class="native-browser-address" data-browser-address>${escapeHtml(browserAddress())}</span>` : ''}
            </div>
            ${state.settings.showBrowserControls ? renderBrowserControls() : ''}
          </div>

          ${state.error ? `<p class="inline-error">${escapeHtml(state.error)}</p>` : ''}

          <section class="native-webview">
            ${state.moduleLoading ? renderFrameLoader() : ''}
            ${state.frameUrl
              ? `<iframe class="native-frame" src="${escapeHtml(state.frameUrl)}" title="${escapeHtml(title)}" allow="geolocation; camera; microphone; clipboard-read; clipboard-write; fullscreen; payment" allowfullscreen></iframe>`
              : '<div class="native-frame-empty">Selectionnez un module.</div>'}
          </section>
        </main>
      </section>

      ${renderSettingsModal()}
    </div>
  `;

  bindShellEvents();
  updateBrowserChrome();
  postLocationToFrame();
}

function renderBrowserControls(): string {
  return `
    <div class="native-module-actions" aria-label="Navigation integree">
      <button class="native-tool-button" type="button" data-browser-back aria-label="Retour" ${canGoBack() ? '' : 'disabled'}>&lsaquo;</button>
      <button class="native-tool-button" type="button" data-browser-forward aria-label="Avancer" ${canGoForward() ? '' : 'disabled'}>&rsaquo;</button>
      <button class="native-tool-button" type="button" data-refresh-module aria-label="Actualiser">&#8635;</button>
    </div>
  `;
}

function renderFrameLoader(): string {
  return `
    <div class="native-frame-loader">
      <div class="brand-morph-loader__stage" aria-label="Chargement" role="status">
        <div class="brand-morph-loader__symbol">
          <span class="segment segment--top"></span>
          <span class="segment segment--right"></span>
          <span class="segment segment--bottom"></span>
          <span class="segment segment--left"></span>
        </div>
      </div>
    </div>
  `;
}

function bindShellEvents(): void {
  app.querySelector('[data-toggle-menu]')?.addEventListener('click', () => {
    state.menuOpen = !state.menuOpen;
    renderShell();
  });

  app.querySelector('[data-close-menu]')?.addEventListener('click', () => {
    state.menuOpen = false;
    renderShell();
  });

  app.querySelector('[data-browser-back]')?.addEventListener('click', () => {
    void navigateBrowserHistory(-1);
  });

  app.querySelector('[data-browser-forward]')?.addEventListener('click', () => {
    void navigateBrowserHistory(1);
  });

  app.querySelector('[data-refresh-module]')?.addEventListener('click', () => {
    void refreshBrowser();
  });

  app.querySelector('[data-logout]')?.addEventListener('click', () => {
    void handleLogout();
  });

  app.querySelector<HTMLSelectElement>('[data-site-select]')?.addEventListener('change', (event) => {
    const select = event.currentTarget as HTMLSelectElement;
    const siteId = Number(select.value);

    state.selectedSiteId = Number.isFinite(siteId) && siteId > 0 ? siteId : null;

    if (state.selectedSiteId) {
      localStorage.setItem(storage.siteId, String(state.selectedSiteId));
    }

    void openActiveModule({ addHistory: true });
  });

  app.querySelectorAll<HTMLButtonElement>('[data-module-slug]').forEach((button) => {
    button.addEventListener('click', () => {
      state.menuOpen = false;
      state.activeSlug = button.dataset.moduleSlug || '';

      if (state.settings.rememberLastModule) {
        localStorage.setItem(storage.activeSlug, state.activeSlug);
      }

      void openActiveModule({ addHistory: true });
    });
  });

  app.querySelector<HTMLIFrameElement>('.native-frame')?.addEventListener('load', () => {
    state.moduleLoading = false;
    const loader = app.querySelector('.native-frame-loader');
    loader?.remove();
    postLocationToFrame();
    updateBrowserChrome();
  });

  bindSettingsEvents();
}

async function openActiveModule(options: { addHistory: boolean }): Promise<void> {
  ensureSelection();
  const item = activeMenuItem();

  if (!item) {
    state.frameUrl = '';
    render();
    return;
  }

  const routePath = item.routePath || `/${item.slug}`;
  await openBrowserEntry({
    routePath,
    label: item.label,
    external: isExternalRoute(routePath),
    pushedAt: Date.now(),
  }, options);
}

async function openBrowserEntry(entry: BrowserEntry, options: { addHistory: boolean }): Promise<void> {
  state.moduleLoading = true;
  state.error = '';

  if (entry.external) {
    if (options.addHistory) {
      pushBrowserEntry(entry);
    }

    state.frameUrl = entry.routePath;
    render();
    return;
  }

  const redirectPath = routeWithEmbed(entry.routePath);
  const storedEntry: BrowserEntry = {
    ...entry,
    routePath: redirectPath,
    external: false,
  };

  if (options.addHistory) {
    pushBrowserEntry(storedEntry);
  }

  render();

  try {
    const result = await apiFetch<WebSessionResult>('/api/mobile/web-session', {
      method: 'POST',
      body: JSON.stringify({
        redirectPath,
        siteId: state.selectedSiteId,
      }),
    });

    if (!result.ok || !result.url) {
      state.error = result.error || 'Impossible de charger le module.';
      state.frameUrl = '';
      state.moduleLoading = false;
      render();
      return;
    }

    state.frameUrl = result.url;
    render();
  } catch {
    state.error = state.network.connected ? 'Impossible de preparer la session web du module.' : 'Connexion reseau indisponible.';
    state.frameUrl = '';
    state.moduleLoading = false;
    render();
  }
}

async function navigateBrowserHistory(delta: -1 | 1): Promise<void> {
  const nextIndex = state.browserIndex + delta;
  const entry = state.browserHistory[nextIndex];

  if (!entry) {
    return;
  }

  state.browserIndex = nextIndex;
  syncActiveSlugFromPath(entry.routePath);
  await openBrowserEntry(entry, { addHistory: false });
}

async function refreshBrowser(): Promise<void> {
  const entry = currentBrowserEntry();

  if (entry) {
    await openBrowserEntry(entry, { addHistory: false });
    return;
  }

  await openActiveModule({ addHistory: false });
}

function pushBrowserEntry(entry: BrowserEntry): void {
  const routePath = normalizeBrowserRoute(entry.routePath);
  const current = currentBrowserEntry();

  if (!routePath) {
    return;
  }

  if (current?.routePath === routePath) {
    current.label = entry.label || current.label;
    current.external = entry.external;
    return;
  }

  const nextHistory = state.browserHistory.slice(0, state.browserIndex + 1);
  nextHistory.push({
    ...entry,
    routePath,
    pushedAt: Date.now(),
  });

  while (nextHistory.length > 40) {
    nextHistory.shift();
  }

  state.browserHistory = nextHistory;
  state.browserIndex = nextHistory.length - 1;
}

function currentBrowserEntry(): BrowserEntry | null {
  return state.browserHistory[state.browserIndex] || null;
}

function canGoBack(): boolean {
  return state.browserIndex > 0;
}

function canGoForward(): boolean {
  return state.browserIndex >= 0 && state.browserIndex < state.browserHistory.length - 1;
}

function handleFrameMessage(event: MessageEvent): void {
  const origin = crmOrigin();

  if (!origin || event.origin !== origin || !isFrameMessage(event.data)) {
    return;
  }

  const message = event.data;

  if (message.type === 'crm:navigation') {
    const routePath = normalizeBrowserRoute(message.payload?.path || '');

    if (!routePath) {
      return;
    }

    syncActiveSlugFromPath(routePath);
    pushBrowserEntry({
      routePath,
      label: cleanTitle(message.payload?.title || '') || activeMenuItem()?.label || 'CRM',
      external: isExternalRoute(routePath),
      pushedAt: Date.now(),
    });
    updateBrowserChrome();
    return;
  }

  if (message.type === 'crm:external-url') {
    const url = normalizeExternalUrl(message.payload?.url || '');

    if (!url) {
      return;
    }

    void openBrowserEntry({
      routePath: url,
      label: cleanTitle(message.payload?.title || '') || 'Lien externe',
      external: true,
      pushedAt: Date.now(),
    }, { addHistory: true });
    return;
  }

  if (message.type === 'crm:location-request') {
    void refreshLocation('request');
  }
}

function isFrameMessage(value: unknown): value is FrameMessage {
  if (!value || typeof value !== 'object') {
    return false;
  }

  const message = value as FrameMessage;

  return message.source === 'martin-sols-crm' && typeof message.type === 'string';
}

function updateBrowserChrome(): void {
  const activeItem = activeMenuItem();
  const currentEntry = currentBrowserEntry();
  const title = activeItem?.label || currentEntry?.label || 'CRM';
  const subtitle = activeItem?.groupKey ? groupTitle(activeItem.groupKey) : currentEntry?.external ? 'Navigateur integre' : 'Module';

  app.querySelectorAll<HTMLButtonElement>('[data-browser-back]').forEach((button) => {
    button.disabled = !canGoBack();
  });

  app.querySelectorAll<HTMLButtonElement>('[data-browser-forward]').forEach((button) => {
    button.disabled = !canGoForward();
  });

  app.querySelectorAll<HTMLElement>('[data-browser-address]').forEach((element) => {
    element.textContent = browserAddress();
  });

  app.querySelectorAll<HTMLElement>('[data-module-title]').forEach((element) => {
    element.textContent = title;
  });

  app.querySelectorAll<HTMLElement>('[data-module-subtitle]').forEach((element) => {
    element.textContent = subtitle;
  });

  app.querySelectorAll<HTMLElement>('[data-network-status]').forEach((element) => {
    element.textContent = networkLabel(state.network);
    element.classList.toggle('is-offline', !state.network.connected);
  });

  app.querySelectorAll<HTMLElement>('[data-location-status]').forEach((element) => {
    element.textContent = locationLabel();
  });
}

async function handleLogout(): Promise<void> {
  try {
    await apiFetch('/api/mobile/logout', { method: 'POST' });
  } catch {
    // Local logout still clears the mobile session.
  }

  clearSession();
  render();
}

async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');

  if (options.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  if (state.token) {
    headers.set('Authorization', `Bearer ${state.token}`);
  }

  const url = new URL(path, state.apiBase).toString();

  if (isNativeApp()) {
    return nativeApiFetch<T>(url, options, headers);
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (response.status === 401) {
    clearSession();
    render();
    throw new Error('Unauthenticated');
  }

  return response.json() as Promise<T>;
}

async function nativeApiFetch<T>(url: string, options: RequestInit, headers: Headers): Promise<T> {
  const method = String(options.method || 'GET').toUpperCase();
  const nativeHeaders: HttpHeaders = {};

  headers.forEach((value, key) => {
    nativeHeaders[key] = value;
  });

  const response = await CapacitorHttp.request({
    url,
    method,
    headers: nativeHeaders,
    data: nativeRequestData(options.body, headers),
    responseType: 'json',
    connectTimeout: 15000,
    readTimeout: 30000,
  });

  if (response.status === 401) {
    clearSession();
    render();
    throw new Error('Unauthenticated');
  }

  return parseNativeResponse<T>(response.data);
}

function nativeRequestData(body: BodyInit | null | undefined, headers: Headers): unknown {
  if (typeof body !== 'string') {
    return body;
  }

  if (!headers.get('Content-Type')?.includes('application/json')) {
    return body;
  }

  try {
    return JSON.parse(body) as unknown;
  } catch {
    return body;
  }
}

function parseNativeResponse<T>(data: unknown): T {
  if (typeof data !== 'string') {
    return data as T;
  }

  try {
    return JSON.parse(data) as T;
  } catch {
    return data as T;
  }
}

function ensureSelection(): void {
  const availableSites = sites();

  if (!state.selectedSiteId && state.user?.defaultSiteId) {
    state.selectedSiteId = state.user.defaultSiteId;
  }

  if (!state.selectedSiteId && availableSites[0]) {
    state.selectedSiteId = availableSites[0].id;
  }

  if (state.selectedSiteId) {
    localStorage.setItem(storage.siteId, String(state.selectedSiteId));
  }

  const items = menuItems();
  const storedSlug = state.settings.rememberLastModule ? state.activeSlug : '';
  const hasActive = items.some((item) => item.slug === storedSlug);

  if (!hasActive) {
    state.activeSlug = items[0]?.slug || '';
  }

  if (state.activeSlug && state.settings.rememberLastModule) {
    localStorage.setItem(storage.activeSlug, state.activeSlug);
  }
}

function sites(): MobileSite[] {
  if (state.user?.sites?.length) {
    return state.user.sites;
  }

  return (state.user?.siteIds || []).map((id) => ({
    id,
    name: `Site ${id}`,
  }));
}

function menuGroups(): MobileMenuGroup[] {
  if (state.user?.menu?.length) {
    return state.user.menu;
  }

  const modules = state.user?.modules || [];
  const groups = new Map<string, MobileMenuGroup>();

  modules.forEach((module) => {
    const groupKey = defaultGroupFor(module.slug);
    const group = groups.get(groupKey) || {
      key: groupKey,
      title: groupTitle(groupKey),
      items: [],
    };

    group.items.push({
      key: `module:${module.slug}`,
      groupKey,
      iconKey: defaultIconFor(module.slug),
      label: module.name,
      slug: module.slug,
      routePath: module.routePath || `/${module.slug}`,
    });

    groups.set(groupKey, group);
  });

  return Array.from(groups.values());
}

function menuItems(): MobileMenuItem[] {
  return menuGroups().flatMap((group) => group.items);
}

function activeMenuItem(): MobileMenuItem | null {
  return menuItems().find((item) => item.slug === state.activeSlug) || null;
}

function renderMenuGroups(): string {
  const groups = menuGroups();

  if (!groups.length) {
    return '<p class="native-empty-menu">Aucun module disponible.</p>';
  }

  return groups.map((group) => `
    <section class="native-menu-group">
      <p>${escapeHtml(group.title)}</p>
      ${group.items.map((item) => `
        <button class="native-menu-item ${item.slug === state.activeSlug ? 'is-active' : ''}" type="button" data-module-slug="${escapeHtml(item.slug)}">
          <span class="native-menu-icon">${escapeHtml(iconLabel(item.iconKey || defaultIconFor(item.slug)))}</span>
          <span>${escapeHtml(item.label)}</span>
        </button>
      `).join('')}
    </section>
  `).join('');
}

function renderSettingsModal(): string {
  if (!state.settingsOpen) {
    return '';
  }

  const appVersion = state.appInfo ? `${state.appInfo.version} (${state.appInfo.build})` : 'Web';
  const platform = state.deviceInfo ? `${state.deviceInfo.platform} ${state.deviceInfo.osVersion}` : platformLabel();
  const webView = state.deviceInfo?.webViewVersion || navigator.userAgent;

  return `
    <div class="settings-overlay" role="dialog" aria-modal="true" aria-label="Parametres de l'app">
      <button class="settings-backdrop" type="button" data-settings-close aria-label="Fermer"></button>
      <form id="settings-form" class="settings-panel">
        <div class="settings-header">
          <div>
            <p class="eyebrow">Application</p>
            <h2>Parametres</h2>
          </div>
          <button class="settings-close" type="button" data-settings-close aria-label="Fermer">&times;</button>
        </div>

        <label>
          <span>Serveur CRM</span>
          <input name="crmUrl" type="url" inputmode="url" autocomplete="url" value="${escapeHtml(state.apiBase)}" required>
        </label>

        <div class="settings-switch-list">
          ${renderSwitch('locationEnabled', 'Localisation', state.settings.locationEnabled)}
          ${renderSwitch('highAccuracyLocation', 'Haute precision', state.settings.highAccuracyLocation)}
          ${renderSwitch('rememberLastModule', 'Dernier module', state.settings.rememberLastModule)}
          ${renderSwitch('showBrowserControls', 'Commandes navigateur', state.settings.showBrowserControls)}
        </div>

        <div class="settings-status-grid">
          <div>
            <span>Reseau</span>
            <strong data-network-status class="${state.network.connected ? '' : 'is-offline'}">${escapeHtml(networkLabel(state.network))}</strong>
          </div>
          <div>
            <span>Localisation</span>
            <strong data-location-status>${escapeHtml(locationLabel())}</strong>
          </div>
          <div>
            <span>Version</span>
            <strong>${escapeHtml(appVersion)}</strong>
          </div>
          <div>
            <span>Plateforme</span>
            <strong>${escapeHtml(platform)}</strong>
          </div>
        </div>

        <p class="settings-webview">${escapeHtml(webView)}</p>
        ${state.settingsError ? `<p class="form-error">${escapeHtml(state.settingsError)}</p>` : ''}
        ${state.location.error ? `<p class="form-error">${escapeHtml(state.location.error)}</p>` : ''}

        <div class="settings-actions">
          <button class="secondary-button" type="button" data-test-location ${state.location.loading ? 'disabled' : ''}>
            ${state.location.loading ? 'Recherche...' : 'Tester localisation'}
          </button>
          <button class="primary-button compact" type="submit" ${state.settingsSaving ? 'disabled' : ''}>
            ${state.settingsSaving ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </form>
    </div>
  `;
}

function renderSwitch(name: keyof AppSettings, label: string, checked: boolean): string {
  return `
    <label class="settings-switch">
      <span>${escapeHtml(label)}</span>
      <input name="${escapeHtml(name)}" type="checkbox" ${checked ? 'checked' : ''}>
      <i aria-hidden="true"></i>
    </label>
  `;
}

function bindSettingsEvents(): void {
  app.querySelectorAll('[data-open-settings]').forEach((button) => {
    button.addEventListener('click', () => {
      openSettings();
    });
  });

  app.querySelectorAll('[data-settings-close]').forEach((button) => {
    button.addEventListener('click', () => {
      closeSettings();
    });
  });

  app.querySelector<HTMLFormElement>('#settings-form')?.addEventListener('submit', (event) => {
    event.preventDefault();
    void handleSettingsSubmit(event.currentTarget as HTMLFormElement);
  });

  app.querySelector('[data-test-location]')?.addEventListener('click', () => {
    const form = app.querySelector<HTMLFormElement>('#settings-form');
    const highAccuracy = form ? formCheckbox(form, 'highAccuracyLocation') : state.settings.highAccuracyLocation;

    void refreshLocation('request', highAccuracy);
  });
}

function openSettings(): void {
  state.settingsOpen = true;
  state.settingsError = '';

  if (isCrmWebViewShellActive()) {
    updateSettingsModalInPlace();
    return;
  }

  render();
}

function closeSettings(): void {
  state.settingsOpen = false;
  state.settingsSaving = false;
  state.settingsError = '';

  if (isCrmWebViewShellActive()) {
    updateSettingsModalInPlace();
    return;
  }

  render();
}

function isCrmWebViewShellActive(): boolean {
  return Boolean(app.querySelector('.crm-webview-shell'));
}

function updateSettingsModalInPlace(): void {
  app.querySelector('.settings-overlay')?.remove();

  if (!state.settingsOpen) {
    return;
  }

  app.querySelector('.crm-webview-shell')?.insertAdjacentHTML('beforeend', renderSettingsModal());
  bindSettingsEvents();
}

async function handleSettingsSubmit(form: HTMLFormElement): Promise<void> {
  const formData = new FormData(form);
  const nextApiBase = normalizeUrl(String(formData.get('crmUrl') || ''));
  const apiChanged = nextApiBase !== state.apiBase;
  const webViewShellActive = isCrmWebViewShellActive();

  state.settingsSaving = true;
  state.settingsError = '';

  if (webViewShellActive) {
    updateSettingsModalInPlace();
  } else {
    render();
  }

  state.settings = {
    crmUrl: nextApiBase,
    locationEnabled: formCheckbox(form, 'locationEnabled'),
    highAccuracyLocation: formCheckbox(form, 'highAccuracyLocation'),
    rememberLastModule: formCheckbox(form, 'rememberLastModule'),
    showBrowserControls: formCheckbox(form, 'showBrowserControls'),
  };
  state.apiBase = nextApiBase;

  if (!state.settings.rememberLastModule) {
    localStorage.removeItem(storage.activeSlug);
  }

  try {
    await persistSettings();

    if (state.settings.locationEnabled) {
      await refreshLocation('request', state.settings.highAccuracyLocation, false);
    }

    if (apiChanged) {
      clearSession();
      state.error = 'Serveur modifie. Connectez-vous a nouveau.';
    }

    state.settingsOpen = false;
  } catch {
    state.settingsError = 'Impossible d enregistrer les parametres.';
  } finally {
    state.settingsSaving = false;

    if (webViewShellActive && !apiChanged && state.token && state.user) {
      updateSettingsModalInPlace();
    } else {
      render();
    }
  }
}

function formCheckbox(form: HTMLFormElement, name: string): boolean {
  const element = form.elements.namedItem(name);

  return element instanceof HTMLInputElement ? element.checked : false;
}

async function refreshLocation(mode: 'request' | 'silent' = 'request', highAccuracy = state.settings.highAccuracyLocation, shouldRender = true): Promise<void> {
  state.location.loading = true;
  state.location.error = '';
  const webViewShellActive = isCrmWebViewShellActive();

  if (shouldRender && state.settingsOpen) {
    if (webViewShellActive) {
      updateSettingsModalInPlace();
    } else {
      render();
    }
  } else {
    updateBrowserChrome();
  }

  try {
    let permissions = await Geolocation.checkPermissions();

    if (mode === 'request' && !permissionStatusAllowsLocation(permissions)) {
      permissions = await Geolocation.requestPermissions({
        permissions: highAccuracy ? ['location'] : ['coarseLocation'],
      });
    }

    applyLocationPermissions(permissions);

    if (!permissionStatusAllowsLocation(permissions)) {
      state.location.error = 'Autorisation de localisation refusee.';
      return;
    }

    const position = await Geolocation.getCurrentPosition({
      enableHighAccuracy: highAccuracy,
      timeout: 12000,
      maximumAge: 60000,
    });

    state.location.last = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      updatedAt: new Date(position.timestamp).toISOString(),
    };
    state.location.error = '';
    postLocationToFrame();
  } catch (error) {
    state.location.error = error instanceof Error && error.message ? error.message : 'Localisation indisponible.';
  } finally {
    state.location.loading = false;

    if (shouldRender && state.settingsOpen) {
      if (webViewShellActive) {
        updateSettingsModalInPlace();
      } else {
        render();
      }
    } else {
      updateBrowserChrome();
    }
  }
}

function permissionStatusAllowsLocation(status: GeolocationPermissionStatus): boolean {
  return status.location === 'granted' || status.coarseLocation === 'granted';
}

function hasLocationGrant(): boolean {
  return state.location.permission === 'granted' || state.location.coarsePermission === 'granted';
}

function postLocationToFrame(): void {
  const frame = app.querySelector<HTMLIFrameElement>('.crm-webview-frame, .native-frame');

  if (!frame?.contentWindow) {
    return;
  }

  frame.contentWindow.postMessage({
    source: 'martin-sols-mobile',
    type: 'mobile:location-update',
    payload: {
      enabled: state.settings.locationEnabled,
      permission: state.location.permission,
      coarsePermission: state.location.coarsePermission,
      last: state.location.last,
    },
  }, '*');
}

function routeWithEmbed(routePath: string): string {
  const url = new URL(routePath, state.apiBase);
  const normalizedPath = url.pathname.replace(/\/+$/, '') || '/';

  if (normalizedPath === '/dashboard/crm') {
    url.pathname = '/';
  }

  url.searchParams.set('mobile_embed', '1');

  if (state.selectedSiteId) {
    url.searchParams.set('mobile_site_id', String(state.selectedSiteId));
  }

  return `${url.pathname}${url.search}${url.hash}`;
}

function normalizeBrowserRoute(value: string): string {
  const route = value.trim();

  if (!route) {
    return '';
  }

  if (isExternalRoute(route)) {
    return normalizeExternalUrl(route);
  }

  try {
    const url = new URL(route, state.apiBase);

    if (url.origin !== crmOrigin()) {
      return '';
    }

    return `${url.pathname}${url.search}${url.hash}` || '/';
  } catch {
    return '';
  }
}

function normalizeExternalUrl(value: string): string {
  try {
    const url = new URL(value);

    if (!['http:', 'https:'].includes(url.protocol)) {
      return '';
    }

    return url.toString();
  } catch {
    return '';
  }
}

function isExternalRoute(routePath: string): boolean {
  if (!/^https?:\/\//i.test(routePath)) {
    return false;
  }

  try {
    return new URL(routePath).origin !== crmOrigin();
  } catch {
    return false;
  }
}

function syncActiveSlugFromPath(routePath: string): void {
  const normalized = normalizeBrowserRoute(routePath);

  if (!normalized || isExternalRoute(normalized)) {
    return;
  }

  const target = new URL(normalized, state.apiBase);
  const targetPath = target.pathname.replace(/\/+$/, '') || '/';
  const match = menuItems().find((item) => {
    const itemUrl = new URL(item.routePath || `/${item.slug}`, state.apiBase);
    const itemPath = itemUrl.pathname.replace(/\/+$/, '') || '/';

    return targetPath === itemPath || targetPath.startsWith(`${itemPath}/`) || (item.slug === 'dashboard' && targetPath === '/');
  });

  if (!match) {
    return;
  }

  state.activeSlug = match.slug;

  if (state.settings.rememberLastModule) {
    localStorage.setItem(storage.activeSlug, state.activeSlug);
  }
}

function browserAddress(): string {
  const entry = currentBrowserEntry();

  if (!entry) {
    return state.apiBase;
  }

  if (entry.external) {
    return entry.routePath;
  }

  try {
    const url = new URL(entry.routePath, state.apiBase);
    url.searchParams.delete('mobile_embed');
    url.searchParams.delete('mobile_site_id');

    return `${url.origin}${url.pathname}${url.search}${url.hash}`;
  } catch {
    return state.apiBase;
  }
}

function crmOrigin(): string {
  try {
    return new URL(state.apiBase).origin;
  } catch {
    return '';
  }
}

function logoUrl(): string {
  return new URL('/assets/logo/martin-sols-logo.png', state.apiBase).toString();
}

function defaultDeviceName(): string {
  const platform = state.deviceInfo?.platform === 'android' ? 'Android' : state.deviceInfo?.platform === 'ios' ? 'iOS' : 'Mobile';
  const model = state.deviceInfo?.model ? ` ${state.deviceInfo.model}` : '';

  return `Martin Sols ${platform}${model}`;
}

function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);

  return (parts[0]?.[0] || 'M').toUpperCase() + (parts[1]?.[0] || 'S').toUpperCase();
}

function firstName(name: string): string {
  return name.trim().split(/\s+/)[0] || name;
}

function roleLabel(role: string): string {
  return {
    admin: 'Admin',
    responsable: 'Responsable',
    user: 'Utilisateur',
  }[role] || role;
}

function defaultGroupFor(slug: string): string {
  if (['controle-caisse', 'remise-cheques', 'addvance', 'demandes-acompte'].includes(slug)) {
    return 'accounting';
  }

  if (['dashboard'].includes(slug)) {
    return 'home';
  }

  if (['reservations', 'locations-materiel', 'equipes', 'conges', 'tournees-representants', 'tapis-romus'].includes(slug) || slug.startsWith('documents-')) {
    return 'apps';
  }

  return 'internal';
}

function groupTitle(key: string): string {
  return {
    home: 'Accueil',
    apps: 'Applications CRM',
    accounting: 'Comptabilite',
    internal: 'Administration',
  }[key] || key;
}

function defaultIconFor(slug: string): string {
  return {
    dashboard: 'dashboard',
    reservations: 'truck',
    'locations-materiel': 'package',
    equipes: 'users',
    conges: 'calendar',
    'tournees-representants': 'calendar',
    'tapis-romus': 'table',
    'controle-caisse': 'card',
    'demandes-acompte': 'banknote',
    'remise-cheques': 'check',
    addvance: 'link',
    administration: 'settings',
    'pages-crm': 'page',
  }[slug] || (slug.startsWith('documents-') ? 'article' : 'module');
}

function iconLabel(iconKey: string): string {
  return {
    dashboard: 'TB',
    truck: 'RV',
    package: 'LM',
    users: 'EQ',
    table: 'TR',
    calendar: 'CA',
    card: 'CC',
    creditCard: 'CC',
    banknote: 'DA',
    check: 'RC',
    link: 'AD',
    settings: 'PR',
    page: 'PG',
    article: 'DO',
  }[iconKey] || 'MS';
}

function networkLabel(status: ConnectionStatus): string {
  if (!status.connected) {
    return 'Hors ligne';
  }

  return {
    wifi: 'Wi-Fi',
    cellular: 'Cellulaire',
    unknown: 'En ligne',
    none: 'Hors ligne',
  }[status.connectionType] || 'En ligne';
}

function locationLabel(): string {
  if (state.location.loading) {
    return 'Recherche...';
  }

  if (state.location.last) {
    const accuracy = Math.round(state.location.last.accuracy);

    return `${state.location.last.latitude.toFixed(5)}, ${state.location.last.longitude.toFixed(5)} / ${accuracy} m`;
  }

  if (hasLocationGrant()) {
    return 'Autorisee';
  }

  if (state.location.permission === 'denied' && state.location.coarsePermission === 'denied') {
    return 'Refusee';
  }

  return state.settings.locationEnabled ? 'A demander' : 'Desactivee';
}

function platformLabel(): string {
  const platform = Capacitor.getPlatform();

  return platform === 'web' ? 'Web' : platform;
}

function fallbackNetworkStatus(): ConnectionStatus {
  return {
    connected: navigator.onLine,
    connectionType: navigator.onLine ? 'unknown' : 'none',
  };
}

function cleanTitle(value: string): string {
  return value.replace(/\s+/g, ' ').replace(/\s+-\s+Martin Sols.*$/i, '').trim();
}

function brandMorphLoaderMarkup(visible: boolean): string {
  return `
    <div id="brand-morph-loader" class="brand-morph-loader ${visible ? 'is-visible' : ''}" aria-hidden="${visible ? 'false' : 'true'}">
      <div class="brand-morph-loader__backdrop"></div>
      <div class="brand-morph-loader__stage" role="status" aria-live="polite" aria-label="Chargement">
        <div class="brand-morph-loader__symbol">
          <span class="segment segment--top"></span>
          <span class="segment segment--right"></span>
          <span class="segment segment--bottom"></span>
          <span class="segment segment--left"></span>
        </div>
      </div>
    </div>
  `;
}

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

function isNativeApp(): boolean {
  return Capacitor.isNativePlatform();
}

function escapeHtml(value: unknown): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
