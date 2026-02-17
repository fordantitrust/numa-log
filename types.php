<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Type Management - Numa Log</title>
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
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-tags"></i> Type Management <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-bar-chart-line"></i> Report</a>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Type List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-tags"></i> Type Categories</strong>
                    <button class="btn btn-primary btn-sm" onclick="showForm()">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-center" style="width:70px">Rows</th>
                                <th class="text-center" style="width:70px">Qty</th>
                                <th class="text-end" style="width:120px">Total Spent</th>
                                <th class="text-center" style="width:60px">Order</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="typeList">
                            <tr><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Summary</strong></div>
                <div class="card-body py-2" id="statsPanel">-</div>
            </div>

            <div class="card">
                <div class="card-header py-2"><strong>Unmapped Type Names</strong></div>
                <div class="card-body py-2" id="unmappedPanel">
                    <div class="text-muted">Loading...</div>
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
                <h5 class="modal-title" id="formTitle">Add Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="typeForm">
                    <input type="hidden" id="tId">
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input type="text" class="form-control form-control-sm" id="tName" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Description</label>
                        <input type="text" class="form-control form-control-sm" id="tDesc">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Sort Order</label>
                        <input type="number" class="form-control form-control-sm" id="tSort" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveType()">
                    <i class="bi bi-check-lg"></i> Save
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
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete type <strong id="delName"></strong>?</p>
                <input type="hidden" id="delId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash"></i> Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
const fmt = n => new Intl.NumberFormat('th-TH').format(n);
let allTypes = [];

document.addEventListener('DOMContentLoaded', loadTypes);

async function loadTypes() {
    const res = await fetch('api.php?action=type_list').then(r => r.json());
    allTypes = res.types;
    renderTable();
    renderStats();
    renderUnmapped(res.unmapped);
}

function renderTable() {
    if (allTypes.length === 0) {
        $('typeList').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No types yet. Click "Add" to create one.</td></tr>';
        return;
    }

    $('typeList').innerHTML = allTypes.map((t, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td><strong>${escHtml(t.name)}</strong></td>
            <td class="stat-muted">${escHtml(t.description || '')}</td>
            <td class="text-center">${t.items_count}</td>
            <td class="text-center">${t.total_qty}</td>
            <td class="text-end">${t.total_price > 0 ? '฿' + fmt(t.total_price) : '-'}</td>
            <td class="text-center">${t.sort_order}</td>
            <td>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editType(${t.id})" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteType(${t.id}, '${escJs(t.name)}')" title="Delete"><i class="bi bi-trash"></i></button>
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
        <div>Total types: <strong>${total}</strong></div>
        <div>With items: <strong>${withItems}</strong></div>
        <div>Total qty: <strong>${fmt(totalQty)}</strong></div>
        <div class="mt-2 pt-2 border-top">Total tracked spend: <strong>฿${fmt(totalSpend)}</strong></div>
    `;
}

function renderUnmapped(unmapped) {
    if (unmapped.length === 0) {
        $('unmappedPanel').innerHTML = '<div class="text-success"><i class="bi bi-check-circle"></i> All type names are mapped!</div>';
    } else {
        $('unmappedPanel').innerHTML = unmapped.map(n =>
            `<div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                <span>${escHtml(n)}</span>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="quickAdd('${escJs(n)}')" title="Add"><i class="bi bi-plus"></i></button>
            </div>`
        ).join('');
    }
}

// --- CRUD ---
function showForm() {
    $('tId').value = '';
    $('typeForm').reset();
    $('formTitle').textContent = 'Add Type';
    new bootstrap.Modal($('formModal')).show();
}

function editType(id) {
    const t = allTypes.find(x => x.id == id);
    if (!t) return;
    $('tId').value = t.id;
    $('tName').value = t.name;
    $('tDesc').value = t.description || '';
    $('tSort').value = t.sort_order;
    $('formTitle').textContent = 'Edit: ' + t.name;
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
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
