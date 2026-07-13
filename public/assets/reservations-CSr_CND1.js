import {
    C as e,
    E as t,
    I as n,
    K as r,
    S as i,
    T as a,
    W as o,
    f as s,
    l as c,
    m as l,
    p as u,
    u as d,
    w as f,
} from "./index-CqSzWeas.js?v=1783861909";
import { t as p } from "./dashboard-Chzs1W9w.js?v=1783861909";
import {
    a as m,
    i as h,
    n as ee,
    o as g,
    r as te,
    s as ne,
    t as re,
} from "./reservations-DvkqhioU.js?v=1783861909";
var _ = r(o(), 1),
    v = `/api/reservations.php`;
function y(e) {
    return {
        source: `fallback`,
        mode: `local-fallback`,
        user: g[0],
        users: g,
        sites: m,
        modules: ee,
        vehicles: ne,
        reservations: re,
        permissions: te,
        error: e,
    };
}
async function b(e) {
    if (!(e.headers.get(`content-type`) ?? ``).includes(`application/json`))
        throw Error(`API reservations indisponible`);
    let t = await e.json();
    if (!e.ok || t.ok === !1)
        throw Error(t.error || `Action reservations refusee`);
    return t;
}
function x(e) {
    return e ? { "X-CRM-User-Id": String(e) } : void 0;
}
async function ie(e) {
    try {
        let t = new URLSearchParams({ action: `bootstrap` });
        return (
            e && t.set(`user_id`, String(e)),
            {
                ...(await b(
                    await fetch(`${v}?${t.toString()}`, {
                        headers: x(e),
                        credentials: `same-origin`,
                    }),
                )),
                source: `api`,
            }
        );
    } catch (e) {
        return y(
            e instanceof Error ? e.message : `API reservations indisponible`,
        );
    }
}
async function ae(e) {
    return (
        await b(
            await fetch(`${v}?action=create_reservation`, {
                method: `POST`,
                headers: {
                    "Content-Type": `application/json`,
                    ...x(e.actorUserId),
                },
                credentials: `same-origin`,
                body: JSON.stringify(e),
            }),
        )
    ).reservation;
}
async function oe(e) {
    return (
        await b(
            await fetch(`${v}?action=update_reservation`, {
                method: `POST`,
                headers: {
                    "Content-Type": `application/json`,
                    ...x(e.actorUserId),
                },
                credentials: `same-origin`,
                body: JSON.stringify(e),
            }),
        )
    ).reservation;
}
async function se(e, t) {
    await b(
        await fetch(`${v}?action=delete_reservation`, {
            method: `POST`,
            headers: { "Content-Type": `application/json`, ...x(t) },
            credentials: `same-origin`,
            body: JSON.stringify({ id: e }),
        }),
    );
}
function ce(e, t, n = !0) {
    return !n || !t.includes(`reservations.view`)
        ? `blocked`
        : e === `admin` || t.includes(`platform.manage_users`)
          ? `admin`
          : e === `responsable` || t.includes(`reservations.manage_vehicles`)
            ? `manager`
            : `employee`;
}
var S = n(),
    le = new Intl.DateTimeFormat(`fr-FR`, {
        dateStyle: `medium`,
        timeStyle: `short`,
    }),
    C = new Intl.DateTimeFormat(`fr-FR`, {
        weekday: `short`,
        day: `2-digit`,
        month: `2-digit`,
    }),
    w = new Intl.DateTimeFormat(`fr-FR`, { month: `long`, year: `numeric` }),
    ue = {
        vehicleId: `1`,
        title: ``,
        contactPhone: ``,
        startAt: `2026-07-22T07:30`,
        endAt: `2026-07-22T12:00`,
        notes: ``,
    };
function T(e) {
    return new Date(e.replace(` `, `T`));
}
function E(e) {
    let t = String(e.getMonth() + 1).padStart(2, `0`),
        n = String(e.getDate()).padStart(2, `0`);
    return `${e.getFullYear()}-${t}-${n}`;
}
function D(e) {
    let t = String(e.getMonth() + 1).padStart(2, `0`),
        n = String(e.getDate()).padStart(2, `0`),
        r = String(e.getHours()).padStart(2, `0`),
        i = String(e.getMinutes()).padStart(2, `0`);
    return `${e.getFullYear()}-${t}-${n}T${r}:${i}`;
}
function siteHours(e) {
    let t = e?.hours || {};
    return {
        morningStart: String(
            t.morningStart || e?.morningStart || `07:30`,
        ).slice(0, 5),
        morningEnd: String(t.morningEnd || e?.morningEnd || `12:00`).slice(
            0,
            5,
        ),
        afternoonStart: String(
            t.afternoonStart || e?.afternoonStart || `13:30`,
        ).slice(0, 5),
        afternoonEnd: String(
            t.afternoonEnd || e?.afternoonEnd || `17:30`,
        ).slice(0, 5),
    };
}
function siteHoursLabel(e) {
    let t = siteHours(e);
    return `${t.morningStart}-${t.morningEnd} / ${t.afternoonStart}-${t.afternoonEnd}`;
}
function siteTime(e, t) {
    let n = new Date(e),
        r = String(t || `00:00`)
            .split(`:`)
            .map(Number);
    return (n.setHours(r[0] || 0, r[1] || 0, 0, 0), n);
}
function siteDaySlots(e, t) {
    let n = siteHours(t);
    return [
        {
            key: `morning`,
            label: `Matin`,
            start: siteTime(e, n.morningStart),
            end: siteTime(e, n.morningEnd),
        },
        {
            key: `afternoon`,
            label: `Apr\u00e8s-midi`,
            start: siteTime(e, n.afternoonStart),
            end: siteTime(e, n.afternoonEnd),
        },
        {
            key: `full_day`,
            label: `Journ\u00e9e`,
            start: siteTime(e, n.morningStart),
            end: siteTime(e, n.afternoonEnd),
        },
    ];
}
function timeMinutes(e) {
    let t = String(e || `00:00`)
        .slice(0, 5)
        .split(`:`)
        .map(Number);
    return (t[0] || 0) * 60 + (t[1] || 0);
}
function reservationPeriodTone(e, t) {
    let n = siteHours(t),
        r = timeMinutes(e.startAt.slice(11, 16)),
        i = timeMinutes(e.endAt.slice(11, 16)),
        a = timeMinutes(n.morningStart),
        o = timeMinutes(n.morningEnd),
        s = timeMinutes(n.afternoonStart),
        c = timeMinutes(n.afternoonEnd);
	    return r <= a + 15 && i >= c - 15
	      ? {
	              name: `day`,
	              bg: `linear-gradient(135deg,#5d7cff 0%,#3f5ee6 100%)`,
	              border: `#496bf1`,
	              accent: `#3856d9`,
	              text: `#ffffff`,
	              muted: `#eef3ff`,
	          }
	        : i <= o + 15
	          ? {
	                name: `morning`,
	                bg: `linear-gradient(135deg,#e9fbf8 0%,#d3f5ef 100%)`,
	                border: `#55cbc5`,
	                accent: `#10aaa4`,
	                text: `#075d62`,
	                muted: `#08797d`,
	            }
	          : r >= o - 15 || r >= s - 30
	            ? {
	                  name: `afternoon`,
	                  bg: `linear-gradient(135deg,#fff0ed 0%,#ffd6cf 100%)`,
	                  border: `#ff8b7d`,
	                  accent: `#ff5e52`,
	                  text: `#842921`,
	                  muted: `#a62d2b`,
	              }
            : {
                  name: `mixed`,
                  bg: `linear-gradient(135deg,#f8fafc 0%,#e9edf3 100%)`,
                  border: `#b9c3cf`,
                  accent: `#64748b`,
                  text: `#1e293b`,
                  muted: `#526174`,
              };
}
function O(e, t) {
    let n = new Date(e);
    return (n.setDate(e.getDate() + t), n);
}
function k(e) {
    let t = new Date(e);
    return (
        t.setHours(0, 0, 0, 0),
        t.setDate(t.getDate() - ((t.getDay() + 6) % 7)),
        t
    );
}
function A(e) {
    let t = k(new Date(e.getFullYear(), e.getMonth(), 1));
    return Array.from({ length: 42 }, (e, n) => O(t, n));
}
function j(e, t) {
    let n = E(t);
    return e
        .filter((e) => {
            let t = e.startAt.slice(0, 10),
                r = e.endAt.slice(0, 10);
            return t <= n && r >= n;
        })
        .sort((e, t) => e.startAt.localeCompare(t.startAt));
}
function de(e) {
    return le.format(T(e));
}
function M(e) {
    return e.slice(11, 16);
}
function N(e, t) {
    return e.permissions.includes(t);
}
function fe(e, t, n) {
    return (
        N(e, `reservations.update_any`) ||
        (N(e, `reservations.update_own`) && n.userId === t.id)
    );
}
function P(e, t, n, r) {
    return T(e) < T(r) && T(t) > T(n);
}
function F(e, t) {
    return e.find((e) => e.id === t);
}
function vehicleInitials(e) {
    return (
        String(e || `?`)
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((e) => e.charAt(0))
            .join(``)
            .toUpperCase() || `?`
    );
}
function vehicleImage(e) {
    return e.photoUrl
        ? (0, S.jsx)(`img`, {
              src: e.photoUrl,
              alt: e.name,
              className: `reservation-resource-image`,
              loading: `lazy`,
          })
        : (0, S.jsx)(`div`, {
              className: `reservation-resource-empty`,
              children: (0, S.jsx)(`span`, {
                  className: `text-lg font-bold`,
                  children: vehicleInitials(e.name),
              }),
          });
}
function vehicleAvailableNow(e, t) {
    let n = new Date(),
        r = new Date(n);
    r.setHours(r.getHours() + 4);
    return !t.some((t) => t.vehicleId === e.id && P(D(n), D(r), t.startAt, t.endAt));
}
function pe(e, t) {
    if (!e) return h[0];
    let n = t.find((e) => e.slug === `reservations`),
        r = !n || e.moduleIds.includes(n.id),
        i = ce(e.role, e.permissions, r),
        a = h.find((e) => e.key === i) ?? h[0];
    return {
        ...a,
        label: e.name,
        description: `${a.label} - ${a.description}`,
        permissions: e.permissions,
    };
}
function I({ label: e, value: t, hint: n, icon: r }) {
    return (0, S.jsx)(l, {
        padding: `sm`,
        className: `rounded-xl`,
        children: (0, S.jsxs)(`div`, {
            className: `flex items-center gap-3`,
            children: [
                (0, S.jsx)(`div`, {
                    className: `flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-theme-primary/10 text-theme-primary`,
                    children: (0, S.jsx)(c, { icon: r, className: `h-5 w-5` }),
                }),
                (0, S.jsxs)(`div`, {
                    className: `min-w-0`,
                    children: [
                        (0, S.jsx)(`p`, {
                            className: `text-caption text-secondary-500 dark:text-secondary-400`,
                            children: e,
                        }),
                        (0, S.jsx)(`p`, {
                            className: `heading-5 truncate text-secondary-900 dark:text-white`,
                            children: t,
                        }),
                        (0, S.jsx)(`p`, {
                            className: `text-ui-xs text-secondary-400 dark:text-secondary-500`,
                            children: n,
                        }),
                    ],
                }),
            ],
        }),
    });
}
function VehicleResourceSection({
    vehicles: e,
    reservations: t,
    selectedVehicle: n,
    onSelect: r,
    onClear: i,
}) {
    return (0, S.jsxs)(`section`, {
        className: `space-y-4`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`,
                children: [
                    (0, S.jsxs)(`div`, {
                        children: [
                            (0, S.jsx)(`h2`, {
                                className: `heading-5 text-secondary-900 dark:text-white`,
                                children: `Vehicules du site`,
                            }),
                            (0, S.jsx)(`p`, {
                                className: `text-body-sm text-secondary-500 dark:text-secondary-400`,
                                children: n
                                    ? `Planning filtre sur ${n.name}`
                                    : `Selectionnez un vehicule pour afficher son planning`,
                            }),
                        ],
                    }),
                    (0, S.jsxs)(`div`, {
                        className: `flex items-center gap-3`,
                        children: [
                            (0, S.jsx)(s, {
                                variant: `primary`,
                                children: e.length,
                            }),
                            n &&
                                (0, S.jsx)(`button`, {
                                    type: `button`,
                                    className: `text-body-sm font-semibold text-theme-primary hover:text-theme-primary/80`,
                                    onClick: i,
                                    children: `Changer`,
                                }),
                        ],
                    }),
                ],
            }),
            (0, S.jsx)(`div`, {
                className: `reservation-resource-grid`,
                children: e.length
                    ? e.map((e) => {
                          let i = n?.id === e.id,
                              a = vehicleAvailableNow(e, t);
                          return (0, S.jsxs)(
                              `button`,
                              {
                                  type: `button`,
                                  className: `reservation-resource-card${i ? ` is-active` : ``}`,
                                  "data-vehicle-filter": e.id,
                                  "aria-pressed": i ? `true` : `false`,
                                  onClick: () => r(e.id),
                                  children: [
                                      (0, S.jsxs)(`span`, {
                                          className: `reservation-resource-media`,
                                          children: [
                                              vehicleImage(e),
                                              (0, S.jsx)(`span`, {
                                                  className: `reservation-resource-status-dot`,
                                                  style: {
                                                      backgroundColor: a ? `#16a34a` : `#dc2626`,
                                                  },
                                                  title: a ? `Disponible` : `Indisponible`,
                                              }),
                                          ],
                                      }),
                                      (0, S.jsxs)(`span`, {
                                          className: `reservation-resource-body`,
                                          children: [
                                              (0, S.jsx)(`span`, {
                                                  className: `reservation-resource-title`,
                                                  children: e.name,
                                              }),
                                              (0, S.jsx)(`span`, {
                                                  className: `reservation-resource-meta`,
                                                  children: e.description || `Vehicule`,
                                              }),
                                              (0, S.jsx)(`span`, {
                                                  className: `reservation-resource-foot`,
                                                  children: (0, S.jsx)(`span`, {
                                                      className: `reservation-resource-state`,
                                                      style: {
                                                          "--resource-status": a ? `#16a34a` : `#dc2626`,
                                                      },
                                                      children: a ? `Disponible` : `Indisponible`,
                                                  }),
                                              }),
                                          ],
                                      }),
                                  ],
                              },
                              e.id,
                          );
                      })
                    : (0, S.jsx)(`p`, {
                          className: `rounded-xl border border-dashed border-surface-200 p-4 text-center text-body-sm text-secondary-400 dark:border-surface-700`,
                          children: `Aucun vehicule sur ce site.`,
                      }),
            }),
        ],
    });
}
function L({ vehicle: e }) {
    return e
        ? (0, S.jsxs)(`span`, {
              className: `inline-flex items-center gap-1.5 rounded-full bg-surface-100 px-2 py-1 text-ui-xs font-semibold text-secondary-700 dark:bg-surface-800 dark:text-secondary-200`,
              children: [
                  (0, S.jsx)(`span`, {
                      className: `h-2 w-2 rounded-full`,
                      style: { backgroundColor: e.color },
                  }),
                  e.name,
              ],
          })
        : null;
}
function R({
    reservation: e,
    vehicle: n,
    site: r,
    compact: i = !1,
    onEdit: a,
    timelineStyle: timelineStyle,
    timeline: timeline = !1,
}) {
    let o = reservationPeriodTone(e, r),
        s = i
            ? n?.name || e.title || `Vehicule`
            : e.title || n?.name || `Reservation vehicule`,
        l = t(
            `reservation-event-card relative overflow-hidden rounded-lg border p-2 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md`,
            timeline ? `reservation-timeline-card` : `w-full`,
            i ? `space-y-1` : `space-y-1.5`,
            a &&
                `cursor-pointer focus:outline-none focus:ring-2 focus:ring-theme-primary/40`,
        ),
        u = {
            ...(timelineStyle || {}),
            borderColor: o.border,
            background: o.bg,
            color: o.text,
        },
        f = (0, S.jsxs)(S.Fragment, {
            children: [
                (0, S.jsx)(`span`, {
                    className: `absolute left-2 top-2 h-2.5 w-2.5 rounded-full`,
                    style: {
                        backgroundColor: o.accent,
                        boxShadow: `0 0 0 3px rgba(255,255,255,.45)`,
                    },
                    "aria-hidden": !0,
                }),
                (0, S.jsxs)(`div`, {
                    className: `flex items-start justify-between gap-2`,
                    children: [
                        (0, S.jsxs)(`span`, {
                            className: `pl-4 text-ui-xs font-bold`,
                            style: { color: o.muted },
                            children: [M(e.startAt), `-`, M(e.endAt)],
                        }),
                        a &&
                            !i &&
                            (0, S.jsx)(c, {
                                icon: d.edit,
                                className: `h-4 w-4 shrink-0`,
                                style: { color: o.accent },
                            }),
                    ],
                }),
                (0, S.jsx)(`p`, {
                    className: t(
                        `font-bold leading-snug`,
                        i ? `text-ui-xs` : `text-label`,
                    ),
                    style: { color: o.text },
                    children: s,
                }),
                !i &&
                    (0, S.jsxs)(S.Fragment, {
                        children: [
                            (0, S.jsx)(`p`, {
                                className: `text-caption font-semibold`,
                                style: { color: o.muted },
                                children: n?.name || `Vehicule`,
                            }),
                            (0, S.jsxs)(`p`, {
                                className: `text-caption`,
                                style: { color: o.muted },
                                children: [
                                    e.userName,
                                    e.contactPhone
                                        ? ` · ${e.contactPhone}`
                                        : ``,
                                ],
                            }),
                        ],
                    }),
            ],
        });
    return a
        ? (0, S.jsx)(`button`, {
              type: `button`,
              className: l,
              style: u,
              "data-period": o.name,
              "aria-label": `Modifier la reservation #${e.id}`,
              onClick: a,
              children: f,
          })
        : (0, S.jsx)(`div`, {
              className: l,
              style: u,
              "data-period": o.name,
              children: f,
          });
}
function ReservationPeriodLegend() {
    return (0, S.jsxs)(`div`, {
        className: `flex flex-wrap items-center justify-center gap-4 border-t border-surface-200 bg-white px-4 py-3 text-ui-xs font-semibold text-secondary-600 dark:border-surface-700 dark:bg-surface-900 dark:text-secondary-300`,
        children: [
            (0, S.jsxs)(`span`, {
                className: `inline-flex items-center gap-2`,
                children: [
                    (0, S.jsx)(`i`, {
                        className: `h-4 w-4 rounded-md shadow-sm`,
                        style: {
                            background: `linear-gradient(135deg,#55cbc5 0%,#10aaa4 100%)`,
                        },
                    }),
                    `Matin`,
                ],
            }),
            (0, S.jsxs)(`span`, {
                className: `inline-flex items-center gap-2`,
                children: [
                    (0, S.jsx)(`i`, {
                        className: `h-4 w-4 rounded-md shadow-sm`,
                        style: {
	            background: `linear-gradient(135deg,#ff8b7d 0%,#ff5e52 100%)`,
                        },
                    }),
                    `Apr\u00e8s-midi`,
                ],
            }),
            (0, S.jsxs)(`span`, {
                className: `inline-flex items-center gap-2`,
                children: [
                    (0, S.jsx)(`i`, {
                        className: `h-4 w-4 rounded-md shadow-sm`,
                        style: {
	            background: `linear-gradient(135deg,#5d7cff 0%,#3f5ee6 100%)`,
                        },
                    }),
                    `Journ\u00e9e compl\u00e8te`,
                ],
            }),
        ],
    });
}
function ReservationTimelineStyles() {
    return (0, S.jsx)(`style`, {
        children: `
.reservation-resource-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.reservation-resource-card{position:relative;display:flex;min-width:0;flex-direction:column;overflow:hidden;border:1px solid #e4e9f1;border-radius:1rem;background:#fff;text-align:left;box-shadow:0 12px 28px rgba(15,23,42,.055);transition:border-color var(--duration-fast),box-shadow var(--duration-fast),transform var(--duration-fast)}
.reservation-resource-card:hover{border-color:rgb(var(--theme-primary)/.38);box-shadow:0 18px 38px rgba(15,23,42,.09);transform:translateY(-1px)}
.reservation-resource-card.is-active{border-color:rgb(var(--theme-primary));box-shadow:0 0 0 2px rgb(var(--theme-primary)/.16),0 18px 38px rgba(15,23,42,.1)}
.reservation-resource-media{position:relative;display:block;aspect-ratio:4/3;background:#f7f9fc}
.reservation-resource-image{height:100%;width:100%;object-fit:cover}
.reservation-resource-empty{display:flex;height:100%;width:100%;align-items:center;justify-content:center;background:linear-gradient(135deg,rgb(var(--theme-primary)/.12),rgb(var(--theme-primary)/.04));color:rgb(var(--theme-primary))}
.reservation-resource-status-dot{position:absolute;right:8px;top:8px;z-index:2;height:.75rem;width:.75rem;border-radius:999px;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.08)}
.reservation-resource-body{display:flex;min-width:0;flex:1;flex-direction:column;gap:.35rem;padding:.75rem}
.reservation-resource-title{display:-webkit-box;min-height:2.35em;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;color:#223957;font-size:.88rem;font-weight:800;line-height:1.18}
.reservation-resource-meta{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:.72rem;font-weight:700}
.reservation-resource-foot{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:.5rem}
.reservation-resource-state{display:inline-flex;align-items:center;gap:.35rem;color:#526174;font-size:.72rem;font-weight:800}
.reservation-resource-state:before{content:"";height:.45rem;width:.45rem;border-radius:999px;background:var(--resource-status,#16a34a)}
@media (min-width:640px){.reservation-resource-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}}
@media (min-width:1180px){.reservation-resource-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
.reservation-agenda-toolbar{background:linear-gradient(180deg,#fff 0%,#fbfcff 100%)}
.reservation-agenda-nav,.reservation-agenda-actions{min-width:0}
.reservation-view-switch{display:inline-flex;align-items:center;gap:.25rem;border:1px solid #e4e9f1;border-radius:.8rem;background:#f7f9fc;padding:.22rem}
.reservation-view-switch .btn{border-color:transparent;box-shadow:none}
.reservation-view-switch .btn-primary{box-shadow:0 8px 16px rgb(var(--theme-primary)/.18)}
.reservation-time-planner{overflow-x:auto;background:#fff;overscroll-behavior-x:contain;scroll-snap-type:x proximity}
.reservation-time-planner::-webkit-scrollbar{height:0}
.reservation-time-grid{--planner-height:640px;display:grid;min-width:100%;grid-template-columns:58px repeat(var(--planner-columns,7),minmax(132px,1fr));grid-template-rows:54px var(--planner-height);background:#fff}
.reservation-time-grid-week{min-width:calc(58px + (7 * 132px))}
.reservation-time-spacer,.reservation-time-header,.reservation-time-axis,.reservation-time-column{border-top:1px solid #e4e9f1}
.reservation-time-spacer{border-right:1px solid #e4e9f1}
.reservation-time-header{display:flex;min-width:0;align-items:center;justify-content:center;border-left:1px solid #e4e9f1;background:#fbfcff;padding:.5rem .25rem;text-align:center;scroll-snap-align:start}
.reservation-time-header button{display:inline-flex;flex-direction:column;align-items:center;gap:.1rem;color:#243957;font-size:.75rem;font-weight:800;line-height:1.1}
.reservation-time-header small{color:#526782;font-size:.62rem;font-weight:700;text-transform:uppercase}
.reservation-time-axis{position:relative;border-right:1px solid #e4e9f1;color:#526782;font-size:.72rem;font-weight:700}
.reservation-time-axis span{position:absolute;right:.45rem;transform:translateY(-50%)}
.reservation-time-column{position:relative;min-width:0;border-left:1px solid #e4e9f1;background:#fff;scroll-snap-align:start}
.reservation-time-line{position:absolute;left:0;right:0;height:1px;background:#e4e9f1;opacity:.72}
.reservation-event-card{border-radius:.7rem!important;box-shadow:0 8px 18px rgba(15,23,42,.035)!important}
.reservation-timeline-card{position:absolute!important;left:.45rem;right:.45rem;z-index:2;display:flex;min-height:54px;flex-direction:column;justify-content:flex-start;padding:.65rem .72rem}
.reservation-timeline-card p{line-height:1.25}
.reservation-timeline-card p:nth-of-type(n+2){display:none}
.reservation-available-slot{position:absolute;left:.45rem;right:.45rem;z-index:1;display:flex;min-height:46px;align-items:center;justify-content:center;border:1px dashed var(--color-surface-300);border-radius:var(--radius);background:rgba(255,255,255,.72);color:var(--color-secondary-500);font-size:.75rem;font-weight:700;transition:border-color var(--duration-fast),color var(--duration-fast),background var(--duration-fast)}
.reservation-available-slot:hover{border-color:rgb(var(--theme-primary));background:rgb(var(--theme-primary)/.05);color:rgb(var(--theme-primary))}
.reservation-day-planner{padding:1rem;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%)}
.reservation-day-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.9rem;border:1px solid #e4e9f1;border-radius:1rem;background:#fff;padding:.9rem 1rem;box-shadow:0 14px 32px rgba(15,23,42,.055)}
.reservation-day-kicker{color:rgb(var(--theme-primary));font-size:.72rem;font-weight:800;text-transform:uppercase}
.reservation-day-date{color:#223957;font-size:1.05rem;font-weight:800}
.reservation-day-meta{color:#64748b;font-size:.78rem;font-weight:700}
.reservation-day-pills{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.5rem}
.reservation-day-pill{display:inline-flex;align-items:center;gap:.45rem;border:1px solid var(--pill-border,#d9e7f7);border-radius:999px;background:var(--pill-bg,#f8fbff);color:#31435a;padding:.45rem .7rem;font-size:.74rem;font-weight:800;white-space:nowrap}
.reservation-day-pill i{height:.55rem;width:.55rem;border-radius:999px;background:var(--pill-color,#10aaa4);box-shadow:0 0 0 3px var(--pill-ring,rgba(16,170,164,.16))}
.reservation-day-pill-morning{--pill-color:#10aaa4;--pill-ring:rgba(16,170,164,.16);--pill-bg:#effbf9;--pill-border:#c6efea}
.reservation-day-pill-afternoon{--pill-color:#ff5e52;--pill-ring:rgba(255,94,82,.16);--pill-bg:#fff4f2;--pill-border:#ffd4ce}
.reservation-day-planner .reservation-time-grid{--planner-height:560px;grid-template-columns:72px minmax(0,1fr);border:1px solid #e4e9f1;border-radius:1rem;overflow:hidden;box-shadow:0 18px 44px rgba(15,23,42,.06)}
.reservation-day-planner .reservation-time-spacer{background:#fbfcff}
.reservation-day-planner .reservation-time-header{border-left:0;background:linear-gradient(180deg,#fff 0%,#f5f8fd 100%)}
.reservation-day-planner .reservation-time-header button{color:#223957;font-size:.82rem}
.reservation-day-planner .reservation-time-column{border-left:0;background:linear-gradient(180deg,rgba(85,203,197,.1) 0%,rgba(85,203,197,.035) 49%,rgba(255,139,125,.095) 49%,rgba(255,139,125,.035) 100%),#fff}
.reservation-day-planner .reservation-time-line{background:rgba(148,163,184,.35)}
.reservation-day-planner .reservation-timeline-card{left:1rem;right:1rem;min-height:64px;border-radius:1rem!important;padding:.78rem 1rem .78rem 1.15rem;box-shadow:0 18px 36px rgba(15,23,42,.1)!important}
.reservation-day-planner .reservation-available-slot{left:1rem;right:1rem;min-height:58px;border-color:rgb(var(--theme-primary)/.38);border-radius:1rem;background:rgba(255,255,255,.86);color:rgb(var(--theme-primary));box-shadow:inset 0 0 0 1px rgb(var(--theme-primary)/.08)}
.reservation-day-planner .reservation-available-slot:hover{background:rgb(var(--theme-primary)/.08);box-shadow:0 12px 24px rgb(var(--theme-primary)/.12)}
.reservation-day-slots{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.reservation-day-slot-card{position:relative;display:flex;min-height:15.5rem;flex-direction:column;overflow:hidden;border:1px solid var(--slot-border,#e4e9f1);border-radius:1rem;background:var(--slot-bg,#fff);box-shadow:0 18px 42px rgba(15,23,42,.06)}
.reservation-day-slot-card:before{content:"";position:absolute;inset:0 0 auto;height:.32rem;background:var(--slot-accent,rgb(var(--theme-primary)))}
.reservation-day-slot-morning{--slot-accent:#10aaa4;--slot-border:#bfeee8;--slot-bg:linear-gradient(180deg,#f0fbf9 0%,#fff 62%)}
.reservation-day-slot-afternoon{--slot-accent:#ff5e52;--slot-border:#ffd0c9;--slot-bg:linear-gradient(180deg,#fff3f1 0%,#fff 62%)}
.reservation-day-slot-full_day{--slot-accent:#496bf1;--slot-border:#cad5ff;--slot-bg:linear-gradient(180deg,#f1f4ff 0%,#fff 62%)}
.reservation-day-slot-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem;padding:1rem 1rem .75rem}
.reservation-day-slot-title{color:#223957;font-size:1rem;font-weight:900;line-height:1.1}
.reservation-day-slot-hours{margin-top:.25rem;color:#526782;font-size:.78rem;font-weight:800}
.reservation-day-slot-status{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--slot-status-border,#dbe4ef);border-radius:999px;background:#fff;padding:.35rem .55rem;color:#31435a;font-size:.68rem;font-weight:900;white-space:nowrap}
.reservation-day-slot-status:before{content:"";height:.48rem;width:.48rem;border-radius:999px;background:var(--slot-status,#16a34a)}
.reservation-day-slot-card.is-booked{--slot-status:#dc2626;--slot-status-border:#fecaca}
.reservation-day-slot-body{display:flex;min-height:0;flex:1;flex-direction:column;gap:.65rem;padding:0 1rem 1rem}
.reservation-day-slot-empty{display:flex;min-height:5.5rem;flex:1;align-items:center;justify-content:center;border:1px dashed var(--slot-border,#dbe4ef);border-radius:.85rem;background:rgba(255,255,255,.66);color:#64748b;text-align:center;font-size:.82rem;font-weight:800;line-height:1.35}
.reservation-day-slot-bookings{display:grid;gap:.55rem}
.reservation-day-slot-bookings .reservation-event-card{box-shadow:none!important}
.reservation-day-slot-button{margin-top:auto;display:flex;min-height:2.85rem;width:100%;align-items:center;justify-content:center;border:0;border-radius:.85rem;background:rgb(var(--theme-primary));color:#fff;font-size:.9rem;font-weight:900;box-shadow:0 14px 26px rgb(var(--theme-primary)/.18);transition:background .15s,transform .15s,box-shadow .15s}
.reservation-day-slot-button:hover{background:rgb(var(--theme-primary)/.92);box-shadow:0 16px 30px rgb(var(--theme-primary)/.22);transform:translateY(-1px)}
.reservation-day-slot-note{margin-top:auto;border-radius:.75rem;background:#f8fafc;padding:.75rem;color:#64748b;text-align:center;font-size:.78rem;font-weight:800}
.reservation-month-grid{background:#e4e9f1!important;border:1px solid #e4e9f1;overflow:hidden}
.reservation-month-heading{background:#fff!important;color:#243957!important;letter-spacing:0}
.reservation-month-cell{position:relative;display:flex;flex-direction:column;overflow:hidden;background:#fff!important}
.reservation-month-cell-muted{background:#fafbfd!important;color:#aab4c2!important}
.reservation-month-events{position:relative;z-index:3}
.reservation-month-events .reservation-event-card{min-height:auto;border-radius:.7rem!important;box-shadow:none!important;padding:.52rem .58rem .52rem .62rem!important}
.reservation-month-events .reservation-event-card span{font-size:.62rem!important}
.reservation-month-events .reservation-event-card p{font-size:.75rem!important;line-height:1.15}
.reservation-month-dots{display:none}
@media (max-width:840px){
  .reservation-agenda-toolbar{padding:.85rem}
  .reservation-agenda-nav{width:100%;justify-content:space-between}
  .reservation-agenda-nav>div{min-width:0;text-align:center}
  .reservation-agenda-actions{display:grid;width:100%;grid-template-columns:repeat(3,minmax(0,1fr));gap:.35rem}
  .reservation-view-switch{display:contents}
  .reservation-agenda-actions .btn{min-width:0;justify-content:center;padding-inline:.35rem;font-size:.72rem}
  .reservation-time-grid{--planner-height:720px;grid-template-columns:48px repeat(var(--planner-columns,7),minmax(118px,1fr));grid-template-rows:48px var(--planner-height)}
  .reservation-time-grid-week{min-width:calc(48px + (7 * 118px))}
  .reservation-time-grid:not(.reservation-time-grid-week){min-width:100%;grid-template-columns:48px minmax(270px,1fr)}
  .reservation-time-spacer,.reservation-time-axis{position:sticky;left:0;z-index:5;background:#fff}
  .reservation-time-header{position:sticky;top:0;z-index:4;background:#fbfcff}
  .reservation-time-header{padding:.35rem .1rem}
  .reservation-time-header button{font-size:.66rem}
  .reservation-time-header small{font-size:.56rem}
  .reservation-time-axis{font-size:.66rem}
  .reservation-time-axis span{right:.25rem}
  .reservation-timeline-card{left:.36rem;right:.36rem;min-height:44px;border-radius:.72rem;padding:.52rem .5rem .52rem .58rem}
  .reservation-timeline-card span{padding-left:.72rem!important;font-size:.58rem}
  .reservation-timeline-card p{font-size:.72rem!important;line-height:1.16}
  .reservation-timeline-card svg{display:none}
  .reservation-available-slot{left:.36rem;right:.36rem;font-size:.62rem;text-align:center}
  .reservation-day-planner{padding:.7rem}
  .reservation-day-hero{align-items:flex-start;flex-direction:column;padding:.75rem}
  .reservation-day-pills{display:grid;width:100%;grid-template-columns:repeat(2,minmax(0,1fr));justify-content:stretch}
  .reservation-day-pill{justify-content:center;padding-inline:.45rem;font-size:.66rem}
  .reservation-day-slots{grid-template-columns:1fr;gap:.75rem}
  .reservation-day-slot-card{min-height:0}
  .reservation-day-slot-head{padding:.85rem .85rem .55rem}
  .reservation-day-slot-body{padding:0 .85rem .85rem}
  .reservation-day-planner .reservation-time-grid{--planner-height:680px;grid-template-columns:48px minmax(240px,1fr)}
  .reservation-day-planner .reservation-timeline-card,.reservation-day-planner .reservation-available-slot{left:.55rem;right:.55rem}
  .reservation-month-grid{overflow:hidden}
  .reservation-month-cell{min-height:3.85rem!important;align-items:center;justify-content:flex-start;gap:.2rem;overflow:hidden;padding:.38rem .18rem!important;text-align:center}
  .reservation-month-cell>span:first-child{margin-bottom:0!important;color:#223957}
  .reservation-month-cell-muted>span:first-child{color:#a6b0bf!important}
  .reservation-month-events{display:none}
  .reservation-month-dots{display:inline-flex;max-width:100%;align-items:center;justify-content:center;gap:.16rem;line-height:1}
  .reservation-month-dots i{display:block;width:.38rem;height:.38rem;border-radius:999px;box-shadow:0 0 0 1px rgba(255,255,255,.9)}
  .reservation-month-dots b{color:rgb(var(--theme-primary));font-size:.58rem;font-weight:800}
}
`,
    });
}
function timelineMinuteLabel(e) {
    let t = String(Math.floor(e / 60)).padStart(2, `0`),
        n = String(e % 60).padStart(2, `0`);
    return `${t}:${n}`;
}
function reservationPlannerBounds(e) {
    let t = siteHours(e),
        n = Math.floor(
            Math.min(timeMinutes(t.morningStart), timeMinutes(t.afternoonStart)) /
                60,
        ) * 60,
        r = Math.ceil(
            Math.max(timeMinutes(t.morningEnd), timeMinutes(t.afternoonEnd)) /
                60,
        ) * 60;
    r <= n && (r = n + 600);
    let i = [];
    for (let e = n; e <= r; e += 60) i.push(e);
    return i[i.length - 1] !== r && i.push(r), { start: n, end: r, total: r - n, marks: i };
}
function reservationTimelinePosition(e, t, n) {
    let r = E(n),
        i = e.startAt.slice(0, 10) < r ? t.start : timeMinutes(e.startAt.slice(11, 16)),
        a = e.endAt.slice(0, 10) > r ? t.end : timeMinutes(e.endAt.slice(11, 16));
    i = Math.max(t.start, Math.min(t.end, i));
    a = Math.max(t.start, Math.min(t.end, a));
    a <= i && (a = Math.min(t.end, i + 30));
    let o = ((i - t.start) / t.total) * 100,
        s = Math.max(((a - i) / t.total) * 100, 7);
    return o + s > 100 && (o = Math.max(0, 100 - s)), { top: `${o}%`, height: `${s}%` };
}
function ReservationTimeLines({ bounds: e }) {
    return (0, S.jsx)(S.Fragment, {
        children: e.marks.map((t) =>
            (0, S.jsx)(
                `span`,
                {
                    className: `reservation-time-line`,
                    style: { top: `${((t - e.start) / e.total) * 100}%` },
                },
                t,
            ),
        ),
    });
}
function ReservationTimeAxis({ bounds: e }) {
    return (0, S.jsx)(`div`, {
        className: `reservation-time-axis`,
        children: e.marks.map((t) =>
            (0, S.jsx)(
                `span`,
                {
                    style: { top: `${((t - e.start) / e.total) * 100}%` },
                    children: timelineMinuteLabel(t),
                },
                t,
            ),
        ),
    });
}
function ReservationTimeHeader({ day: e, onSelectDay: t, site: n, showHours: r = !1 }) {
    let i = siteHours(n);
    return (0, S.jsx)(`div`, {
        className: `reservation-time-header`,
        children: (0, S.jsxs)(`button`, {
            type: `button`,
            onClick: () => t(e),
            children: [
                (0, S.jsx)(`span`, { children: C.format(e) }),
                r &&
                    (0, S.jsxs)(`small`, {
                        children: [i.morningStart, `-`, i.afternoonEnd],
                    }),
            ],
        }),
    });
}
function ReservationTimelineCard({
    reservation: e,
    vehicle: t,
    site: n,
    day: r,
    canEditReservation: i,
    onEditReservation: a,
}) {
    let o = reservationTimelinePosition(e, reservationPlannerBounds(n), r);
    return (0, S.jsx)(R, {
        reservation: e,
        vehicle: t,
        site: n,
        timeline: !0,
        timelineStyle: o,
        onEdit: i(e) ? () => a(e) : void 0,
    });
}
function ReservationAvailableSlot({ slot: e, reservations: t, vehicles: n, site: r, onSelectSlot: i }) {
    let a = D(e.start),
        o = D(e.end),
        s = t.some((e) => P(a, o, e.startAt, e.endAt)),
        c = n.some((e) => !t.some((t) => t.vehicleId === e.id && P(a, o, t.startAt, t.endAt)));
    return s || !c
        ? null
        : (0, S.jsx)(`button`, {
              type: `button`,
              className: `reservation-available-slot`,
              style: reservationTimelinePosition(
                  { startAt: a, endAt: o },
                  reservationPlannerBounds(r),
                  e.start,
              ),
              onClick: () => i(a, o),
              children: `Creneau disponible`,
          });
}
function reservationSlotTitle(e) {
    return e === `morning`
        ? `Matin`
        : e === `afternoon`
          ? `Apr\u00e8s-midi`
          : `Journ\u00e9e compl\u00e8te`;
}
function ReservationDaySlot({
    slot: e,
    reservations: t,
    vehicle: n,
    site: r,
    canEditReservation: i,
    onEditReservation: a,
    onSelectSlot: o,
}) {
    let s = D(e.start),
        c = D(e.end),
        l = t.filter((e) => P(s, c, e.startAt, e.endAt)),
        u = l.length === 0,
        d = reservationSlotTitle(e.key);
    return (0, S.jsxs)(`article`, {
        className: `reservation-day-slot-card reservation-day-slot-${e.key} ${u ? `is-available` : `is-booked`}`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `reservation-day-slot-head`,
                children: [
                    (0, S.jsxs)(`div`, {
                        children: [
                            (0, S.jsx)(`h3`, {
                                className: `reservation-day-slot-title`,
                                children: d,
                            }),
                            (0, S.jsxs)(`p`, {
                                className: `reservation-day-slot-hours`,
                                children: [M(s), ` - `, M(c)],
                            }),
                        ],
                    }),
                    (0, S.jsx)(`span`, {
                        className: `reservation-day-slot-status`,
                        children: u ? `Libre` : `Occup\u00e9`,
                    }),
                ],
            }),
            (0, S.jsxs)(`div`, {
                className: `reservation-day-slot-body`,
                children: [
                    l.length
                        ? (0, S.jsx)(`div`, {
                              className: `reservation-day-slot-bookings`,
                              children: l.map((e) =>
                                  (0, S.jsx)(
                                      R,
                                      {
                                          reservation: e,
                                          vehicle: n,
                                          site: r,
                                          onEdit: i(e) ? () => a(e) : void 0,
                                      },
                                      e.id,
                                  ),
                              ),
                          })
                        : (0, S.jsx)(`div`, {
                              className: `reservation-day-slot-empty`,
                              children: `Disponible pour ${n?.name || `ce v\u00e9hicule`}`,
                          }),
                    u
                        ? (0, S.jsx)(`button`, {
                              type: `button`,
                              className: `reservation-day-slot-button`,
                              onClick: () => o(s, c),
                              children: `R\u00e9server ${d.toLowerCase()}`,
                          })
                        : (0, S.jsx)(`p`, {
                              className: `reservation-day-slot-note`,
                              children: `Ce cr\u00e9neau chevauche d\u00e9j\u00e0 une r\u00e9servation.`,
                          }),
                ],
            }),
        ],
    });
}
function z({
    view: e,
    focusDate: t,
    onViewChange: n,
    onPrevious: r,
    onNext: i,
    onToday: o,
}) {
    return (0, S.jsxs)(`div`, {
        className: `reservation-agenda-toolbar flex flex-col gap-3 border-b border-surface-200 p-4 dark:border-surface-700 sm:flex-row sm:items-center sm:justify-between`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `reservation-agenda-nav flex items-center gap-2`,
                children: [
                    (0, S.jsx)(a, {
                        variant: `secondary`,
                        size: `sm`,
                        iconOnly: !0,
                        "aria-label": `Periode precedente`,
                        onClick: r,
                        children: (0, S.jsx)(c, {
                            icon: d.chevronLeft,
                            className: `h-4 w-4`,
                        }),
                    }),
                    (0, S.jsxs)(`div`, {
                        children: [
                            (0, S.jsx)(`p`, {
                                className: `heading-5 text-secondary-900 dark:text-white`,
                                children:
                                    e === `month` ? w.format(t) : C.format(t),
                            }),
                            (0, S.jsx)(`p`, {
                                className: `text-caption text-secondary-500`,
                                children: `Planning vehicules`,
                            }),
                        ],
                    }),
                    (0, S.jsx)(a, {
                        variant: `secondary`,
                        size: `sm`,
                        iconOnly: !0,
                        "aria-label": `Periode suivante`,
                        onClick: i,
                        children: (0, S.jsx)(c, {
                            icon: d.chevronRight,
                            className: `h-4 w-4`,
                        }),
                    }),
                ],
            }),
            (0, S.jsxs)(`div`, {
                className: `reservation-agenda-actions flex flex-wrap items-center gap-2`,
                children: [
                    (0, S.jsx)(`span`, {
                        className: `reservation-view-switch`,
                        children: [`month`, `day`].map((t) =>
                            (0, S.jsx)(
                                a,
                                {
                                    variant: e === t ? `primary` : `secondary`,
                                    size: `sm`,
                                    onClick: () => n(t),
                                    children:
                                        t === `month`
                                            ? `Mois`
                                            : `Jour`,
                                },
                                t,
                            ),
                        ),
                    }),
                    (0, S.jsx)(a, {
                        variant: `ghost`,
                        size: `sm`,
                        onClick: o,
                        children: `Aujourd'hui`,
                    }),
                ],
            }),
        ],
    });
}
function B({ focusDate: e, reservations: n, vehicles: r, site: i, onSelectDay: o }) {
    let s = A(e),
        l = e.getMonth();
    return (0, S.jsxs)(`div`, {
        className: `reservation-month-grid grid grid-cols-7 gap-px rounded-b-xl bg-surface-200 dark:bg-surface-700`,
        children: [
            [`Lun`, `Mar`, `Mer`, `Jeu`, `Ven`, `Sam`, `Dim`].map((e) =>
                (0, S.jsx)(
                    `div`,
                    {
                        className: `reservation-month-heading bg-surface-50 px-2 py-2 text-center text-ui-xs font-bold uppercase text-secondary-500 dark:bg-surface-800`,
                        children: e,
                    },
                    e,
                ),
            ),
            s.map((e) => {
                let a = j(n, e);
                return (0, S.jsxs)(
                    `button`,
                    {
                        type: `button`,
                        className: t(
                            `reservation-month-cell min-h-[118px] bg-white p-2 text-left transition-colors hover:bg-theme-primary/5 dark:bg-surface-900`,
                            e.getMonth() !== l &&
                                `reservation-month-cell-muted bg-surface-50 text-secondary-400 dark:bg-surface-900/60`,
                        ),
                        onClick: () => o(e),
                        children: [
	                            (0, S.jsx)(`span`, {
	                                className: `mb-2 block text-ui-xs font-bold text-secondary-600 dark:text-secondary-300`,
	                                children: e.getDate(),
	                            }),
	                            a.length > 0 &&
	                                (0, S.jsxs)(`span`, {
	                                    className: `reservation-month-dots`,
	                                    children: [
	                                        a.slice(0, 3).map((e) =>
	                                            (0, S.jsx)(
	                                                `i`,
	                                                {
	                                                    style: {
	                                                        backgroundColor:
	                                                            reservationPeriodTone(e, i).accent,
	                                                    },
	                                                },
	                                                e.id,
	                                            ),
	                                        ),
	                                        a.length > 3 &&
	                                            (0, S.jsxs)(`b`, {
	                                                children: [`+`, a.length - 3],
	                                            }),
	                                    ],
	                                }),
	                            (0, S.jsxs)(`div`, {
	                                className: `reservation-month-events space-y-1.5`,
                                children: [
                                    a.slice(0, 2).map((e) =>
                                        (0, S.jsx)(
                                            R,
                                            {
                                                reservation: e,
                                                vehicle: F(r, e.vehicleId),
                                                site: i,
                                                compact: !0,
                                            },
                                            e.id,
                                        ),
                                    ),
                                    a.length > 2 &&
                                        (0, S.jsxs)(`span`, {
                                            className: `text-ui-xs font-semibold text-theme-primary`,
                                            children: [
                                                `+`,
                                                a.length - 2,
                                                ` autres`,
                                            ],
                                        }),
                                ],
                            }),
                        ],
                    },
                    E(e),
                );
            }),
        ],
    });
}
function me({
    focusDate: e,
    reservations: t,
    vehicles: n,
    site: r,
    onSelectDay: i,
    canEditReservation: a,
    onEditReservation: o,
}) {
    let s = k(e),
        c = Array.from({ length: 7 }, (e, t) => O(s, t)),
        l = reservationPlannerBounds(r);
    return (0, S.jsx)(`div`, {
        className: `reservation-time-planner rounded-b-xl`,
        children: (0, S.jsxs)(`div`, {
            className: `reservation-time-grid reservation-time-grid-week`,
            style: { "--planner-columns": 7 },
            children: [
                (0, S.jsx)(`div`, { className: `reservation-time-spacer` }),
                c.map((e) =>
                    (0, S.jsx)(
                        ReservationTimeHeader,
                        { day: e, site: r, onSelectDay: i },
                        E(e),
                    ),
                ),
                (0, S.jsx)(ReservationTimeAxis, { bounds: l }),
                c.map((e) => {
                    let s = j(t, e);
                    return (0, S.jsxs)(
                        `div`,
                        {
                            className: `reservation-time-column`,
                            children: [
                                (0, S.jsx)(ReservationTimeLines, { bounds: l }),
                                s.map((t) =>
                                    (0, S.jsx)(
                                        ReservationTimelineCard,
                                        {
                                            reservation: t,
                                            vehicle: F(n, t.vehicleId),
                                            site: r,
                                            day: e,
                                            canEditReservation: a,
                                            onEditReservation: o,
                                        },
                                        t.id,
                                    ),
                                ),
                            ],
                        },
                        E(e),
                    );
                }),
            ],
        }),
    });
}
function V({
    focusDate: e,
    reservations: t,
    vehicles: n,
    site: r,
    onSelectSlot: i,
    canEditReservation: a,
    onEditReservation: o,
}) {
    let s = j(t, e),
        l = siteDaySlots(e, r),
        u = siteHours(r),
        f = n[0] || null;
    return (0, S.jsxs)(`div`, {
        className: `reservation-time-planner reservation-day-planner rounded-b-xl`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `reservation-day-hero`,
                children: [
                    (0, S.jsxs)(`div`, {
                        children: [
                            (0, S.jsx)(`p`, {
                                className: `reservation-day-kicker`,
                                children: `Journ\u00e9e`,
                            }),
                            (0, S.jsx)(`p`, {
                                className: `reservation-day-date`,
                                children: C.format(e),
                            }),
                            (0, S.jsxs)(`p`, {
                                className: `reservation-day-meta`,
                                children: [
                                    s.length,
                                    s.length > 1 ? ` reservations sur ` : ` reservation sur `,
                                    f?.name || `le v\u00e9hicule`,
                                ],
                            }),
                        ],
                    }),
                    (0, S.jsxs)(`div`, {
                        className: `reservation-day-pills`,
                        children: [
                            (0, S.jsxs)(`span`, {
                                className: `reservation-day-pill reservation-day-pill-morning`,
                                children: [
                                    (0, S.jsx)(`i`, {}),
                                    `Matin ${u.morningStart}-${u.morningEnd}`,
                                ],
                            }),
                            (0, S.jsxs)(`span`, {
                                className: `reservation-day-pill reservation-day-pill-afternoon`,
                                children: [
                                    (0, S.jsx)(`i`, {}),
                                    `Apr\u00e8s-midi ${u.afternoonStart}-${u.afternoonEnd}`,
                                ],
                            }),
                        ],
                    }),
                ],
            }),
            (0, S.jsx)(`div`, {
                className: `reservation-day-slots`,
                children: l.map((e) =>
                    (0, S.jsx)(
                        ReservationDaySlot,
                        {
                            slot: e,
                            reservations: s,
                            vehicle: f,
                            site: r,
                            canEditReservation: a,
                            onEditReservation: o,
                            onSelectSlot: i,
                        },
                        e.key,
                    ),
                ),
            }),
        ],
    });
}
function he({
    view: e,
    focusDate: t,
    reservations: n,
    vehicles: r,
    site: p,
    onViewChange: i,
    onFocusDateChange: a,
    onSelectSlot: o,
    canEditReservation: s,
    onEditReservation: c,
}) {
    let u = e === `month` ? 30 : 1,
        f = e === `week` ? `day` : e;
    return (0, S.jsxs)(l, {
        padding: `none`,
        className: `overflow-hidden rounded-xl`,
        children: [
            (0, S.jsx)(z, {
                view: f,
                focusDate: t,
                onViewChange: i,
                onPrevious: () => a(O(t, -u)),
                onNext: () => a(O(t, u)),
                onToday: () => a(new Date()),
            }),
            f === `month` &&
                (0, S.jsx)(B, {
                    focusDate: t,
                    reservations: n,
                    vehicles: r,
                    site: p,
                    onSelectDay: (e) => {
                        (a(e), i(`day`));
                    },
                }),
            f === `day` &&
                (0, S.jsx)(V, {
                    focusDate: t,
                    reservations: n,
                    vehicles: r,
                    site: p,
                    onSelectSlot: o,
                    canEditReservation: s,
                    onEditReservation: c,
                }),
            (0, S.jsx)(ReservationPeriodLegend, {}),
        ],
    });
}
function ge({
    open: e,
    mode: t,
    form: n,
    vehicles: r,
    notice: i,
    loading: o,
    canSubmit: l,
    onChange: u,
    onSubmit: f,
    onClose: p,
}) {
    let m = t === `edit`;
    return (
        (0, _.useEffect)(() => {
            if (!e) return;
            let t = (e) => {
                e.key === `Escape` && p();
            };
            return (
                document.addEventListener(`keydown`, t),
                window.setTimeout(
                    () => document.getElementById(`reservation-title`)?.focus(),
                    80,
                ),
                () => document.removeEventListener(`keydown`, t)
            );
        }, [e, p]),
        e
            ? (0, S.jsxs)(`div`, {
                  className: `fixed inset-0 z-[1050] flex items-center justify-center p-4`,
                  children: [
                      (0, S.jsx)(`button`, {
                          type: `button`,
                          className: `absolute inset-0 bg-black/50 backdrop-blur-sm`,
                          "aria-label": `Fermer la reservation rapide`,
                          onClick: p,
                      }),
                      (0, S.jsxs)(`div`, {
                          className: `card relative max-h-[90vh] w-full max-w-2xl overflow-hidden overflow-y-auto rounded-2xl`,
                          children: [
                              (0, S.jsxs)(`div`, {
                                  className: `sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4 dark:border-surface-700 dark:bg-surface-900`,
                                  children: [
                                      (0, S.jsxs)(`div`, {
                                          children: [
                                              (0, S.jsx)(`h2`, {
                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                  children: m
                                                      ? `Modifier reservation`
                                                      : `Reservation rapide`,
                                              }),
                                              (0, S.jsx)(`p`, {
                                                  className: `mt-1 text-body-sm text-secondary-500 dark:text-secondary-400`,
                                                  children: `Formulaire Sprinter branche sur le planning CRM.`,
                                              }),
                                          ],
                                      }),
                                      (0, S.jsxs)(`div`, {
                                          className: `flex items-center gap-2`,
                                          children: [
                                              (0, S.jsx)(s, {
                                                  variant: l
                                                      ? `primary`
                                                      : `neutral`,
                                                  children: l
                                                      ? `Autorise`
                                                      : `Lecture seule`,
                                              }),
                                              (0, S.jsx)(a, {
                                                  type: `button`,
                                                  variant: `ghost`,
                                                  size: `sm`,
                                                  iconOnly: !0,
                                                  onClick: p,
                                                  "aria-label": `Fermer`,
                                                  children: (0, S.jsx)(c, {
                                                      icon: d.x,
                                                      className: `h-5 w-5`,
                                                  }),
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                              (0, S.jsx)(`div`, {
                                  className: `p-6`,
                                  children: (0, S.jsx)(H, {
                                      form: n,
                                      vehicles: r,
                                      notice: i,
                                      loading: o,
                                      canSubmit: l,
                                      submitLabel: m
                                          ? `Modifier la reservation`
                                          : `Enregistrer la reservation`,
                                      onChange: u,
                                      onSubmit: f,
                                  }),
                              }),
                          ],
                      }),
                  ],
              })
            : null
    );
}
function _e({ notice: e, className: n }) {
    return e
        ? (0, S.jsx)(`div`, {
              className: t(
                  `rounded-xl border p-3 text-body-sm`,
                  e.type === `success`
                      ? `border-success-200 bg-success-50 text-success-800 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200`
                      : `border-danger-200 bg-danger-50 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200`,
                  n,
              ),
              children: e.message,
          })
        : null;
}
function H({
    form: t,
    vehicles: n,
    notice: r,
    loading: o,
    canSubmit: s,
    submitLabel: l,
    onChange: p,
    onSubmit: m,
}) {
    return (0, S.jsxs)(S.Fragment, {
        children: [
            (0, S.jsx)(_e, { notice: r, className: `mb-4` }),
            (0, S.jsxs)(`form`, {
                className: `space-y-4`,
                onSubmit: m,
                children: [
                    (0, S.jsx)(u, {
                        label: `Vehicule`,
                        htmlFor: `reservation-vehicle`,
                        required: !0,
                        children: (0, S.jsx)(i, {
                            id: `reservation-vehicle`,
                            value: t.vehicleId,
                            disabled: !s,
                            onChange: (e) =>
                                p({ ...t, vehicleId: e.target.value }),
                            children: n.map((e) =>
                                (0, S.jsx)(
                                    `option`,
                                    { value: e.id, children: e.name },
                                    e.id,
                                ),
                            ),
                        }),
                    }),
                    (0, S.jsxs)(`div`, {
                        className: `grid grid-cols-1 gap-4 md:grid-cols-2`,
                        children: [
                            (0, S.jsx)(u, {
                                label: `Nom du client`,
                                htmlFor: `reservation-title`,
                                children: (0, S.jsx)(f, {
                                    id: `reservation-title`,
                                    value: t.title,
                                    disabled: !s,
                                    placeholder: `Ex : Client Dupont`,
                                    onChange: (e) =>
                                        p({ ...t, title: e.target.value }),
                                }),
                            }),
                            (0, S.jsx)(u, {
                                label: `Telephone`,
                                htmlFor: `reservation-phone`,
                                children: (0, S.jsx)(f, {
                                    id: `reservation-phone`,
                                    value: t.contactPhone,
                                    disabled: !s,
                                    placeholder: `06 12 34 56 78`,
                                    onChange: (e) =>
                                        p({
                                            ...t,
                                            contactPhone: e.target.value,
                                        }),
                                }),
                            }),
                        ],
                    }),
                    (0, S.jsxs)(`div`, {
                        className: `grid grid-cols-1 gap-4 md:grid-cols-2`,
                        children: [
                            (0, S.jsx)(u, {
                                label: `Debut`,
                                htmlFor: `reservation-start`,
                                required: !0,
                                children: (0, S.jsx)(f, {
                                    id: `reservation-start`,
                                    type: `datetime-local`,
                                    value: t.startAt,
                                    disabled: !s,
                                    onChange: (e) =>
                                        p({ ...t, startAt: e.target.value }),
                                }),
                            }),
                            (0, S.jsx)(u, {
                                label: `Fin`,
                                htmlFor: `reservation-end`,
                                required: !0,
                                children: (0, S.jsx)(f, {
                                    id: `reservation-end`,
                                    type: `datetime-local`,
                                    value: t.endAt,
                                    disabled: !s,
                                    onChange: (e) =>
                                        p({ ...t, endAt: e.target.value }),
                                }),
                            }),
                        ],
                    }),
                    (0, S.jsx)(u, {
                        label: `Notes`,
                        htmlFor: `reservation-notes`,
                        children: (0, S.jsx)(e, {
                            id: `reservation-notes`,
                            value: t.notes,
                            disabled: !s,
                            rows: 3,
                            placeholder: `Adresse, materiel, precision chantier...`,
                            onChange: (e) => p({ ...t, notes: e.target.value }),
                        }),
                    }),
                    (0, S.jsxs)(a, {
                        type: `submit`,
                        fullWidth: !0,
                        loading: o,
                        disabled: !s || n.length === 0,
                        children: [(0, S.jsx)(c, { icon: d.truck }), l],
                    }),
                ],
            }),
        ],
    });
}
function U({
    reservation: e,
    vehicle: t,
    role: n,
    activeUser: r,
    deletingReservationId: i,
    canEditReservation: o,
    onEdit: s,
    onDelete: l,
}) {
    let u =
            N(n, `reservations.delete_any`) ||
            (N(n, `reservations.delete_own`) && e.userId === r.id),
        f = o(e);
    return (0, S.jsxs)(`div`, {
        className: `min-w-[260px] rounded-xl border border-surface-200 p-3 dark:border-surface-700`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `mb-2 flex items-center justify-between gap-2`,
                children: [
                    (0, S.jsx)(L, { vehicle: t }),
                    (0, S.jsxs)(`div`, {
                        className: `flex items-center gap-2`,
                        children: [
                            (0, S.jsxs)(`span`, {
                                className: `text-ui-xs text-secondary-400`,
                                children: [`#`, e.id],
                            }),
                            f &&
                                (0, S.jsx)(a, {
                                    variant: `ghost`,
                                    size: `sm`,
                                    iconOnly: !0,
                                    "aria-label": `Modifier la reservation`,
                                    onClick: () => s(e),
                                    children: (0, S.jsx)(c, {
                                        icon: d.edit,
                                        className: `h-4 w-4`,
                                    }),
                                }),
                            u &&
                                (0, S.jsx)(a, {
                                    variant: `ghost`,
                                    size: `sm`,
                                    iconOnly: !0,
                                    loading: i === e.id,
                                    "aria-label": `Supprimer la reservation`,
                                    onClick: () => l(e),
                                    children: (0, S.jsx)(c, {
                                        icon: d.trash,
                                        className: `h-4 w-4`,
                                    }),
                                }),
                        ],
                    }),
                ],
            }),
            (0, S.jsx)(`p`, {
                className: `text-label text-secondary-900 dark:text-white`,
                children: e.title || `Reservation vehicule`,
            }),
            (0, S.jsx)(`p`, {
                className: `mt-1 text-caption text-secondary-500`,
                children: de(e.startAt),
            }),
            (0, S.jsxs)(`p`, {
                className: `text-caption text-secondary-400`,
                children: [`Par `, e.userName],
            }),
        ],
    });
}
function ve({
    reservations: e,
    vehicles: t,
    role: n,
    activeUser: r,
    deletingReservationId: i,
    canEditReservation: a,
    onViewAll: o,
    onEdit: c,
    onDelete: u,
}) {
    let d = e.slice(0, 5);
    return (0, S.jsxs)(l, {
        className: `rounded-xl`,
        padding: `sm`,
        children: [
            (0, S.jsxs)(`div`, {
                className: `mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`,
                children: [
                    (0, S.jsxs)(`div`, {
                        children: [
                            (0, S.jsx)(`h2`, {
                                className: `heading-5 text-secondary-900 dark:text-white`,
                                children: `A venir`,
                            }),
                            (0, S.jsx)(`p`, {
                                className: `text-body-sm text-secondary-500 dark:text-secondary-400`,
                                children: `Reservations du site actif`,
                            }),
                        ],
                    }),
                    (0, S.jsxs)(`div`, {
                        className: `flex items-center gap-3`,
                        children: [
                            (0, S.jsx)(s, {
                                variant: `primary`,
                                children: e.length,
                            }),
                            (0, S.jsx)(`button`, {
                                type: `button`,
                                className: `text-body-sm font-semibold text-theme-primary hover:text-theme-primary/80`,
                                onClick: o,
                                children: `Voir tout`,
                            }),
                        ],
                    }),
                ],
            }),
            (0, S.jsx)(`div`, {
                className: `flex gap-3 overflow-x-auto pb-1`,
                children: d.length
                    ? d.map((e) =>
                          (0, S.jsx)(
                              U,
                              {
                                  reservation: e,
                                  vehicle: F(t, e.vehicleId),
                                  role: n,
                                  activeUser: r,
                                  deletingReservationId: i,
                                  canEditReservation: a,
                                  onEdit: c,
                                  onDelete: u,
                              },
                              e.id,
                          ),
                      )
                    : (0, S.jsx)(`p`, {
                          className: `w-full rounded-xl border border-dashed border-surface-200 p-4 text-center text-body-sm text-secondary-400 dark:border-surface-700`,
                          children: `Aucune reservation a venir.`,
                      }),
            }),
        ],
    });
}
function ye({
    open: e,
    reservations: t,
    vehicles: n,
    role: r,
    activeUser: i,
    deletingReservationId: o,
    canEditReservation: l,
    onEdit: u,
    onDelete: f,
    onClose: p,
}) {
    return (
        (0, _.useEffect)(() => {
            if (!e) return;
            let t = (e) => {
                e.key === `Escape` && p();
            };
            return (
                document.addEventListener(`keydown`, t),
                () => document.removeEventListener(`keydown`, t)
            );
        }, [e, p]),
        e
            ? (0, S.jsxs)(`div`, {
                  className: `fixed inset-0 z-[1040] flex items-center justify-center p-4`,
                  children: [
                      (0, S.jsx)(`button`, {
                          type: `button`,
                          className: `absolute inset-0 bg-black/50 backdrop-blur-sm`,
                          "aria-label": `Fermer les reservations a venir`,
                          onClick: p,
                      }),
                      (0, S.jsxs)(`div`, {
                          className: `card relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-2xl`,
                          children: [
                              (0, S.jsxs)(`div`, {
                                  className: `flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4 dark:border-surface-700 dark:bg-surface-900`,
                                  children: [
                                      (0, S.jsxs)(`div`, {
                                          children: [
                                              (0, S.jsx)(`h2`, {
                                                  className: `heading-5 text-secondary-900 dark:text-white`,
                                                  children: `A venir`,
                                              }),
                                              (0, S.jsx)(`p`, {
                                                  className: `mt-1 text-body-sm text-secondary-500 dark:text-secondary-400`,
                                                  children: `Toutes les reservations du site actif`,
                                              }),
                                          ],
                                      }),
                                      (0, S.jsxs)(`div`, {
                                          className: `flex items-center gap-2`,
                                          children: [
                                              (0, S.jsx)(s, {
                                                  variant: `primary`,
                                                  children: t.length,
                                              }),
                                              (0, S.jsx)(a, {
                                                  type: `button`,
                                                  variant: `ghost`,
                                                  size: `sm`,
                                                  iconOnly: !0,
                                                  onClick: p,
                                                  "aria-label": `Fermer`,
                                                  children: (0, S.jsx)(c, {
                                                      icon: d.x,
                                                      className: `h-5 w-5`,
                                                  }),
                                              }),
                                          ],
                                      }),
                                  ],
                              }),
                              (0, S.jsx)(`div`, {
                                  className: `max-h-[70vh] overflow-y-auto p-6`,
                                  children: t.length
                                      ? (0, S.jsx)(`div`, {
                                            className: `grid grid-cols-1 gap-3 md:grid-cols-2`,
                                            children: t.map((e) =>
                                                (0, S.jsx)(
                                                    U,
                                                    {
                                                        reservation: e,
                                                        vehicle: F(
                                                            n,
                                                            e.vehicleId,
                                                        ),
                                                        role: r,
                                                        activeUser: i,
                                                        deletingReservationId:
                                                            o,
                                                        canEditReservation: l,
                                                        onEdit: u,
                                                        onDelete: f,
                                                    },
                                                    e.id,
                                                ),
                                            ),
                                        })
                                      : (0, S.jsx)(`p`, {
                                            className: `rounded-xl border border-dashed border-surface-200 p-6 text-center text-body-sm text-secondary-400 dark:border-surface-700`,
                                            children: `Aucune reservation a venir.`,
                                        }),
                              }),
                          ],
                      }),
                  ],
              })
            : null
    );
}
function be({ role: e }) {
    return (0, S.jsxs)(l, {
        className: `rounded-xl border border-dashed border-danger-200 bg-danger-50/60 text-center dark:border-danger-800 dark:bg-danger-900/20`,
        children: [
            (0, S.jsx)(`div`, {
                className: `mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-danger-100 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300`,
                children: (0, S.jsx)(c, { icon: d.lock, className: `h-7 w-7` }),
            }),
            (0, S.jsx)(`h2`, {
                className: `heading-4 text-secondary-900 dark:text-white`,
                children: `Module reservation masque`,
            }),
            (0, S.jsxs)(`p`, {
                className: `mx-auto mt-2 max-w-xl text-body-sm text-secondary-600 dark:text-secondary-300`,
                children: [
                    `Le profil `,
                    (0, S.jsx)(`strong`, { children: e.label }),
                    " ne possede pas `reservations.view`. Dans la version connectee, cette entree disparaitra du menu pour l utilisateur.",
                ],
            }),
        ],
    });
}
function qt(e) {
    let t = Number(
        window.CRM_ACTIVE_SITE?.getSiteId?.() ||
            window.localStorage.getItem(`crm:active-site-id`) ||
            0,
    );
    return e.find((e) => e.id === t) || e[0];
}
function W() {
    let [e, t] = (0, _.useState)(1),
        [n, r] = (0, _.useState)(m),
        [o, s] = (0, _.useState)(ee),
        [l, u] = (0, _.useState)(ne),
        [f, h] = (0, _.useState)(g),
        [te, v] = (0, _.useState)(g[0].id),
        [y, b] = (0, _.useState)(`loading`),
        [x, ce] = (0, _.useState)(`month`),
        [le, C] = (0, _.useState)(() => new Date(`2026-07-22T00:00`)),
        [w, E] = (0, _.useState)(re),
        [O, k] = (0, _.useState)(ue),
        [A, j] = (0, _.useState)(null),
        [L, R] = (0, _.useState)(!1),
        [z, B] = (0, _.useState)(!1),
        [me, V] = (0, _.useState)(!1),
        [H, U] = (0, _.useState)(null),
        [W, xe] = (0, _.useState)(null),
        [Pe, Fe] = (0, _.useState)(null);
    ((0, _.useEffect)(() => {
        let e = !0;
        return (
            ie().then((n) => {
                if (!e) return;
                (r(n.sites),
                    s(n.modules),
                    u(n.vehicles),
                    h(n.users),
                    E(n.reservations),
                    v(n.user.id),
                    b(n.source));
                let i = qt(n.sites),
                    a = n.vehicles.find((e) => e.siteId === i?.id && e.active);
                (i && t(i.id),
                    a && k((e) => ({ ...e, vehicleId: String(a.id) })),
                    Fe(null),
                    n.source === `fallback` &&
                        n.error &&
                        j({
                            type: `error`,
                            message: `Mode local : ${n.error}`,
                        }));
            }),
            () => {
                e = !1;
            }
        );
    }, []),
        (0, _.useEffect)(() => {
            let e = (e) => {
                let n = Number(
                    e.detail?.siteId ||
                        window.CRM_ACTIVE_SITE?.getSiteId?.() ||
                        window.localStorage.getItem(`crm:active-site-id`) ||
                        0,
                );
                n && je(n);
            };
            return (
                window.addEventListener(`crm:active-site-changed`, e),
                e({
                    detail: {
                        siteId: Number(
                            window.CRM_ACTIVE_SITE?.getSiteId?.() ||
                                window.localStorage.getItem(
                                    `crm:active-site-id`,
                                ) ||
                                0,
                        ),
                    },
                }),
                () => window.removeEventListener(`crm:active-site-changed`, e)
            );
        }, [l]));
    let G = f.find((e) => e.id === te) ?? f[0] ?? g[0],
        K = pe(G, o),
        q = n.find((t) => t.id === e) ?? n[0] ?? m[0],
        J = (0, _.useMemo)(
            () => l.filter((e) => e.siteId === q.id && e.active),
            [q.id, l],
        ),
        Y = (0, _.useMemo)(() => w.filter((e) => e.siteId === q.id), [q.id, w]),
        selectedVehicle = (0, _.useMemo)(
            () => J.find((e) => e.id === Pe) ?? null,
            [J, Pe],
        ),
        filteredReservations = (0, _.useMemo)(
            () => (selectedVehicle ? Y.filter((e) => e.vehicleId === selectedVehicle.id) : []),
            [Y, selectedVehicle],
        ),
        Se = (0, _.useMemo)(
            () =>
                filteredReservations.filter(
                    (e) => T(e.endAt) >= new Date(`2026-07-04T00:00`),
                ).sort((e, t) => e.startAt.localeCompare(t.startAt)),
            [filteredReservations],
        ),
        X = new Date(`2026-07-04T12:00`),
        Ce = new Date(X);
    Ce.setHours(X.getHours() + 1);
    let we = J.filter(
            (e) =>
                !Y.some((t) =>
                    t.vehicleId === e.id
                        ? P(D(X), D(Ce), t.startAt, t.endAt)
                        : !1,
                ),
        ),
        Z = filteredReservations.filter((e) => T(e.startAt) >= X).sort((e, t) =>
            e.startAt.localeCompare(t.startAt),
        )[0],
        Te = filteredReservations.filter((e) => e.startAt.startsWith(`2026-07`)),
        Ee = N(K, `reservations.view`),
        De = H ? (Y.find((e) => e.id === H) ?? null) : null,
        Q = (0, _.useCallback)((e) => fe(K, G, e), [G, K]),
        Oe = De ? Q(De) : N(K, `reservations.create`),
        ke = (0, _.useCallback)(() => {
            (B(!1), j(null), U(null));
        }, []);
    function Ae(e, t) {
        if (!e || !t) {
            let n = siteDaySlots(le, q)[0];
            e = D(n.start);
            t = D(n.end);
        }
        (j(null),
            U(null),
            k((n) => {
                let i = selectedVehicle,
                    r =
                    e && t
                        ? i &&
                          !Y.some((n) =>
                              n.vehicleId === i.id
                                  ? P(e, t, n.startAt, n.endAt)
                                  : !1,
                          )
                            ? i
                            : J.find(
                              (n) =>
                                  !Y.some((r) =>
                                      r.vehicleId === n.id
                                          ? P(e, t, r.startAt, r.endAt)
                                          : !1,
                                  ),
                          )
                        : i;
                return {
                    ...n,
                    ...(e && t
                        ? {
                              startAt: e,
                              endAt: t,
                              title: ``,
                              contactPhone: ``,
                              notes: ``,
                          }
                        : {}),
                    vehicleId: String(r?.id ?? i?.id ?? J[0]?.id ?? n.vehicleId),
                };
            }),
            B(!0));
    }
    function $(e) {
        (j(null),
            V(!1),
            U(e.id),
            k({
                vehicleId: String(e.vehicleId),
                title: e.title,
                contactPhone: e.contactPhone,
                startAt: e.startAt,
                endAt: e.endAt,
                notes: e.notes,
            }),
            C(T(e.startAt)),
            B(!0));
    }
    function je(e) {
        let n = Number(e),
            r = l.find((e) => e.siteId === n && e.active);
        (t(n), Fe(null), k((e) => ({ ...e, vehicleId: String(r?.id ?? ``) })));
    }
    function selectVehicle(e) {
        let n = Number(e) || null,
            r = J.find((e) => e.id === n);
        (Fe(n), r && k((e) => ({ ...e, vehicleId: String(r.id) })));
    }
    async function Me(e) {
        (e.preventDefault(), j(null));
        let t = H ? (w.find((e) => e.id === H) ?? null) : null,
            n = H !== null,
            r = t ? fe(K, G, t) : N(K, `reservations.create`);
        if (n && !t) {
            j({
                type: `error`,
                message: `Reservation introuvable ou deja supprimee.`,
            });
            return;
        }
        if (!r) {
            j({
                type: `error`,
                message: n
                    ? `Ce role ne peut pas modifier cette reservation.`
                    : `Ce role ne peut pas creer de reservation.`,
            });
            return;
        }
        if (
            !O.vehicleId ||
            !O.startAt ||
            !O.endAt ||
            T(O.endAt) <= T(O.startAt)
        ) {
            j({
                type: `error`,
                message: `Creneau invalide : verifie le vehicule et les dates.`,
            });
            return;
        }
        let i = Number(O.vehicleId);
        if (
            w.some((e) =>
                t && e.id === t.id
                    ? !1
                    : e.vehicleId === i &&
                      P(O.startAt, O.endAt, e.startAt, e.endAt),
            )
        ) {
            j({
                type: `error`,
                message: `Creneau indisponible : ce vehicule est deja reserve sur cette periode.`,
            });
            return;
        }
        R(!0);
        try {
            let e = O.title.trim() || `Reservation vehicule`,
                n = O.contactPhone.trim(),
                r = O.notes.trim();
            if (t) {
                let a =
                    y === `api`
                        ? await oe({
                              id: t.id,
                              vehicleId: i,
                              title: e,
                              contactPhone: n,
                              startAt: O.startAt,
                              endAt: O.endAt,
                              notes: r,
                              actorUserId: G.id,
                          })
                        : {
                              ...t,
                              siteId: F(l, i)?.siteId ?? q.id,
                              vehicleId: i,
                              title: e,
                              contactPhone: n,
                              startAt: O.startAt,
                              endAt: O.endAt,
                              notes: r,
                          };
                (E((e) => e.map((e) => (e.id === a.id ? a : e))),
                    j({
                        type: `success`,
                        message:
                            y === `api`
                                ? `Reservation modifiee en base MySQL.`
                                : `Reservation modifiee en mode local.`,
                    }));
            } else {
                let t =
                    y === `api`
                        ? await ae({
                              vehicleId: i,
                              title: e,
                              contactPhone: n,
                              startAt: O.startAt,
                              endAt: O.endAt,
                              notes: r,
                              actorUserId: G.id,
                          })
                        : {
                              id: Math.max(0, ...w.map((e) => e.id)) + 1,
                              siteId: q.id,
                              vehicleId: i,
                              userId: G.id,
                              userName: G.name,
                              title: e,
                              contactPhone: n,
                              startAt: O.startAt,
                              endAt: O.endAt,
                              notes: r,
                          };
                (E((e) => [...e, t]),
                    k((e) => ({
                        ...e,
                        title: ``,
                        contactPhone: ``,
                        notes: ``,
                    })),
                    j({
                        type: `success`,
                        message:
                            y === `api`
                                ? `Reservation enregistree en base MySQL.`
                                : `Reservation preparee en mode local.`,
                    }));
            }
            (C(T(O.startAt)), B(!1), U(null));
        } catch (e) {
            j({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible d enregistrer la reservation.`,
            });
        } finally {
            R(!1);
        }
    }
    async function Ne(e) {
        (j(null), xe(e.id));
        try {
            (y === `api` && (await se(e.id, G.id)),
                E((t) => t.filter((t) => t.id !== e.id)),
                j({ type: `success`, message: `Reservation supprimee.` }));
        } catch (e) {
            j({
                type: `error`,
                message:
                    e instanceof Error
                        ? e.message
                        : `Impossible de supprimer la reservation.`,
            });
        } finally {
            xe(null);
        }
    }
    return (0, S.jsxs)(`div`, {
        className: `animate-fade-in space-y-6`,
        children: [
            (0, S.jsx)(p, {
                title: `Reservations vehicules`,
                subtitle:
                    y === `api`
                        ? `Module Sprinter connecte a MySQL`
                        : `Module Sprinter dans le CRM Martin Sols`,
                actions: (0, S.jsxs)(S.Fragment, {
                    children: [
                        (0, S.jsxs)(a, {
                            onClick: () => Ae(),
                            children: [
                                (0, S.jsx)(c, { icon: d.plus }),
                                `Nouvelle reservation`,
                            ],
                        }),
                    ],
                }),
            }),
            (0, S.jsx)(ReservationTimelineStyles, {}),
            !z && (0, S.jsx)(_e, { notice: A }),
            Ee
                ? (0, S.jsxs)(S.Fragment, {
                      children: [
                          (0, S.jsxs)(`div`, {
                              className: `space-y-6`,
                              children: [
                                  (0, S.jsx)(VehicleResourceSection, {
                                      vehicles: J,
                                      reservations: Y,
                                      selectedVehicle: selectedVehicle,
                                      onSelect: selectVehicle,
                                      onClear: () => Fe(null),
                                  }),
                                  selectedVehicle &&
                                      (0, S.jsx)(he, {
                                          view: x,
                                          focusDate: le,
                                          reservations: filteredReservations,
                                          vehicles: [selectedVehicle],
                                          site: q,
                                          onViewChange: ce,
                                          onFocusDateChange: C,
                                          onSelectSlot: Ae,
                                          canEditReservation: Q,
                                          onEditReservation: $,
                                      }),
                              ],
                          }),
                          (0, S.jsx)(ye, {
                              open: me,
                              reservations: Se,
                              vehicles: J,
                              role: K,
                              activeUser: G,
                              deletingReservationId: W,
                              canEditReservation: Q,
                              onEdit: $,
                              onDelete: Ne,
                              onClose: () => V(!1),
                          }),
                          (0, S.jsx)(ge, {
                              open: z,
                              mode: H ? `edit` : `create`,
                              form: O,
                              vehicles: J,
                              notice: A,
                              loading: L,
                              canSubmit: Oe,
                              onChange: k,
                              onSubmit: Me,
                              onClose: ke,
                          }),
                      ],
                  })
                : (0, S.jsx)(be, { role: K }),
        ],
    });
}
export { W as ReservationsPage };
