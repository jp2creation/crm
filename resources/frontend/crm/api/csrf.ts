const mutationMethods = new Set(['DELETE', 'PATCH', 'POST', 'PUT']);

export function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '';
}

export function isMutationMethod(method: string): boolean {
  return mutationMethods.has(method.toUpperCase());
}

function requestMethod(input: RequestInfo | URL, init?: RequestInit): string {
  const request = input instanceof Request ? input : null;

  return String(init?.method || request?.method || 'GET').toUpperCase();
}

function isSameOrigin(input: RequestInfo | URL): boolean {
  try {
    const value = input instanceof Request ? input.url : input;

    return new URL(value, window.location.href).origin === window.location.origin;
  } catch {
    return false;
  }
}

export function installCsrfFetch(): void {
  const token = csrfToken();
  const originalFetch = window.fetch;

  if (!token || typeof originalFetch !== 'function' || window.__martinSolsCrmFetchCsrf) {
    return;
  }

  window.__martinSolsCrmFetchCsrf = true;

  window.fetch = function crmFetch(input: RequestInfo | URL, init?: RequestInit) {
    const method = requestMethod(input, init);

    if (!isMutationMethod(method) || !isSameOrigin(input)) {
      return originalFetch.call(this, input, init);
    }

    const nextInit: RequestInit = init ? { ...init } : {};
    const request = input instanceof Request ? input : null;
    const headers = new Headers(nextInit.headers || request?.headers || undefined);

    if (!headers.has('X-CSRF-TOKEN') && !headers.has('X-XSRF-TOKEN')) {
      headers.set('X-CSRF-TOKEN', token);
    }

    if (!headers.has('X-Requested-With')) {
      headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    nextInit.headers = headers;

    return originalFetch.call(this, input, nextInit);
  };
}
