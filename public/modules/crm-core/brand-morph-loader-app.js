(() => {
    const loader = document.getElementById('brand-morph-loader');

    if (!loader) return;

    const startedAt = window.performance ? performance.now() : Date.now();
    const standaloneQuery = '(display-mode: standalone)';
    const isStandalone =
        (window.matchMedia && window.matchMedia(standaloneQuery).matches) ||
        window.navigator.standalone === true;
    const maxStartupWait = isStandalone ? 9000 : 7000;
    const minimumStartupMs = isStandalone ? 950 : 520;
    const minimumRouteMs = isStandalone ? 650 : 420;
    const maxRouteWait = isStandalone ? 9000 : 7000;
    const transitionPollMs = 120;
    const loadingTextPattern = /(Chargement|Loading)/i;
    const loadingErrorPattern = /(Chargement impossible|Loading failed)/i;
    const styleId = 'brand-morph-loader-app-style';
    let hideTimer = null;
    let rootObserver = null;
    let monitoredUntil = startedAt + maxStartupWait;

    function now() {
        return window.performance ? performance.now() : Date.now();
    }

    function showNow() {
        ensureAppStyle();
        clearTimeout(hideTimer);
        loader.classList.add('is-visible');
        loader.setAttribute('aria-hidden', 'false');
    }

    function ensureAppStyle() {
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');

        style.id = styleId;
        style.textContent = ''
            + '#brand-morph-loader.is-visible .brand-morph-loader__backdrop{background:#f5f7fb;backdrop-filter:none;-webkit-backdrop-filter:none;}'
            + '.dark #brand-morph-loader.is-visible .brand-morph-loader__backdrop{background:#0f172a;}'
            + '#brand-morph-loader.is-visible ~ #root{opacity:0!important;pointer-events:none!important;}'
            + '#brand-morph-loader.is-visible ~ #root [class*="loading"],'
            + '#brand-morph-loader.is-visible ~ #root [class*="spinner"],'
            + '#brand-morph-loader.is-visible ~ #root [class~="animate-spin"],'
            + '#brand-morph-loader.is-visible ~ #root [role="progressbar"],'
            + '#brand-morph-loader.is-visible ~ #root [aria-busy="true"],'
            + '#brand-morph-loader.is-visible ~ #root [id^="crm-"][id$="-root"]{opacity:0!important;color:transparent!important;text-shadow:none!important;}';

        document.head.appendChild(style);
    }

    function forceHide() {
        clearTimeout(hideTimer);

        if (window.BrandMorphLoader && typeof window.BrandMorphLoader.forceHide === 'function') {
            window.BrandMorphLoader.forceHide();
            return;
        }

        loader.classList.remove('is-visible');
        loader.setAttribute('aria-hidden', 'true');
    }

    function appIsReady() {
        const root = document.getElementById('root');

        return !root || root.childElementCount > 0 || now() - startedAt > maxStartupWait;
    }

    function isVisible(element) {
        const rect = element.getBoundingClientRect();

        if (!rect.width || !rect.height) return false;

        const style = window.getComputedStyle(element);

        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
    }

    function isSpinnerCandidate(element) {
        if (element.closest('button, a, [role="button"]')) return false;
        if (element.getAttribute('role') === 'progressbar') return true;
        if (element.getAttribute('aria-busy') === 'true') return true;

        return Array.from(element.classList || []).some((className) => {
            return className === 'animate-spin' || /spinner|loader/i.test(className);
        });
    }

    function hasBlockingLoader() {
        const root = document.getElementById('root');

        if (!root) return false;

        const text = root.textContent || '';

        const hasLoadingText = loadingTextPattern.test(text) && !loadingErrorPattern.test(text);

        if (hasLoadingText) {
            return true;
        }

        const candidates = root.querySelectorAll([
            '[class*="loading"]',
            '[class*="Loading"]',
            '[class*="spinner"]',
            '[class~="animate-spin"]',
            '[role="progressbar"]',
            '[aria-busy="true"]',
            '[class*="module-host"]',
            '[id*="-module"]',
            '[id^="crm-"][id$="-root"]',
            '[class*="crm-empty"]',
            '[class*="crm-card"]'
        ].join(', '));

        for (const candidate of candidates) {
            if (loader.contains(candidate) || !isVisible(candidate)) continue;

            const candidateText = candidate.textContent || '';

            if (hasLoadingText && loadingTextPattern.test(candidateText) && !loadingErrorPattern.test(candidateText)) {
                return true;
            }

            if (isSpinnerCandidate(candidate)) {
                return true;
            }
        }

        return false;
    }

    function monitorTransition(transitionStartedAt, maxWait) {
        monitoredUntil = Math.max(monitoredUntil, transitionStartedAt + maxWait);
    }

    function observeLoadingMutations() {
        if (rootObserver || !window.MutationObserver) return;

        rootObserver = new MutationObserver(() => {
            if (now() > monitoredUntil || loader.classList.contains('is-visible')) return;
            if (!hasBlockingLoader()) return;

            const transitionStartedAt = now();

            showNow();
            monitorTransition(transitionStartedAt, maxRouteWait);
            hideRouteWhenReady(transitionStartedAt);
        });

        rootObserver.observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }

    function hideWhenReady() {
        const wait = Math.max(0, minimumStartupMs - (now() - startedAt));

        hideTimer = window.setTimeout(() => {
            if (now() - startedAt >= maxStartupWait || (appIsReady() && !hasBlockingLoader())) {
                forceHide();
                return;
            }

            hideWhenReady();
        }, wait || 80);
    }

    function hideRouteWhenReady(routeStartedAt) {
        const elapsed = now() - routeStartedAt;
        const nextDelay = elapsed >= minimumRouteMs
            ? transitionPollMs
            : Math.max(transitionPollMs, minimumRouteMs - elapsed);

        hideTimer = window.setTimeout(() => {
            const currentElapsed = now() - routeStartedAt;

            if (currentElapsed >= maxRouteWait || (currentElapsed >= minimumRouteMs && !hasBlockingLoader())) {
                forceHide();
                return;
            }

            hideRouteWhenReady(routeStartedAt);
        }, nextDelay);
    }

    function routeSettled() {
        const transitionStartedAt = now();

        showNow();
        monitorTransition(transitionStartedAt, maxRouteWait);
        hideRouteWhenReady(transitionStartedAt);
    }

    ['pushState', 'replaceState'].forEach((method) => {
        const original = history[method];

        history[method] = function patchedBrandLoaderHistoryState() {
            const result = original.apply(this, arguments);
            routeSettled();

            return result;
        };
    });

    showNow();
    observeLoadingMutations();

    window.addEventListener('pageshow', () => {
        showNow();
        monitorTransition(startedAt, maxStartupWait);
        hideWhenReady();
    });

    window.addEventListener('load', () => {
        showNow();
        monitorTransition(startedAt, maxStartupWait);
        hideWhenReady();
    });

    window.addEventListener('popstate', routeSettled);

    if (document.readyState === 'complete') {
        hideWhenReady();
    }
})();
