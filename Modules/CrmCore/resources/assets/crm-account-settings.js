(function () {
  const ACCOUNT_PATH = '/pages/account-settings';
  const API_URLS = ['/api/administration', '/api/administration.php'];
  const DEFAULT_PHOTO = '/assets/logo/logomark.png';

  let cachedProfile = null;
  let pendingPhotoDataUrl = '';
  let bootScheduled = false;

  function isAccountRoute() {
    return window.location.pathname.replace(/\/+$/, '') === ACCOUNT_PATH;
  }

  function syncRouteClass() {
    const active = isAccountRoute();

    document.documentElement.classList.toggle('crm-account-settings-route', active);
    document.body?.classList.toggle('crm-account-settings-route', active);
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function roleLabel(role) {
    const labels = {
      admin: 'Admin',
      responsable: 'Responsable',
      user: 'Utilisateur',
      blocked: 'Sans acc\u00e8s',
    };

    return labels[role] || 'Utilisateur';
  }

  function initials(profile) {
    const source = profile?.displayName || profile?.name || 'Utilisateur';
    const parts = source
      .split(/\s+/)
      .map((part) => part.trim())
      .filter(Boolean);

    if (parts.length === 0) return 'U';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

    return `${parts[0][0] || ''}${parts[parts.length - 1][0] || ''}`.toUpperCase();
  }

  function photoUrl(profile) {
    return profile?.photoUrl || DEFAULT_PHOTO;
  }

  async function api(action, payload) {
    const options = {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
      },
      method: payload ? 'POST' : 'GET',
    };

    if (payload) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(payload);
    }

    let lastError = null;

    for (const url of API_URLS) {
      try {
        const response = await fetch(`${url}?action=${encodeURIComponent(action)}`, options);
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.ok === false) {
          throw new Error(data.error || 'Erreur API profil');
        }

        return data;
      } catch (error) {
        lastError = error;
      }
    }

    throw lastError || new Error('Erreur API profil');
  }

  async function loadProfile(force) {
    if (cachedProfile && !force) return cachedProfile;

    const data = await api('profile');
    cachedProfile = data.profile || null;

    if (cachedProfile) {
      hydrateHeader(cachedProfile);
    }

    return cachedProfile;
  }

  function findUserButton() {
    const actions = document.querySelector('.layout-header .ms-auto');
    if (!actions) return null;

    const relativeChildren = Array.from(actions.children).filter((child) => {
      return child.matches('div.relative') && child.querySelector('button');
    });

    const userContainer = relativeChildren[relativeChildren.length - 1];

    return userContainer ? userContainer.querySelector('button') : null;
  }

  function findAvatar(button) {
    if (!button) return null;

    return Array.from(button.querySelectorAll('div')).find((node) => {
      const classes = String(node.className || '');

      return classes.includes('rounded-full') && classes.includes('h-9') && classes.includes('w-9');
    });
  }

  function hydrateHeader(profile) {
    const button = findUserButton();
    if (!button || !profile) return;

    const lines = Array.from(button.querySelectorAll('p'));
    const displayName = profile.displayName || profile.name || 'Utilisateur';
    const role = roleLabel(profile.role);

    if (lines[0] && lines[0].textContent !== displayName) {
      lines[0].textContent = displayName;
    }

    if (lines[1] && lines[1].textContent !== role) {
      lines[1].textContent = role;
    }

    ensureStyles();

    const avatar = findAvatar(button);
    if (!avatar) return;

    const src = photoUrl(profile);
    if (avatar.dataset.crmPhotoUrl === src && avatar.dataset.crmInitials === initials(profile)) return;

    avatar.dataset.crmPhotoUrl = src;
    avatar.dataset.crmInitials = initials(profile);
    avatar.classList.add('crm-header-profile-avatar');
    avatar.innerHTML = `
      <img src="${escapeHtml(src)}" alt="${escapeHtml(displayName)}" onerror="this.remove(); this.parentElement.textContent='${escapeHtml(initials(profile))}'" />
    `;
  }

  function outlet() {
    return document.getElementById('crm-account-settings-module')
      || document.querySelector('main .layout-container.layout-page')
      || document.querySelector('main')
      || document.getElementById('root');
  }

  function ensureStyles() {
    if (document.getElementById('crm-account-settings-style')) return;

    const style = document.createElement('style');
    style.id = 'crm-account-settings-style';
    style.textContent = `
      html.crm-account-settings-route,
      body.crm-account-settings-route {
        overflow-x: hidden;
      }

      html.crm-account-settings-route main,
      html.crm-account-settings-route .layout-container.layout-page {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
      }

      html.crm-account-settings-route .layout-container.layout-page > :not(.crm-account-shell):not(#crm-account-settings-module) {
        display: none !important;
      }

      .crm-header-profile-avatar {
        width: 2.25rem !important;
        height: 2.25rem !important;
        min-width: 2.25rem !important;
        min-height: 2.25rem !important;
        aspect-ratio: 1 / 1;
        flex: 0 0 2.25rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
        border-radius: 9999px !important;
        background: #fff !important;
        color: #95002e !important;
        box-shadow: inset 0 0 0 1px rgba(149, 0, 46, .12);
      }

      .crm-header-profile-avatar img {
        width: 100% !important;
        height: 100% !important;
        min-width: 100%;
        min-height: 100%;
        object-fit: cover;
        display: block;
        border-radius: inherit !important;
        overflow: hidden;
      }

      .layout-header .ms-auto button .crm-header-profile-avatar,
      .layout-header .ms-auto button .crm-header-profile-avatar img {
        border-radius: 9999px !important;
      }

      .crm-account-shell {
        width: 100%;
        max-width: 980px;
        min-width: 0;
        margin: 0 auto;
        padding: 1.5rem 0 2rem;
        color: #24364b;
        box-sizing: border-box;
      }

      .crm-account-shell *,
      .crm-account-shell *::before,
      .crm-account-shell *::after {
        box-sizing: border-box;
      }

      .crm-account-grid {
        display: grid;
        grid-template-columns: minmax(0, .78fr) minmax(320px, 1.22fr);
        gap: 1rem;
        align-items: start;
        min-width: 0;
      }

      .crm-account-card {
        min-width: 0;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
      }

      .crm-account-summary {
        padding: 1.35rem;
        position: sticky;
        top: 6.5rem;
      }

      .crm-account-avatar {
        width: 88px;
        height: 88px;
        overflow: hidden;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(149, 0, 46, .15);
        box-shadow: 0 12px 26px rgba(149, 0, 46, .14);
      }

      .crm-account-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
      }

      .crm-account-eyebrow {
        margin: 1rem 0 .25rem;
        color: #95002e;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
      }

      .crm-account-summary h1 {
        margin: 0;
        color: #1f3349;
        font-size: clamp(1.55rem, 2vw, 2rem);
        line-height: 1.05;
        letter-spacing: 0;
        overflow-wrap: anywhere;
      }

      .crm-account-summary p {
        margin: .65rem 0 0;
        color: #64748b;
        line-height: 1.5;
      }

      .crm-account-role {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-top: 1.1rem;
        padding: .45rem .7rem;
        border-radius: 999px;
        color: #95002e;
        background: rgba(149, 0, 46, .08);
        font-size: .85rem;
        font-weight: 800;
      }

      .crm-account-role::before {
        content: "";
        width: .55rem;
        height: .55rem;
        border-radius: 999px;
        background: #95002e;
      }

      .crm-account-form {
        overflow: hidden;
        min-width: 0;
      }

      .crm-account-form-header,
      .crm-account-form-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid rgba(148, 163, 184, .18);
      }

      .crm-account-form-footer {
        border-top: 1px solid rgba(148, 163, 184, .18);
        border-bottom: 0;
        justify-content: flex-end;
        background: #f8fafc;
      }

      .crm-account-form-header h2 {
        margin: 0;
        color: #1f3349;
        font-size: 1.25rem;
        letter-spacing: 0;
      }

      .crm-account-status {
        min-height: 1.2rem;
        color: #16a34a;
        font-size: .88rem;
        font-weight: 700;
      }

      .crm-account-form-body {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.25rem;
        min-width: 0;
      }

      .crm-account-field {
        display: grid;
        gap: .45rem;
        min-width: 0;
      }

      .crm-account-field.crm-account-field-wide {
        grid-column: 1 / -1;
      }

      .crm-account-field label {
        color: #334155;
        font-size: .9rem;
        font-weight: 800;
      }

      .crm-account-field input,
      .crm-account-field textarea {
        width: 100%;
        min-width: 0;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 14px;
        background: #fff;
        color: #1f3349;
        font: inherit;
        font-weight: 650;
        outline: none;
        padding: .85rem .95rem;
        transition: border-color .18s ease, box-shadow .18s ease;
      }

      .crm-account-field textarea {
        min-height: 104px;
        resize: vertical;
      }

      .crm-account-field input:focus,
      .crm-account-field textarea:focus {
        border-color: rgba(149, 0, 46, .75);
        box-shadow: 0 0 0 4px rgba(149, 0, 46, .12);
      }

      .crm-account-field input[readonly] {
        background: #f8fafc;
        color: #64748b;
      }

      .crm-account-photo-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .9rem;
        grid-column: 1 / -1;
        padding: .8rem;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 16px;
        background: #f8fafc;
      }

      .crm-account-photo-row .crm-account-avatar {
        width: 64px;
        height: 64px;
        box-shadow: none;
        flex: 0 0 auto;
      }

      .crm-account-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 12px;
        background: #fff;
        color: #334155;
        cursor: pointer;
        font: inherit;
        font-weight: 850;
        padding: .7rem 1rem;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        text-align: center;
      }

      .crm-account-btn:hover {
        border-color: rgba(149, 0, 46, .35);
        box-shadow: 0 12px 24px rgba(15, 23, 42, .08);
        transform: translateY(-1px);
      }

      .crm-account-btn-primary {
        border-color: #95002e;
        background: #b0003a;
        color: #fff;
      }

      .crm-account-btn[disabled] {
        cursor: wait;
        opacity: .7;
        transform: none;
      }

      .crm-account-error {
        padding: 1rem 1.25rem;
        color: #b91c1c;
        font-weight: 800;
      }

      @media (max-width: 1180px) {
        .crm-account-shell {
          padding: .85rem 0 1.5rem;
        }

        .crm-account-grid {
          grid-template-columns: 1fr;
        }

        .crm-account-summary {
          position: static;
        }

        .crm-account-form-body {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 640px) {
        .crm-account-form-footer {
          flex-direction: column-reverse;
          align-items: stretch;
        }

        .crm-account-photo-row {
          align-items: flex-start;
        }

        .crm-account-photo-row > div:last-child,
        .crm-account-photo-row .crm-account-btn {
          width: 100%;
        }
      }
    `;

    document.head.appendChild(style);
  }

  function loadingMarkup() {
    return `
      <section class="crm-account-shell">
        <div class="crm-account-card crm-account-error">Chargement du compte...</div>
      </section>
    `;
  }

  function errorMarkup(message) {
    return `
      <section class="crm-account-shell">
        <div class="crm-account-card crm-account-error">${escapeHtml(message)}</div>
      </section>
    `;
  }

  function accountMarkup(profile, status) {
    const src = pendingPhotoDataUrl || photoUrl(profile);
    const canEditIdentity = profile.canEditIdentity !== false;
    const readOnly = canEditIdentity ? '' : 'readonly';

    return `
      <section class="crm-account-shell">
        <div class="crm-account-grid">
          <aside class="crm-account-card crm-account-summary">
            <div class="crm-account-avatar">
              <img src="${escapeHtml(src)}" alt="${escapeHtml(profile.displayName)}" />
            </div>
            <p class="crm-account-eyebrow">Compte utilisateur</p>
            <h1>${escapeHtml(profile.displayName || profile.name || 'Utilisateur')}</h1>
            <p>Ces informations alimentent la photo, le nom et le r\u00f4le affich\u00e9s dans le menu du haut.</p>
            <span class="crm-account-role">${escapeHtml(roleLabel(profile.role))}</span>
          </aside>

          <form class="crm-account-card crm-account-form" data-crm-account-form>
            <div class="crm-account-form-header">
              <h2>Informations personnelles</h2>
              <span class="crm-account-status" data-crm-account-status>${escapeHtml(status || '')}</span>
            </div>

            <div class="crm-account-form-body">
              <div class="crm-account-photo-row">
                <div class="crm-account-avatar">
                  <img data-crm-account-preview src="${escapeHtml(src)}" alt="${escapeHtml(profile.displayName)}" />
                </div>
                <div>
                  <button class="crm-account-btn" type="button" data-crm-account-photo-button>Changer la photo</button>
                  <input type="file" accept="image/*" hidden data-crm-account-photo-input />
                </div>
              </div>

              <div class="crm-account-field">
                <label for="crm-account-first-name">Pr\u00e9nom</label>
                <input id="crm-account-first-name" name="firstName" value="${escapeHtml(profile.firstName || '')}" ${readOnly} />
              </div>

              <div class="crm-account-field">
                <label for="crm-account-last-name">Nom</label>
                <input id="crm-account-last-name" name="lastName" value="${escapeHtml(profile.lastName || '')}" ${readOnly} />
              </div>

              <div class="crm-account-field crm-account-field-wide">
                <label for="crm-account-email">Adresse e-mail</label>
                <input id="crm-account-email" name="email" type="email" value="${escapeHtml(profile.email || '')}" />
              </div>

              <div class="crm-account-field crm-account-field-wide">
                <label for="crm-account-bio">Bio</label>
                <textarea id="crm-account-bio" name="bio" maxlength="255">${escapeHtml(profile.bio || '')}</textarea>
              </div>
            </div>

            <div class="crm-account-form-footer">
              <button class="crm-account-btn" type="button" data-crm-account-reset>Annuler</button>
              <button class="crm-account-btn crm-account-btn-primary" type="submit" data-crm-account-submit>Enregistrer</button>
            </div>
          </form>
        </div>
      </section>
    `;
  }

  function renderAccount(profile, status) {
    const target = outlet();
    if (!target) return false;

    ensureStyles();
    target.dataset.crmAccountSettingsMounted = '1';
    target.innerHTML = accountMarkup(profile, status);
    bindAccountEvents(target, profile);

    return true;
  }

  function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Photo illisible'));
      reader.readAsDataURL(file);
    });
  }

  function setStatus(target, message, error) {
    const node = target.querySelector('[data-crm-account-status]');
    if (!node) return;

    node.textContent = message || '';
    node.style.color = error ? '#b91c1c' : '#16a34a';
  }

  function bindAccountEvents(target, profile) {
    const form = target.querySelector('[data-crm-account-form]');
    const photoButton = target.querySelector('[data-crm-account-photo-button]');
    const photoInput = target.querySelector('[data-crm-account-photo-input]');
    const preview = target.querySelector('[data-crm-account-preview]');
    const resetButton = target.querySelector('[data-crm-account-reset]');
    const submitButton = target.querySelector('[data-crm-account-submit]');

    photoButton?.addEventListener('click', () => photoInput?.click());

    photoInput?.addEventListener('change', async () => {
      const file = photoInput.files && photoInput.files[0];
      if (!file) return;

      try {
        pendingPhotoDataUrl = await readFileAsDataUrl(file);
        if (preview) preview.src = pendingPhotoDataUrl;
        setStatus(target, 'Photo pr\u00eate \u00e0 enregistrer.');
      } catch (error) {
        setStatus(target, error.message || 'Photo illisible', true);
      }
    });

    resetButton?.addEventListener('click', () => {
      pendingPhotoDataUrl = '';
      renderAccount(profile);
    });

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();

      const formData = new FormData(form);
      const payload = {
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        bio: formData.get('bio'),
      };

      if (pendingPhotoDataUrl) {
        payload.photoDataUrl = pendingPhotoDataUrl;
      }

      try {
        if (submitButton) submitButton.disabled = true;
        setStatus(target, 'Enregistrement...');
        const data = await api('save_profile', payload);
        pendingPhotoDataUrl = '';
        cachedProfile = data.profile;
        hydrateHeader(cachedProfile);
        renderAccount(cachedProfile, 'Profil enregistr\u00e9.');
      } catch (error) {
        setStatus(target, error.message || 'Erreur pendant l\u2019enregistrement', true);
      } finally {
        if (submitButton) submitButton.disabled = false;
      }
    });
  }

  async function mountAccountPage() {
    syncRouteClass();

    if (!isAccountRoute()) return;

    const target = outlet();
    if (!target) return;

    ensureStyles();

    if (!cachedProfile) {
      target.dataset.crmAccountSettingsMounted = '1';
      target.innerHTML = loadingMarkup();
    }

    try {
      const profile = await loadProfile();
      renderAccount(profile);
    } catch (error) {
      target.dataset.crmAccountSettingsMounted = '1';
      target.innerHTML = errorMarkup(error.message || 'Impossible de charger le compte.');
    }
  }

  function scheduleBoot(forceProfile) {
    syncRouteClass();

    if (bootScheduled) return;

    bootScheduled = true;
    window.setTimeout(async () => {
      bootScheduled = false;
      syncRouteClass();

      if (document.querySelector('.layout-header')) {
        try {
          const profile = await loadProfile(forceProfile);
          hydrateHeader(profile);
        } catch (error) {
          // The header can be present before the session API is ready.
        }
      }

      mountAccountPage();
    }, 80);
  }

  document.addEventListener('DOMContentLoaded', () => scheduleBoot());
  document.addEventListener('click', () => scheduleBoot(), true);
  window.addEventListener('popstate', () => scheduleBoot());

  if (!window.__crmAccountSettingsRouteWatcher) {
    window.__crmAccountSettingsRouteWatcher = true;

    ['pushState', 'replaceState'].forEach((method) => {
      const original = history[method];
      history[method] = function (...args) {
        const result = original.apply(this, args);
        scheduleBoot(true);
        return result;
      };
    });
  }

  const observer = new MutationObserver(() => scheduleBoot());
  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
