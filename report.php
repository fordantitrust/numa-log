<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; font-size: 14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .nav-pills .nav-link.active { background: var(--primary); }
        .nav-pills .nav-link { color: var(--primary); }
        .table th { background: #f9fafb; white-space: nowrap; }
        .table td { vertical-align: middle; }
        .rank-1 { color: #eab308; font-weight: 700; }
        .rank-2 { color: #9ca3af; font-weight: 700; }
        .rank-3 { color: #b45309; font-weight: 700; }
        .chart-container { position: relative; height: 400px; }
        .table-scroll { max-height: 500px; overflow-y: auto; }
        .progress-bar-custom { height: 6px; border-radius: 3px; background: #e5e7eb; }
        .progress-bar-custom .fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--primary), #a78bfa); }
        .badge-type { background: #fce7f3; color: #9d174d; }
        .badge-company { background: #dc2626; color: white; }
        .badge-group { background: #7c3aed; color: white; }
        .badge-unit { background: #0891b2; color: white; }
        .badge-solo { background: #f59e0b; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-bar-chart-line"></i> Report <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>
</nav>

<div class="container-fluid py-3">
    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabMonthly">
                <i class="bi bi-calendar3"></i> Monthly
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabIdol">
                <i class="bi bi-person-hearts"></i> By Member
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabGroup">
                <i class="bi bi-diagram-3"></i> By Group
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabCompany">
                <i class="bi bi-building"></i> By Company
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabType">
                <i class="bi bi-tags"></i> By Type
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Monthly Tab -->
        <div class="tab-pane fade show active" id="tabMonthly">
            <!-- Monthly overview -->
            <div id="monthlyMainView">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Monthly Spending</h6>
                            <div class="chart-container">
                                <canvas id="chartMonthly"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header py-2"><strong>Monthly Breakdown</strong> <span class="text-muted small">- click to view daily</span></div>
                            <div class="table-scroll">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableMonthly"></tbody>
                                    <tfoot id="footMonthly" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Daily detail view -->
            <div id="dailyDetailView" style="display:none">
                <div class="mb-3 d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" onclick="hideDailyDetail()">
                        <i class="bi bi-arrow-left"></i> Back to Monthly
                    </button>
                    <select class="form-select form-select-sm" style="width:auto" id="dailyMonthSelect" onchange="loadDaily(this.value)"></select>
                    <span class="fw-bold fs-5" id="dailyMonthLabel"></span>
                </div>
                <!-- Summary cards -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f5f3ff">
                            <div class="small text-muted">Active Days</div>
                            <div class="fs-4 fw-bold" style="color:var(--primary)" id="dailyDays">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fdf2f8">
                            <div class="small text-muted">Total Items</div>
                            <div class="fs-4 fw-bold" style="color:#ec4899" id="dailyItems">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f0fdf4">
                            <div class="small text-muted">Total Qty</div>
                            <div class="fs-4 fw-bold" style="color:#16a34a" id="dailyQty">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fffbeb">
                            <div class="small text-muted">Total Spent</div>
                            <div class="fs-4 fw-bold" style="color:#d97706" id="dailySpent">฿0</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Daily Spending</h6>
                            <div class="chart-container">
                                <canvas id="chartDaily"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header py-2"><strong>Daily Breakdown</strong></div>
                            <div class="table-scroll">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableDaily"></tbody>
                                    <tfoot id="footDaily" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Idol Tab -->
        <div class="tab-pane fade" id="tabIdol">
            <!-- Main idol ranking view -->
            <div id="idolMainView">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Top 10 Members by Spending</h6>
                            <div class="chart-container">
                                <canvas id="chartIdolPie"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header py-2"><strong>All Members Ranking</strong> <span class="text-muted small">- click name to view detail</span></div>
                            <div class="table-scroll">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Idol</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                            <th style="width:120px">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableIdol"></tbody>
                                    <tfoot id="footIdol" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Idol detail view (hidden by default) -->
            <div id="idolDetailView" style="display:none">
                <div class="mb-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="hideIdolDetail()">
                        <i class="bi bi-arrow-left"></i> Back to All Idols
                    </button>
                    <span class="ms-2 fw-bold fs-5" id="idolDetailName"></span>
                </div>
                <!-- Summary cards for selected idol -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f5f3ff">
                            <div class="small text-muted">Total Items</div>
                            <div class="fs-4 fw-bold" style="color:var(--primary)" id="idolDetItems">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fdf2f8">
                            <div class="small text-muted">Total Qty</div>
                            <div class="fs-4 fw-bold" style="color:#ec4899" id="idolDetQty">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f0fdf4">
                            <div class="small text-muted">Total Spent</div>
                            <div class="fs-4 fw-bold" style="color:#16a34a" id="idolDetSpent">฿0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fffbeb">
                            <div class="small text-muted">Avg per Item</div>
                            <div class="fs-4 fw-bold" style="color:#d97706" id="idolDetAvg">฿0</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <!-- By Type chart + table -->
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Spending by Type</h6>
                            <div class="chart-container" style="height:300px">
                                <canvas id="chartIdolDetailType"></canvas>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="card-header py-2"><strong>Type Breakdown</strong></div>
                            <div class="table-scroll" style="max-height:300px">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Type</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                            <th style="width:100px">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableIdolDetailType"></tbody>
                                    <tfoot id="footIdolDetailType" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- By Month chart + table -->
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Monthly Spending</h6>
                            <div class="chart-container" style="height:300px">
                                <canvas id="chartIdolDetailMonth"></canvas>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="card-header py-2"><strong>Monthly Breakdown</strong></div>
                            <div class="table-scroll" style="max-height:300px">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableIdolDetailMonth"></tbody>
                                    <tfoot id="footIdolDetailMonth" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Type Tab -->
        <div class="tab-pane fade" id="tabType">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Top 10 Types by Spending</h6>
                        <div class="chart-container">
                            <canvas id="chartTypePie"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header py-2"><strong>All Types Ranking</strong></div>
                        <div class="table-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Type</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:120px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableType"></tbody>
                                <tfoot id="footType" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company Tab -->
        <div class="tab-pane fade" id="tabCompany">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending by Company</h6>
                        <div class="chart-container">
                            <canvas id="chartCompanyPie"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>Company Ranking</strong>
                            <a href="idols.php" class="btn btn-outline-primary btn-sm px-2 py-0"><i class="bi bi-gear"></i> Manage</a>
                        </div>
                        <div class="table-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Company</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:120px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableCompany"></tbody>
                                <tfoot id="footCompany" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>
                    <!-- Groups detail panel -->
                    <div class="card mt-3" id="companyDetailCard" style="display:none">
                        <div class="card-header py-2"><strong>Groups under <span id="companyDetailName"></span></strong></div>
                        <div class="table-scroll" style="max-height:300px">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Group</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:100px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableCompanyDetail"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Group Tab -->
        <div class="tab-pane fade" id="tabGroup">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending by Group</h6>
                        <div class="chart-container">
                            <canvas id="chartGroupPie"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>Group Ranking</strong>
                            <a href="idols.php" class="btn btn-outline-primary btn-sm px-2 py-0"><i class="bi bi-gear"></i> Manage</a>
                        </div>
                        <div class="table-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Group</th>
                                        <th>Type</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:120px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableGroup"></tbody>
                                <tfoot id="footGroup" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>
                    <!-- Members detail panel -->
                    <div class="card mt-3" id="groupDetailCard" style="display:none">
                        <div class="card-header py-2"><strong>Members of <span id="groupDetailName"></span></strong></div>
                        <div class="table-scroll" style="max-height:300px">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Member</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:100px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableGroupDetail"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
const fmt = n => new Intl.NumberFormat('th-TH').format(n);
const COLORS = [
    '#7c3aed','#ec4899','#f59e0b','#10b981','#3b82f6',
    '#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16',
    '#6366f1','#14b8a6','#e11d48','#a855f7','#0ea5e9',
    '#d946ef','#22c55e','#eab308','#64748b','#fb923c'
];

let chartMonthly = null;
let chartDaily = null;
let chartIdolPie = null;
let chartTypePie = null;
let chartIdolDetailType = null;
let chartIdolDetailMonth = null;
let chartGroupPie = null;
let chartCompanyPie = null;
let groupData = [];
let companyData = [];

document.addEventListener('DOMContentLoaded', () => {
    loadMonthly();
    loadIdol();
    loadType();
    loadGroup();
    loadCompany();
});

// --- Monthly ---
async function loadMonthly() {
    const res = await fetch('api.php?action=report_monthly').then(r => r.json());
    const data = res.data;

    // Chart
    const ctx = $('chartMonthly').getContext('2d');
    if (chartMonthly) chartMonthly.destroy();
    chartMonthly = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(r => formatMonth(r.month)),
            datasets: [
                {
                    label: 'Spending (฿)',
                    data: data.map(r => r.total_price),
                    backgroundColor: 'rgba(124,58,237,0.7)',
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Quantity',
                    data: data.map(r => r.total_qty),
                    type: 'line',
                    borderColor: '#ec4899',
                    backgroundColor: '#ec4899',
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            if (ctx.dataset.yAxisID === 'y') return `Spending: ฿${fmt(ctx.raw)}`;
                            return `Qty: ${fmt(ctx.raw)}`;
                        }
                    }
                }
            },
            scales: {
                y: { position: 'left', ticks: { callback: v => '฿' + fmt(v) } },
                y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => fmt(v) + ' pcs' } },
            }
        }
    });

    // Table
    const totals = data.reduce((acc, r) => {
        acc.items += Number(r.items);
        acc.qty += Number(r.total_qty);
        acc.price += Number(r.total_price);
        return acc;
    }, { items: 0, qty: 0, price: 0 });

    $('tableMonthly').innerHTML = data.map(r => `
        <tr style="cursor:pointer" onclick="showDailyDetail('${r.month}')">
            <td><a href="#" class="text-decoration-none" onclick="return false">${formatMonth(r.month)}</a></td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>
    `).join('');

    $('footMonthly').innerHTML = `
        <tr>
            <td>Total</td>
            <td class="text-end">${fmt(totals.items)}</td>
            <td class="text-end">${fmt(totals.qty)}</td>
            <td class="text-end">${fmt(totals.price)}</td>
        </tr>`;

    // Store months for the daily select
    window._monthlyData = data;
}

// --- Daily Detail ---
function showDailyDetail(month) {
    $('monthlyMainView').style.display = 'none';
    $('dailyDetailView').style.display = 'block';

    // Populate month selector
    const sel = $('dailyMonthSelect');
    sel.innerHTML = (window._monthlyData || []).map(r =>
        `<option value="${r.month}" ${r.month === month ? 'selected' : ''}>${formatMonth(r.month)}</option>`
    ).join('');

    loadDaily(month);
}

function hideDailyDetail() {
    $('dailyDetailView').style.display = 'none';
    $('monthlyMainView').style.display = 'block';
}

async function loadDaily(month) {
    $('dailyMonthLabel').textContent = formatMonth(month);
    const res = await fetch('api.php?action=report_daily&month=' + encodeURIComponent(month)).then(r => r.json());
    const data = res.data;

    // Summary cards
    const totItems = data.reduce((s, r) => s + Number(r.items), 0);
    const totQty = data.reduce((s, r) => s + Number(r.total_qty), 0);
    const totPrice = data.reduce((s, r) => s + Number(r.total_price), 0);
    $('dailyDays').textContent = data.length;
    $('dailyItems').textContent = fmt(totItems);
    $('dailyQty').textContent = fmt(totQty);
    $('dailySpent').textContent = '฿' + fmt(totPrice);

    // Chart
    if (chartDaily) chartDaily.destroy();
    chartDaily = new Chart($('chartDaily').getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.map(r => formatDay(r.day)),
            datasets: [
                {
                    label: 'Spending (฿)',
                    data: data.map(r => Number(r.total_price)),
                    backgroundColor: 'rgba(124,58,237,0.7)',
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Quantity',
                    data: data.map(r => Number(r.total_qty)),
                    type: 'line',
                    borderColor: '#ec4899',
                    backgroundColor: '#ec4899',
                    pointRadius: 3,
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                tooltip: { callbacks: { label: ctx => {
                    if (ctx.dataset.yAxisID === 'y') return `Spending: ฿${fmt(ctx.raw)}`;
                    return `Qty: ${fmt(ctx.raw)}`;
                }}}
            },
            scales: {
                y: { position: 'left', ticks: { callback: v => '฿' + fmt(v) } },
                y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => fmt(v) + ' pcs' } },
            }
        }
    });

    // Table
    $('tableDaily').innerHTML = data.map(r => `
        <tr>
            <td>${formatDay(r.day)}</td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>
    `).join('');

    $('footDaily').innerHTML = `
        <tr>
            <td>Total (${data.length} days)</td>
            <td class="text-end">${fmt(totItems)}</td>
            <td class="text-end">${fmt(totQty)}</td>
            <td class="text-end">${fmt(totPrice)}</td>
        </tr>`;
}

function formatDay(d) {
    if (!d) return '-';
    const dt = new Date(d + 'T00:00:00');
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    return `${d.substring(8)} ${days[dt.getDay()]}`;
}

// --- Idol ---
async function loadIdol() {
    const res = await fetch('api.php?action=report_idol').then(r => r.json());
    renderRankReport(res.data, 'Idol', 'idol', 'chartIdolPie', 'tableIdol', 'footIdol');
}

// --- Type ---
async function loadType() {
    const res = await fetch('api.php?action=report_type').then(r => r.json());
    renderRankReport(res.data, 'Type', 'type', 'chartTypePie', 'tableType', 'footType');
}

function renderRankReport(data, label, key, chartId, tableId, footId) {
    const grandTotal = data.reduce((s, r) => s + Number(r.total_price), 0);
    const maxPrice = data.length > 0 ? Number(data[0].total_price) : 1;

    // Pie chart - top 10
    const top10 = data.slice(0, 10);
    const othersPrice = data.slice(10).reduce((s, r) => s + Number(r.total_price), 0);
    const pieLabels = top10.map(r => r[key]);
    const pieData = top10.map(r => Number(r.total_price));
    if (othersPrice > 0) {
        pieLabels.push('Others');
        pieData.push(othersPrice);
    }

    const ctx = $(chartId).getContext('2d');
    const existingChart = chartId === 'chartIdolPie' ? chartIdolPie : chartTypePie;
    if (existingChart) existingChart.destroy();

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: COLORS.slice(0, pieLabels.length),
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const pct = ((ctx.raw / grandTotal) * 100).toFixed(1);
                            return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });

    if (chartId === 'chartIdolPie') chartIdolPie = chart;
    else chartTypePie = chart;

    // Table
    const totals = data.reduce((acc, r) => {
        acc.items += Number(r.items);
        acc.qty += Number(r.total_qty);
        acc.price += Number(r.total_price);
        return acc;
    }, { items: 0, qty: 0, price: 0 });

    const isIdol = key === 'idol';
    $(tableId).innerHTML = data.map((r, i) => {
        const rank = i + 1;
        const pct = grandTotal > 0 ? ((Number(r.total_price) / grandTotal) * 100) : 0;
        const barWidth = maxPrice > 0 ? ((Number(r.total_price) / maxPrice) * 100) : 0;
        const rankClass = rank <= 3 ? `rank-${rank}` : '';
        const medal = rank === 1 ? ' <i class="bi bi-trophy-fill rank-1"></i>' : rank === 2 ? ' <i class="bi bi-trophy-fill rank-2"></i>' : rank === 3 ? ' <i class="bi bi-trophy-fill rank-3"></i>' : '';
        const nameHtml = isIdol
            ? `<a href="#" class="text-decoration-none fw-semibold" onclick="showIdolDetail('${escJs(r[key])}');return false">${escHtml(r[key])}</a>${medal}`
            : `${escHtml(r[key])}${medal}`;
        return `
        <tr>
            <td class="${rankClass}">${rank}</td>
            <td>${nameHtml}</td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <div class="progress-bar-custom flex-grow-1">
                        <div class="fill" style="width:${barWidth}%"></div>
                    </div>
                    <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
                </div>
            </td>
        </tr>`;
    }).join('');

    $(footId).innerHTML = `
        <tr>
            <td></td>
            <td>Total</td>
            <td class="text-end">${fmt(totals.items)}</td>
            <td class="text-end">${fmt(totals.qty)}</td>
            <td class="text-end">${fmt(totals.price)}</td>
            <td><span class="small text-muted">100%</span></td>
        </tr>`;
}

function formatMonth(m) {
    if (!m) return '-';
    const [y, mo] = m.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[parseInt(mo) - 1] + ' ' + y;
}

// --- Idol Detail ---
async function showIdolDetail(idol) {
    $('idolDetailName').textContent = idol;
    $('idolMainView').style.display = 'none';
    $('idolDetailView').style.display = 'block';

    const res = await fetch('api.php?action=report_idol_detail&idol=' + encodeURIComponent(idol)).then(r => r.json());
    const byType = res.by_type;
    const byMonth = res.by_month;

    // Summary cards
    const totItems = byType.reduce((s, r) => s + Number(r.items), 0);
    const totQty = byType.reduce((s, r) => s + Number(r.total_qty), 0);
    const totPrice = byType.reduce((s, r) => s + Number(r.total_price), 0);
    $('idolDetItems').textContent = fmt(totItems);
    $('idolDetQty').textContent = fmt(totQty);
    $('idolDetSpent').textContent = '฿' + fmt(totPrice);
    $('idolDetAvg').textContent = '฿' + fmt(totItems > 0 ? Math.round(totPrice / totItems) : 0);

    // --- By Type doughnut ---
    if (chartIdolDetailType) chartIdolDetailType.destroy();
    chartIdolDetailType = new Chart($('chartIdolDetailType').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: byType.map(r => r.type),
            datasets: [{
                data: byType.map(r => Number(r.total_price)),
                backgroundColor: COLORS.slice(0, byType.length),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = ((ctx.raw / totPrice) * 100).toFixed(1);
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });

    // By Type table
    const maxTypePrice = byType.length > 0 ? Number(byType[0].total_price) : 1;
    $('tableIdolDetailType').innerHTML = byType.map((r, i) => {
        const pct = totPrice > 0 ? ((Number(r.total_price) / totPrice) * 100) : 0;
        const barW = maxTypePrice > 0 ? ((Number(r.total_price) / maxTypePrice) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td><span class="badge badge-type">${escHtml(r.type)}</span></td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footIdolDetailType').innerHTML = `<tr>
        <td></td><td>Total</td>
        <td class="text-end">${fmt(totItems)}</td>
        <td class="text-end">${fmt(totQty)}</td>
        <td class="text-end">${fmt(totPrice)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;

    // --- By Month bar chart ---
    if (chartIdolDetailMonth) chartIdolDetailMonth.destroy();
    chartIdolDetailMonth = new Chart($('chartIdolDetailMonth').getContext('2d'), {
        type: 'bar',
        data: {
            labels: byMonth.map(r => formatMonth(r.month)),
            datasets: [{
                label: 'Spending (฿)',
                data: byMonth.map(r => Number(r.total_price)),
                backgroundColor: 'rgba(124,58,237,0.7)',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => '฿' + fmt(ctx.raw) } }
            },
            scales: { y: { ticks: { callback: v => '฿' + fmt(v) } } }
        }
    });

    // By Month table
    const mTotals = byMonth.reduce((a, r) => {
        a.items += Number(r.items); a.qty += Number(r.total_qty); a.price += Number(r.total_price);
        return a;
    }, { items: 0, qty: 0, price: 0 });

    $('tableIdolDetailMonth').innerHTML = byMonth.map(r => `<tr>
        <td>${formatMonth(r.month)}</td>
        <td class="text-end">${fmt(r.items)}</td>
        <td class="text-end">${fmt(r.total_qty)}</td>
        <td class="text-end">${fmt(r.total_price)}</td>
    </tr>`).join('');

    $('footIdolDetailMonth').innerHTML = `<tr>
        <td>Total</td>
        <td class="text-end">${fmt(mTotals.items)}</td>
        <td class="text-end">${fmt(mTotals.qty)}</td>
        <td class="text-end">${fmt(mTotals.price)}</td>
    </tr>`;
}

function hideIdolDetail() {
    $('idolDetailView').style.display = 'none';
    $('idolMainView').style.display = 'block';
}

// --- Group Report ---
async function loadGroup() {
    const res = await fetch('api.php?action=report_by_group').then(r => r.json());
    groupData = res.data;
    const grandTotal = groupData.reduce((s, r) => s + Number(r.total_price), 0);
    const maxPrice = groupData.length > 0 ? Number(groupData[0].total_price) : 1;

    // Pie chart
    if (chartGroupPie) chartGroupPie.destroy();
    chartGroupPie = new Chart($('chartGroupPie').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: groupData.map(r => r.name),
            datasets: [{
                data: groupData.map(r => Number(r.total_price)),
                backgroundColor: COLORS.slice(0, groupData.length),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = ((ctx.raw / grandTotal) * 100).toFixed(1);
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });

    // Category badges
    const catBadge = cat => {
        const cls = {group:'badge-group',unit:'badge-unit',solo:'badge-solo'}[cat] || 'bg-secondary';
        const label = cat === 'solo' ? 'Solo' : cat.charAt(0).toUpperCase() + cat.slice(1);
        return `<span class="badge ${cls}">${label}</span>`;
    };

    // Table
    const totals = groupData.reduce((a, r) => {
        a.items += Number(r.items); a.qty += Number(r.total_qty); a.price += Number(r.total_price);
        return a;
    }, { items:0, qty:0, price:0 });

    $('tableGroup').innerHTML = groupData.map((r, i) => {
        const rank = i + 1;
        const pct = grandTotal > 0 ? ((Number(r.total_price) / grandTotal) * 100) : 0;
        const barW = maxPrice > 0 ? ((Number(r.total_price) / maxPrice) * 100) : 0;
        const rankClass = rank <= 3 ? `rank-${rank}` : '';
        const medal = rank <= 3 ? ` <i class="bi bi-trophy-fill rank-${rank}"></i>` : '';
        return `<tr style="cursor:pointer" onclick="showGroupMembers(${i})">
            <td class="${rankClass}">${rank}</td>
            <td><strong>${escHtml(r.name)}</strong>${medal}</td>
            <td>${catBadge(r.category)}</td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footGroup').innerHTML = `<tr>
        <td></td><td>Total</td><td></td>
        <td class="text-end">${fmt(totals.items)}</td>
        <td class="text-end">${fmt(totals.qty)}</td>
        <td class="text-end">${fmt(totals.price)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;
}

async function showGroupMembers(idx) {
    const group = groupData[idx];
    if (!group || !group.members || group.members.length === 0) {
        $('groupDetailCard').style.display = 'none';
        return;
    }

    $('groupDetailName').textContent = group.name;
    $('groupDetailCard').style.display = 'block';

    // Fetch individual member stats
    const res = await fetch('api.php?action=report_idol').then(r => r.json());
    const memberSet = new Set(group.members);
    const members = res.data.filter(r => memberSet.has(r.idol));
    members.sort((a, b) => Number(b.total_price) - Number(a.total_price));

    const groupTotal = Number(group.total_price);
    const maxP = members.length > 0 ? Number(members[0].total_price) : 1;

    $('tableGroupDetail').innerHTML = members.map((r, i) => {
        const pct = groupTotal > 0 ? ((Number(r.total_price) / groupTotal) * 100) : 0;
        const barW = maxP > 0 ? ((Number(r.total_price) / maxP) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td><a href="#" class="text-decoration-none fw-semibold" onclick="event.stopPropagation();document.querySelector('[data-bs-target=\\'#tabIdol\\']').click();setTimeout(()=>showIdolDetail('${escJs(r.idol)}'),200);return false">${escHtml(r.idol)}</a></td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');
}

// --- Company Report ---
async function loadCompany() {
    const res = await fetch('api.php?action=report_by_company').then(r => r.json());
    companyData = res.data;
    const grandTotal = companyData.reduce((s, r) => s + Number(r.total_price), 0);
    const maxPrice = companyData.length > 0 ? Number(companyData[0].total_price) : 1;

    // Pie chart
    if (chartCompanyPie) chartCompanyPie.destroy();
    chartCompanyPie = new Chart($('chartCompanyPie').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: companyData.map(r => r.name),
            datasets: [{
                data: companyData.map(r => Number(r.total_price)),
                backgroundColor: COLORS.slice(0, companyData.length),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = ((ctx.raw / grandTotal) * 100).toFixed(1);
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });

    // Table
    const totals = companyData.reduce((a, r) => {
        a.items += Number(r.items); a.qty += Number(r.total_qty); a.price += Number(r.total_price);
        return a;
    }, { items:0, qty:0, price:0 });

    $('tableCompany').innerHTML = companyData.map((r, i) => {
        const rank = i + 1;
        const pct = grandTotal > 0 ? ((Number(r.total_price) / grandTotal) * 100) : 0;
        const barW = maxPrice > 0 ? ((Number(r.total_price) / maxPrice) * 100) : 0;
        const rankClass = rank <= 3 ? `rank-${rank}` : '';
        const medal = rank <= 3 ? ` <i class="bi bi-trophy-fill rank-${rank}"></i>` : '';
        return `<tr style="cursor:pointer" onclick="showCompanyGroups(${i})">
            <td class="${rankClass}">${rank}</td>
            <td><strong>${escHtml(r.name)}</strong>${medal}</td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footCompany').innerHTML = `<tr>
        <td></td><td>Total</td>
        <td class="text-end">${fmt(totals.items)}</td>
        <td class="text-end">${fmt(totals.qty)}</td>
        <td class="text-end">${fmt(totals.price)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;
}

function showCompanyGroups(idx) {
    const company = companyData[idx];
    if (!company || !company.groups || company.groups.length === 0) {
        $('companyDetailCard').style.display = 'none';
        return;
    }

    $('companyDetailName').textContent = company.name;
    $('companyDetailCard').style.display = 'block';

    const companyTotal = Number(company.total_price);
    const maxP = company.groups.length > 0 ? Number(company.groups[0].total_price) : 1;

    const catBadge = cat => {
        const cls = {group:'badge-group',unit:'badge-unit'}[cat] || 'bg-secondary';
        const label = cat.charAt(0).toUpperCase() + cat.slice(1);
        return `<span class="badge ${cls}">${label}</span>`;
    };

    $('tableCompanyDetail').innerHTML = company.groups.map((r, i) => {
        const pct = companyTotal > 0 ? ((Number(r.total_price) / companyTotal) * 100) : 0;
        const barW = maxP > 0 ? ((Number(r.total_price) / maxP) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td>${catBadge(r.category)} <strong>${escHtml(r.name)}</strong></td>
            <td class="text-end">${fmt(r.items)}</td>
            <td class="text-end">${fmt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
