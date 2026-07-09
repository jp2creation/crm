<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Location materiel - Martin Sols</title>
    <link rel="stylesheet" crossorigin href="{{ asset('assets/index-CVBlw941.css') }}">
    <style>
      :root {
        --theme-primary: 149 0 46;
        --theme-accent: 255 194 10;
      }

      body {
        margin: 0;
        background: var(--color-surface-50);
        color: var(--color-secondary-900);
        font-family: "DM Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      .module-shell {
        min-height: 100vh;
        padding: 1.25rem;
      }

      .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 1px;
        background: var(--color-surface-200);
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        overflow: hidden;
      }

      .calendar-cell {
        min-height: 126px;
        background: #fff;
        border: 0;
        padding: .625rem;
        text-align: left;
      }

      .calendar-cell:hover {
        background: rgb(var(--theme-primary) / .05);
      }

      .calendar-cell-muted {
        background: var(--color-surface-50);
        color: var(--color-secondary-400);
      }

      .rental-card {
        width: 100%;
        border: 1px solid var(--color-surface-200);
        border-radius: var(--radius);
        background: #fff;
        padding: .625rem;
        text-align: left;
        transition: border-color var(--duration-fast), background-color var(--duration-fast);
      }

      .rental-card:hover {
        border-color: rgb(var(--theme-primary) / .45);
        background: rgb(var(--theme-primary) / .04);
      }

      .modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, .5);
      }

      .modal-panel {
        width: min(760px, 100%);
        max-height: 90vh;
        overflow: auto;
        border-radius: 1rem;
        background: #fff;
        box-shadow: var(--shadow-xl);
      }

      .modal-panel-wide {
        width: min(980px, 100%);
      }

      @media (max-width: 840px) {
        .module-shell {
          padding: .875rem;
        }

        .calendar-grid {
          grid-template-columns: 1fr;
        }

        .calendar-heading {
          display: none;
        }

        .calendar-cell {
          min-height: auto;
        }
      }
    </style>
  </head>
  <body>
    <main id="equipment-rentals-root" class="module-shell"></main>

    <script>
      (function () {
        const API = '/api/equipment-rentals.php'
        const root = document.getElementById('equipment-rentals-root')
        const dateFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
        const dayFormatter = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: '2-digit', month: '2-digit' })
        const monthFormatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' })
        const today = new Date()

        const state = {
          data: null,
          error: '',
          loading: true,
          saving: false,
          notice: null,
          selectedUserId: null,
          selectedSiteId: null,
          view: 'month',
          focusDate: new Date(today.getFullYear(), today.getMonth(), today.getDate()),
          modal: null,
          viewAll: false,
          deletingId: null,
        }

        function html(value) {
          return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
        }

        function dateKey(date) {
          const month = String(date.getMonth() + 1).padStart(2, '0')
          const day = String(date.getDate()).padStart(2, '0')
          return `${date.getFullYear()}-${month}-${day}`
        }

        function dateTimeLocal(date) {
          const month = String(date.getMonth() + 1).padStart(2, '0')
          const day = String(date.getDate()).padStart(2, '0')
          const hours = String(date.getHours()).padStart(2, '0')
          const minutes = String(date.getMinutes()).padStart(2, '0')
          return `${date.getFullYear()}-${month}-${day}T${hours}:${minutes}`
        }

        function parseDate(value) {
          return new Date(String(value).replace(' ', 'T'))
        }

        function addDays(date, amount) {
          const next = new Date(date)
          next.setDate(next.getDate() + amount)
          return next
        }

        function startOfWeek(date) {
          const next = new Date(date)
          next.setHours(0, 0, 0, 0)
          next.setDate(next.getDate() - ((next.getDay() + 6) % 7))
          return next
        }

        function monthDays(date) {
          const first = new Date(date.getFullYear(), date.getMonth(), 1)
          const start = startOfWeek(first)
          return Array.from({ length: 42 }, (_, index) => addDays(start, index))
        }

        function overlaps(startA, endA, startB, endB) {
          return parseDate(startA) < parseDate(endB) && parseDate(endA) > parseDate(startB)
        }

        function actorHeaders() {
          return {}
        }

        async function api(action, body) {
          const response = await fetch(`${API}?action=${action}`, {
            method: body ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: body ? { 'Content-Type': 'application/json', ...actorHeaders() } : actorHeaders(),
            body: body ? JSON.stringify(body) : undefined,
          })
          const payload = await response.json()
          if (!response.ok || payload.ok === false) throw new Error(payload.error || 'Action refusee')
          return payload
        }

        async function loadData(preferredUserId) {
          state.loading = true
          render()
          try {
            const params = new URLSearchParams({ action: 'bootstrap' })
            const response = await fetch(`${API}?${params.toString()}`, { credentials: 'same-origin' })
            const payload = await response.json()
            if (!response.ok || payload.ok === false) throw new Error(payload.error || 'API indisponible')

            state.data = payload
            state.error = ''
            state.selectedUserId = payload.user.id
            const allowedSites = payload.user.siteIds || []
            const currentSiteAllowed = allowedSites.includes(state.selectedSiteId)
            state.selectedSiteId = currentSiteAllowed ? state.selectedSiteId : (allowedSites[0] || payload.sites[0]?.id || null)
          } catch (error) {
            state.error = error instanceof Error ? error.message : 'API location materiel indisponible'
          } finally {
            state.loading = false
            render()
          }
        }

        function activeUser() {
          if (!state.data) return null
          return state.data.users.find((user) => user.id === state.selectedUserId) || state.data.user
        }

        function activeSite() {
          if (!state.data) return null
          return state.data.sites.find((site) => site.id === state.selectedSiteId) || state.data.sites[0] || null
        }

        function activeRole() {
          const user = activeUser()
          if (!user || !state.data) return { blocked: true, permissions: [] }
          const module = state.data.modules.find((item) => item.slug === 'locations-materiel')
          const moduleAllowed = !module || user.moduleIds.includes(module.id)
          return {
            blocked: !moduleAllowed || !user.permissions.includes('equipment_rentals.view'),
            permissions: user.permissions,
          }
        }

        function hasPermission(name) {
          return activeRole().permissions.includes(name)
        }

        function canUpdate(rental) {
          const user = activeUser()
          return hasPermission('equipment_rentals.update_any') || (hasPermission('equipment_rentals.update_own') && rental.userId === user?.id)
        }

        function canDelete(rental) {
          const user = activeUser()
          return hasPermission('equipment_rentals.delete_any') || (hasPermission('equipment_rentals.delete_own') && rental.userId === user?.id)
        }

        function itemsForSite() {
          if (!state.data || !state.selectedSiteId) return []
          return state.data.equipmentItems.filter((item) => item.siteId === state.selectedSiteId && item.active)
        }

        function rentalsForSite() {
          if (!state.data || !state.selectedSiteId) return []
          return state.data.equipmentRentals.filter((rental) => rental.siteId === state.selectedSiteId)
        }

        function rentalsForDay(day) {
          const key = dateKey(day)
          return rentalsForSite()
            .filter((rental) => rental.startAt.slice(0, 10) <= key && rental.endAt.slice(0, 10) >= key)
            .sort((a, b) => a.startAt.localeCompare(b.startAt))
        }

        function itemById(id) {
          return state.data?.equipmentItems.find((item) => item.id === Number(id)) || null
        }

        function statusLabel(status) {
          return {
            reserved: 'Reservee',
            picked_up: 'Sortie',
            returned: 'Retournee',
            cancelled: 'Annulee',
          }[status] || status
        }

        function periodLabel(period) {
          return period === 'day' ? 'Journee' : 'Demi-journee'
        }

        function slotRange(day, slot) {
          const start = new Date(day)
          const end = new Date(day)
          if (slot === 'afternoon') {
            start.setHours(13, 0, 0, 0)
            end.setHours(17, 0, 0, 0)
            return [dateTimeLocal(start), dateTimeLocal(end)]
          }
          if (slot === 'full_day') {
            start.setHours(8, 0, 0, 0)
            end.setHours(17, 0, 0, 0)
            return [dateTimeLocal(start), dateTimeLocal(end)]
          }
          start.setHours(8, 0, 0, 0)
          end.setHours(12, 0, 0, 0)
          return [dateTimeLocal(start), dateTimeLocal(end)]
        }

        function firstAvailableItem(startAt, endAt, ignoreRentalId) {
          return itemsForSite().find((item) => !rentalsForSite().some((rental) => {
            if (rental.status === 'cancelled' || rental.id === ignoreRentalId) return false
            return rental.equipmentItemId === item.id && overlaps(startAt, endAt, rental.startAt, rental.endAt)
          }))
        }

        function upcomingRentals() {
          const nowKey = dateTimeLocal(new Date())
          return rentalsForSite()
            .filter((rental) => rental.status !== 'cancelled' && rental.endAt >= nowKey)
            .sort((a, b) => a.startAt.localeCompare(b.startAt))
        }

        function rentalCard(rental, compact) {
          const item = itemById(rental.equipmentItemId)
          const color = item?.color || '#95002e'
          const editAttr = canUpdate(rental) ? ` data-edit="${rental.id}"` : ''
          const deleteButton = canDelete(rental)
            ? `<button type="button" class="btn btn-ghost btn-xs" data-delete="${rental.id}" aria-label="Supprimer">Supprimer</button>`
            : ''
          return `
            <article class="rental-card"${editAttr} style="border-color:${html(color)}; background:${html(color)}12">
              <div class="flex items-center justify-between gap-2">
                <span class="text-ui-xs font-bold text-secondary-900">${html(rental.startAt.slice(11, 16))}-${html(rental.endAt.slice(11, 16))}</span>
                <span class="badge badge-primary">${html(statusLabel(rental.status))}</span>
              </div>
              <p class="mt-1 text-label text-secondary-900">${html(rental.title || 'Location materiel')}</p>
              ${compact ? '' : `<p class="mt-1 text-caption text-secondary-500">${html(item?.name || 'Materiel')} - ${html(rental.userName)}</p>`}
              ${compact ? '' : `<div class="mt-2 flex flex-wrap items-center gap-2">${deleteButton}</div>`}
            </article>
          `
        }

        function statCards() {
          const site = activeSite()
          const items = itemsForSite()
          const rentals = rentalsForSite()
          const currentMonth = dateKey(state.focusDate).slice(0, 7)
          const monthCount = rentals.filter((rental) => rental.startAt.startsWith(currentMonth)).length
          const [nowStart, nowEnd] = [dateTimeLocal(new Date()), dateTimeLocal(new Date(Date.now() + 4 * 60 * 60 * 1000))]
          const available = items.filter((item) => !rentals.some((rental) => rental.status !== 'cancelled' && rental.equipmentItemId === item.id && overlaps(nowStart, nowEnd, rental.startAt, rental.endAt)))
          const next = upcomingRentals()[0]
          const cards = [
            ['Site actif', site?.name || '-', 'Filtrage multi-sites'],
            ['Disponibles', `${available.length}/${items.length}`, 'Materiel du site'],
            ['Locations du mois', String(monthCount), monthFormatter.format(state.focusDate)],
            ['Prochaine location', next ? next.startAt.slice(11, 16) : 'Aucune', next ? dateFormatter.format(parseDate(next.startAt)) : 'Planning libre'],
          ]

          return `<section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            ${cards.map(([label, value, hint]) => `
              <div class="card rounded-xl p-4">
                <p class="text-caption text-secondary-500">${html(label)}</p>
                <p class="heading-4 mt-1 text-secondary-900">${html(value)}</p>
                <p class="text-ui-xs text-secondary-400">${html(hint)}</p>
              </div>
            `).join('')}
          </section>`
        }

        function upcomingSection() {
          const upcoming = upcomingRentals()
          return `
            <section class="card rounded-xl p-4">
              <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h2 class="heading-5 text-secondary-900">A venir</h2>
                  <p class="text-body-sm text-secondary-500">Locations du site actif</p>
                </div>
                <div class="flex items-center gap-3">
                  <span class="badge badge-primary">${upcoming.length}</span>
                  <button type="button" class="text-body-sm font-semibold text-theme-primary" data-view-all>Voir tout</button>
                </div>
              </div>
              <div class="flex gap-3 overflow-x-auto pb-1">
                ${upcoming.slice(0, 6).map((rental) => `<div class="min-w-[270px]">${rentalCard(rental, false)}</div>`).join('') || '<p class="w-full rounded-xl border border-dashed border-surface-200 p-4 text-center text-body-sm text-secondary-400">Aucune location a venir.</p>'}
              </div>
            </section>
          `
        }

        function calendarHeader() {
          const step = state.view === 'month' ? 30 : state.view === 'week' ? 7 : 1
          return `
            <div class="flex flex-col gap-3 border-b border-surface-200 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm btn-icon" data-move="${-step}" aria-label="Periode precedente">&lt;</button>
                <div>
                  <p class="heading-5 text-secondary-900">${html(state.view === 'month' ? monthFormatter.format(state.focusDate) : dayFormatter.format(state.focusDate))}</p>
                  <p class="text-caption text-secondary-500">Planning location materiel</p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm btn-icon" data-move="${step}" aria-label="Periode suivante">&gt;</button>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                ${['month', 'week', 'day'].map((view) => `<button type="button" class="btn btn-${state.view === view ? 'primary' : 'secondary'} btn-sm" data-view="${view}">${view === 'month' ? 'Mois' : view === 'week' ? 'Semaine' : 'Jour'}</button>`).join('')}
                <button type="button" class="btn btn-ghost btn-sm" data-today>Aujourd'hui</button>
              </div>
            </div>
          `
        }

        function renderMonth() {
          const month = state.focusDate.getMonth()
          return `<div class="calendar-grid">
            ${['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map((label) => `<div class="calendar-heading bg-surface-50 px-2 py-2 text-center text-ui-xs font-bold uppercase text-secondary-500">${label}</div>`).join('')}
            ${monthDays(state.focusDate).map((day) => {
              const rentals = rentalsForDay(day)
              const muted = day.getMonth() !== month ? ' calendar-cell-muted' : ''
              return `<button type="button" class="calendar-cell${muted}" data-day="${dateKey(day)}">
                <span class="mb-2 block text-ui-xs font-bold text-secondary-600">${day.getDate()}</span>
                <div class="space-y-1.5">
                  ${rentals.slice(0, 2).map((rental) => rentalCard(rental, true)).join('')}
                  ${rentals.length > 2 ? `<span class="text-ui-xs font-semibold text-theme-primary">+${rentals.length - 2} autres</span>` : ''}
                </div>
              </button>`
            }).join('')}
          </div>`
        }

        function renderWeek() {
          const start = startOfWeek(state.focusDate)
          return `<div class="grid grid-cols-1 gap-px rounded-b-xl bg-surface-200 md:grid-cols-7">
            ${Array.from({ length: 7 }, (_, index) => addDays(start, index)).map((day) => {
              const rentals = rentalsForDay(day)
              return `<div class="min-h-[220px] bg-white p-3">
                <button type="button" class="mb-3 text-left text-label text-secondary-900 hover:text-theme-primary" data-day="${dateKey(day)}">${html(dayFormatter.format(day))}</button>
                <div class="space-y-2">${rentals.length ? rentals.map((rental) => rentalCard(rental, false)).join('') : '<p class="rounded-lg border border-dashed border-surface-200 px-3 py-4 text-center text-caption text-secondary-400">Libre</p>'}</div>
              </div>`
            }).join('')}
          </div>`
        }

        function renderDay() {
          const slots = [
            ['morning', 'Matin', 'Demi-journee'],
            ['afternoon', 'Apres-midi', 'Demi-journee'],
            ['full_day', 'Journee', 'Journee'],
          ]

          return `<div class="space-y-3 rounded-b-xl bg-white p-4">
            ${slots.map(([slot, label, period]) => {
              const [startAt, endAt] = slotRange(state.focusDate, slot)
              const rentals = rentalsForSite().filter((rental) => rental.status !== 'cancelled' && overlaps(startAt, endAt, rental.startAt, rental.endAt))
              const available = firstAvailableItem(startAt, endAt)
              return `<div class="grid gap-3 rounded-xl border border-surface-200 p-3 md:grid-cols-[140px_1fr]">
                <div class="text-ui-xs font-bold text-secondary-500">
                  ${html(label)}<br>
                  <span class="font-medium text-secondary-400">${startAt.slice(11, 16)}-${endAt.slice(11, 16)}</span>
                </div>
                <div class="space-y-2">
                  ${rentals.length ? rentals.map((rental) => rentalCard(rental, false)).join('') : ''}
                  ${available && hasPermission('equipment_rentals.create') ? `<button type="button" class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-surface-200 px-3 py-2 text-caption font-semibold text-secondary-500 transition-colors hover:border-theme-primary hover:bg-theme-primary/5 hover:text-theme-primary" data-slot="${slot}">Creneau disponible - ${html(period)}</button>` : ''}
                  ${!rentals.length && !available ? '<p class="rounded-lg border border-dashed border-surface-200 px-3 py-2 text-caption text-secondary-400">Aucun materiel disponible</p>' : ''}
                </div>
              </div>`
            }).join('')}
          </div>`
        }

        function calendarSection() {
          return `<section class="card overflow-hidden rounded-xl">
            ${calendarHeader()}
            ${state.view === 'month' ? renderMonth() : state.view === 'week' ? renderWeek() : renderDay()}
          </section>`
        }

        function defaultForm(slot) {
          const selectedSlot = slot || 'morning'
          const [startAt, endAt] = slotRange(state.focusDate, selectedSlot)
          const item = firstAvailableItem(startAt, endAt) || itemsForSite()[0]
          return {
            equipmentItemId: item?.id ? String(item.id) : '',
            periodType: selectedSlot === 'full_day' ? 'day' : 'half_day',
            slot: selectedSlot,
            status: 'reserved',
            title: '',
            contactPhone: '',
            date: dateKey(state.focusDate),
            startAt,
            endAt,
            notes: '',
          }
        }

        function openCreate(slot) {
          state.notice = null
          state.modal = { mode: 'create', form: defaultForm(slot) }
          render()
        }

        function openEdit(id) {
          const rental = rentalsForSite().find((item) => item.id === Number(id))
          if (!rental || !canUpdate(rental)) return
          state.notice = null
          state.focusDate = parseDate(rental.startAt)
          state.modal = {
            mode: 'edit',
            id: rental.id,
            form: {
              equipmentItemId: String(rental.equipmentItemId),
              periodType: rental.periodType,
              slot: rental.slot,
              status: rental.status,
              title: rental.title || '',
              contactPhone: rental.contactPhone || '',
              date: rental.startAt.slice(0, 10),
              startAt: rental.startAt,
              endAt: rental.endAt,
              notes: rental.notes || '',
            },
          }
          render()
        }

        function syncSlotFields() {
          if (!state.modal) return
          const form = state.modal.form
          const day = parseDate(`${form.date}T00:00`)
          const [startAt, endAt] = slotRange(day, form.slot)
          form.periodType = form.slot === 'full_day' ? 'day' : 'half_day'
          form.startAt = startAt
          form.endAt = endAt
        }

        function modalHtml() {
          if (!state.modal) return ''
          const form = state.modal.form
          const isEdit = state.modal.mode === 'edit'
          return `<div class="modal-backdrop" data-close-modal>
            <div class="modal-panel" data-modal-panel>
              <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4">
                <div>
                  <h2 class="heading-5 text-secondary-900">${isEdit ? 'Modifier location' : 'Location rapide'}</h2>
                  <p class="mt-1 text-body-sm text-secondary-500">Materiel branche sur le planning CRM.</p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" data-close>Fermer</button>
              </div>
              <form class="space-y-4 p-6" data-rental-form>
                ${state.notice ? `<div class="rounded-xl border ${state.notice.type === 'success' ? 'border-success-200 bg-success-50 text-success-800' : 'border-danger-200 bg-danger-50 text-danger-800'} p-3 text-body-sm">${html(state.notice.message)}</div>` : ''}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <label class="form-field">
                    <span class="label label-required">Materiel</span>
                    <select class="select-native input" name="equipmentItemId" required>
                      ${itemsForSite().map((item) => `<option value="${item.id}" ${String(item.id) === form.equipmentItemId ? 'selected' : ''}>${html(item.name)}${item.inventoryCode ? ` - ${html(item.inventoryCode)}` : ''}</option>`).join('')}
                    </select>
                  </label>
                  <label class="form-field">
                    <span class="label">Statut</span>
                    <select class="select-native input" name="status">
                      ${['reserved', 'picked_up', 'returned', 'cancelled'].map((status) => `<option value="${status}" ${status === form.status ? 'selected' : ''}>${html(statusLabel(status))}</option>`).join('')}
                    </select>
                  </label>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                  <label class="form-field">
                    <span class="label">Date</span>
                    <input class="input" type="date" name="date" value="${html(form.date)}">
                  </label>
                  <label class="form-field">
                    <span class="label">Creneau</span>
                    <select class="select-native input" name="slot">
                      <option value="morning" ${form.slot === 'morning' ? 'selected' : ''}>Matin</option>
                      <option value="afternoon" ${form.slot === 'afternoon' ? 'selected' : ''}>Apres-midi</option>
                      <option value="full_day" ${form.slot === 'full_day' ? 'selected' : ''}>Journee</option>
                    </select>
                  </label>
                  <label class="form-field">
                    <span class="label">Duree</span>
                    <select class="select-native input" name="periodType">
                      <option value="half_day" ${form.periodType === 'half_day' ? 'selected' : ''}>Demi-journee</option>
                      <option value="day" ${form.periodType === 'day' ? 'selected' : ''}>Journee</option>
                    </select>
                  </label>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <label class="form-field">
                    <span class="label label-required">Debut</span>
                    <input class="input" type="datetime-local" name="startAt" value="${html(form.startAt)}" required>
                  </label>
                  <label class="form-field">
                    <span class="label label-required">Fin</span>
                    <input class="input" type="datetime-local" name="endAt" value="${html(form.endAt)}" required>
                  </label>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <label class="form-field">
                    <span class="label">Client / chantier</span>
                    <input class="input" name="title" value="${html(form.title)}" placeholder="Ex : Client Dupont">
                  </label>
                  <label class="form-field">
                    <span class="label">Telephone</span>
                    <input class="input" name="contactPhone" value="${html(form.contactPhone)}" placeholder="06 12 34 56 78">
                  </label>
                </div>
                <label class="form-field">
                  <span class="label">Notes</span>
                  <textarea class="textarea" name="notes" rows="3">${html(form.notes)}</textarea>
                </label>
                <button type="submit" class="btn btn-primary btn-md w-full" ${state.saving ? 'disabled' : ''}>${state.saving ? 'Enregistrement...' : isEdit ? 'Modifier la location' : 'Enregistrer la location'}</button>
              </form>
            </div>
          </div>`
        }

        function viewAllHtml() {
          if (!state.viewAll) return ''
          const upcoming = upcomingRentals()
          return `<div class="modal-backdrop" data-close-view-all>
            <div class="modal-panel modal-panel-wide" data-modal-panel>
              <div class="flex items-start justify-between gap-4 border-b border-surface-200 bg-white px-6 py-4">
                <div>
                  <h2 class="heading-5 text-secondary-900">A venir</h2>
                  <p class="mt-1 text-body-sm text-secondary-500">Toutes les locations du site actif</p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" data-close-view>Fermer</button>
              </div>
              <div class="grid max-h-[70vh] grid-cols-1 gap-3 overflow-y-auto p-6 md:grid-cols-2">
                ${upcoming.map((rental) => rentalCard(rental, false)).join('') || '<p class="rounded-xl border border-dashed border-surface-200 p-6 text-center text-body-sm text-secondary-400">Aucune location a venir.</p>'}
              </div>
            </div>
          </div>`
        }

        function render() {
          if (state.loading) {
            root.innerHTML = '<div class="card rounded-xl p-6 text-body-sm text-secondary-500">Chargement location materiel...</div>'
            return
          }

          if (state.error) {
            root.innerHTML = `<div class="card rounded-xl border border-danger-200 bg-danger-50 p-6 text-danger-800">${html(state.error)}</div>`
            return
          }

          const role = activeRole()
          const site = activeSite()
          const user = activeUser()

          root.innerHTML = `
            <div class="space-y-6">
              <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <h1 class="heading-2 text-secondary-900">Location materiel</h1>
                  <p class="mt-1 text-body-sm text-secondary-500">Locations demi-journee et journee par site.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <select class="select-native input input-sm w-auto" data-user-select>
                    ${state.data.users.map((item) => `<option value="${item.id}" ${item.id === user?.id ? 'selected' : ''}>${html(item.name)}</option>`).join('')}
                  </select>
                  <select class="select-native input input-sm w-auto" data-site-select>
                    ${state.data.sites.filter((item) => user?.siteIds.includes(item.id)).map((item) => `<option value="${item.id}" ${item.id === site?.id ? 'selected' : ''}>${html(item.name)}</option>`).join('')}
                  </select>
                  <button type="button" class="btn btn-primary btn-sm" data-new ${role.blocked || !hasPermission('equipment_rentals.create') ? 'disabled' : ''}>Nouvelle location</button>
                </div>
              </header>
              ${state.notice && !state.modal ? `<div class="rounded-xl border ${state.notice.type === 'success' ? 'border-success-200 bg-success-50 text-success-800' : 'border-danger-200 bg-danger-50 text-danger-800'} p-3 text-body-sm">${html(state.notice.message)}</div>` : ''}
              ${role.blocked ? `<section class="card rounded-xl border border-dashed border-danger-200 bg-danger-50 p-6 text-center">
                <h2 class="heading-4 text-secondary-900">Module location materiel masque</h2>
                <p class="mx-auto mt-2 max-w-xl text-body-sm text-secondary-600">Ce profil ne possede pas le droit equipment_rentals.view ou le module locations-materiel.</p>
              </section>` : `${statCards()}${upcomingSection()}${calendarSection()}`}
            </div>
            ${modalHtml()}
            ${viewAllHtml()}
          `

          bindEvents()
        }

        function bindEvents() {
          root.querySelector('[data-user-select]')?.addEventListener('change', (event) => {
            state.selectedUserId = Number(event.target.value)
            loadData(state.selectedUserId)
          })

          root.querySelector('[data-site-select]')?.addEventListener('change', (event) => {
            state.selectedSiteId = Number(event.target.value)
            render()
          })

          root.querySelector('[data-new]')?.addEventListener('click', () => openCreate())
          root.querySelector('[data-view-all]')?.addEventListener('click', () => { state.viewAll = true; render() })
          root.querySelector('[data-close-view]')?.addEventListener('click', () => { state.viewAll = false; render() })
          root.querySelector('[data-close]')?.addEventListener('click', () => { state.modal = null; state.notice = null; render() })
          root.querySelector('[data-today]')?.addEventListener('click', () => { state.focusDate = new Date(today.getFullYear(), today.getMonth(), today.getDate()); render() })

          root.querySelectorAll('[data-view]').forEach((button) => {
            button.addEventListener('click', () => {
              state.view = button.dataset.view
              render()
            })
          })

          root.querySelectorAll('[data-move]').forEach((button) => {
            button.addEventListener('click', () => {
              state.focusDate = addDays(state.focusDate, Number(button.dataset.move))
              render()
            })
          })

          root.querySelectorAll('[data-day]').forEach((button) => {
            button.addEventListener('click', () => {
              state.focusDate = parseDate(`${button.dataset.day}T00:00`)
              state.view = 'day'
              render()
            })
          })

          root.querySelectorAll('[data-slot]').forEach((button) => {
            button.addEventListener('click', () => openCreate(button.dataset.slot))
          })

          root.querySelectorAll('[data-edit]').forEach((card) => {
            card.addEventListener('click', (event) => {
              if (event.target.closest('[data-delete]')) return
              event.stopPropagation()
              openEdit(card.dataset.edit)
            })
          })

          root.querySelectorAll('[data-delete]').forEach((button) => {
            button.addEventListener('click', async (event) => {
              event.stopPropagation()
              await deleteRental(Number(button.dataset.delete))
            })
          })

          root.querySelector('[data-rental-form]')?.addEventListener('submit', saveRental)
          root.querySelector('[data-rental-form]')?.addEventListener('change', (event) => {
            if (!state.modal) return
            const field = event.target.name
            state.modal.form[field] = event.target.value
            if (field === 'slot' || field === 'date') {
              syncSlotFields()
              render()
            }
          })

          root.querySelectorAll('[data-close-modal], [data-close-view-all]').forEach((backdrop) => {
            backdrop.addEventListener('click', (event) => {
              if (event.target.dataset.closeModal !== undefined) {
                state.modal = null
                state.notice = null
                render()
              }
              if (event.target.dataset.closeViewAll !== undefined) {
                state.viewAll = false
                render()
              }
            })
          })

          root.querySelectorAll('[data-modal-panel]').forEach((panel) => {
            panel.addEventListener('click', (event) => event.stopPropagation())
          })
        }

        async function saveRental(event) {
          event.preventDefault()
          if (!state.modal) return
          const formData = new FormData(event.target)
          const payload = Object.fromEntries(formData.entries())
          if (state.modal.id) payload.id = state.modal.id

          state.saving = true
          state.notice = null
          render()

          try {
            const action = state.modal.mode === 'edit' ? 'update_rental' : 'create_rental'
            const result = await api(action, payload)
            const saved = result.equipmentRental
            const rentals = state.data.equipmentRentals
            const index = rentals.findIndex((item) => item.id === saved.id)
            if (index >= 0) rentals[index] = saved
            else rentals.push(saved)
            state.notice = { type: 'success', message: state.modal.mode === 'edit' ? 'Location modifiee.' : 'Location enregistree.' }
            state.focusDate = parseDate(saved.startAt)
            state.modal = null
          } catch (error) {
            state.notice = { type: 'error', message: error instanceof Error ? error.message : 'Impossible d enregistrer la location.' }
          } finally {
            state.saving = false
            render()
          }
        }

        async function deleteRental(id) {
          try {
            await api('delete_rental', { id })
            state.data.equipmentRentals = state.data.equipmentRentals.filter((item) => item.id !== id)
            state.notice = { type: 'success', message: 'Location supprimee.' }
          } catch (error) {
            state.notice = { type: 'error', message: error instanceof Error ? error.message : 'Impossible de supprimer la location.' }
          } finally {
            render()
          }
        }

        loadData()
      })()
    </script>
  </body>
</html>
