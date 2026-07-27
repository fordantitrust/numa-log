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
        .date-filter-link { cursor: pointer; border-bottom: 1px dashed #9ca3af; }
        .date-filter-link:hover { color: var(--primary); border-bottom-color: var(--primary); }
        .filter-clickable { cursor: pointer; }
        .filter-clickable:hover { filter: brightness(0.92); }
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
        .sd-clear { position: absolute; top: 50%; right: 6px; transform: translateY(-50%); border: none; background: transparent; color: #9ca3af; padding: 0 4px; line-height: 1; cursor: pointer; z-index: 3; }
        .sd-clear:hover { color: #ef4444; }
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

<?php
$navActive = 'items';
$navIcon = 'bi-list-ul';
$navTitle = t('nav.items');
$navExtra = '';
if (ALLOW_IMPORT) {
    $navExtra = '<button class="btn btn-outline-light btn-sm me-2" onclick="showImportModal()"><i class="bi bi-file-earmark-arrow-up"></i><span> ' . t('items.import_excel') . '</span></button>';
}
require __DIR__ . '/navbar.php';
?>

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
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-funnel"></i> <?= t('items.filters_title') ?></strong>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-primary btn-sm" onclick="showFormModal()">
                    <i class="bi bi-plus-lg"></i> <?= t('items.add_item') ?>
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportExcel()">
                    <i class="bi bi-file-earmark-arrow-down"></i> <?= t('items.export') ?>
                </button>
            </div>
        </div>
        <div class="card-body py-2">
            <form id="filterForm" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small mb-0"><?= t('common.search') ?></label>
                    <input type="text" class="form-control form-control-sm" id="fSearch" placeholder="<?= t('items.search_ph') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('items.date_field') ?></label>
                    <select class="form-select form-select-sm" id="fDateField">
                        <option value="order_date"><?= t('items.order_date') ?></option>
                        <option value="event_date"><?= t('items.event_date') ?></option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('items.from') ?></label>
                    <input type="date" class="form-control form-control-sm" id="fDateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0"><?= t('items.to') ?></label>
                    <input type="date" class="form-control form-control-sm" id="fDateTo">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0"><?= t('common.idol') ?></label>
                    <div class="ms-wrap" id="msIdol">
                        <div class="ms-box form-control form-control-sm"><span class="ms-ph"><?= t('common.all') ?></span></div>
                        <div class="ms-drop">
                            <input type="text" class="ms-search" placeholder="<?= t('items.search_dots') ?>">
                            <div class="ms-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0"><?= t('common.type') ?></label>
                    <div class="ms-wrap" id="msType">
                        <div class="ms-box form-control form-control-sm"><span class="ms-ph"><?= t('common.all') ?></span></div>
                        <div class="ms-drop">
                            <input type="text" class="ms-search" placeholder="<?= t('items.search_dots') ?>">
                            <div class="ms-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-0"><?= t('events.event_label') ?></label>
                    <div class="ms-wrap" id="msEvent">
                        <div class="ms-box form-control form-control-sm"><span class="ms-ph"><?= t('common.all') ?></span></div>
                        <div class="ms-drop">
                            <input type="text" class="ms-search" placeholder="<?= t('items.search_dots') ?>">
                            <div class="ms-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()">
                        <i class="bi bi-x-lg"></i> <?= t('items.clear') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Action Bar -->
    <div id="bulkBar" class="card mb-2 d-none">
        <div class="card-body py-2 d-flex align-items-center flex-wrap gap-2">
            <span id="bulkCount" class="small fw-semibold"></span>
            <button class="btn btn-primary btn-sm" onclick="showBulkAssignModal()">
                <i class="bi bi-link-45deg"></i> <?= t('events.assign_to_event') ?>
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="confirmBulkUnassign()">
                <i class="bi bi-x-circle"></i> <?= t('events.unassign_from_event') ?>
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()"><?= t('common.cancel') ?></button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width:28px"><input type="checkbox" id="chkAll" title="<?= t('common.select_all') ?>"></th>
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
        <div class="card-footer d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="small text-muted" id="pageInfo">-</div>
                <label class="small text-muted mb-0 ms-2" for="fPerPage"><?= t('items.per_page') ?></label>
                <select class="form-select form-select-sm" id="fPerPage" style="width:auto">
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
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
                            <label class="form-label small"><?= t('events.event_label') ?></label>
                            <div class="sd-wrap">
                                <input type="text" class="form-control form-control-sm" id="itemEventName" autocomplete="off" placeholder="<?= t('events.no_event_ph') ?>" style="padding-right:28px">
                                <button type="button" class="sd-clear d-none" id="itemEventClear" onclick="clearItemEvent()" title="<?= t('events.clear_event') ?>"><i class="bi bi-x-lg"></i></button>
                                <input type="hidden" id="itemEventId">
                                <div class="sd-list" id="eventDropdown"></div>
                            </div>
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

<!-- Bulk Assign Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('events.assign_to_event') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small"><?= t('events.pick_event') ?></label>
                <div class="sd-wrap">
                    <input type="text" class="form-control form-control-sm" id="bulkEventName" autocomplete="off" placeholder="<?= t('events.pick_event') ?>">
                    <input type="hidden" id="bulkEventId">
                    <div class="sd-list" id="bulkEventDropdown"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="confirmBulkAssign()">
                    <i class="bi bi-link-45deg"></i> <?= t('events.assign_to_event') ?>
                </button>
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
let currentSort = 'order_date';
let currentDir = 'desc';
let currentPage = 1;
let debounceTimer = null;
let filtersData = { idols: [], types: [] };
let eventsData = [];
let msIdol, msType, msEvent;
let selectedIds = new Set();

function eventDateRange(ev) { return ev.end_date && ev.end_date !== ev.event_date ? `${ev.event_date} – ${ev.end_date}` : ev.event_date; }
function eventDisplay(ev) { return `${ev.name} (${eventDateRange(ev)})`; }

// --- Init ---
document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pDateFrom = urlParams.get('date_from');
    const pDateTo   = urlParams.get('date_to');
    const pIdol     = urlParams.get('idol');
    const pEventId  = urlParams.get('event_id');

    if (pDateFrom) $('fDateFrom').value = pDateFrom;
    if (pDateTo)   $('fDateTo').value   = pDateTo;

    await loadFilters();

    if (pIdol) msIdol.setSelected([pIdol]);
    if (pEventId) {
        const ev = eventsData.find(e => e.id == pEventId);
        if (ev) msEvent.setSelected([eventDisplay(ev)]);
    }

    loadData();
    setupSort();
    setupFilterEvents();

    // อัพเดทดรอปดาวน์เมื่อมีการเปลี่ยน idol/type/event จากแท็บอื่น
    onDataChanged(() => { loadFilters(); });

    // safety net: รีเฟรชเมื่อสลับกลับมาที่แท็บนี้ (กรณีพลาดสัญญาณ broadcast)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) loadFilters();
    });
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
    ['fDateField', 'fDateFrom', 'fDateTo'].forEach(id => {
        $(id).addEventListener('change', () => { currentPage = 1; loadData(); });
    });
    $('fPerPage').addEventListener('change', () => { currentPage = 1; loadData(); });
    // When adding/cloning, keep the filter's From date in sync with the order date
    // so the next clone references the value the user just entered.
    $('itemOrderDate').addEventListener('change', () => {
        if ($('itemId').value) return;                       // add/clone only
        if ($('fDateField').value !== 'order_date') return;  // filter must be keyed by order_date
        $('fDateFrom').value = $('itemOrderDate').value;
    });
}

// --- API ---
async function api(url, opts = {}) {
    const res = await fetch(url, opts);
    return res.json();
}

async function loadFilters() {
    [filtersData] = await Promise.all([api('api.php?action=filters')]);
    const evRes = await api('api.php?action=event_list');
    eventsData = evRes.events || [];
    msIdol = initMultiSelect('msIdol', () => filtersData.idols, () => { currentPage = 1; loadData(); });
    msType = initMultiSelect('msType', () => filtersData.types, () => { currentPage = 1; loadData(); });
    msEvent = initMultiSelect('msEvent', () => eventsData.map(eventDisplay), () => { currentPage = 1; loadData(); });
    initSearchableDropdown('itemIdol', 'idolDropdown', () => filtersData.idols);
    initSearchableDropdown('itemType', 'typeDropdown', () => filtersData.types);
    initEventDropdown('itemEventName', 'eventDropdown', 'itemEventId', 'itemEventDate', () => eventsData, 'itemEventClear');
    initEventDropdown('bulkEventName', 'bulkEventDropdown', 'bulkEventId', null, () => eventsData);
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
        addSelected(val)   {
            if (selected.includes(val)) return;
            selected = [...selected, val];
            renderBox(); renderList(); onChange(selected);
        },
    };
    return wrap._msInst;
}

async function loadData() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        per_page: $('fPerPage').value,
        sort: currentSort,
        dir: currentDir,
        search: $('fSearch').value,
        date_field: $('fDateField').value,
        date_from: $('fDateFrom').value,
        date_to: $('fDateTo').value,
    });
    if (msIdol) msIdol.getSelected().forEach(v => params.append('idol[]', v));
    if (msType) msType.getSelected().forEach(v => params.append('type[]', v));
    if (msEvent) msEvent.getSelected().forEach(disp => {
        const ev = eventsData.find(e => eventDisplay(e) === disp);
        if (ev) params.append('event_id[]', ev.id);
    });

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

function dateCell(raw, field) {
    if (!raw) return '<span class="text-muted">-</span>';
    return `<span class="date-filter-link" title="${t('items.filter_by_this_date')}" onclick="filterByDate('${field}','${raw}')">${formatDate(raw)}</span>`;
}

function filterByDate(field, date) {
    $('fDateField').value = field;
    $('fDateFrom').value = date;
    currentPage = 1;
    loadData();
}

function filterByIdol(val) {
    if (msIdol) msIdol.addSelected(val);
    currentPage = 1;
    loadData();
}

function filterByType(val) {
    if (msType) msType.addSelected(val);
    currentPage = 1;
    loadData();
}

function filterByEvent(eventId) {
    const ev = eventsData.find(e => e.id == eventId);
    if (ev && msEvent) msEvent.addSelected(eventDisplay(ev));
    currentPage = 1;
    loadData();
}

function renderEventCell(r) {
    const date = dateCell(r.event_date, 'event_date');
    if (r.event_name) {
        return `${date}<br><span class="badge filter-clickable" title="${t('items.filter_by_this_value')}" style="background:#ede9fe;color:#5b21b6;font-size:10px;font-weight:500" onclick="filterByEvent(${r.event_id})">${escHtml(r.event_name)}</span>`;
    }
    return date;
}

function renderTable(res) {
    const tbody = $('tableBody');
    if (!res.data || res.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-muted">${t('items.no_data_found')}</td></tr>`;
        return;
    }
    const offset = (res.page - 1) * res.per_page;
    tbody.innerHTML = res.data.map((r, i) => `
        <tr>
            <td><input type="checkbox" class="row-chk" data-id="${r.id}"></td>
            <td class="text-muted">${offset + i + 1}</td>
            <td>${dateCell(r.order_date, 'order_date')}</td>
            <td>${renderEventCell(r)}</td>
            <td>${escHtml(r.title)}</td>
            <td><span class="badge badge-idol filter-clickable" title="${t('items.filter_by_this_value')}" onclick="filterByIdol('${escJs(r.idol)}')">${escHtml(r.idol)}</span></td>
            <td><span class="badge badge-type filter-clickable" title="${t('items.filter_by_this_value')}" onclick="filterByType('${escJs(r.type)}')">${escHtml(r.type)}</span></td>
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
    attachCheckboxListeners();
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
    $('itemEventId').value = '';
    $('itemEventName').value = '';
    $('itemEventClear').classList.add('d-none');
    $('itemIdolHint').style.display = 'none';
    $('itemIdolHint').innerHTML = '';
    $('itemForm').reset();
    const filterFrom = $('fDateFrom').value;
    if (filterFrom) {
        if ($('fDateField').value === 'event_date') {
            $('itemEventDate').value = filterFrom;
        } else {
            $('itemOrderDate').value = filterFrom;
        }
    }
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
    $('itemEventId').value = d.event_id || '';
    $('itemEventName').value = d.event_id && d.event_name ? `${d.event_name} (${d.event_date})` : '';
    $('itemEventClear').classList.toggle('d-none', !d.event_id);
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
    const filterFrom = $('fDateFrom').value;
    const dateField = $('fDateField').value;
    const orderDate = (filterFrom && dateField === 'order_date') ? filterFrom : today;
    const eventDate = d.event_date || today;
    $('itemId').value = '';
    $('itemOrderDate').value = orderDate;
    $('itemEventDate').value = eventDate;
    $('itemEventId').value = d.event_id || '';
    $('itemEventName').value = d.event_id && d.event_name ? `${d.event_name} (${eventDate})` : '';
    $('itemEventClear').classList.toggle('d-none', !d.event_id);
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
    const eventId = $('itemEventId').value;
    if (eventId) body.append('event_id', eventId);
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
    $('fDateField').value = 'order_date';
    $('fDateFrom').value = '';
    $('fDateTo').value = '';
    if (msIdol) msIdol.clear();
    if (msType) msType.clear();
    if (msEvent) msEvent.clear();
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
    if (msEvent) msEvent.getSelected().forEach(disp => {
        const ev = eventsData.find(e => eventDisplay(e) === disp);
        if (ev) params.append('event_id[]', ev.id);
    });
    window.location.href = 'export.php?' + params.toString();
}

// --- Event Dropdown ---
function initEventDropdown(inputId, listId, hiddenId, dateFillId, getItems, clearBtnId) {
    const input  = document.getElementById(inputId);
    const list   = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);
    const clearBtn = clearBtnId ? document.getElementById(clearBtnId) : null;
    if (!input || input._edInit) return;
    input._edInit = true;

    const toggleClear = () => { if (clearBtn) clearBtn.classList.toggle('d-none', !hidden.value); };

    input.addEventListener('input', () => { if (hidden) hidden.value = ''; toggleClear(); });

    function render() {
        const q = input.value.toLowerCase();
        const items = getItems().filter(ev => !q || ev.name.toLowerCase().includes(q) || ev.event_date.includes(q) || (ev.end_date || '').includes(q));
        const noEvHtml = `<div class="sd-item" data-val="" data-name="" data-date="" style="color:#9ca3af;font-style:italic">${t('events.no_event_ph')}</div>`;
        if (items.length === 0 && q) {
            list.innerHTML = noEvHtml + `<div class="sd-empty">${t('items.no_match')}</div>`;
        } else {
            list.innerHTML = noEvHtml + items.map(ev =>
                `<div class="sd-item" data-val="${ev.id}" data-name="${escHtml(ev.name)}" data-date="${ev.event_date}">
                    <strong>${escHtml(ev.name)}</strong>
                    <span class="text-muted ms-2" style="font-size:12px">${eventDateRange(ev)}</span>
                </div>`
            ).join('');
        }
        list.classList.add('show');
    }

    list.addEventListener('mousedown', e => {
        const item = e.target.closest('.sd-item');
        if (!item) return;
        e.preventDefault();
        const val  = item.dataset.val;
        const name = item.dataset.name || '';
        const date = item.dataset.date || '';
        if (hidden) hidden.value = val;
        input.value = val ? `${name} (${date})` : '';
        if (dateFillId && date) {
            const df = document.getElementById(dateFillId);
            if (df) df.value = date;
        }
        toggleClear();
        list.classList.remove('show');
    });

    input.addEventListener('focus', render);
    input.addEventListener('input', render);
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !list.contains(e.target)) list.classList.remove('show');
    });
}

// Clear the event link on the item form (keeps the item's own Event Date).
function clearItemEvent() {
    $('itemEventId').value = '';
    $('itemEventName').value = '';
    $('itemEventClear').classList.add('d-none');
    $('eventDropdown').classList.remove('show');
}

// --- Checkbox / Bulk Select ---
function attachCheckboxListeners() {
    document.querySelectorAll('.row-chk').forEach(chk => {
        chk.checked = selectedIds.has(parseInt(chk.dataset.id, 10));
        chk.addEventListener('change', () => {
            const id = parseInt(chk.dataset.id, 10);
            chk.checked ? selectedIds.add(id) : selectedIds.delete(id);
            updateBulkBar();
        });
    });
    const chkAll = $('chkAll');
    if (chkAll) {
        chkAll.checked = false;
        chkAll.onchange = () => {
            document.querySelectorAll('.row-chk').forEach(c => {
                const id = parseInt(c.dataset.id, 10);
                if (chkAll.checked) { selectedIds.add(id); c.checked = true; }
                else { selectedIds.delete(id); c.checked = false; }
            });
            updateBulkBar();
        };
    }
}

function updateBulkBar() {
    const bar = $('bulkBar');
    if (!bar) return;
    if (selectedIds.size > 0) {
        $('bulkCount').textContent = t('events.n_selected', { n: selectedIds.size });
        bar.classList.remove('d-none');
    } else {
        bar.classList.add('d-none');
    }
}

function clearSelection() {
    selectedIds.clear();
    document.querySelectorAll('.row-chk').forEach(c => c.checked = false);
    const chkAll = $('chkAll');
    if (chkAll) chkAll.checked = false;
    updateBulkBar();
}

function showBulkAssignModal() {
    $('bulkEventId').value = '';
    $('bulkEventName').value = '';
    new bootstrap.Modal($('bulkAssignModal')).show();
}

async function confirmBulkAssign() {
    const eventId = $('bulkEventId').value;
    if (!eventId) { alert(t('events.pick_event_required')); return; }
    const ids = [...selectedIds];
    const body = new FormData();
    body.append('action', 'event_bulk_assign');
    body.append('event_id', eventId);
    ids.forEach(id => body.append('ids[]', id));
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('bulkAssignModal')).hide();
    clearSelection();
    loadData();
}

async function confirmBulkUnassign() {
    if (!selectedIds.size) return;
    if (!confirm(t('events.bulk_unassign_confirm', { n: selectedIds.size }))) return;
    const body = new FormData();
    body.append('action', 'event_bulk_assign');
    body.append('event_id', ''); // empty event_id = unassign (set NULL)
    [...selectedIds].forEach(id => body.append('ids[]', id));
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    clearSelection();
    loadData();
}

// --- Helpers ---
function showLoading(show) { $('loading').style.display = show ? 'flex' : 'none'; }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
