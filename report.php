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
        .table-scroll { max-height: 500px; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        /* Mobile */
        @media (max-width: 767.98px) { .chart-container { height: 250px; } }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="password"],
            select, textarea { font-size: 16px !important; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
            .nav-pills .nav-link { padding: .375rem .5rem; font-size: 13px; }
        }
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
        <div>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-speedometer2"></i><span class="d-none d-sm-inline"> Dashboard</span></a>
            <a href="items.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-list-ul"></i><span class="d-none d-sm-inline"> Items</span></a>
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
    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabOverview">
                <i class="bi bi-grid-1x2"></i> Overview
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabMonthly">
                <i class="bi bi-calendar3"></i> Monthly
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabTrends">
                <i class="bi bi-graph-up-arrow"></i> Trends
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabSeasonality">
                <i class="bi bi-calendar-week"></i> Seasonality
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabIdol">
                <i class="bi bi-person-hearts"></i> By Member
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabCompare">
                <i class="bi bi-arrow-left-right"></i> Compare
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabGroup">
                <i class="bi bi-diagram-3"></i> By Group
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabUnit">
                <i class="bi bi-diagram-2"></i> By Unit
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
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabEvent">
                <i class="bi bi-calendar-event"></i> By Event
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabTopItems">
                <i class="bi bi-trophy"></i> Top Items
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabInactive">
                <i class="bi bi-hourglass-split"></i> Inactive
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="tabOverview">
            <!-- KPI cards -->
            <div class="row g-3 mb-3" id="ovKpis">
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f5f3ff">
                        <div class="small text-muted"><i class="bi bi-cash-stack"></i> Total Spent</div>
                        <div class="fs-4 fw-bold" style="color:var(--primary)" id="ovSpent">฿0</div>
                        <div class="small text-muted" id="ovSpentSub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fdf2f8">
                        <div class="small text-muted"><i class="bi bi-box-seam"></i> Total Items</div>
                        <div class="fs-4 fw-bold" style="color:#ec4899" id="ovItems">0</div>
                        <div class="small text-muted" id="ovItemsSub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f0fdf4">
                        <div class="small text-muted"><i class="bi bi-calendar-month"></i> Avg / Month</div>
                        <div class="fs-4 fw-bold" style="color:#16a34a" id="ovAvgMonth">฿0</div>
                        <div class="small text-muted" id="ovAvgMonthSub">&nbsp;</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fffbeb">
                        <div class="small text-muted"><i class="bi bi-graph-up"></i> Latest Month (MoM)</div>
                        <div class="fs-4 fw-bold" style="color:#d97706" id="ovMom">฿0</div>
                        <div class="small text-muted" id="ovMomSub">&nbsp;</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Monthly Spending Trend</h6>
                        <div class="chart-container"><canvas id="chartOvMonthly"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header py-2"><strong>Top 5 Members</strong></div>
                        <ul class="list-group list-group-flush" id="ovTopMembers"></ul>
                    </div>
                    <div class="card">
                        <div class="card-header py-2"><strong>Highlights</strong></div>
                        <ul class="list-group list-group-flush" id="ovHighlights"></ul>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">By Type</h6>
                        <div class="chart-container" style="height:300px"><canvas id="chartOvType"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">By Company</h6>
                        <div class="chart-container" style="height:300px"><canvas id="chartOvCompany"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Tab -->
        <div class="tab-pane fade" id="tabMonthly">
            <!-- Monthly overview -->
            <div id="monthlyMainView">
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Monthly Spending</h6>
                            <div class="chart-container">
                                <canvas id="chartMonthly"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
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
                    <div class="col-12 col-lg-8">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Daily Spending</h6>
                            <div class="chart-container">
                                <canvas id="chartDaily"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
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
                <!-- Type & Idol Breakdown for selected month -->
                <div class="row g-3 mt-2">
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Top 10 by Type</h6>
                            <div class="chart-container" style="height:280px">
                                <canvas id="chartDailyType"></canvas>
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
                                    <tbody id="tableDailyType"></tbody>
                                    <tfoot id="footDailyType" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Top 10 by Idol</h6>
                            <div class="chart-container" style="height:280px">
                                <canvas id="chartDailyIdol"></canvas>
                            </div>
                        </div>
                        <div class="card mt-3">
                            <div class="card-header py-2"><strong>Idol Breakdown</strong></div>
                            <div class="table-scroll" style="max-height:300px">
                                <table class="table table-sm table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Idol</th>
                                            <th class="text-end">Items</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Total (฿)</th>
                                            <th style="width:100px">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableDailyIdol"></tbody>
                                    <tfoot id="footDailyIdol" class="table-light fw-bold"></tfoot>
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
            <!-- Type main view -->
            <div id="typeMainView">
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
                            <div class="card-header py-2"><strong>All Types Ranking</strong> <span class="text-muted small">- click to view members</span></div>
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
            <!-- Type detail view -->
            <div id="typeDetailView" style="display:none">
                <div class="mb-3">
                    <button class="btn btn-outline-secondary btn-sm" onclick="hideTypeDetail()">
                        <i class="bi bi-arrow-left"></i> Back to All Types
                    </button>
                    <span class="ms-2 fw-bold fs-5" id="typeDetailName"></span>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f5f3ff">
                            <div class="small text-muted">Total Items</div>
                            <div class="fs-4 fw-bold" style="color:var(--primary)" id="typeDetItems">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fdf2f8">
                            <div class="small text-muted">Total Qty</div>
                            <div class="fs-4 fw-bold" style="color:#ec4899" id="typeDetQty">0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#f0fdf4">
                            <div class="small text-muted">Total Spent</div>
                            <div class="fs-4 fw-bold" style="color:#16a34a" id="typeDetSpent">฿0</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card p-3 text-center" style="background:#fffbeb">
                            <div class="small text-muted">Members</div>
                            <div class="fs-4 fw-bold" style="color:#d97706" id="typeDetMembers">0</div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header py-2"><strong>Member Breakdown</strong></div>
                    <div class="table-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th>Member</th>
                                    <th>Group / Unit</th>
                                    <th>Company</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Total (฿)</th>
                                    <th style="width:120px">Share</th>
                                </tr>
                            </thead>
                            <tbody id="tableTypeDetail"></tbody>
                            <tfoot id="footTypeDetail" class="table-light fw-bold"></tfoot>
                        </table>
                    </div>
                </div>
                <!-- Monthly Breakdown for Type -->
                <div class="row g-3 mt-2">
                    <div class="col-lg-5">
                        <div class="card p-3">
                            <h6 class="card-title mb-3">Monthly Spending</h6>
                            <div class="chart-container" style="height:300px">
                                <canvas id="chartTypeDetailMonth"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="card-header py-2"><strong>Monthly Breakdown</strong></div>
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
                                    <tbody id="tableTypeDetailMonth"></tbody>
                                    <tfoot id="footTypeDetailMonth" class="table-light fw-bold"></tfoot>
                                </table>
                            </div>
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

        <!-- Trends Tab -->
        <div class="tab-pane fade" id="tabTrends">
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f5f3ff">
                        <div class="small text-muted">Cumulative Total</div>
                        <div class="fs-5 fw-bold" style="color:var(--primary)" id="trCumulative">฿0</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fdf2f8">
                        <div class="small text-muted">Best Month</div>
                        <div class="fs-5 fw-bold" style="color:#ec4899" id="trBest">-</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f0fdf4">
                        <div class="small text-muted">Avg MoM Growth</div>
                        <div class="fs-5 fw-bold" style="color:#16a34a" id="trGrowth">-</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fffbeb">
                        <div class="small text-muted">This Month (forecast)</div>
                        <div class="fs-5 fw-bold" style="color:#d97706" id="trForecast">฿0</div>
                        <div class="small text-muted" id="trForecastSub">&nbsp;</div>
                    </div>
                </div>
            </div>
            <div class="card p-3 mb-3">
                <h6 class="card-title mb-3">Cumulative Spending</h6>
                <div class="chart-container"><canvas id="chartTrendCumulative"></canvas></div>
            </div>
            <div class="card p-3">
                <h6 class="card-title mb-3">Month-over-Month Growth (%)</h6>
                <div class="chart-container" style="height:300px"><canvas id="chartTrendMoM"></canvas></div>
            </div>
        </div>

        <!-- Seasonality Tab -->
        <div class="tab-pane fade" id="tabSeasonality">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending by Day of Week</h6>
                        <div class="chart-container"><canvas id="chartSeasonWeekday"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending by Month of Year</h6>
                        <div class="chart-container"><canvas id="chartSeasonMonth"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header py-2"><strong>Weekday Breakdown</strong></div>
                        <div class="table-scroll" style="max-height:340px">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Day</th><th class="text-end">Items</th><th class="text-end">Qty</th><th class="text-end">Total (฿)</th><th style="width:100px">Share</th></tr></thead>
                                <tbody id="tableSeasonWeekday"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header py-2"><strong>Month Breakdown</strong></div>
                        <div class="table-scroll" style="max-height:340px">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Month</th><th class="text-end">Items</th><th class="text-end">Qty</th><th class="text-end">Total (฿)</th><th style="width:100px">Share</th></tr></thead>
                                <tbody id="tableSeasonMonth"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compare Tab -->
        <div class="tab-pane fade" id="tabCompare">
            <div class="card p-3 mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-5">
                        <label class="form-label small mb-1">Member A</label>
                        <select class="form-select form-select-sm" id="cmpSelA"></select>
                    </div>
                    <div class="col-sm-2 text-center">
                        <i class="bi bi-arrow-left-right fs-4 text-muted"></i>
                    </div>
                    <div class="col-sm-5">
                        <label class="form-label small mb-1">Member B</label>
                        <select class="form-select form-select-sm" id="cmpSelB"></select>
                    </div>
                </div>
            </div>
            <div class="row g-3" id="cmpCards"></div>
            <div class="card p-3 mt-3">
                <h6 class="card-title mb-3">Monthly Spending Comparison</h6>
                <div class="chart-container"><canvas id="chartCompareMonth"></canvas></div>
            </div>
            <div class="card p-3 mt-3">
                <h6 class="card-title mb-3">Spending by Type Comparison</h6>
                <div class="chart-container" style="height:320px"><canvas id="chartCompareType"></canvas></div>
            </div>
        </div>

        <!-- Unit Tab -->
        <div class="tab-pane fade" id="tabUnit">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending by Unit</h6>
                        <div class="chart-container"><canvas id="chartUnitPie"></canvas></div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong>Unit Ranking</strong>
                            <span class="text-muted small">includes sub-unit / project memberships</span>
                        </div>
                        <div class="table-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Unit</th>
                                        <th>Parent</th>
                                        <th class="text-end">Members</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                        <th style="width:110px">Share</th>
                                    </tr>
                                </thead>
                                <tbody id="tableUnit"></tbody>
                                <tfoot id="footUnit" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Tab -->
        <div class="tab-pane fade" id="tabEvent">
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f5f3ff">
                        <div class="small text-muted">Events Tracked</div>
                        <div class="fs-4 fw-bold" style="color:var(--primary)" id="evCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#f0fdf4">
                        <div class="small text-muted">Avg Lead Time</div>
                        <div class="fs-4 fw-bold" style="color:#16a34a" id="evLead">-</div>
                        <div class="small text-muted" id="evLeadSub">order → event</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fffbeb">
                        <div class="small text-muted">Lead Time Range</div>
                        <div class="fs-6 fw-bold" style="color:#d97706" id="evLeadRange">-</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card p-3" style="background:#fef2f2">
                        <div class="small text-muted">Items w/o Event Date</div>
                        <div class="fs-4 fw-bold" style="color:#dc2626" id="evNoEvent">0</div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <div class="card p-3">
                        <h6 class="card-title mb-3">Spending per Event</h6>
                        <div class="chart-container"><canvas id="chartEvent"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-header py-2"><strong>Event Breakdown</strong></div>
                        <div class="table-scroll">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Event Date</th>
                                        <th class="text-end">Items</th>
                                        <th class="text-end">Idols</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                    </tr>
                                </thead>
                                <tbody id="tableEvent"></tbody>
                                <tfoot id="footEvent" class="table-light fw-bold"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Items Tab -->
        <div class="tab-pane fade" id="tabTopItems">
            <div class="row g-3">
                <div class="col-12 col-xl-7">
                    <div class="card">
                        <div class="card-header py-2"><strong><i class="bi bi-cash-coin"></i> Top 20 Most Expensive Purchases</strong></div>
                        <div class="table-scroll" style="max-height:520px">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px">#</th>
                                        <th>Title</th>
                                        <th>Idol</th>
                                        <th>Type</th>
                                        <th class="text-end">Unit ฿</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Total (฿)</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTopExpensive"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="card mb-3">
                        <div class="card-header py-2"><strong><i class="bi bi-repeat"></i> Top 20 Most Frequent Titles</strong></div>
                        <div class="table-scroll" style="max-height:240px">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr><th style="width:40px">#</th><th>Title</th><th class="text-end">Items</th><th class="text-end">Qty</th><th class="text-end">Total (฿)</th></tr>
                                </thead>
                                <tbody id="tableTopFrequent"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header py-2"><strong><i class="bi bi-rulers"></i> Avg Unit Price by Type</strong></div>
                        <div class="table-scroll" style="max-height:240px">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr><th>Type</th><th class="text-end">Avg ฿</th><th class="text-end">Min</th><th class="text-end">Max</th><th class="text-end">Items</th></tr>
                                </thead>
                                <tbody id="tableTopAvg"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Tab -->
        <div class="tab-pane fade" id="tabInactive">
            <div class="card p-3 mb-3 d-flex flex-row align-items-center gap-2 flex-wrap">
                <span class="fw-semibold">Inactive threshold:</span>
                <div class="btn-group btn-group-sm" role="group" id="inactiveThresholds">
                    <button class="btn btn-outline-primary" data-days="30">30 days</button>
                    <button class="btn btn-outline-primary active" data-days="90">90 days</button>
                    <button class="btn btn-outline-primary" data-days="180">180 days</button>
                    <button class="btn btn-outline-primary" data-days="365">1 year</button>
                </div>
                <span class="ms-auto text-muted small" id="inactiveSummary"></span>
            </div>
            <div class="card">
                <div class="card-header py-2"><strong>Members With No Recent Purchases</strong> <span class="text-muted small">- click name to view detail</span></div>
                <div class="table-scroll">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Member</th>
                                <th>Last Purchase</th>
                                <th class="text-end">Days Ago</th>
                                <th class="text-end">Items</th>
                                <th class="text-end">Total Spent (฿)</th>
                            </tr>
                        </thead>
                        <tbody id="tableInactive"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
const fmt = n => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
const fmtInt = n => new Intl.NumberFormat('th-TH').format(n);
const COLORS = [
    '#7c3aed','#ec4899','#f59e0b','#10b981','#3b82f6',
    '#ef4444','#8b5cf6','#06b6d4','#f97316','#84cc16',
    '#6366f1','#14b8a6','#e11d48','#a855f7','#0ea5e9',
    '#d946ef','#22c55e','#eab308','#64748b','#fb923c'
];

let chartMonthly = null;
let chartDaily = null;
let chartDailyType = null;
let chartDailyIdol = null;
let chartIdolPie = null;
let chartTypePie = null;
let chartIdolDetailType = null;
let chartIdolDetailMonth = null;
let chartTypeDetailMonth = null;
let chartGroupPie = null;
let chartCompanyPie = null;
let groupData = [];
let companyData = [];
// New report charts
let chartOvMonthly = null, chartOvType = null, chartOvCompany = null;
let chartTrendCumulative = null, chartTrendMoM = null;
let chartSeasonWeekday = null, chartSeasonMonth = null;
let chartCompareMonth = null, chartCompareType = null;
let chartUnitPie = null, chartEvent = null;
let unitData = [];
let cmpMembers = [];

document.addEventListener('DOMContentLoaded', () => {
    loadOverview();   // default active tab
    loadMonthly();
    loadIdol();
    loadType();
    loadGroup();
    loadCompany();

    // Lazy-load the heavier analytics tabs the first time they're shown.
    const lazy = {
        '#tabTrends': loadTrends,
        '#tabSeasonality': loadSeasonality,
        '#tabCompare': initCompare,
        '#tabUnit': loadUnit,
        '#tabEvent': loadEvent,
        '#tabTopItems': loadTopItems,
        '#tabInactive': loadInactive,
    };
    const loaded = {};
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', e => {
            const target = e.target.getAttribute('data-bs-target');
            if (lazy[target] && !loaded[target]) { loaded[target] = true; lazy[target](); }
        });
    });
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
                            return `Qty: ${fmtInt(ctx.raw)}`;
                        }
                    }
                }
            },
            scales: {
                y: { position: 'left', ticks: { callback: v => '฿' + fmt(v) } },
                y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => fmtInt(v) + ' pcs' } },
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
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>
    `).join('');

    $('footMonthly').innerHTML = `
        <tr>
            <td>Total</td>
            <td class="text-end">${fmtInt(totals.items)}</td>
            <td class="text-end">${fmtInt(totals.qty)}</td>
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
    $('dailyItems').textContent = fmtInt(totItems);
    $('dailyQty').textContent = fmtInt(totQty);
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
                    return `Qty: ${fmtInt(ctx.raw)}`;
                }}}
            },
            scales: {
                y: { position: 'left', ticks: { callback: v => '฿' + fmt(v) } },
                y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => fmtInt(v) + ' pcs' } },
            }
        }
    });

    // Table
    $('tableDaily').innerHTML = data.map(r => `
        <tr>
            <td><a href="items.php?date_from=${r.day}&date_to=${r.day}" class="text-decoration-none">${formatDay(r.day)}</a></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>
    `).join('');

    $('footDaily').innerHTML = `
        <tr>
            <td>Total (${data.length} days)</td>
            <td class="text-end">${fmtInt(totItems)}</td>
            <td class="text-end">${fmtInt(totQty)}</td>
            <td class="text-end">${fmt(totPrice)}</td>
        </tr>`;

    // --- Type breakdown ---
    const byType = res.by_type || [];
    if (chartDailyType) chartDailyType.destroy();
    chartDailyType = new Chart($('chartDailyType').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: byType.slice(0, 10).map(r => r.type),
            datasets: [{
                data: byType.slice(0, 10).map(r => Number(r.total_price)),
                backgroundColor: COLORS.slice(0, Math.min(byType.length, 10)),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = totPrice > 0 ? ((ctx.raw / totPrice) * 100).toFixed(1) : 0;
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });

    const maxTypeP = byType.length > 0 ? Number(byType[0].total_price) : 1;
    $('tableDailyType').innerHTML = byType.map((r, i) => {
        const pct = totPrice > 0 ? ((Number(r.total_price) / totPrice) * 100) : 0;
        const barW = maxTypeP > 0 ? ((Number(r.total_price) / maxTypeP) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td><span class="badge badge-type">${escHtml(r.type)}</span></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center text-muted py-2">No data</td></tr>';
    const typeTotItems = byType.reduce((s, r) => s + Number(r.items), 0);
    const typeTotQty = byType.reduce((s, r) => s + Number(r.total_qty), 0);
    const typeTotPrice = byType.reduce((s, r) => s + Number(r.total_price), 0);
    $('footDailyType').innerHTML = `<tr><td></td><td>Total</td>
        <td class="text-end">${fmtInt(typeTotItems)}</td>
        <td class="text-end">${fmtInt(typeTotQty)}</td>
        <td class="text-end">${fmt(typeTotPrice)}</td>
        <td><span class="small text-muted">100%</span></td></tr>`;

    // --- Idol breakdown ---
    const byIdol = res.by_idol || [];
    const top10Idol = byIdol.slice(0, 10);
    const othersIdolPrice = byIdol.slice(10).reduce((s, r) => s + Number(r.total_price), 0);
    const idolPieLabels = top10Idol.map(r => r.idol);
    const idolPieData = top10Idol.map(r => Number(r.total_price));
    if (othersIdolPrice > 0) { idolPieLabels.push('Others'); idolPieData.push(othersIdolPrice); }
    if (chartDailyIdol) chartDailyIdol.destroy();
    chartDailyIdol = new Chart($('chartDailyIdol').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: idolPieLabels,
            datasets: [{
                data: idolPieData,
                backgroundColor: COLORS.slice(0, idolPieLabels.length),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = totPrice > 0 ? ((ctx.raw / totPrice) * 100).toFixed(1) : 0;
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });

    const idolTotPrice = byIdol.reduce((s, r) => s + Number(r.total_price), 0);
    const maxIdolP = byIdol.length > 0 ? Number(byIdol[0].total_price) : 1;
    $('tableDailyIdol').innerHTML = byIdol.map((r, i) => {
        const pct = idolTotPrice > 0 ? ((Number(r.total_price) / idolTotPrice) * 100) : 0;
        const barW = maxIdolP > 0 ? ((Number(r.total_price) / maxIdolP) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td><a href="#" class="text-decoration-none fw-semibold"
                onclick="document.querySelector('[data-bs-target=\\'#tabIdol\\']').click();setTimeout(()=>showIdolDetail('${escJs(r.idol)}'),200);return false">${escHtml(r.idol)}</a></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center text-muted py-2">No data</td></tr>';
    const idolTotItems = byIdol.reduce((s, r) => s + Number(r.items), 0);
    const idolTotQty = byIdol.reduce((s, r) => s + Number(r.total_qty), 0);
    $('footDailyIdol').innerHTML = `<tr><td></td><td>Total</td>
        <td class="text-end">${fmtInt(idolTotItems)}</td>
        <td class="text-end">${fmtInt(idolTotQty)}</td>
        <td class="text-end">${fmt(idolTotPrice)}</td>
        <td><span class="small text-muted">100%</span></td></tr>`;
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
    const isType = key === 'type';
    $(tableId).innerHTML = data.map((r, i) => {
        const rank = i + 1;
        const pct = grandTotal > 0 ? ((Number(r.total_price) / grandTotal) * 100) : 0;
        const barWidth = maxPrice > 0 ? ((Number(r.total_price) / maxPrice) * 100) : 0;
        const rankClass = rank <= 3 ? `rank-${rank}` : '';
        const medal = rank === 1 ? ' <i class="bi bi-trophy-fill rank-1"></i>' : rank === 2 ? ' <i class="bi bi-trophy-fill rank-2"></i>' : rank === 3 ? ' <i class="bi bi-trophy-fill rank-3"></i>' : '';
        const nameHtml = isIdol
            ? `<a href="#" class="text-decoration-none fw-semibold" onclick="showIdolDetail('${escJs(r[key])}');return false">${escHtml(r[key])}</a>${medal}`
            : isType
            ? `<a href="#" class="text-decoration-none fw-semibold" onclick="showTypeDetail('${escJs(r[key])}');return false">${escHtml(r[key])}</a>${medal}`
            : `${escHtml(r[key])}${medal}`;
        return `
        <tr>
            <td class="${rankClass}">${rank}</td>
            <td>${nameHtml}</td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
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
            <td class="text-end">${fmtInt(totals.items)}</td>
            <td class="text-end">${fmtInt(totals.qty)}</td>
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

function monthLastDay(month) {
    const [y, m] = month.split('-');
    const d = new Date(parseInt(y), parseInt(m), 0);
    const yy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yy}-${mm}-${dd}`;
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
    $('idolDetItems').textContent = fmtInt(totItems);
    $('idolDetQty').textContent = fmtInt(totQty);
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
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footIdolDetailType').innerHTML = `<tr>
        <td></td><td>Total</td>
        <td class="text-end">${fmtInt(totItems)}</td>
        <td class="text-end">${fmtInt(totQty)}</td>
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

    $('tableIdolDetailMonth').innerHTML = byMonth.map(r => {
        const dateFrom = r.month + '-01';
        const dateTo   = monthLastDay(r.month);
        const url = 'items.php?idol=' + encodeURIComponent(idol) + '&date_from=' + dateFrom + '&date_to=' + dateTo;
        return `<tr>
            <td><a href="${url}" class="text-decoration-none">${formatMonth(r.month)}</a></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>`;
    }).join('');

    $('footIdolDetailMonth').innerHTML = `<tr>
        <td>Total</td>
        <td class="text-end">${fmtInt(mTotals.items)}</td>
        <td class="text-end">${fmtInt(mTotals.qty)}</td>
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
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footGroup').innerHTML = `<tr>
        <td></td><td>Total</td><td></td>
        <td class="text-end">${fmtInt(totals.items)}</td>
        <td class="text-end">${fmtInt(totals.qty)}</td>
        <td class="text-end">${fmt(totals.price)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;
}

async function showGroupMembers(idx) {
    const group = groupData[idx];
    if (!group) {
        $('groupDetailCard').style.display = 'none';
        return;
    }

    $('groupDetailName').textContent = group.name;
    $('groupDetailCard').style.display = 'block';

    // Prefer the v5 endpoint: returns primary members + sub-unit memberships + monthly.
    let detail = null;
    if (group.group_id) {
        const res = await fetch(`api.php?action=report_group_detail&group_id=${group.group_id}`).then(r => r.json());
        if (!res.error) detail = res;
    }

    let members = detail ? detail.members : null;
    if (!members) {
        // Legacy fallback — derive members by name (for solo / unmapped buckets)
        const res = await fetch('api.php?action=report_idol').then(r => r.json());
        const memberSet = new Set(group.members || []);
        members = (res.data || []).filter(r => memberSet.has(r.idol));
    }
    members.sort((a, b) => Number(b.total_price) - Number(a.total_price));

    const groupTotal = Number(group.total_price);
    const maxP = members.length > 0 ? Number(members[0].total_price) : 1;

    $('tableGroupDetail').innerHTML = members.map((r, i) => {
        const pct = groupTotal > 0 ? ((Number(r.total_price) / groupTotal) * 100) : 0;
        const barW = maxP > 0 ? ((Number(r.total_price) / maxP) * 100) : 0;
        const displayName = r.display || r.idol || '';
        return `<tr>
            <td>${i + 1}</td>
            <td><a href="#" class="text-decoration-none fw-semibold" onclick="event.stopPropagation();document.querySelector('[data-bs-target=\\'#tabIdol\\']').click();setTimeout(()=>showIdolDetail('${escJs(r.idol || displayName)}'),200);return false">${escHtml(displayName)}</a></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    // Sub-units (non-primary memberships) — v5 feature
    if (detail && Array.isArray(detail.sub_units) && detail.sub_units.length > 0) {
        renderGroupSubUnits(detail.sub_units);
    } else {
        const su = $('groupSubUnits');
        if (su) su.style.display = 'none';
    }
}

function renderGroupSubUnits(units) {
    let card = $('groupSubUnits');
    if (!card) {
        const detailCard = $('groupDetailCard');
        if (!detailCard) return;
        const div = document.createElement('div');
        div.id = 'groupSubUnits';
        div.className = 'mt-2 small';
        detailCard.appendChild(div);
        card = div;
    }
    card.style.display = '';
    card.innerHTML = `
        <div class="text-muted mb-1"><i class="bi bi-people"></i> Sub-unit / project members in this group:</div>
        ${units.map(u => {
            const display = u.display_hint ? `${u.idol} [${u.display_hint}]` : u.idol;
            const range = `${u.start_date || '—'} → ${u.end_date || 'current'}`;
            return `<div class="d-flex justify-content-between border-bottom py-1">
                <span>${escHtml(display)}</span>
                <span class="text-muted">${range}</span>
            </div>`;
        }).join('')}
    `;
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
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footCompany').innerHTML = `<tr>
        <td></td><td>Total</td>
        <td class="text-end">${fmtInt(totals.items)}</td>
        <td class="text-end">${fmtInt(totals.qty)}</td>
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
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');
}

// --- Type Detail ---
async function showTypeDetail(type) {
    $('typeDetailName').textContent = type;
    $('typeMainView').style.display = 'none';
    $('typeDetailView').style.display = 'block';
    $('tableTypeDetail').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Loading...</td></tr>';

    const res = await fetch('api.php?action=report_type_detail&type=' + encodeURIComponent(type)).then(r => r.json());
    const members = res.members || [];

    const totItems = members.reduce((s, r) => s + r.items_count, 0);
    const totQty   = members.reduce((s, r) => s + r.total_qty, 0);
    const totPrice = members.reduce((s, r) => s + r.total_price, 0);

    $('typeDetItems').textContent   = fmtInt(totItems);
    $('typeDetQty').textContent     = fmtInt(totQty);
    $('typeDetSpent').textContent   = '฿' + fmt(totPrice);
    $('typeDetMembers').textContent = members.length;

    if (members.length === 0) {
        $('tableTypeDetail').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No member data</td></tr>';
        $('footTypeDetail').innerHTML = '';
        return;
    }

    const maxP = members[0].total_price;
    $('tableTypeDetail').innerHTML = members.map((m, i) => {
        const pct = totPrice > 0 ? ((m.total_price / totPrice) * 100) : 0;
        const barW = maxP > 0 ? ((m.total_price / maxP) * 100) : 0;
        return `<tr>
            <td>${i + 1}</td>
            <td><a href="#" class="text-decoration-none fw-semibold"
                onclick="document.querySelector('[data-bs-target=\\'#tabIdol\\']').click();setTimeout(()=>showIdolDetail('${escJs(m.member)}'),200);return false">${escHtml(m.member)}</a></td>
            <td class="text-muted">${m.group ? escHtml(m.group) : '-'}</td>
            <td class="text-muted">${m.company ? escHtml(m.company) : '-'}</td>
            <td class="text-end">${fmtInt(m.items_count)}</td>
            <td class="text-end">${fmtInt(m.total_qty)}</td>
            <td class="text-end">${fmt(m.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    $('footTypeDetail').innerHTML = `<tr>
        <td></td><td>Total</td><td></td><td></td>
        <td class="text-end">${fmtInt(totItems)}</td>
        <td class="text-end">${fmtInt(totQty)}</td>
        <td class="text-end">${fmt(totPrice)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;

    // --- Monthly Breakdown for Type ---
    const byMonth = res.by_month || [];
    if (chartTypeDetailMonth) chartTypeDetailMonth.destroy();
    chartTypeDetailMonth = new Chart($('chartTypeDetailMonth').getContext('2d'), {
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

    const mTotals = byMonth.reduce((a, r) => {
        a.items += Number(r.items); a.qty += Number(r.total_qty); a.price += Number(r.total_price);
        return a;
    }, { items: 0, qty: 0, price: 0 });

    $('tableTypeDetailMonth').innerHTML = byMonth.map(r => {
        const dateFrom = r.month + '-01';
        const dateTo = monthLastDay(r.month);
        const url = 'items.php?type=' + encodeURIComponent(type) + '&date_from=' + dateFrom + '&date_to=' + dateTo;
        return `<tr>
            <td><a href="${url}" class="text-decoration-none">${formatMonth(r.month)}</a></td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="4" class="text-center text-muted py-2">No data</td></tr>';

    $('footTypeDetailMonth').innerHTML = `<tr>
        <td>Total</td>
        <td class="text-end">${fmtInt(mTotals.items)}</td>
        <td class="text-end">${fmtInt(mTotals.qty)}</td>
        <td class="text-end">${fmt(mTotals.price)}</td>
    </tr>`;
}

function hideTypeDetail() {
    $('typeDetailView').style.display = 'none';
    $('typeMainView').style.display = 'block';
}

// =====================================================================
//  Shared helpers for the new report tabs
// =====================================================================
function makeDoughnut(prev, id, labels, data) {
    if (prev) prev.destroy();
    const total = data.reduce((s, v) => s + Number(v), 0);
    return new Chart($(id).getContext('2d'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: COLORS.slice(0, labels.length), borderWidth: 2, borderColor: '#fff' }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => {
                    const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                    return `${ctx.label}: ฿${fmt(ctx.raw)} (${pct}%)`;
                }}}
            }
        }
    });
}
function barOptsBaht() {
    return {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => '฿' + fmt(ctx.raw) } } },
        scales: { y: { ticks: { callback: v => '฿' + fmt(v) } } }
    };
}

// =====================================================================
//  #1 Overview
// =====================================================================
async function loadOverview() {
    const res = await fetch('api.php?action=report_dashboard').then(r => r.json());
    const k = res.kpis || {};
    $('ovSpent').textContent = '฿' + fmt(k.total_spent || 0);
    $('ovSpentSub').textContent = (k.active_months || 0) + ' active months';
    $('ovItems').textContent = fmtInt(k.total_items || 0);
    $('ovItemsSub').textContent = fmtInt(k.total_qty || 0) + ' qty';
    $('ovAvgMonth').textContent = '฿' + fmt(k.avg_per_month || 0);
    $('ovAvgMonthSub').textContent = 'top type: ' + (k.top_type || '-');
    $('ovMom').textContent = '฿' + fmt(k.latest_month_spent || 0);
    const mom = k.mom_change_pct;
    if (mom === null || mom === undefined) {
        $('ovMomSub').innerHTML = '&nbsp;';
    } else {
        const up = mom >= 0;
        $('ovMomSub').innerHTML = `<span class="${up ? 'text-success' : 'text-danger'}"><i class="bi bi-arrow-${up ? 'up' : 'down'}"></i> ${Math.abs(mom).toFixed(1)}% vs prev</span>`;
    }

    const monthly = res.monthly || [];
    if (chartOvMonthly) chartOvMonthly.destroy();
    chartOvMonthly = new Chart($('chartOvMonthly').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthly.map(r => formatMonth(r.month)),
            datasets: [{ label: 'Spending (฿)', data: monthly.map(r => Number(r.total_price)), backgroundColor: 'rgba(124,58,237,0.7)', borderRadius: 4 }]
        },
        options: barOptsBaht()
    });

    $('ovTopMembers').innerHTML = (res.top_members || []).map((r, i) =>
        `<li class="list-group-item d-flex justify-content-between py-1"><span>${i + 1}. ${escHtml(r.display || r.idol)}</span><strong>฿${fmt(r.total_price)}</strong></li>`
    ).join('') || '<li class="list-group-item text-muted">No data</li>';

    $('ovHighlights').innerHTML = `
        <li class="list-group-item d-flex justify-content-between py-1"><span><i class="bi bi-person-hearts text-primary"></i> Top member</span><strong>${escHtml(k.top_member || '-')}</strong></li>
        <li class="list-group-item d-flex justify-content-between py-1"><span><i class="bi bi-diagram-3 text-primary"></i> Top group</span><strong>${escHtml(k.top_group || '-')}</strong></li>
        <li class="list-group-item d-flex justify-content-between py-1"><span><i class="bi bi-tags text-primary"></i> Top type</span><strong>${escHtml(k.top_type || '-')}</strong></li>
        <li class="list-group-item d-flex justify-content-between py-1"><span><i class="bi bi-calendar3 text-primary"></i> Latest month</span><strong>${k.latest_month ? formatMonth(k.latest_month) : '-'}</strong></li>`;

    const byType = res.by_type || [];
    chartOvType = makeDoughnut(chartOvType, 'chartOvType', byType.slice(0, 10).map(r => r.type), byType.slice(0, 10).map(r => Number(r.total_price)));
    const byCompany = res.by_company || [];
    chartOvCompany = makeDoughnut(chartOvCompany, 'chartOvCompany', byCompany.map(r => r.name), byCompany.map(r => Number(r.total_price)));
}

// =====================================================================
//  #5 Trends (cumulative + MoM growth + forecast)
// =====================================================================
async function loadTrends() {
    const res = await fetch('api.php?action=report_monthly').then(r => r.json());
    const data = res.data || [];

    let cum = 0;
    const cumData = data.map(r => { cum += Number(r.total_price); return cum; });
    $('trCumulative').textContent = '฿' + fmt(cum);

    let best = null;
    data.forEach(r => { if (!best || Number(r.total_price) > Number(best.total_price)) best = r; });
    $('trBest').textContent = best ? `${formatMonth(best.month)} · ฿${fmt(best.total_price)}` : '-';

    const growth = [];
    for (let i = 1; i < data.length; i++) {
        const prev = Number(data[i - 1].total_price);
        growth.push(prev > 0 ? ((Number(data[i].total_price) - prev) / prev * 100) : null);
    }
    const validG = growth.filter(g => g !== null);
    const avgG = validG.length ? validG.reduce((s, g) => s + g, 0) / validG.length : null;
    $('trGrowth').textContent = avgG === null ? '-' : (avgG >= 0 ? '+' : '') + avgG.toFixed(1) + '%';

    const now = new Date();
    const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    const cur = data.find(r => r.month === ym);
    if (cur) {
        const dim = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
        const day = now.getDate();
        const proj = Number(cur.total_price) / day * dim;
        $('trForecast').textContent = '฿' + fmt(proj);
        $('trForecastSub').textContent = `฿${fmt(cur.total_price)} so far · day ${day}/${dim}`;
    } else {
        $('trForecast').textContent = '—';
        $('trForecastSub').textContent = 'no orders this month';
    }

    if (chartTrendCumulative) chartTrendCumulative.destroy();
    chartTrendCumulative = new Chart($('chartTrendCumulative').getContext('2d'), {
        type: 'line',
        data: {
            labels: data.map(r => formatMonth(r.month)),
            datasets: [{ label: 'Cumulative (฿)', data: cumData, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.1)', fill: true, tension: 0.3, pointRadius: 2 }]
        },
        options: barOptsBaht()
    });

    if (chartTrendMoM) chartTrendMoM.destroy();
    chartTrendMoM = new Chart($('chartTrendMoM').getContext('2d'), {
        type: 'bar',
        data: {
            labels: data.slice(1).map(r => formatMonth(r.month)),
            datasets: [{ label: 'MoM %', data: growth, backgroundColor: growth.map(g => g >= 0 ? 'rgba(16,163,74,0.7)' : 'rgba(220,38,38,0.7)'), borderRadius: 4 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ctx.raw === null ? 'n/a' : ctx.raw.toFixed(1) + '%' } } },
            scales: { y: { ticks: { callback: v => v + '%' } } }
        }
    });
}

// =====================================================================
//  #6 Seasonality (day-of-week + month-of-year)
// =====================================================================
function seasonRow(label, r, total) {
    const pct = total > 0 ? (Number(r.total_price) / total * 100) : 0;
    return `<tr>
        <td>${label}</td>
        <td class="text-end">${fmtInt(r.items)}</td>
        <td class="text-end">${fmtInt(r.total_qty)}</td>
        <td class="text-end">${fmt(r.total_price)}</td>
        <td><div class="d-flex align-items-center gap-1">
            <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${pct}%"></div></div>
            <span class="small text-muted" style="min-width:36px">${pct.toFixed(1)}%</span>
        </div></td>
    </tr>`;
}
async function loadSeasonality() {
    const res = await fetch('api.php?action=report_seasonality').then(r => r.json());

    const dows = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const wkMap = {}; (res.weekday || []).forEach(r => wkMap[r.dow] = r);
    const wkFull = dows.map((n, i) => wkMap[i] || { dow: i, items: 0, total_qty: 0, total_price: 0 });
    const wkTotal = wkFull.reduce((s, r) => s + Number(r.total_price), 0);
    if (chartSeasonWeekday) chartSeasonWeekday.destroy();
    chartSeasonWeekday = new Chart($('chartSeasonWeekday').getContext('2d'), {
        type: 'bar',
        data: { labels: dows.map(d => d.slice(0, 3)), datasets: [{ data: wkFull.map(r => Number(r.total_price)), backgroundColor: 'rgba(124,58,237,0.7)', borderRadius: 4 }] },
        options: barOptsBaht()
    });
    $('tableSeasonWeekday').innerHTML = wkFull.map((r, i) => seasonRow(dows[i], r, wkTotal)).join('');

    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const moyMap = {}; (res.month_of_year || []).forEach(r => moyMap[r.moy] = r);
    const moyFull = months.map((n, i) => moyMap[i + 1] || { moy: i + 1, items: 0, total_qty: 0, total_price: 0 });
    const moyTotal = moyFull.reduce((s, r) => s + Number(r.total_price), 0);
    if (chartSeasonMonth) chartSeasonMonth.destroy();
    chartSeasonMonth = new Chart($('chartSeasonMonth').getContext('2d'), {
        type: 'bar',
        data: { labels: months.map(m => m.slice(0, 3)), datasets: [{ data: moyFull.map(r => Number(r.total_price)), backgroundColor: 'rgba(236,72,153,0.7)', borderRadius: 4 }] },
        options: barOptsBaht()
    });
    $('tableSeasonMonth').innerHTML = moyFull.map((r, i) => seasonRow(months[i], r, moyTotal)).join('');
}

// =====================================================================
//  #7 Compare two members
// =====================================================================
function summarize(arr) {
    return (arr || []).reduce((a, r) => { a.items += Number(r.items); a.qty += Number(r.total_qty); a.price += Number(r.total_price); return a; }, { items: 0, qty: 0, price: 0 });
}
function cmpCard(name, s, color) {
    return `<div class="col-md-6"><div class="card p-3">
        <div class="fw-bold mb-2" style="color:${color}">${escHtml(name)}</div>
        <div class="row text-center g-0">
            <div class="col"><div class="small text-muted">Items</div><div class="fw-bold">${fmtInt(s.items)}</div></div>
            <div class="col"><div class="small text-muted">Qty</div><div class="fw-bold">${fmtInt(s.qty)}</div></div>
            <div class="col"><div class="small text-muted">Spent</div><div class="fw-bold">฿${fmt(s.price)}</div></div>
            <div class="col"><div class="small text-muted">Avg/Item</div><div class="fw-bold">฿${fmt(s.items > 0 ? Math.round(s.price / s.items) : 0)}</div></div>
        </div>
    </div></div>`;
}
async function initCompare() {
    const res = await fetch('api.php?action=report_idol').then(r => r.json());
    cmpMembers = (res.data || []).filter(r => r.idol && r.idol !== '-');
    const opts = cmpMembers.map((r, i) => `<option value="${i}">${escHtml(r.display || r.idol)}</option>`).join('');
    $('cmpSelA').innerHTML = opts;
    $('cmpSelB').innerHTML = opts;
    if (cmpMembers.length > 1) $('cmpSelB').value = '1';
    $('cmpSelA').addEventListener('change', loadCompare);
    $('cmpSelB').addEventListener('change', loadCompare);
    if (cmpMembers.length) loadCompare();
}
async function loadCompare() {
    const a = cmpMembers[+$('cmpSelA').value];
    const b = cmpMembers[+$('cmpSelB').value];
    if (!a || !b) return;
    const [da, db] = await Promise.all([
        fetch('api.php?action=report_idol_detail&idol=' + encodeURIComponent(a.idol)).then(r => r.json()),
        fetch('api.php?action=report_idol_detail&idol=' + encodeURIComponent(b.idol)).then(r => r.json())
    ]);

    $('cmpCards').innerHTML = cmpCard(a.display || a.idol, summarize(da.by_type), 'var(--primary)')
                            + cmpCard(b.display || b.idol, summarize(db.by_type), '#ec4899');

    // Monthly comparison (union of months)
    const months = [...new Set([...(da.by_month || []).map(r => r.month), ...(db.by_month || []).map(r => r.month)])].sort();
    const mapA = {}; (da.by_month || []).forEach(r => mapA[r.month] = Number(r.total_price));
    const mapB = {}; (db.by_month || []).forEach(r => mapB[r.month] = Number(r.total_price));
    if (chartCompareMonth) chartCompareMonth.destroy();
    chartCompareMonth = new Chart($('chartCompareMonth').getContext('2d'), {
        type: 'line',
        data: {
            labels: months.map(formatMonth),
            datasets: [
                { label: a.display || a.idol, data: months.map(m => mapA[m] || 0), borderColor: '#7c3aed', backgroundColor: '#7c3aed', tension: 0.3, pointRadius: 2 },
                { label: b.display || b.idol, data: months.map(m => mapB[m] || 0), borderColor: '#ec4899', backgroundColor: '#ec4899', tension: 0.3, pointRadius: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ฿' + fmt(ctx.raw) } } },
            scales: { y: { ticks: { callback: v => '฿' + fmt(v) } } }
        }
    });

    // Type comparison (union of types)
    const types = [...new Set([...(da.by_type || []).map(r => r.type), ...(db.by_type || []).map(r => r.type)])];
    const tA = {}; (da.by_type || []).forEach(r => tA[r.type] = Number(r.total_price));
    const tB = {}; (db.by_type || []).forEach(r => tB[r.type] = Number(r.total_price));
    if (chartCompareType) chartCompareType.destroy();
    chartCompareType = new Chart($('chartCompareType').getContext('2d'), {
        type: 'bar',
        data: {
            labels: types,
            datasets: [
                { label: a.display || a.idol, data: types.map(t => tA[t] || 0), backgroundColor: '#7c3aed' },
                { label: b.display || b.idol, data: types.map(t => tB[t] || 0), backgroundColor: '#ec4899' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ฿' + fmt(ctx.raw) } } },
            scales: { y: { ticks: { callback: v => '฿' + fmt(v) } } }
        }
    });
}

// =====================================================================
//  #3 By Unit
// =====================================================================
async function loadUnit() {
    const res = await fetch('api.php?action=report_by_unit').then(r => r.json());
    unitData = res.data || [];
    if (unitData.length === 0) {
        $('tableUnit').innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No unit data — assign members to a unit-category entity in Idols.</td></tr>';
        $('footUnit').innerHTML = '';
        if (chartUnitPie) { chartUnitPie.destroy(); chartUnitPie = null; }
        return;
    }
    const grand = unitData.reduce((s, r) => s + Number(r.total_price), 0);
    const maxP = Number(unitData[0].total_price) || 1;
    chartUnitPie = makeDoughnut(chartUnitPie, 'chartUnitPie', unitData.slice(0, 10).map(r => r.name), unitData.slice(0, 10).map(r => Number(r.total_price)));

    $('tableUnit').innerHTML = unitData.map((r, i) => {
        const rank = i + 1;
        const pct = grand > 0 ? (Number(r.total_price) / grand * 100) : 0;
        const barW = maxP > 0 ? (Number(r.total_price) / maxP * 100) : 0;
        const rankClass = rank <= 3 ? `rank-${rank}` : '';
        const medal = rank <= 3 ? ` <i class="bi bi-trophy-fill rank-${rank}"></i>` : '';
        return `<tr>
            <td class="${rankClass}">${rank}</td>
            <td><strong>${escHtml(r.name)}</strong>${medal}</td>
            <td class="text-muted">${r.parent ? escHtml(r.parent) : '-'}</td>
            <td class="text-end">${fmtInt(r.members)}</td>
            <td class="text-end">${fmtInt(r.items)}</td>
            <td class="text-end">${fmtInt(r.total_qty)}</td>
            <td class="text-end">${fmt(r.total_price)}</td>
            <td><div class="d-flex align-items-center gap-1">
                <div class="progress-bar-custom flex-grow-1"><div class="fill" style="width:${barW}%"></div></div>
                <span class="small text-muted" style="min-width:40px">${pct.toFixed(1)}%</span>
            </div></td>
        </tr>`;
    }).join('');

    const ut = unitData.reduce((a, r) => { a.items += r.items; a.qty += r.total_qty; a.price += Number(r.total_price); return a; }, { items: 0, qty: 0, price: 0 });
    $('footUnit').innerHTML = `<tr>
        <td></td><td>Total</td><td></td><td></td>
        <td class="text-end">${fmtInt(ut.items)}</td>
        <td class="text-end">${fmtInt(ut.qty)}</td>
        <td class="text-end">${fmt(ut.price)}</td>
        <td><span class="small text-muted">100%</span></td>
    </tr>`;
}

// =====================================================================
//  #2 By Event (event_date + lead time)
// =====================================================================
async function loadEvent() {
    const res = await fetch('api.php?action=report_event').then(r => r.json());
    const data = res.data || [];
    const lt = res.lead_time || {};
    $('evCount').textContent = fmtInt(data.length);
    $('evLead').textContent = (lt.avg_days !== null && lt.avg_days !== undefined) ? lt.avg_days + ' d' : '-';
    $('evLeadRange').textContent = (lt.min_days !== null && lt.min_days !== undefined) ? `${lt.min_days} – ${lt.max_days} d` : '-';
    $('evNoEvent').textContent = fmtInt(res.no_event || 0);

    const chron = [...data].reverse();
    if (chartEvent) chartEvent.destroy();
    chartEvent = new Chart($('chartEvent').getContext('2d'), {
        type: 'bar',
        data: { labels: chron.map(r => r.event), datasets: [{ data: chron.map(r => Number(r.total_price)), backgroundColor: 'rgba(124,58,237,0.7)', borderRadius: 4 }] },
        options: barOptsBaht()
    });

    const tot = data.reduce((a, r) => { a.items += r.items; a.qty += r.total_qty; a.price += Number(r.total_price); return a; }, { items: 0, qty: 0, price: 0 });
    $('tableEvent').innerHTML = data.map(r => `<tr>
        <td><a href="items.php?date_from=${r.event}&date_to=${r.event}" class="text-decoration-none">${r.event}</a></td>
        <td class="text-end">${fmtInt(r.items)}</td>
        <td class="text-end">${fmtInt(r.idols)}</td>
        <td class="text-end">${fmtInt(r.total_qty)}</td>
        <td class="text-end">${fmt(r.total_price)}</td>
    </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-3">No items have an event date yet</td></tr>';
    $('footEvent').innerHTML = data.length ? `<tr>
        <td>Total (${data.length})</td>
        <td class="text-end">${fmtInt(tot.items)}</td>
        <td></td>
        <td class="text-end">${fmtInt(tot.qty)}</td>
        <td class="text-end">${fmt(tot.price)}</td>
    </tr>` : '';
}

// =====================================================================
//  #4 Top Items
// =====================================================================
async function loadTopItems() {
    const res = await fetch('api.php?action=report_top_items').then(r => r.json());

    $('tableTopExpensive').innerHTML = (res.expensive || []).map((r, i) => `<tr>
        <td>${i + 1}</td>
        <td>${escHtml(r.title)}</td>
        <td>${escHtml(r.idol)}</td>
        <td><span class="badge badge-type">${escHtml(r.type)}</span></td>
        <td class="text-end">${fmt(r.price_per_qty)}</td>
        <td class="text-end">${fmtInt(r.qty)}</td>
        <td class="text-end fw-semibold">${fmt(r.line_total)}</td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-center text-muted py-3">No data</td></tr>';

    $('tableTopFrequent').innerHTML = (res.frequent || []).map((r, i) => `<tr>
        <td>${i + 1}</td>
        <td>${escHtml(r.title)}</td>
        <td class="text-end">${fmtInt(r.items)}</td>
        <td class="text-end">${fmtInt(r.total_qty)}</td>
        <td class="text-end">${fmt(r.total_price)}</td>
    </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-3">No data</td></tr>';

    $('tableTopAvg').innerHTML = (res.avg_price || []).map(r => `<tr>
        <td><span class="badge badge-type">${escHtml(r.type)}</span></td>
        <td class="text-end fw-semibold">${fmt(r.avg_price)}</td>
        <td class="text-end text-muted">${fmt(r.min_price)}</td>
        <td class="text-end text-muted">${fmt(r.max_price)}</td>
        <td class="text-end">${fmtInt(r.items)}</td>
    </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted py-3">No data</td></tr>';
}

// =====================================================================
//  #8 Inactive members
// =====================================================================
let inactiveData = [];
async function loadInactive() {
    const res = await fetch('api.php?action=report_inactive').then(r => r.json());
    inactiveData = res.data || [];
    document.querySelectorAll('#inactiveThresholds button').forEach(b => {
        b.addEventListener('click', () => {
            document.querySelectorAll('#inactiveThresholds button').forEach(x => x.classList.remove('active'));
            b.classList.add('active');
            renderInactive(+b.dataset.days);
        });
    });
    renderInactive(90);
}
function renderInactive(days) {
    const rows = inactiveData.filter(r => r.days_since !== null && r.days_since >= days);
    $('inactiveSummary').textContent = `${rows.length} member(s) inactive ≥ ${days} days`;
    $('tableInactive').innerHTML = rows.map((r, i) => `<tr>
        <td>${i + 1}</td>
        <td><a href="#" class="text-decoration-none fw-semibold" onclick="document.querySelector('[data-bs-target=\\'#tabIdol\\']').click();setTimeout(()=>showIdolDetail('${escJs(r.idol)}'),200);return false">${escHtml(r.display || r.idol)}</a></td>
        <td>${r.last_order || '-'}</td>
        <td class="text-end">${fmtInt(r.days_since)}</td>
        <td class="text-end">${fmtInt(r.items)}</td>
        <td class="text-end">${fmt(r.total_price)}</td>
    </tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted py-3">No inactive members for this threshold 🎉</td></tr>';
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
