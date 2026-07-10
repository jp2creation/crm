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

  function normalizeColor(value, fallback = "#facc15") {
    return /^#[0-9a-f]{6}$/i.test(String(value || "")) ? value : fallback;
  }

  function typeMeta(type) {
    return (state.data?.types || []).find((item) => item.value === type) || { value: "conge", label: "Conge", color: "#facc15" };
  }

  function employees() {
    return (state.data?.employees || []).filter((employee) => employee.active);
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
    return activeLeaves().filter((leave) => leave.startDate <= date && leave.endDate >= date);
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
    root.innerHTML = '<div class="leave-phone leave-loading">Chargement...</div>';
    try {
      state.data = await request("bootstrap");
      syncEmployeeFilter();
      render();
    } catch (error) {
      root.innerHTML = `<div class="leave-phone"><div class="leaves-notice">${esc(error instanceof Error ? error.message : "Chargement impossible")}</div></div>`;
    }
  }

  function styles() {
    let style = document.getElementById("crm-conges-style");
    if (!style) {
      style = document.createElement("style");
      style.id = "crm-conges-style";
      document.head.appendChild(style);
    }

    style.textContent = `
      #crm-leaves-module { color:#15171c; }
      #crm-leaves-module .leave-phone { position:relative; width:min(100%, 52rem); margin:0 auto; border-radius:1.65rem; background:#fff; padding:1.15rem 1.15rem 1.4rem; box-shadow:0 18px 60px rgba(15,23,42,.08); }
      #crm-leaves-module .leave-loading { min-height:16rem; display:grid; place-items:center; color:#7b8089; font-weight:800; }
      #crm-leaves-module .leave-month-nav { display:grid; grid-template-columns:3rem 1fr 3rem; align-items:center; gap:.6rem; margin:.2rem 0 1.6rem; }
      #crm-leaves-module .leave-month-title { margin:0; text-align:center; font-size:clamp(1.55rem, 4vw, 2.25rem); font-weight:900; line-height:1.05; letter-spacing:0; color:#07080a; }
      #crm-leaves-module .leave-nav-button { display:grid; place-items:center; width:3rem; height:3rem; border:0; border-radius:999px; background:transparent; color:#777b82; font-size:2.45rem; line-height:1; font-weight:700; }
      #crm-leaves-module .leave-nav-button:hover { background:#f3f4f6; color:#20242a; }
      #crm-leaves-module .leave-weekdays { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); margin-bottom:.7rem; color:#7f848c; font-size:clamp(.8rem, 2.4vw, 1.05rem); font-weight:750; text-align:center; text-transform:uppercase; }
      #crm-leaves-module .leave-calendar-grid { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); gap:.35rem; margin-bottom:.9rem; }
      #crm-leaves-module .leave-date { position:relative; display:grid; grid-template-rows:1fr auto; align-items:center; justify-items:center; min-width:0; height:clamp(3.6rem, 8.5vw, 4.7rem); border:1px solid transparent; border-radius:.9rem; background:transparent; color:#1e2228; padding:.35rem .12rem .42rem; font-size:clamp(.98rem, 3vw, 1.38rem); font-weight:650; line-height:1; }
      #crm-leaves-module .leave-date.has-absences { background:#fffaf0; }
      #crm-leaves-module .leave-date.is-selected { border-color:#1f8fbd; background:#eef9ff; box-shadow:0 .45rem 1rem rgba(31,143,189,.14); }
      #crm-leaves-module .leave-date.is-today .leave-number { outline:2px solid rgba(31,143,189,.28); }
      #crm-leaves-module .leave-number { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:999px; }
      #crm-leaves-module .leave-markers { display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:.16rem; min-height:.62rem; max-width:100%; }
      #crm-leaves-module .leave-marker { width:.48rem; height:.48rem; border-radius:999px; background:var(--marker-color); box-shadow:0 0 0 2px #fff; }
      #crm-leaves-module .leave-more { min-width:1.15rem; border-radius:999px; background:#e9edf3; color:#5c6470; padding:.05rem .24rem; font-size:.56rem; font-weight:900; line-height:1.2; }
      #crm-leaves-module .leave-date.is-other { color:#a7abb2; }
      #crm-leaves-module .leave-date.is-sunday { color:#be2f3c; }
      #crm-leaves-module .leave-date.is-other.is-sunday { color:#c98a91; }
      #crm-leaves-module .leave-date:hover { background:#f4f7fb; }
      #crm-leaves-module .leave-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin:1.1rem 0 1.1rem; }
      #crm-leaves-module .leave-summary-card { min-height:5.5rem; border-radius:1.05rem; padding:.72rem .55rem; color:#fff; text-align:center; box-shadow:0 .7rem 1.3rem rgba(15,23,42,.10); }
      #crm-leaves-module .leave-summary-card span { display:block; font-size:clamp(.78rem, 2.2vw, 1rem); font-weight:800; opacity:.82; }
      #crm-leaves-module .leave-summary-card strong { display:block; margin:.15rem 0; font-size:clamp(1.4rem, 4vw, 2rem); font-weight:950; line-height:1; letter-spacing:0; }
      #crm-leaves-module .leave-summary-card small { display:block; font-size:clamp(.64rem, 1.8vw, .82rem); font-weight:800; opacity:.78; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
      #crm-leaves-module .leave-summary-card.is-blue { background:linear-gradient(135deg,#49bde9,#2074b4); }
      #crm-leaves-module .leave-summary-card.is-green { background:linear-gradient(135deg,#6dd0b5,#42b98e); }
      #crm-leaves-module .leave-summary-card.is-gray { background:linear-gradient(135deg,#bfc0c4,#8f9096); }
      #crm-leaves-module .leave-day-card, #crm-leaves-module .leave-users-card { border:1px solid #e3e3e5; border-radius:1.35rem; background:#fff; padding:1.05rem 1.15rem 1.1rem; box-shadow:0 .25rem .7rem rgba(15,23,42,.14); }
      #crm-leaves-module .leave-day-card { margin-bottom:1rem; }
      #crm-leaves-module .leave-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.85rem; }
      #crm-leaves-module .leave-card-title { margin:0; color:#191b20; font-size:clamp(1.15rem, 3.2vw, 1.55rem); font-weight:850; line-height:1.1; letter-spacing:0; }
      #crm-leaves-module .leave-card-subtitle { margin:.28rem 0 0; color:#7d8189; font-size:.9rem; font-weight:650; }
      #crm-leaves-module .leave-add-day { flex:0 0 auto; border:0; border-radius:999px; background:#33b889; color:#fff; padding:.58rem .9rem; font-size:.82rem; font-weight:900; box-shadow:0 .45rem 1rem rgba(20,124,91,.22); }
      #crm-leaves-module .leave-day-list { display:grid; gap:.52rem; }
      #crm-leaves-module .leave-day-row { display:grid; grid-template-columns:1rem minmax(0,1fr) auto; align-items:center; gap:.55rem; min-height:2.35rem; border:0; border-radius:.7rem; background:#f8fafc; padding:.45rem .55rem; color:#22262d; text-align:left; }
      #crm-leaves-module .leave-day-row:hover { background:#eef6ff; }
      #crm-leaves-module .leave-day-dot { width:.72rem; height:.72rem; border-radius:999px; background:var(--day-color); }
      #crm-leaves-module .leave-day-name { display:block; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:.94rem; font-weight:850; }
      #crm-leaves-module .leave-day-meta { display:block; margin-top:.12rem; color:#7b818c; font-size:.74rem; font-weight:750; }
      #crm-leaves-module .leave-day-edit { color:#1f7bad; font-size:.76rem; font-weight:900; }
      #crm-leaves-module .leave-day-empty { border:1px dashed #cbd5e1; border-radius:.9rem; padding:.9rem; color:#7b818c; font-size:.9rem; font-weight:700; text-align:center; }
      #crm-leaves-module .leave-users-head { display:grid; grid-template-columns:1.2rem 1fr; column-gap:.65rem; align-items:start; margin-bottom:.9rem; }
      #crm-leaves-module .leave-users-icon { display:flex; align-items:flex-end; gap:.16rem; width:1.2rem; height:1.75rem; padding-top:.32rem; }
      #crm-leaves-module .leave-users-icon span { display:block; width:.24rem; border-radius:.18rem; background:#25282f; }
      #crm-leaves-module .leave-users-icon span:nth-child(1) { height:.95rem; }
      #crm-leaves-module .leave-users-icon span:nth-child(2) { height:1.35rem; }
      #crm-leaves-module .leave-users-icon span:nth-child(3) { height:.72rem; }
      #crm-leaves-module .leave-users-title { margin:0; color:#191b20; font-size:clamp(1.45rem, 4vw, 2rem); font-weight:500; line-height:1.1; letter-spacing:0; }
      #crm-leaves-module .leave-users-site { grid-column:2; margin:.35rem 0 0; color:#7d8189; font-size:clamp(.86rem, 2.5vw, 1.05rem); font-weight:500; }
      #crm-leaves-module .leave-users-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.15rem 1.35rem; }
      #crm-leaves-module .leave-user-row { display:grid; grid-template-columns:1rem minmax(0,1fr) auto; align-items:center; gap:.45rem; min-height:2.15rem; border:0; border-radius:.55rem; background:transparent; padding:.15rem .25rem; color:#22262d; text-align:left; }
      #crm-leaves-module .leave-user-row:hover, #crm-leaves-module .leave-user-row.is-active { background:#f5f7fa; }
      #crm-leaves-module .leave-user-dot { width:.78rem; height:.78rem; border-radius:999px; background:var(--user-color); }
      #crm-leaves-module .leave-user-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:clamp(.86rem, 2.7vw, 1.05rem); font-weight:600; }
      #crm-leaves-module .leave-user-count { color:#8b8f97; font-size:clamp(.82rem, 2.4vw, 1rem); font-weight:700; }
      #crm-leaves-module .leave-all-users { margin-top:.4rem; color:#8d929a; }
      #crm-leaves-module .leaves-modal-backdrop { position:fixed; inset:0; z-index:80; display:flex; align-items:center; justify-content:center; background:rgba(15,23,42,.48); padding:1rem; }
      #crm-leaves-module .leaves-modal { width:min(42rem,100%); max-height:calc(100vh - 2rem); overflow:auto; border-radius:1rem; background:#fff; padding:1rem; box-shadow:0 24px 80px rgba(15,23,42,.24); }
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
      @media (max-width:640px) {
        #crm-leaves-module .leave-phone { width:100%; border-radius:1.1rem; padding:.95rem .75rem 1.05rem; box-shadow:none; }
        #crm-leaves-module .leave-summary { gap:.55rem; }
        #crm-leaves-module .leave-day-card, #crm-leaves-module .leave-users-card { padding:.9rem .8rem 1rem; }
        #crm-leaves-module .leave-users-grid { gap:.1rem .65rem; }
        #crm-leaves-module .leaves-form-grid { grid-template-columns:1fr; }
      }
    `;
  }

  function renderCalendar() {
    const days = calendarDays(state.month);
    return `
      <section class="leave-calendar" aria-label="Calendrier conges">
        <div class="leave-month-nav">
          <button type="button" class="leave-nav-button" data-prev aria-label="Mois precedent">&#8249;</button>
          <h1 class="leave-month-title">${esc(monthLabel(state.month))}</h1>
          <button type="button" class="leave-nav-button" data-next aria-label="Mois suivant">&#8250;</button>
        </div>
        <div class="leave-weekdays">
          ${["LUN", "MAR", "MER", "JEU", "VEN", "SAM", "DIM"].map((day) => `<span>${day}</span>`).join("")}
        </div>
        <div class="leave-calendar-grid">
          ${days.map((day) => renderDay(day)).join("")}
        </div>
      </section>
    `;
  }

  function renderDay(day) {
    const date = formatDate(day);
    const leaves = leavesForDate(date);
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
    const markers = leaves.slice(0, 4).map((leave) => {
      const color = normalizeColor(leave.employeeColor || typeMeta(leave.type).color || "#38bdf8");
      return `<span class="leave-marker" style="--marker-color:${esc(color)}"></span>`;
    }).join("");
    const more = leaves.length > 4 ? `<span class="leave-more">+${leaves.length - 4}</span>` : "";

    return `
      <button type="button" class="${classes}" data-day data-date="${date}" aria-label="${esc(dateLabel(date))}">
        <span class="leave-number">${day.getDate()}</span>
        <span class="leave-markers">${markers}${more}</span>
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
        <div class="leave-summary-card is-blue"><span>Utilisateurs</span><strong>${usersCount}</strong><small>${esc(activeSiteName())}</small></div>
        <div class="leave-summary-card is-green"><span>Planifies</span><strong>${formatDaysCount(plannedDays)} j</strong><small>${monthLabel(state.month)}</small></div>
        <div class="leave-summary-card is-gray"><span>Poses</span><strong>${formatDaysCount(usedDays)} j</strong><small>Annee ${state.month.getFullYear()}</small></div>
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
          <span class="leave-users-icon"><span></span><span></span><span></span></span>
          <h2 class="leave-users-title">Utilisateurs</h2>
          <p class="leave-users-site">Comptes CRM du site ${esc(activeSiteName())}</p>
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
    styles();
    syncEmployeeFilter();
    root.innerHTML = `
      <div class="leave-phone">
        ${renderCalendar()}
        ${renderSummary()}
        ${renderSelectedDay()}
        ${renderUsers()}
        ${renderModal()}
      </div>
    `;
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
    root.querySelectorAll("[data-user-id]").forEach((button) => button.addEventListener("click", () => {
      state.filters.employeeId = button.dataset.userId || "all";
      render();
    }));
    root.querySelector("[data-add-day]")?.addEventListener("click", () => openModal({
      employeeId: selectedEmployee()?.id || employees()[0]?.id,
      startDate: state.selectedDate,
      endDate: state.selectedDate,
    }));
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
    root = rootNode;
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
