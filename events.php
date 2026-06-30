<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('events.title') ?> - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; font-size: 14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .stat-muted { color: #9ca3af; font-size: 12px; }
        .table th { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], select, textarea { font-size: 16px !important; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } if (!opts.cache) opts.cache = 'no-store'; return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<?php $navActive = 'events'; $navIcon = 'bi-calendar-event'; $navTitle = t('events.title'); require __DIR__ . '/navbar.php'; ?>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Event List -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-calendar-event"></i> <?= t('events.title') ?></strong>
                    <button class="btn btn-primary btn-sm" onclick="showForm()">
                        <i class="bi bi-plus-lg"></i> <?= t('events.add') ?>
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:150px"><?= t('events.date_range') ?></th>
                                <th><?= t('events.name') ?></th>
                                <th class="text-center" style="width:70px"><?= t('events.items_count') ?></th>
                                <th class="text-end" style="width:120px"><?= t('events.total_spent') ?></th>
                                <th style="width:110px"></th>
                            </tr>
                        </thead>
                        <tbody id="eventList">
                            <tr><td colspan="5" class="text-center text-muted py-4"><?= t('common.loading') ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header py-2"><strong><?= t('types.summary') ?></strong></div>
                <div class="card-body py-2" id="statsPanel">-</div>
            </div>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle"><?= t('events.add') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="eventForm">
                    <input type="hidden" id="eId">
                    <div class="mb-2">
                        <label class="form-label small"><?= t('events.name') ?> *</label>
                        <input type="text" class="form-control form-control-sm" id="eName" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small"><?= t('events.start_date') ?> *</label>
                            <input type="date" class="form-control form-control-sm" id="eDate" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('events.end_date') ?></label>
                            <input type="date" class="form-control form-control-sm" id="eEnd">
                        </div>
                    </div>
                    <div class="form-text small mb-2"><?= t('events.end_date_hint') ?></div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('events.description') ?></label>
                        <input type="text" class="form-control form-control-sm" id="eDesc">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveEvent()">
                    <i class="bi bi-check-lg"></i> <?= t('common.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('events.confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= t('events.delete_q') ?> <strong id="delName"></strong>?</p>
                <p class="text-warning small d-none" id="delWarn"></p>
                <input type="hidden" id="delId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash"></i> <?= t('common.delete') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js?v=<?= APP_VERSION ?>"></script>
<script src="assets/sync.js?v=<?= APP_VERSION ?>"></script>
<script>
const $ = id => document.getElementById(id);
const fmt = n => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
// Date label: single day, or "start – end" for multi-day events.
const dateRange = ev => ev.end_date && ev.end_date !== ev.event_date
    ? `${ev.event_date} – ${ev.end_date}` : ev.event_date;
let allEvents = [];

document.addEventListener('DOMContentLoaded', loadEvents);

async function loadEvents() {
    const res = await fetch('api.php?action=event_list').then(r => r.json());
    allEvents = res.events || [];
    renderTable();
    renderStats();
}

function renderTable() {
    if (allEvents.length === 0) {
        $('eventList').innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">${t('events.none')}</td></tr>`;
        return;
    }

    $('eventList').innerHTML = allEvents.map(ev => {
        const autoBtn = ev.unassigned_same_date > 0
            ? `<button class="btn btn-outline-secondary btn-sm px-1 py-0 ms-1" onclick="autoAssign(${ev.id}, ${ev.unassigned_same_date}, '${escJs(dateRange(ev))}')"
                title="${t('events.auto_assign')}"><i class="bi bi-link-45deg"></i> ${ev.unassigned_same_date}</button>`
            : '';
        return `<tr>
            <td class="text-nowrap">${dateRange(ev)}${autoBtn}</td>
            <td>
                <strong>${escHtml(ev.name)}</strong>
                ${ev.description ? `<div class="stat-muted">${escHtml(ev.description)}</div>` : ''}
            </td>
            <td class="text-center">
                ${ev.items_count > 0
                    ? `<a href="items.php?event_id=${ev.id}" class="text-decoration-none">${ev.items_count}</a>`
                    : `<span class="text-muted">0</span>`}
            </td>
            <td class="text-end">${ev.total_price > 0 ? '฿' + fmt(ev.total_price) : '-'}</td>
            <td class="text-end">
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editEvent(${ev.id})" title="${t('common.edit')}"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteEvent(${ev.id}, '${escJs(ev.name)}', ${ev.items_count})" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function renderStats() {
    const total = allEvents.length;
    const withItems = allEvents.filter(e => e.items_count > 0).length;
    const totalSpend = allEvents.reduce((s, e) => s + (e.total_price || 0), 0);
    $('statsPanel').innerHTML = `
        <div>${t('events.stat_total')}: <strong>${total}</strong></div>
        <div>${t('events.stat_with_items')}: <strong>${withItems}</strong></div>
        <div class="mt-2 pt-2 border-top">${t('events.stat_spend')}: <strong>฿${fmt(totalSpend)}</strong></div>
    `;
}

// --- CRUD ---
function showForm() {
    $('eId').value = '';
    $('eventForm').reset();
    $('formTitle').textContent = t('events.add');
    new bootstrap.Modal($('formModal')).show();
}

function editEvent(id) {
    const ev = allEvents.find(x => x.id == id);
    if (!ev) return;
    $('eId').value = ev.id;
    $('eName').value = ev.name;
    $('eDate').value = ev.event_date;
    $('eEnd').value = ev.end_date || '';
    $('eDesc').value = ev.description || '';
    $('formTitle').textContent = t('events.edit_prefix', { name: ev.name });
    new bootstrap.Modal($('formModal')).show();
}

async function saveEvent() {
    const form = $('eventForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const body = new FormData();
    body.append('action', 'event_save');
    const id = $('eId').value;
    if (id) body.append('id', id);
    body.append('name', $('eName').value);
    body.append('event_date', $('eDate').value);
    body.append('end_date', $('eEnd').value);
    body.append('description', $('eDesc').value);

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadEvents();
    notifyDataChanged('events');
}

function deleteEvent(id, name, itemsCount) {
    $('delId').value = id;
    $('delName').textContent = name;
    const warn = $('delWarn');
    if (itemsCount > 0) {
        warn.textContent = t('events.delete_warn', { n: itemsCount });
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'event_delete');
    body.append('id', $('delId').value);
    await fetch('api.php', { method: 'POST', body });
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadEvents();
    notifyDataChanged('events');
}

async function autoAssign(eventId, count, date) {
    if (!confirm(t('events.auto_assign_confirm', { n: count, date: date }))) return;
    const body = new FormData();
    body.append('action', 'event_auto_assign');
    body.append('event_id', eventId);
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    alert(t('events.auto_assign_done', { n: res.updated }));
    loadEvents();
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
