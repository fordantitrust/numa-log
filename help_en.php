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
        .lang-switcher { font-size: 13px; }
        .lang-switcher a { color: rgba(255,255,255,.7); text-decoration: none; padding: 2px 8px; border-radius: 4px; }
        .lang-switcher a:hover { color: white; background: rgba(255,255,255,.15); }
        .lang-switcher a.active { color: white; background: rgba(255,255,255,.25); font-weight: 600; }
        @media (max-width: 991px) {
            .nav-section { position: static; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-stars"></i> Numa Log <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Items
            </a>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-bar-chart-line"></i> Report
            </a>
            <?php $u = currentUser(); ?>
            <?php if (AUTH_ENABLED && $u): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($u['display_name']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($u['role'] === 'admin'): ?>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a class="dropdown-item" href="backup.php"><i class="bi bi-database"></i> Backup</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<div class="help-hero">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1><i class="bi bi-question-circle"></i> Help & Guide</h1>
                <p>User guide for Numa Log &mdash; Idol merchandise purchase tracking and analytics</p>
            </div>
            <div class="lang-switcher">
                <a href="help.php">TH</a>
                <a href="help_en.php" class="active">EN</a>
            </div>
        </div>
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
                            <span class="text-muted">Use Username: <code>admin</code> / Password: <code>admin</code>, then change the password immediately</span>
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
                            <span class="text-muted">Click <strong>Add Item</strong> on the main page to start recording purchases</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="step-number">5</span>
                        <div>
                            <strong>View reports</strong><br>
                            <span class="text-muted">Go to the <strong>Report</strong> page to see spending summaries from various perspectives</span>
                        </div>
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
                                        <li>Click the <span class="shortcut-key">Add Item</span> button in the top bar</li>
                                        <li>Fill in the form:
                                            <table class="table table-sm help-table mt-2 mb-2">
                                                <tr><th style="width:140px">Order Date</th><td>Purchase date</td></tr>
                                                <tr><th>Event Date</th><td>Event date (if applicable)</td></tr>
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
                    <p class="text-muted">The <strong>Report</strong> page provides analytics in 5 views with interactive charts.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar3 text-primary"></i> Monthly</h6>
                                <p class="small text-muted mb-2">Bar chart (spending) + line chart (quantity) by month</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click on any month bar</strong> to drill down to daily details
                                </div>
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
                                <h6><i class="bi bi-people text-primary"></i> By Group</h6>
                                <p class="small text-muted mb-2">Aggregated spending for each group/unit</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>Click to expand</strong> and see member breakdown
                                </div>
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
                                <p class="small text-muted mb-0">Ranking of item types by spending, with item count and quantity</p>
                            </div>
                        </div>
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
                    <p class="text-muted">Manage the hierarchical structure of idols.</p>

                    <h6>Hierarchy Structure</h6>
                    <div class="border rounded p-3 mb-3" style="background:#f9fafb; font-family: monospace; font-size:13px;">
                        <i class="bi bi-building"></i> <strong>Company</strong><br>
                        <span class="ms-3"><i class="bi bi-people"></i> <strong>Group / Unit</strong></span><br>
                        <span class="ms-5"><i class="bi bi-person"></i> <strong>Member</strong></span>
                    </div>

                    <h6>How to Add</h6>
                    <ol>
                        <li>Click the <span class="shortcut-key">Add Entity</span> button</li>
                        <li>Enter <strong>Name</strong>, select <strong>Category</strong> (company / group / unit / member)</li>
                        <li>Select <strong>Parent</strong> (e.g., which group a member belongs to)</li>
                        <li>Click <strong>Save</strong></li>
                    </ol>

                    <h6>Unmapped Names</h6>
                    <p class="small text-muted mb-0">The system detects idol names in items that haven't been categorized yet. They appear as a list with a <strong>Quick Add</strong> button for fast entry. Each entity shows statistics for item count and total spending.</p>
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

                    <p class="small text-muted mb-0">Each type shows statistics: row count, quantity, and total spending. The system also has an <strong>Unmapped Names</strong> feature to detect type names that haven't been added yet.</p>
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
