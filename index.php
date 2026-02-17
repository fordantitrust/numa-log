<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #7c3aed;
            --primary-hover: #6d28d9;
        }
        body { background: #f3f4f6; font-size: 14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .table th { background: #f9fafb; position: sticky; top: 0; white-space: nowrap; cursor: pointer; user-select: none; }
        .table th:hover { background: #e5e7eb; }
        .table td { vertical-align: middle; }
        .sort-icon::after { content: ' \2195'; opacity: .3; }
        .sort-asc::after { content: ' \2191'; opacity: 1; }
        .sort-desc::after { content: ' \2193'; opacity: 1; }
        .summary-card { background: linear-gradient(135deg, var(--primary), #a78bfa); color: white; }
        .summary-card .display-6 { font-weight: 700; }
        .badge-idol { background: #ddd6fe; color: #5b21b6; }
        .badge-type { background: #fce7f3; color: #9d174d; }
        .table-responsive { max-height: 65vh; overflow-y: auto; }
        .page-link { color: var(--primary); }
        .page-link.active, .active > .page-link { background: var(--primary); border-color: var(--primary); }
        #loading { display: none; }
        .spinner-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,.7); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        }
        /* Searchable dropdown */
        .sd-wrap { position: relative; }
        .sd-wrap input { cursor: text; }
        .sd-list {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
            max-height: 200px; overflow-y: auto; background: white;
            border: 1px solid #dee2e6; border-radius: 0 0 .375rem .375rem;
            box-shadow: 0 4px 12px rgba(0,0,0,.15); display: none;
        }
        .sd-list.show { display: block; }
        .sd-list .sd-item {
            padding: 5px 10px; cursor: pointer; font-size: 13px;
        }
        .sd-list .sd-item:hover, .sd-list .sd-item.active { background: #f3f0ff; color: var(--primary); }
        .sd-list .sd-empty { padding: 8px 10px; color: #9ca3af; font-size: 12px; font-style: italic; }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
// Auto-append CSRF token to all FormData POST requests
const _origAppend = FormData.prototype.append;
const _origFetch = window.fetch;
window.fetch = function(url, opts = {}) {
    if (opts.body instanceof FormData) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token && !opts.body.has('csrf_token')) opts.body.append('csrf_token', token);
    }
    return _origFetch.call(this, url, opts);
};
</script>

<div id="loading" class="spinner-overlay">
    <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-stars"></i> Numa Log <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-bar-chart-line"></i> Report
            </a>
            <a href="idols.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-people"></i> Idols
            </a>
            <a href="types.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-tags"></i> Types
            </a>
            <?php if (ALLOW_IMPORT): ?>
            <button class="btn btn-outline-light btn-sm me-2" onclick="showImportModal()">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </button>
            <?php endif; ?>
            <?php $u = currentUser(); if ($u && $u['role'] === 'admin'): ?>
            <a href="backup.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-database"></i> Backup
            </a>
            <?php endif; ?>
            <button class="btn btn-light btn-sm me-2" onclick="showFormModal()">
                <i class="bi bi-plus-lg"></i> Add Item
            </button>
            <?php if (AUTH_ENABLED && $u): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($u['display_name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <!-- Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75">Total Items</div>
                <div class="display-6" id="sumTotal">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75">Total Quantity</div>
                <div class="display-6" id="sumQty">0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75">Total Spent</div>
                <div class="display-6" id="sumPrice">&#3647;0</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75">Avg per Item</div>
                <div class="display-6" id="sumAvg">&#3647;0</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form id="filterForm" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">Search</label>
                    <input type="text" class="form-control form-control-sm" id="fSearch" placeholder="Search title...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Idol</label>
                    <select class="form-select form-select-sm" id="fIdol"><option value="">All</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Type</label>
                    <select class="form-select form-select-sm" id="fType"><option value="">All</option></select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" class="form-control form-control-sm" id="fDateFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" class="form-control form-control-sm" id="fDateTo">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()">
                        <i class="bi bi-x-lg"></i> Clear
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th data-sort="order_date" class="sort-icon">Order Date</th>
                        <th data-sort="event_date" class="sort-icon">Event Date</th>
                        <th data-sort="title" class="sort-icon">Title</th>
                        <th data-sort="idol" class="sort-icon">Idol</th>
                        <th data-sort="type" class="sort-icon">Type</th>
                        <th data-sort="price_per_qty" class="sort-icon text-end">Price/Qty</th>
                        <th data-sort="qty" class="sort-icon text-end">Qty</th>
                        <th class="text-end">Total</th>
                        <th style="width:110px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="10" class="text-center py-4 text-muted">Loading data...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <div class="small text-muted" id="pageInfo">-</div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm">
                    <input type="hidden" id="itemId">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Order Date</label>
                            <input type="date" class="form-control form-control-sm" id="itemOrderDate" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Event Date</label>
                            <input type="date" class="form-control form-control-sm" id="itemEventDate">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Title</label>
                            <input type="text" class="form-control form-control-sm" id="itemTitle" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Idol</label>
                            <div class="sd-wrap">
                                <input type="text" class="form-control form-control-sm" id="itemIdol" required autocomplete="off" placeholder="Search or type...">
                                <div class="sd-list" id="idolDropdown"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Type</label>
                            <div class="sd-wrap">
                                <input type="text" class="form-control form-control-sm" id="itemType" required autocomplete="off" placeholder="Search or type...">
                                <div class="sd-list" id="typeDropdown"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Price / Qty</label>
                            <input type="number" class="form-control form-control-sm" id="itemPrice" min="0" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Qty</label>
                            <input type="number" class="form-control form-control-sm" id="itemQty" min="1" value="1" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveItem()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Import data from <strong>idols.xlsx</strong>. This will <strong class="text-danger">replace all existing data</strong> in the database.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="doImport()">
                    <i class="bi bi-file-earmark-excel"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                <input type="hidden" id="deleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
let currentSort = 'order_date';
let currentDir = 'desc';
let currentPage = 1;
let debounceTimer = null;
let filtersData = { idols: [], types: [] };

// --- Init ---
document.addEventListener('DOMContentLoaded', () => {
    loadFilters();
    loadData();
    setupSort();
    setupFilterEvents();
});

function setupSort() {
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.sort;
            if (currentSort === col) {
                currentDir = currentDir === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = col;
                currentDir = 'asc';
            }
            document.querySelectorAll('th[data-sort]').forEach(h => h.className = 'sort-icon');
            th.className = currentDir === 'asc' ? 'sort-asc' : 'sort-desc';
            if (['price_per_qty', 'qty'].includes(col)) th.classList.add('text-end');
            currentPage = 1;
            loadData();
        });
    });
}

function setupFilterEvents() {
    $('fSearch').addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage = 1; loadData(); }, 300); });
    ['fIdol', 'fType', 'fDateFrom', 'fDateTo'].forEach(id => {
        $(id).addEventListener('change', () => { currentPage = 1; loadData(); });
    });
}

// --- API ---
async function api(url, opts = {}) {
    const res = await fetch(url, opts);
    return res.json();
}

async function loadFilters() {
    filtersData = await api('api.php?action=filters');
    populateSelect('fIdol', filtersData.idols);
    populateSelect('fType', filtersData.types);
    initSearchableDropdown('itemIdol', 'idolDropdown', () => filtersData.idols);
    initSearchableDropdown('itemType', 'typeDropdown', () => filtersData.types);
}

function populateSelect(id, items) {
    const sel = $(id);
    const val = sel.value;
    sel.innerHTML = '<option value="">All</option>';
    items.forEach(i => { const o = document.createElement('option'); o.value = i; o.textContent = i; sel.appendChild(o); });
    sel.value = val;
}

function initSearchableDropdown(inputId, listId, getItems) {
    const input = $(inputId);
    const list = $(listId);
    if (input._sdInit) return;
    input._sdInit = true;
    let activeIdx = -1;

    function render() {
        const q = input.value.toLowerCase();
        const items = getItems().filter(i => !q || i.toLowerCase().includes(q));
        activeIdx = -1;
        if (items.length === 0) {
            list.innerHTML = '<div class="sd-empty">No match — type to add new</div>';
        } else {
            list.innerHTML = items.map((item, i) =>
                `<div class="sd-item" data-idx="${i}" data-val="${escHtml(item)}">${highlightMatch(item, q)}</div>`
            ).join('');
        }
        list.classList.add('show');
    }

    function highlightMatch(text, q) {
        if (!q) return escHtml(text);
        const idx = text.toLowerCase().indexOf(q);
        if (idx === -1) return escHtml(text);
        return escHtml(text.substring(0, idx)) + '<strong>' + escHtml(text.substring(idx, idx + q.length)) + '</strong>' + escHtml(text.substring(idx + q.length));
    }

    function pick(val) {
        input.value = val;
        list.classList.remove('show');
        input.focus();
    }

    input.addEventListener('focus', render);
    input.addEventListener('input', render);
    input.addEventListener('keydown', e => {
        const items = list.querySelectorAll('.sd-item');
        if (!list.classList.contains('show') || items.length === 0) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); items.forEach((el, i) => el.classList.toggle('active', i === activeIdx)); items[activeIdx]?.scrollIntoView({ block: 'nearest' }); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); items.forEach((el, i) => el.classList.toggle('active', i === activeIdx)); items[activeIdx]?.scrollIntoView({ block: 'nearest' }); }
        else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); pick(items[activeIdx].dataset.val); }
        else if (e.key === 'Escape') { list.classList.remove('show'); }
    });

    list.addEventListener('mousedown', e => {
        const item = e.target.closest('.sd-item');
        if (item) { e.preventDefault(); pick(item.dataset.val); }
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !list.contains(e.target)) list.classList.remove('show');
    });
}

async function loadData() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        per_page: 20,
        sort: currentSort,
        dir: currentDir,
        search: $('fSearch').value,
        idol: $('fIdol').value,
        type: $('fType').value,
        date_from: $('fDateFrom').value,
        date_to: $('fDateTo').value,
    });

    const res = await api('api.php?' + params);
    renderTable(res);
    renderPagination(res);
    renderSummary(res);
}

function formatNumber(n) {
    return new Intl.NumberFormat('th-TH').format(n);
}

function formatDate(d) {
    if (!d) return '<span class="text-muted">-</span>';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderTable(res) {
    const tbody = $('tableBody');
    if (!res.data || res.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No data found</td></tr>';
        return;
    }
    const offset = (res.page - 1) * res.per_page;
    tbody.innerHTML = res.data.map((r, i) => `
        <tr>
            <td class="text-muted">${offset + i + 1}</td>
            <td>${formatDate(r.order_date)}</td>
            <td>${formatDate(r.event_date)}</td>
            <td>${escHtml(r.title)}</td>
            <td><span class="badge badge-idol">${escHtml(r.idol)}</span></td>
            <td><span class="badge badge-type">${escHtml(r.type)}</span></td>
            <td class="text-end">${formatNumber(r.price_per_qty)}</td>
            <td class="text-end">${r.qty}</td>
            <td class="text-end fw-semibold">${formatNumber(r.total_price)}</td>
            <td class="text-center text-nowrap">
                <button class="btn btn-outline-secondary btn-sm px-1 py-0" onclick="cloneItem(${r.id})" title="Clone">
                    <i class="bi bi-copy"></i>
                </button>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editItem(${r.id})" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteItem(${r.id}, '${escJs(r.title)}')" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(res) {
    const pg = $('pagination');
    const { page, total_pages } = res;
    $('pageInfo').textContent = `Showing ${((page-1)*res.per_page)+1}-${Math.min(page*res.per_page, res.total)} of ${formatNumber(res.total)} items`;

    if (total_pages <= 1) { pg.innerHTML = ''; return; }

    let html = '';
    html += `<li class="page-item ${page===1?'disabled':''}"><a class="page-link" href="#" onclick="goPage(${page-1});return false">&laquo;</a></li>`;

    let start = Math.max(1, page - 2);
    let end = Math.min(total_pages, page + 2);
    if (start > 1) html += `<li class="page-item"><a class="page-link" href="#" onclick="goPage(1);return false">1</a></li><li class="page-item disabled"><span class="page-link">...</span></li>`;
    for (let i = start; i <= end; i++) {
        html += `<li class="page-item ${i===page?'active':''}"><a class="page-link" href="#" onclick="goPage(${i});return false">${i}</a></li>`;
    }
    if (end < total_pages) html += `<li class="page-item disabled"><span class="page-link">...</span></li><li class="page-item"><a class="page-link" href="#" onclick="goPage(${total_pages});return false">${total_pages}</a></li>`;

    html += `<li class="page-item ${page===total_pages?'disabled':''}"><a class="page-link" href="#" onclick="goPage(${page+1});return false">&raquo;</a></li>`;
    pg.innerHTML = html;
}

function renderSummary(res) {
    $('sumTotal').textContent = formatNumber(res.total);
    $('sumQty').textContent = formatNumber(res.summary.total_qty);
    $('sumPrice').textContent = '฿' + formatNumber(res.summary.total_price);
    const avg = res.total > 0 ? Math.round(res.summary.total_price / res.total) : 0;
    $('sumAvg').textContent = '฿' + formatNumber(avg);
}

function goPage(p) { currentPage = p; loadData(); }

// --- CRUD ---
function showFormModal(id = null) {
    $('itemId').value = '';
    $('itemForm').reset();
    $('formTitle').textContent = id ? 'Edit Item' : 'Add Item';
    new bootstrap.Modal($('formModal')).show();
}

async function editItem(id) {
    const res = await api('api.php?action=get&id=' + id);
    if (res.error) { alert(res.error); return; }
    const d = res.data;
    $('itemId').value = d.id;
    $('itemOrderDate').value = d.order_date || '';
    $('itemEventDate').value = d.event_date || '';
    $('itemTitle').value = d.title;
    $('itemIdol').value = d.idol;
    $('itemType').value = d.type;
    $('itemPrice').value = d.price_per_qty;
    $('itemQty').value = d.qty;
    $('formTitle').textContent = 'Edit Item';
    new bootstrap.Modal($('formModal')).show();
}

async function cloneItem(id) {
    const res = await api('api.php?action=get&id=' + id);
    if (res.error) { alert(res.error); return; }
    const d = res.data;
    $('itemId').value = '';
    $('itemOrderDate').value = d.order_date || '';
    $('itemEventDate').value = d.event_date || '';
    $('itemTitle').value = d.title;
    $('itemIdol').value = d.idol;
    $('itemType').value = d.type;
    $('itemPrice').value = d.price_per_qty;
    $('itemQty').value = d.qty;
    $('formTitle').textContent = 'Clone Item';
    new bootstrap.Modal($('formModal')).show();
}

async function saveItem() {
    const form = $('itemForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const id = $('itemId').value;
    const body = new FormData();
    body.append('action', id ? 'update' : 'create');
    if (id) body.append('id', id);
    body.append('order_date', $('itemOrderDate').value);
    body.append('event_date', $('itemEventDate').value);
    body.append('title', $('itemTitle').value);
    body.append('idol', $('itemIdol').value);
    body.append('type', $('itemType').value);
    body.append('price_per_qty', $('itemPrice').value);
    body.append('qty', $('itemQty').value);

    showLoading(true);
    const res = await api('api.php', { method: 'POST', body });
    showLoading(false);

    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadFilters();
    loadData();
}

function deleteItem(id, title) {
    $('deleteId').value = id;
    $('deleteName').textContent = title;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const id = $('deleteId').value;
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', id);

    showLoading(true);
    await api('api.php', { method: 'POST', body });
    showLoading(false);

    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadFilters();
    loadData();
}

// --- Import ---
function showImportModal() {
    new bootstrap.Modal($('importModal')).show();
}

async function doImport() {
    bootstrap.Modal.getInstance($('importModal')).hide();
    showLoading(true);
    const body = new FormData();
    body.append('action', 'import');
    const res = await api('import.php', { method: 'POST', body });
    showLoading(false);
    alert(res.message || 'Import complete');
    loadFilters();
    loadData();
}

function resetFilters() {
    $('fSearch').value = '';
    $('fIdol').value = '';
    $('fType').value = '';
    $('fDateFrom').value = '';
    $('fDateTo').value = '';
    currentPage = 1;
    loadData();
}

// --- Helpers ---
function showLoading(show) { $('loading').style.display = show ? 'flex' : 'none'; }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
