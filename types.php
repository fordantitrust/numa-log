<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('types.title') ?> - Numa Log</title>
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
        /* Mobile */
        .table-responsive, .card-body.p-0 { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="password"],
            select, textarea { font-size: 16px !important; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } if (!opts.cache) opts.cache = 'no-store'; return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<?php $navActive = 'types'; $navIcon = 'bi-tags'; $navTitle = t('types.title'); require __DIR__ . '/navbar.php'; ?>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Type List -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-tags"></i> <?= t('types.categories') ?></strong>
                    <button class="btn btn-primary btn-sm" onclick="showForm()">
                        <i class="bi bi-plus-lg"></i> <?= t('common.add') ?>
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th><?= t('types.name') ?></th>
                                <th><?= t('types.description') ?></th>
                                <th class="text-center" style="width:70px"><?= t('types.rows') ?></th>
                                <th class="text-center" style="width:70px"><?= t('common.qty') ?></th>
                                <th class="text-end" style="width:120px"><?= t('types.total_spent') ?></th>
                                <th class="text-center" style="width:60px"><?= t('types.order') ?></th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="typeList">
                            <tr><td colspan="8" class="text-center text-muted py-4"><?= t('common.loading') ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong><?= t('types.summary') ?></strong></div>
                <div class="card-body py-2" id="statsPanel">-</div>
            </div>

            <div class="card">
                <div class="card-header py-2"><strong><?= t('types.unmapped_title') ?></strong></div>
                <div class="card-body py-2" id="unmappedPanel">
                    <div class="text-muted"><?= t('common.loading') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Members by Type Report -->
    <div class="row g-3 mt-0">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-people"></i> <?= t('types.members_by_type') ?></strong>
                    <span class="badge bg-secondary" id="reportBadge"><?= t('common.loading') ?></span>
                </div>
                <div class="card-body p-2" id="membersReport">
                    <div class="text-center text-muted py-3"><?= t('common.loading') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle"><?= t('types.add') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="typeForm">
                    <input type="hidden" id="tId">
                    <div class="mb-2">
                        <label class="form-label small"><?= t('types.name') ?></label>
                        <input type="text" class="form-control form-control-sm" id="tName" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('types.description') ?></label>
                        <input type="text" class="form-control form-control-sm" id="tDesc">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('types.sort_order') ?></label>
                        <input type="number" class="form-control form-control-sm" id="tSort" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveType()">
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
                <h5 class="modal-title"><?= t('types.confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= t('types.delete_q') ?> <strong id="delName"></strong>?</p>
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
let allTypes = [];

document.addEventListener('DOMContentLoaded', () => {
    loadTypes();
    loadMembersReport();
});

async function loadTypes() {
    const res = await fetch('api.php?action=type_list').then(r => r.json());
    allTypes = res.types;
    renderTable();
    renderStats();
    renderUnmapped(res.unmapped);
}

async function loadMembersReport() {
    const res = await fetch('api.php?action=type_members_report').then(r => r.json());
    renderMembersReport(res.by_type);
}

function renderMembersReport(byType) {
    const types = Object.keys(byType);
    $('reportBadge').textContent = t('types.types_count', { n: types.length });

    if (types.length === 0) {
        $('membersReport').innerHTML = `<div class="text-muted text-center py-3">${t('common.no_data')}</div>`;
        return;
    }

    const html = `<div class="accordion accordion-flush" id="typeAccordion">` +
        types.map((type, i) => {
            const members = byType[type];
            // group members by company then group
            const rows = members.map(m => `
                <tr>
                    <td>${escHtml(m.member)}</td>
                    <td class="text-muted">${m.group ? escHtml(m.group) : '-'}</td>
                    <td class="text-muted">${m.company ? escHtml(m.company) : '-'}</td>
                    <td class="text-center">${m.items_count}</td>
                    <td class="text-center">${m.total_qty}</td>
                    <td class="text-end">${m.total_price > 0 ? '฿' + fmt(m.total_price) : '-'}</td>
                </tr>`).join('');
            return `
            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2 px-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#typeCollapse${i}" style="font-size:14px">
                        <strong>${escHtml(type)}</strong>
                        <span class="badge bg-secondary ms-2" style="font-weight:normal;font-size:11px">${t('types.members_count', { n: members.length })}</span>
                    </button>
                </h2>
                <div id="typeCollapse${i}" class="accordion-collapse collapse">
                    <div class="accordion-body p-0">
                        <table class="table table-sm table-hover mb-0" style="font-size:13px">
                            <thead>
                                <tr>
                                    <th>${t('common.member')}</th>
                                    <th>${t('report.group_unit')}</th>
                                    <th>${t('common.company')}</th>
                                    <th class="text-center" style="width:60px">${t('types.rows')}</th>
                                    <th class="text-center" style="width:60px">${t('common.qty')}</th>
                                    <th class="text-end" style="width:110px">${t('common.total')}</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </div>
            </div>`;
        }).join('') + `</div>`;

    $('membersReport').innerHTML = html;
}

function renderTable() {
    if (allTypes.length === 0) {
        $('typeList').innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">${t('types.none')}</td></tr>`;
        return;
    }

    $('typeList').innerHTML = allTypes.map((ty, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td><strong>${escHtml(ty.name)}</strong></td>
            <td class="stat-muted">${escHtml(ty.description || '')}</td>
            <td class="text-center">${ty.items_count}</td>
            <td class="text-center">${ty.total_qty}</td>
            <td class="text-end">${ty.total_price > 0 ? '฿' + fmt(ty.total_price) : '-'}</td>
            <td class="text-center">${ty.sort_order}</td>
            <td>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editType(${ty.id})" title="${t('common.edit')}"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteType(${ty.id}, '${escJs(ty.name)}')" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderStats() {
    const total = allTypes.length;
    const withItems = allTypes.filter(t => t.items_count > 0).length;
    const totalQty = allTypes.reduce((s, t) => s + (t.total_qty || 0), 0);
    const totalSpend = allTypes.reduce((s, t) => s + (t.total_price || 0), 0);
    $('statsPanel').innerHTML = `
        <div>${t('types.stat_total')} <strong>${total}</strong></div>
        <div>${t('types.stat_with')} <strong>${withItems}</strong></div>
        <div>${t('types.stat_qty')} <strong>${fmt(totalQty)}</strong></div>
        <div class="mt-2 pt-2 border-top">${t('types.stat_spend')} <strong>฿${fmt(totalSpend)}</strong></div>
    `;
}

function renderUnmapped(unmapped) {
    if (unmapped.length === 0) {
        $('unmappedPanel').innerHTML = `<div class="text-success"><i class="bi bi-check-circle"></i> ${t('types.all_mapped')}</div>`;
    } else {
        $('unmappedPanel').innerHTML = unmapped.map(n =>
            `<div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                <span>${escHtml(n)}</span>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="quickAdd('${escJs(n)}')" title="${t('common.add')}"><i class="bi bi-plus"></i></button>
            </div>`
        ).join('');
    }
}

// --- CRUD ---
function showForm() {
    $('tId').value = '';
    $('typeForm').reset();
    $('formTitle').textContent = t('types.add');
    new bootstrap.Modal($('formModal')).show();
}

function editType(id) {
    const ty = allTypes.find(x => x.id == id);
    if (!ty) return;
    $('tId').value = ty.id;
    $('tName').value = ty.name;
    $('tDesc').value = ty.description || '';
    $('tSort').value = ty.sort_order;
    $('formTitle').textContent = t('types.edit_prefix', { name: ty.name });
    new bootstrap.Modal($('formModal')).show();
}

function quickAdd(name) {
    showForm();
    $('tName').value = name;
}

async function saveType() {
    const form = $('typeForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const body = new FormData();
    body.append('action', 'type_save');
    const id = $('tId').value;
    if (id) body.append('id', id);
    body.append('name', $('tName').value);
    body.append('description', $('tDesc').value);
    body.append('sort_order', $('tSort').value);

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadTypes();
    notifyDataChanged('types');
}

function deleteType(id, name) {
    $('delId').value = id;
    $('delName').textContent = name;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'type_delete');
    body.append('id', $('delId').value);
    await fetch('api.php', { method: 'POST', body });
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadTypes();
    notifyDataChanged('types');
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
