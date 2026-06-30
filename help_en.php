<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help - Numa Log</title>
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
        .help-hero {
            background: linear-gradient(135deg, var(--primary), #a78bfa);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 1.5rem;
        }
        .help-hero h1 { font-weight: 700; }
        .help-hero p { opacity: .85; margin-bottom: 0; }
        .accordion-button:not(.collapsed) {
            background: #f3f0ff;
            color: var(--primary);
            font-weight: 600;
        }
        .accordion-button:focus { box-shadow: 0 0 0 .2rem rgba(124,58,237,.25); }
        .feature-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; flex-shrink: 0;
        }
        .feature-icon-purple { background: #ede9fe; color: #7c3aed; }
        .feature-icon-pink { background: #fce7f3; color: #db2777; }
        .feature-icon-blue { background: #dbeafe; color: #2563eb; }
        .feature-icon-green { background: #d1fae5; color: #059669; }
        .feature-icon-amber { background: #fef3c7; color: #d97706; }
        .feature-icon-red { background: #fee2e2; color: #dc2626; }
        .feature-icon-cyan { background: #cffafe; color: #0891b2; }
        .toc-link { color: var(--primary); text-decoration: none; padding: 6px 12px; display: block; border-radius: 6px; font-size: 13px; }
        .toc-link:hover { background: #f3f0ff; color: var(--primary-hover); }
        .toc-link i { width: 20px; text-align: center; }
        .shortcut-key {
            display: inline-block; background: #e5e7eb; color: #374151;
            padding: 1px 8px; border-radius: 4px; font-size: 12px;
            font-family: monospace; border: 1px solid #d1d5db;
        }
        .tip-box {
            background: #fffbeb; border-left: 4px solid #f59e0b;
            padding: 12px 16px; border-radius: 0 8px 8px 0;
            font-size: 13px; margin: 12px 0;
        }
        .warning-box {
            background: #fef2f2; border-left: 4px solid #ef4444;
            padding: 12px 16px; border-radius: 0 8px 8px 0;
            font-size: 13px; margin: 12px 0;
        }
        .step-number {
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--primary); color: white;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; flex-shrink: 0;
        }
        .help-table th { background: #f9fafb; font-size: 13px; }
        .help-table td { font-size: 13px; vertical-align: middle; }
        .nav-section { position: sticky; top: 1rem; }
        @media (max-width: 991px) {
            .nav-section { position: static; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

<?php $navActive = 'help'; $navIcon = 'bi-stars'; $navTitle = 'Numa Log'; require __DIR__ . '/navbar.php'; ?>

<!-- Hero Section -->
<div class="help-hero">
    <div class="container">
        <h1><i class="bi bi-question-circle"></i> Help & Guide</h1>
        <p>User guide for Numa Log &mdash; Idol merchandise purchase tracking and analytics</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">

        <!-- Sidebar: Table of Contents -->
        <div class="col-lg-3 mb-3">
            <div class="card nav-section">
                <div class="card-body p-2">
                    <div class="fw-bold text-muted small px-3 py-2">MENU</div>
                    <a href="#getting-started" class="toc-link"><i class="bi bi-rocket-takeoff"></i> Getting Started</a>
                    <a href="#items" class="toc-link"><i class="bi bi-list-ul"></i> Item Management</a>
                    <a href="#reports" class="toc-link"><i class="bi bi-bar-chart-line"></i> Reports</a>
                    <a href="#budget" class="toc-link"><i class="bi bi-piggy-bank"></i> Budgets</a>
                    <a href="#events" class="toc-link"><i class="bi bi-calendar-event"></i> Event Management</a>
                    <a href="#idols" class="toc-link"><i class="bi bi-people"></i> Idol Management</a>
                    <a href="#types" class="toc-link"><i class="bi bi-tags"></i> Type Management</a>
                    <a href="#users" class="toc-link"><i class="bi bi-person-gear"></i> User Management</a>
                    <a href="#backup" class="toc-link"><i class="bi bi-database"></i> Backup & Restore</a>
                    <a href="#import" class="toc-link"><i class="bi bi-file-earmark-excel"></i> Excel Import</a>
                    <a href="#roles" class="toc-link"><i class="bi bi-shield-lock"></i> Permissions</a>
                    <a href="#faq" class="toc-link"><i class="bi bi-chat-dots"></i> FAQ</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">

            <!-- Getting Started -->
            <div class="card mb-3" id="getting-started">
                <div class="card-body">
                    <h4 class="mb-3"><i class="bi bi-rocket-takeoff text-primary"></i> Getting Started</h4>
                    <p>Numa Log helps you record idol merchandise purchases, analyze spending, and manage idol data in an organized way.</p>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">1</span>
                        <div>
                            <strong>Log in</strong><br>
                            <span class="text-muted">Use Username: <code>admin</code> / Password: <code>admin</code>, then change the password immediately &mdash; you will be taken to the <strong>Dashboard</strong> automatically after login <span class="badge bg-info" style="font-size:.65rem">v1.6.0</span></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">2</span>
                        <div>
                            <strong>Set up idol data</strong><br>
                            <span class="text-muted">Go to the <strong>Idols</strong> page to add companies, groups, and members</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">3</span>
                        <div>
                            <strong>Set up item types</strong><br>
                            <span class="text-muted">Go to the <strong>Types</strong> page to add item types (e.g., Photocard, T-Shirt)</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">4</span>
                        <div>
                            <strong>Start recording items</strong><br>
                            <span class="text-muted">Go to the <strong>Items</strong> page and click <strong>Add Item</strong> to start recording purchases</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="step-number">5</span>
                        <div>
                            <strong>View reports</strong><br>
                            <span class="text-muted">Go to the <strong>Report</strong> page to see spending analytics across 13 views <span class="badge bg-info" style="font-size:.65rem">v1.6.1</span></span>
                        </div>
                    </div>
                    <div class="tip-box mt-3">
                        <i class="bi bi-translate"></i> <strong>v1.7.0 Language Switcher:</strong> Toggle between <strong>EN / TH</strong> using the button in the top-right corner of every page. Your preference is saved across sessions.
                    </div>
                </div>
            </div>

            <!-- Items Management -->
            <div class="card mb-3" id="items">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-purple"><i class="bi bi-list-ul"></i></div>
                        <h4 class="mb-0">Item Management</h4>
                    </div>
                    <p class="text-muted">The main page (<strong>Items</strong>) for recording all purchase data.</p>

                    <div class="accordion" id="accItems">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#itemAdd">
                                    <i class="bi bi-plus-circle me-2"></i> Add New Item
                                </button>
                            </h2>
                            <div id="itemAdd" class="accordion-collapse collapse show" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Click the <span class="shortcut-key">Add Item</span> button in the Filters card header <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.8</span></li>
                                        <li>Fill in the form:
                                            <table class="table table-sm help-table mt-2 mb-2">
                                                <tr><th style="width:140px">Order Date</th><td>Purchase date</td></tr>
                                                <tr><th>Event Date</th><td>Event date (if applicable)</td></tr>
                                                <tr><th>Event</th><td>Pick a named event from the searchable dropdown &mdash; auto-fills Event Date <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.7</span> (see <a href="#events">Event Management</a>)</td></tr>
                                                <tr><th>Title</th><td>Item name</td></tr>
                                                <tr><th>Idol</th><td>Idol/group name &mdash; type to search from dropdown</td></tr>
                                                <tr><th>Type</th><td>Item type &mdash; type to search from dropdown</td></tr>
                                                <tr><th>Price per Qty</th><td>Price per unit</td></tr>
                                                <tr><th>Qty</th><td>Quantity</td></tr>
                                            </table>
                                        </li>
                                        <li>Click <strong>Save</strong></li>
                                    </ol>
                                    <div class="tip-box">
                                        <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> The Idol and Type fields are searchable dropdowns. You can type to search, or enter a new name directly without adding it to the Idols/Types page first.
                                    </div>
                                    <div class="tip-box mt-2" style="background:#fff7ed;border-left:3px solid #f59e0b">
                                        <i class="bi bi-info-circle"></i> <strong>v1.5:</strong> If the entered idol name matches more than one member (e.g. "Yuna" in ITZY and AKB48), the form shows a candidate list under the Idol field for you to pick the correct one &mdash; the save retries automatically after you choose.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#itemEdit">
                                    <i class="bi bi-pencil me-2"></i> Edit / Clone / Delete
                                </button>
                            </h2>
                            <div id="itemEdit" class="accordion-collapse collapse" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <table class="table table-sm help-table">
                                        <tr>
                                            <th style="width:100px"><i class="bi bi-pencil-square text-primary"></i> Edit</th>
                                            <td>Click the pencil icon on the item row, modify the data, then click Save</td>
                                        </tr>
                                        <tr>
                                            <th><i class="bi bi-copy text-success"></i> Clone</th>
                                            <td>Click the copy icon to duplicate an item. A new item with the same data will be created, and the form will open for editing before saving</td>
                                        </tr>
                                        <tr>
                                            <th><i class="bi bi-trash text-danger"></i> Delete</th>
                                            <td>Click the trash icon and confirm the deletion</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#itemFilter">
                                    <i class="bi bi-funnel me-2"></i> Filter, Search & Sort
                                </button>
                            </h2>
                            <div id="itemFilter" class="accordion-collapse collapse" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <h6>Filters</h6>
                                    <table class="table table-sm help-table mb-3">
                                        <tr><th style="width:120px">Idol</th><td>Filter by specific idol/group</td></tr>
                                        <tr><th>Type</th><td>Filter by specific item type</td></tr>
                                        <tr><th>Date Range</th><td>Filter by order date range</td></tr>
                                        <tr><th>Search</th><td>Search by item title</td></tr>
                                    </table>
                                    <h6>Sorting</h6>
                                    <p>Click on any column header to sort. Click again to toggle between <i class="bi bi-sort-up"></i> ascending and <i class="bi bi-sort-down"></i> descending order.</p>
                                    <h6>Summary Cards</h6>
                                    <p>The top of the table shows 3 summary values (changes based on active filters):</p>
                                    <ul class="mb-0">
                                        <li><strong>Total Items</strong> &mdash; Number of items</li>
                                        <li><strong>Total Quantity</strong> &mdash; Total quantity</li>
                                        <li><strong>Total Spending</strong> &mdash; Total amount</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="card mb-3" id="reports">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-pink"><i class="bi bi-bar-chart-line"></i></div>
                        <h4 class="mb-0">Reports</h4>
                    </div>
                    <p class="text-muted">The <strong>Report</strong> page provides analytics across <strong>13 views (tabs)</strong> grouped into dropdowns, with interactive charts &mdash; data-heavy tabs are lazy-loaded the first time they are opened. (Budgets live on the separate <a href="#budget">Budgets</a> page.)</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-grid-1x2 text-primary"></i> Overview <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Landing tab with KPI cards (total spent, items/qty, avg per month, MoM%), monthly trend chart, Top 5 members, and Type / Company doughnuts</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar3 text-primary"></i> Monthly</h6>
                                <p class="small text-muted mb-2">Bar chart (spending) + line chart (quantity) by month</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click on any month bar</strong> to drill down to daily details with type and idol breakdown
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-graph-up-arrow text-primary"></i> Trends <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Cumulative spending line + month-over-month growth bars (green/red) with a <strong>current-month forecast</strong> projected from spend-to-date</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar-week text-primary"></i> Seasonality <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Spending by day of week (Mon–Sun) and by month of year &mdash; identifies your busiest buying periods</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-person text-primary"></i> By Member</h6>
                                <p class="small text-muted mb-2">Ranking of idol members by spending</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click on a member name</strong> to see type breakdown + monthly chart
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-arrow-left-right text-primary"></i> Compare <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Pick any two members to compare side by side: summary cards, monthly spending line, and by-type grouped bars</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-people text-primary"></i> By Group</h6>
                                <p class="small text-muted mb-2">Aggregated spending for each group/unit (Primary Memberships only)</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click to expand</strong> and see member breakdown
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-diagram-2 text-primary"></i> By Unit <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Spending rolled up to unit-level entities, including sub-unit / project memberships that By Group omits — useful for groups with nested units</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-building text-primary"></i> By Company</h6>
                                <p class="small text-muted mb-2">Aggregated spending for each company</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click to expand</strong> and see groups under the company
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-tags text-primary"></i> By Type</h6>
                                <p class="small text-muted mb-2">Ranking of item types by spending, with item count and quantity</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click on a type name</strong> to see the member, group, and company breakdown for that type
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar-event text-primary"></i> By Event <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span> <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.7</span></h6>
                                <p class="small text-muted mb-0">Spending grouped by named events (see <a href="#events">Event Management</a>), linking to a pre-filtered Items list. Items not yet linked to a named event are grouped separately by Event Date below, plus order-to-event <strong>lead-time stats</strong> (avg / min / max days)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar-range text-primary"></i> Event Summary <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.11</span></h6>
                                <p class="small text-muted mb-0">An event-entity view: each event shows its <strong>start–end date range</strong>, <strong>duration in days</strong>, <strong>Upcoming / Ongoing / Past</strong> status, item count, total spend and <strong>average spend per event-day</strong>, with summary cards (total events, multi-day events, total event-days, upcoming).</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-trophy text-primary"></i> Top Items <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">Top 20 most expensive purchases, top 20 most frequently bought titles, and avg / min / max unit price per type</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-hourglass-split text-primary"></i> Inactive <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-2">Members with no recent purchases, with a selectable threshold (30 / 90 / 180 / 365 days)</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click on a member name</strong> to view their detail
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget -->
            <div class="card mb-3" id="budget">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-green"><i class="bi bi-piggy-bank"></i></div>
                        <h4 class="mb-0">Budgets <span class="badge bg-info ms-1" style="font-size:.7rem">v1.8.0</span></h4>
                    </div>
                    <p class="text-muted">The <strong>Budget</strong> page sets monthly spending limits and tracks spending against them, with colour-coded status bars. It is a <em>visual warning only</em> &mdash; it never blocks adding items.</p>

                    <div class="accordion" id="accBudget">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#budgetManage">
                                    <i class="bi bi-sliders me-2"></i> Set budgets (Manage)
                                </button>
                            </h2>
                            <div id="budgetManage" class="accordion-collapse collapse show" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>Define a <strong>recurring default</strong> that applies to every month, scoped one of five ways:</p>
                                    <table class="table table-sm help-table mt-2 mb-2">
                                        <tr><th style="width:140px">Overall</th><td>Total budget per month</td></tr>
                                        <tr><th>By Type</th><td>Budget per item type, e.g. Photocard</td></tr>
                                        <tr><th>By Company</th><td>Budget per company</td></tr>
                                        <tr><th>By Group</th><td>Budget per group / unit</td></tr>
                                        <tr><th>By Member</th><td>Budget per member</td></tr>
                                    </table>
                                    <p class="mb-1">Each budget sets a <strong>limit (฿)</strong> and colour thresholds:</p>
                                    <ul class="mb-0">
                                        <li><span class="badge bg-success">Green</span> below the yellow %</li>
                                        <li><span class="badge bg-warning text-dark">Yellow</span> from the yellow % up to the red %</li>
                                        <li><span class="badge bg-danger">Red</span> at or above the red % (over budget)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetProgress">
                                    <i class="bi bi-bar-chart-line me-2"></i> Track by month (Progress)
                                </button>
                            </h2>
                            <div id="budgetProgress" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>The <strong>Progress</strong> tab shows a progress bar for every budget in the selected month (spent / limit and remaining).</p>
                                    <ul class="mb-0">
                                        <li>Pick a month with the <strong>Month</strong> selector at the top</li>
                                        <li>Click <i class="bi bi-pencil"></i> to <strong>override the budget for that month only</strong>, leaving the recurring default untouched</li>
                                        <li>Click <i class="bi bi-arrow-counterclockwise"></i> to <strong>reset back to the recurring default</strong></li>
                                        <li><span class="badge bg-light text-secondary border">Default</span> = recurring budget, <span class="badge bg-primary-subtle text-primary border">Custom</span> = month override</li>
                                    </ul>
                                    <div class="tip-box mt-2">
                                        <i class="bi bi-info-circle"></i> Group / Company / Member budgets use the same membership-aware joins as the reports, so the numbers match.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetInsights">
                                    <i class="bi bi-graph-up-arrow me-2"></i> Insights <span class="badge bg-info ms-2" style="font-size:.65rem">v1.9.0</span>
                                </button>
                            </h2>
                            <div id="budgetInsights" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>The <strong>Insights</strong> tab analyses spending vs. budget <strong>over time</strong> to answer "how am I doing historically?" (available both on the Budget page and in the report page's Budget tab).</p>
                                    <ul>
                                        <li><strong>Scope selector</strong> &mdash; Overall, or any scope that has a budget. Comparing one scope at a time avoids double-counting nested entities.</li>
                                        <li><strong>Range</strong> &mdash; last 6 / 12 / 24 months (default 12)</li>
                                        <li><strong>KPI cards</strong> &mdash; total &amp; average spend, average budget, average % used, months over budget, and the peak month</li>
                                        <li><strong>Spent vs. budget chart</strong> &mdash; monthly spend bars (green / yellow / red by status) with the limit drawn as a line</li>
                                        <li><strong>% used trend</strong> &mdash; a line with a 100% reference</li>
                                        <li><strong>Recommendations</strong> &mdash; automatic tips: over budget frequently, over last month, projected overspend at the current pace, trending up/down, consistently under budget (suggests a lower limit), or on track</li>
                                    </ul>
                                    <div class="tip-box mb-0">
                                        <i class="bi bi-lightbulb"></i> If the selected scope has spending but no budget yet, you'll be prompted to set one to start tracking.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetMonthly">
                                    <i class="bi bi-grid-3x3 me-2"></i> Monthly Grid <span class="badge bg-info ms-2" style="font-size:.65rem">v1.9.6</span>
                                </button>
                            </h2>
                            <div id="budgetMonthly" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>The <strong>Monthly</strong> tab shows a Scopes × Months grid &mdash; one row per budget scope, a <strong>Default</strong> column, then one column per month of the year. Use the ‹ Year › controls to switch years.</p>
                                    <ul class="mb-0">
                                        <li>Click the <strong>Default</strong> cell to edit the scope's recurring default</li>
                                        <li>Click any month cell to <strong>set/override</strong> that month (prefilled from the existing override, else the default). Overridden months are highlighted, with a one-click reset back to the default</li>
                                        <li>Footer rows total each month: <strong>Overall budget</strong>, <strong>Allocated</strong> to sub-budgets, and <strong>Unallocated</strong> remaining (red when over-allocated)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Management -->
            <div class="card mb-3" id="events">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-pink"><i class="bi bi-calendar-event"></i></div>
                        <h4 class="mb-0">Event Management <span class="badge bg-info ms-1" style="font-size:.7rem">v1.9.7</span></h4>
                    </div>
                    <p class="text-muted">The <strong>Events</strong> page lets you name real events (concerts, fan meets, fan signs, etc.) and link purchased items to them, instead of just a free-floating event date.</p>

                    <div class="accordion" id="accEvents">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#eventAdd">
                                    <i class="bi bi-plus-circle me-2"></i> Creating an Event
                                </button>
                            </h2>
                            <div id="eventAdd" class="accordion-collapse collapse show" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>Click <strong>Add Event</strong> and fill in:</p>
                                    <table class="table table-sm help-table mt-2 mb-0">
                                        <tr><th style="width:140px">Event Name</th><td>e.g. "TWICE 5th World Tour Bangkok" (the same name can be reused on a different date)</td></tr>
                                        <tr><th>Start Date / End Date</th><td>The event's start and end dates &mdash; multi-day events (e.g. a two-day concert) can set an End Date; for a single-day event, <strong>leave End Date empty</strong> <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.9</span></td></tr>
                                        <tr><th>Description</th><td>Optional extra details</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventLink">
                                    <i class="bi bi-link-45deg me-2"></i> Linking Items to an Event
                                </button>
                            </h2>
                            <div id="eventLink" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>The item add/edit form (Items page) has a searchable <strong>Event</strong> field &mdash; type the event name and pick it from the list. This <strong>auto-fills Event Date</strong> (still editable afterward). Linked items show an event-name badge in the items table.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventBulk">
                                    <i class="bi bi-collection me-2"></i> Linking Existing Items in Bulk
                                </button>
                            </h2>
                            <div id="eventBulk" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>For older items that already have an Event Date but aren't linked to a named event yet, there are two ways to catch up:</p>
                                    <ul class="mb-0">
                                        <li><strong>Auto-assign</strong> &mdash; on the Events page, each row shows a button with the count of unlinked items whose date falls <strong>within the event's range</strong> (multi-day events count every day in the span). One click assigns them all. <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.9</span></li>
                                        <li><strong>Manual bulk assign</strong> &mdash; on the Items page, check the boxes (leftmost column) on the items you want, then click <strong>Assign to Event</strong> to pick the target event.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventUnassign">
                                    <i class="bi bi-x-circle me-2"></i> Un-assigning an Event <span class="badge bg-info ms-2" style="font-size:.65rem">v1.9.10</span>
                                </button>
                            </h2>
                            <div id="eventUnassign" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>Linked the wrong event? You can unlink without deleting anything:</p>
                                    <ul class="mb-0">
                                        <li><strong>Single item</strong> &mdash; open the item for editing and click the <i class="bi bi-x-lg"></i> button next to the Event field to clear it in one click (the item's own Event Date is kept).</li>
                                        <li><strong>In bulk</strong> &mdash; on the Items page, check multiple items, then click <strong>Unassign</strong> in the bulk action bar to clear the event link on all of them at once.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventFilter">
                                    <i class="bi bi-funnel me-2"></i> Filtering Items by Event
                                </button>
                            </h2>
                            <div id="eventFilter" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p class="mb-0">On the Items page, the <strong>Event</strong> filter lets you select multiple events at once (same style as the Idol/Type filters). Clicking the item count on the Events page also jumps to Items pre-filtered by that event.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tip-box mb-0">
                        <i class="bi bi-info-circle"></i> Deleting an event only <strong>unlinks</strong> its items (their event_id is cleared) &mdash; it never deletes the items themselves.
                    </div>
                </div>
            </div>

            <!-- Idol Management -->
            <div class="card mb-3" id="idols">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-blue"><i class="bi bi-people"></i></div>
                        <h4 class="mb-0">Idol Management</h4>
                    </div>
                    <p class="text-muted">Manage the hierarchical structure of idols &mdash; since v1.5 the system tracks group moves and supports duplicate names.</p>

                    <h6>Hierarchy Structure</h6>
                    <div class="border rounded p-3 mb-3" style="background:#f9fafb; font-family: monospace; font-size:13px;">
                        <i class="bi bi-building"></i> <strong>Company</strong><br>
                        <span class="ms-3"><i class="bi bi-people"></i> <strong>Group / Unit</strong></span><br>
                        <span class="ms-5"><i class="bi bi-person"></i> <strong>Member</strong> &mdash; linked to groups via <strong>Memberships</strong> (time-bounded)</span>
                    </div>

                    <div class="accordion" id="accIdols">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#idolAdd">
                                    <i class="bi bi-plus-circle me-2"></i> Add / Edit Entity
                                </button>
                            </h2>
                            <div id="idolAdd" class="accordion-collapse collapse show" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Click <span class="shortcut-key">Add Entity</span></li>
                                        <li>Enter <strong>Name</strong>, select <strong>Category</strong> (company / group / unit / member)</li>
                                        <li>Select <strong>Parent</strong> (the default group for a member)</li>
                                        <li><strong>Display Hint</strong> (optional) &mdash; a short label like <code>ITZY</code> or <code>AKB48 Team A</code> to disambiguate same-name members</li>
                                        <li>Click <strong>Save</strong> &mdash; for member entities a default primary membership is created automatically from the parent</li>
                                    </ol>
                                    <div class="tip-box">
                                        <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> If you enter a name that already exists for another member, the form warns you and auto-fills the Display Hint from the parent group (you can edit it).
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#idolMembership">
                                    <i class="bi bi-arrow-left-right me-2"></i> Membership &mdash; group moves &amp; affiliation history <span class="badge bg-info ms-2">v1.5</span>
                                </button>
                            </h2>
                            <div id="idolMembership" class="accordion-collapse collapse" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <p>Since v1.5 every member stores its <strong>affiliation history</strong> as time-bounded periods (start_date &rarr; end_date). By-Group reports automatically attribute each item to the correct group based on its <code>order_date</code>.</p>

                                    <h6 class="mt-3">View and manage memberships</h6>
                                    <ol>
                                        <li>Open <strong>Idols</strong> and click the <i class="bi bi-pencil"></i> edit icon on the member</li>
                                        <li>Scroll down to the <strong>Memberships</strong> section in the form &mdash; it lists each period (Group, date range, primary/sub-unit)</li>
                                    </ol>

                                    <h6>Move to a new group (one-click)</h6>
                                    <ol>
                                        <li>Click <span class="shortcut-key">Move to new group</span></li>
                                        <li>Pick the new group and enter the <em>Move date</em></li>
                                        <li>Click <strong>Save</strong> &mdash; the current open membership is closed on <em>Move date &minus; 1</em> and a new one is opened on Move date</li>
                                    </ol>

                                    <h6>Add a custom membership</h6>
                                    <ul>
                                        <li>Click <span class="shortcut-key">Add membership</span> to add a parallel membership (e.g. sub-unit, project group)</li>
                                        <li><strong>Primary</strong> (checked) = main group, counted in By-Group / By-Company reports &mdash; only one primary membership may be active at a time</li>
                                        <li><strong>Sub-unit</strong> (unchecked) = visible only in the group drill-down (not double-counted in totals)</li>
                                    </ul>
                                    <div class="tip-box">
                                        <i class="bi bi-info-circle"></i> <strong>Overlap warning:</strong> If two primary periods overlap, the save still succeeds but a warning is surfaced so you can fix it later.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#idolDuplicate">
                                    <i class="bi bi-people-fill me-2"></i> Duplicate names / Display Hint <span class="badge bg-info ms-2">v1.5</span>
                                </button>
                            </h2>
                            <div id="idolDuplicate" class="accordion-collapse collapse" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <p>v1.5 allows multiple members to share a name (e.g. "Yuna" in ITZY and AKB48). The UI distinguishes them via <strong>Display Hint</strong>; under the hood items are linked by <strong>idol_id</strong>.</p>

                                    <h6>Adding an item with an ambiguous idol name</h6>
                                    <ul>
                                        <li>If the typed name matches multiple members, the form surfaces candidate buttons such as <code>Yuna [ITZY]</code> or <code>Yuna [AKB48]</code></li>
                                        <li>Click the correct candidate &mdash; the form retries the save with the right entity automatically</li>
                                    </ul>

                                    <h6>Ambiguous Mappings (queue)</h6>
                                    <p>Items imported earlier with ambiguous names appear in the <strong>Ambiguous Mappings panel</strong> on the Idols page (right side), and a banner is shown on the Items page.</p>
                                    <ol>
                                        <li>Click <span class="shortcut-key">Resolve Conflicts</span></li>
                                        <li>The modal lists every ambiguous name with its candidates</li>
                                        <li>Click the desired candidate to bulk-remap all items with that name to the chosen entity</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#idolUnmapped">
                                    <i class="bi bi-question-circle me-2"></i> Unmapped Names &amp; Tree Stats
                                </button>
                            </h2>
                            <div id="idolUnmapped" class="accordion-collapse collapse" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <h6>Unmapped Names</h6>
                                    <p>The system detects idol names in items that have no entity yet and lists them with a <strong>Quick Add</strong> button. After you create the entity, items whose name uniquely matches are <em>auto-backfilled</em> with the new <code>idol_id</code>.</p>
                                    <h6>Tree Stats</h6>
                                    <ul class="mb-0">
                                        <li>Each entity shows <strong>item count</strong> and <strong>total spending</strong></li>
                                        <li>Members with more than one membership row get an <i class="bi bi-arrow-left-right text-info"></i> icon next to the name</li>
                                        <li>Members with a Display Hint render as <code>Name [hint]</code> in the tree</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type Management -->
            <div class="card mb-3" id="types">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-amber"><i class="bi bi-tags"></i></div>
                        <h4 class="mb-0">Type Management</h4>
                    </div>
                    <p class="text-muted">Manage item type categories (e.g., Photocard, T-Shirt, Lightstick)</p>

                    <ol>
                        <li>Click the <span class="shortcut-key">Add Type</span> button</li>
                        <li>Enter <strong>Name</strong>, <strong>Description</strong>, and <strong>Sort Order</strong></li>
                        <li>Click <strong>Save</strong></li>
                    </ol>

                    <p class="small text-muted mb-2">Each type shows statistics: row count, quantity, and total spending. The system also has an <strong>Unmapped Names</strong> feature to detect type names that haven't been added yet.</p>
                    <h6 class="mt-2">Members by Type</h6>
                    <p class="small text-muted mb-0">The bottom of the Types page includes a <strong>Members by Type</strong> accordion showing which members, groups, and companies purchased each type, along with item count, quantity, and total spending statistics.</p>
                </div>
            </div>

            <!-- User Management -->
            <div class="card mb-3" id="users">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-green"><i class="bi bi-person-gear"></i></div>
                        <h4 class="mb-0">User Management</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> Manage user accounts, except changing own password (available to all roles)</p>

                    <h6>Create New User</h6>
                    <ol>
                        <li>Click the <span class="shortcut-key">Add User</span> button</li>
                        <li>Enter Username, Password, and Display Name</li>
                        <li>Select Role: <code>admin</code> (full access) or <code>user</code> (general use)</li>
                        <li>Click <strong>Save</strong></li>
                    </ol>

                    <h6>Change Password</h6>
                    <p class="small text-muted mb-0">All users can change their own password by clicking <strong>Change Password</strong> on the Users page.</p>
                </div>
            </div>

            <!-- Backup & Restore -->
            <div class="card mb-3" id="backup">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-cyan"><i class="bi bi-database"></i></div>
                        <h4 class="mb-0">Backup & Restore</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> Create database snapshots for backup or restoration.</p>

                    <table class="table table-sm help-table">
                        <tr>
                            <th style="width:160px"><i class="bi bi-plus-circle text-success"></i> Create Backup</th>
                            <td>Create a new backup with an optional label name</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-arrow-counterclockwise text-primary"></i> Restore</th>
                            <td>Restore data from a selected backup</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-download text-info"></i> Download</th>
                            <td>Download a backup file to your computer</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-upload text-warning"></i> Upload</th>
                            <td>Upload a previously downloaded backup file back to the system</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-trash text-danger"></i> Delete</th>
                            <td>Remove unwanted backups</td>
                        </tr>
                    </table>
                    <div class="tip-box">
                        <i class="bi bi-shield-check"></i> <strong>Auto-backup:</strong> The system automatically creates a backup before every Restore to prevent data loss.
                    </div>
                </div>
            </div>

            <!-- Excel Import -->
            <div class="card mb-3" id="import">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-green"><i class="bi bi-file-earmark-excel"></i></div>
                        <h4 class="mb-0">Excel Import</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> Import data from <code>.xlsx</code> files</p>

                    <?php if (!ALLOW_IMPORT): ?>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Disabled:</strong> This feature is currently disabled. Enable it by setting <code>ALLOW_IMPORT = true</code> in <code>config.php</code>
                    </div>
                    <?php endif; ?>

                    <h6>How to Use</h6>
                    <ol>
                        <li>Prepare an <code>.xlsx</code> file with columns: Order Date, Event Date, Title, Idol, Type, Price per Qty, Qty</li>
                        <li>Click the <span class="shortcut-key">Import Excel</span> button on the Items page</li>
                        <li>Select the file and confirm the import</li>
                    </ol>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Warning:</strong> Importing will <strong>delete all existing data</strong> before importing new data. Always create a backup first!
                    </div>
                </div>
            </div>

            <!-- Role Permissions -->
            <div class="card mb-3" id="roles">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-red"><i class="bi bi-shield-lock"></i></div>
                        <h4 class="mb-0">Permissions</h4>
                    </div>
                    <p class="text-muted">The system has 2 permission levels</p>
                    <table class="table table-sm help-table table-bordered">
                        <thead>
                            <tr><th>Feature</th><th class="text-center" style="width:80px">Admin</th><th class="text-center" style="width:80px">User</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>View / Add / Edit / Delete items</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>View reports</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>Manage idols</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>Manage types</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>Change own password</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>Import Excel</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>Backup / Restore</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>Manage users</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>Re-seed idol data</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAQ -->
            <div class="card mb-3" id="faq">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-amber"><i class="bi bi-chat-dots"></i></div>
                        <h4 class="mb-0">FAQ</h4>
                    </div>

                    <div class="accordion" id="accFaq">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    I forgot the admin password. What should I do?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-1">If using Docker, delete the database and restart:</p>
                                    <code>docker compose down -v && docker compose up -d</code>
                                    <p class="mt-2 mb-1">If using Manual setup, delete the <code>database.sqlite</code> file and reload the page.</p>
                                    <div class="warning-box">
                                        <i class="bi bi-exclamation-triangle"></i> This will delete all data. Make sure to back up first.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What are Unmapped Names?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-0">When you record items with idol or type names that haven't been created in the Idols/Types pages, the system shows them as "Unmapped Names" with a Quick Add button. Mapping these names enables accurate reporting in the By Group / By Company views.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqMove">
                                    <span class="badge bg-info me-2">v1.5</span> A member moved groups &mdash; how do I keep old/new purchases attributed to the right group?
                                </button>
                            </h2>
                            <div id="faqMove" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <ol class="mb-2">
                                        <li>Go to <strong>Idols</strong> and click <i class="bi bi-pencil"></i> on the member who moved</li>
                                        <li>Scroll to the <strong>Memberships</strong> section and click <span class="shortcut-key">Move to new group</span></li>
                                        <li>Pick the new group, enter the Move date, and click Save</li>
                                    </ol>
                                    <p class="mb-0">The current membership is closed on <em>Move date &minus; 1</em> and a new one opens on Move date. Reports compare each item's <code>order_date</code> against the membership timeline to attribute it to the correct group &mdash; <strong>no need to edit old items manually</strong>.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqDuplicate">
                                    <span class="badge bg-info me-2">v1.5</span> Two different members share the same name &mdash; how do I tell them apart?
                                </button>
                            </h2>
                            <div id="faqDuplicate" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p>v1.5 supports duplicate names. Use the <strong>Display Hint</strong> field to distinguish them in the UI:</p>
                                    <ol>
                                        <li>Add both members normally with their real name (e.g. two "Yuna" entities)</li>
                                        <li>Fill the <strong>Display Hint</strong> for each, e.g. <code>ITZY</code> and <code>AKB48</code></li>
                                        <li>The tree view will render them as <code>Yuna [ITZY]</code> and <code>Yuna [AKB48]</code></li>
                                        <li>When adding an item with an ambiguous name the form asks you to pick the correct member</li>
                                    </ol>
                                    <p class="mb-0">Any pre-existing items with ambiguous names land in the <strong>Ambiguous Mappings panel</strong> on the Idols page (with a banner on the Items page). Click <span class="shortcut-key">Resolve Conflicts</span> to bulk-remap them.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Where is the data stored?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <table class="table table-sm help-table mb-0">
                                        <tr><th style="width:100px">Docker</th><td>Data is stored in a Docker volume named <code>app-data</code> at <code>data/database.sqlite</code></td></tr>
                                        <tr><th>Manual</th><td>Data is stored in <code>database.sqlite</code> at the project root</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Can I disable the login system?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-1">Yes, suitable for personal use. Edit <code>config.php</code>:</p>
                                    <code>define('AUTH_ENABLED', false);</code>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    By Group / By Company reports show no data?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-0">You need to set up the hierarchy structure on the <strong>Idols</strong> page first. Add Company, Group/Unit, and Member entities with correct Parent assignments. Member names must match the Idol names used in your items.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    How do I migrate data to another machine?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>Create a Backup on the Backup page, then <strong>Download</strong> the file</li>
                                        <li>Install Numa Log on the new machine</li>
                                        <li>Go to the Backup page and <strong>Upload</strong> the backup file</li>
                                        <li>Click <strong>Restore</strong> to restore the data</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Version Info -->
            <div class="text-center text-muted small py-3">
                Numa Log v<?= APP_VERSION ?> &mdash; Built with PHP, SQLite, Bootstrap 5, Chart.js
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scroll for TOC links
document.querySelectorAll('.toc-link').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelectorAll('.toc-link').forEach(l => l.style.background = '');
            link.style.background = '#f3f0ff';
        }
    });
});

// Highlight TOC on scroll
const sections = document.querySelectorAll('[id]');
const tocLinks = document.querySelectorAll('.toc-link');
window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => {
        if (window.scrollY >= s.offsetTop - 100) current = s.id;
    });
    tocLinks.forEach(link => {
        link.style.background = link.getAttribute('href') === '#' + current ? '#f3f0ff' : '';
        link.style.fontWeight = link.getAttribute('href') === '#' + current ? '600' : '';
    });
});
</script>
</body>
</html>
