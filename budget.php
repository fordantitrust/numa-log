<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('budget.page_title') ?> - Numa Log</title>
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
        .nav-pills .nav-link.active { background: var(--primary); }
        .nav-pills .nav-link { color: var(--primary); }
        .table th { font-size: 12px; text-transform: uppercase; color: #6b7280; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="month"], input[type="number"],
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

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-piggy-bank"></i> <?= t('budget.title') ?> <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div class="d-flex align-items-center">
            <?= langSwitcher() ?>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-speedometer2"></i><span class="d-none d-sm-inline"> <?= t('nav.dashboard') ?></span></a>
            <a href="items.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-list-ul"></i><span class="d-none d-sm-inline"> <?= t('nav.items') ?></span></a>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline"> <?= t('nav.report') ?></span></a>
            <a href="idols.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-people"></i><span class="d-none d-sm-inline"> <?= t('nav.idols') ?></span></a>
            <a href="types.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-tags"></i><span class="d-none d-sm-inline"> <?= t('nav.types') ?></span></a>
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
    <div class="mb-3">
        <h5 class="mb-0"><i class="bi bi-piggy-bank text-primary"></i> <?= t('budget.page_title') ?></h5>
        <div class="stat-muted"><?= t('budget.subtitle') ?></div>
    </div>

    <!-- Tabs: Progress (report) vs Manage (definitions) -->
    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#paneReport">
                <i class="bi bi-bar-chart-line"></i> <?= t('budget.tab_report') ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#paneManage">
                <i class="bi bi-sliders"></i> <?= t('budget.tab_manage') ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#paneInsights">
                <i class="bi bi-graph-up-arrow"></i> <?= t('budget.tab_insights') ?>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Progress / Report -->
        <div class="tab-pane fade show active" id="paneReport">
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span><strong><i class="bi bi-graph-up-arrow"></i> <span id="reportPeriod"></span></strong>
                                <span class="stat-muted ms-1 d-none d-sm-inline"><?= t('budget.report_hint') ?></span></span>
                            <span class="d-flex align-items-center gap-2">
                                <label class="small text-muted mb-0"><?= t('budget.month') ?></label>
                                <input type="month" class="form-control form-control-sm" id="monthSelect" style="width:auto">
                                <button class="btn btn-outline-primary btn-sm" onclick="addMonth()" title="<?= t('budget.add_for_month') ?>"><i class="bi bi-plus-lg"></i></button>
                            </span>
                        </div>
                        <div class="card-body" id="budgetReport">
                            <div class="text-center text-muted py-4"><?= t('common.loading') ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header py-2"><strong><i class="bi bi-calculator"></i> <?= t('budget.summary') ?></strong> <span class="text-muted small" id="summaryPeriod"></span></div>
                        <div class="card-body py-2" id="statsPanel">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage definitions -->
        <div class="tab-pane fade" id="paneManage">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><strong><i class="bi bi-sliders"></i> <?= t('budget.tab_manage') ?></strong>
                        <span class="stat-muted ms-1 d-none d-sm-inline"><?= t('budget.manage_hint') ?></span></span>
                    <button class="btn btn-primary btn-sm" onclick="showForm()"><i class="bi bi-plus-lg"></i> <?= t('budget.add') ?></button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th><?= t('budget.scope') ?></th>
                                    <th><?= t('budget.target') ?></th>
                                    <th class="text-end"><?= t('budget.col_limit') ?></th>
                                    <th class="text-center"><?= t('budget.warn_pct') ?></th>
                                    <th class="text-center"><?= t('budget.danger_pct') ?></th>
                                    <th><?= t('budget.note') ?></th>
                                    <th style="width:80px"></th>
                                </tr>
                            </thead>
                            <tbody id="budgetTable">
                                <tr><td colspan="8" class="text-center text-muted py-4"><?= t('common.loading') ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insights: multi-month spend-vs-budget analytics -->
        <div class="tab-pane fade" id="paneInsights">
            <div class="mb-2 stat-muted"><?= t('budget.insights_hint') ?></div>
            <div id="budgetInsightsRoot"></div>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle"><?= t('budget.add') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="budgetForm">
                    <input type="hidden" id="bId">
                    <input type="hidden" id="bPeriod">
                    <div id="periodContext" class="alert alert-light border py-1 px-2 small mb-2"></div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('budget.scope') ?></label>
                        <select class="form-select form-select-sm" id="bScope" onchange="onScopeChange()">
                            <option value="overall"><?= t('budget.scope_overall') ?></option>
                            <option value="type"><?= t('budget.scope_type') ?></option>
                            <option value="company"><?= t('budget.scope_company') ?></option>
                            <option value="group"><?= t('budget.scope_group') ?></option>
                            <option value="member"><?= t('budget.scope_member') ?></option>
                        </select>
                    </div>
                    <div class="mb-2 d-none" id="targetWrap">
                        <label class="form-label small"><?= t('budget.target') ?></label>
                        <input type="text" class="form-control form-control-sm mb-1 d-none" id="bSearch" placeholder="<?= t('budget.pick_entity') ?>" oninput="onSearchInput()">
                        <select class="form-select form-select-sm" id="bRef"></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('budget.amount') ?></label>
                        <input type="number" min="0" step="1" class="form-control form-control-sm" id="bAmount" required>
                    </div>
                    <div class="row g-2 mb-1">
                        <div class="col-6">
                            <label class="form-label small text-warning"><?= t('budget.warn_pct') ?></label>
                            <input type="number" min="1" max="1000" class="form-control form-control-sm" id="bWarn" value="80">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-danger"><?= t('budget.danger_pct') ?></label>
                            <input type="number" min="1" max="1000" class="form-control form-control-sm" id="bDanger" value="100">
                        </div>
                    </div>
                    <div class="form-text mb-2"><?= t('budget.threshold_hint') ?></div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('budget.note') ?></label>
                        <input type="text" class="form-control form-control-sm" id="bNote">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveBudget()"><i class="bi bi-check-lg"></i> <?= t('common.save') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="delTitle"><?= t('budget.confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><span id="delBodyLabel"><?= t('budget.delete_q') ?></span> <strong id="delName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash"></i> <?= t('common.delete') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js"></script>
<script src="assets/budget.js"></script>
<script>
const $ = id => document.getElementById(id);
let budgets = [];   // effective budgets for the selected month (Progress tab)
let defaults = [];  // recurring default definitions (Manage tab)
let allTypes = [];
let searchTimer = null;
let pendingDelete = null;

document.addEventListener('DOMContentLoaded', () => {
    $('monthSelect').value = new Date().toLocaleDateString('en-CA').slice(0, 7);
    $('monthSelect').addEventListener('change', loadBudgets);
    loadBudgets();

    // Lazy-mount the Insights analytics the first time its tab is shown.
    document.querySelector('[data-bs-target="#paneInsights"]')
        .addEventListener('shown.bs.tab', () => Budget.Insights.mount($('budgetInsightsRoot')), { once: true });
});

function currentMonth() { return $('monthSelect').value || new Date().toLocaleDateString('en-CA').slice(0, 7); }

// Human-readable label for a YYYY-MM value, e.g. "June 2026" / "มิถุนายน 2026".
function fmtMonthLabel(ym) {
    if (!ym) return '';
    const [y, m] = ym.split('-').map(Number);
    const months = (window.I18N && window.I18N['date.months_long']) || [];
    return (months[m - 1] || String(m)) + ' ' + y;
}

function updatePeriodLabels() {
    const lbl = fmtMonthLabel(currentMonth());
    $('reportPeriod').textContent = lbl;
    $('summaryPeriod').textContent = lbl;
}

async function loadBudgets() {
    updatePeriodLabels();
    const [eff, defs] = await Promise.all([
        fetch('api.php?action=budget_progress&month=' + currentMonth()).then(r => r.json()),
        fetch('api.php?action=budget_list&mode=defaults').then(r => r.json()),
    ]);
    if (eff.error || defs.error) { $('budgetReport').innerHTML = `<div class="text-danger">${t('budget.err_load')}</div>`; return; }
    budgets = eff.budgets || [];
    defaults = defs.budgets || [];
    renderReport();
    renderStats();
    renderTable();
}

// Progress tab — effective budgets for the selected month, with per-month controls.
function renderReport() {
    if (budgets.length === 0) {
        $('budgetReport').innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-piggy-bank" style="font-size:2rem;opacity:.4"></i>
                <div class="mt-2">${t('budget.none')}</div>
                <button class="btn btn-outline-primary btn-sm mt-2" onclick="gotoManage()"><i class="bi bi-plus-lg"></i> ${t('budget.none_cta')}</button>
            </div>`;
        return;
    }
    $('budgetReport').innerHTML = budgets.map((b, i) => {
        const tag = b.is_override
            ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-weight:500">${t('budget.custom_badge')}</span>`
            : `<span class="badge bg-light text-secondary border ms-1" style="font-weight:500">${t('budget.default_badge')}</span>`;
        let actions = `<button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="setMonth(${i})" title="${t('budget.set_this_month')}"><i class="bi bi-pencil"></i></button>`;
        if (b.is_override && b.has_default)
            actions += ` <button class="btn btn-outline-secondary btn-sm px-1 py-0" onclick="resetMonth(${i})" title="${t('budget.reset_default')}"><i class="bi bi-arrow-counterclockwise"></i></button>`;
        return Budget.renderBudgetBar(b, { tag, actions });
    }).join('');
}

// Manage tab — recurring default definitions with edit/delete.
function renderTable() {
    if (defaults.length === 0) {
        $('budgetTable').innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">${t('budget.none')}</td></tr>`;
        return;
    }
    const SCOPE_BADGE = { overall: 'bg-dark', type: 'bg-info', group: 'bg-primary', company: 'bg-danger', member: 'bg-warning text-dark' };
    $('budgetTable').innerHTML = defaults.map((b, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td><span class="badge ${SCOPE_BADGE[b.scope_type] || 'bg-secondary'}" style="font-weight:500">${Budget.escHtml(Budget.scopeTypeLabel(b.scope_type))}</span></td>
            <td><strong>${b.scope_type === 'overall' ? '—' : Budget.escHtml(Budget.scopeLabel(b))}</strong></td>
            <td class="text-end">${Budget.fmtMoney(b.amount)}</td>
            <td class="text-center text-warning fw-semibold">${b.warn_pct}%</td>
            <td class="text-center text-danger fw-semibold">${b.danger_pct}%</td>
            <td class="stat-muted">${Budget.escHtml(b.note || '')}</td>
            <td>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editBudget(${b.id})" title="${t('common.edit')}"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteBudget(${b.id})" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`).join('');
}

// Switch to the Manage tab, then open the add form (recurring default).
function gotoManage() {
    document.querySelector('[data-bs-target="#paneManage"]').click();
    showForm();
}

function renderStats() {
    const totalLimit = budgets.reduce((s, b) => s + b.amount, 0);
    const totalSpent = budgets.reduce((s, b) => s + b.spent, 0);
    const overCount = budgets.filter(b => b.status === 'over').length;
    $('statsPanel').innerHTML = `
        <div>${t('budget.stat_total')} <strong>${budgets.length}</strong></div>
        <div>${t('budget.stat_limit')} <strong>${Budget.fmtMoney(totalLimit)}</strong></div>
        <div>${t('budget.stat_spent')} <strong>${Budget.fmtMoney(totalSpent)}</strong></div>
        <div class="mt-2 pt-2 border-top">${t('budget.stat_over')} <strong class="${overCount ? 'text-danger' : ''}">${overCount}</strong></div>`;
}

// --- Form ---
// Unified opener. period='' → recurring default; period='YYYY-MM' → month override.
async function openBudgetForm(o) {
    $('budgetForm').reset();
    $('bId').value = o.id || '';
    $('bPeriod').value = o.period || '';
    $('bScope').value = o.scopeType || 'overall';
    $('bAmount').value = (o.amount ?? '');
    $('bWarn').value = o.warn ?? 80;
    $('bDanger').value = o.danger ?? 100;
    $('bNote').value = o.note || '';
    $('formTitle').textContent = o.title || t('budget.add');
    $('periodContext').textContent = o.period
        ? t('budget.ctx_for_month', { month: fmtMonthLabel(o.period) })
        : t('budget.ctx_recurring');
    await onScopeChange();
    if (o.scopeType === 'type' && o.refName) {
        $('bRef').value = o.refName;
    } else if (o.refId) {
        ensureOption($('bRef'), o.refId, o.refName || '');
        $('bRef').value = String(o.refId);
    }
    setScopeLocked(!!o.lockScope);
    new bootstrap.Modal($('formModal')).show();
}

function setScopeLocked(locked) {
    $('bScope').disabled = locked;
    $('bRef').disabled = locked;
    $('bSearch').disabled = locked;
}

// Manage: add a recurring default.
function showForm() {
    openBudgetForm({ title: t('budget.add'), period: '', lockScope: false });
}

// Manage: edit a recurring default.
function editBudget(id) {
    const b = defaults.find(x => x.id === id);
    if (!b) return;
    openBudgetForm({
        id: b.id, scopeType: b.scope_type, refId: b.scope_ref_id,
        refName: b.scope_type === 'type' ? b.scope_ref_name : b.label,
        amount: b.amount, warn: b.warn_pct, danger: b.danger_pct, note: b.note,
        period: '', lockScope: false, title: t('budget.edit_prefix', { name: Budget.scopeLabel(b) }),
    });
}

// Progress: add a budget that applies only to the selected month.
function addMonth() {
    openBudgetForm({ title: t('budget.add_for_month'), period: currentMonth(), lockScope: false });
}

// Progress: set/override the amount for the selected month on an existing scope.
function setMonth(i) {
    const b = budgets[i];
    if (!b) return;
    openBudgetForm({
        id: b.override_id || '', scopeType: b.scope_type, refId: b.scope_ref_id,
        refName: b.scope_type === 'type' ? b.scope_ref_name : b.label,
        amount: b.amount, warn: b.warn_pct, danger: b.danger_pct, note: b.note,
        period: currentMonth(), lockScope: true,
        title: t('budget.set_this_month') + ' · ' + fmtMonthLabel(currentMonth()),
    });
}

function ensureOption(sel, value, label) {
    if (![...sel.options].some(o => o.value === String(value))) {
        const o = document.createElement('option');
        o.value = String(value); o.textContent = label;
        sel.appendChild(o);
    }
}

async function onScopeChange() {
    const scope = $('bScope').value;
    const wrap = $('targetWrap'), search = $('bSearch'), ref = $('bRef');
    if (scope === 'overall') {
        wrap.classList.add('d-none');
        ref.innerHTML = '';
        return;
    }
    wrap.classList.remove('d-none');
    if (scope === 'type') {
        search.classList.add('d-none');
        await loadTypes();
        ref.innerHTML = `<option value="">${t('budget.pick_type')}</option>` +
            allTypes.map(ty => `<option value="${Budget.escHtml(ty.name)}">${Budget.escHtml(ty.name)}</option>`).join('');
    } else {
        search.classList.remove('d-none');
        search.value = '';
        ref.innerHTML = '';
        searchEntities('');
    }
}

async function loadTypes() {
    if (allTypes.length) return;
    const res = await fetch('api.php?action=type_list').then(r => r.json());
    allTypes = res.types || [];
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => searchEntities($('bSearch').value.trim()), 250);
}

async function searchEntities(q) {
    const scope = $('bScope').value; // group | company | member
    const res = await fetch('api.php?action=idol_search&category=' + scope + '&q=' + encodeURIComponent(q)).then(r => r.json());
    const rows = res.data || [];
    $('bRef').innerHTML = `<option value="">${t('budget.pick_entity')}</option>` +
        rows.map(r => `<option value="${r.id}">${Budget.escHtml(r.display)}</option>`).join('');
}

async function saveBudget() {
    const form = $('budgetForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const scope = $('bScope').value;
    const warn = parseInt($('bWarn').value, 10), danger = parseInt($('bDanger').value, 10);
    if (!(warn >= 1 && danger >= 1 && warn <= danger)) { alert(t('budget.threshold_hint')); return; }

    const body = new FormData();
    body.append('action', 'budget_save');
    if ($('bId').value) body.append('id', $('bId').value);
    body.append('scope_type', scope);
    body.append('amount', $('bAmount').value);
    body.append('warn_pct', warn);
    body.append('danger_pct', danger);
    body.append('note', $('bNote').value);
    if ($('bPeriod').value) body.append('period', $('bPeriod').value);
    if (scope === 'type') body.append('scope_ref_name', $('bRef').value);
    else if (scope !== 'overall') body.append('scope_ref_id', $('bRef').value);

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadBudgets();
}

// Manage: delete a recurring default.
function deleteBudget(id) {
    const b = defaults.find(x => x.id === id);
    pendingDelete = id;
    $('delTitle').textContent = t('budget.confirm_delete');
    $('delBodyLabel').textContent = t('budget.delete_q');
    $('delName').textContent = b ? Budget.scopeLabel(b) : '';
    new bootstrap.Modal($('deleteModal')).show();
}

// Progress: reset a month override back to the recurring default (delete the override).
function resetMonth(i) {
    const b = budgets[i];
    if (!b || !b.override_id) return;
    pendingDelete = b.override_id;
    $('delTitle').textContent = t('budget.reset_confirm');
    $('delBodyLabel').textContent = t('budget.reset_q');
    $('delName').textContent = Budget.scopeLabel(b);
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    if (!pendingDelete) return;
    const body = new FormData();
    body.append('action', 'budget_delete');
    body.append('id', pendingDelete);
    await fetch('api.php', { method: 'POST', body });
    pendingDelete = null;
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadBudgets();
}
</script>
</body>
</html>
