(() => {
  const api = "/api/conges.php";
  let root = null;
  const mountedRoots = new WeakSet();
  const state = {
    data: null,
    month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    view: "planning",
    filters: {
      employeeId: "all",
      type: "all",
      status: "active",
      query: "",
    },
    modal: null,
    employeePanel: false,
    employeeForm: null,
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
    return date.toLocaleDateString("fr-FR", { month: "long", year: "numeric" });
  }

  function daysInMonth(date) {
    const days = [];
    const current = new Date(date.getFullYear(), date.getMonth(), 1);
    while (current.getMonth() === date.getMonth()) {
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

  function visibleEmployees() {
    const query = state.filters.query.trim().toLowerCase();
    return employees().filter((employee) => {
      if (state.filters.employeeId !== "all" && Number(employee.id) !== Number(state.filters.employeeId)) return false;
      if (!query) return true;
      return employee.name.toLowerCase().includes(query) || filteredLeaves().some((leave) => Number(leave.employeeId) === Number(employee.id));
    });
  }

  function leaveFor(employeeId, date) {
    return filteredLeaves().find((leave) => {
      return Number(leave.employeeId) === Number(employeeId)
        && leave.status !== "refused"
        && leave.startDate <= date
        && leave.endDate >= date;
    });
  }

  function monthLeaves() {
    const first = formatDate(new Date(state.month.getFullYear(), state.month.getMonth(), 1));
    const last = formatDate(new Date(state.month.getFullYear(), state.month.getMonth() + 1, 0));
    return filteredLeaves().filter((leave) => leave.status !== "refused" && leave.startDate <= last && leave.endDate >= first);
  }

  function daysCount(leave) {
    const start = parseDate(leave.startDate);
    const end = parseDate(leave.endDate);
    const days = Math.max(1, Math.round((end - start) / 86400000) + 1);
    return days === 1 && leave.period !== "full" ? 0.5 : days;
  }

  function formatDaysCount(value) {
    return Number.isInteger(value) ? String(value) : String(value).replace(".", ",");
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

  function openModal(leave) {
    state.modal = {
      id: leave?.id || "",
      employeeId: leave?.employeeId || employees()[0]?.id || "",
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
    const options = { credentials: "same-origin" };
    if (payload) {
      options.method = "POST";
      options.headers = { "Content-Type": "application/json" };
      options.body = JSON.stringify(payload);
    }
    const response = await fetch(`${api}?action=${action}`, options);
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.ok === false) {
      throw new Error(data.error || "API conges indisponible");
    }
    return data;
  }

  async function load() {
    root.innerHTML = '<div class="leaves-panel">Chargement des conges...</div>';
    try {
      state.data = await request("bootstrap");
      render();
    } catch (error) {
      root.innerHTML = `<div class="leaves-notice">${esc(error instanceof Error ? error.message : "Chargement impossible")}</div>`;
    }
  }

  function styles() {
    if (document.getElementById("crm-conges-style")) return;
    const style = document.createElement("style");
    style.id = "crm-conges-style";
    style.textContent = `
      #crm-leaves-module { color: rgb(15 23 42); }
      #crm-leaves-module .leaves-topbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
      #crm-leaves-module .leaves-kicker { color:rgb(var(--theme-primary)); font-size:.72rem; font-weight:800; text-transform:uppercase; }
      #crm-leaves-module .leaves-title { margin:.25rem 0 0; font-size:clamp(1.35rem,2vw,2rem); font-weight:850; line-height:1.12; }
      #crm-leaves-module .leaves-subtitle { margin-top:.45rem; color:rgb(100 116 139); font-size:.92rem; }
      #crm-leaves-module .leaves-actions { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
      #crm-leaves-module .leaves-button { display:inline-flex; min-height:2.35rem; align-items:center; justify-content:center; border:1px solid rgb(226 232 240); border-radius:.45rem; background:#fff; color:rgb(51 65 85); padding:.55rem .75rem; font-size:.84rem; font-weight:800; line-height:1; text-decoration:none; }
      #crm-leaves-module .leaves-button:hover { color:rgb(var(--theme-primary)); border-color:rgb(var(--theme-primary) / .45); }
      #crm-leaves-module .leaves-button-primary { border-color:rgb(var(--theme-primary)); background:rgb(var(--theme-primary)); color:#fff; }
      #crm-leaves-module .leaves-button-primary:hover { color:#fff; filter:brightness(.97); }
      #crm-leaves-module .leaves-button-active { border-color:rgb(var(--theme-primary) / .45); background:rgb(var(--theme-primary) / .08); color:rgb(var(--theme-primary)); }
      #crm-leaves-module .leaves-toolbar { margin-bottom:1rem; }
      #crm-leaves-module .leaves-filter-grid { display:grid; grid-template-columns:minmax(13rem,1.3fr) repeat(3,minmax(9rem,1fr)); gap:.75rem; align-items:end; }
      #crm-leaves-module .leaves-view-tabs { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.85rem; }
      #crm-leaves-module .leaves-badge { display:inline-flex; align-items:center; border-radius:999px; background:rgb(241 245 249); color:rgb(71 85 105); padding:.18rem .5rem; font-size:.68rem; font-weight:850; }
      #crm-leaves-module .leaves-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
      #crm-leaves-module .leaves-stat, #crm-leaves-module .leaves-panel { border:1px solid rgb(226 232 240); border-radius:.55rem; background:#fff; padding:.9rem; }
      #crm-leaves-module .leaves-stat-label { color:rgb(100 116 139); font-size:.72rem; font-weight:800; text-transform:uppercase; }
      #crm-leaves-module .leaves-stat-value { margin-top:.3rem; font-size:1.35rem; font-weight:850; }
      #crm-leaves-module .leaves-board { overflow:auto; border:1px solid rgb(226 232 240); border-radius:.55rem; background:#fff; }
      #crm-leaves-module table { min-width:980px; width:100%; border-collapse:separate; border-spacing:0; table-layout:fixed; }
      #crm-leaves-module th, #crm-leaves-module td { height:3rem; border-right:1px solid rgb(226 232 240); border-bottom:1px solid rgb(226 232 240); text-align:center; }
      #crm-leaves-module th { position:sticky; top:0; z-index:2; background:rgb(248 250 252); color:rgb(71 85 105); font-size:.72rem; font-weight:850; }
      #crm-leaves-module .leaves-employee { position:sticky; left:0; z-index:3; width:10.5rem; min-width:10.5rem; background:#fff; text-align:left; padding:.65rem .75rem; font-size:.84rem; font-weight:850; }
      #crm-leaves-module .leaves-dot { display:inline-block; width:.7rem; height:.7rem; margin-right:.45rem; border-radius:999px; vertical-align:middle; }
      #crm-leaves-module .leaves-day { width:2.65rem; }
      #crm-leaves-module .leaves-day small { display:block; margin-top:.1rem; color:rgb(100 116 139); font-size:.66rem; }
      #crm-leaves-module .leaves-weekend { background:rgb(239 246 255); }
      #crm-leaves-module .leaves-cell { width:100%; height:100%; border:0; background:transparent; color:rgb(51 65 85); font-size:.7rem; font-weight:850; line-height:1.15; }
      #crm-leaves-module .leaves-cell:hover { background:rgb(var(--theme-primary) / .08); }
      #crm-leaves-module .leaves-lower { display:grid; grid-template-columns:minmax(0,1fr) minmax(18rem,24rem); gap:1rem; margin-top:1rem; }
      #crm-leaves-module .leaves-list { display:grid; gap:.5rem; margin-top:.7rem; }
      #crm-leaves-module .leaves-list-item { display:flex; align-items:center; justify-content:space-between; gap:.75rem; border:1px solid rgb(241 245 249); border-radius:.45rem; padding:.65rem; }
      #crm-leaves-module .leaves-list-title { font-size:.86rem; font-weight:850; }
      #crm-leaves-module .leaves-list-meta { margin-top:.15rem; color:rgb(100 116 139); font-size:.76rem; }
      #crm-leaves-module .leaves-table-panel { overflow:auto; border:1px solid rgb(226 232 240); border-radius:.55rem; background:#fff; }
      #crm-leaves-module .leaves-list-table { min-width:760px; width:100%; border-collapse:separate; border-spacing:0; table-layout:auto; }
      #crm-leaves-module .leaves-list-table th, #crm-leaves-module .leaves-list-table td { height:auto; border-right:0; padding:.7rem .8rem; text-align:left; }
      #crm-leaves-module .leaves-list-table th { position:sticky; top:0; z-index:1; background:rgb(248 250 252); color:rgb(71 85 105); font-size:.72rem; font-weight:850; text-transform:uppercase; }
      #crm-leaves-module .leaves-list-table td { border-bottom:1px solid rgb(241 245 249); color:rgb(51 65 85); font-size:.84rem; }
      #crm-leaves-module .leaves-year-table { min-width:920px; table-layout:auto; }
      #crm-leaves-module .leaves-year-table th, #crm-leaves-module .leaves-year-table td { text-align:center; }
      #crm-leaves-module .leaves-year-table th:first-child, #crm-leaves-module .leaves-year-table td:first-child { position:sticky; left:0; z-index:2; background:#fff; text-align:left; }
      #crm-leaves-module .leaves-year-table th:first-child { background:rgb(248 250 252); }
      #crm-leaves-module .leaves-empty { border:1px dashed rgb(203 213 225); border-radius:.55rem; background:rgb(248 250 252); color:rgb(100 116 139); padding:1rem; text-align:center; font-size:.86rem; font-weight:750; }
      #crm-leaves-module .leaves-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
      #crm-leaves-module .leaves-field { display:grid; gap:.32rem; }
      #crm-leaves-module .leaves-field-full { grid-column:1/-1; }
      #crm-leaves-module label { color:rgb(71 85 105); font-size:.76rem; font-weight:800; }
      #crm-leaves-module input, #crm-leaves-module select, #crm-leaves-module textarea { min-height:2.4rem; width:100%; border:1px solid rgb(203 213 225); border-radius:.45rem; background:#fff; color:rgb(15 23 42); padding:.5rem .65rem; font-size:.85rem; }
      #crm-leaves-module textarea { min-height:5.2rem; resize:vertical; }
      #crm-leaves-module .leaves-modal-backdrop { position:fixed; inset:0; z-index:80; display:flex; align-items:center; justify-content:center; background:rgb(15 23 42 / .48); padding:1rem; }
      #crm-leaves-module .leaves-modal { width:min(42rem,100%); max-height:calc(100vh - 2rem); overflow:auto; border-radius:.65rem; background:#fff; padding:1rem; box-shadow:0 24px 80px rgb(15 23 42 / .24); }
      #crm-leaves-module .leaves-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:.85rem; }
      #crm-leaves-module .leaves-notice { border:1px solid rgb(254 202 202); border-radius:.5rem; background:rgb(254 242 242); color:rgb(153 27 27); padding:.75rem; font-size:.85rem; }
      @media (max-width:980px) { #crm-leaves-module .leaves-stats, #crm-leaves-module .leaves-lower, #crm-leaves-module .leaves-filter-grid { grid-template-columns:1fr; } }
    `;
    document.head.appendChild(style);
  }

  function renderStats() {
    const leaves = monthLeaves();
    const totalDays = leaves.reduce((sum, leave) => sum + daysCount(leave), 0);
    const today = formatDate(new Date());
    const todayLeaves = (state.data?.leaves || []).filter((leave) => leave.status !== "refused" && leave.startDate <= today && leave.endDate >= today);
    const nextLeave = (state.data?.leaves || []).filter((leave) => leave.status !== "refused" && leave.endDate >= today).sort((a, b) => a.startDate.localeCompare(b.startDate))[0];
    return `
      <div class="leaves-stats">
        <div class="leaves-stat"><div class="leaves-stat-label">Salaries affiches</div><div class="leaves-stat-value">${visibleEmployees().length}</div></div>
        <div class="leaves-stat"><div class="leaves-stat-label">Periodes ce mois</div><div class="leaves-stat-value">${leaves.length}</div></div>
        <div class="leaves-stat"><div class="leaves-stat-label">Jours poses</div><div class="leaves-stat-value">${formatDaysCount(totalDays)}</div></div>
        <div class="leaves-stat"><div class="leaves-stat-label">Absents aujourd'hui</div><div class="leaves-stat-value">${todayLeaves.length}</div><div class="leaves-list-meta">${nextLeave ? `Prochain: ${esc(nextLeave.employeeName)} le ${esc(dateLabel(nextLeave.startDate))}` : "Aucun a venir"}</div></div>
      </div>
    `;
  }

  function renderFilters() {
    const filters = state.filters;
    return `
      <div class="leaves-panel leaves-toolbar">
        <div class="leaves-filter-grid">
          <div class="leaves-field"><label>Recherche</label><input data-filter="query" value="${esc(filters.query)}" placeholder="Nom, note, date..."></div>
          <div class="leaves-field"><label>Salarie</label><select data-filter="employeeId"><option value="all">Tous les salaries</option>${employees().map((employee) => `<option value="${employee.id}" ${String(filters.employeeId) === String(employee.id) ? "selected" : ""}>${esc(employee.name)}</option>`).join("")}</select></div>
          <div class="leaves-field"><label>Type</label><select data-filter="type"><option value="all">Tous les types</option>${(state.data?.types || []).map((type) => `<option value="${esc(type.value)}" ${filters.type === type.value ? "selected" : ""}>${esc(type.label)}</option>`).join("")}</select></div>
          <div class="leaves-field"><label>Statut</label><select data-filter="status"><option value="active" ${filters.status === "active" ? "selected" : ""}>Actifs sauf refuses</option><option value="all" ${filters.status === "all" ? "selected" : ""}>Tous les statuts</option><option value="approved" ${filters.status === "approved" ? "selected" : ""}>Valides</option><option value="planned" ${filters.status === "planned" ? "selected" : ""}>Planifies</option><option value="pending" ${filters.status === "pending" ? "selected" : ""}>A valider</option><option value="refused" ${filters.status === "refused" ? "selected" : ""}>Refuses</option></select></div>
        </div>
        <div class="leaves-view-tabs">
          <button type="button" class="leaves-button ${state.view === "planning" ? "leaves-button-active" : ""}" data-view="planning">Planning mensuel</button>
          <button type="button" class="leaves-button ${state.view === "list" ? "leaves-button-active" : ""}" data-view="list">Liste</button>
          <button type="button" class="leaves-button ${state.view === "annual" ? "leaves-button-active" : ""}" data-view="annual">Synthese annuelle</button>
          <button type="button" class="leaves-button" data-export>Exporter CSV</button>
        </div>
      </div>
    `;
  }

  function renderCalendar() {
    const days = daysInMonth(state.month);
    const rows = visibleEmployees();
    return `
      <div class="leaves-board">
        <table>
          <thead>
            <tr>
              <th class="leaves-employee">Salarie</th>
              ${days.map((day) => `<th class="leaves-day ${(day.getDay() === 0 || day.getDay() === 6) ? "leaves-weekend" : ""}">${day.getDate()}<small>${esc(day.toLocaleDateString("fr-FR", { weekday: "short" }).slice(0, 3))}</small></th>`).join("")}
            </tr>
          </thead>
          <tbody>
            ${rows.length ? rows.map((employee) => `
              <tr>
                <td class="leaves-employee"><span class="leaves-dot" style="background:${esc(normalizeColor(employee.color, "#64748b"))}"></span>${esc(employee.name)}</td>
                ${days.map((day) => renderCell(employee, day)).join("")}
              </tr>
            `).join("") : `<tr><td colspan="${days.length + 1}"><div class="leaves-empty">Aucun salarie ne correspond aux filtres.</div></td></tr>`}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderCell(employee, day) {
    const date = formatDate(day);
    const leave = leaveFor(employee.id, date);
    const meta = leave ? typeMeta(leave.type) : null;
    const label = leave ? (leave.period === "morning" ? "Matin" : leave.period === "afternoon" ? "A-midi" : meta.label) : "";
    const style = leave ? `background:${esc(normalizeColor(meta.color))}` : "";
    return `
      <td class="${(day.getDay() === 0 || day.getDay() === 6) ? "leaves-weekend" : ""}">
        <button type="button" class="leaves-cell" style="${style}" data-cell data-employee-id="${employee.id}" data-date="${date}" ${leave ? `data-leave-id="${leave.id}"` : ""}>${esc(label)}</button>
      </td>
    `;
  }

  function renderLeaveList() {
    const items = filteredLeaves().sort((a, b) => a.startDate.localeCompare(b.startDate) || a.employeeName.localeCompare(b.employeeName));
    if (!items.length) {
      return '<div class="leaves-empty">Aucun conge ne correspond aux filtres.</div>';
    }

    return `
      <div class="leaves-table-panel">
        <table class="leaves-list-table">
          <thead><tr><th>Salarie</th><th>Periode</th><th>Type</th><th>Journee</th><th>Statut</th><th>Notes</th><th></th></tr></thead>
          <tbody>
            ${items.map((leave) => {
              const meta = typeMeta(leave.type);
              return `<tr>
                <td><span class="leaves-dot" style="background:${esc(normalizeColor(leave.employeeColor, "#64748b"))}"></span>${esc(leave.employeeName)}</td>
                <td>${esc(dateLabel(leave.startDate))}${leave.startDate !== leave.endDate ? ` au ${esc(dateLabel(leave.endDate))}` : ""}<div class="leaves-list-meta">${formatDaysCount(daysCount(leave))} jour(s)</div></td>
                <td><span class="leaves-badge" style="background:${esc(normalizeColor(meta.color))}; color:rgb(15 23 42)">${esc(meta.label)}</span></td>
                <td>${esc(periodLabel(leave.period))}</td>
                <td>${esc(statusLabel(leave.status))}</td>
                <td>${esc(leave.notes || "")}</td>
                <td>${state.data?.user?.canManage ? `<button type="button" class="leaves-button" data-edit="${leave.id}">Modifier</button>` : ""}</td>
              </tr>`;
            }).join("")}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderAnnualSummary() {
    const year = state.month.getFullYear();
    const monthNames = Array.from({ length: 12 }, (_, index) => new Date(year, index, 1).toLocaleDateString("fr-FR", { month: "short" }));
    const leaves = filteredLeaves().filter((leave) => leave.status !== "refused" && leave.endDate >= `${year}-01-01` && leave.startDate <= `${year}-12-31`);
    const rows = visibleEmployees().map((employee) => {
      const months = Array.from({ length: 12 }, (_, index) => {
        const first = formatDate(new Date(year, index, 1));
        const last = formatDate(new Date(year, index + 1, 0));
        return leaves
          .filter((leave) => Number(leave.employeeId) === Number(employee.id))
          .reduce((sum, leave) => sum + overlapDays(leave, first, last), 0);
      });
      const total = months.reduce((sum, value) => sum + value, 0);
      return { employee, months, total };
    });

    return `
      <div class="leaves-table-panel">
        <table class="leaves-list-table leaves-year-table">
          <thead><tr><th>Salarie</th>${monthNames.map((name) => `<th>${esc(name)}</th>`).join("")}<th>Total</th></tr></thead>
          <tbody>
            ${rows.length ? rows.map((row) => `<tr>
              <td><span class="leaves-dot" style="background:${esc(normalizeColor(row.employee.color, "#64748b"))}"></span>${esc(row.employee.name)}</td>
              ${row.months.map((value) => `<td>${value ? formatDaysCount(value) : ""}</td>`).join("")}
              <td><strong>${formatDaysCount(row.total)}</strong></td>
            </tr>`).join("") : `<tr><td colspan="14"><div class="leaves-empty">Aucune donnee annuelle pour ces filtres.</div></td></tr>`}
          </tbody>
        </table>
      </div>
    `;
  }

  function renderMainView() {
    if (state.view === "list") return renderLeaveList();
    if (state.view === "annual") return renderAnnualSummary();
    return renderCalendar();
  }

  function renderUpcoming() {
    const today = formatDate(new Date());
    const items = filteredLeaves().filter((leave) => leave.endDate >= today && leave.status !== "refused").sort((a, b) => a.startDate.localeCompare(b.startDate)).slice(0, 8);
    return `
      <div class="leaves-panel">
        <strong>Prochains conges</strong>
        <div class="leaves-list">
          ${items.length ? items.map((leave) => {
            const meta = typeMeta(leave.type);
            return `<div class="leaves-list-item">
              <div><div class="leaves-list-title">${esc(leave.employeeName)}</div><div class="leaves-list-meta">${esc(meta.label)} - ${esc(dateLabel(leave.startDate))}${leave.startDate !== leave.endDate ? ` au ${esc(dateLabel(leave.endDate))}` : ""}</div></div>
              ${state.data?.user?.canManage ? `<button type="button" class="leaves-button" data-edit="${leave.id}">Modifier</button>` : ""}
            </div>`;
          }).join("") : '<div class="leaves-list-meta">Aucun conge a venir.</div>'}
        </div>
      </div>
    `;
  }

  function movePeriod(delta) {
    if (state.view === "annual") {
      state.month = new Date(state.month.getFullYear() + delta, state.month.getMonth(), 1);
      render();
      return;
    }

    state.month = new Date(state.month.getFullYear(), state.month.getMonth() + delta, 1);
    render();
  }

  function viewSubtitle() {
    if (state.view === "annual") return `Annee ${state.month.getFullYear()}`;
    if (state.view === "list") return `${filteredLeaves().length} periode(s) selon les filtres`;
    return monthLabel(state.month);
  }

  function csvValue(value) {
    const text = String(value ?? "");
    return /[\";,\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
  }

  function exportCsv() {
    const rows = filteredLeaves().sort((a, b) => a.startDate.localeCompare(b.startDate) || a.employeeName.localeCompare(b.employeeName));
    if (!rows.length) {
      alert("Aucun conge a exporter avec ces filtres.");
      return;
    }

    const header = ["Salarie", "Debut", "Fin", "Jours", "Type", "Journee", "Statut", "Notes", "Source"];
    const lines = [header.map(csvValue).join(";")].concat(rows.map((leave) => [
      leave.employeeName,
      leave.startDate,
      leave.endDate,
      formatDaysCount(daysCount(leave)),
      typeMeta(leave.type).label,
      periodLabel(leave.period),
      statusLabel(leave.status),
      leave.notes || "",
      leave.source || "crm",
    ].map(csvValue).join(";")));

    const blob = new Blob([`\ufeff${lines.join("\r\n")}`], { type: "text/csv;charset=utf-8" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `conges-palissy-${formatDate(new Date())}.csv`;
    document.body.appendChild(link);
    link.click();
    URL.revokeObjectURL(link.href);
    link.remove();
  }

  function renderEmployees() {
    if (!state.employeePanel) return "<div></div>";
    const form = state.employeeForm || { id: "", name: "", color: "#f59e0b", active: true, sortOrder: 100 };
    return `
      <div class="leaves-panel">
        <div class="leaves-modal-head"><strong>Salaries</strong><button type="button" class="leaves-button" data-close-employees>Fermer</button></div>
        <div class="leaves-list">
          ${(state.data?.employees || []).map((employee) => `<div class="leaves-list-item">
            <div><div class="leaves-list-title"><span class="leaves-dot" style="background:${esc(normalizeColor(employee.color, "#64748b"))}"></span>${esc(employee.name)}</div><div class="leaves-list-meta">${employee.active ? "Actif" : "Archive"} - ordre ${employee.sortOrder}</div></div>
            <button type="button" class="leaves-button" data-edit-employee="${employee.id}">Modifier</button>
          </div>`).join("")}
        </div>
        <form class="leaves-form-grid" data-employee-form style="margin-top:1rem">
          <input type="hidden" name="id" value="${esc(form.id || "")}">
          <div class="leaves-field"><label>Nom</label><input name="name" value="${esc(form.name || "")}" required></div>
          <div class="leaves-field"><label>Couleur</label><input name="color" type="color" value="${esc(form.color || "#f59e0b")}"></div>
          <div class="leaves-field"><label>Ordre</label><input name="sortOrder" type="number" min="0" max="999" value="${esc(form.sortOrder ?? 100)}"></div>
          <div class="leaves-field"><label>Etat</label><select name="active"><option value="1" ${form.active !== false ? "selected" : ""}>Actif</option><option value="0" ${form.active === false ? "selected" : ""}>Archive</option></select></div>
          <div class="leaves-actions leaves-field-full"><button type="submit" class="leaves-button leaves-button-primary">Enregistrer</button><button type="button" class="leaves-button" data-new-employee>Nouveau</button></div>
        </form>
      </div>
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
            <div class="leaves-field leaves-field-full"><label>Salarie</label><select name="employeeId" required>${employees().map((employee) => `<option value="${employee.id}" ${Number(form.employeeId) === Number(employee.id) ? "selected" : ""}>${esc(employee.name)}</option>`).join("")}</select></div>
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
    const canManage = Boolean(state.data?.user?.canManage);
    root.innerHTML = `
      <div class="leaves-topbar">
        <div>
          <div class="leaves-kicker">Planning interne</div>
          <h1 class="leaves-title">Conges Palissy</h1>
          <div class="leaves-subtitle">${esc(viewSubtitle())}</div>
        </div>
        <div class="leaves-actions">
          <button type="button" class="leaves-button" data-prev>Precedent</button>
          <button type="button" class="leaves-button" data-today>Aujourd'hui</button>
          <button type="button" class="leaves-button" data-next>Suivant</button>
          ${canManage ? '<button type="button" class="leaves-button leaves-button-primary" data-new>Ajouter</button><button type="button" class="leaves-button" data-employees>Salaries</button>' : ""}
        </div>
      </div>
      ${renderFilters()}
      ${renderStats()}
      ${renderMainView()}
      <div class="leaves-lower">${renderEmployees()}${renderUpcoming()}</div>
      ${renderModal()}
    `;
    bind();
  }

  function bind() {
    root.querySelector("[data-prev]")?.addEventListener("click", () => movePeriod(-1));
    root.querySelector("[data-next]")?.addEventListener("click", () => movePeriod(1));
    root.querySelector("[data-today]")?.addEventListener("click", () => { const now = new Date(); state.month = new Date(now.getFullYear(), now.getMonth(), 1); render(); });
    root.querySelectorAll("[data-view]").forEach((button) => button.addEventListener("click", () => { state.view = button.dataset.view || "planning"; render(); }));
    root.querySelectorAll("[data-filter]").forEach((field) => field.addEventListener("change", () => {
      state.filters[field.dataset.filter] = field.value;
      render();
    }));
    root.querySelector('[data-filter="query"]')?.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      state.filters.query = event.currentTarget.value;
      render();
    });
    root.querySelector("[data-export]")?.addEventListener("click", exportCsv);
    root.querySelector("[data-new]")?.addEventListener("click", () => openModal(null));
    root.querySelector("[data-employees]")?.addEventListener("click", () => { state.employeePanel = !state.employeePanel; state.employeeForm = null; render(); });
    root.querySelector("[data-close-employees]")?.addEventListener("click", () => { state.employeePanel = false; render(); });
    root.querySelectorAll("[data-cell]").forEach((button) => button.addEventListener("click", () => {
      if (!state.data?.user?.canManage) return;
      const leave = button.dataset.leaveId ? state.data.leaves.find((item) => Number(item.id) === Number(button.dataset.leaveId)) : null;
      openModal(leave || { employeeId: Number(button.dataset.employeeId), startDate: button.dataset.date, endDate: button.dataset.date });
    }));
    root.querySelectorAll("[data-edit]").forEach((button) => button.addEventListener("click", () => openModal(state.data.leaves.find((leave) => Number(leave.id) === Number(button.dataset.edit)))));
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
        render();
      } catch (error) {
        alert(error instanceof Error ? error.message : "Suppression impossible");
      }
    });
    root.querySelectorAll("[data-edit-employee]").forEach((button) => button.addEventListener("click", () => {
      state.employeeForm = { ...state.data.employees.find((employee) => Number(employee.id) === Number(button.dataset.editEmployee)) };
      render();
    }));
    root.querySelector("[data-new-employee]")?.addEventListener("click", () => { state.employeeForm = { id: "", name: "", color: "#f59e0b", active: true, sortOrder: 100 }; render(); });
    root.querySelector("[data-employee-form]")?.addEventListener("submit", async (event) => {
      event.preventDefault();
      const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
      payload.id = payload.id ? Number(payload.id) : undefined;
      payload.sortOrder = Number(payload.sortOrder || 100);
      payload.active = payload.active === "1";
      try {
        await request("save_employee", payload);
        state.employeeForm = null;
        state.data = await request("bootstrap");
        render();
      } catch (error) {
        alert(error instanceof Error ? error.message : "Enregistrement impossible");
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
  document.addEventListener("DOMContentLoaded", tryBoot);

  const observer = new MutationObserver(() => tryBoot());
  observer.observe(document.documentElement, { childList: true, subtree: true });

  tryBoot();
})();
