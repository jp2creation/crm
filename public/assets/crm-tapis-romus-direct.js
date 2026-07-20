import { I as jsxRuntime, K as wrapModule, W as reactFactory } from "./index-CqSzWeas.js?v=202607201920";

const React = wrapModule(reactFactory(), 1);
const jsx = jsxRuntime();
const ROMUS_BASE = "/romus-tapis/";
const CRM_ROMUS_VERSION = "1783867454";

let pdfLibLoader;

function loadPdfLib() {
  if (window.PDFLib) return Promise.resolve();

  pdfLibLoader ||= new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-romus-pdf-lib="1"]');

    if (existing) {
      existing.addEventListener("load", resolve, { once: true });
      existing.addEventListener("error", () => reject(new Error("Chargement pdf-lib impossible")), { once: true });
      return;
    }

    const script = document.createElement("script");
    script.src = `${ROMUS_BASE}pdf-lib.min.js`;
    script.async = true;
    script.dataset.romusPdfLib = "1";
    script.onload = () => resolve();
    script.onerror = () => reject(new Error("Chargement pdf-lib impossible"));
    document.head.appendChild(script);
  });

  return pdfLibLoader;
}

function absolutize(value) {
  if (!value || /^(?:[a-z][a-z0-9+.-]*:|#|data:|blob:)/i.test(value) || value.startsWith("/")) {
    return value;
  }

  return ROMUS_BASE + (value.startsWith("./") ? value.slice(2) : value);
}

function getCrmOverrideStyle() {
  return `
    <style>
      :host {
        display: block;
        --crm-red: #95002e;
        --crm-red-dark: #7f0028;
        --crm-text: #0f2740;
        --crm-muted: #64748b;
        --crm-line: #e5e7eb;
        --crm-soft: #f8fafc;
        --crm-panel: #ffffff;
        --crm-shadow: 0 16px 36px rgba(15, 39, 64, 0.08);
      }

      .page {
        min-height: auto !important;
        padding: 0 !important;
        background: transparent !important;
        font-family: "DM Sans", Arial, Helvetica, sans-serif !important;
      }

      .app {
        max-width: none !important;
        margin: 0 !important;
        border-radius: 14px !important;
        border: 1px solid var(--crm-line) !important;
        background: var(--crm-panel) !important;
        box-shadow: var(--crm-shadow) !important;
        overflow: hidden !important;
      }

      .hero,
      .help,
      .plan-stack {
        display: none !important;
      }

      .status-bar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 14px !important;
        padding: 16px 18px !important;
        border-bottom: 1px solid var(--crm-line) !important;
        background: var(--crm-panel) !important;
      }

      .choice-group {
        width: 100% !important;
        gap: 12px !important;
      }

      .choice-legend {
        color: var(--crm-text) !important;
        font-size: 0.88rem !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
      }

      .choice-divider {
        display: none !important;
      }

      .choice-card {
        min-height: 44px !important;
        border: 1px solid var(--crm-line) !important;
        border-radius: 12px !important;
        padding: 10px 14px !important;
        background: var(--crm-panel) !important;
        color: var(--crm-text) !important;
      }

      .choice-card.active {
        border-color: rgba(149, 0, 46, 0.32) !important;
        background: rgba(149, 0, 46, 0.07) !important;
        color: var(--crm-red) !important;
      }

      .choice-card input {
        width: 18px !important;
        height: 18px !important;
        border-color: #94a3b8 !important;
        box-shadow: none !important;
      }

      .choice-card input:checked {
        border-color: var(--crm-red) !important;
        background: radial-gradient(circle at center, var(--crm-red) 0 44%, transparent 47%), #ffffff !important;
        box-shadow: 0 0 0 3px rgba(149, 0, 46, 0.10) !important;
      }

      .content {
        grid-template-columns: 260px minmax(0, 1fr) !important;
        min-height: auto !important;
      }

      .sidebar {
        border-right: 1px solid var(--crm-line) !important;
        background: var(--crm-soft) !important;
        padding: 18px 14px !important;
      }

      .stepper {
        gap: 10px !important;
        top: 88px !important;
      }

      .step-item {
        align-items: center !important;
        gap: 12px !important;
        padding: 12px !important;
        border-radius: 12px !important;
        border: 1px solid transparent !important;
      }

      .step-item.active {
        border-color: var(--crm-line) !important;
        background: #ffffff !important;
        box-shadow: 0 10px 24px rgba(15, 39, 64, 0.06) !important;
      }

      .step-item:hover {
        background: #ffffff !important;
      }

      .step-number,
      .section-chip {
        background: var(--crm-red) !important;
        color: #ffffff !important;
      }

      .step-copy strong,
      .section-head h2,
      .field label,
      .dimension-fields-head strong,
      .dimension-field label,
      .option-card > label,
      th {
        color: var(--crm-text) !important;
      }

      .step-copy span,
      .hint,
      .dimension-fields-head span,
      .diagram-caption {
        color: var(--crm-muted) !important;
      }

      .main {
        padding: 22px 24px 28px !important;
        background: #ffffff !important;
      }

      .section-head {
        margin-bottom: 16px !important;
      }

      .section-head h2 {
        font-size: 1.35rem !important;
      }

      .section-band {
        border-radius: 10px !important;
        border: 1px solid var(--crm-line) !important;
        background: var(--crm-soft) !important;
        color: var(--crm-text) !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
      }

      .card,
      .table-card,
      .mat-card,
      .dimension-diagram {
        border: 1px solid var(--crm-line) !important;
        border-radius: 14px !important;
        background: #ffffff !important;
        box-shadow: 0 10px 28px rgba(15, 39, 64, 0.05) !important;
      }

      .mat-head {
        background: var(--crm-text) !important;
      }

      .mat-stage,
      .dimension-diagram {
        background: linear-gradient(180deg, #ffffff 0%, var(--crm-soft) 100%) !important;
      }

      .field input,
      .field textarea,
      .field select,
      td input,
      .dimension-input input,
      .subline input[type="text"] {
        min-height: 46px !important;
        border: 1px solid #d7dee8 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        color: var(--crm-text) !important;
      }

      .field input:focus,
      .field textarea:focus,
      .field select:focus,
      td input:focus,
      .dimension-input input:focus,
      .subline input[type="text"]:focus {
        border-color: var(--crm-red) !important;
        box-shadow: 0 0 0 3px rgba(149, 0, 46, 0.10) !important;
      }

      .option-card {
        border-color: var(--crm-line) !important;
        border-radius: 12px !important;
      }

      .option-card.active {
        border-color: rgba(149, 0, 46, 0.28) !important;
        background: rgba(149, 0, 46, 0.04) !important;
        box-shadow: none !important;
      }

      .option-card input[type="radio"],
      .option-card input[type="checkbox"] {
        accent-color: var(--crm-red) !important;
      }

      .dimension-code {
        background: rgba(149, 0, 46, 0.08) !important;
        color: var(--crm-red) !important;
      }

      .foot-note {
        border-color: rgba(149, 0, 46, 0.18) !important;
        background: rgba(149, 0, 46, 0.05) !important;
        color: var(--crm-red-dark) !important;
      }

      .warning {
        border: 1px solid #fecaca !important;
        border-radius: 12px !important;
        background: #fff7f7 !important;
        color: #991b1b !important;
      }

      .btn {
        border-radius: 12px !important;
        min-height: 46px !important;
        box-shadow: none !important;
      }

      .btn-primary {
        background: var(--crm-red) !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(149, 0, 46, 0.20) !important;
      }

      .btn-primary:hover {
        background: var(--crm-red-dark) !important;
      }

      .btn-secondary {
        background: var(--crm-soft) !important;
        color: var(--crm-text) !important;
        border: 1px solid var(--crm-line) !important;
      }

      .result.success {
        background: #ecfdf3 !important;
      }

      .result.error {
        background: #fff1f2 !important;
      }

      @media (max-width: 900px) {
        .content {
          display: block !important;
        }

        .sidebar {
          border-right: none !important;
          border-bottom: 1px solid var(--crm-line) !important;
        }

        .stepper {
          display: grid !important;
          grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
          gap: 8px !important;
          position: static !important;
        }

        .step-item {
          display: grid !important;
          justify-items: center !important;
          text-align: center !important;
          padding: 10px 8px !important;
        }

        .step-copy strong {
          font-size: 0.78rem !important;
        }

        .step-copy span {
          display: none !important;
        }
      }

      @media (max-width: 640px) {
        .app {
          border-radius: 12px !important;
        }

        .status-bar {
          padding: 14px !important;
        }

        .choice-group {
          display: grid !important;
          grid-template-columns: 1fr !important;
        }

        .choice-card {
          justify-content: flex-start !important;
        }

        .main {
          padding: 18px 14px 22px !important;
        }

        .stepper {
          grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .grid-2,
        .grid-3,
        .layout-2,
        .dimension-grid {
          grid-template-columns: 1fr !important;
        }

        .actions {
          justify-content: stretch !important;
        }

        .actions .btn {
          width: 100% !important;
        }
      }
    </style>
  `;
}

function prepareRomusDocument(html) {
  const parsed = new DOMParser().parseFromString(html, "text/html");
  const scripts = [...parsed.querySelectorAll("script:not([src])")].map((script) => script.textContent || "");

  parsed.querySelectorAll("script").forEach((script) => script.remove());
  parsed.querySelectorAll("[src]").forEach((node) => node.setAttribute("src", absolutize(node.getAttribute("src"))));
  parsed.querySelectorAll("[href]").forEach((node) => {
    const value = node.getAttribute("href");
    if (value && !value.startsWith("blob:")) node.setAttribute("href", absolutize(value));
  });

  const legend = parsed.querySelector(".choice-legend");
  if (legend) legend.textContent = "Type de demande";

  const stepLabels = [
    ["Coordonnees", "Client et chantier"],
    ["Produits", "References et quantites"],
    ["Mesures", "Dimensions et cadre"],
    ["PDF", "Controle et generation"],
  ];

  parsed.querySelectorAll(".step-item").forEach((item, index) => {
    const strong = item.querySelector(".step-copy strong");
    const span = item.querySelector(".step-copy span");
    if (stepLabels[index]) {
      if (strong) strong.textContent = stepLabels[index][0];
      if (span) span.textContent = stepLabels[index][1];
    }
  });

  const styles = [...parsed.querySelectorAll("style")]
    .map((style) => {
      let css = style.textContent || "";
      css = css.replace(/:root\s*\{/g, ":host{").replace(/body\s*\{/g, ".page{");
      return `<style>${css}</style>`;
    })
    .join("");

  return {
    html: styles + getCrmOverrideStyle() + parsed.body.innerHTML,
    scripts,
  };
}

function createScopedDocument(root) {
  const realDocument = document;

  return {
    querySelector: (selector) => root.querySelector(selector),
    querySelectorAll: (selector) => root.querySelectorAll(selector),
    getElementById: (id) => root.querySelector(`#${CSS.escape(id)}`),
    createElement: (...args) => realDocument.createElement(...args),
    body: realDocument.body,
    head: realDocument.head,
    addEventListener: (...args) => realDocument.addEventListener(...args),
    removeEventListener: (...args) => realDocument.removeEventListener(...args),
  };
}

function runRomusScript(root, source) {
  const scopedDocument = createScopedDocument(root);
  const runner = new Function("document", "window", "PDFLib", "URL", "Blob", "navigator", "CSS", source);
  runner(scopedDocument, window, window.PDFLib, URL, Blob, navigator, CSS);
}

export function TapisRomusPage() {
  const hostRef = React.useRef(null);
  const [state, setState] = React.useState("loading");

  React.useEffect(() => {
    let cancelled = false;
    let shadowRoot = null;

    async function mount() {
      const host = hostRef.current;
      if (!host) return;

      shadowRoot = host.shadowRoot || host.attachShadow({ mode: "open" });
      shadowRoot.innerHTML = `
        <style>
          :host{display:block}
          .romus-loading{border:1px solid #e5e7eb;border-radius:14px;background:white;padding:24px;font-family:Arial,Helvetica,sans-serif;color:#0f2740;font-weight:700}
        </style>
        <div class="romus-loading">Chargement du module ROMUS...</div>
      `;

      try {
        const [response] = await Promise.all([
          fetch(`${ROMUS_BASE}index.html?v=${CRM_ROMUS_VERSION}`, { cache: "no-store" }),
          loadPdfLib(),
        ]);

        if (!response.ok) throw new Error(`Chargement ROMUS impossible (${response.status})`);

        const source = await response.text();
        if (cancelled) return;

        const prepared = prepareRomusDocument(source);
        shadowRoot.innerHTML = prepared.html;

        for (const script of prepared.scripts) runRomusScript(shadowRoot, script);
        if (!cancelled) setState("ready");
      } catch (error) {
        if (cancelled) return;

        setState("error");
        if (shadowRoot) {
          shadowRoot.innerHTML = `
            <style>:host{display:block}</style>
            <div class="romus-direct-error">Impossible de charger le module ROMUS. ${String(error?.message || error)}</div>
          `;
        }
      }
    }

    mount();

    return () => {
      cancelled = true;
      if (shadowRoot) shadowRoot.innerHTML = "";
    };
  }, []);

  return jsx.jsxs("div", {
    className: "page-container space-y-4",
    children: [
      jsx.jsxs("div", {
        className: "flex flex-wrap items-center justify-between gap-3",
        children: [
          jsx.jsxs("div", {
            children: [
              jsx.jsx("h1", {
                className: "heading-2 text-secondary-900 dark:text-white",
                children: "Tapis ROMUS",
              }),
              jsx.jsx("p", {
                className: "text-body-sm text-secondary-500",
                children: "Bon de commande ROMUS integre au CRM avec generation PDF.",
              }),
            ],
          }),
        ],
      }),
      state === "error" &&
        jsx.jsx("div", {
          className: "rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-body-sm font-semibold text-danger-700",
          children: "Le module ROMUS n'a pas pu etre charge.",
        }),
      jsx.jsx("div", { ref: hostRef, className: "romus-direct-host" }),
    ],
  });
}
