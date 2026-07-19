(() => {
    const loader = document.getElementById('brand-morph-loader');

    if (!loader) return;

    let timer = null;
    let activeRequests = 0;

    function show(delay = 100) {
        activeRequests += 1;
        clearTimeout(timer);

        timer = window.setTimeout(() => {
            loader.classList.add('is-visible');
            loader.setAttribute('aria-hidden', 'false');
        }, delay);
    }

    function hide() {
        activeRequests = Math.max(0, activeRequests - 1);

        if (activeRequests > 0) return;

        clearTimeout(timer);
        loader.classList.remove('is-visible');
        loader.setAttribute('aria-hidden', 'true');
    }

    function forceHide() {
        activeRequests = 0;
        clearTimeout(timer);
        loader.classList.remove('is-visible');
        loader.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (!link) return;

        const url = new URL(link.href, window.location.href);

        const ignored =
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            link.target === '_blank' ||
            link.hasAttribute('download') ||
            link.hasAttribute('data-no-loader') ||
            url.origin !== window.location.origin ||
            (url.pathname === window.location.pathname && url.hash);

        if (!ignored) show();
    });

    document.addEventListener('submit', () => show());

    window.addEventListener('pageshow', forceHide);
    window.addEventListener('load', forceHide);

    window.BrandMorphLoader = {
        show,
        hide,
        forceHide
    };
})();
