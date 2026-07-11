import {
    C as e,
    E as t,
    I as n,
    K as r,
    S as i,
    T as a,
    W as o,
    a as s,
    b as c,
    d as l,
    f as u,
    i as d,
    l as f,
    m as p,
    n as ee,
    o as te,
    p as m,
    r as h,
    s as ne,
    t as g,
    u as _,
    w as v,
} from "./index-CqSzWeas.js?v=2026071042";
import { t as re } from "./dashboard-Chzs1W9w.js?v=2026071042";
var y = r(o(), 1),
    b = n(),
    x = {
        name: ``,
        active: !0,
        morningStart: `07:30`,
        morningEnd: `12:00`,
        afternoonStart: `13:30`,
        afternoonEnd: `17:30`,
    },
    S = {
        name: ``,
        slug: ``,
        description: ``,
        routePath: ``,
        menuBadge: ``,
        showMenuBadge: !1,
        active: !0,
        sortOrder: `100`,
    },
    C = {
        name: ``,
        role: `user`,
        active: !0,
        siteIds: [],
        moduleIds: [],
        permissionIds: [],
    },
    w = [`Essentiels`, `CRM`, `Contenu`, `Bibliothèque fine`],
    T = [
        { key: `dashboard`, label: `Dashboard`, section: `Essentiels` },
        { key: `homeAngle`, label: `Accueil`, section: `Essentiels` },
        { key: `chartLine`, label: `Graphique ligne`, section: `Essentiels` },
        { key: `chartBar`, label: `Graphique barres`, section: `Essentiels` },
        { key: `chartPie`, label: `Graphique rond`, section: `Essentiels` },
        { key: `settings`, label: `Réglages`, section: `Essentiels` },
        { key: `category`, label: `Module`, section: `Essentiels` },
        { key: `layoutGrid`, label: `Grille`, section: `Essentiels` },
        { key: `table`, label: `Liste`, section: `Essentiels` },
        { key: `database`, label: `Base de données`, section: `Essentiels` },
        { key: `search`, label: `Recherche`, section: `Essentiels` },
        { key: `filter`, label: `Filtre`, section: `Essentiels` },
        { key: `briefcase`, label: `CRM`, section: `CRM` },
        { key: `building`, label: `Bâtiment`, section: `CRM` },
        { key: `truck`, label: `Véhicule`, section: `CRM` },
        { key: `garage`, label: `Garage`, section: `CRM` },
        { key: `calendar`, label: `Calendrier`, section: `CRM` },
        { key: `calendarEvent`, label: `Événement`, section: `CRM` },
        { key: `clock`, label: `Horloge`, section: `CRM` },
        { key: `contacts`, label: `Contacts`, section: `CRM` },
        { key: `user`, label: `Utilisateur`, section: `CRM` },
        { key: `users`, label: `Utilisateurs`, section: `CRM` },
        { key: `userPlus`, label: `Ajouter utilisateur`, section: `CRM` },
        { key: `shield`, label: `Sécurité`, section: `CRM` },
        { key: `mapPin`, label: `Localisation`, section: `CRM` },
        { key: `route`, label: `Itinéraire`, section: `CRM` },
        { key: `article`, label: `Document`, section: `Contenu` },
        { key: `documents`, label: `Documents`, section: `Contenu` },
        { key: `file`, label: `Fichier`, section: `Contenu` },
        { key: `folder`, label: `Dossier`, section: `Contenu` },
        { key: `folderOpen`, label: `Dossier ouvert`, section: `Contenu` },
        { key: `checklist`, label: `Checklist`, section: `Contenu` },
        { key: `checks`, label: `Validation`, section: `Contenu` },
        { key: `mail`, label: `Email`, section: `Contenu` },
        { key: `message`, label: `Message`, section: `Contenu` },
        { key: `note`, label: `Note`, section: `Contenu` },
        { key: `kanban`, label: `Kanban`, section: `Contenu` },
        { key: `photo`, label: `Image`, section: `Contenu` },
        { key: `edit`, label: `Édition`, section: `Contenu` },
        { key: `heading`, label: `Texte`, section: `Contenu` },
        { key: `archive`, label: `Archive`, section: `Contenu` },
        {
            key: `clipboard`,
            label: `Presse-papiers`,
            section: `Bibliothèque fine`,
        },
        {
            key: `clipboardCheck`,
            label: `Presse-papiers validé`,
            section: `Bibliothèque fine`,
        },
        {
            key: `clipboardList`,
            label: `Liste de contrôle`,
            section: `Bibliothèque fine`,
        },
        { key: `copy`, label: `Copie`, section: `Bibliothèque fine` },
        { key: `printer`, label: `Imprimante`, section: `Bibliothèque fine` },
        { key: `qrCode`, label: `QR code`, section: `Bibliothèque fine` },
        { key: `scanner`, label: `Scanner`, section: `Bibliothèque fine` },
        { key: `shopping`, label: `Commerce`, section: `Bibliothèque fine` },
        { key: `shoppingBag`, label: `Sac`, section: `Bibliothèque fine` },
        { key: `shop`, label: `Boutique`, section: `Bibliothèque fine` },
        { key: `creditCard`, label: `Carte`, section: `Bibliothèque fine` },
        { key: `package`, label: `Colis`, section: `Bibliothèque fine` },
        { key: `wallet`, label: `Portefeuille`, section: `Bibliothèque fine` },
        { key: `walletMoney`, label: `Paiement`, section: `Bibliothèque fine` },
        {
            key: `calculator`,
            label: `Calculatrice`,
            section: `Bibliothèque fine`,
        },
        { key: `bill`, label: `Facture`, section: `Bibliothèque fine` },
        { key: `banknote`, label: `Billet`, section: `Bibliothèque fine` },
        { key: `sale`, label: `Remise`, section: `Bibliothèque fine` },
        { key: `palette`, label: `Palette`, section: `Bibliothèque fine` },
        { key: `paintRoller`, label: `Rouleau`, section: `Bibliothèque fine` },
        { key: `ruler`, label: `Règle`, section: `Bibliothèque fine` },
        { key: `scissors`, label: `Ciseaux`, section: `Bibliothèque fine` },
        { key: `broom`, label: `Nettoyage`, section: `Bibliothèque fine` },
        { key: `magicStick`, label: `Magie`, section: `Bibliothèque fine` },
        { key: `tag`, label: `Étiquette`, section: `Bibliothèque fine` },
        { key: `help`, label: `Aide`, section: `Bibliothèque fine` },
        { key: `heart`, label: `Favori`, section: `Bibliothèque fine` },
        { key: `star`, label: `Étoile`, section: `Bibliothèque fine` },
        {
            key: `starShine`,
            label: `Étoile fine`,
            section: `Bibliothèque fine`,
        },
        { key: `bolt`, label: `Éclair`, section: `Bibliothèque fine` },
        { key: `rocket`, label: `Fusée`, section: `Bibliothèque fine` },
        { key: `target`, label: `Cible`, section: `Bibliothèque fine` },
        { key: `flag`, label: `Drapeau`, section: `Bibliothèque fine` },
        { key: `bell`, label: `Notification`, section: `Bibliothèque fine` },
        {
            key: `bellOff`,
            label: `Notification coupée`,
            section: `Bibliothèque fine`,
        },
        { key: `alarm`, label: `Alarme`, section: `Bibliothèque fine` },
        {
            key: `stopwatch`,
            label: `Chronomètre`,
            section: `Bibliothèque fine`,
        },
        { key: `hourglass`, label: `Sablier`, section: `Bibliothèque fine` },
        { key: `ruleEngine`, label: `Règles`, section: `Bibliothèque fine` },
        { key: `queryBuilder`, label: `Requête`, section: `Bibliothèque fine` },
        {
            key: `simulation`,
            label: `Simulation`,
            section: `Bibliothèque fine`,
        },
        { key: `insights`, label: `Idée`, section: `Bibliothèque fine` },
        {
            key: `workflowBuilder`,
            label: `Workflow`,
            section: `Bibliothèque fine`,
        },
        { key: `taskScheduler`, label: `Tâches`, section: `Bibliothèque fine` },
        { key: `library`, label: `Bibliothèque`, section: `Bibliothèque fine` },
        { key: `layers`, label: `Calques`, section: `Bibliothèque fine` },
        { key: `structure`, label: `Structure`, section: `Bibliothèque fine` },
        { key: `server`, label: `Serveur`, section: `Bibliothèque fine` },
        { key: `cloud`, label: `Cloud`, section: `Bibliothèque fine` },
        { key: `wifiRouter`, label: `Réseau`, section: `Bibliothèque fine` },
        { key: `laptop`, label: `Ordinateur`, section: `Bibliothèque fine` },
        {
            key: `monitorSmartphone`,
            label: `Multi-écran`,
            section: `Bibliothèque fine`,
        },
        { key: `deviceMobile`, label: `Mobile`, section: `Bibliothèque fine` },
        {
            key: `deviceTablet`,
            label: `Tablette`,
            section: `Bibliothèque fine`,
        },
        { key: `compass`, label: `Boussole`, section: `Bibliothèque fine` },
        { key: `map`, label: `Carte`, section: `Bibliothèque fine` },
        { key: `signpost`, label: `Panneau`, section: `Bibliothèque fine` },
        { key: `bus`, label: `Bus`, section: `Bibliothèque fine` },
        { key: `fuel`, label: `Carburant`, section: `Bibliothèque fine` },
        { key: `medal`, label: `Médaille`, section: `Bibliothèque fine` },
        { key: `crown`, label: `Premium`, section: `Bibliothèque fine` },
    ];
function ie(e) {
    return e
        .normalize(`NFD`)
        .replace(/[\u0300-\u036f]/g, ``)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, `-`)
        .replace(/(^-|-$)/g, ``);
}
function E(e, t) {
    return e.includes(t) ? e.filter((e) => e !== t) : [...e, t];
}
function D(e, t) {
    let n = t?.roles.find((t) => t.key === e);
    return {
        moduleIds:
            n?.moduleSlugs
                .map((e) => t?.modules.find((t) => t.slug === e)?.id)
                .filter((e) => typeof e == `number`) ?? [],
        permissionIds:
            n?.permissions
                .map((e) => t?.permissions.find((t) => t.name === e)?.id)
                .filter((e) => typeof e == `number`) ?? [],
    };
}
function ae(e) {
    return {
        id: e.id,
        name: e.name,
        role: e.role,
        active: e.active,
        siteIds: e.siteIds,
        moduleIds: e.moduleIds,
        permissionIds: e.permissionIds,
    };
}
function siteTimeValue(e, t) {
    return String(e || t).slice(0, 5);
}
function siteHoursLabel(e) {
    return `${siteTimeValue(e.morningStart, `07:30`)}-${siteTimeValue(e.morningEnd, `12:00`)} / ${siteTimeValue(e.afternoonStart, `13:30`)}-${siteTimeValue(e.afternoonEnd, `17:30`)}`;
}
function oe(e) {
    let t = e.hours || {};
    return {
        id: e.id,
        name: e.name,
        active: e.active,
        morningStart: siteTimeValue(t.morningStart || e.morningStart, `07:30`),
        morningEnd: siteTimeValue(t.morningEnd || e.morningEnd, `12:00`),
        afternoonStart: siteTimeValue(
            t.afternoonStart || e.afternoonStart,
            `13:30`,
        ),
        afternoonEnd: siteTimeValue(t.afternoonEnd || e.afternoonEnd, `17:30`),
    };
}
function se(e) {
    return {
        id: e.id,
        name: e.name,
        slug: e.slug,
        description: e.description,
        routePath: e.routePath,
        menuBadge: e.menuBadge,
        showMenuBadge: e.showMenuBadge,
        active: e.active,
        sortOrder: String(e.sortOrder),
    };
}
function ce(e) {
    return e.map((e) => ({
        menuKey: e.menuKey,
        title: e.title,
        active: e.active,
        sortOrder: String(e.sortOrder),
    }));
}
function O(e) {
    return e.map((e) => ({
        itemKey: e.itemKey,
        groupKey: e.groupKey,
        iconKey: e.iconKey || `category`,
        label: e.label,
        active: e.active,
        sortOrder: String(e.sortOrder),
    }));
}
function k(e) {
    return Number(e) || 100;
}
function A(e) {
    return _[e] ?? _.category;
}
function j(e) {
    return T.find((t) => t.key === e)?.label ?? `Icône personnalisée`;
}
function le({ value: e, label: n, onChange: r }) {
    let i = (0, y.useRef)(null),
        [a, o] = (0, y.useState)(!1),
        [s, c] = (0, y.useState)(``),
        l = s
            .normalize(`NFD`)
            .replace(/[\u0300-\u036f]/g, ``)
            .toLowerCase()
            .trim(),
        u = (0, y.useMemo)(
            () =>
                T.filter((e) =>
                    l
                        ? `${e.label} ${e.key} ${e.section}`
                              .normalize(`NFD`)
                              .replace(/[\u0300-\u036f]/g, ``)
                              .toLowerCase()
                              .includes(l)
                        : !0,
                ),
            [l],
        );
    (0, y.useEffect)(() => {
        if (!a) return;
        let e = (e) => {
                i.current?.contains(e.target) || o(!1);
            },
            t = (e) => {
                e.key === `Escape` && o(!1);
            };
        return (
            document.addEventListener(`mousedown`, e),
            document.addEventListener(`keydown`, t),
            () => {
                (document.removeEventListener(`mousedown`, e),
                    document.removeEventListener(`keydown`, t));
            }
        );
    }, [a]);
    let d = j(e);
    return (0, b.jsxs)(`div`, {
        ref: i,
        className: `relative`,
        children: [
            (0, b.jsxs)(`button`, {
                type: `button`,
                className: `flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-surface-200 bg-white px-3 text-left text-body-sm font-medium text-secondary-800 transition-colors hover:border-theme-primary/60 focus:border-theme-primary focus:outline-none focus:ring-4 focus:ring-theme-primary/10 dark:border-surface-700 dark:bg-surface-900 dark:text-secondary-100`,
                "aria-haspopup": `listbox`,
                "aria-expanded": a,
                "aria-label": `Choisir l'icône ${n}`,
                "data-icon-picker-value": e,
                onClick: () => o((e) => !e),
                children: [
                    (0, b.jsxs)(`span`, {
                        className: `flex min-w-0 items-center gap-2`,
                        children: [
                            (0, b.jsx)(`span`, {
                                className: `flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-theme-primary/10 text-theme-primary`,
                                children: (0, b.jsx)(f, {
                                    icon: A(e),
                                    className: `h-4 w-4`,
                                }),
                            }),
                            (0, b.jsx)(`span`, {
                                className: `truncate`,
                                children: d,
                            }),
                        ],
                    }),
                    (0, b.jsx)(f, {
                        icon: _.chevronDown,
                        className: t(
                            `h-4 w-4 shrink-0 text-secondary-400 transition-transform`,
                            a && `rotate-180`,
                        ),
                    }),
                ],
            }),
            a &&
                (0, b.jsxs)(`div`, {
                    className: `absolute left-0 top-[calc(100%+6px)] z-[1050] w-[min(420px,calc(100vw-3rem))] rounded-xl border border-surface-200 bg-white p-3 shadow-xl dark:border-surface-700 dark:bg-surface-900`,
                    children: [
                        (0, b.jsx)(v, {
                            value: s,
                            placeholder: `Rechercher une icône...`,
                            "aria-label": `Rechercher une icône`,
                            onChange: (e) => c(e.target.value),
                        }),
                        (0, b.jsxs)(`div`, {
                            className: `mt-3 max-h-80 space-y-4 overflow-y-auto pr-1 scrollbar-thin`,
                            role: `listbox`,
                            "aria-label": `Bibliothèque d'icônes`,
                            children: [
                                w.map((n) => {
                                    let i = u.filter((e) => e.section === n);
                                    return i.length
                                        ? (0, b.jsxs)(
                                              `div`,
                                              {
                                                  children: [
                                                      (0, b.jsx)(`p`, {
                                                          className: `mb-2 text-ui-xs font-semibold uppercase tracking-wide text-secondary-400`,
                                                          children: n,
                                                      }),
                                                      (0, b.jsx)(`div`, {
                                                          className: `grid grid-cols-1 gap-1 sm:grid-cols-2`,
                                                          children: i.map(
                                                              (n) => {
                                                                  let i =
                                                                      n.key ===
                                                                      e;
                                                                  return (0,
                                                                  b.jsxs)(
                                                                      `button`,
                                                                      {
                                                                          type: `button`,
                                                                          role: `option`,
                                                                          "aria-selected":
                                                                              i,
                                                                          "data-icon-choice":
                                                                              n.key,
                                                                          className:
                                                                              t(
                                                                                  `flex min-w-0 items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left text-body-sm transition-colors`,
                                                                                  i
                                                                                      ? `bg-theme-primary/10 text-theme-primary`
                                                                                      : `text-secondary-700 hover:bg-surface-100 dark:text-secondary-200 dark:hover:bg-surface-800`,
                                                                              ),
                                                                          onClick:
                                                                              () => {
                                                                                  (r(
                                                                                      n.key,
                                                                                  ),
                                                                                      o(
                                                                                          !1,
                                                                                      ),
                                                                                      c(
                                                                                          ``,
                                                                                      ));
                                                                              },
                                                                          children:
                                                                              [
                                                                                  (0,
                                                                                  b.jsxs)(
                                                                                      `span`,
                                                                                      {
                                                                                          className: `flex min-w-0 items-center gap-2`,
                                                                                          children:
                                                                                              [
                                                                                                  (0,
                                                                                                  b.jsx)(
                                                                                                      `span`,
                                                                                                      {
                                                                                                          className: `flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-surface-200 bg-white text-theme-primary dark:border-surface-700 dark:bg-surface-950`,
                                                                                                          children:
                                                                                                              (0,
                                                                                                              b.jsx)(
                                                                                                                  f,
                                                                                                                  {
                                                                                                                      icon: A(
                                                                                                                          n.key,
                                                                                                                      ),
                                                                                                                      className: `h-[18px] w-[18px]`,
                                                                                                                  },
                                                                                                              ),
                                                                                                      },
                                                                                                  ),
                                                                                                  (0,
                                                                                                  b.jsx)(
                                                                                                      `span`,
                                                                                                      {
                                                                                                          className: `truncate`,
                                                                                                          children:
                                                                                                              n.label,
                                                                                                      },
                                                                                                  ),
                                                                                              ],
                                                                                      },
                                                                                  ),
                                                                                  i
                                                                                      ? (0,
                                                                                        b.jsx)(
                                                                                            f,
                                                                                            {
                                                                                                icon: _.check,
                                                                                                className: `h-4 w-4 shrink-0`,
                                                                                            },
                                                                                        )
                                                                                      : null,
                                                                              ],
                                                                      },
                                                                      n.key,
                                                                  );
                                                              },
                                                          ),
                                                      }),
                                                  ],
                                              },
                                              n,
                                          )
                                        : null;
                                }),
                                !u.length &&
                                    (0, b.jsx)(`p`, {
                                        className: `rounded-lg border border-dashed border-surface-200 p-3 text-body-sm text-secondary-400 dark:border-surface-700`,
                                        children: `Aucune icône trouvée.`,
                                    }),
                            ],
                        }),
                    ],
                }),
        ],
    });
}
function M(e) {
    return e.map((e, t) => ({ ...e, sortOrder: String((t + 1) * 10) }));
}
function N(e, t, n, r) {
    return (0, b.jsx)(p, {
        padding: `sm`,
        className: `rounded-xl`,
        children: (0, b.jsxs)(`div`, {
            className: `flex items-center gap-3`,
            children: [
                (0, b.jsx)(`div`, {
                    className: `flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-theme-primary/10 text-theme-primary`,
                    children: (0, b.jsx)(f, { icon: n, className: `h-5 w-5` }),
                }),
                (0, b.jsxs)(`div`, {
                    className: `min-w-0`,
                    children: [
                        (0, b.jsx)(`p`, {
                            className: `text-caption text-secondary-500`,
                            children: e,
                        }),
                        (0, b.jsx)(`p`, {
                            className: `heading-5 truncate text-secondary-900 dark:text-white`,
                            children: t,
                        }),
                        (0, b.jsx)(`p`, {
                            className: `text-ui-xs text-secondary-400`,
                            children: r,
                        }),
                    ],
                }),
            ],
        }),
    });
}
function P({
    title: e,
    subtitle: t,
    badge: n,
    active: r,
    onEdit: i,
    onDelete: o,
    deleteLoading: s = !1,
}) {
    return (0, b.jsx)(`div`, {
        className: `w-full rounded-xl border border-surface-200 p-3 transition-colors hover:border-theme-primary/50 hover:bg-theme-primary/5 dark:border-surface-700`,
        children: (0, b.jsxs)(`div`, {
            className: `flex items-start justify-between gap-3`,
            children: [
                (0, b.jsxs)(`button`, {
                    type: `button`,
                    className: `min-w-0 flex-1 text-left`,
                    onClick: i,
                    children: [
                        (0, b.jsx)(`p`, {
                            className: `text-label text-secondary-900 dark:text-white`,
                            children: e,
                        }),
                        (0, b.jsx)(`p`, {
                            className: `mt-0.5 truncate text-caption text-secondary-500`,
                            children: t,
                        }),
                    ],
                }),
                (0, b.jsxs)(`div`, {
                    className: `flex shrink-0 items-center gap-2`,
                    children: [
                        n !== void 0 &&
                            (0, b.jsx)(u, { variant: `primary`, children: n }),
                        (0, b.jsx)(u, {
                            variant: r ? `success` : `neutral`,
                            children: r ? `Actif` : `Inactif`,
                        }),
                        o &&
                            (0, b.jsx)(a, {
                                variant: `ghost`,
                                size: `sm`,
                                iconOnly: !0,
                                loading: s,
                                "aria-label": `Supprimer`,
                                onClick: o,
                                className: `text-danger-600 hover:bg-danger-50 hover:text-danger-700 dark:hover:bg-danger-900/20`,
                                children: (0, b.jsx)(f, {
                                    icon: _.trash,
                                    className: `h-4 w-4`,
                                }),
                            }),
                    ],
                }),
            ],
        }),
    });
}
function ue({ items: e, values: t, onToggle: n, getLabel: r }) {
    return (0, b.jsx)(`div`, {
        className: `grid grid-cols-1 gap-2 sm:grid-cols-2`,
        children: e.map((e) =>
            (0, b.jsxs)(
                `label`,
                {
                    className: `flex cursor-pointer items-center gap-2 rounded-lg border border-surface-200 px-3 py-2 text-body-sm dark:border-surface-700`,
                    children: [
                        (0, b.jsx)(c, {
                            checked: t.includes(e.id),
                            onChange: () => n(e.id),
                        }),
                        (0, b.jsx)(`span`, {
                            className: `min-w-0 truncate text-secondary-700 dark:text-secondary-200`,
                            children: r(e.id),
                        }),
                    ],
                },
                e.id,
            ),
        ),
    });
}
function de({ notice: e }) {
    return e
        ? (0, b.jsx)(`div`, {
              className: t(
                  `rounded-xl border p-3 text-body-sm`,
                  e.type === `success`
                      ? `border-success-200 bg-success-50 text-success-800 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200`
                      : `border-danger-200 bg-danger-50 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200`,
              ),
              children: e.message,
          })
        : null;
}
const adminSectionKeys = [`users`, `sites`, `modules`, `menu`, `equipment`, `theme`, `roles`];
function adminSectionFromLocation() {
    let e = new URLSearchParams(window.location.search).get(`section`);
    return adminSectionKeys.includes(e) ? e : `users`;
}
function fe() {
    let [t, n] = (0, y.useState)(null),
        [r, o] = (0, y.useState)(() => adminSectionFromLocation()),
        [w, T] = (0, y.useState)(null),
        [j, fe] = (0, y.useState)(!0),
        [F, I] = (0, y.useState)(!1),
        [L, R] = (0, y.useState)(null),
        [z, B] = (0, y.useState)(x),
        [V, H] = (0, y.useState)(S),
        [U, W] = (0, y.useState)(C),
        [G, K] = (0, y.useState)([]),
        [q, J] = (0, y.useState)([]);
    async function Y(e = !1, t = !0) {
        let r = await h();
        (n(r),
            t && (K(ce(r.menuGroups)), J(O(r.menuItems))),
            e &&
                T({
                    type: `success`,
                    message: `Administration synchronisee avec MySQL.`,
                }));
    }
    (0, y.useEffect)(() => {
        let e = !0;
        return (
            h()
                .then((t) => {
                    if (!e) return;
                    (n(t), K(ce(t.menuGroups)), J(O(t.menuItems)));
                    let r = t.sites.find((e) => e.active) ?? t.sites[0],
                        i = D(`user`, t);
                    W((e) => ({
                        ...e,
                        siteIds: r ? [r.id] : [],
                        moduleIds: i.moduleIds,
                        permissionIds: i.permissionIds,
                    }));
                })
                .catch((t) => {
                    e &&
                        T({
                            type: `error`,
                            message:
                                t instanceof Error
                                    ? t.message
                                    : `Administration indisponible.`,
                        });
                })
                .finally(() => {
                    e && fe(!1);
                }),
            () => {
                e = !1;
            }
        );
    }, []);
    (0, y.useEffect)(() => {
        let e = (e) => {
                let t =
                    typeof e.detail == `string`
                        ? e.detail
                        : adminSectionFromLocation();
                adminSectionKeys.includes(t) && o(t);
            },
            t = () => o(adminSectionFromLocation());
        return (
            window.addEventListener(`crm:admin-section-change`, e),
            window.addEventListener(`popstate`, t),
            () => {
                (window.removeEventListener(`crm:admin-section-change`, e),
                    window.removeEventListener(`popstate`, t));
            }
        );
    }, []);
    let pe = (0, y.useMemo)(
            () => new Map(t?.sites.map((e) => [e.id, e.name]) ?? []),
            [t?.sites],
        ),
        me = (0, y.useMemo)(
            () => new Map(t?.modules.map((e) => [e.id, e.name]) ?? []),
            [t?.modules],
        ),
        he = (0, y.useMemo)(
            () => new Map(G.map((e) => [e.menuKey, e.title])),
            [G],
        );
    function ge(e) {
        let n = D(e, t);
        W((t) => ({
            ...t,
            role: e,
            moduleIds: n.moduleIds,
            permissionIds: n.permissionIds,
        }));
    }
    function X(e, t) {
        K((n) => n.map((n) => (n.menuKey === e ? { ...n, ...t } : n)));
    }
    function Z(e, t) {
        J((n) => n.map((n) => (n.itemKey === e ? { ...n, ...t } : n)));
    }
    function _e(e, t) {
        K((n) => {
            let r = [...n].sort(
                    (e, t) =>
                        k(e.sortOrder) - k(t.sortOrder) ||
                        e.title.localeCompare(t.title),
                ),
                i = r.findIndex((t) => t.menuKey === e),
                a = i + t;
            if (i < 0 || a < 0 || a >= r.length) return n;
            let o = [...r];
            return (([o[i], o[a]] = [o[a], o[i]]), M(o));
        });
    }
    function ve(e, t) {
        J((n) => {
            let r = n.find((t) => t.itemKey === e);
            if (!r) return n;
            let i = n
                    .filter((e) => e.groupKey === r.groupKey)
                    .sort(
                        (e, t) =>
                            k(e.sortOrder) - k(t.sortOrder) ||
                            e.label.localeCompare(t.label),
                    ),
                a = i.findIndex((t) => t.itemKey === e),
                o = a + t;
            if (a < 0 || o < 0 || o >= i.length) return n;
            let s = [...i];
            [s[a], s[o]] = [s[o], s[a]];
            let c = new Map(M(s).map((e) => [e.itemKey, e.sortOrder]));
            return n.map((e) =>
                c.has(e.itemKey)
                    ? { ...e, sortOrder: c.get(e.itemKey) ?? e.sortOrder }
                    : e,
            );
        });
    }
    function ye(e, t) {
        J((n) => {
            let r = Math.max(
                0,
                ...n.filter((e) => e.groupKey === t).map((e) => k(e.sortOrder)),
            );
            return n.map((n) =>
                n.itemKey === e
                    ? { ...n, groupKey: t, sortOrder: String(r + 10) }
                    : n,
            );
        });
    }
    async function be(e) {
        (e.preventDefault(), I(!0), T(null));
        try {
            (await te(z), B(x), await Y(!0));
        } catch (e) {
            T({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible d enregistrer le site.`,
            });
        } finally {
            I(!1);
        }
    }
    async function xe(e) {
        if (window.confirm(`Supprimer le site "${e.name}" ?`)) {
            (R(e.id), T(null));
            try {
                (await ee(e.id),
                    z.id === e.id && B(x),
                    await Y(!1),
                    T({
                        type: `success`,
                        message: `Site "${e.name}" supprime.`,
                    }));
            } catch (e) {
                T({
                    type: `error`,
                    message:
                        e instanceof Error
                            ? e.message
                            : `Impossible de supprimer le site.`,
                });
            } finally {
                R(null);
            }
        }
    }
    async function Se(e) {
        (e.preventDefault(), I(!0), T(null));
        try {
            (await s({ ...V, sortOrder: Number(V.sortOrder) || 100 }),
                H(S),
                await Y(!0),
                g());
        } catch (e) {
            T({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible d enregistrer le module.`,
            });
        } finally {
            I(!1);
        }
    }
    async function Ce(e) {
        (e.preventDefault(), I(!0), T(null));
        try {
            (await d({
                groups: G.map((e) => ({
                    ...e,
                    sortOrder: Number(e.sortOrder) || 100,
                })),
                items: q.map((e) => ({
                    itemKey: e.itemKey,
                    groupKey: e.groupKey,
                    iconKey: e.iconKey,
                    active: e.active,
                    sortOrder: Number(e.sortOrder) || 100,
                })),
            }),
                await Y(!0),
                g());
        } catch (e) {
            T({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible d enregistrer le menu.`,
            });
        } finally {
            I(!1);
        }
    }
    async function we(e) {
        (e.preventDefault(), I(!0), T(null));
        try {
            await ne(U);
            let e = t?.sites.find((e) => e.active) ?? t?.sites[0],
                n = D(`user`, t);
            (W({
                ...C,
                siteIds: e ? [e.id] : [],
                moduleIds: n.moduleIds,
                permissionIds: n.permissionIds,
            }),
                await Y(!0));
        } catch (e) {
            T({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible d enregistrer l utilisateur.`,
            });
        } finally {
            I(!1);
        }
    }
    let Te = t?.sites.filter((e) => e.active).length ?? 0,
        Ee = t?.modules.filter((e) => e.active).length ?? 0,
        De = t?.users.filter((e) => e.active).length ?? 0,
        Q = (0, y.useMemo)(
            () =>
                [...G].sort(
                    (e, t) =>
                        k(e.sortOrder) - k(t.sortOrder) ||
                        e.title.localeCompare(t.title),
                ),
            [G],
        ),
        $ = (0, y.useMemo)(() => new Map(Q.map((e, t) => [e.menuKey, t])), [Q]),
        Oe = (0, y.useMemo)(
            () =>
                [...q].sort(
                    (e, t) =>
                        ($.get(e.groupKey) ?? 999) -
                            ($.get(t.groupKey) ?? 999) ||
                        k(e.sortOrder) - k(t.sortOrder) ||
                        e.label.localeCompare(t.label),
                ),
            [$, q],
        );
    return (0, b.jsxs)(`div`, {
        className: `animate-fade-in space-y-6`,
        children: [
            (0, b.jsx)(re, {
                title: `Administration`,
                subtitle: `Gestion CRM des sites, modules, utilisateurs et roles`,
                actions: (0, b.jsxs)(a, {
                    variant: `secondary`,
                    onClick: () => Y(!0),
                    disabled: j || F,
                    children: [
                        (0, b.jsx)(f, { icon: _.refresh }),
                        `Synchroniser`,
                    ],
                }),
            }),
            (0, b.jsxs)(`div`, {
                className: `grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4`,
                children: [
                    N(
                        `Sites actifs`,
                        String(Te),
                        _.building,
                        `Perimetres multi-sites`,
                    ),
                    N(
                        `Modules actifs`,
                        String(Ee),
                        _.category,
                        `Acces par utilisateur`,
                    ),
                    N(
                        `Utilisateurs actifs`,
                        String(De),
                        _.users,
                        `Comptes CRM`,
                    ),
                    N(
                        `Permissions`,
                        String(t?.permissions.length ?? 0),
                        _.shield,
                        `Rattachees aux roles`,
                    ),
                ],
            }),
            (0, b.jsx)(de, { notice: w }),
            j || !t
                ? (0, b.jsx)(p, {
                      className: `rounded-xl`,
                      children: (0, b.jsx)(`p`, {
                          className: `text-body-sm text-secondary-500`,
                          children: `Chargement de l administration...`,
                      }),
                  })
                : (0, b.jsxs)(b.Fragment, {
                      children: [
                          r === `users` &&
                              (0, b.jsxs)(`div`, {
                                  className: `grid grid-cols-1 gap-6 xl:grid-cols-[1fr_460px]`,
                                  children: [
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-4 flex items-center justify-between`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          children: [
                                                              (0, b.jsx)(`h2`, {
                                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                                  children: `Utilisateurs`,
                                                              }),
                                                              (0, b.jsx)(`p`, {
                                                                  className: `text-body-sm text-secondary-500`,
                                                                  children: `Selectionne un compte pour modifier ses sites, modules et permissions.`,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(u, {
                                                          variant: `primary`,
                                                          children:
                                                              t.users.length,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsx)(`div`, {
                                                  className: `space-y-3`,
                                                  children: t.users.map((e) =>
                                                      (0, b.jsx)(
                                                          P,
                                                          {
                                                              title: e.name,
                                                              subtitle: `${e.role} - ${e.siteIds.length} site(s) - ${e.moduleIds.length} module(s)`,
                                                              badge: `${e.permissionIds.length} droits`,
                                                              active: e.active,
                                                              onEdit: () =>
                                                                  W(ae(e)),
                                                          },
                                                          e.id,
                                                      ),
                                                  ),
                                              }),
                                          ],
                                      }),
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-5 flex items-center justify-between`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          children: [
                                                              (0, b.jsx)(`h2`, {
                                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                                  children: U.id
                                                                      ? `Modifier utilisateur`
                                                                      : `Nouvel utilisateur`,
                                                              }),
                                                              (0, b.jsx)(`p`, {
                                                                  className: `text-body-sm text-secondary-500`,
                                                                  children: `Les droits sont appliques directement en base.`,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(a, {
                                                          variant: `ghost`,
                                                          size: `sm`,
                                                          onClick: () => W(C),
                                                          children: `Nouveau`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(`form`, {
                                                  className: `space-y-4`,
                                                  onSubmit: we,
                                                  children: [
                                                      (0, b.jsx)(m, {
                                                          label: `Nom`,
                                                          htmlFor: `admin-user-name`,
                                                          required: !0,
                                                          children: (0, b.jsx)(
                                                              v,
                                                              {
                                                                  id: `admin-user-name`,
                                                                  value: U.name,
                                                                  onChange: (
                                                                      e,
                                                                  ) =>
                                                                      W({
                                                                          ...U,
                                                                          name: e
                                                                              .target
                                                                              .value,
                                                                      }),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `grid grid-cols-1 gap-4 sm:grid-cols-2`,
                                                          children: [
                                                              (0, b.jsx)(m, {
                                                                  label: `Role`,
                                                                  htmlFor: `admin-user-role`,
                                                                  children: (0,
                                                                  b.jsx)(i, {
                                                                      id: `admin-user-role`,
                                                                      value: U.role,
                                                                      onChange:
                                                                          (e) =>
                                                                              ge(
                                                                                  e
                                                                                      .target
                                                                                      .value,
                                                                              ),
                                                                      children:
                                                                          t.roles.map(
                                                                              (
                                                                                  e,
                                                                              ) =>
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      `option`,
                                                                                      {
                                                                                          value: e.key,
                                                                                          children:
                                                                                              e.label,
                                                                                      },
                                                                                      e.key,
                                                                                  ),
                                                                          ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Etat`,
                                                                  children: (0,
                                                                  b.jsxs)(
                                                                      `label`,
                                                                      {
                                                                          className: `flex h-10 items-center gap-2 rounded-lg border border-surface-200 px-3 dark:border-surface-700`,
                                                                          children:
                                                                              [
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      c,
                                                                                      {
                                                                                          checked:
                                                                                              U.active,
                                                                                          onChange:
                                                                                              () =>
                                                                                                  W(
                                                                                                      {
                                                                                                          ...U,
                                                                                                          active: !U.active,
                                                                                                      },
                                                                                                  ),
                                                                                      },
                                                                                  ),
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      `span`,
                                                                                      {
                                                                                          className: `text-body-sm`,
                                                                                          children: `Compte actif`,
                                                                                      },
                                                                                  ),
                                                                              ],
                                                                      },
                                                                  ),
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(m, {
                                                          label: `Sites autorises`,
                                                          children: (0, b.jsx)(
                                                              ue,
                                                              {
                                                                  items: t.sites,
                                                                  values: U.siteIds,
                                                                  getLabel: (
                                                                      e,
                                                                  ) =>
                                                                      pe.get(
                                                                          e,
                                                                      ) ??
                                                                      String(e),
                                                                  onToggle: (
                                                                      e,
                                                                  ) =>
                                                                      W({
                                                                          ...U,
                                                                          siteIds:
                                                                              E(
                                                                                  U.siteIds,
                                                                                  e,
                                                                              ),
                                                                      }),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsx)(m, {
                                                          label: `Modules autorises`,
                                                          children: (0, b.jsx)(
                                                              ue,
                                                              {
                                                                  items: t.modules,
                                                                  values: U.moduleIds,
                                                                  getLabel: (
                                                                      e,
                                                                  ) =>
                                                                      me.get(
                                                                          e,
                                                                      ) ??
                                                                      String(e),
                                                                  onToggle: (
                                                                      e,
                                                                  ) =>
                                                                      W({
                                                                          ...U,
                                                                          moduleIds:
                                                                              E(
                                                                                  U.moduleIds,
                                                                                  e,
                                                                              ),
                                                                      }),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsx)(m, {
                                                          label: `Permissions`,
                                                          children: (0, b.jsx)(
                                                              `div`,
                                                              {
                                                                  className: `max-h-[320px] space-y-2 overflow-y-auto pr-1`,
                                                                  children:
                                                                      t.permissions.map(
                                                                          (e) =>
                                                                              (0,
                                                                              b.jsxs)(
                                                                                  `label`,
                                                                                  {
                                                                                      className: `flex cursor-pointer items-start gap-2 rounded-lg border border-surface-200 px-3 py-2 dark:border-surface-700`,
                                                                                      children:
                                                                                          [
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  c,
                                                                                                  {
                                                                                                      checked:
                                                                                                          U.permissionIds.includes(
                                                                                                              e.id,
                                                                                                          ),
                                                                                                      onChange:
                                                                                                          () =>
                                                                                                              W(
                                                                                                                  {
                                                                                                                      ...U,
                                                                                                                      permissionIds:
                                                                                                                          E(
                                                                                                                              U.permissionIds,
                                                                                                                              e.id,
                                                                                                                          ),
                                                                                                                  },
                                                                                                              ),
                                                                                                  },
                                                                                              ),
                                                                                              (0,
                                                                                              b.jsxs)(
                                                                                                  `span`,
                                                                                                  {
                                                                                                      className: `min-w-0`,
                                                                                                      children:
                                                                                                          [
                                                                                                              (0,
                                                                                                              b.jsx)(
                                                                                                                  `span`,
                                                                                                                  {
                                                                                                                      className: `block text-body-sm font-medium text-secondary-800 dark:text-secondary-100`,
                                                                                                                      children:
                                                                                                                          e.label,
                                                                                                                  },
                                                                                                              ),
                                                                                                              (0,
                                                                                                              b.jsx)(
                                                                                                                  `span`,
                                                                                                                  {
                                                                                                                      className: `block text-ui-xs text-secondary-400`,
                                                                                                                      children:
                                                                                                                          e.name,
                                                                                                                  },
                                                                                                              ),
                                                                                                          ],
                                                                                                  },
                                                                                              ),
                                                                                          ],
                                                                                  },
                                                                                  e.id,
                                                                              ),
                                                                      ),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsxs)(a, {
                                                          type: `submit`,
                                                          fullWidth: !0,
                                                          loading: F,
                                                          children: [
                                                              (0, b.jsx)(f, {
                                                                  icon: _.deviceFloppy,
                                                              }),
                                                              `Enregistrer utilisateur`,
                                                          ],
                                                      }),
                                                  ],
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                          r === `sites` &&
                              (0, b.jsxs)(`div`, {
                                  className: `grid grid-cols-1 gap-6 xl:grid-cols-[1fr_420px]`,
                                  children: [
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-4`,
                                                  children: [
                                                      (0, b.jsx)(`h2`, {
                                                          className: `heading-5 text-secondary-900 dark:text-white`,
                                                          children: `Sites`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-body-sm text-secondary-500`,
                                                          children: `Creation et modification des sites rattaches aux utilisateurs.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsx)(`div`, {
                                                  className: `space-y-3`,
                                                  children: t.sites.map((e) =>
                                                      (0, b.jsx)(
                                                          P,
                                                          {
                                                              title: e.name,
                                                              subtitle: `${e.slug} - ${siteHoursLabel(oe(e))}`,
                                                              active: e.active,
                                                              onEdit: () =>
                                                                  B(oe(e)),
                                                              onDelete: () =>
                                                                  xe(e),
                                                              deleteLoading:
                                                                  L === e.id,
                                                          },
                                                          e.id,
                                                      ),
                                                  ),
                                              }),
                                          ],
                                      }),
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-5 flex items-center justify-between`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          children: [
                                                              (0, b.jsx)(`h2`, {
                                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                                  children: z.id
                                                                      ? `Modifier site`
                                                                      : `Nouveau site`,
                                                              }),
                                                              (0, b.jsx)(`p`, {
                                                                  className: `text-body-sm text-secondary-500`,
                                                                  children: `Le slug est genere automatiquement.`,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(a, {
                                                          variant: `ghost`,
                                                          size: `sm`,
                                                          onClick: () => B(x),
                                                          children: `Nouveau`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(`form`, {
                                                  className: `space-y-4`,
                                                  onSubmit: be,
                                                  children: [
                                                      (0, b.jsx)(m, {
                                                          label: `Nom du site`,
                                                          htmlFor: `admin-site-name`,
                                                          required: !0,
                                                          children: (0, b.jsx)(
                                                              v,
                                                              {
                                                                  id: `admin-site-name`,
                                                                  value: z.name,
                                                                  onChange: (
                                                                      e,
                                                                  ) =>
                                                                      B({
                                                                          ...z,
                                                                          name: e
                                                                              .target
                                                                              .value,
                                                                      }),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsx)(m, {
                                                          label: `Etat`,
                                                          children: (0, b.jsxs)(
                                                              `label`,
                                                              {
                                                                  className: `flex h-10 items-center gap-2 rounded-lg border border-surface-200 px-3 dark:border-surface-700`,
                                                                  children: [
                                                                      (0,
                                                                      b.jsx)(
                                                                          c,
                                                                          {
                                                                              checked:
                                                                                  z.active,
                                                                              onChange:
                                                                                  () =>
                                                                                      B(
                                                                                          {
                                                                                              ...z,
                                                                                              active: !z.active,
                                                                                          },
                                                                                      ),
                                                                          },
                                                                      ),
                                                                      (0,
                                                                      b.jsx)(
                                                                          `span`,
                                                                          {
                                                                              className: `text-body-sm`,
                                                                              children: `Site actif`,
                                                                          },
                                                                      ),
                                                                  ],
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `grid grid-cols-1 gap-4 sm:grid-cols-2`,
                                                          children: [
                                                              (0, b.jsx)(m, {
                                                                  label: `Matin debut`,
                                                                  htmlFor: `admin-site-morning-start`,
                                                                  required: !0,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-site-morning-start`,
                                                                      type: `time`,
                                                                      value: z.morningStart,
                                                                      onChange:
                                                                          (e) =>
                                                                              B(
                                                                                  {
                                                                                      ...z,
                                                                                      morningStart:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Matin fin`,
                                                                  htmlFor: `admin-site-morning-end`,
                                                                  required: !0,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-site-morning-end`,
                                                                      type: `time`,
                                                                      value: z.morningEnd,
                                                                      onChange:
                                                                          (e) =>
                                                                              B(
                                                                                  {
                                                                                      ...z,
                                                                                      morningEnd:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Apres-midi debut`,
                                                                  htmlFor: `admin-site-afternoon-start`,
                                                                  required: !0,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-site-afternoon-start`,
                                                                      type: `time`,
                                                                      value: z.afternoonStart,
                                                                      onChange:
                                                                          (e) =>
                                                                              B(
                                                                                  {
                                                                                      ...z,
                                                                                      afternoonStart:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Apres-midi fin`,
                                                                  htmlFor: `admin-site-afternoon-end`,
                                                                  required: !0,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-site-afternoon-end`,
                                                                      type: `time`,
                                                                      value: z.afternoonEnd,
                                                                      onChange:
                                                                          (e) =>
                                                                              B(
                                                                                  {
                                                                                      ...z,
                                                                                      afternoonEnd:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsxs)(a, {
                                                          type: `submit`,
                                                          fullWidth: !0,
                                                          loading: F,
                                                          children: [
                                                              (0, b.jsx)(f, {
                                                                  icon: _.deviceFloppy,
                                                              }),
                                                              z.id
                                                                  ? `Modifier site`
                                                                  : `Enregistrer site`,
                                                          ],
                                                      }),
                                                      z.id &&
                                                          (0, b.jsxs)(a, {
                                                              type: `button`,
                                                              variant: `danger`,
                                                              fullWidth: !0,
                                                              loading:
                                                                  L === z.id,
                                                              onClick: () => {
                                                                  let e =
                                                                      t.sites.find(
                                                                          (e) =>
                                                                              e.id ===
                                                                              z.id,
                                                                      );
                                                                  e && xe(e);
                                                              },
                                                              children: [
                                                                  (0, b.jsx)(
                                                                      f,
                                                                      {
                                                                          icon: _.trash,
                                                                      },
                                                                  ),
                                                                  `Supprimer site`,
                                                              ],
                                                          }),
                                                  ],
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                          r === `modules` &&
                              (0, b.jsxs)(`div`, {
                                  className: `grid grid-cols-1 gap-6 xl:grid-cols-[1fr_460px]`,
                                  children: [
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-4`,
                                                  children: [
                                                      (0, b.jsx)(`h2`, {
                                                          className: `heading-5 text-secondary-900 dark:text-white`,
                                                          children: `Modules`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-body-sm text-secondary-500`,
                                                          children: `Modules visibles dans le CRM et attribuables aux utilisateurs.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsx)(`div`, {
                                                  className: `space-y-3`,
                                                  children: t.modules.map((e) =>
                                                      (0, b.jsx)(
                                                          P,
                                                          {
                                                              title: e.name,
                                                              subtitle: `${e.slug} - ${e.routePath || `sans route`}${e.showMenuBadge && e.menuBadge ? ` - badge ${e.menuBadge}` : ``}`,
                                                              badge: e.sortOrder,
                                                              active: e.active,
                                                              onEdit: () =>
                                                                  H(se(e)),
                                                          },
                                                          e.id,
                                                      ),
                                                  ),
                                              }),
                                          ],
                                      }),
                                      (0, b.jsxs)(p, {
                                          className: `rounded-xl`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  className: `mb-5 flex items-center justify-between`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          children: [
                                                              (0, b.jsx)(`h2`, {
                                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                                  children: V.id
                                                                      ? `Modifier module`
                                                                      : `Nouveau module`,
                                                              }),
                                                              (0, b.jsx)(`p`, {
                                                                  className: `text-body-sm text-secondary-500`,
                                                                  children: `Slug, route et ordre du menu interne.`,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(a, {
                                                          variant: `ghost`,
                                                          size: `sm`,
                                                          onClick: () => H(S),
                                                          children: `Nouveau`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(`form`, {
                                                  className: `space-y-4`,
                                                  onSubmit: Se,
                                                  children: [
                                                      (0, b.jsx)(m, {
                                                          label: `Nom`,
                                                          htmlFor: `admin-module-name`,
                                                          required: !0,
                                                          children: (0, b.jsx)(
                                                              v,
                                                              {
                                                                  id: `admin-module-name`,
                                                                  value: V.name,
                                                                  onChange: (
                                                                      e,
                                                                  ) => {
                                                                      let t =
                                                                          e
                                                                              .target
                                                                              .value;
                                                                      H({
                                                                          ...V,
                                                                          name: t,
                                                                          slug: V.id
                                                                              ? V.slug
                                                                              : ie(
                                                                                    t,
                                                                                ),
                                                                          routePath:
                                                                              V.id
                                                                                  ? V.routePath
                                                                                  : `/${ie(t)}`,
                                                                      });
                                                                  },
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `grid grid-cols-1 gap-4 sm:grid-cols-2`,
                                                          children: [
                                                              (0, b.jsx)(m, {
                                                                  label: `Slug`,
                                                                  htmlFor: `admin-module-slug`,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-module-slug`,
                                                                      value: V.slug,
                                                                      onChange:
                                                                          (e) =>
                                                                              H(
                                                                                  {
                                                                                      ...V,
                                                                                      slug: e
                                                                                          .target
                                                                                          .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Route`,
                                                                  htmlFor: `admin-module-route`,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-module-route`,
                                                                      value: V.routePath,
                                                                      onChange:
                                                                          (e) =>
                                                                              H(
                                                                                  {
                                                                                      ...V,
                                                                                      routePath:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(m, {
                                                          label: `Description`,
                                                          htmlFor: `admin-module-description`,
                                                          children: (0, b.jsx)(
                                                              e,
                                                              {
                                                                  id: `admin-module-description`,
                                                                  rows: 3,
                                                                  value: V.description,
                                                                  onChange: (
                                                                      e,
                                                                  ) =>
                                                                      H({
                                                                          ...V,
                                                                          description:
                                                                              e
                                                                                  .target
                                                                                  .value,
                                                                      }),
                                                              },
                                                          ),
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `grid grid-cols-1 gap-4 sm:grid-cols-[1fr_150px]`,
                                                          children: [
                                                              (0, b.jsx)(m, {
                                                                  label: `Badge menu`,
                                                                  htmlFor: `admin-module-badge`,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-module-badge`,
                                                                      value: V.menuBadge,
                                                                      maxLength: 40,
                                                                      onChange:
                                                                          (e) =>
                                                                              H(
                                                                                  {
                                                                                      ...V,
                                                                                      menuBadge:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Affichage`,
                                                                  children: (0,
                                                                  b.jsxs)(
                                                                      `label`,
                                                                      {
                                                                          className: `flex h-10 items-center gap-2 rounded-lg border border-surface-200 px-3 dark:border-surface-700`,
                                                                          children:
                                                                              [
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      c,
                                                                                      {
                                                                                          checked:
                                                                                              V.showMenuBadge,
                                                                                          onChange:
                                                                                              () =>
                                                                                                  H(
                                                                                                      {
                                                                                                          ...V,
                                                                                                          showMenuBadge:
                                                                                                              !V.showMenuBadge,
                                                                                                      },
                                                                                                  ),
                                                                                      },
                                                                                  ),
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      `span`,
                                                                                      {
                                                                                          className: `text-body-sm`,
                                                                                          children: `Afficher badge`,
                                                                                      },
                                                                                  ),
                                                                              ],
                                                                      },
                                                                  ),
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `grid grid-cols-1 gap-4 sm:grid-cols-2`,
                                                          children: [
                                                              (0, b.jsx)(m, {
                                                                  label: `Ordre`,
                                                                  htmlFor: `admin-module-order`,
                                                                  children: (0,
                                                                  b.jsx)(v, {
                                                                      id: `admin-module-order`,
                                                                      type: `number`,
                                                                      value: V.sortOrder,
                                                                      onChange:
                                                                          (e) =>
                                                                              H(
                                                                                  {
                                                                                      ...V,
                                                                                      sortOrder:
                                                                                          e
                                                                                              .target
                                                                                              .value,
                                                                                  },
                                                                              ),
                                                                  }),
                                                              }),
                                                              (0, b.jsx)(m, {
                                                                  label: `Etat`,
                                                                  children: (0,
                                                                  b.jsxs)(
                                                                      `label`,
                                                                      {
                                                                          className: `flex h-10 items-center gap-2 rounded-lg border border-surface-200 px-3 dark:border-surface-700`,
                                                                          children:
                                                                              [
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      c,
                                                                                      {
                                                                                          checked:
                                                                                              V.active,
                                                                                          onChange:
                                                                                              () =>
                                                                                                  H(
                                                                                                      {
                                                                                                          ...V,
                                                                                                          active: !V.active,
                                                                                                      },
                                                                                                  ),
                                                                                      },
                                                                                  ),
                                                                                  (0,
                                                                                  b.jsx)(
                                                                                      `span`,
                                                                                      {
                                                                                          className: `text-body-sm`,
                                                                                          children: `Module actif`,
                                                                                      },
                                                                                  ),
                                                                              ],
                                                                      },
                                                                  ),
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsxs)(a, {
                                                          type: `submit`,
                                                          fullWidth: !0,
                                                          loading: F,
                                                          children: [
                                                              (0, b.jsx)(f, {
                                                                  icon: _.deviceFloppy,
                                                              }),
                                                              `Enregistrer module`,
                                                          ],
                                                      }),
                                                  ],
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                          r === `menu` &&
                              (0, b.jsxs)(`form`, {
                                  className: `space-y-6`,
                                  onSubmit: Ce,
                                  children: [
                                      (0, b.jsxs)(`div`, {
                                          className: `grid grid-cols-1 gap-6 2xl:grid-cols-[480px_1fr]`,
                                          children: [
                                              (0, b.jsxs)(p, {
                                                  className: `rounded-xl`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          className: `mb-4 flex items-start justify-between gap-3`,
                                                          children: [
                                                              (0, b.jsxs)(
                                                                  `div`,
                                                                  {
                                                                      children:
                                                                          [
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `h2`,
                                                                                  {
                                                                                      className: `heading-5 text-secondary-900 dark:text-white`,
                                                                                      children: `Groupes du menu`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `p`,
                                                                                  {
                                                                                      className: `text-body-sm text-secondary-500`,
                                                                                      children: `Titre, ordre et affichage des sections.`,
                                                                                  },
                                                                              ),
                                                                          ],
                                                                  },
                                                              ),
                                                              (0, b.jsx)(u, {
                                                                  variant: `primary`,
                                                                  children:
                                                                      G.length,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsx)(`div`, {
                                                          className: `space-y-3`,
                                                          children: Q.map((e) =>
                                                              (0, b.jsxs)(
                                                                  `div`,
                                                                  {
                                                                      className: `rounded-xl border border-surface-200 p-3 dark:border-surface-700`,
                                                                      children:
                                                                          [
                                                                              (0,
                                                                              b.jsxs)(
                                                                                  `div`,
                                                                                  {
                                                                                      className: `mb-3 flex items-center justify-between gap-2`,
                                                                                      children:
                                                                                          [
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  u,
                                                                                                  {
                                                                                                      variant:
                                                                                                          e.active
                                                                                                              ? `success`
                                                                                                              : `neutral`,
                                                                                                      children:
                                                                                                          e.active
                                                                                                              ? `Visible`
                                                                                                              : `Masque`,
                                                                                                  },
                                                                                              ),
                                                                                              (0,
                                                                                              b.jsxs)(
                                                                                                  `div`,
                                                                                                  {
                                                                                                      className: `flex items-center gap-1`,
                                                                                                      children:
                                                                                                          [
                                                                                                              (0,
                                                                                                              b.jsx)(
                                                                                                                  a,
                                                                                                                  {
                                                                                                                      type: `button`,
                                                                                                                      variant: `ghost`,
                                                                                                                      size: `sm`,
                                                                                                                      iconOnly:
                                                                                                                          !0,
                                                                                                                      "aria-label": `Monter le groupe`,
                                                                                                                      onClick:
                                                                                                                          () =>
                                                                                                                              _e(
                                                                                                                                  e.menuKey,
                                                                                                                                  -1,
                                                                                                                              ),
                                                                                                                      children:
                                                                                                                          (0,
                                                                                                                          b.jsx)(
                                                                                                                              f,
                                                                                                                              {
                                                                                                                                  icon: _.arrowUp,
                                                                                                                                  className: `h-4 w-4`,
                                                                                                                              },
                                                                                                                          ),
                                                                                                                  },
                                                                                                              ),
                                                                                                              (0,
                                                                                                              b.jsx)(
                                                                                                                  a,
                                                                                                                  {
                                                                                                                      type: `button`,
                                                                                                                      variant: `ghost`,
                                                                                                                      size: `sm`,
                                                                                                                      iconOnly:
                                                                                                                          !0,
                                                                                                                      "aria-label": `Descendre le groupe`,
                                                                                                                      onClick:
                                                                                                                          () =>
                                                                                                                              _e(
                                                                                                                                  e.menuKey,
                                                                                                                                  1,
                                                                                                                              ),
                                                                                                                      children:
                                                                                                                          (0,
                                                                                                                          b.jsx)(
                                                                                                                              f,
                                                                                                                              {
                                                                                                                                  icon: _.arrowDown,
                                                                                                                                  className: `h-4 w-4`,
                                                                                                                              },
                                                                                                                          ),
                                                                                                                  },
                                                                                                              ),
                                                                                                          ],
                                                                                                  },
                                                                                              ),
                                                                                          ],
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsxs)(
                                                                                  `div`,
                                                                                  {
                                                                                      className: `grid grid-cols-1 gap-3 sm:grid-cols-[1fr_90px]`,
                                                                                      children:
                                                                                          [
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  m,
                                                                                                  {
                                                                                                      label: `Titre`,
                                                                                                      htmlFor: `menu-group-title-${e.menuKey}`,
                                                                                                      children:
                                                                                                          (0,
                                                                                                          b.jsx)(
                                                                                                              v,
                                                                                                              {
                                                                                                                  id: `menu-group-title-${e.menuKey}`,
                                                                                                                  value: e.title,
                                                                                                                  onChange:
                                                                                                                      (
                                                                                                                          t,
                                                                                                                      ) =>
                                                                                                                          X(
                                                                                                                              e.menuKey,
                                                                                                                              {
                                                                                                                                  title: t
                                                                                                                                      .target
                                                                                                                                      .value,
                                                                                                                              },
                                                                                                                          ),
                                                                                                              },
                                                                                                          ),
                                                                                                  },
                                                                                              ),
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  m,
                                                                                                  {
                                                                                                      label: `Ordre`,
                                                                                                      htmlFor: `menu-group-order-${e.menuKey}`,
                                                                                                      children:
                                                                                                          (0,
                                                                                                          b.jsx)(
                                                                                                              v,
                                                                                                              {
                                                                                                                  id: `menu-group-order-${e.menuKey}`,
                                                                                                                  type: `number`,
                                                                                                                  value: e.sortOrder,
                                                                                                                  onChange:
                                                                                                                      (
                                                                                                                          t,
                                                                                                                      ) =>
                                                                                                                          X(
                                                                                                                              e.menuKey,
                                                                                                                              {
                                                                                                                                  sortOrder:
                                                                                                                                      t
                                                                                                                                          .target
                                                                                                                                          .value,
                                                                                                                              },
                                                                                                                          ),
                                                                                                              },
                                                                                                          ),
                                                                                                  },
                                                                                              ),
                                                                                          ],
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsxs)(
                                                                                  `label`,
                                                                                  {
                                                                                      className: `mt-3 flex h-10 items-center gap-2 rounded-lg border border-surface-200 px-3 dark:border-surface-700`,
                                                                                      children:
                                                                                          [
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  c,
                                                                                                  {
                                                                                                      checked:
                                                                                                          e.active,
                                                                                                      onChange:
                                                                                                          () =>
                                                                                                              X(
                                                                                                                  e.menuKey,
                                                                                                                  {
                                                                                                                      active: !e.active,
                                                                                                                  },
                                                                                                              ),
                                                                                                  },
                                                                                              ),
                                                                                              (0,
                                                                                              b.jsx)(
                                                                                                  `span`,
                                                                                                  {
                                                                                                      className: `text-body-sm`,
                                                                                                      children: `Afficher ce groupe`,
                                                                                                  },
                                                                                              ),
                                                                                          ],
                                                                                  },
                                                                              ),
                                                                          ],
                                                                  },
                                                                  e.menuKey,
                                                              ),
                                                          ),
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(p, {
                                                  className: `rounded-xl`,
                                                  children: [
                                                      (0, b.jsxs)(`div`, {
                                                          className: `mb-4 flex flex-wrap items-start justify-between gap-3`,
                                                          children: [
                                                              (0, b.jsxs)(
                                                                  `div`,
                                                                  {
                                                                      children:
                                                                          [
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `h2`,
                                                                                  {
                                                                                      className: `heading-5 text-secondary-900 dark:text-white`,
                                                                                      children: `Liens du menu`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `p`,
                                                                                  {
                                                                                      className: `text-body-sm text-secondary-500`,
                                                                                      children: `Groupe, ordre et affichage de chaque lien principal.`,
                                                                                  },
                                                                              ),
                                                                          ],
                                                                  },
                                                              ),
                                                              (0, b.jsx)(u, {
                                                                  variant: `primary`,
                                                                  children:
                                                                      q.length,
                                                              }),
                                                          ],
                                                      }),
                                                      (0, b.jsxs)(`div`, {
                                                          className: `rounded-xl border border-surface-200 dark:border-surface-700`,
                                                          children: [
                                                              (0, b.jsxs)(
                                                                  `div`,
                                                                  {
                                                                      className: `hidden grid-cols-[1fr_190px_230px_90px_128px] gap-3 rounded-t-xl bg-surface-50 px-4 py-3 text-label text-secondary-500 dark:bg-surface-900 lg:grid`,
                                                                      children:
                                                                          [
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `span`,
                                                                                  {
                                                                                      children: `Lien`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `span`,
                                                                                  {
                                                                                      children: `Groupe`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `span`,
                                                                                  {
                                                                                      children: `Icône`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `span`,
                                                                                  {
                                                                                      children: `Ordre`,
                                                                                  },
                                                                              ),
                                                                              (0,
                                                                              b.jsx)(
                                                                                  `span`,
                                                                                  {
                                                                                      children: `Actions`,
                                                                                  },
                                                                              ),
                                                                          ],
                                                                  },
                                                              ),
                                                              (0, b.jsx)(
                                                                  `div`,
                                                                  {
                                                                      className: `divide-y divide-surface-200 dark:divide-surface-700`,
                                                                      children:
                                                                          Oe.map(
                                                                              (
                                                                                  e,
                                                                              ) =>
                                                                                  (0,
                                                                                  b.jsxs)(
                                                                                      `div`,
                                                                                      {
                                                                                          className: `grid grid-cols-1 gap-3 px-4 py-4 lg:grid-cols-[1fr_190px_230px_90px_128px] lg:items-center`,
                                                                                          children:
                                                                                              [
                                                                                                  (0,
                                                                                                  b.jsxs)(
                                                                                                      `div`,
                                                                                                      {
                                                                                                          className: `min-w-0`,
                                                                                                          children:
                                                                                                              [
                                                                                                                  (0,
                                                                                                                  b.jsxs)(
                                                                                                                      `div`,
                                                                                                                      {
                                                                                                                          className: `flex min-w-0 items-center gap-2`,
                                                                                                                          children:
                                                                                                                              [
                                                                                                                                  (0,
                                                                                                                                  b.jsx)(
                                                                                                                                      `span`,
                                                                                                                                      {
                                                                                                                                          className: `flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-theme-primary/10 text-theme-primary`,
                                                                                                                                          children:
                                                                                                                                              (0,
                                                                                                                                              b.jsx)(
                                                                                                                                                  f,
                                                                                                                                                  {
                                                                                                                                                      icon: A(
                                                                                                                                                          e.iconKey,
                                                                                                                                                      ),
                                                                                                                                                      className: `h-4 w-4`,
                                                                                                                                                  },
                                                                                                                                              ),
                                                                                                                                      },
                                                                                                                                  ),
                                                                                                                                  (0,
                                                                                                                                  b.jsx)(
                                                                                                                                      `p`,
                                                                                                                                      {
                                                                                                                                          className: `truncate text-label text-secondary-900 dark:text-white`,
                                                                                                                                          children:
                                                                                                                                              e.label,
                                                                                                                                      },
                                                                                                                                  ),
                                                                                                                              ],
                                                                                                                      },
                                                                                                                  ),
                                                                                                                  (0,
                                                                                                                  b.jsx)(
                                                                                                                      `p`,
                                                                                                                      {
                                                                                                                          className: `truncate text-ui-xs text-secondary-400`,
                                                                                                                          children:
                                                                                                                              e.itemKey,
                                                                                                                      },
                                                                                                                  ),
                                                                                                                  (0,
                                                                                                                  b.jsx)(
                                                                                                                      `p`,
                                                                                                                      {
                                                                                                                          className: `mt-1 text-ui-xs text-secondary-500 lg:hidden`,
                                                                                                                          children:
                                                                                                                              he.get(
                                                                                                                                  e.groupKey,
                                                                                                                              ) ??
                                                                                                                              e.groupKey,
                                                                                                                      },
                                                                                                                  ),
                                                                                                              ],
                                                                                                      },
                                                                                                  ),
                                                                                                  (0,
                                                                                                  b.jsx)(
                                                                                                      i,
                                                                                                      {
                                                                                                          value: e.groupKey,
                                                                                                          onChange:
                                                                                                              (
                                                                                                                  t,
                                                                                                              ) =>
                                                                                                                  ye(
                                                                                                                      e.itemKey,
                                                                                                                      t
                                                                                                                          .target
                                                                                                                          .value,
                                                                                                                  ),
                                                                                                          children:
                                                                                                              Q.map(
                                                                                                                  (
                                                                                                                      e,
                                                                                                                  ) =>
                                                                                                                      (0,
                                                                                                                      b.jsx)(
                                                                                                                          `option`,
                                                                                                                          {
                                                                                                                              value: e.menuKey,
                                                                                                                              children:
                                                                                                                                  e.title,
                                                                                                                          },
                                                                                                                          e.menuKey,
                                                                                                                      ),
                                                                                                              ),
                                                                                                      },
                                                                                                  ),
                                                                                                  (0,
                                                                                                  b.jsx)(
                                                                                                      le,
                                                                                                      {
                                                                                                          value:
                                                                                                              e.iconKey ||
                                                                                                              `category`,
                                                                                                          label: e.label,
                                                                                                          onChange:
                                                                                                              (
                                                                                                                  t,
                                                                                                              ) =>
                                                                                                                  Z(
                                                                                                                      e.itemKey,
                                                                                                                      {
                                                                                                                          iconKey:
                                                                                                                              t,
                                                                                                                      },
                                                                                                                  ),
                                                                                                      },
                                                                                                  ),
                                                                                                  (0,
                                                                                                  b.jsx)(
                                                                                                      v,
                                                                                                      {
                                                                                                          type: `number`,
                                                                                                          value: e.sortOrder,
                                                                                                          onChange:
                                                                                                              (
                                                                                                                  t,
                                                                                                              ) =>
                                                                                                                  Z(
                                                                                                                      e.itemKey,
                                                                                                                      {
                                                                                                                          sortOrder:
                                                                                                                              t
                                                                                                                                  .target
                                                                                                                                  .value,
                                                                                                                      },
                                                                                                                  ),
                                                                                                      },
                                                                                                  ),
                                                                                                  (0,
                                                                                                  b.jsxs)(
                                                                                                      `div`,
                                                                                                      {
                                                                                                          className: `flex items-center gap-1`,
                                                                                                          children:
                                                                                                              [
                                                                                                                  (0,
                                                                                                                  b.jsx)(
                                                                                                                      a,
                                                                                                                      {
                                                                                                                          type: `button`,
                                                                                                                          variant: `ghost`,
                                                                                                                          size: `sm`,
                                                                                                                          iconOnly:
                                                                                                                              !0,
                                                                                                                          "aria-label": `Monter le lien`,
                                                                                                                          onClick:
                                                                                                                              () =>
                                                                                                                                  ve(
                                                                                                                                      e.itemKey,
                                                                                                                                      -1,
                                                                                                                                  ),
                                                                                                                          children:
                                                                                                                              (0,
                                                                                                                              b.jsx)(
                                                                                                                                  f,
                                                                                                                                  {
                                                                                                                                      icon: _.arrowUp,
                                                                                                                                      className: `h-4 w-4`,
                                                                                                                                  },
                                                                                                                              ),
                                                                                                                      },
                                                                                                                  ),
                                                                                                                  (0,
                                                                                                                  b.jsx)(
                                                                                                                      a,
                                                                                                                      {
                                                                                                                          type: `button`,
                                                                                                                          variant: `ghost`,
                                                                                                                          size: `sm`,
                                                                                                                          iconOnly:
                                                                                                                              !0,
                                                                                                                          "aria-label": `Descendre le lien`,
                                                                                                                          onClick:
                                                                                                                              () =>
                                                                                                                                  ve(
                                                                                                                                      e.itemKey,
                                                                                                                                      1,
                                                                                                                                  ),
                                                                                                                          children:
                                                                                                                              (0,
                                                                                                                              b.jsx)(
                                                                                                                                  f,
                                                                                                                                  {
                                                                                                                                      icon: _.arrowDown,
                                                                                                                                      className: `h-4 w-4`,
                                                                                                                                  },
                                                                                                                              ),
                                                                                                                      },
                                                                                                                  ),
                                                                                                                  (0,
                                                                                                                  b.jsx)(
                                                                                                                      a,
                                                                                                                      {
                                                                                                                          type: `button`,
                                                                                                                          variant:
                                                                                                                              e.active
                                                                                                                                  ? `secondary`
                                                                                                                                  : `ghost`,
                                                                                                                          size: `sm`,
                                                                                                                          iconOnly:
                                                                                                                              !0,
                                                                                                                          "aria-label":
                                                                                                                              e.active
                                                                                                                                  ? `Masquer le lien`
                                                                                                                                  : `Afficher le lien`,
                                                                                                                          onClick:
                                                                                                                              () =>
                                                                                                                                  Z(
                                                                                                                                      e.itemKey,
                                                                                                                                      {
                                                                                                                                          active: !e.active,
                                                                                                                                      },
                                                                                                                                  ),
                                                                                                                          children:
                                                                                                                              (0,
                                                                                                                              b.jsx)(
                                                                                                                                  f,
                                                                                                                                  {
                                                                                                                                      icon: e.active
                                                                                                                                          ? _.eye
                                                                                                                                          : _.x,
                                                                                                                                      className: `h-4 w-4`,
                                                                                                                                  },
                                                                                                                              ),
                                                                                                                      },
                                                                                                                  ),
                                                                                                              ],
                                                                                                      },
                                                                                                  ),
                                                                                              ],
                                                                                      },
                                                                                      e.itemKey,
                                                                                  ),
                                                                          ),
                                                                  },
                                                              ),
                                                          ],
                                                      }),
                                                  ],
                                              }),
                                          ],
                                      }),
                                      (0, b.jsx)(`div`, {
                                          className: `flex justify-end`,
                                          children: (0, b.jsxs)(a, {
                                              type: `submit`,
                                              loading: F,
                                              children: [
                                                  (0, b.jsx)(f, {
                                                      icon: _.deviceFloppy,
                                                  }),
                                                  `Enregistrer menu`,
                                              ],
                                          }),
                                      }),
                                  ],
                              }),
                          r === `equipment` &&
                              (0, b.jsxs)(p, {
                                  className: `rounded-xl`,
                                  children: [
                                      (0, b.jsxs)(`div`, {
                                          className: `mb-5 flex flex-wrap items-start justify-between gap-3`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  children: [
                                                      (0, b.jsx)(`h2`, {
                                                          className: `heading-5 text-secondary-900 dark:text-white`,
                                                          children: `Location materiel`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-body-sm text-secondary-500`,
                                                          children: `Gestion du parc materiel, des locations et des categories.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsx)(u, {
                                                  variant: `primary`,
                                                  children: `Administration`,
                                              }),
                                          ],
                                      }),
                                      (0, b.jsxs)(`div`, {
                                          className: `grid grid-cols-1 gap-4 md:grid-cols-3`,
                                          children: [
                                              (0, b.jsxs)(`a`, {
                                                  href: `/admin/crm-equipment-items`,
                                                  className: `rounded-xl border border-surface-200 bg-white p-4 transition-colors hover:border-theme-primary/60 hover:bg-theme-primary/5 dark:border-surface-700 dark:bg-surface-900`,
                                                  children: [
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-label text-secondary-900 dark:text-white`,
                                                          children: `Materiel`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `mt-1 text-body-sm text-secondary-500`,
                                                          children: `Creer, modifier, image, prix et duree de location.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(`a`, {
                                                  href: `/admin/crm-equipment-rentals`,
                                                  className: `rounded-xl border border-surface-200 bg-white p-4 transition-colors hover:border-theme-primary/60 hover:bg-theme-primary/5 dark:border-surface-700 dark:bg-surface-900`,
                                                  children: [
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-label text-secondary-900 dark:text-white`,
                                                          children: `Locations`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `mt-1 text-body-sm text-secondary-500`,
                                                          children: `Consulter et corriger les reservations materiel.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsxs)(`a`, {
                                                  href: `/admin/crm-equipment-categories`,
                                                  className: `rounded-xl border border-surface-200 bg-white p-4 transition-colors hover:border-theme-primary/60 hover:bg-theme-primary/5 dark:border-surface-700 dark:bg-surface-900`,
                                                  children: [
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-label text-secondary-900 dark:text-white`,
                                                          children: `Categories`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `mt-1 text-body-sm text-secondary-500`,
                                                          children: `Organiser les familles de materiel.`,
                                                      }),
                                                  ],
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                          r === `theme` &&
                              (0, b.jsxs)(p, {
                                  className: `rounded-xl`,
                                  children: [
                                      (0, b.jsxs)(`div`, {
                                          className: `mb-5 flex flex-wrap items-start justify-between gap-3`,
                                          children: [
                                              (0, b.jsxs)(`div`, {
                                                  children: [
                                                      (0, b.jsx)(`h2`, {
                                                          className: `heading-5 text-secondary-900 dark:text-white`,
                                                          children: `Paramètres du thème`,
                                                      }),
                                                      (0, b.jsx)(`p`, {
                                                          className: `text-body-sm text-secondary-500`,
                                                          children: `Réglages visuels appliqués au CRM.`,
                                                      }),
                                                  ],
                                              }),
                                              (0, b.jsx)(u, {
                                                  variant: `primary`,
                                                  children: `CRM`,
                                              }),
                                          ],
                                      }),
                                      (0, b.jsx)(l, {}),
                                  ],
                              }),
                          r === `roles` &&
                              (0, b.jsx)(`div`, {
                                  className: `grid grid-cols-1 gap-6 xl:grid-cols-2`,
                                  children: t.roles.map((e) => {
                                      let n = e.moduleSlugs
                                          .map(
                                              (e) =>
                                                  t.modules.find(
                                                      (t) => t.slug === e,
                                                  )?.name,
                                          )
                                          .filter(Boolean);
                                      return (0, b.jsxs)(
                                          p,
                                          {
                                              className: `rounded-xl`,
                                              children: [
                                                  (0, b.jsxs)(`div`, {
                                                      className: `mb-4 flex items-start justify-between gap-3`,
                                                      children: [
                                                          (0, b.jsxs)(`div`, {
                                                              children: [
                                                                  (0, b.jsx)(
                                                                      `h2`,
                                                                      {
                                                                          className: `heading-5 text-secondary-900 dark:text-white`,
                                                                          children:
                                                                              e.label,
                                                                      },
                                                                  ),
                                                                  (0, b.jsx)(
                                                                      `p`,
                                                                      {
                                                                          className: `mt-1 text-body-sm text-secondary-500`,
                                                                          children:
                                                                              e.description,
                                                                      },
                                                                  ),
                                                              ],
                                                          }),
                                                          (0, b.jsx)(u, {
                                                              variant:
                                                                  e.key ===
                                                                  `admin`
                                                                      ? `danger`
                                                                      : e.key ===
                                                                          `responsable`
                                                                        ? `warning`
                                                                        : e.key ===
                                                                            `blocked`
                                                                          ? `neutral`
                                                                          : `primary`,
                                                              children: e.key,
                                                          }),
                                                      ],
                                                  }),
                                                  (0, b.jsxs)(`div`, {
                                                      className: `mb-4`,
                                                      children: [
                                                          (0, b.jsx)(`p`, {
                                                              className: `mb-2 text-label text-secondary-900 dark:text-white`,
                                                              children: `Modules par defaut`,
                                                          }),
                                                          (0, b.jsx)(`div`, {
                                                              className: `flex flex-wrap gap-2`,
                                                              children: n.length
                                                                  ? n.map((e) =>
                                                                        (0,
                                                                        b.jsx)(
                                                                            u,
                                                                            {
                                                                                variant: `primary`,
                                                                                children:
                                                                                    e,
                                                                            },
                                                                            e,
                                                                        ),
                                                                    )
                                                                  : (0, b.jsx)(
                                                                        u,
                                                                        {
                                                                            variant: `neutral`,
                                                                            children: `Aucun module`,
                                                                        },
                                                                    ),
                                                          }),
                                                      ],
                                                  }),
                                                  (0, b.jsxs)(`div`, {
                                                      children: [
                                                          (0, b.jsx)(`p`, {
                                                              className: `mb-2 text-label text-secondary-900 dark:text-white`,
                                                              children: `Permissions par defaut`,
                                                          }),
                                                          (0, b.jsx)(`div`, {
                                                              className: `space-y-2`,
                                                              children: e
                                                                  .permissions
                                                                  .length
                                                                  ? e.permissions.map(
                                                                        (e) =>
                                                                            (0,
                                                                            b.jsxs)(
                                                                                `div`,
                                                                                {
                                                                                    className: `rounded-lg border border-surface-200 px-3 py-2 dark:border-surface-700`,
                                                                                    children:
                                                                                        [
                                                                                            (0,
                                                                                            b.jsx)(
                                                                                                `p`,
                                                                                                {
                                                                                                    className: `text-body-sm font-medium text-secondary-900 dark:text-white`,
                                                                                                    children:
                                                                                                        t.permissions.find(
                                                                                                            (
                                                                                                                t,
                                                                                                            ) =>
                                                                                                                t.name ===
                                                                                                                e,
                                                                                                        )
                                                                                                            ?.label ??
                                                                                                        e,
                                                                                                },
                                                                                            ),
                                                                                            (0,
                                                                                            b.jsx)(
                                                                                                `p`,
                                                                                                {
                                                                                                    className: `text-ui-xs text-secondary-400`,
                                                                                                    children:
                                                                                                        e,
                                                                                                },
                                                                                            ),
                                                                                        ],
                                                                                },
                                                                                e,
                                                                            ),
                                                                    )
                                                                  : (0, b.jsx)(
                                                                        `p`,
                                                                        {
                                                                            className: `rounded-lg border border-dashed border-surface-200 p-3 text-body-sm text-secondary-400 dark:border-surface-700`,
                                                                            children: `Aucune permission.`,
                                                                        },
                                                                    ),
                                                          }),
                                                      ],
                                                  }),
                                              ],
                                          },
                                          e.key,
                                      );
                                  }),
                              }),
                      ],
                  }),
        ],
    });
}
export { fe as AdministrationPage };
