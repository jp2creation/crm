(function () {
  var storageKey = 'crm:active-site-id';
  var eventName = 'crm:active-site-changed';
  var rootId = 'crm-active-site-switcher';
  var styleId = 'crm-active-site-style';
  var state = {
    loading: false,
    loaded: false,
    sites: [],
    activeSiteId: numberOrNull(readStorage()),
  };

  function numberOrNull(value) {
    var number = Number(value || 0);
    return Number.isFinite(number) && number > 0 ? number : null;
  }

  function siteById(siteId) {
    return state.sites.find(function (site) {
      return Number(site.id) === Number(siteId);
    }) || null;
  }

  function setSiteId(siteId, options) {
    var next = numberOrNull(siteId);

    if (!next || state.activeSiteId === next) {
      renderSwitcher();
      return state.activeSiteId;
    }

    state.activeSiteId = next;
    writeStorage(next);
    renderSwitcher();

    if (!options || options.silent !== true) {
      window.dispatchEvent(new CustomEvent(eventName, {
        detail: {
          siteId: next,
          site: siteById(next),
        },
      }));
    }

    return next;
  }

  function allowedSitesFromPayload(payload) {
    var sites = Array.isArray(payload && payload.sites) ? payload.sites : [];
    var allowedIds = Array.isArray(payload && payload.user && payload.user.siteIds)
      ? payload.user.siteIds.map(Number)
      : [];

    var activeSites = sites.filter(function (site) {
      return site && site.active !== false;
    });

    if (!allowedIds.length) {
      return activeSites;
    }

    return activeSites.filter(function (site) {
      return allowedIds.includes(Number(site.id));
    });
  }

  async function fetchBootstrap(url) {
    var response = await fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error('Bootstrap unavailable');
    }

    return response.json();
  }

  async function loadSites() {
    if (state.loading || state.loaded) {
      return;
    }

    state.loading = true;

    var urls = [
      '/api/reservations.php?action=bootstrap',
      '/api/equipment-rentals.php?action=bootstrap',
      '/api/administration.php?action=bootstrap',
    ];

    for (var index = 0; index < urls.length; index += 1) {
      try {
        var payload = await fetchBootstrap(urls[index]);
        var sites = allowedSitesFromPayload(payload);

        if (sites.length) {
          state.sites = sites;
          state.loaded = true;
          state.loading = false;

          var saved = numberOrNull(readStorage());
          var savedAllowed = saved && sites.some(function (site) {
            return Number(site.id) === saved;
          });

          setSiteId(savedAllowed ? saved : Number(sites[0].id), { silent: true });
          renderSwitcher();
          return;
        }
      } catch (error) {
        // Try the next CRM API.
      }
    }

    state.loaded = true;
    state.loading = false;
    renderSwitcher();
  }

  function ensureStyle() {
    if (document.getElementById(styleId)) {
      return;
    }

    var style = document.createElement('style');
    style.id = styleId;
    style.textContent = [
      '.crm-active-site-switcher{display:flex;align-items:center;gap:.35rem;min-width:0;max-width:36vw}',
      '.crm-active-site-label{font-size:.72rem;font-weight:800;line-height:1;text-transform:uppercase;color:var(--color-secondary-500,#64748b);white-space:nowrap}',
      '.crm-active-site-select{height:2.2rem;min-width:0;width:100%;max-width:8rem;border:1px solid var(--color-surface-200,#e2e8f0);border-radius:.55rem;background:var(--color-surface-50,#f8fafc);padding:0 1.7rem 0 .65rem;font-size:.78rem;font-weight:800;color:var(--color-secondary-900,#0f172a);outline:none;text-overflow:ellipsis}',
      '.crm-active-site-select:hover,.crm-active-site-select:focus{border-color:rgb(var(--theme-primary,149 0 46) / .55);box-shadow:0 0 0 3px rgb(var(--theme-primary,149 0 46) / .12)}',
      '.dark .crm-active-site-select{border-color:var(--color-surface-700,#334155);background:var(--color-surface-800,#1e293b);color:#fff}',
      '@media (max-width:767px){.crm-active-site-label{display:none}.crm-active-site-switcher{flex:1 1 auto;max-width:7.8rem}.crm-active-site-select{max-width:7.8rem}}',
      '@media (min-width:768px){.crm-active-site-switcher{gap:.45rem;flex:0 0 auto;max-width:none}.crm-active-site-select{height:2.35rem;min-width:9.5rem;max-width:13rem;padding:0 2rem 0 .8rem;font-size:.84rem}}',
    ].join('');
    document.head.appendChild(style);
  }

  function headerActions() {
    return document.querySelector('.layout-header .ms-auto');
  }

  function renderSwitcher() {
    if (!document.body) {
      return;
    }

    ensureStyle();

    var host = document.getElementById(rootId);
    var actions = headerActions();

    if (!actions || !state.sites.length) {
      if (host) {
        host.remove();
      }
      return;
    }

    if (!host) {
      host = document.createElement('label');
      host.id = rootId;
      host.className = 'crm-active-site-switcher';
      host.innerHTML = '<span class="crm-active-site-label">Site</span><select class="crm-active-site-select" aria-label="Changer de site"></select>';

      var divider = actions.querySelector('.header-actions-divider');
      actions.insertBefore(host, divider || actions.firstChild);

      host.querySelector('select').addEventListener('change', function (event) {
        setSiteId(event.target.value);
      });
    }

    var select = host.querySelector('select');
    var options = state.sites.map(function (site) {
      return '<option value="' + String(site.id) + '">' + escapeHtml(site.name || site.slug || ('Site #' + site.id)) + '</option>';
    }).join('');

    if (select.innerHTML !== options) {
      select.innerHTML = options;
    }

    select.value = String(state.activeSiteId || state.sites[0].id);
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  window.CRM_ACTIVE_SITE = {
    getSiteId: function () {
      return state.activeSiteId || numberOrNull(readStorage());
    },
    setSiteId: function (siteId) {
      return setSiteId(siteId);
    },
    getSites: function () {
      return state.sites.slice();
    },
    reload: function () {
      state.loaded = false;
      state.loading = false;
      return loadSites();
    },
  };

  function readStorage() {
    try {
      return window.localStorage.getItem(storageKey);
    } catch (error) {
      return null;
    }
  }

  function writeStorage(siteId) {
    try {
      window.localStorage.setItem(storageKey, String(siteId));
    } catch (error) {
      // Keeping the site in memory is enough for the current page.
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    loadSites();
    renderSwitcher();

    var observer = new MutationObserver(function () {
      renderSwitcher();
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  });
})();
