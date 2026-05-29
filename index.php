<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Numa Log</title>
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

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-speedometer2"></i> Dashboard <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="items.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-list-ul"></i><span class="d-none d-sm-inline"> Items</span></a>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline"> Report</span></a>
            <a href="idols.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-people"></i><span class="d-none d-sm-inline"> Idols</span></a>
            <a href="types.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-tags"></i><span class="d-none d-sm-inline"> Types</span></a>
            <?php $u = currentUser(); if (AUTH_ENABLED && $u): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"> <?= htmlspecialchars($u['display_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($u['role'] === 'admin'): ?>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item" href="backup.php"><i class="bi bi-database"></i> Backup</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="help.php"><i class="bi bi-question-circle"></i> Help</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <!-- Period selector -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> ภาพรวมการใช้จ่าย</h5>
        <div class="d-flex align-items-center gap-2">
            <label class="small text-muted mb-0">ช่วงเวลา:</label>
            <select class="form-select form-select-sm" id="periodSelect" style="width:auto">
                <option value="all">ทั้งหมด (All time)</option>
                <option value="last12">12 เดือนล่าสุด</option>
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-cash-stack"></i> ยอดใช้จ่ายรวม</div>
                <div class="kpi-value" id="kpiSpent">&#3647;0</div>
                <div class="small opacity-75" id="kpiSpentSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-box-seam"></i> จำนวนรายการ / ชิ้น</div>
                <div class="kpi-value" id="kpiItems">0</div>
                <div class="small opacity-75" id="kpiItemsSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-calendar-month"></i> เฉลี่ยต่อเดือน</div>
                <div class="kpi-value" id="kpiAvg">&#3647;0</div>
                <div class="small opacity-75" id="kpiAvgSub">&nbsp;</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card p-3 h-100">
                <div class="small opacity-75"><i class="bi bi-graph-up"></i> เดือนล่าสุด</div>
                <div class="kpi-value" id="kpiLatest">&#3647;0</div>
                <div class="small opacity-75" id="kpiLatestSub">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Monthly trend + Type doughnut -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3">แนวโน้มรายเดือน</h6>
                <div class="chart-container">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3">สัดส่วนตามประเภท</h6>
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
                <div class="card-header py-2"><strong><i class="bi bi-person-hearts"></i> Top สมาชิก</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th>สมาชิก</th><th class="text-end">ยอด (฿)</th></tr></thead>
                        <tbody id="topMembers"><tr><td colspan="3" class="text-center text-muted py-3">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header py-2"><strong><i class="bi bi-diagram-3"></i> Top วง/กลุ่ม</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>#</th><th>วง/กลุ่ม</th><th class="text-end">ยอด (฿)</th></tr></thead>
                        <tbody id="topGroups"><tr><td colspan="3" class="text-center text-muted py-3">Loading...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card p-3 h-100">
                <h6 class="card-title mb-3">สัดส่วนตามค่าย</h6>
                <div class="chart-container sm">
                    <canvas id="chartCompany"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
let chartMonthly, chartType, chartCompany;

const PALETTE = ['#7c3aed','#a78bfa','#ec4899','#f59e0b','#0891b2','#10b981','#ef4444','#6366f1','#14b8a6','#f97316'];

document.addEventListener('DOMContentLoaded', () => {
    $('periodSelect').addEventListener('change', loadDashboard);
    loadDashboard();
});

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
    const res = await fetch('api.php?' + params, { cache: 'no-store' }).then(r => r.json());
    if (res.error) { alert(res.error); return; }

    populateYears(res.years);
    renderKpis(res.kpis);
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
        o.value = y; o.textContent = `ปี ${y}`;
        sel.appendChild(o);
    });
    sel._yearsDone = true;
}

// --- Formatters ---
function fmtMoney(n) { return '฿' + new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(n || 0); }
function fmtInt(n)   { return new Intl.NumberFormat('th-TH').format(n || 0); }
function escHtml(s)  { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

function renderKpis(k) {
    $('kpiSpent').textContent  = fmtMoney(k.total_spent);
    $('kpiSpentSub').innerHTML = `${k.active_months || 0} เดือนที่มีข้อมูล`;

    $('kpiItems').textContent  = fmtInt(k.total_items);
    $('kpiItemsSub').innerHTML = `${fmtInt(k.total_qty)} ชิ้น`;

    $('kpiAvg').textContent    = fmtMoney(k.avg_per_month);
    $('kpiAvgSub').innerHTML   = k.top_type ? `ประเภทเด่น: ${escHtml(k.top_type)}` : '&nbsp;';

    $('kpiLatest').textContent = fmtMoney(k.latest_month_spent);
    if (k.mom_change_pct === null || k.mom_change_pct === undefined) {
        $('kpiLatestSub').innerHTML = k.latest_month ? escHtml(k.latest_month) : '&nbsp;';
    } else {
        const up = k.mom_change_pct >= 0;
        const cls = up ? 'delta-up' : 'delta-down';
        const arrow = up ? '▲' : '▼';
        $('kpiLatestSub').innerHTML =
            `<span class="${cls}">${arrow} ${Math.abs(k.mom_change_pct).toFixed(1)}%</span> เทียบเดือนก่อน`;
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
                label: 'ยอดใช้จ่าย (฿)',
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
    const rows = data.map(d => ({ label: d[labelKey] || '(ไม่ระบุ)', value: d.total_price }));
    if (rows.length <= n) return rows;
    const head = rows.slice(0, n);
    const restTotal = rows.slice(n).reduce((s, r) => s + r.value, 0);
    if (restTotal > 0) head.push({ label: `อื่นๆ (${rows.length - n})`, value: restTotal });
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
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">ไม่มีข้อมูล</td></tr>';
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
