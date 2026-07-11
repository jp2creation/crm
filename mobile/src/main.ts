import './styles.css';

type MobileModule = {
  id: number;
  name: string;
  slug: string;
  routePath?: string;
};

type MobileUser = {
  id: number;
  name: string;
  email?: string;
  role: string;
  siteIds: number[];
  modules: MobileModule[];
  permissions: string[];
};

type LoginResult = {
  ok: boolean;
  token?: string;
  tokenType?: string;
  expiresAt?: string | null;
  user?: MobileUser;
  error?: string;
};

type ApiResult = Record<string, unknown> & {
  ok?: boolean;
  error?: string;
};

type ModuleDescriptor = {
  slug: string;
  title: string;
  endpoint?: string;
  metricKey?: string;
};

type AppState = {
  apiBase: string;
  token: string;
  user: MobileUser | null;
  activeSlug: string;
  loading: boolean;
  error: string;
  snapshots: Record<string, ApiResult>;
};

const appRoot = document.querySelector<HTMLDivElement>('#app');

if (!appRoot) {
  throw new Error('App root missing');
}

const app = appRoot;

const moduleCatalog: Record<string, ModuleDescriptor> = {
  reservations: {
    slug: 'reservations',
    title: 'Reservations',
    endpoint: '/api/reservations?action=bootstrap',
    metricKey: 'reservations',
  },
  'locations-materiel': {
    slug: 'locations-materiel',
    title: 'Materiel',
    endpoint: '/api/equipment-rentals?action=bootstrap',
    metricKey: 'equipmentRentals',
  },
  conges: {
    slug: 'conges',
    title: 'Conges',
    endpoint: '/api/conges?action=bootstrap',
    metricKey: 'leaves',
  },
  'pages-crm': {
    slug: 'pages-crm',
    title: 'Pages',
    endpoint: '/api/pages?action=bootstrap',
    metricKey: 'pages',
  },
  administration: {
    slug: 'administration',
    title: 'Profil',
    endpoint: '/api/administration?action=profile',
    metricKey: 'profile',
  },
};

const fallbackModules: MobileModule[] = [
  { id: 1, name: 'Reservations', slug: 'reservations', routePath: '/reservations' },
  { id: 2, name: 'Materiel', slug: 'locations-materiel', routePath: '/locations-materiel' },
  { id: 3, name: 'Conges', slug: 'conges', routePath: '/conges' },
  { id: 4, name: 'Pages', slug: 'pages-crm', routePath: '/pages-crm' },
];

const storage = {
  apiBase: 'crm.mobile.apiBase',
  token: 'crm.mobile.token',
  user: 'crm.mobile.user',
};

const defaultApiBase = 'https://crm.jp2.fr';

const state: AppState = {
  apiBase: normalizeUrl(localStorage.getItem(storage.apiBase) || import.meta.env.VITE_API_BASE_URL || defaultApiBase),
  token: localStorage.getItem(storage.token) || '',
  user: readStoredUser(),
  activeSlug: '',
  loading: false,
  error: '',
  snapshots: {},
};

state.activeSlug = firstAvailableModule()?.slug || 'reservations';
render();

if (state.token && state.user) {
  void loadModule(state.activeSlug);
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

function persistSession(result: LoginResult): void {
  if (!result.token || !result.user) {
    return;
  }

  state.token = result.token;
  state.user = result.user;
  state.activeSlug = firstAvailableModule()?.slug || 'reservations';
  state.snapshots = {};
  localStorage.setItem(storage.token, result.token);
  localStorage.setItem(storage.user, JSON.stringify(result.user));
}

function clearSession(): void {
  state.token = '';
  state.user = null;
  state.snapshots = {};
  state.error = '';
  localStorage.removeItem(storage.token);
  localStorage.removeItem(storage.user);
}

function normalizeUrl(value: string): string {
  let trimmed = value.trim();

  if (!trimmed) {
    return defaultApiBase;
  }

  if (!/^https?:\/\//i.test(trimmed)) {
    trimmed = `https://${trimmed}`;
  }

  try {
    const url = new URL(trimmed);
    const path = url.pathname.replace(/\/+$/, '');

    if (!path || path.startsWith('/api')) {
      return url.origin;
    }

    return `${url.origin}${path}`;
  } catch {
    return defaultApiBase;
  }
}

function availableModules(): MobileModule[] {
  const modules = state.user?.modules?.length ? state.user.modules : fallbackModules;

  return modules.filter((module) => module.slug !== 'blocked');
}

function firstAvailableModule(): MobileModule | null {
  return availableModules()[0] || null;
}

function descriptorFor(slug: string): ModuleDescriptor {
  return moduleCatalog[slug] || {
    slug,
    title: titleFromSlug(slug),
  };
}

function titleFromSlug(slug: string): string {
  return slug
    .split('-')
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function render(): void {
  if (!state.token || !state.user) {
    renderLogin();
    return;
  }

  renderShell();
}

function renderLogin(): void {
  app.innerHTML = `
    <main class="auth-screen">
      <section class="auth-layout">
        <div class="auth-brand-card">
          <div class="brand-logo-frame">
            <img class="brand-logo" src="${escapeHtml(logoUrl())}" alt="Martin Sols" />
          </div>
          <div>
            <p class="eyebrow">Martin Sols</p>
            <h1>CRM</h1>
            <p class="auth-subtitle">Application mobile</p>
          </div>
        </div>

        <form id="login-form" class="auth-panel">
          <div class="form-header">
            <p class="eyebrow">Acces securise</p>
            <h2>Connexion</h2>
          </div>

          <label>
            <span>Serveur API</span>
            <input name="apiBase" type="url" value="${escapeHtml(state.apiBase)}" autocomplete="url" required />
          </label>
          <label>
            <span>Email</span>
            <input name="email" type="email" autocomplete="email" required />
          </label>
          <label>
            <span>Mot de passe</span>
            <input name="password" type="password" autocomplete="current-password" required />
          </label>
          <label>
            <span>Appareil</span>
            <input name="deviceName" type="text" value="${escapeHtml(defaultDeviceName())}" maxlength="120" required />
          </label>
          ${state.error ? `<p class="form-error">${escapeHtml(state.error)}</p>` : ''}
          <button class="primary-button" type="submit" ${state.loading ? 'disabled' : ''}>
            ${state.loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>
      </section>
    </main>
  `;

  app.querySelector<HTMLFormElement>('#login-form')?.addEventListener('submit', (event) => {
    event.preventDefault();
    void login(new FormData(event.currentTarget as HTMLFormElement));
  });
}

function renderShell(): void {
  const user = state.user;

  if (!user) {
    renderLogin();
    return;
  }

  const modules = availableModules();
  const activeModule = modules.find((module) => module.slug === state.activeSlug) || modules[0];

  if (activeModule && state.activeSlug !== activeModule.slug) {
    state.activeSlug = activeModule.slug;
  }

  app.innerHTML = `
    <main class="app-shell">
      <header class="crm-header">
        <div class="brand-row">
          <div class="brand-mark">MS</div>
          <div>
            <p class="eyebrow">Martin Sols</p>
            <h1>CRM</h1>
          </div>
        </div>

        <div class="header-user">
          <div class="user-copy">
            <span>${escapeHtml(user.name)}</span>
            <small>${escapeHtml(user.role || 'Utilisateur')}</small>
          </div>
          <button class="icon-button" type="button" data-action="logout" aria-label="Deconnexion" title="Deconnexion">
            <span aria-hidden="true">X</span>
          </button>
        </div>
      </header>

      <div class="mobile-workspace">
        <nav class="module-nav" aria-label="Modules CRM">
          ${modules.map((module) => renderModuleButton(module)).join('')}
        </nav>

        <section class="module-workspace">
          ${renderActiveModule()}
        </section>
      </div>

      <footer class="app-footer">
        <span>${escapeHtml(state.apiBase)}</span>
      </footer>
    </main>
  `;

  app.querySelectorAll<HTMLButtonElement>('[data-module]').forEach((button) => {
    button.addEventListener('click', () => {
      const slug = button.dataset.module || '';
      state.activeSlug = slug;
      render();

      if (!state.snapshots[slug]) {
        void loadModule(slug);
      }
    });
  });

  app.querySelector<HTMLButtonElement>('[data-action="refresh"]')?.addEventListener('click', () => {
    void loadModule(state.activeSlug);
  });

  app.querySelector<HTMLButtonElement>('[data-action="logout"]')?.addEventListener('click', () => {
    void logout();
  });
}

function renderModuleButton(module: MobileModule): string {
  const active = module.slug === state.activeSlug;
  const label = moduleCatalog[module.slug]?.title || module.name;
  const icon = moduleIcon(module.slug, label);

  return `
    <button class="module-link ${active ? 'is-active' : ''}" type="button" data-module="${escapeHtml(module.slug)}">
      <span class="module-link-mark" aria-hidden="true">${escapeHtml(icon)}</span>
      <span class="module-link-copy">
        <strong>${escapeHtml(label)}</strong>
        <small>${escapeHtml(module.slug)}</small>
      </span>
    </button>
  `;
}

function renderActiveModule(): string {
  const descriptor = descriptorFor(state.activeSlug);
  const snapshot = state.snapshots[state.activeSlug];

  return `
    <div class="module-header">
      <div>
        <p class="eyebrow">Module CRM</p>
        <h2>${escapeHtml(descriptor.title)}</h2>
      </div>
      ${descriptor.endpoint ? `<button class="secondary-button" type="button" data-action="refresh" ${state.loading ? 'disabled' : ''}>Actualiser</button>` : ''}
    </div>
    ${state.error ? `<p class="inline-error">${escapeHtml(state.error)}</p>` : ''}
    ${state.loading ? '<div class="loading-state">Chargement...</div>' : renderModuleBody(descriptor, snapshot)}
  `;
}

function renderModuleBody(descriptor: ModuleDescriptor, snapshot?: ApiResult): string {
  if (!descriptor.endpoint) {
    return `
      <div class="empty-state">
        <strong>${escapeHtml(descriptor.title)}</strong>
        <span>Disponible dans le CRM web.</span>
      </div>
    `;
  }

  if (!snapshot) {
    return `
      <div class="empty-state">
        <strong>Aucune synchronisation</strong>
        <button class="primary-button compact" type="button" data-action="refresh">Charger</button>
      </div>
    `;
  }

  if (descriptor.slug === 'reservations') {
    return renderReservations(snapshot);
  }

  if (descriptor.slug === 'locations-materiel') {
    return renderEquipment(snapshot);
  }

  if (descriptor.slug === 'conges') {
    return renderLeaves(snapshot);
  }

  if (descriptor.slug === 'pages-crm') {
    return renderPages(snapshot);
  }

  if (descriptor.slug === 'administration') {
    return renderProfile(snapshot);
  }

  return renderGeneric(snapshot, descriptor.metricKey || 'items');
}

function renderReservations(snapshot: ApiResult): string {
  const reservations = asArray(snapshot.reservations);
  const vehicles = byId(snapshot.vehicles, 'name');
  const sites = byId(snapshot.sites, 'name');
  const nextRows = reservations
    .slice()
    .sort((a, b) => textField(a, 'startAt').localeCompare(textField(b, 'startAt')))
    .slice(0, 6);

  return `
    <div class="stat-grid">
      ${renderStat('Reservations', reservations.length, 'red')}
      ${renderStat('Vehicules', asArray(snapshot.vehicles).length, 'blue')}
      ${renderStat('Sites', asArray(snapshot.sites).length, 'green')}
    </div>
    ${renderRows(nextRows, (row) => ({
      title: textField(row, 'title') || 'Reservation',
      meta: `${lookup(vehicles, numberField(row, 'vehicleId'))} - ${lookup(sites, numberField(row, 'siteId'))}`,
      detail: `${formatDate(textField(row, 'startAt'))} - ${formatDate(textField(row, 'endAt'))}`,
    }))}
  `;
}

function renderEquipment(snapshot: ApiResult): string {
  const rentals = asArray(snapshot.equipmentRentals);
  const items = byId(snapshot.equipmentItems, 'name');
  const rows = rentals
    .slice()
    .sort((a, b) => textField(a, 'startAt').localeCompare(textField(b, 'startAt')))
    .slice(0, 6);

  return `
    <div class="stat-grid">
      ${renderStat('Locations', rentals.length, 'red')}
      ${renderStat('Materiels', asArray(snapshot.equipmentItems).length, 'blue')}
      ${renderStat('Categories', asArray(snapshot.equipmentCategories).length, 'green')}
    </div>
    ${renderRows(rows, (row) => ({
      title: textField(row, 'title') || 'Location',
      meta: lookup(items, numberField(row, 'equipmentItemId')),
      detail: `${formatDate(textField(row, 'startAt'))} - ${formatDate(textField(row, 'endAt'))}`,
    }))}
  `;
}

function renderLeaves(snapshot: ApiResult): string {
  const leaves = asArray(snapshot.leaves);
  const rows = leaves.slice(0, 6);

  return `
    <div class="stat-grid">
      ${renderStat('Absences', leaves.length, 'red')}
      ${renderStat('Employes', asArray(snapshot.employees).length, 'blue')}
      ${renderStat('Sites', asArray(snapshot.sites).length, 'green')}
    </div>
    ${renderRows(rows, (row) => ({
      title: textField(row, 'employeeName') || 'Employe',
      meta: textField(row, 'type') || 'conge',
      detail: `${formatDate(textField(row, 'startDate'))} - ${formatDate(textField(row, 'endDate'))}`,
    }))}
  `;
}

function renderPages(snapshot: ApiResult): string {
  const pages = asArray(snapshot.pages);

  return `
    <div class="stat-grid">
      ${renderStat('Pages', pages.length, 'red')}
      ${renderStat('Actives', pages.filter((page) => booleanField(page, 'active', true)).length, 'green')}
      ${renderStat('Menu', pages.filter((page) => booleanField(page, 'showInMenu', false)).length, 'blue')}
    </div>
    ${renderRows(pages.slice(0, 6), (row) => ({
      title: textField(row, 'title') || 'Page',
      meta: textField(row, 'slug'),
      detail: textField(row, 'excerpt') || textField(row, 'routePath'),
    }))}
  `;
}

function renderProfile(snapshot: ApiResult): string {
  const profile = recordField(snapshot, 'profile');

  return `
    <div class="profile-block">
      <span class="avatar">${escapeHtml(initials(textField(profile, 'displayName') || state.user?.name || 'MS'))}</span>
      <div>
        <h3>${escapeHtml(textField(profile, 'displayName') || state.user?.name || '')}</h3>
        <p>${escapeHtml(textField(profile, 'email') || state.user?.email || '')}</p>
      </div>
    </div>
  `;
}

function renderGeneric(snapshot: ApiResult, key: string): string {
  const items = asArray(snapshot[key]);

  return `
    <div class="stat-grid">
      ${renderStat('Elements', items.length, 'red')}
      ${renderStat('Modules', asArray(snapshot.modules).length, 'blue')}
      ${renderStat('Sites', asArray(snapshot.sites).length, 'green')}
    </div>
  `;
}

function renderStat(label: string, value: number, tone: 'red' | 'blue' | 'green'): string {
  return `
    <div class="stat-card tone-${tone}">
      <span>${escapeHtml(label)}</span>
      <strong>${value}</strong>
    </div>
  `;
}

function renderRows(rows: unknown[], mapRow: (row: unknown) => { title: string; meta: string; detail: string }): string {
  if (!rows.length) {
    return `
      <div class="empty-state">
        <strong>Aucune donnee</strong>
        <span>Synchronisation terminee.</span>
      </div>
    `;
  }

  return `
    <div class="row-list">
      ${rows
        .map((row) => {
          const mapped = mapRow(row);

          return `
            <article class="data-row">
              <div>
                <h3>${escapeHtml(mapped.title)}</h3>
                <p>${escapeHtml(mapped.meta)}</p>
              </div>
              <span>${escapeHtml(mapped.detail)}</span>
            </article>
          `;
        })
        .join('')}
    </div>
  `;
}

async function login(formData: FormData): Promise<void> {
  state.loading = true;
  state.error = '';
  state.apiBase = normalizeUrl(String(formData.get('apiBase') || ''));
  localStorage.setItem(storage.apiBase, state.apiBase);
  render();

  try {
    const result = await request<LoginResult>('/api/mobile/token', {
      method: 'POST',
      body: JSON.stringify({
        email: String(formData.get('email') || ''),
        password: String(formData.get('password') || ''),
        device_name: String(formData.get('deviceName') || defaultDeviceName()),
      }),
    }, false);

    if (!result.ok || !result.token || !result.user) {
      throw new Error(result.error || 'Connexion refusee.');
    }

    persistSession(result);
    await loadModule(state.activeSlug);
  } catch (error) {
    state.error = error instanceof Error ? error.message : 'Connexion impossible.';
    state.loading = false;
    render();
  }
}

async function logout(): Promise<void> {
  try {
    if (state.token) {
      await request<ApiResult>('/api/mobile/logout', { method: 'POST' });
    }
  } catch {
    // The local session must still be cleared if the remote token is already invalid.
  }

  clearSession();
  render();
}

async function loadModule(slug: string): Promise<void> {
  const descriptor = descriptorFor(slug);

  if (!descriptor.endpoint) {
    state.error = '';
    render();
    return;
  }

  state.loading = true;
  state.error = '';
  render();

  try {
    const result = await request<ApiResult>(descriptor.endpoint);

    if (result.ok === false) {
      throw new Error(result.error || 'Synchronisation refusee.');
    }

    state.snapshots[slug] = result;
  } catch (error) {
    state.error = error instanceof Error ? error.message : 'Synchronisation impossible.';
  } finally {
    state.loading = false;
    render();
  }
}

async function request<T extends ApiResult | LoginResult>(path: string, options: RequestInit = {}, authenticated = true): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');

  if (options.body) {
    headers.set('Content-Type', 'application/json');
  }

  if (authenticated && state.token) {
    headers.set('Authorization', `Bearer ${state.token}`);
  }

  let response: Response;

  try {
    response = await fetch(`${state.apiBase}${path}`, {
      ...options,
      headers,
    });
  } catch {
    throw new Error('Serveur indisponible.');
  }

  const payload = await response.json().catch(() => ({})) as T;

  if (response.status === 401 && authenticated) {
    clearSession();
    throw new Error('Session expiree.');
  }

  if (!response.ok) {
    throw new Error(String(payload.error || `Erreur HTTP ${response.status}`));
  }

  return payload;
}

function asArray(value: unknown): unknown[] {
  return Array.isArray(value) ? value : [];
}

function recordField(row: unknown, key: string): Record<string, unknown> {
  if (!isRecord(row)) {
    return {};
  }

  const value = row[key];

  return isRecord(value) ? value : {};
}

function textField(row: unknown, key: string): string {
  if (!isRecord(row)) {
    return '';
  }

  const value = row[key];

  return value === null || value === undefined ? '' : String(value);
}

function numberField(row: unknown, key: string): number {
  const value = Number(textField(row, key));

  return Number.isFinite(value) ? value : 0;
}

function booleanField(row: unknown, key: string, fallback: boolean): boolean {
  if (!isRecord(row)) {
    return fallback;
  }

  const value = row[key];

  return typeof value === 'boolean' ? value : fallback;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

function byId(value: unknown, labelKey: string): Map<number, string> {
  return new Map(
    asArray(value)
      .map((row) => [numberField(row, 'id'), textField(row, labelKey)] as const)
      .filter(([id]) => id > 0),
  );
}

function lookup(map: Map<number, string>, id: number): string {
  return map.get(id) || `#${id}`;
}

function formatDate(value: string): string {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value.replace('T', ' ');
  }

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
}

function defaultDeviceName(): string {
  return navigator.userAgent.includes('Android') ? 'Android' : 'Mobile';
}

function logoUrl(): string {
  return `${state.apiBase}/assets/logo/martin-sols-logo.png`;
}

function moduleIcon(slug: string, label: string): string {
  return {
    reservations: 'R',
    'locations-materiel': 'M',
    conges: 'C',
    'pages-crm': 'P',
    administration: 'A',
  }[slug] || label.charAt(0).toUpperCase();
}

function initials(value: string): string {
  const parts = value.trim().split(/\s+/).filter(Boolean);
  const first = parts[0]?.charAt(0) || 'M';
  const second = parts[1]?.charAt(0) || 'S';

  return `${first}${second}`.toUpperCase();
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
