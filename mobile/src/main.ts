import './styles.css';

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

type AppState = {
  apiBase: string;
  token: string;
  user: MobileUser | null;
  selectedSiteId: number | null;
  activeSlug: string;
  frameUrl: string;
  menuOpen: boolean;
  loading: boolean;
  moduleLoading: boolean;
  error: string;
};

const appRoot = document.querySelector<HTMLDivElement>('#app');

if (!appRoot) {
  throw new Error('App root missing');
}

const app: HTMLDivElement = appRoot;

const defaultApiBase = normalizeUrl(import.meta.env.VITE_CRM_URL || import.meta.env.VITE_API_BASE_URL || 'https://crm.jp2.fr');

const storage = {
  token: 'martin-sols.mobile.token',
  user: 'martin-sols.mobile.user',
  siteId: 'martin-sols.mobile.siteId',
  activeSlug: 'martin-sols.mobile.activeSlug',
};

const state: AppState = {
  apiBase: defaultApiBase,
  token: localStorage.getItem(storage.token) || '',
  user: readStoredUser(),
  selectedSiteId: readStoredNumber(storage.siteId),
  activeSlug: localStorage.getItem(storage.activeSlug) || '',
  frameUrl: '',
  menuOpen: false,
  loading: false,
  moduleLoading: false,
  error: '',
};

void boot();

async function boot(): Promise<void> {
  if (!state.token) {
    render();
    return;
  }

  renderLoadingSession();

  try {
    const result = await apiFetch<MeResult>('/api/mobile/me');

    if (!result.ok || !result.user) {
      clearSession();
      render();
      return;
    }

    state.user = result.user;
    persistUser(result.user);
    ensureSelection();
    render();
    await openActiveModule();
  } catch {
    state.error = 'Impossible de verifier la session mobile.';
    clearSession();
    render();
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
  localStorage.removeItem(storage.token);
  localStorage.removeItem(storage.user);
  localStorage.removeItem(storage.siteId);
  localStorage.removeItem(storage.activeSlug);
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
  if (!state.token || !state.user) {
    renderLogin();
    return;
  }

  renderShell();
}

function renderLoadingSession(): void {
  app.innerHTML = `
    <main class="auth-screen">
      <section class="auth-layout">
        <div class="auth-brand-card">
          <div class="brand-logo-frame"><img class="brand-logo" src="${escapeHtml(logoUrl())}" alt="Martin Sols"></div>
          <div>
            <p class="eyebrow">Martin Sols</p>
            <h1>Connexion</h1>
            <p class="auth-subtitle">Verification de la session...</p>
          </div>
        </div>
      </section>
    </main>
  `;
}

function renderLogin(): void {
  app.innerHTML = `
    <main class="auth-screen">
      <section class="auth-layout">
        <div class="auth-brand-card">
          <div class="brand-logo-frame">
            <img class="brand-logo" src="${escapeHtml(logoUrl())}" alt="Martin Sols">
          </div>
          <div>
            <p class="eyebrow">Martin Sols</p>
            <h1>Application mobile</h1>
            <p class="auth-subtitle">Acces CRM securise</p>
          </div>
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

          <p class="login-server">${escapeHtml(state.apiBase)}</p>
        </form>
      </section>
    </main>
  `;

  app.querySelector<HTMLFormElement>('#login-form')?.addEventListener('submit', (event) => {
    event.preventDefault();
    void handleLogin(event.currentTarget as HTMLFormElement);
  });
}

async function handleLogin(form: HTMLFormElement): Promise<void> {
  const formData = new FormData(form);

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
    render();
    await openActiveModule();
  } catch {
    state.error = 'Impossible de contacter le CRM.';
  } finally {
    state.loading = false;
    render();
  }
}

function renderShell(): void {
  const user = state.user;

  if (!user) {
    renderLogin();
    return;
  }

  const activeItem = activeMenuItem();
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
        <button class="native-logout" type="button" data-logout>Se deconnecter</button>
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

          <button class="native-bell" type="button" aria-label="Notifications">
            <span>!</span>
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
            <div>
              <p class="eyebrow">${escapeHtml(activeItem?.groupKey ? groupTitle(activeItem.groupKey) : 'Module')}</p>
              <h1>${escapeHtml(activeItem?.label || 'CRM')}</h1>
            </div>
            <button class="secondary-button compact" type="button" data-refresh-module>Actualiser</button>
          </div>

          ${state.error ? `<p class="inline-error">${escapeHtml(state.error)}</p>` : ''}

          <section class="native-webview">
            ${state.moduleLoading ? '<div class="native-frame-loader">Chargement...</div>' : ''}
            ${state.frameUrl
              ? `<iframe class="native-frame" src="${escapeHtml(state.frameUrl)}" title="${escapeHtml(activeItem?.label || 'Module CRM')}" allow="camera; microphone; clipboard-read; clipboard-write; fullscreen"></iframe>`
              : '<div class="native-frame-empty">Selectionnez un module.</div>'}
          </section>
        </main>
      </section>
    </div>
  `;

  bindShellEvents();
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

  app.querySelector('[data-refresh-module]')?.addEventListener('click', () => {
    void openActiveModule();
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

    void openActiveModule();
  });

  app.querySelectorAll<HTMLButtonElement>('[data-module-slug]').forEach((button) => {
    button.addEventListener('click', () => {
      state.menuOpen = false;
      state.activeSlug = button.dataset.moduleSlug || '';
      localStorage.setItem(storage.activeSlug, state.activeSlug);
      void openActiveModule();
    });
  });

  app.querySelector<HTMLIFrameElement>('.native-frame')?.addEventListener('load', () => {
    state.moduleLoading = false;
    const loader = app.querySelector('.native-frame-loader');
    loader?.remove();
  });
}

async function openActiveModule(): Promise<void> {
  ensureSelection();
  const item = activeMenuItem();

  if (!item) {
    state.frameUrl = '';
    render();
    return;
  }

  state.moduleLoading = true;
  state.error = '';
  render();

  const routePath = item.routePath || `/${item.slug}`;

  if (/^https?:\/\//i.test(routePath)) {
    state.frameUrl = routePath;
    state.moduleLoading = false;
    render();
    return;
  }

  try {
    const result = await apiFetch<WebSessionResult>('/api/mobile/web-session', {
      method: 'POST',
      body: JSON.stringify({
        redirectPath: routeWithEmbed(routePath),
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
    state.error = 'Impossible de preparer la session web du module.';
    state.frameUrl = '';
    state.moduleLoading = false;
    render();
  }
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

  const response = await fetch(new URL(path, state.apiBase), {
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
  const hasActive = items.some((item) => item.slug === state.activeSlug);

  if (!hasActive) {
    state.activeSlug = items[0]?.slug || '';
  }

  if (state.activeSlug) {
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
  return menuItems().find((item) => item.slug === state.activeSlug) || menuItems()[0] || null;
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

function routeWithEmbed(routePath: string): string {
  const url = new URL(routePath, state.apiBase);
  url.searchParams.set('mobile_embed', '1');

  if (state.selectedSiteId) {
    url.searchParams.set('mobile_site_id', String(state.selectedSiteId));
  }

  return `${url.pathname}${url.search}`;
}

function logoUrl(): string {
  return new URL('/assets/logo/logo.svg', state.apiBase).toString();
}

function defaultDeviceName(): string {
  const platform = navigator.userAgent.includes('Android') ? 'Android' : 'Mobile';

  return `Martin Sols ${platform}`;
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
  if (['controle-caisse', 'remise-cheques', 'addvance'].includes(slug)) {
    return 'accounting';
  }

  if (['reservations', 'locations-materiel', 'tapis-romus'].includes(slug)) {
    return 'apps';
  }

  return 'internal';
}

function groupTitle(key: string): string {
  return {
    apps: 'Applications CRM',
    accounting: 'Comptabilite',
    internal: 'Administration',
  }[key] || key;
}

function defaultIconFor(slug: string): string {
  return {
    reservations: 'truck',
    'locations-materiel': 'package',
    'tapis-romus': 'table',
    conges: 'calendar',
    'controle-caisse': 'card',
    'remise-cheques': 'check',
    addvance: 'link',
    administration: 'settings',
    'pages-crm': 'page',
  }[slug] || 'module';
}

function iconLabel(iconKey: string): string {
  return {
    truck: 'RV',
    package: 'LM',
    table: 'TR',
    calendar: 'CA',
    card: 'CC',
    check: 'RC',
    link: 'AD',
    settings: 'PR',
    page: 'PG',
    article: 'PG',
  }[iconKey] || 'MS';
}

function escapeHtml(value: unknown): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
