(() => {
  const api = "/api/conges.php";
  let root = null;
  const mountedRoots = new WeakSet();
  const state = {
    data: null,
    month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    filters: {
      employeeId: "all",
      type: "all",
      status: "active",
      query: "",
    },
    selectedDate: formatDate(new Date()),
    modal: null,
  };

  const esc = (value) => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

  function parseDate(value) {
    const [year, month, day] = String(value || "").split("-").map(Number);
    return new Date(year, month - 1, day);
  }

  function formatDate(date) {
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, "0"),
      String(date.getDate()).padStart(2, "0"),
    ].join("-");
  }

  function dateLabel(value) {
    const date = parseDate(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString("fr-FR", { day: "2-digit", month: "short", year: "numeric" });
  }

  function monthLabel(date) {
    const label = date.toLocaleDateString("fr-FR", { month: "long", year: "numeric" });
    return label.charAt(0).toUpperCase() + label.slice(1);
  }

  function addDays(date, days) {
    const next = new Date(date);
    next.setDate(next.getDate() + days);
    return next;
  }

  function calendarDays(date) {
    const first = new Date(date.getFullYear(), date.getMonth(), 1);
    const last = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    const start = addDays(first, -((first.getDay() + 6) % 7));
    const end = addDays(last, 6 - ((last.getDay() + 6) % 7));
    const days = [];
    const current = new Date(start);

    while (current <= end) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }

    return days;
  }

  function weekStart(date) {
    return addDays(date, -((date.getDay() + 6) % 7));
  }

  function weekKey(date) {
    return formatDate(weekStart(date));
  }

  function normalizeColor(value, fallback = "#facc15") {
    return /^#[0-9a-f]{6}$/i.test(String(value || "")) ? value : fallback;
  }

  function typeMeta(type) {
    return (state.data?.types || []).find((item) => item.value === type) || { value: "conge", label: "Conge", color: "#facc15" };
  }

  function employees() {
    return (state.data?.employees || []).filter((employee) => employee.active);
  }

  function employeeSortIndex(employeeId) {
    const index = employees().findIndex((employee) => Number(employee.id) === Number(employeeId));
    return index === -1 ? 9999 : index;
  }

  function selectedEmployee() {
    if (state.filters.employeeId === "all") return null;
    return employees().find((employee) => Number(employee.id) === Number(state.filters.employeeId)) || null;
  }

  function activeSiteId() {
    const fromApi = Number(window.CRM_ACTIVE_SITE?.getSiteId?.() || 0);
    if (Number.isFinite(fromApi) && fromApi > 0) return fromApi;

    try {
      const fromStorage = Number(window.localStorage.getItem("crm:active-site-id") || 0);
      if (Number.isFinite(fromStorage) && fromStorage > 0) return fromStorage;
    } catch (error) {
      // The server will fall back to the first authorized site.
    }

    const selected = Number(state.data?.selectedSiteId || state.data?.user?.selectedSiteId || 0);
    return Number.isFinite(selected) && selected > 0 ? selected : "";
  }

  function activeSiteName() {
    const siteId = Number(state.data?.selectedSiteId || state.data?.user?.selectedSiteId || activeSiteId());
    const site = (state.data?.sites || []).find((item) => Number(item.id) === siteId);
    return site?.name || "Site actif";
  }

  function syncEmployeeFilter() {
    if (state.filters.employeeId === "all") return;
    const exists = employees().some((employee) => Number(employee.id) === Number(state.filters.employeeId));
    if (!exists) state.filters.employeeId = "all";
  }

  function statusLabel(status) {
    return {
      approved: "Valide",
      planned: "Planifie",
      pending: "A valider",
      refused: "Refuse",
    }[status] || status || "";
  }

  function periodLabel(period) {
    return {
      full: "Journee",
      morning: "Matin",
      afternoon: "Apres-midi",
    }[period] || period || "";
  }

  function matchesFilters(leave) {
    const filters = state.filters;
    const query = filters.query.trim().toLowerCase();

    if (filters.employeeId !== "all" && Number(leave.employeeId) !== Number(filters.employeeId)) return false;
    if (filters.type !== "all" && leave.type !== filters.type) return false;
    if (filters.status === "active" && leave.status === "refused") return false;
    if (filters.status !== "all" && filters.status !== "active" && leave.status !== filters.status) return false;

    if (query) {
      const meta = typeMeta(leave.type);
      const haystack = [
        leave.employeeName,
        meta.label,
        statusLabel(leave.status),
        periodLabel(leave.period),
        leave.startDate,
        leave.endDate,
        leave.notes,
      ].join(" ").toLowerCase();
      if (!haystack.includes(query)) return false;
    }

    return true;
  }

  function filteredLeaves() {
    return (state.data?.leaves || []).filter(matchesFilters);
  }

  function activeLeaves() {
    return filteredLeaves()
      .filter((leave) => leave.status !== "refused")
      .sort((a, b) => a.startDate.localeCompare(b.startDate) || a.employeeName.localeCompare(b.employeeName));
  }

  function leavesForDate(date) {
    return activeLeaves()
      .filter((leave) => leave.startDate <= date && leave.endDate >= date)
      .sort((a, b) => (
        employeeSortIndex(a.employeeId) - employeeSortIndex(b.employeeId)
        || a.employeeName.localeCompare(b.employeeName)
        || a.startDate.localeCompare(b.startDate)
      ));
  }

  function employeeIdsForWeek(day) {
    const start = weekStart(day);
    const end = addDays(start, 6);
    const first = formatDate(start);
    const last = formatDate(end);
    const seen = new Set();

    return activeLeaves()
      .filter((leave) => leave.startDate <= last && leave.endDate >= first)
      .sort((a, b) => (
        employeeSortIndex(a.employeeId) - employeeSortIndex(b.employeeId)
        || a.employeeName.localeCompare(b.employeeName)
        || a.startDate.localeCompare(b.startDate)
      ))
      .map((leave) => Number(leave.employeeId))
      .filter((employeeId) => {
        if (seen.has(employeeId)) return false;
        seen.add(employeeId);
        return true;
      });
  }

  function selectedDateLeaves() {
    return leavesForDate(state.selectedDate);
  }

  function monthLeaves() {
    const first = formatDate(new Date(state.month.getFullYear(), state.month.getMonth(), 1));
    const last = formatDate(new Date(state.month.getFullYear(), state.month.getMonth() + 1, 0));
    return activeLeaves().filter((leave) => leave.startDate <= last && leave.endDate >= first);
  }

  function yearLeaves() {
    const first = `${state.month.getFullYear()}-01-01`;
    const last = `${state.month.getFullYear()}-12-31`;
    return activeLeaves().filter((leave) => leave.startDate <= last && leave.endDate >= first);
  }

  function daysCount(leave) {
    const start = parseDate(leave.startDate);
    const end = parseDate(leave.endDate);
    const days = Math.max(1, Math.round((end - start) / 86400000) + 1);
    return days === 1 && leave.period !== "full" ? 0.5 : days;
  }

  function overlapDays(leave, startDate, endDate) {
    const start = parseDate(leave.startDate);
    const end = parseDate(leave.endDate);
    const rangeStart = parseDate(startDate);
    const rangeEnd = parseDate(endDate);
    const from = start > rangeStart ? start : rangeStart;
    const to = end < rangeEnd ? end : rangeEnd;

    if (to < from) return 0;

    const days = Math.max(1, Math.round((to - from) / 86400000) + 1);
    return days === 1 && leave.period !== "full" ? 0.5 : days;
  }

  function formatDaysCount(value) {
    return Number.isInteger(value) ? String(value) : String(value).replace(".", ",");
  }

  function openModal(leave) {
    state.modal = {
      id: leave?.id || "",
      employeeId: leave?.employeeId || selectedEmployee()?.id || employees()[0]?.id || "",
      startDate: leave?.startDate || formatDate(new Date()),
      endDate: leave?.endDate || leave?.startDate || formatDate(new Date()),
      type: leave?.type || "conge",
      period: leave?.period || "full",
      status: leave?.status || "approved",
      notes: leave?.notes || "",
    };
    render();
  }

  async function request(action, payload = null) {
    const params = new URLSearchParams({ action });
    const siteId = activeSiteId();
    if (siteId) params.set("siteId", String(siteId));

    const options = { credentials: "same-origin" };
    if (payload) {
      if (siteId && payload.siteId === undefined && payload.site_id === undefined) {
        payload = { ...payload, siteId: Number(siteId) };
      }
      options.method = "POST";
      options.headers = { "Content-Type": "application/json" };
      options.body = JSON.stringify(payload);
    }
    const response = await fetch(`${api}?${params.toString()}`, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.ok === false) {
      throw new Error(data.error || "API conges indisponible");
    }
    return data;
  }

  async function load() {
    root.innerHTML = '<div class="leave-card leave-loading">Chargement...</div>';
    styles();
    try {
      state.data = await request("bootstrap");
      syncEmployeeFilter();
      render();
    } catch (error) {
      root.innerHTML = `<div class="leave-card leave-card-pad"><div class="leaves-notice">${esc(error instanceof Error ? error.message : "Chargement impossible")}</div></div>`;
      styles();
    }
  }

  function styles() {
    const target = root instanceof ShadowRoot ? root : document.head;
    let style = target.querySelector("#crm-conges-style");
    if (!style) {
      style = document.createElement("style");
      style.id = "crm-conges-style";
      target.appendChild(style);
    }

    const css = `
      #crm-leaves-module { color:var(--color-secondary-900,#0f172a); }
      #crm-leaves-module .leaves-page { display:grid; gap:1.5rem; }
      #crm-leaves-module .leaves-header { display:flex; flex-direction:column; gap:1rem; }
      #crm-leaves-module .leaves-kicker { color:rgb(var(--theme-primary)); font-size:.72rem; font-weight:800; text-transform:uppercase; }
      #crm-leaves-module .leaves-title { margin:.25rem 0 0; color:var(--color-secondary-900,#0f172a); font-size:1.6rem; font-weight:850; line-height:1.15; letter-spacing:0; }
      #crm-leaves-module .leaves-subtitle { margin:.35rem 0 0; color:var(--color-secondary-500,#64748b); font-size:.92rem; }
      #crm-leaves-module .leaves-header-actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
      #crm-leaves-module .leaves-button { display:inline-flex; min-height:2.35rem; align-items:center; justify-content:center; border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.55rem; background:#fff; color:var(--color-secondary-700,#334155); padding:.55rem .75rem; font-size:.84rem; font-weight:800; line-height:1; text-decoration:none; }
      #crm-leaves-module .leaves-button:hover { color:rgb(var(--theme-primary)); border-color:rgb(var(--theme-primary) / .45); background:rgb(var(--theme-primary) / .04); }
      #crm-leaves-module .leaves-button-primary { border-color:rgb(var(--theme-primary)); background:rgb(var(--theme-primary)); color:#fff; }
      #crm-leaves-module .leaves-button-primary:hover { color:#fff; filter:brightness(.97); }
      #crm-leaves-module .leaves-button.is-active { border-color:rgb(var(--theme-primary) / .45); background:rgb(var(--theme-primary) / .08); color:rgb(var(--theme-primary)); }
      #crm-leaves-module .leave-loading { min-height:16rem; display:grid; place-items:center; color:var(--color-secondary-500,#64748b); font-weight:800; }
      #crm-leaves-module .leave-summary { display:grid; grid-template-columns:repeat(1,minmax(0,1fr)); gap:1rem; }
      #crm-leaves-module .leave-summary-card { border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.75rem; background:#fff; padding:1rem; }
      #crm-leaves-module .leave-summary-card span { display:block; color:var(--color-secondary-500,#64748b); font-size:.75rem; font-weight:800; }
      #crm-leaves-module .leave-summary-card strong { display:block; margin:.25rem 0; color:var(--color-secondary-900,#0f172a); font-size:1.5rem; font-weight:850; line-height:1.1; letter-spacing:0; }
      #crm-leaves-module .leave-summary-card small { display:block; color:var(--color-secondary-400,#94a3b8); font-size:.72rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
      #crm-leaves-module .leave-workspace { display:grid; gap:1.5rem; }
      #crm-leaves-module .leave-card { border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.75rem; background:#fff; overflow:hidden; }
      #crm-leaves-module .leave-card-pad { padding:1rem; }
      #crm-leaves-module .leave-month-nav { display:flex; flex-direction:column; gap:.9rem; border-bottom:1px solid var(--color-surface-200,#e2e8f0); padding:1rem; }
      #crm-leaves-module .leave-month-left { display:flex; align-items:center; gap:.6rem; }
      #crm-leaves-module .leave-month-title { margin:0; color:var(--color-secondary-900,#0f172a); font-size:1.15rem; font-weight:850; line-height:1.15; letter-spacing:0; }
      #crm-leaves-module .leave-month-subtitle { margin:.15rem 0 0; color:var(--color-secondary-500,#64748b); font-size:.76rem; font-weight:700; }
      #crm-leaves-module .leave-nav-button { display:grid; place-items:center; width:2.2rem; height:2.2rem; border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.55rem; background:#fff; color:var(--color-secondary-600,#475569); font-size:1.2rem; line-height:1; font-weight:850; }
      #crm-leaves-module .leave-nav-button:hover { border-color:rgb(var(--theme-primary) / .45); color:rgb(var(--theme-primary)); background:rgb(var(--theme-primary) / .04); }
      #crm-leaves-module .leave-weekdays { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); background:var(--color-surface-50,#f8fafc); border-bottom:1px solid var(--color-surface-200,#e2e8f0); }
      #crm-leaves-module .leave-weekdays span { padding:.7rem .45rem; color:var(--color-secondary-500,#64748b); font-size:.72rem; font-weight:850; text-align:center; text-transform:uppercase; }
      #crm-leaves-module .leave-calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:0; background:var(--color-surface-200,#e2e8f0); }
      #crm-leaves-module .leave-date { --line-x-pad:.55rem; display:flex; min-width:0; min-height:7.2rem; flex-direction:column; border:0; background:#fff; box-shadow:inset -1px 0 0 var(--color-surface-200,#e2e8f0), inset 0 -1px 0 var(--color-surface-200,#e2e8f0); color:var(--color-secondary-900,#0f172a); padding:.55rem; text-align:left; transition:background-color .15s ease; }
      #crm-leaves-module .leave-date:hover { background:rgb(var(--theme-primary) / .04); }
      #crm-leaves-module .leave-date.is-selected { background:rgb(var(--theme-primary) / .04); }
      #crm-leaves-module .leave-date.is-other { background:var(--color-surface-50,#f8fafc); color:var(--color-secondary-400,#94a3b8); }
      #crm-leaves-module .leave-date.has-absences:not(.is-selected) { background:linear-gradient(180deg,#fff 0%,var(--color-surface-50,#f8fafc) 100%); }
      #crm-leaves-module .leave-date.is-today .leave-number { background:rgb(var(--theme-primary)); color:#fff; }
      #crm-leaves-module .leave-date.is-sunday:not(.is-other) .leave-number { color:#be123c; }
      #crm-leaves-module .leave-date.is-today.is-sunday .leave-number { color:#fff; }
      #crm-leaves-module .leave-day-head { display:flex; align-items:center; justify-content:space-between; gap:.35rem; }
      #crm-leaves-module .leave-number { display:inline-flex; width:1.8rem; height:1.8rem; align-items:center; justify-content:center; border-radius:999px; font-size:.86rem; font-weight:850; }
      #crm-leaves-module .leave-day-items { display:grid; align-content:start; gap:.18rem; margin-top:.45rem; }
      #crm-leaves-module .leave-line { --line-overlap-left:0rem; --line-overlap-right:0rem; display:block; min-width:0; width:calc(100% + var(--line-overlap-left) + var(--line-overlap-right)); height:.2rem; margin-left:calc(var(--line-overlap-left) * -1); border-radius:999px; background:var(--line-color); box-shadow:0 2px 7px var(--line-shadow); opacity:.95; }
      #crm-leaves-module .leave-line.is-continued-before { --line-overlap-left:var(--line-x-pad); border-top-left-radius:0; border-bottom-left-radius:0; }
      #crm-leaves-module .leave-line.is-continued-after { --line-overlap-right:var(--line-x-pad); border-top-right-radius:0; border-bottom-right-radius:0; }
      #crm-leaves-module .leave-line.is-morning,
      #crm-leaves-module .leave-line.is-afternoon { width:58%; }
      #crm-leaves-module .leave-line.is-afternoon { margin-left:auto; }
      #crm-leaves-module .leave-line.is-planned,
      #crm-leaves-module .leave-line.is-pending { opacity:.72; }
      #crm-leaves-module .leave-lane-spacer { display:block; height:.2rem; }
      #crm-leaves-module .leave-day-card, #crm-leaves-module .leave-users-card { border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.75rem; background:#fff; }
      #crm-leaves-module .leave-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid var(--color-surface-200,#e2e8f0); padding:1rem; }
      #crm-leaves-module .leave-card-title { margin:0; color:var(--color-secondary-900,#0f172a); font-size:1rem; font-weight:850; line-height:1.2; letter-spacing:0; }
      #crm-leaves-module .leave-card-subtitle { margin:.25rem 0 0; color:var(--color-secondary-500,#64748b); font-size:.8rem; font-weight:650; }
      #crm-leaves-module .leave-add-day { flex:0 0 auto; border:1px solid rgb(var(--theme-primary)); border-radius:.55rem; background:rgb(var(--theme-primary)); color:#fff; padding:.55rem .75rem; font-size:.78rem; font-weight:900; }
      #crm-leaves-module .leave-day-list { display:grid; gap:.55rem; padding:1rem; }
      #crm-leaves-module .leave-day-row { display:grid; grid-template-columns:1rem minmax(0,1fr) auto; align-items:center; gap:.55rem; min-height:2.5rem; border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.55rem; background:#fff; padding:.55rem .65rem; color:var(--color-secondary-900,#0f172a); text-align:left; }
      #crm-leaves-module .leave-day-row:hover { border-color:rgb(var(--theme-primary) / .45); background:rgb(var(--theme-primary) / .04); }
      #crm-leaves-module .leave-day-dot { width:.72rem; height:.72rem; border-radius:999px; background:var(--day-color); }
      #crm-leaves-module .leave-day-name { display:block; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.86rem; font-weight:850; }
      #crm-leaves-module .leave-day-meta { display:block; margin-top:.12rem; color:var(--color-secondary-500,#64748b); font-size:.72rem; font-weight:700; }
      #crm-leaves-module .leave-day-edit { color:rgb(var(--theme-primary)); font-size:.72rem; font-weight:900; }
      #crm-leaves-module .leave-day-empty { border:1px dashed var(--color-surface-300,#cbd5e1); border-radius:.55rem; padding:1rem; color:var(--color-secondary-500,#64748b); font-size:.85rem; font-weight:700; text-align:center; }
      #crm-leaves-module .leave-users-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; border-bottom:1px solid var(--color-surface-200,#e2e8f0); padding:1rem; }
      #crm-leaves-module .leave-users-title { margin:0; color:var(--color-secondary-900,#0f172a); font-size:1rem; font-weight:850; line-height:1.2; letter-spacing:0; }
      #crm-leaves-module .leave-users-site { margin:.25rem 0 0; color:var(--color-secondary-500,#64748b); font-size:.8rem; font-weight:650; }
      #crm-leaves-module .leave-users-count { display:inline-flex; align-items:center; border-radius:999px; background:rgb(var(--theme-primary) / .08); color:rgb(var(--theme-primary)); padding:.25rem .55rem; font-size:.72rem; font-weight:900; }
      #crm-leaves-module .leave-users-grid { display:grid; grid-template-columns:1fr; gap:.55rem; padding:1rem; }
      #crm-leaves-module .leave-user-row { display:grid; grid-template-columns:1rem minmax(0,1fr) auto; align-items:center; gap:.5rem; min-height:2.35rem; border:1px solid var(--color-surface-200,#e2e8f0); border-radius:.55rem; background:#fff; padding:.48rem .6rem; color:var(--color-secondary-900,#0f172a); text-align:left; }
      #crm-leaves-module .leave-user-row:hover, #crm-leaves-module .leave-user-row.is-active { border-color:rgb(var(--theme-primary) / .45); background:rgb(var(--theme-primary) / .05); }
      #crm-leaves-module .leave-user-dot { width:.72rem; height:.72rem; border-radius:999px; background:var(--user-color); }
      #crm-leaves-module .leave-user-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.84rem; font-weight:850; }
      #crm-leaves-module .leave-user-count { color:var(--color-secondary-500,#64748b); font-size:.8rem; font-weight:850; }
      #crm-leaves-module .leaves-modal-backdrop { position:fixed; inset:0; z-index:80; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,.48); padding:1rem; }
      #crm-leaves-module .leaves-modal { width:min(42rem,100%); max-height:calc(100vh - 2rem); overflow:auto; border-radius:.75rem; background:#fff; padding:1rem; box-shadow:0 24px 80px rgba(15,23,42,.24); }
      #crm-leaves-module .leaves-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.85rem; }
      #crm-leaves-module .leaves-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
      #crm-leaves-module .leaves-field { display:grid; gap:.32rem; }
      #crm-leaves-module .leaves-field-full { grid-column:1/-1; }
      #crm-leaves-module label { color:#475569; font-size:.76rem; font-weight:800; }
      #crm-leaves-module input, #crm-leaves-module select, #crm-leaves-module textarea { min-height:2.4rem; width:100%; border:1px solid #cbd5e1; border-radius:.55rem; background:#fff; color:#0f172a; padding:.5rem .65rem; font-size:.85rem; }
      #crm-leaves-module textarea { min-height:5.2rem; resize:vertical; }
      #crm-leaves-module .leaves-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
      #crm-leaves-module .leaves-button { display:inline-flex; min-height:2.35rem; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:.55rem; background:#fff; color:#334155; padding:.55rem .75rem; font-size:.84rem; font-weight:800; line-height:1; text-decoration:none; }
      #crm-leaves-module .leaves-button:hover { color:rgb(var(--theme-primary)); border-color:rgb(var(--theme-primary) / .45); }
      #crm-leaves-module .leaves-button-primary { border-color:rgb(var(--theme-primary)); background:rgb(var(--theme-primary)); color:#fff; }
      #crm-leaves-module .leaves-button-primary:hover { color:#fff; filter:brightness(.97); }
      #crm-leaves-module .leaves-notice { border:1px solid #fecaca; border-radius:.75rem; background:#fef2f2; color:#991b1b; padding:.75rem; font-size:.85rem; }
      .dark #crm-leaves-module .leave-summary-card,
      .dark #crm-leaves-module .leave-card,
      .dark #crm-leaves-module .leave-day-card,
      .dark #crm-leaves-module .leave-users-card,
      .dark #crm-leaves-module .leave-date,
      .dark #crm-leaves-module .leave-day-row,
      .dark #crm-leaves-module .leave-user-row,
      .dark #crm-leaves-module .leaves-modal { background:var(--color-surface-900,#0f172a); border-color:var(--color-surface-700,#334155); }
      .dark #crm-leaves-module .leave-weekdays,
      .dark #crm-leaves-module .leave-date.is-other { background:var(--color-surface-800,#1e293b); }
      .dark #crm-leaves-module .leave-date.has-absences:not(.is-selected) { background:linear-gradient(180deg,var(--color-surface-900,#0f172a) 0%,var(--color-surface-800,#1e293b) 100%); }
      @media (min-width:640px) {
        #crm-leaves-module .leaves-header { flex-direction:row; align-items:flex-start; justify-content:space-between; }
        #crm-leaves-module .leave-summary { grid-template-columns:repeat(3,minmax(0,1fr)); }
        #crm-leaves-module .leave-month-nav { flex-direction:row; align-items:center; justify-content:space-between; }
        #crm-leaves-module .leave-users-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
      }
      @media (min-width:1180px) {
        #crm-leaves-module .leave-workspace { grid-template-columns:minmax(0,1fr) minmax(22rem,28rem); align-items:start; }
        #crm-leaves-module .leave-side { display:grid; gap:1rem; }
      }
      @media (max-width:760px) {
        #crm-leaves-module .leave-date { --line-x-pad:.32rem; min-height:5.35rem; padding:.42rem .32rem; }
        #crm-leaves-module .leave-day-items { gap:.16rem; margin-top:.32rem; }
        #crm-leaves-module .leave-line,
        #crm-leaves-module .leave-lane-spacer { height:.16rem; box-shadow:none; }
        #crm-leaves-module .leaves-form-grid { grid-template-columns:1fr; }
      }
      #crm-leaves-module .leaves-page { gap:1.15rem; }
      #crm-leaves-module .leaves-header { display:none; }
      #crm-leaves-module .leave-workspace { display:grid; grid-template-columns:minmax(0,1fr); gap:1.2rem; align-items:start; }
      #crm-leaves-module .leave-main-column,
      #crm-leaves-module .leave-side { display:grid; min-width:0; gap:1.2rem; }
      #crm-leaves-module .leave-card,
      #crm-leaves-module .leave-day-card,
      #crm-leaves-module .leave-users-card,
      #crm-leaves-module .leave-summary-card {
        border-radius:1.05rem;
        box-shadow:0 16px 42px rgba(15,23,42,.06);
      }
      #crm-leaves-module .leave-calendar { overflow:hidden; }
      #crm-leaves-module .leave-planning-head {
        display:grid;
        grid-template-columns:3.1rem minmax(0,1fr) 3.1rem;
        align-items:center;
        gap:.75rem;
        padding:1.15rem 1.1rem .75rem;
      }
      #crm-leaves-module .leave-planning-title { min-width:0; text-align:center; }
      #crm-leaves-module .leave-month-title {
        color:var(--color-secondary-900,#0f172a);
        font-size:1.55rem;
        font-weight:900;
        line-height:1.1;
        text-align:center;
      }
      #crm-leaves-module .leave-month-subtitle {
        margin:.25rem 0 0;
        color:var(--color-secondary-500,#64748b);
        font-size:.86rem;
        font-weight:700;
        text-align:center;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
      }
      #crm-leaves-module .leave-nav-button {
        width:3.1rem;
        height:3.1rem;
        border-radius:.9rem;
        background:var(--color-surface-50,#f8fafc);
        font-size:1.35rem;
        box-shadow:0 8px 22px rgba(15,23,42,.05);
      }
      #crm-leaves-module .leave-tabs {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:.55rem;
        padding:0 1.1rem 1rem;
      }
      #crm-leaves-module .leave-tab {
        min-width:0;
        min-height:2.8rem;
        border:0;
        border-radius:.9rem;
        background:var(--color-surface-50,#f8fafc);
        color:var(--color-secondary-500,#64748b);
        font-size:.86rem;
        font-weight:900;
      }
      #crm-leaves-module .leave-tab.is-active {
        background:rgb(var(--theme-primary));
        color:#fff;
        box-shadow:0 12px 28px rgb(var(--theme-primary) / .23);
      }
      #crm-leaves-module .leave-weekdays {
        border-top:1px solid var(--color-surface-200,#e2e8f0);
        background:#fff;
      }
      #crm-leaves-module .leave-weekdays span {
        padding:.82rem .35rem;
        font-size:.74rem;
      }
      #crm-leaves-module .leave-calendar-grid { border-radius:0; overflow:hidden; }
      #crm-leaves-module .leave-date {
        --line-x-pad:.48rem;
        min-height:6.75rem;
        padding:.58rem .48rem .5rem;
      }
      #crm-leaves-module .leave-date.is-selected {
        background:#fff;
      }
      #crm-leaves-module .leave-date.is-selected .leave-number {
        background:rgb(var(--theme-primary));
        color:#fff;
      }
      #crm-leaves-module .leave-number {
        width:1.85rem;
        height:1.85rem;
        font-size:.85rem;
      }
      #crm-leaves-module .leave-day-items {
        display:grid;
        align-content:start;
        gap:.18rem;
        margin-top:.4rem;
      }
      #crm-leaves-module .leave-line,
      #crm-leaves-module .leave-lane-spacer { height:.2rem; }
      #crm-leaves-module .leave-legend {
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:.75rem 1rem;
        border-top:1px solid var(--color-surface-200,#e2e8f0);
        padding:1rem 1.15rem;
        color:var(--color-secondary-500,#64748b);
        font-size:.8rem;
        font-weight:850;
      }
      #crm-leaves-module .leave-legend-item {
        display:inline-flex;
        min-width:0;
        align-items:center;
        gap:.45rem;
      }
      #crm-leaves-module .leave-legend-item span:last-child {
        min-width:0;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
      }
      #crm-leaves-module .leave-legend-dot {
        width:.68rem;
        height:.68rem;
        flex:0 0 auto;
        border-radius:999px;
        background:var(--legend-color);
      }
      #crm-leaves-module .leave-legend-more {
        display:inline-flex;
        min-height:1.45rem;
        align-items:center;
        border-radius:999px;
        background:var(--color-surface-100,#f1f5f9);
        padding:.15rem .5rem;
        color:var(--color-secondary-500,#64748b);
      }
      #crm-leaves-module .leave-card-head,
      #crm-leaves-module .leave-users-head {
        padding:1.1rem 1.15rem;
      }
      #crm-leaves-module .leave-card-title,
      #crm-leaves-module .leave-users-title {
        font-size:1.05rem;
      }
      #crm-leaves-module .leave-add-day {
        min-height:2.75rem;
        border-radius:.85rem;
        padding:.65rem .9rem;
        box-shadow:0 12px 28px rgb(var(--theme-primary) / .2);
      }
      #crm-leaves-module .leave-day-list,
      #crm-leaves-module .leave-users-grid {
        padding:1.05rem 1.15rem;
      }
      #crm-leaves-module .leave-day-row,
      #crm-leaves-module .leave-user-row {
        border-radius:.85rem;
        padding:.7rem .75rem;
      }
      #crm-leaves-module .leave-summary {
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:.75rem;
      }
      #crm-leaves-module .leave-summary-card {
        min-width:0;
        padding:.95rem;
      }
      #crm-leaves-module .leave-summary-card strong { font-size:1.35rem; }
      .dark #crm-leaves-module .leave-tab,
      .dark #crm-leaves-module .leave-nav-button,
      .dark #crm-leaves-module .leave-legend-more {
        background:var(--color-surface-800,#1e293b);
      }
      .dark #crm-leaves-module .leave-weekdays,
      .dark #crm-leaves-module .leave-date.is-selected {
        background:var(--color-surface-900,#0f172a);
      }
      @media (min-width:1180px) {
        #crm-leaves-module .leave-workspace {
          grid-template-columns:minmax(0,1fr) minmax(21rem,25rem);
          gap:1.2rem;
        }
        #crm-leaves-module .leave-summary { grid-template-columns:1fr; }
        #crm-leaves-module .leave-date { min-height:7rem; }
      }
      @media (max-width:760px) {
        #crm-leaves-module .leaves-page { gap:1rem; }
        #crm-leaves-module .leave-main-column,
        #crm-leaves-module .leave-side { gap:1rem; }
        #crm-leaves-module .leave-planning-head {
          grid-template-columns:2.75rem minmax(0,1fr) 2.75rem;
          gap:.55rem;
          padding:1rem .9rem .65rem;
        }
        #crm-leaves-module .leave-nav-button {
          width:2.75rem;
          height:2.75rem;
          border-radius:.8rem;
        }
        #crm-leaves-module .leave-month-title { font-size:1.38rem; }
        #crm-leaves-module .leave-month-subtitle { font-size:.78rem; }
        #crm-leaves-module .leave-tabs {
          gap:.42rem;
          padding:0 .9rem .85rem;
        }
        #crm-leaves-module .leave-tab {
          min-height:2.55rem;
          font-size:.8rem;
        }
        #crm-leaves-module .leave-weekdays span {
          padding:.65rem .2rem;
          font-size:.68rem;
        }
        #crm-leaves-module .leave-date {
          --line-x-pad:.28rem;
          min-height:5.45rem;
          padding:.43rem .28rem;
        }
        #crm-leaves-module .leave-number {
          width:1.65rem;
          height:1.65rem;
          font-size:.78rem;
        }
        #crm-leaves-module .leave-day-items {
          gap:.13rem;
          margin-top:.32rem;
        }
        #crm-leaves-module .leave-line,
        #crm-leaves-module .leave-lane-spacer {
          height:.15rem;
          box-shadow:none;
        }
        #crm-leaves-module .leave-legend {
          gap:.55rem .8rem;
          padding:.85rem .95rem;
          font-size:.74rem;
        }
        #crm-leaves-module .leave-legend-item span:last-child { max-width:5.5rem; }
        #crm-leaves-module .leave-card-head,
        #crm-leaves-module .leave-users-head {
          padding:1rem;
        }
        #crm-leaves-module .leave-day-list,
        #crm-leaves-module .leave-users-grid {
          padding:1rem;
        }
        #crm-leaves-module .leave-summary {
          grid-template-columns:repeat(3,minmax(0,1fr));
          gap:.55rem;
        }
        #crm-leaves-module .leave-summary-card {
          padding:.75rem .65rem;
          border-radius:.85rem;
        }
        #crm-leaves-module .leave-summary-card strong { font-size:1.05rem; }
        #crm-leaves-module .leave-summary-card small { white-space:normal; }
      }
    `;
    style.textContent = root instanceof ShadowRoot
      ? css.replace(/\.dark #crm-leaves-module/g, ":host-context(.dark)").replace(/#crm-leaves-module/g, ":host")
      : css;
  }

  function renderLegend() {
    const visibleEmployees = employees().slice(0, 6);
    const remaining = Math.max(0, employees().length - visibleEmployees.length);
    const items = visibleEmployees.map((employee) => `
      <span class="leave-legend-item">
        <span class="leave-legend-dot" style="--legend-color:${esc(normalizeColor(employee.color, "#38bdf8"))}"></span>
        <span>${esc(employee.name)}</span>
      </span>
    `).join("");

    return `
      <div class="leave-legend" aria-label="Legende utilisateurs">
        ${items || '<span class="leave-legend-item"><span class="leave-legend-dot" style="--legend-color:#94a3b8"></span><span>Aucun utilisateur</span></span>'}
        ${remaining ? `<span class="leave-legend-more">+${remaining}</span>` : ""}
      </div>
    `;
  }

  function renderCalendar() {
    const days = calendarDays(state.month);
    const weekLanes = new Map();
    days.forEach((day) => {
      const key = weekKey(day);
      if (!weekLanes.has(key)) weekLanes.set(key, employeeIdsForWeek(day));
    });
    const employee = selectedEmployee();
    const subtitle = employee
      ? `Planning conges - ${employee.name}`
      : `Planning conges - ${activeSiteName()}`;
    return `
      <section class="leave-card leave-calendar" aria-label="Calendrier conges">
        <div class="leave-planning-head">
          <button type="button" class="leave-nav-button" data-prev aria-label="Mois precedent">&lt;</button>
          <div class="leave-planning-title">
            <h2 class="leave-month-title">${esc(monthLabel(state.month))}</h2>
            <p class="leave-month-subtitle">${esc(subtitle)}</p>
          </div>
          <button type="button" class="leave-nav-button" data-next aria-label="Mois suivant">&gt;</button>
        </div>
        <div class="leave-tabs" aria-label="Vues calendrier">
          <button type="button" class="leave-tab is-active">Mois</button>
          <button type="button" class="leave-tab" data-focus-selected>Jour</button>
          <button type="button" class="leave-tab" data-today>Aujourd'hui</button>
        </div>
        <div class="leave-weekdays">
          ${["LUN", "MAR", "MER", "JEU", "VEN", "SAM", "DIM"].map((day) => `<span>${day}</span>`).join("")}
        </div>
        <div class="leave-calendar-grid">
          ${days.map((day) => renderDay(day, weekLanes.get(weekKey(day)) || [])).join("")}
        </div>
        ${renderLegend()}
      </section>
    `;
  }

  function renderDay(day, weekEmployeeIds = []) {
    const date = formatDate(day);
    const leaves = leavesForDate(date);
    const leavesByEmployee = new Map();
    leaves.forEach((leave) => {
      const employeeId = Number(leave.employeeId);
      if (!leavesByEmployee.has(employeeId)) leavesByEmployee.set(employeeId, leave);
    });
    const otherMonth = day.getMonth() !== state.month.getMonth();
    const sunday = day.getDay() === 0;
    const today = date === formatDate(new Date());
    const selected = date === state.selectedDate;
    const classes = [
      "leave-date",
      otherMonth ? "is-other" : "",
      sunday ? "is-sunday" : "",
      today ? "is-today" : "",
      selected ? "is-selected" : "",
      leaves.length ? "has-absences" : "",
    ].filter(Boolean).join(" ");
    const visibleEmployeeIds = weekEmployeeIds.slice(0, 6);
    const leaveLines = visibleEmployeeIds.map((employeeId) => {
      const leave = leavesByEmployee.get(Number(employeeId));
      if (!leave) return '<span class="leave-lane-spacer" aria-hidden="true"></span>';

      const color = normalizeColor(leave.employeeColor || typeMeta(leave.type).color || "#38bdf8");
      const period = ["morning", "afternoon"].includes(leave.period) ? leave.period : "full";
      const status = String(leave.status || "approved").replace(/[^a-z0-9_-]/gi, "") || "approved";
      const label = `${leave.employeeName} - ${typeMeta(leave.type).label} - ${periodLabel(leave.period)}`;
      const weekDayIndex = (day.getDay() + 6) % 7;
      const continuationClasses = period === "full"
        ? [
            leave.startDate < date && weekDayIndex > 0 ? "is-continued-before" : "",
            leave.endDate > date && weekDayIndex < 6 ? "is-continued-after" : "",
          ].filter(Boolean).join(" ")
        : "";
      return `
        <span class="leave-line is-${esc(period)} is-${esc(status)} ${esc(continuationClasses)}" style="--line-color:${esc(color)};--line-border:${esc(color)}66;--line-shadow:${esc(color)}24" title="${esc(label)}" aria-hidden="true"></span>
      `;
    }).join("");
    const ariaLabel = leaves.length
      ? `${dateLabel(date)} - ${leaves.length} absent(s): ${leaves.map((leave) => leave.employeeName).join(", ")}`
      : dateLabel(date);

    return `
      <button type="button" class="${classes}" data-day data-date="${date}" aria-label="${esc(ariaLabel)}">
        <span class="leave-day-head">
          <span class="leave-number">${day.getDate()}</span>
        </span>
        <span class="leave-day-items">${leaveLines}</span>
      </button>
    `;
  }

  function renderSummary() {
    const usersCount = employees().length;
    const plannedDays = yearLeaves()
      .filter((leave) => ["planned", "pending"].includes(leave.status))
      .reduce((sum, leave) => sum + daysCount(leave), 0);
    const usedDays = yearLeaves()
      .filter((leave) => leave.status === "approved")
      .reduce((sum, leave) => sum + daysCount(leave), 0);

    return `
      <div class="leave-summary">
        <div class="leave-summary-card"><span>Utilisateurs</span><strong>${usersCount}</strong><small>${esc(activeSiteName())}</small></div>
        <div class="leave-summary-card"><span>Planifies</span><strong>${formatDaysCount(plannedDays)} j</strong><small>${monthLabel(state.month)}</small></div>
        <div class="leave-summary-card"><span>Poses</span><strong>${formatDaysCount(usedDays)} j</strong><small>Annee ${state.month.getFullYear()}</small></div>
      </div>
    `;
  }

  function userDays(employeeId) {
    const first = `${state.month.getFullYear()}-01-01`;
    const last = `${state.month.getFullYear()}-12-31`;
    return (state.data?.leaves || [])
      .filter((leave) => Number(leave.employeeId) === Number(employeeId))
      .filter((leave) => leave.status !== "refused" && leave.startDate <= last && leave.endDate >= first)
      .reduce((sum, leave) => sum + overlapDays(leave, first, last), 0);
  }

  function renderSelectedDay() {
    const items = selectedDateLeaves();
    const canManage = Boolean(state.data?.user?.canManage);
    return `
      <section class="leave-day-card">
        <div class="leave-card-head">
          <div>
            <h2 class="leave-card-title">Absents le ${esc(dateLabel(state.selectedDate))}</h2>
            <p class="leave-card-subtitle">${items.length ? `${items.length} utilisateur(s) absent(s)` : "Aucune absence sur cette date"}</p>
          </div>
          ${canManage ? '<button type="button" class="leave-add-day" data-add-day>+ Ajouter</button>' : ""}
        </div>
        <div class="leave-day-list">
          ${items.length ? items.map((leave) => {
            const color = normalizeColor(leave.employeeColor || typeMeta(leave.type).color || "#38bdf8");
            const range = leave.startDate === leave.endDate
              ? dateLabel(leave.startDate)
              : `${dateLabel(leave.startDate)} au ${dateLabel(leave.endDate)}`;
            return `
              <button type="button" class="leave-day-row" data-edit-leave="${leave.id}" style="--day-color:${esc(color)}">
                <span class="leave-day-dot"></span>
                <span>
                  <span class="leave-day-name">${esc(leave.employeeName)}</span>
                  <span class="leave-day-meta">${esc(typeMeta(leave.type).label)} - ${esc(periodLabel(leave.period))} - ${esc(range)}</span>
                </span>
                ${canManage ? '<span class="leave-day-edit">Modifier</span>' : '<span></span>'}
              </button>
            `;
          }).join("") : '<div class="leave-day-empty">Selectionne une autre date ou ajoute un conge sur ce jour.</div>'}
        </div>
      </section>
    `;
  }

  function renderUsers() {
    const selectedId = selectedEmployee()?.id || "all";
    return `
      <section class="leave-users-card">
        <div class="leave-users-head">
          <div>
            <h2 class="leave-users-title">Utilisateurs</h2>
            <p class="leave-users-site">Comptes CRM du site ${esc(activeSiteName())}</p>
          </div>
          <span class="leave-users-count">${employees().length}</span>
        </div>
        <div class="leave-users-grid">
          ${employees().map((employee) => {
            const days = userDays(employee.id);
            return `
              <button type="button" class="leave-user-row ${Number(selectedId) === Number(employee.id) ? "is-active" : ""}" data-user-id="${employee.id}" style="--user-color:${esc(normalizeColor(employee.color, "#38bdf8"))}">
                <span class="leave-user-dot"></span>
                <span class="leave-user-name">${esc(employee.name)}</span>
                <span class="leave-user-count">${formatDaysCount(days)}</span>
              </button>
            `;
          }).join("")}
          <button type="button" class="leave-user-row leave-all-users ${selectedId === "all" ? "is-active" : ""}" data-user-id="all" style="--user-color:#a4a9b2">
            <span class="leave-user-dot"></span>
            <span class="leave-user-name">Tous</span>
            <span class="leave-user-count">${formatDaysCount(yearLeaves().reduce((sum, leave) => sum + daysCount(leave), 0))}</span>
          </button>
        </div>
      </section>
    `;
  }

  function renderModal() {
    if (!state.modal) return "";
    const form = state.modal;
    return `
      <div class="leaves-modal-backdrop" data-modal-backdrop>
        <div class="leaves-modal">
          <div class="leaves-modal-head"><strong>${form.id ? "Modifier le conge" : "Ajouter un conge"}</strong><button type="button" class="leaves-button" data-close-modal>Fermer</button></div>
          <form class="leaves-form-grid" data-leave-form>
            <input type="hidden" name="id" value="${esc(form.id || "")}">
            <div class="leaves-field leaves-field-full"><label>Utilisateur</label><select name="employeeId" required>${employees().map((employee) => `<option value="${employee.id}" ${Number(form.employeeId) === Number(employee.id) ? "selected" : ""}>${esc(employee.name)}</option>`).join("")}</select></div>
            <div class="leaves-field"><label>Debut</label><input type="date" name="startDate" value="${esc(form.startDate)}" required></div>
            <div class="leaves-field"><label>Fin</label><input type="date" name="endDate" value="${esc(form.endDate)}" required></div>
            <div class="leaves-field"><label>Type</label><select name="type">${(state.data?.types || []).map((type) => `<option value="${esc(type.value)}" ${form.type === type.value ? "selected" : ""}>${esc(type.label)}</option>`).join("")}</select></div>
            <div class="leaves-field"><label>Journee</label><select name="period">${(state.data?.periods || []).map((period) => `<option value="${esc(period.value)}" ${form.period === period.value ? "selected" : ""}>${esc(period.label)}</option>`).join("")}</select></div>
            <div class="leaves-field"><label>Statut</label><select name="status"><option value="approved" ${form.status === "approved" ? "selected" : ""}>Valide</option><option value="planned" ${form.status === "planned" ? "selected" : ""}>Planifie</option><option value="pending" ${form.status === "pending" ? "selected" : ""}>A valider</option><option value="refused" ${form.status === "refused" ? "selected" : ""}>Refuse</option></select></div>
            <div class="leaves-field leaves-field-full"><label>Notes</label><textarea name="notes">${esc(form.notes || "")}</textarea></div>
            <div class="leaves-actions leaves-field-full"><button type="submit" class="leaves-button leaves-button-primary">Enregistrer</button>${form.id ? `<button type="button" class="leaves-button" data-delete="${form.id}">Supprimer</button>` : ""}</div>
          </form>
        </div>
      </div>
    `;
  }

  function render() {
    syncEmployeeFilter();
    root.innerHTML = `
      <div class="leaves-page">
        <div class="leave-workspace">
          <div class="leave-main-column">
            ${renderCalendar()}
            ${renderSelectedDay()}
          </div>
          <aside class="leave-side">
            ${renderUsers()}
            ${renderSummary()}
          </aside>
        </div>
        ${renderModal()}
      </div>
    `;
    styles();
    bind();
  }

  function bind() {
    root.querySelector("[data-prev]")?.addEventListener("click", () => {
      state.month = new Date(state.month.getFullYear(), state.month.getMonth() - 1, 1);
      state.selectedDate = formatDate(state.month);
      render();
    });
    root.querySelector("[data-next]")?.addEventListener("click", () => {
      state.month = new Date(state.month.getFullYear(), state.month.getMonth() + 1, 1);
      state.selectedDate = formatDate(state.month);
      render();
    });
    root.querySelector("[data-today]")?.addEventListener("click", () => {
      const today = new Date();
      state.month = new Date(today.getFullYear(), today.getMonth(), 1);
      state.selectedDate = formatDate(today);
      render();
    });
    root.querySelector("[data-focus-selected]")?.addEventListener("click", () => {
      root.querySelector(".leave-day-card")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
    root.querySelectorAll("[data-user-id]").forEach((button) => button.addEventListener("click", () => {
      state.filters.employeeId = button.dataset.userId || "all";
      render();
    }));
    root.querySelectorAll("[data-add-day]").forEach((button) => button.addEventListener("click", () => openModal({
      employeeId: selectedEmployee()?.id || employees()[0]?.id,
      startDate: state.selectedDate,
      endDate: state.selectedDate,
    })));
    root.querySelectorAll("[data-day]").forEach((button) => button.addEventListener("click", () => {
      state.selectedDate = button.dataset.date || state.selectedDate;
      render();
    }));
    root.querySelectorAll("[data-edit-leave]").forEach((button) => button.addEventListener("click", () => {
      if (!state.data?.user?.canManage) return;
      const leave = state.data.leaves.find((item) => Number(item.id) === Number(button.dataset.editLeave));
      if (leave) openModal(leave);
    }));
    root.querySelector("[data-close-modal]")?.addEventListener("click", () => { state.modal = null; render(); });
    root.querySelector("[data-modal-backdrop]")?.addEventListener("click", (event) => { if (event.target === event.currentTarget) { state.modal = null; render(); } });
    root.querySelector("[data-leave-form]")?.addEventListener("submit", async (event) => {
      event.preventDefault();
      const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
      payload.id = payload.id ? Number(payload.id) : undefined;
      payload.employeeId = Number(payload.employeeId);
      try {
        await request("save_leave", payload);
        state.modal = null;
        state.data = await request("bootstrap");
        syncEmployeeFilter();
        render();
      } catch (error) {
        alert(error instanceof Error ? error.message : "Enregistrement impossible");
      }
    });
    root.querySelector("[data-delete]")?.addEventListener("click", async (event) => {
      if (!confirm("Supprimer ce conge ?")) return;
      try {
        await request("delete_leave", { id: Number(event.currentTarget.dataset.delete) });
        state.modal = null;
        state.data = await request("bootstrap");
        syncEmployeeFilter();
        render();
      } catch (error) {
        alert(error instanceof Error ? error.message : "Suppression impossible");
      }
    });
  }

  function boot(rootNode) {
    if (!rootNode || mountedRoots.has(rootNode)) return;
    root = rootNode.shadowRoot || rootNode.attachShadow({ mode: "open" });
    mountedRoots.add(rootNode);
    load();
  }

  function isLeavesRoute() {
    return window.location.pathname.replace(/\/$/, "") === "/conges";
  }

  function tryBoot() {
    if (!isLeavesRoute()) return;
    boot(document.getElementById("crm-leaves-module"));
  }

  const originalPushState = history.pushState;
  history.pushState = function (...args) {
    const result = originalPushState.apply(this, args);
    window.dispatchEvent(new Event("crm:route-changed"));
    return result;
  };

  const originalReplaceState = history.replaceState;
  history.replaceState = function (...args) {
    const result = originalReplaceState.apply(this, args);
    window.dispatchEvent(new Event("crm:route-changed"));
    return result;
  };

  window.addEventListener("popstate", tryBoot);
  window.addEventListener("crm:route-changed", () => window.setTimeout(tryBoot, 0));
  window.addEventListener("crm:active-site-changed", () => {
    if (!isLeavesRoute() || !root) return;
    state.modal = null;
    state.filters.employeeId = "all";
    load();
  });
  document.addEventListener("DOMContentLoaded", tryBoot);

  const observer = new MutationObserver(() => tryBoot());
  observer.observe(document.documentElement, { childList: true, subtree: true });

  tryBoot();
})();
