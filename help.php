<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="th">
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

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-stars"></i> Numa Log <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-list-ul"></i> Items
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
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
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
        <h1><i class="bi bi-question-circle"></i> Help & Guide</h1>
        <p>คู่มือการใช้งาน Numa Log &mdash; ระบบบันทึกและวิเคราะห์ข้อมูลการซื้อสินค้าไอดอล</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row">

        <!-- Sidebar: Table of Contents -->
        <div class="col-lg-3 mb-3">
            <div class="card nav-section">
                <div class="card-body p-2">
                    <div class="fw-bold text-muted small px-3 py-2">MENU</div>
                    <a href="#getting-started" class="toc-link"><i class="bi bi-rocket-takeoff"></i> เริ่มต้นใช้งาน</a>
                    <a href="#items" class="toc-link"><i class="bi bi-list-ul"></i> จัดการรายการ</a>
                    <a href="#reports" class="toc-link"><i class="bi bi-bar-chart-line"></i> รายงาน</a>
                    <a href="#idols" class="toc-link"><i class="bi bi-people"></i> จัดการไอดอล</a>
                    <a href="#types" class="toc-link"><i class="bi bi-tags"></i> จัดการประเภท</a>
                    <a href="#users" class="toc-link"><i class="bi bi-person-gear"></i> จัดการผู้ใช้</a>
                    <a href="#backup" class="toc-link"><i class="bi bi-database"></i> สำรอง/กู้คืน</a>
                    <a href="#import" class="toc-link"><i class="bi bi-file-earmark-excel"></i> นำเข้า Excel</a>
                    <a href="#roles" class="toc-link"><i class="bi bi-shield-lock"></i> สิทธิ์การใช้งาน</a>
                    <a href="#faq" class="toc-link"><i class="bi bi-chat-dots"></i> FAQ</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">

            <!-- Getting Started -->
            <div class="card mb-3" id="getting-started">
                <div class="card-body">
                    <h4 class="mb-3"><i class="bi bi-rocket-takeoff text-primary"></i> เริ่มต้นใช้งาน</h4>
                    <p>Numa Log ช่วยให้คุณบันทึกรายการซื้อสินค้าไอดอล วิเคราะห์ยอดใช้จ่าย และจัดการข้อมูลไอดอลอย่างเป็นระบบ</p>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">1</span>
                        <div>
                            <strong>เข้าสู่ระบบ</strong><br>
                            <span class="text-muted">ใช้ Username: <code>admin</code> / Password: <code>admin</code> แล้วเปลี่ยนรหัสผ่านทันที</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">2</span>
                        <div>
                            <strong>ตั้งค่าข้อมูลไอดอล</strong><br>
                            <span class="text-muted">ไปที่หน้า <strong>Idols</strong> เพื่อเพิ่มค่าย กลุ่ม และสมาชิก</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">3</span>
                        <div>
                            <strong>ตั้งค่าประเภทสินค้า</strong><br>
                            <span class="text-muted">ไปที่หน้า <strong>Types</strong> เพื่อเพิ่มประเภทสินค้า เช่น Photocard, T-Shirt</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="step-number">4</span>
                        <div>
                            <strong>เริ่มบันทึกรายการ</strong><br>
                            <span class="text-muted">กดปุ่ม <strong>Add Item</strong> ที่หน้าหลักเพื่อเริ่มบันทึกข้อมูลการซื้อ</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="step-number">5</span>
                        <div>
                            <strong>ดูรายงาน</strong><br>
                            <span class="text-muted">ไปที่หน้า <strong>Report</strong> เพื่อดูสรุปยอดใช้จ่ายในมุมมองต่างๆ</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Management -->
            <div class="card mb-3" id="items">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-purple"><i class="bi bi-list-ul"></i></div>
                        <h4 class="mb-0">จัดการรายการสินค้า</h4>
                    </div>
                    <p class="text-muted">หน้าหลัก (<strong>Items</strong>) สำหรับบันทึกข้อมูลการซื้อสินค้าทั้งหมด</p>

                    <div class="accordion" id="accItems">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#itemAdd">
                                    <i class="bi bi-plus-circle me-2"></i> เพิ่มรายการใหม่
                                </button>
                            </h2>
                            <div id="itemAdd" class="accordion-collapse collapse show" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <ol>
                                        <li>กดปุ่ม <span class="shortcut-key">Add Item</span> ที่แถบด้านบน</li>
                                        <li>กรอกข้อมูลในฟอร์ม:
                                            <table class="table table-sm help-table mt-2 mb-2">
                                                <tr><th style="width:140px">Order Date</th><td>วันที่สั่งซื้อ</td></tr>
                                                <tr><th>Event Date</th><td>วันที่งาน/อีเวนต์ (ถ้ามี)</td></tr>
                                                <tr><th>Title</th><td>ชื่อสินค้า</td></tr>
                                                <tr><th>Idol</th><td>ชื่อไอดอล/กลุ่ม &mdash; พิมพ์เพื่อค้นหาจาก dropdown</td></tr>
                                                <tr><th>Type</th><td>ประเภทสินค้า &mdash; พิมพ์เพื่อค้นหาจาก dropdown</td></tr>
                                                <tr><th>Price per Qty</th><td>ราคาต่อชิ้น</td></tr>
                                                <tr><th>Qty</th><td>จำนวน</td></tr>
                                            </table>
                                        </li>
                                        <li>กดปุ่ม <strong>Save</strong></li>
                                    </ol>
                                    <div class="tip-box">
                                        <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> ช่อง Idol และ Type เป็น searchable dropdown สามารถพิมพ์ชื่อเพื่อค้นหาได้ หรือพิมพ์ชื่อใหม่ได้เลยโดยไม่ต้องเพิ่มในหน้า Idols/Types ก่อน
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#itemEdit">
                                    <i class="bi bi-pencil me-2"></i> แก้ไข / Clone / ลบ
                                </button>
                            </h2>
                            <div id="itemEdit" class="accordion-collapse collapse" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <table class="table table-sm help-table">
                                        <tr>
                                            <th style="width:100px"><i class="bi bi-pencil-square text-primary"></i> แก้ไข</th>
                                            <td>กดไอคอนดินสอที่แถวรายการ แก้ข้อมูลแล้วกด Save</td>
                                        </tr>
                                        <tr>
                                            <th><i class="bi bi-copy text-success"></i> Clone</th>
                                            <td>กดไอคอน copy เพื่อทำซ้ำรายการ ระบบจะสร้างรายการใหม่ที่มีข้อมูลเหมือนเดิม พร้อมเปิดฟอร์มให้แก้ไขก่อน Save</td>
                                        </tr>
                                        <tr>
                                            <th><i class="bi bi-trash text-danger"></i> ลบ</th>
                                            <td>กดไอคอนถังขยะ แล้วยืนยันการลบ</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#itemFilter">
                                    <i class="bi bi-funnel me-2"></i> กรอง ค้นหา และเรียงลำดับ
                                </button>
                            </h2>
                            <div id="itemFilter" class="accordion-collapse collapse" data-bs-parent="#accItems">
                                <div class="accordion-body">
                                    <h6>ตัวกรอง</h6>
                                    <table class="table table-sm help-table mb-3">
                                        <tr><th style="width:120px">Idol</th><td>กรองเฉพาะไอดอล/กลุ่มที่เลือก</td></tr>
                                        <tr><th>Type</th><td>กรองเฉพาะประเภทสินค้าที่เลือก</td></tr>
                                        <tr><th>Date Range</th><td>กรองตามช่วงวันที่สั่งซื้อ</td></tr>
                                        <tr><th>Search</th><td>ค้นหาจากชื่อสินค้า (Title)</td></tr>
                                    </table>
                                    <h6>การเรียงลำดับ</h6>
                                    <p>คลิกที่หัวคอลัมน์ในตารางเพื่อเรียงลำดับ คลิกซ้ำเพื่อสลับระหว่าง <i class="bi bi-sort-up"></i> น้อย &rarr; มาก และ <i class="bi bi-sort-down"></i> มาก &rarr; น้อย</p>
                                    <h6>Summary Cards</h6>
                                    <p>ด้านบนตารางแสดงสรุป 3 ค่า (เปลี่ยนตามตัวกรองที่ใช้):</p>
                                    <ul class="mb-0">
                                        <li><strong>Total Items</strong> &mdash; จำนวนรายการ</li>
                                        <li><strong>Total Quantity</strong> &mdash; จำนวนชิ้นรวม</li>
                                        <li><strong>Total Spending</strong> &mdash; ยอดเงินรวม</li>
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
                        <h4 class="mb-0">รายงาน</h4>
                    </div>
                    <p class="text-muted">หน้า <strong>Report</strong> แสดงการวิเคราะห์ข้อมูลใน 5 มุมมอง พร้อมกราฟแบบ interactive</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar3 text-primary"></i> Monthly</h6>
                                <p class="small text-muted mb-2">กราฟแท่ง (ยอดเงิน) + กราฟเส้น (จำนวน) รายเดือน</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่แท่งกราฟเดือนใดก็ได้</strong> เพื่อดูรายละเอียดรายวัน
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-person text-primary"></i> By Member</h6>
                                <p class="small text-muted mb-2">อันดับสมาชิกไอดอลตามยอดใช้จ่าย</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่ชื่อสมาชิก</strong> เพื่อดูสัดส่วนตามประเภทสินค้า + กราฟรายเดือน
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-people text-primary"></i> By Group</h6>
                                <p class="small text-muted mb-2">ยอดใช้จ่ายรวมของแต่ละกลุ่ม/ยูนิต</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกเพื่อขยาย</strong> ดูรายละเอียดสมาชิกในกลุ่ม
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-building text-primary"></i> By Company</h6>
                                <p class="small text-muted mb-2">ยอดใช้จ่ายรวมของแต่ละค่าย</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกเพื่อขยาย</strong> ดูกลุ่ม/ยูนิตภายใต้ค่าย
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-tags text-primary"></i> By Type</h6>
                                <p class="small text-muted mb-0">อันดับประเภทสินค้าตามยอดใช้จ่าย พร้อมจำนวนรายการและจำนวนชิ้น</p>
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
                        <h4 class="mb-0">จัดการข้อมูลไอดอล</h4>
                    </div>
                    <p class="text-muted">จัดการโครงสร้างลำดับชั้นของไอดอล</p>

                    <h6>โครงสร้างลำดับชั้น</h6>
                    <div class="border rounded p-3 mb-3" style="background:#f9fafb; font-family: monospace; font-size:13px;">
                        <i class="bi bi-building"></i> <strong>Company</strong> (ค่าย)<br>
                        <span class="ms-3"><i class="bi bi-people"></i> <strong>Group / Unit</strong> (กลุ่ม / ยูนิต)</span><br>
                        <span class="ms-5"><i class="bi bi-person"></i> <strong>Member</strong> (สมาชิก)</span>
                    </div>

                    <h6>วิธีเพิ่มข้อมูล</h6>
                    <ol>
                        <li>กดปุ่ม <span class="shortcut-key">Add Entity</span></li>
                        <li>กรอก <strong>Name</strong>, เลือก <strong>Category</strong> (company / group / unit / member)</li>
                        <li>เลือก <strong>Parent</strong> (สังกัด) เช่น สมาชิกอยู่ใต้กลุ่มไหน</li>
                        <li>กดปุ่ม <strong>Save</strong></li>
                    </ol>

                    <h6>Unmapped Names</h6>
                    <p class="small text-muted mb-0">ระบบจะตรวจจับชื่อ Idol ในรายการสินค้าที่ยังไม่ได้จัดกลุ่ม แสดงเป็นรายการพร้อมปุ่ม <strong>Quick Add</strong> เพื่อเพิ่มเข้าระบบอย่างรวดเร็ว แต่ละ entity จะแสดงสถิติจำนวนรายการและยอดใช้จ่ายรวม</p>
                </div>
            </div>

            <!-- Type Management -->
            <div class="card mb-3" id="types">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-amber"><i class="bi bi-tags"></i></div>
                        <h4 class="mb-0">จัดการประเภทสินค้า</h4>
                    </div>
                    <p class="text-muted">จัดการหมวดหมู่ประเภทสินค้า เช่น Photocard, T-Shirt, Lightstick</p>

                    <ol>
                        <li>กดปุ่ม <span class="shortcut-key">Add Type</span></li>
                        <li>กรอก <strong>Name</strong> (ชื่อประเภท), <strong>Description</strong> (คำอธิบาย), <strong>Sort Order</strong> (ลำดับ)</li>
                        <li>กดปุ่ม <strong>Save</strong></li>
                    </ol>

                    <p class="small text-muted mb-0">แต่ละประเภทจะแสดงสถิติ: จำนวนแถว, จำนวนชิ้น, ยอดใช้จ่ายรวม นอกจากนี้ยังมีระบบ <strong>Unmapped Names</strong> เพื่อตรวจจับชื่อ Type ที่ยังไม่ได้เพิ่มในระบบ</p>
                </div>
            </div>

            <!-- User Management -->
            <div class="card mb-3" id="users">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-green"><i class="bi bi-person-gear"></i></div>
                        <h4 class="mb-0">จัดการผู้ใช้งาน</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> จัดการบัญชีผู้ใช้ ยกเว้นการเปลี่ยนรหัสผ่านตัวเอง (ทุก role ทำได้)</p>

                    <h6>สร้างผู้ใช้ใหม่</h6>
                    <ol>
                        <li>กดปุ่ม <span class="shortcut-key">Add User</span></li>
                        <li>กรอก Username, Password, Display Name</li>
                        <li>เลือก Role: <code>admin</code> (เข้าถึงทุกฟีเจอร์) หรือ <code>user</code> (ใช้งานทั่วไป)</li>
                        <li>กดปุ่ม <strong>Save</strong></li>
                    </ol>

                    <h6>เปลี่ยนรหัสผ่าน</h6>
                    <p class="small text-muted mb-0">ผู้ใช้ทุกคนสามารถเปลี่ยนรหัสผ่านของตัวเองได้ โดยกดปุ่ม <strong>Change Password</strong> ในหน้า Users</p>
                </div>
            </div>

            <!-- Backup & Restore -->
            <div class="card mb-3" id="backup">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-cyan"><i class="bi bi-database"></i></div>
                        <h4 class="mb-0">สำรองและกู้คืนข้อมูล</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> สร้าง snapshot ของฐานข้อมูล เพื่อสำรองหรือกู้คืนข้อมูล</p>

                    <table class="table table-sm help-table">
                        <tr>
                            <th style="width:160px"><i class="bi bi-plus-circle text-success"></i> Create Backup</th>
                            <td>สร้าง backup ใหม่ พร้อมตั้งชื่อ label (ไม่บังคับ)</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-arrow-counterclockwise text-primary"></i> Restore</th>
                            <td>กู้คืนข้อมูลจาก backup ที่เลือก</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-download text-info"></i> Download</th>
                            <td>ดาวน์โหลดไฟล์ backup เก็บไว้ในเครื่อง</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-upload text-warning"></i> Upload</th>
                            <td>อัปโหลดไฟล์ backup ที่เคยดาวน์โหลดกลับเข้าระบบ</td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-trash text-danger"></i> Delete</th>
                            <td>ลบ backup ที่ไม่ต้องการ</td>
                        </tr>
                    </table>
                    <div class="tip-box">
                        <i class="bi bi-shield-check"></i> <strong>Auto-backup:</strong> ระบบจะสร้าง backup อัตโนมัติก่อนทำการ Restore ทุกครั้ง เพื่อป้องกันข้อมูลสูญหาย
                    </div>
                </div>
            </div>

            <!-- Excel Import -->
            <div class="card mb-3" id="import">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-green"><i class="bi bi-file-earmark-excel"></i></div>
                        <h4 class="mb-0">นำเข้าข้อมูลจาก Excel</h4>
                    </div>
                    <p class="text-muted"><span class="badge bg-danger">Admin Only</span> นำเข้าข้อมูลจากไฟล์ <code>.xlsx</code></p>

                    <?php if (!ALLOW_IMPORT): ?>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle"></i> <strong>ปิดอยู่:</strong> ฟีเจอร์นี้ถูกปิดอยู่ เปิดใช้งานโดยตั้งค่า <code>ALLOW_IMPORT = true</code> ใน <code>config.php</code>
                    </div>
                    <?php endif; ?>

                    <h6>วิธีใช้งาน</h6>
                    <ol>
                        <li>เตรียมไฟล์ <code>.xlsx</code> ที่มีคอลัมน์: Order Date, Event Date, Title, Idol, Type, Price per Qty, Qty</li>
                        <li>กดปุ่ม <span class="shortcut-key">Import Excel</span> ที่หน้า Items</li>
                        <li>เลือกไฟล์ แล้วยืนยันการนำเข้า</li>
                    </ol>
                    <div class="warning-box">
                        <i class="bi bi-exclamation-triangle"></i> <strong>ระวัง:</strong> การ Import จะ<strong>ลบข้อมูลเดิมทั้งหมด</strong>ก่อนนำเข้าข้อมูลใหม่ ควรสำรองข้อมูลก่อนเสมอ!
                    </div>
                </div>
            </div>

            <!-- Role Permissions -->
            <div class="card mb-3" id="roles">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-red"><i class="bi bi-shield-lock"></i></div>
                        <h4 class="mb-0">สิทธิ์การใช้งาน</h4>
                    </div>
                    <p class="text-muted">ระบบแบ่งสิทธิ์เป็น 2 ระดับ</p>
                    <table class="table table-sm help-table table-bordered">
                        <thead>
                            <tr><th>ฟีเจอร์</th><th class="text-center" style="width:80px">Admin</th><th class="text-center" style="width:80px">User</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>ดู / เพิ่ม / แก้ไข / ลบ รายการสินค้า</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>ดูรายงาน</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>จัดการไอดอล (Idols)</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>จัดการประเภทสินค้า (Types)</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>เปลี่ยนรหัสผ่านตัวเอง</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td></tr>
                            <tr><td>นำเข้า Excel</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>สำรอง / กู้คืนข้อมูล</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>จัดการผู้ใช้งาน</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                            <tr><td>Re-seed ข้อมูลไอดอล</td><td class="text-center text-success"><i class="bi bi-check-lg"></i></td><td class="text-center text-danger"><i class="bi bi-x-lg"></i></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAQ -->
            <div class="card mb-3" id="faq">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon feature-icon-amber"><i class="bi bi-chat-dots"></i></div>
                        <h4 class="mb-0">FAQ - คำถามที่พบบ่อย</h4>
                    </div>

                    <div class="accordion" id="accFaq">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    ลืมรหัสผ่าน Admin ทำอย่างไร?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-1">ถ้าใช้ Docker ให้ลบฐานข้อมูลแล้วเริ่มใหม่:</p>
                                    <code>docker compose down -v && docker compose up -d</code>
                                    <p class="mt-2 mb-1">ถ้าใช้ Manual ให้ลบไฟล์ <code>database.sqlite</code> แล้วเปิดเว็บใหม่</p>
                                    <div class="warning-box">
                                        <i class="bi bi-exclamation-triangle"></i> วิธีนี้จะลบข้อมูลทั้งหมด ควรสำรองข้อมูลก่อน
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Unmapped Names คืออะไร?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-0">เมื่อคุณบันทึกรายการสินค้าโดยพิมพ์ชื่อ Idol หรือ Type ที่ยังไม่ได้สร้างในหน้า Idols/Types ระบบจะแสดงชื่อเหล่านั้นเป็น "Unmapped Names" พร้อมปุ่ม Quick Add เพื่อให้คุณเพิ่มเข้าระบบได้อย่างรวดเร็ว การจัดกลุ่ม (map) จะช่วยให้รายงาน By Group / By Company ทำงานได้ถูกต้อง</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    ข้อมูลเก็บอยู่ที่ไหน?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <table class="table table-sm help-table mb-0">
                                        <tr><th style="width:100px">Docker</th><td>ข้อมูลเก็บใน Docker volume ชื่อ <code>app-data</code> ที่ path <code>data/database.sqlite</code></td></tr>
                                        <tr><th>Manual</th><td>ข้อมูลเก็บในไฟล์ <code>database.sqlite</code> ที่ root ของโปรเจกต์</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    ปิดระบบ Login ได้ไหม?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-1">ได้ เหมาะสำหรับใช้งานส่วนตัว แก้ไขไฟล์ <code>config.php</code>:</p>
                                    <code>define('AUTH_ENABLED', false);</code>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    รายงาน By Group / By Company ไม่แสดงข้อมูล?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p class="mb-0">ต้องตั้งค่าโครงสร้างลำดับชั้นในหน้า <strong>Idols</strong> ก่อน โดยเพิ่ม Company, Group/Unit, Member พร้อมระบุ Parent ให้ถูกต้อง และชื่อสมาชิกต้องตรงกับชื่อ Idol ที่ใช้ในรายการสินค้า</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    ย้ายข้อมูลไปเครื่องอื่นทำอย่างไร?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <ol class="mb-0">
                                        <li>สร้าง Backup ในหน้า Backup แล้ว <strong>Download</strong> ไฟล์เก็บไว้</li>
                                        <li>ติดตั้ง Numa Log ในเครื่องใหม่</li>
                                        <li>เข้าหน้า Backup แล้ว <strong>Upload</strong> ไฟล์ backup</li>
                                        <li>กด <strong>Restore</strong> เพื่อกู้คืนข้อมูล</li>
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
            // Update active state
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
