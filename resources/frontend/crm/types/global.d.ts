export {};

declare global {
  interface Window {
    BrandMorphLoader?: {
      begin?: (key?: string, options?: CrmLoaderOptions) => string | void;
      end?: (key?: string) => void;
      fail?: (key?: string, error?: unknown) => void;
      forceHide: () => void;
      hide: (key?: string) => void;
      show: (delay?: number) => void;
      track?: <T>(key: string, promise: Promise<T>, options?: CrmLoaderOptions) => Promise<T>;
    };
    CrmLoader?: Window['BrandMorphLoader'];
    CRM_NAV_FALLBACK?: CrmFallbackNavigation;
    MartinSolsCrmApi?: CrmApiClient;
    MartinSolsCrmAssets?: {
      brandMorphLoaderStylesheet?: string;
      legacyAdminexScript?: string;
      legacyAdminexStylesheet?: string;
      logoUrl?: string;
    };
    MartinSolsCrmConfig?: CrmShellConfig;
    MartinSolsCrmLogout?: () => void;
    MartinSolsMobileApp?: {
      requestLocation: () => void;
    };
    __martinSolsCrmFetchCsrf?: boolean;
    __martinSolsCrmLegacyAdminexLoading?: Promise<void>;
    __martinSolsCrmModulesLoaded?: boolean;
  }
}

export type CrmLoaderOptions = {
  delay?: number;
  timeout?: number;
  timeoutMessage?: string;
};

export type CrmApiClient = {
  get: <T = unknown>(url: string, options?: CrmRequestOptions) => Promise<T>;
  post: <T = unknown>(url: string, body?: CrmRequestOptions['body'], options?: CrmRequestOptions) => Promise<T>;
  request: <T = unknown>(url: string, options?: CrmRequestOptions) => Promise<T>;
};

export type CrmFallbackNavigation = {
  menuGroups: CrmMenuGroup[];
  menuItems: CrmMenuItem[];
  modules: CrmModule[];
};

export type CrmMenuGroup = {
  active: boolean;
  menuKey: string;
  sortOrder: number;
  title: string;
};

export type CrmMenuItem = {
  active: boolean;
  groupKey: string;
  iconKey: string;
  itemKey: string;
  label: string;
  sortOrder: number;
};

export type CrmModule = {
  active: boolean;
  menuBadge?: string;
  name: string;
  routePath: string;
  showMenuBadge?: boolean;
  slug: string;
  sortOrder: number;
};

export type CrmRequestOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | null;
};

export type CrmShellConfig = {
  assets: {
    brandMorphLoaderStylesheet: string;
    legacyAdminexScript: string;
    legacyAdminexStylesheet: string;
    logoUrl: string;
  };
  csrfToken: string;
  locale: string;
  logout: {
    legacyLogoutPath: string;
    loginUrl: string;
    logoutUrl: string;
  };
  mobile: {
    app: boolean;
    embed: boolean;
    siteId: number | null;
  };
  themeStorageKey: string;
};
