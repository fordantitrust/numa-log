<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('nav.dashboard') ?> - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; font-size: 14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .summary-card { background: linear-gradient(135deg, var(--primary), #a78bfa); color: white; }
        .summary-card .kpi-value { font-weight: 700; font-size: 1.6rem; line-height: 1.2; }
        .table th { background: #f9fafb; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .rank-1 { color: #eab308; font-weight: 700; }
        .rank-2 { color: #9ca3af; font-weight: 700; }
        .rank-3 { color: #b45309; font-weight: 700; }
        .chart-container { position: relative; height: 320px; }
        .chart-container.sm { height: 280px; }
        .progress-bar-custom { height: 6px; border-radius: 3px; background: #e5e7eb; }
        .progress-bar-custom .fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--primary), #a78bfa); }
        .badge-type { background: #fce7f3; color: #9d174d; }
        .badge-idol { background: #ddd6fe; color: #5b21b6; }
        .delta-up { color: #fecaca; }
        .delta-down { color: #bbf7d0; }
        @media (max-width: 767.98px) { .chart-container, .chart-container.sm { height: 240px; } }
        @media (max-width: 575.98px) {
            select { font-size: 16px !important; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
            .summary-card .kpi-value { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<?php $navActive = 'dashboard'; $navIcon = 'bi-speedometer2'; $navTitle = t('nav.dashboard'); require __DIR__ . '/navbar.php'; ?>

<div class="container-fluid py-3">
    <!-- Period selector -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> <?= t('dashboard.overview_title') ?></h5>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0 d-none" id="excludedToggleWrap">
                <input class="form-check-input" type="checkbox" id="chkIncludeExcluded">
                <label class="form-check-label small text-muted" for="chkIncludeExcluded"><?= t('excluded.toggle') ?></label>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0"><?= t('dashboard.period') ?></label>
                <select class="form-select form-select-sm" id="periodSelect" style="width:auto">
                    <option value="all"><?= t('dashboard.period_all') ?></option>
                    <option value="last12"><?= t('dashboard.period_last12') ?></option>
                </select>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-cash-stack"></i> <?= t('dashboard.kpi_total_spent') ?></div>
                <div class="kpi-value" id="kpiSpent">&#3647;0</div>
                <div class="small opacity-75" id="kpiSpentSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-box-seam"></i> <?= t('dashboard.kpi_items') ?></div>
                <div class="kpi-value" id="kpiItems">0</div>
                <div class="small opacity-75" id="kpiItemsSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-calendar-month"></i> <?= t('dashboard.kpi_avg') ?></div>
                <div class="kpi-value" id="kpiAvg">&#3647;0</div>
                <div class="small opacity-75" id="kpiAvgSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-graph-up"></i> <?= t('dashboard.kpi_latest') ?></div>
                <div class="kpi-value" id="kpiLatest">&#3647;0</div>
                <div class="small opacity-75" id="kpiLatestSub">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Budget progress (current month) -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0"><i class="bi bi-piggy-bank text-primary"></i> <?= t('budget.dashboard_title') ?></h6>
                    <a href="budget.php" class="btn btn-outline-primary btn-sm py-0"><i class="bi bi-gear"></i> <?= t('common.manage') ?></a>
                </div>
                <div id="budgetCard"><div class="text-muted small"><?= t('common.loading') ?></div></div>
            </div>
        </div>
    </div>

    <!-- Monthly trend + Type doughnut -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3"><?= t('dashboard.monthly_trend') ?></h6>
                <div class="chart-container">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3"><?= t('dashboard.by_type_share') ?></h6>
                <div class="chart-container sm">
                    <canvas id="chartType"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top members + Top groups + Company doughnut -->
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-person-hearts"></i> <?= t('dashboard.top_members') ?></strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th><?= t('common.member') ?></th><th class="text-end"><?= t('dashboard.amount_baht') ?></th></tr></thead>
                        <tbody id="topMembers"><tr><td colspan="3" class="text-center text-muted py-3"><?= t('common.loading') ?></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-diagram-3"></i> <?= t('dashboard.top_groups') ?></strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th><?= t('dashboard.group_label') ?></th><th class="text-end"><?= t('dashboard.amount_baht') ?></th></tr></thead>
                        <tbody id="topGroups"><tr><td colspan="3" class="text-center text-muted py-3"><?= t('common.loading') ?></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3"><?= t('dashboard.by_company_share') ?></h6>
                <div class="chart-container sm">
                    <canvas id="chartCompany"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js?v=<?= APP_VERSION ?>"></script>
<script src="assets/budget.js?v=<?= APP_VERSION ?>"></script>
<script>
const $ = id => document.getElementById(id);
let chartMonthly, chartType, chartCompany;

// Shared with report.php and items.php so the whole app agrees on one mode. The
// banner/footnote text differs between the two states so it is always clear which
// mode a page is in, even after toggling somewhere else.
let includeExcluded = localStorage.getItem('numalog.includeExcluded') === '1';

const PALETTE = ['#7c3aed','#a78bfa','#ec4899','#f59e0b','#0891b2','#10b981','#ef4444','#6366f1','#14b8a6','#f97316'];

document.addEventListener('DOMContentLoaded', () => {
    $('periodSelect').addEventListener('change', loadDashboard);
    $('chkIncludeExcluded').checked = includeExcluded;
    $('chkIncludeExcluded').addEventListener('change', e => {
        includeExcluded = e.target.checked;
        localStorage.setItem('numalog.includeExcluded', includeExcluded ? '1' : '0');
        loadDashboard();
        loadBudgetCard();
    });
    loadDashboard();
    loadBudgetCard();
});

// Budgets are tied to the current calendar month, independent of the period selector.
async function loadBudgetCard() {
    const month = new Date().toLocaleDateString('en-CA').slice(0, 7);
    const url = 'api.php?action=budget_progress&month=' + month + (includeExcluded ? '&include_excluded=1' : '');
    const res = await fetch(url, { cache: 'no-store' }).then(r => r.json());
    const el = $('budgetCard');
    if (!res || res.error) { el.innerHTML = ''; return; }
    const budgets = res.budgets || [];
    if (budgets.length === 0) {
        el.innerHTML = `<div class="text-muted small"><a href="budget.php" class="text-decoration-none">${t('budget.dashboard_none')}</a></div>`;
        return;
    }
    // Show overall first, then any near/over budgets (limit to keep the card compact).
    const overall = budgets.filter(b => b.scope_type === 'overall');
    const alerts  = budgets.filter(b => b.scope_type !== 'overall' && b.status !== 'ok');
    const shown   = overall.concat(alerts).slice(0, 6);
    const list    = shown.length ? shown : budgets.slice(0, 3);
    el.innerHTML = list.map(b => Budget.renderBudgetBar(b)).join('');
}

function currentRange() {
    const v = $('periodSelect').value;
    if (v === 'all') return { from: '', to: '' };
    if (v === 'last12') {
        const d = new Date();
        const to = d.toLocaleDateString('en-CA');
        d.setMonth(d.getMonth() - 11);
        d.setDate(1);
        return { from: d.toLocaleDateString('en-CA'), to };
    }
    // year value (e.g. "2025")
    return { from: `${v}-01-01`, to: `${v}-12-31` };
}

async function loadDashboard() {
    const { from, to } = currentRange();
    const params = new URLSearchParams({ action: 'report_dashboard', date_from: from, date_to: to });
    if (includeExcluded) params.set('include_excluded', '1');
    const res = await fetch('api.php?' + params, { cache: 'no-store' }).then(r => r.json());
    if (res.error) { alert(res.error); return; }

    // Only offer the toggle once a type is actually flagged — users who never use
    // the feature should see no extra chrome.
    const hasExcluded = res.excluded && res.excluded.items > 0;
    $('excludedToggleWrap').classList.toggle('d-none', !hasExcluded && !includeExcluded);

    populateYears(res.years);
    renderKpis(res.kpis, res.excluded);
    renderMonthly(res.monthly);
    renderType(res.by_type);
    renderCompany(res.by_company);
    renderTop('topMembers', res.top_members, r => r.display);
    renderTop('topGroups', res.top_groups, r => r.name);
}

function populateYears(years) {
    const sel = $('periodSelect');
    if (sel._yearsDone || !Array.isArray(years)) return;
    years.forEach(y => {
        const o = document.createElement('option');
        o.value = y; o.textContent = t('dashboard.year', { y });
        sel.appendChild(o);
    });
    sel._yearsDone = true;
}

// --- Formatters ---
function fmtMoney(n) { return '฿' + new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(n || 0); }
function fmtInt(n)   { return new Intl.NumberFormat('th-TH').format(n || 0); }
function escHtml(s)  { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

function renderKpis(k, excluded) {
    $('kpiSpent').textContent  = fmtMoney(k.total_spent);
    const spentSub = t('dashboard.kpi_spent_sub', { n: k.active_months || 0 });
    // State the excluded amount rather than let it vanish. When the toggle is on the
    // amount is already inside total_spent, so only annotate in the filtered mode.
    $('kpiSpentSub').innerHTML = (!includeExcluded && excluded && excluded.items > 0)
        ? `${spentSub} · <span class="text-warning-emphasis" title="${t('excluded.tooltip')}"><i class="bi bi-eye-slash"></i> ${t('excluded.kpi_note', { amount: fmtMoney(excluded.total_price) })}</span>`
        : spentSub;

    $('kpiItems').textContent  = fmtInt(k.total_items);
    $('kpiItemsSub').innerHTML = t('dashboard.kpi_items_sub', { n: fmtInt(k.total_qty) });

    $('kpiAvg').textContent    = fmtMoney(k.avg_per_month);
    $('kpiAvgSub').innerHTML   = k.top_type ? t('dashboard.kpi_top_type', { type: escHtml(k.top_type) }) : '&nbsp;';

    $('kpiLatest').textContent = fmtMoney(k.latest_month_spent);
    if (k.mom_change_pct === null || k.mom_change_pct === undefined) {
        $('kpiLatestSub').innerHTML = k.latest_month ? escHtml(k.latest_month) : '&nbsp;';
    } else {
        const up = k.mom_change_pct >= 0;
        const cls = up ? 'delta-up' : 'delta-down';
        const arrow = up ? '▲' : '▼';
        $('kpiLatestSub').innerHTML =
            `<span class="${cls}">${arrow} ${Math.abs(k.mom_change_pct).toFixed(1)}%</span> ${t('dashboard.vs_prev_month')}`;
    }
}

function renderMonthly(data) {
    const labels = data.map(d => d.month);
    const values = data.map(d => d.total_price);
    if (chartMonthly) chartMonthly.destroy();
    chartMonthly = new Chart($('chartMonthly'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: t('common.spending_baht'),
                data: values,
                backgroundColor: 'rgba(124,58,237,.7)',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => fmtMoney(c.parsed.y) } },
            },
            scales: { y: { beginAtZero: true, ticks: { callback: v => fmtMoney(v) } } },
        },
    });
}

// Collapse a sorted-desc list to top N slices + an "อื่นๆ" bucket so the
// doughnut stays readable when there are many categories.
function topNWithOthers(data, labelKey, n = 5) {
    const rows = data.map(d => ({ label: d[labelKey] || t('common.unspecified'), value: d.total_price }));
    if (rows.length <= n) return rows;
    const head = rows.slice(0, n);
    const restTotal = rows.slice(n).reduce((s, r) => s + r.value, 0);
    if (restTotal > 0) head.push({ label: t('dashboard.others', { n: rows.length - n }), value: restTotal });
    return head;
}

function renderDoughnut(existing, canvasId, data, labelKey) {
    const slices = topNWithOthers(data, labelKey);
    const labels = slices.map(s => s.label);
    const values = slices.map(s => s.value);
    if (existing) existing.destroy();
    return new Chart($(canvasId), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: labels.map((_, i) => PALETTE[i % PALETTE.length]) }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: c => `${c.label}: ${fmtMoney(c.parsed)}` } },
            },
        },
    });
}

function renderType(data)    { chartType    = renderDoughnut(chartType,    'chartType',    data, 'type'); }
function renderCompany(data) { chartCompany = renderDoughnut(chartCompany, 'chartCompany', data, 'name'); }

function renderTop(tbodyId, rows, nameFn) {
    const tbody = $(tbodyId);
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">${t('common.no_data')}</td></tr>`;
        return;
    }
    const max = Math.max(...rows.map(r => r.total_price), 1);
    tbody.innerHTML = rows.map((r, i) => {
        const rankCls = i < 3 ? `rank-${i + 1}` : '';
        const pct = (r.total_price / max) * 100;
        return `<tr>
            <td class="${rankCls}">${i + 1}</td>
            <td>
                ${escHtml(nameFn(r))}
                <div class="progress-bar-custom mt-1"><div class="fill" style="width:${pct}%"></div></div>
            </td>
            <td class="text-end fw-semibold">${fmtMoney(r.total_price)}</td>
        </tr>`;
    }).join('');
}
</script>
</body>
</html>
