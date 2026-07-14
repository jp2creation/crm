(function () {
  const ACCOUNT_PATH = '/pages/account-settings';
  const API_URLS = ['/api/administration', '/api/administration.php'];
  const DEFAULT_PHOTO = '/assets/logo/logomark.png';

  let cachedProfile = null;
  let pendingPhotoDataUrl = '';
  let bootScheduled = false;
  let accountMountedPath = '';
  let lastProfileError = null;
  let lastProfileErrorAt = 0;

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

    if (!force && lastProfileErrorAt && Date.now() - lastProfileErrorAt < 10000) {
      throw lastProfileError || new Error('Erreur API profil');
    }

    try {
      const data = await api('profile');
      cachedProfile = data.profile || null;
      lastProfileError = null;
      lastProfileErrorAt = 0;
    } catch (error) {
      lastProfileError = error;
      lastProfileErrorAt = Date.now();
      throw error;
    }

    if (cachedProfile) {
      hydrateHeader(cachedProfile);
    }

    return cachedProfile;
  }

  function findUserContainer() {
    const actions = document.querySelector('.layout-header .ms-auto');
    if (!actions) return null;

    const relativeChildren = Array.from(actions.children).filter((child) => {
      return child.matches('div.relative') && child.querySelector('button');
    });

    return relativeChildren[relativeChildren.length - 1] || null;
  }

  function hydrateHeader(profile) {
    if (!profile) return;

    const displayName = profile.displayName || profile.name || 'Utilisateur';
    const role = roleLabel(profile.role);

    ensureStyles();

    const nativeContainer = findUserContainer();
    if (nativeContainer) {
      nativeContainer.setAttribute('data-crm-native-profile-hidden', '1');
    }

    let overlay = document.getElementById('crm-header-profile-overlay');
    if (!overlay) {
      overlay = document.createElement('a');
      overlay.id = 'crm-header-profile-overlay';
      overlay.className = 'crm-header-profile-overlay';
      overlay.href = ACCOUNT_PATH;
      overlay.setAttribute('aria-label', 'Profil utilisateur');
      document.body.appendChild(overlay);
    }

    const src = photoUrl(profile);
    overlay.innerHTML = `
      <span class="crm-header-profile-overlay-text">
        <strong>${escapeHtml(displayName)}</strong>
        <small>${escapeHtml(role)}</small>
      </span>
      <span class="crm-header-profile-avatar" aria-hidden="true">
        <img src="${escapeHtml(src)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_PHOTO)}'" />
      </span>
    `;
  }

  function outlet() {
    let target = document.getElementById('crm-account-settings-module');
    if (target) return target;

    const container = document.querySelector('main .layout-container.layout-page')
      || document.querySelector('main');

    if (!container) return null;

    target = document.createElement('section');
    target.id = 'crm-account-settings-module';
    container.appendChild(target);

    return target;
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

      @media (min-width: 1024px) {
        html.crm-account-settings-route body:not(.crm-mobile-embed) main {
          width: calc(100vw - var(--sidebar-width, 260px)) !important;
          max-width: calc(100vw - var(--sidebar-width, 260px)) !important;
        }
      }

      html.crm-account-settings-route #crm-account-settings-module {
        display: none !important;
      }

      html.crm-account-settings-route [data-crm-native-billing-hidden="1"] {
        display: none !important;
      }

      html.crm-account-settings-route [data-crm-native-status] {
        min-height: 1.25rem;
        color: #16a34a;
        font-size: .88rem;
        font-weight: 800;
      }

      html.crm-account-settings-route input[data-crm-native-readonly="1"] {
        background: #f8fafc !important;
        color: #64748b !important;
        cursor: not-allowed;
      }

      .layout-header [data-crm-native-profile-hidden="1"] {
        visibility: hidden !important;
        pointer-events: none !important;
      }

      .crm-header-profile-overlay {
        position: fixed;
        top: .8rem;
        right: 1rem;
        z-index: 1200;
        display: inline-flex;
        align-items: center;
        gap: .65rem;
        min-height: 3rem;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 999px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .12);
        color: #1f3349;
        padding: .35rem .4rem .35rem .8rem;
        text-decoration: none;
        backdrop-filter: blur(12px);
      }

      .crm-header-profile-overlay-text {
        display: grid;
        gap: .05rem;
        min-width: 0;
        text-align: right;
      }

      .crm-header-profile-overlay-text strong {
        max-width: 10rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #1f3349;
        font-size: .88rem;
        font-weight: 850;
        line-height: 1.1;
      }

      .crm-header-profile-overlay-text small {
        color: #64748b;
        font-size: .72rem;
        font-weight: 750;
        line-height: 1.1;
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

      @media (max-width: 640px) {
        .crm-header-profile-overlay {
          top: .72rem;
          right: .72rem;
          min-height: 2.65rem;
          padding: .2rem;
        }

        .crm-header-profile-overlay-text {
          display: none;
        }
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
              <img src="${escapeHtml(src)}" alt="${escapeHtml(profile.displayName)}" onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_PHOTO)}'" />
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
                  <img data-crm-account-preview src="${escapeHtml(src)}" alt="${escapeHtml(profile.displayName)}" onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_PHOTO)}'" />
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
                <label for="crm-account-phone">Téléphone</label>
                <input id="crm-account-phone" name="phone" type="tel" value="${escapeHtml(profile.phone || '')}" />
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
    accountMountedPath = window.location.pathname;
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
        phone: formData.get('phone'),
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

  function nativeAccountRoot() {
    return document.querySelector('main .layout-container.layout-page > .space-y-6');
  }

  function textOf(node) {
    return String(node?.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function findByText(root, selector, needle) {
    return Array.from(root.querySelectorAll(selector))
      .find((node) => textOf(node).toLowerCase() === needle.toLowerCase()) || null;
  }

  function nativeField(root, labelText) {
    const label = findByText(root, 'label', labelText);
    if (!label) return null;

    const wrapper = label.parentElement;
    return wrapper?.querySelector('input, textarea') || null;
  }

  function setNativeValue(control, value) {
    if (!control) return;

    const prototype = control.tagName === 'TEXTAREA'
      ? window.HTMLTextAreaElement.prototype
      : window.HTMLInputElement.prototype;
    const descriptor = Object.getOwnPropertyDescriptor(prototype, 'value');

    if (descriptor?.set) {
      descriptor.set.call(control, String(value ?? ''));
    } else {
      control.value = String(value ?? '');
    }

    control.dispatchEvent(new Event('input', { bubbles: true }));
    control.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function setNativeReadonly(control, readonly) {
    if (!control) return;

    control.toggleAttribute('readonly', Boolean(readonly));
    control.dataset.crmNativeReadonly = readonly ? '1' : '0';
  }

  function ensureNativeStatus(root) {
    let status = root.querySelector('[data-crm-native-status]');
    if (status) return status;

    const title = findByText(root, 'h2', 'Informations personnelles');
    const header = title?.parentElement;

    status = document.createElement('div');
    status.dataset.crmNativeStatus = '1';

    if (header) {
      header.appendChild(status);
    }

    return status;
  }

  function setNativeStatus(root, message, error) {
    const status = ensureNativeStatus(root);
    if (!status) return;

    status.textContent = message || '';
    status.style.color = error ? '#b91c1c' : '#16a34a';
  }

  function hideNativeBillingTab(root) {
    Array.from(root.querySelectorAll('button')).forEach((button) => {
      if (textOf(button) === 'Facturation') {
        button.dataset.crmNativeBillingHidden = '1';
      }
    });
  }

  function ensureNativePhoneField(root) {
    const existing = nativeField(root, 'Téléphone');
    if (existing) return existing;

    const email = nativeField(root, 'Adresse e-mail');
    const bio = nativeField(root, 'Bio');
    const reference = bio?.parentElement || email?.parentElement;

    if (!reference?.parentElement) return null;

    const wrapper = document.createElement('div');
    wrapper.className = reference.className || 'md:col-span-2';
    wrapper.dataset.crmNativePhoneField = '1';
    wrapper.innerHTML = `
      <label class="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-1.5">Téléphone</label>
      <input class="${escapeHtml(email?.className || '')}" type="tel" />
    `;

    reference.parentElement.insertBefore(wrapper, reference);

    return wrapper.querySelector('input');
  }

  function nativePhotoImage(root) {
    const label = findByText(root, 'label', 'Photo de profil');
    return label?.parentElement?.querySelector('img') || root.querySelector('img[alt="Profile"]');
  }

  function nativePhotoButton(root) {
    return Array.from(root.querySelectorAll('button'))
      .find((button) => textOf(button).includes('Télécharger une nouvelle photo')) || null;
  }

  function nativeActionButton(root, labelPart) {
    return Array.from(root.querySelectorAll('button'))
      .find((button) => textOf(button).includes(labelPart)) || null;
  }

  function bindNativeAccountEvents(root, profile) {
    const photoButton = nativePhotoButton(root);
    const preview = nativePhotoImage(root);
    const saveButton = nativeActionButton(root, 'Enregistrer');
    const resetButton = nativeActionButton(root, 'Annuler');

    if (photoButton && !photoButton.dataset.crmNativeBound) {
      photoButton.dataset.crmNativeBound = '1';

      let input = root.querySelector('[data-crm-native-photo-input]');
      if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.hidden = true;
        input.dataset.crmNativePhotoInput = '1';
        photoButton.insertAdjacentElement('afterend', input);
      }

      photoButton.addEventListener('click', (event) => {
        event.preventDefault();
        input.click();
      });

      input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (!file) return;

        try {
          pendingPhotoDataUrl = await readFileAsDataUrl(file);
          if (preview) preview.src = pendingPhotoDataUrl;
          setNativeStatus(root, 'Photo prête à enregistrer.');
        } catch (error) {
          setNativeStatus(root, error.message || 'Photo illisible', true);
        }
      });
    }

    if (resetButton && !resetButton.dataset.crmNativeBound) {
      resetButton.dataset.crmNativeBound = '1';
      resetButton.addEventListener('click', (event) => {
        event.preventDefault();
        pendingPhotoDataUrl = '';
        hydrateNativeAccountPage(profile);
      });
    }

    if (saveButton && !saveButton.dataset.crmNativeBound) {
      saveButton.dataset.crmNativeBound = '1';
      saveButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const payload = {
          firstName: nativeField(root, 'Prénom')?.value || '',
          lastName: nativeField(root, 'Nom')?.value || '',
          email: nativeField(root, 'Adresse e-mail')?.value || '',
          phone: nativeField(root, 'Téléphone')?.value || '',
          bio: nativeField(root, 'Bio')?.value || '',
        };

        if (pendingPhotoDataUrl) {
          payload.photoDataUrl = pendingPhotoDataUrl;
        }

        try {
          saveButton.disabled = true;
          setNativeStatus(root, 'Enregistrement...');
          const data = await api('save_profile', payload);
          pendingPhotoDataUrl = '';
          cachedProfile = data.profile;
          hydrateHeader(cachedProfile);
          hydrateNativeAccountPage(cachedProfile, 'Profil enregistré.');
        } catch (error) {
          setNativeStatus(root, error.message || 'Erreur pendant l’enregistrement', true);
        } finally {
          saveButton.disabled = false;
        }
      });
    }
  }

  function hydrateNativeAccountPage(profile, status) {
    if (!isAccountRoute()) return false;

    const root = nativeAccountRoot();
    if (!root || !profile) return false;

    ensureStyles();
    hideNativeBillingTab(root);

    const canEditIdentity = profile.canEditIdentity !== false;
    const src = pendingPhotoDataUrl || photoUrl(profile);
    const image = nativePhotoImage(root);

    if (image) {
      image.src = src;
      image.alt = profile.displayName || profile.name || 'Profil';
      image.onerror = () => {
        image.onerror = null;
        image.src = DEFAULT_PHOTO;
      };
    }

    setNativeValue(nativeField(root, 'Prénom'), profile.firstName || '');
    setNativeValue(nativeField(root, 'Nom'), profile.lastName || '');
    setNativeValue(nativeField(root, 'Adresse e-mail'), profile.email || '');
    setNativeValue(ensureNativePhoneField(root), profile.phone || '');
    setNativeValue(nativeField(root, 'Bio'), profile.bio || '');
    setNativeReadonly(nativeField(root, 'Prénom'), !canEditIdentity);
    setNativeReadonly(nativeField(root, 'Nom'), !canEditIdentity);
    if (status !== undefined) {
      setNativeStatus(root, status || '');
    }
    bindNativeAccountEvents(root, profile);

    return true;
  }

  async function mountAccountPage(forceRender) {
    syncRouteClass();

    if (!isAccountRoute()) return;

    ensureStyles();

    try {
      const profile = await loadProfile(forceRender);
      hydrateNativeAccountPage(profile);
    } catch (error) {
      const root = nativeAccountRoot();
      if (root) {
        setNativeStatus(root, error.message || 'Impossible de charger le compte.', true);
      }
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

      mountAccountPage(forceProfile);
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
