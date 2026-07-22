type LegacyReactComponent = (...args: unknown[]) => unknown;
type LegacyReactRoot = {
  render: (node: unknown) => void;
  unmount: () => void;
};
type LegacyReactRuntime = {
  createElement: (component: LegacyReactComponent) => unknown;
};
type LegacyReactDomRuntime = {
  createRoot: (container: Element) => LegacyReactRoot;
};
type LegacyReactModule = Record<string, unknown>;
type LegacyReactRuntimeModule = {
  __crmReact?: unknown;
  __crmReactDomClient?: unknown;
};
type LegacyReactComponentOptions = {
  componentExport: string;
  hostId: string;
  loader: () => Promise<LegacyReactModule>;
};

const legacyRuntimeUrl = '/assets/index-CqSzWeas.js?v=202607201920';
const mountedRoots = new Map<string, LegacyReactRoot>();
let legacyRuntimePromise: Promise<{
  React: LegacyReactRuntime;
  ReactDomClient: LegacyReactDomRuntime;
}> | null = null;

function loadLegacyRuntime(): Promise<{
  React: LegacyReactRuntime;
  ReactDomClient: LegacyReactDomRuntime;
}> {
  if (legacyRuntimePromise) {
    return legacyRuntimePromise;
  }

  legacyRuntimePromise = import(/* @vite-ignore */ legacyRuntimeUrl).then((runtime: LegacyReactRuntimeModule) => {
    if (!runtime.__crmReact || !runtime.__crmReactDomClient) {
      throw new Error('Runtime React CRM transitoire indisponible.');
    }

    return {
      React: runtime.__crmReact as LegacyReactRuntime,
      ReactDomClient: runtime.__crmReactDomClient as LegacyReactDomRuntime,
    };
  });

  return legacyRuntimePromise;
}

function waitForHost(hostId: string): Promise<HTMLElement> {
  return new Promise((resolve, reject) => {
    let attempts = 0;

    const findHost = (): void => {
      const host = document.getElementById(hostId);

      if (host) {
        resolve(host);
        return;
      }

      attempts += 1;

      if (attempts > 12) {
        reject(new Error(`Hôte CRM introuvable : ${hostId}`));
        return;
      }

      window.setTimeout(findHost, 60);
    };

    findHost();
  });
}

function unmountHost(hostId: string): void {
  const root = mountedRoots.get(hostId);

  if (!root) {
    return;
  }

  root.unmount();
  mountedRoots.delete(hostId);
}

export async function mountLegacyReactComponent(options: LegacyReactComponentOptions): Promise<void> {
  const host = await waitForHost(options.hostId);

  if (host.dataset.crmLegacyReactMounted === options.componentExport) {
    return;
  }

  unmountHost(options.hostId);
  host.innerHTML = '';

  const [runtime, module] = await Promise.all([loadLegacyRuntime(), options.loader()]);
  const component = module[options.componentExport];

  if (typeof component !== 'function') {
    throw new Error(`Composant CRM introuvable : ${options.componentExport}`);
  }

  const root = runtime.ReactDomClient.createRoot(host);
  root.render(runtime.React.createElement(component as LegacyReactComponent));
  mountedRoots.set(options.hostId, root);
  host.dataset.crmLegacyReactMounted = options.componentExport;

  host.addEventListener(
    'crm:legacy-react-host-remove',
    () => {
      unmountHost(options.hostId);
    },
    { once: true },
  );
}
