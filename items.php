<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('nav.items') ?> - Numa Log</title>
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
        /* Multi-select filter */
        .ms-wrap { position: relative; }
        .ms-box { min-height: 31px; cursor: pointer; display: flex; flex-wrap: wrap; gap: 3px; align-items: center; padding: 2px 8px; }
        .ms-box .badge { font-size: 11px; font-weight: 500; }
        .ms-ph { color: #6c757d; font-size: 13px; line-height: 1.5; }
        .ms-drop { position: absolute; top: 100%; left: 0; right: 0; z-index: 1060; background: white; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 .375rem .375rem; box-shadow: 0 4px 12px rgba(0,0,0,.15); display: none; }
        .ms-drop.show { display: block; }
        .ms-search { border: none; border-bottom: 1px solid #dee2e6; border-radius: 0; font-size: 13px; width: 100%; padding: 5px 10px; outline: none; }
        .ms-list { max-height: 180px; overflow-y: auto; }
        .ms-item { padding: 5px 10px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .ms-item:hover { background: #f3f0ff; }
        .ms-item.sel { color: var(--primary); }
        .ms-empty { padding: 8px 10px; color: #9ca3af; font-size: 12px; font-style: italic; }
        /* Mobile */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .ms-drop, .sd-list { -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="password"],
            select, textarea, .ms-search { font-size: 16px !important; }
            .ms-item, .sd-list .sd-item { font-size: 15px; min-height: 40px; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
// Auto-append CSRF token to all FormData POST requests + force no-store cache mode.
// The no-store option bypasses the browser HTTP cache regardless of any stale
// Cache-Control header from earlier responses — fixes "Add/Edit doesn't refresh".
const _origAppend = FormData.prototype.append;
const _origFetch = window.fetch;
window.fetch = function(url, opts = {}) {
    if (opts.body instanceof FormData) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token && !opts.body.has('csrf_token')) opts.body.append('csrf_token', token);
    }
    if (!opts.cache) opts.cache = 'no-store';
    return _origFetch.call(this, url, opts);
};
</script>

<div id="loading" class="spinner-overlay">
    <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status">
        <span class="visually-hidden"><?= t('common.loading') ?></span>
    </div>
</div>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-list-ul"></i> <?= t('nav.items') ?> <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div class="d-flex align-items-center">
            <?= langSwitcher() ?>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-speedometer2"></i><span class="d-none d-sm-inline"> <?= t('nav.dashboard') ?></span>
            </a>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline"> <?= t('nav.report') ?></span>
            </a>
            <a href="budget.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-piggy-bank"></i><span class="d-none d-sm-inline"> <?= t('nav.budget') ?></span>
            </a>
            <a href="idols.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-people"></i><span class="d-none d-sm-inline"> <?= t('nav.idols') ?></span>
            </a>
            <a href="types.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-tags"></i><span class="d-none d-sm-inline"> <?= t('nav.types') ?></span>
            </a>
            <?php if (ALLOW_IMPORT): ?>
            <button class="btn btn-outline-light btn-sm me-2" onclick="showImportModal()">
                <i class="bi bi-file-earmark-arrow-up"></i><span class="d-none d-sm-inline"> <?= t('items.import_excel') ?></span>
            </button>
            <?php endif; ?>
            <button class="btn btn-light btn-sm me-2" onclick="showFormModal()">
                <i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline"> <?= t('items.add_item') ?></span>
            </button>
            <?php $u = currentUser(); if (AUTH_ENABLED && $u): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"> <?= htmlspecialchars($u['display_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($u['role'] === 'admin'): ?>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> <?= t('nav.users') ?></a></li>
                    <li><a class="dropdown-item" href="backup.php"><i class="bi bi-database"></i> <?= t('nav.backup') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= currentLang() === 'th' ? 'help.php' : 'help_en.php' ?>"><i class="bi bi-question-circle"></i> <?= t('nav.help') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> <?= t('nav.logout') ?></a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <!-- Pending resolution banner (v5) -->
    <div id="pendingBanner" class="alert alert-warning d-none justify-content-between align-items-center py-2 mb-3">
        <span><i class="bi bi-exclamation-triangle"></i>
            <strong id="pendingBannerCount">0</strong> <?= t('items.pending_banner_text') ?>
        </span>
        <a href="idols.php" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-tools"></i> <?= t('items.resolve') ?>
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75"><?= t('items.total_items') ?></div>
                <div class="display-6" id="sumTotal">0</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75"><?= t('items.total_quantity') ?></div>
                <div class="display-6" id="sumQty">0</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75"><?= t('items.total_spent') ?></div>
                <div class="display-6" id="sumPrice">&#3647;0</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card p-3">
                <div class="small opacity-75"><?= t('items.avg_per_item') ?></div>
                <div class="display-6" id="sumAvg">&#3647;0</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form id="filterForm" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('common.search') ?></label>
                    <input type="text" class="form-control form-control-sm" id="fSearch" placeholder="<?= t('items.search_ph') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('common.idol') ?></label>
                    <div class="ms-wrap" id="msIdol">
                        <div class="ms-box form-control form-control-sm"><span class="ms-ph"><?= t('common.all') ?></span></div>
                        <div class="ms-drop">
                            <input type="text" class="ms-search" placeholder="<?= t('items.search_dots') ?>">
                            <div class="ms-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('common.type') ?></label>
                    <div class="ms-wrap" id="msType">
                        <div class="ms-box form-control form-control-sm"><span class="ms-ph"><?= t('common.all') ?></span></div>
                        <div class="ms-drop">
                            <input type="text" class="ms-search" placeholder="<?= t('items.search_dots') ?>">
                            <div class="ms-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('items.from') ?></label>
                    <input type="date" class="form-control form-control-sm" id="fDateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('items.to') ?></label>
                    <input type="date" class="form-control form-control-sm" id="fDateTo">
                </div>
                <div class="col-6 col-md-2">
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="resetFilters()">
                            <i class="bi bi-x-lg"></i> <?= t('items.clear') ?>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm flex-fill" onclick="exportExcel()">
                            <i class="bi bi-file-earmark-arrow-down"></i> <?= t('items.export') ?>
                        </button>
                    </div>
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
                        <th data-sort="order_date" class="sort-icon"><?= t('items.order_date') ?></th>
                        <th data-sort="event_date" class="sort-icon"><?= t('items.event_date') ?></th>
                        <th data-sort="title" class="sort-icon"><?= t('common.title') ?></th>
                        <th data-sort="idol" class="sort-icon"><?= t('common.idol') ?></th>
                        <th data-sort="type" class="sort-icon"><?= t('common.type') ?></th>
                        <th data-sort="price_per_qty" class="sort-icon text-end"><?= t('items.price_qty') ?></th>
                        <th data-sort="qty" class="sort-icon text-end"><?= t('common.qty') ?></th>
                        <th class="text-end"><?= t('common.total') ?></th>
                        <th style="width:110px" class="text-center"><?= t('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="10" class="text-center py-4 text-muted"><?= t('items.loading_data') ?></td></tr>
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
                <h5 class="modal-title" id="formTitle"><?= t('items.add_item') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="itemForm">
                    <input type="hidden" id="itemId">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small"><?= t('items.order_date') ?></label>
                            <input type="date" class="form-control form-control-sm" id="itemOrderDate" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('items.event_date') ?></label>
                            <input type="date" class="form-control form-control-sm" id="itemEventDate">
                        </div>
                        <div class="col-12">
                            <label class="form-label small"><?= t('common.title') ?></label>
                            <input type="text" class="form-control form-control-sm" id="itemTitle" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('common.idol') ?></label>
                            <div class="sd-wrap">
                                <input type="text" class="form-control form-control-sm" id="itemIdol" required autocomplete="off" placeholder="<?= t('items.search_or_type') ?>">
                                <input type="hidden" id="itemIdolId">
                                <div class="sd-list" id="idolDropdown"></div>
                            </div>
                            <div class="form-text small text-info" id="itemIdolHint" style="display:none"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('common.type') ?></label>
                            <div class="sd-wrap">
                                <input type="text" class="form-control form-control-sm" id="itemType" required autocomplete="off" placeholder="<?= t('items.search_or_type') ?>">
                                <div class="sd-list" id="typeDropdown"></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('items.price_per_qty_label') ?></label>
                            <input type="number" class="form-control form-control-sm" id="itemPrice" min="0" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('common.qty') ?></label>
                            <input type="number" class="form-control form-control-sm" id="itemQty" min="1" value="1" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveItem()">
                    <i class="bi bi-check-lg"></i> <?= t('common.save') ?>
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
                <h5 class="modal-title"><?= t('items.import_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted"><?= t('items.import_body') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="doImport()">
                    <i class="bi bi-file-earmark-excel"></i> <?= t('items.import') ?>
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
                <h5 class="modal-title"><?= t('items.confirm_delete_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><?= t('items.delete_confirm_prefix') ?> <strong id="deleteName"></strong>?</p>
                <input type="hidden" id="deleteId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> <?= t('common.delete') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js"></script>
<script>
const $ = id => document.getElementById(id);
let currentSort = 'order_date';
let currentDir = 'desc';
let currentPage = 1;
let debounceTimer = null;
let filtersData = { idols: [], types: [] };
let msIdol, msType;

// --- Init ---
document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pDateFrom = urlParams.get('date_from');
    const pDateTo   = urlParams.get('date_to');
    const pIdol     = urlParams.get('idol');

    if (pDateFrom) $('fDateFrom').value = pDateFrom;
    if (pDateTo)   $('fDateTo').value   = pDateTo;

    await loadFilters();

    if (pIdol) msIdol.setSelected([pIdol]);

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
    ['fDateFrom', 'fDateTo'].forEach(id => {
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
    msIdol = initMultiSelect('msIdol', () => filtersData.idols, () => { currentPage = 1; loadData(); });
    msType = initMultiSelect('msType', () => filtersData.types, () => { currentPage = 1; loadData(); });
    initSearchableDropdown('itemIdol', 'idolDropdown', () => filtersData.idols);
    initSearchableDropdown('itemType', 'typeDropdown', () => filtersData.types);
    refreshPendingBanner();
}

async function refreshPendingBanner() {
    try {
        const res = await api('api.php?action=idol_entities_tree');
        const count = res.ambiguous_count || 0;
        const banner = $('pendingBanner');
        if (count > 0) {
            $('pendingBannerCount').textContent = count;
            banner.classList.remove('d-none');
            banner.classList.add('d-flex');
        } else {
            banner.classList.remove('d-flex');
            banner.classList.add('d-none');
        }
    } catch (_) { /* non-blocking */ }
}

function initSearchableDropdown(inputId, listId, getItems) {
    const input = $(inputId);
    const list = $(listId);
    if (input._sdInit) return;
    input._sdInit = true;
    let activeIdx = -1;

    // If this dropdown drives the idol field, clear any cached idol_id when text changes.
    if (inputId === 'itemIdol') {
        input.addEventListener('input', () => {
            const hidden = $('itemIdolId');
            if (hidden) hidden.value = '';
            const hint = $('itemIdolHint');
            if (hint) hint.style.display = 'none';
        });
    }

    function render() {
        const q = input.value.toLowerCase();
        const items = getItems().filter(i => !q || i.toLowerCase().includes(q));
        activeIdx = -1;
        if (items.length === 0) {
            list.innerHTML = `<div class="sd-empty">${t('items.no_match_add')}</div>`;
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

function initMultiSelect(wrapId, getItems, onChange) {
    const wrap = document.getElementById(wrapId);
    if (wrap._msInit) return wrap._msInst;
    wrap._msInit = true;
    const box  = wrap.querySelector('.ms-box');
    const drop = wrap.querySelector('.ms-drop');
    const srch = wrap.querySelector('.ms-search');
    const lst  = wrap.querySelector('.ms-list');
    let selected = [];

    function renderBox() {
        box.innerHTML = '';
        if (selected.length === 0) {
            box.innerHTML = `<span class="ms-ph">${t('common.all')}</span>`;
        } else {
            selected.forEach(val => {
                const tag = document.createElement('span');
                tag.className = 'badge badge-idol d-inline-flex align-items-center gap-1';
                tag.innerHTML = escHtml(val) + '<i class="bi bi-x" style="cursor:pointer;font-size:10px"></i>';
                tag.querySelector('i').addEventListener('click', e => {
                    e.stopPropagation();
                    selected = selected.filter(v => v !== val);
                    renderBox(); renderList(); onChange(selected);
                });
                box.appendChild(tag);
            });
        }
    }

    function renderList() {
        const q = srch.value.toLowerCase();
        const items = getItems().filter(i => !q || i.toLowerCase().includes(q));
        if (items.length === 0) {
            lst.innerHTML = `<div class="ms-empty">${t('items.no_match')}</div>`;
        } else {
            lst.innerHTML = items.map(item => {
                const sel = selected.includes(item);
                return `<div class="ms-item${sel ? ' sel' : ''}" data-val="${escHtml(item)}">
                    <i class="bi ${sel ? 'bi-check-square-fill' : 'bi-square'}" style="color:${sel ? 'var(--primary)' : '#adb5bd'}"></i>
                    ${escHtml(item)}
                </div>`;
            }).join('');
            lst.querySelectorAll('.ms-item').forEach(el => {
                el.addEventListener('click', () => {
                    const val = el.dataset.val;
                    selected = selected.includes(val) ? selected.filter(v => v !== val) : [...selected, val];
                    renderBox(); renderList(); onChange(selected);
                });
            });
        }
    }

    box.addEventListener('click', () => { srch.value = ''; renderList(); drop.classList.toggle('show'); });
    srch.addEventListener('input', renderList);
    document.addEventListener('click', e => { if (!wrap.contains(e.target)) drop.classList.remove('show'); });

    renderBox();
    wrap._msInst = {
        getSelected: () => [...selected],
        clear()            { selected = []; renderBox(); },
        setSelected(arr)   { selected = [...arr]; renderBox(); },
    };
    return wrap._msInst;
}

async function loadData() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        per_page: 20,
        sort: currentSort,
        dir: currentDir,
        search: $('fSearch').value,
        date_from: $('fDateFrom').value,
        date_to: $('fDateTo').value,
    });
    if (msIdol) msIdol.getSelected().forEach(v => params.append('idol[]', v));
    if (msType) msType.getSelected().forEach(v => params.append('type[]', v));

    const res = await api('api.php?' + params);
    renderTable(res);
    renderPagination(res);
    renderSummary(res);
}

function formatNumber(n) {
    return new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

function formatInt(n) {
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
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">${t('items.no_data_found')}</td></tr>`;
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
                <button class="btn btn-outline-secondary btn-sm px-1 py-0" onclick="cloneItem(${r.id})" title="${t('common.clone')}">
                    <i class="bi bi-copy"></i>
                </button>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editItem(${r.id})" title="${t('common.edit')}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteItem(${r.id}, '${escJs(r.title)}')" title="${t('common.delete')}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(res) {
    const pg = $('pagination');
    const { page, total_pages } = res;
    $('pageInfo').textContent = t('items.showing', { from: ((page-1)*res.per_page)+1, to: Math.min(page*res.per_page, res.total), total: formatInt(res.total) });

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
    $('sumTotal').textContent = formatInt(res.total);
    $('sumQty').textContent = formatInt(res.summary.total_qty);
    $('sumPrice').textContent = '฿' + formatNumber(res.summary.total_price);
    const avg = res.total > 0 ? Math.round(res.summary.total_price / res.total) : 0;
    $('sumAvg').textContent = '฿' + formatNumber(avg);
}

function goPage(p) { currentPage = p; loadData(); }

// --- CRUD ---
function showFormModal(id = null) {
    $('itemId').value = '';
    $('itemIdolId').value = '';
    $('itemIdolHint').style.display = 'none';
    $('itemIdolHint').innerHTML = '';
    $('itemForm').reset();
    $('formTitle').textContent = id ? t('items.edit_item') : t('items.add_item');
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
    $('itemIdolId').value = d.idol_id || '';
    $('itemIdolHint').style.display = 'none';
    $('itemType').value = d.type;
    $('itemPrice').value = d.price_per_qty;
    $('itemQty').value = d.qty;
    $('formTitle').textContent = t('items.edit_item');
    new bootstrap.Modal($('formModal')).show();
}

async function cloneItem(id) {
    const res = await api('api.php?action=get&id=' + id);
    if (res.error) { alert(res.error); return; }
    const d = res.data;
    const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD
    $('itemId').value = '';
    $('itemOrderDate').value = today;
    $('itemEventDate').value = today;
    $('itemTitle').value = d.title;
    $('itemIdol').value = d.idol;
    $('itemIdolId').value = d.idol_id || '';
    $('itemIdolHint').style.display = 'none';
    $('itemType').value = d.type;
    $('itemPrice').value = d.price_per_qty;
    $('itemQty').value = d.qty;
    $('formTitle').textContent = t('items.clone_item');
    new bootstrap.Modal($('formModal')).show();
}

async function saveItem(explicitIdolId) {
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
    const idolId = explicitIdolId ?? $('itemIdolId').value;
    if (idolId) body.append('idol_id', idolId);
    body.append('type', $('itemType').value);
    body.append('price_per_qty', $('itemPrice').value);
    body.append('qty', $('itemQty').value);

    showLoading(true);
    const res = await fetch('api.php', { method: 'POST', body });
    const json = await res.json().catch(() => ({}));
    showLoading(false);

    // 409 = ambiguous idol name — show picker to disambiguate
    if (res.status === 409 && Array.isArray(json.candidates)) {
        showIdolPicker(json.name || $('itemIdol').value, json.candidates);
        return;
    }
    if (json.error) { alert(json.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadFilters();
    loadData();
}

function showIdolPicker(name, candidates) {
    const html = candidates.map(c => `
        <button class="btn btn-outline-primary btn-sm me-1 mb-1" onclick="pickIdol(${c.id}, '${escJs(c.display)}')">
            ${escHtml(c.display)}
        </button>
    `).join('');
    const wrap = $('itemIdolHint');
    wrap.innerHTML = `<strong>${t('items.idol_picker', { name: escHtml(name) })}</strong><br>${html}`;
    wrap.style.display = '';
}

function pickIdol(idolId, display) {
    $('itemIdolId').value = idolId;
    $('itemIdolHint').innerHTML = `<i class="bi bi-check-circle text-success"></i> ${t('items.linked_to')} <strong>${escHtml(display)}</strong>`;
    saveItem(idolId);
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
    alert(res.message || t('items.import_complete'));
    loadFilters();
    loadData();
}

function resetFilters() {
    $('fSearch').value = '';
    $('fDateFrom').value = '';
    $('fDateTo').value = '';
    if (msIdol) msIdol.clear();
    if (msType) msType.clear();
    currentPage = 1;
    loadData();
}

// --- Export ---
function exportExcel() {
    const params = new URLSearchParams();
    const search = $('fSearch').value;
    const from   = $('fDateFrom').value;
    const to     = $('fDateTo').value;
    if (search) params.set('search', search);
    if (from)   params.set('date_from', from);
    if (to)     params.set('date_to', to);
    if (msIdol) msIdol.getSelected().forEach(v => params.append('idol[]', v));
    if (msType) msType.getSelected().forEach(v => params.append('type[]', v));
    window.location.href = 'export.php?' + params.toString();
}

// --- Helpers ---
function showLoading(show) { $('loading').style.display = show ? 'flex' : 'none'; }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
