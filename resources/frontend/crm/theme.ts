type ThemeConfig = {
  cardStyle?: string;
  color?: string;
  container?: string;
  direction?: string;
  mode?: string;
  sidebarLayout?: string;
};

const colorPresets: Record<string, [string, string]> = {
  blue: ['59 130 246', '99 102 241'],
  purple: ['236 72 153', '14 165 233'],
  green: ['34 197 94', '20 184 166'],
  orange: ['249 115 22', '245 158 11'],
  red: ['239 68 68', '244 63 94'],
  cyan: ['149 0 46', '149 0 46'],
};

export function applyStoredTheme(storageKey: string): void {
  try {
    const saved = localStorage.getItem(storageKey);

    if (!saved) {
      return;
    }

    const config = JSON.parse(saved) as ThemeConfig;
    const root = document.documentElement;

    if (config.mode === 'dark') {
      root.classList.add('dark');
    }

    if (config.direction === 'rtl') {
      root.dir = 'rtl';
    }

    if (config.color && colorPresets[config.color]) {
      root.style.setProperty('--theme-primary', colorPresets[config.color][0]);
      root.style.setProperty('--theme-accent', colorPresets[config.color][1]);
    }

    if (config.sidebarLayout) {
      root.dataset.sidebarLayout = config.sidebarLayout;
    }

    if (config.container) {
      root.dataset.container = config.container;
    }

    if (config.cardStyle) {
      root.dataset.cardStyle = config.cardStyle;
    }
  } catch {
    // Theme preference is optional.
  }
}
