<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Idol Management - Numa Log</title>
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
        .badge-company { background: #dc2626; color: white; }
        .badge-group { background: #7c3aed; color: white; }
        .badge-unit { background: #0891b2; color: white; }
        .badge-member { background: #16a34a; color: white; }
        .tree-item { border-left: 2px solid #e5e7eb; padding-left: 1rem; margin-left: 0.5rem; }
        .tree-item.depth-0 { border-left: none; padding-left: 0; margin-left: 0; }
        .tree-row { padding: 6px 8px; border-radius: 6px; margin-bottom: 2px; transition: background .15s; }
        .tree-row:hover { background: #f3f0ff; }
        .tree-children { margin-top: 2px; }
        .stat-muted { color: #9ca3af; font-size: 12px; }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-people"></i> Idol Management <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-bar-chart-line"></i> Report</a>
            <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Tree View -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-diagram-3"></i> Idol Hierarchy</strong>
                    <div>
                        <?php if (ALLOW_RESEED): ?>
                        <button class="btn btn-outline-secondary btn-sm me-1" onclick="seedData()">
                            <i class="bi bi-arrow-clockwise"></i> Re-seed
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-primary btn-sm" onclick="showForm()">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                </div>
                <div class="card-body" id="treeContainer">
                    <div class="text-center text-muted py-4">Loading...</div>
                </div>
            </div>
        </div>

        <!-- Legend + Stats -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Legend</strong></div>
                <div class="card-body py-2">
                    <div class="mb-1"><span class="badge badge-company">Company</span> - ค่าย / บริษัท</div>
                    <div class="mb-1"><span class="badge badge-group">Group</span> - วง / กลุ่ม</div>
                    <div class="mb-1"><span class="badge badge-unit">Unit</span> - ยูนิตย่อย</div>
                    <div><span class="badge badge-member">Member</span> - สมาชิก / ไอดอล</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2"><strong>Summary</strong></div>
                <div class="card-body py-2" id="statsPanel">-</div>
            </div>

            <div class="card">
                <div class="card-header py-2"><strong>Unmapped Idol Names</strong></div>
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
                <h5 class="modal-title" id="formTitle">Add Entity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="entityForm">
                    <input type="hidden" id="eId">
                    <div class="mb-2">
                        <label class="form-label small">Name</label>
                        <input type="text" class="form-control form-control-sm" id="eName" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Category</label>
                        <select class="form-select form-select-sm" id="eCategory">
                            <option value="company">Company</option>
                            <option value="group">Group</option>
                            <option value="unit">Unit</option>
                            <option value="member" selected>Member</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Parent</label>
                        <select class="form-select form-select-sm" id="eParent">
                            <option value="">- None (Top level) -</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Sort Order</label>
                        <input type="number" class="form-control form-control-sm" id="eSort" value="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveEntity()">
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
                <p>Delete <strong id="delName"></strong>? Children will become unparented.</p>
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
let allEntities = [];
let allParents = [];

document.addEventListener('DOMContentLoaded', loadTree);

async function loadTree() {
    const res = await fetch('api.php?action=idol_entities_tree').then(r => r.json());
    allEntities = res.entities;
    allParents = res.parents;
    renderTree();
    renderStats();
    loadUnmapped();
    populateParentSelect();
}

function renderTree() {
    const byParent = {};
    const roots = [];
    allEntities.forEach(e => {
        const pid = e.parent_id || 'root';
        if (!byParent[pid]) byParent[pid] = [];
        byParent[pid].push(e);
        if (!e.parent_id) roots.push(e);
    });

    function buildNode(entity, depth) {
        const children = byParent[entity.id] || [];
        const badge = `<span class="badge badge-${entity.category}">${entity.category}</span>`;
        const stats = entity.total_price > 0
            ? `<span class="stat-muted ms-2">${entity.items_count} items / ฿${fmt(entity.total_price)}</span>`
            : '';
        const btns = `
            <button class="btn btn-outline-primary btn-sm px-1 py-0 ms-1" onclick="editEntity(${entity.id})" title="Edit"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteEntity(${entity.id}, '${escJs(entity.name)}')" title="Delete"><i class="bi bi-trash"></i></button>
        `;

        let html = `<div class="tree-item depth-${depth}">`;
        html += `<div class="tree-row d-flex align-items-center">
            ${badge}
            <strong class="ms-2">${escHtml(entity.name)}</strong>
            ${stats}
            <span class="ms-auto">${btns}</span>
        </div>`;

        if (children.length > 0) {
            html += `<div class="tree-children">`;
            children.forEach(c => { html += buildNode(c, depth + 1); });
            html += `</div>`;
        }
        html += `</div>`;
        return html;
    }

    let html = '';
    roots.forEach(r => { html += buildNode(r, 0); });
    $('treeContainer').innerHTML = html || '<div class="text-muted text-center py-4">No data. Click "Re-seed" to initialize.</div>';
}

function renderStats() {
    const cats = {};
    allEntities.forEach(e => {
        cats[e.category] = (cats[e.category] || 0) + 1;
    });
    const totalSpend = allEntities.reduce((s, e) => s + (e.total_price || 0), 0);
    $('statsPanel').innerHTML = `
        <div>Company: <strong>${cats.company || 0}</strong></div>
        <div>Group: <strong>${cats.group || 0}</strong></div>
        <div>Unit: <strong>${cats.unit || 0}</strong></div>
        <div>Member: <strong>${cats.member || 0}</strong></div>
        <div class="mt-2 pt-2 border-top">Total entities: <strong>${allEntities.length}</strong></div>
    `;
}

async function loadUnmapped() {
    const mapped = new Set(allEntities.map(e => e.name));
    const res = await fetch('api.php?action=filters').then(r => r.json());
    const unmapped = res.idols.filter(n => n && n !== '-' && !mapped.has(n));

    if (unmapped.length === 0) {
        $('unmappedPanel').innerHTML = '<div class="text-success"><i class="bi bi-check-circle"></i> All idol names are mapped!</div>';
    } else {
        $('unmappedPanel').innerHTML = unmapped.map(n =>
            `<div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                <span>${escHtml(n)}</span>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="quickAdd('${escJs(n)}')" title="Add"><i class="bi bi-plus"></i></button>
            </div>`
        ).join('');
    }
}

function populateParentSelect() {
    const sel = $('eParent');
    const val = sel.value;
    sel.innerHTML = '<option value="">- None (Top level) -</option>';
    allParents.forEach(p => {
        const cat = p.category.charAt(0).toUpperCase() + p.category.slice(1);
        sel.innerHTML += `<option value="${p.id}">[${cat}] ${escHtml(p.name)}</option>`;
    });
    sel.value = val;
}

// --- CRUD ---
function showForm(id = null) {
    $('eId').value = '';
    $('entityForm').reset();
    $('eCategory').value = 'member';
    $('formTitle').textContent = 'Add Entity';
    populateParentSelect();
    new bootstrap.Modal($('formModal')).show();
}

function editEntity(id) {
    const e = allEntities.find(x => x.id == id);
    if (!e) return;
    $('eId').value = e.id;
    $('eName').value = e.name;
    $('eCategory').value = e.category;
    $('eSort').value = e.sort_order;
    $('formTitle').textContent = 'Edit: ' + e.name;
    populateParentSelect();
    $('eParent').value = e.parent_id || '';
    new bootstrap.Modal($('formModal')).show();
}

function quickAdd(name) {
    showForm();
    $('eName').value = name;
    $('eCategory').value = 'member';
}

async function saveEntity() {
    const form = $('entityForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const body = new FormData();
    body.append('action', 'idol_entity_save');
    const id = $('eId').value;
    if (id) body.append('id', id);
    body.append('name', $('eName').value);
    body.append('category', $('eCategory').value);
    body.append('parent_id', $('eParent').value);
    body.append('sort_order', $('eSort').value);

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadTree();
}

function deleteEntity(id, name) {
    $('delId').value = id;
    $('delName').textContent = name;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'idol_entity_delete');
    body.append('id', $('delId').value);
    await fetch('api.php', { method: 'POST', body });
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadTree();
}

async function seedData() {
    if (!confirm('Re-seed will reset all idol entity data. Continue?')) return;
    const body = new FormData();
    body.append('action', 'seed');
    const res = await fetch('seed_idols.php', { method: 'POST', body }).then(r => r.json());
    alert(res.message);
    loadTree();
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
