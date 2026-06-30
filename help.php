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

<?php $navActive = 'help'; $navIcon = 'bi-stars'; $navTitle = 'Numa Log'; require __DIR__ . '/navbar.php'; ?>

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
                    <a href="#budget" class="toc-link"><i class="bi bi-piggy-bank"></i> งบประมาณ</a>
                    <a href="#events" class="toc-link"><i class="bi bi-calendar-event"></i> จัดการอีเวนต์</a>
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
                            <span class="text-muted">ใช้ Username: <code>admin</code> / Password: <code>admin</code> แล้วเปลี่ยนรหัสผ่านทันที &mdash; หลังล็อกอินจะถูกนำไปที่ <strong>Dashboard</strong> โดยอัตโนมัติ <span class="badge bg-info" style="font-size:.65rem">v1.6.0</span></span>
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
                            <span class="text-muted">ไปที่หน้า <strong>Items</strong> แล้วกดปุ่ม <strong>Add Item</strong> เพื่อเริ่มบันทึกข้อมูลการซื้อ</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <span class="step-number">5</span>
                        <div>
                            <strong>ดูรายงาน</strong><br>
                            <span class="text-muted">ไปที่หน้า <strong>Report</strong> เพื่อดูสรุปยอดใช้จ่ายใน 13 มุมมอง <span class="badge bg-info" style="font-size:.65rem">v1.6.1</span></span>
                        </div>
                    </div>
                    <div class="tip-box mt-3">
                        <i class="bi bi-translate"></i> <strong>v1.7.0 Language Switcher:</strong> สลับภาษา <strong>TH / EN</strong> ได้จากปุ่มที่มุมขวาบนของทุกหน้า ระบบจะจำการตั้งค่าไว้ตลอดการใช้งาน
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
                                        <li>กดปุ่ม <span class="shortcut-key">Add Item</span> ที่หัวการ์ดตัวกรอง (Filters) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.8</span></li>
                                        <li>กรอกข้อมูลในฟอร์ม:
                                            <table class="table table-sm help-table mt-2 mb-2">
                                                <tr><th style="width:140px">Order Date</th><td>วันที่สั่งซื้อ</td></tr>
                                                <tr><th>Event Date</th><td>วันที่งาน/อีเวนต์ (ถ้ามี)</td></tr>
                                                <tr><th>Event</th><td>เลือกอีเวนต์ที่ตั้งชื่อไว้แบบค้นหาได้ &mdash; เลือกแล้วเติม Event Date ให้อัตโนมัติ <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.7</span> (ดูหัวข้อ <a href="#events">จัดการอีเวนต์</a>)</td></tr>
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
                                    <div class="tip-box mt-2" style="background:#fff7ed;border-left:3px solid #f59e0b">
                                        <i class="bi bi-info-circle"></i> <strong>v1.5:</strong> ถ้าชื่อ Idol ที่พิมพ์ตรงกับสมาชิกหลายคน (เช่น "Yuna" ใน ITZY และ AKB48) ฟอร์มจะขึ้นรายการ candidate ใต้ช่อง Idol ให้เลือกว่าหมายถึงคนไหน เมื่อเลือกแล้วระบบจะ save ให้อัตโนมัติ
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
                    <p class="text-muted">หน้า <strong>Report</strong> แสดงการวิเคราะห์ข้อมูลใน <strong>13 มุมมอง (tab)</strong> จัดกลุ่มเป็น dropdown พร้อมกราฟแบบ interactive &mdash; tab ที่โหลดข้อมูลมากจะ lazy-load ครั้งแรกที่เปิด (งบประมาณดูได้ที่หน้า <a href="#budget">งบประมาณ</a> แยกต่างหาก)</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-grid-1x2 text-primary"></i> Overview (ภาพรวม) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">หน้า landing แสดง KPI cards (ยอดรวม จำนวน เฉลี่ย MoM%), กราฟแนวโน้มรายเดือน, Top 5 สมาชิก และ doughnut chart ตามประเภท/ค่าย</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar3 text-primary"></i> Monthly (รายเดือน)</h6>
                                <p class="small text-muted mb-2">กราฟแท่ง (ยอดเงิน) + กราฟเส้น (จำนวน) รายเดือน</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่แท่งกราฟเดือนใดก็ได้</strong> เพื่อดูรายละเอียดรายวัน พร้อม breakdown ตามประเภทและไอดอล
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-graph-up-arrow text-primary"></i> Trends (แนวโน้ม) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">กราฟยอดสะสม (cumulative) + กราฟเปลี่ยนแปลงรายเดือน (MoM%) สีเขียว/แดง พร้อม <strong>ประมาณการเดือนปัจจุบัน</strong> จากยอดจ่ายจริง</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar-week text-primary"></i> Seasonality (ฤดูกาล) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">ยอดใช้จ่ายตามวันในสัปดาห์ (จ.–อา.) และตามเดือนในรอบปี &mdash; ช่วยหาช่วงเวลาที่ซื้อบ่อยที่สุด</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-person text-primary"></i> By Member (ตามสมาชิก)</h6>
                                <p class="small text-muted mb-2">อันดับสมาชิกไอดอลตามยอดใช้จ่าย</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่ชื่อสมาชิก</strong> เพื่อดูสัดส่วนตามประเภทสินค้า + กราฟรายเดือน
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-arrow-left-right text-primary"></i> Compare (เปรียบเทียบ) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">เลือกสมาชิก 2 คนเพื่อเปรียบเทียบ side by side: summary cards, กราฟยอดรายเดือน และ grouped bars ตามประเภท</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-people text-primary"></i> By Group (ตามกลุ่ม)</h6>
                                <p class="small text-muted mb-2">ยอดใช้จ่ายรวมของแต่ละกลุ่ม/ยูนิต (นับเฉพาะ Primary Membership)</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกเพื่อขยาย</strong> ดูรายละเอียดสมาชิกในกลุ่ม
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-diagram-2 text-primary"></i> By Unit (ตามยูนิต) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">รายงานระดับ Unit รวมถึง sub-unit / project membership ที่ By Group ไม่แสดง เหมาะสำหรับวงที่มียูนิตย่อย</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-building text-primary"></i> By Company (ตามค่าย)</h6>
                                <p class="small text-muted mb-2">ยอดใช้จ่ายรวมของแต่ละค่าย</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกเพื่อขยาย</strong> ดูกลุ่ม/ยูนิตภายใต้ค่าย
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-tags text-primary"></i> By Type (ตามประเภท)</h6>
                                <p class="small text-muted mb-2">อันดับประเภทสินค้าตามยอดใช้จ่าย พร้อมจำนวนรายการและจำนวนชิ้น</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่ชื่อประเภท</strong> เพื่อดูรายละเอียดสมาชิก กลุ่ม และค่ายที่ซื้อสินค้าประเภทนั้น
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-calendar-event text-primary"></i> By Event (ตามงาน) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span> <span class="badge bg-info ms-1" style="font-size:.65rem">v1.9.7</span></h6>
                                <p class="small text-muted mb-0">แสดงยอดใช้จ่ายต่ออีเวนต์ที่ตั้งชื่อไว้ (ดูหัวข้อ <a href="#events">จัดการอีเวนต์</a>) พร้อมลิงก์ไปหน้า Items ที่กรองตามงานนั้น ส่วนรายการที่ยังไม่ผูกกับอีเวนต์จะถูกจัดกลุ่มตาม Event Date แยกไว้ด้านล่าง + สถิติ lead-time (เวลาระหว่างสั่งซื้อถึงวันงาน: เฉลี่ย/min/max)</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-trophy text-primary"></i> Top Items (รายการเด่น) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-0">20 รายการที่แพงที่สุด, 20 รายการที่ซื้อบ่อยที่สุด และราคาเฉลี่ย/min/max ต่อประเภท</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6><i class="bi bi-hourglass-split text-primary"></i> Inactive (ไม่เคลื่อนไหว) <span class="badge bg-info ms-1" style="font-size:.65rem">v1.6.1</span></h6>
                                <p class="small text-muted mb-2">สมาชิกที่ไม่มีการซื้อในช่วงที่กำหนด (30/90/180/365 วัน)</p>
                                <div class="tip-box mt-auto">
                                    <i class="bi bi-hand-index"></i> <strong>คลิกที่ชื่อสมาชิก</strong> เพื่อดูรายละเอียด
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
                        <h4 class="mb-0">งบประมาณ <span class="badge bg-info ms-1" style="font-size:.7rem">v1.8.0</span></h4>
                    </div>
                    <p class="text-muted">หน้า <strong>Budget</strong> ตั้งงบรายเดือนและติดตามยอดใช้จ่ายเทียบกับลิมิต พร้อมแถบสีเตือนสถานะ &mdash; เป็น <em>การเตือนเชิงภาพ</em> เท่านั้น ไม่บล็อกการเพิ่มรายการ</p>

                    <div class="accordion" id="accBudget">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#budgetManage">
                                    <i class="bi bi-sliders me-2"></i> ตั้งงบ (Manage)
                                </button>
                            </h2>
                            <div id="budgetManage" class="accordion-collapse collapse show" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>ตั้ง <strong>งบตั้งต้นแบบประจำ (recurring default)</strong> ที่ใช้กับทุกเดือน โดยเลือกขอบเขต (scope) ได้ 5 แบบ:</p>
                                    <table class="table table-sm help-table mt-2 mb-2">
                                        <tr><th style="width:140px">Overall (ภาพรวม)</th><td>งบรวมทั้งหมดต่อเดือน</td></tr>
                                        <tr><th>By Type</th><td>งบต่อประเภทสินค้า เช่น Photocard</td></tr>
                                        <tr><th>By Company</th><td>งบต่อค่าย</td></tr>
                                        <tr><th>By Group</th><td>งบต่อกลุ่ม/ยูนิต</td></tr>
                                        <tr><th>By Member</th><td>งบต่อสมาชิก</td></tr>
                                    </table>
                                    <p class="mb-1">แต่ละงบกำหนด <strong>ลิมิต (฿)</strong> และเกณฑ์สี:</p>
                                    <ul class="mb-0">
                                        <li><span class="badge bg-success">เขียว</span> ต่ำกว่า % เหลือง</li>
                                        <li><span class="badge bg-warning text-dark">เหลือง</span> ตั้งแต่ % เหลือง ถึงก่อน % แดง</li>
                                        <li><span class="badge bg-danger">แดง</span> ตั้งแต่ % แดงขึ้นไป (เกินงบ)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetProgress">
                                    <i class="bi bi-bar-chart-line me-2"></i> ติดตามรายเดือน (Progress)
                                </button>
                            </h2>
                            <div id="budgetProgress" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>แท็บ <strong>Progress</strong> แสดงแถบความคืบหน้าของทุกงบในเดือนที่เลือก (ใช้ไป / ลิมิต และยอดคงเหลือ)</p>
                                    <ul class="mb-0">
                                        <li>เลือกเดือนได้จากช่อง <strong>Month</strong> ด้านบน</li>
                                        <li>กดไอคอน <i class="bi bi-pencil"></i> เพื่อ <strong>กำหนดงบเฉพาะเดือนนั้น (override)</strong> โดยไม่กระทบงบตั้งต้น</li>
                                        <li>กดไอคอน <i class="bi bi-arrow-counterclockwise"></i> เพื่อ <strong>คืนค่ากลับเป็นงบตั้งต้น</strong></li>
                                        <li>ป้าย <span class="badge bg-light text-secondary border">Default</span> = ใช้งบประจำ, <span class="badge bg-primary-subtle text-primary border">Custom</span> = override เฉพาะเดือน</li>
                                    </ul>
                                    <div class="tip-box mt-2">
                                        <i class="bi bi-info-circle"></i> งบของ Group/Company/Member คำนวณยอดด้วย membership join ชุดเดียวกับรายงาน ตัวเลขจึงตรงกัน
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetInsights">
                                    <i class="bi bi-graph-up-arrow me-2"></i> ภาพรวม / Insights <span class="badge bg-info ms-2" style="font-size:.65rem">v1.9.0</span>
                                </button>
                            </h2>
                            <div id="budgetInsights" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>แท็บ <strong>ภาพรวม</strong> วิเคราะห์ยอดใช้จ่ายเทียบงบ <strong>ย้อนหลัง</strong> เพื่อตอบว่า "ที่ผ่านมาเป็นอย่างไร" (มีทั้งในหน้า Budget และแท็บ Budget ของหน้า Report)</p>
                                    <ul>
                                        <li><strong>เลือกขอบเขต (Scope)</strong> &mdash; Overall หรือ scope ใดที่มีงบ เทียบทีละ scope เพื่อเลี่ยงการนับซ้ำของหน่วยที่ซ้อนกัน</li>
                                        <li><strong>เลือกช่วงเวลา</strong> &mdash; 6 / 12 / 24 เดือนล่าสุด (ค่าเริ่มต้น 12)</li>
                                        <li><strong>การ์ด KPI</strong> &mdash; ใช้จ่ายรวม/เฉลี่ย, งบเฉลี่ย, % ใช้เฉลี่ย, จำนวนเดือนที่เกินงบ, เดือนที่ใช้สูงสุด</li>
                                        <li><strong>กราฟยอดเทียบงบรายเดือน</strong> &mdash; แท่งยอดใช้จ่าย (สีเขียว/เหลือง/แดงตามสถานะ) + เส้นลิมิต</li>
                                        <li><strong>กราฟแนวโน้ม % ของงบที่ใช้</strong> &mdash; เส้นพร้อมเส้นอ้างอิง 100%</li>
                                        <li><strong>คำแนะนำ</strong> &mdash; วิเคราะห์อัตโนมัติ เช่น เกินงบบ่อย, เกินเดือนล่าสุด, คาดว่าจะเกินตามอัตราเดือนปัจจุบัน, แนวโน้มขึ้น/ลง, ใช้ต่ำกว่างบสม่ำเสมอ (แนะนำปรับลดลิมิต) หรืออยู่ในเกณฑ์ดี</li>
                                    </ul>
                                    <div class="tip-box mb-0">
                                        <i class="bi bi-lightbulb"></i> ถ้า scope ที่เลือกมีการใช้จ่ายแต่ยังไม่ได้ตั้งงบ ระบบจะแนะนำให้ตั้งงบเพื่อเริ่มติดตาม
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#budgetMonthly">
                                    <i class="bi bi-grid-3x3 me-2"></i> ตารางรายเดือน (Monthly) <span class="badge bg-info ms-2" style="font-size:.65rem">v1.9.6</span>
                                </button>
                            </h2>
                            <div id="budgetMonthly" class="accordion-collapse collapse" data-bs-parent="#accBudget">
                                <div class="accordion-body">
                                    <p>แท็บ <strong>Monthly</strong> แสดงตาราง Scopes × Months &mdash; หนึ่งแถวต่อหนึ่ง scope งบ คอลัมน์ <strong>Default</strong> ตามด้วยคอลัมน์รายเดือนตลอดทั้งปี ใช้ปุ่ม ‹ ปี › เพื่อเปลี่ยนปีที่ดู</p>
                                    <ul class="mb-0">
                                        <li>คลิกช่อง <strong>Default</strong> เพื่อแก้งบตั้งต้นแบบประจำ</li>
                                        <li>คลิกช่องเดือนใดก็ได้เพื่อ <strong>ตั้ง/override</strong> งบเฉพาะเดือนนั้น (ค่าเริ่มต้นดึงจาก override เดิมหรืองบตั้งต้น) เดือนที่ override ไว้จะถูกไฮไลต์ พร้อมปุ่มรีเซ็ตกลับเป็นค่าตั้งต้นได้ทันที</li>
                                        <li>แถวสรุปท้ายตารางรวมยอดแต่ละเดือน: <strong>งบรวม (Overall)</strong>, <strong>จัดสรรแล้ว (Allocated)</strong>, <strong>คงเหลือยังไม่จัดสรร (Unallocated)</strong> (แดงถ้าจัดสรรเกินงบรวม)</li>
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
                        <h4 class="mb-0">จัดการอีเวนต์ <span class="badge bg-info ms-1" style="font-size:.7rem">v1.9.7</span></h4>
                    </div>
                    <p class="text-muted">หน้า <strong>Events</strong> ใช้ตั้งชื่อ "งาน" จริง (คอนเสิร์ต แฟนมีต แฟนไซน์ ฯลฯ) แล้วผูกรายการที่ซื้อเข้ากับงานนั้น แทนที่จะมีแค่วันที่งานลอย ๆ เหมือนเดิม</p>

                    <div class="accordion" id="accEvents">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#eventAdd">
                                    <i class="bi bi-plus-circle me-2"></i> สร้างอีเวนต์
                                </button>
                            </h2>
                            <div id="eventAdd" class="accordion-collapse collapse show" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>กดปุ่ม <strong>Add Event</strong> แล้วกรอก:</p>
                                    <table class="table table-sm help-table mt-2 mb-0">
                                        <tr><th style="width:140px">Event Name</th><td>ชื่องาน เช่น "TWICE 5th World Tour Bangkok" (ตั้งซ้ำชื่อเดิมคนละวันได้)</td></tr>
                                        <tr><th>Event Date</th><td>วันที่จัดงาน</td></tr>
                                        <tr><th>Description</th><td>รายละเอียดเพิ่มเติม (ไม่บังคับ)</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventLink">
                                    <i class="bi bi-link-45deg me-2"></i> ผูกรายการเข้ากับอีเวนต์
                                </button>
                            </h2>
                            <div id="eventLink" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>ในฟอร์มเพิ่ม/แก้ไขรายการ (หน้า Items) มีช่อง <strong>Event</strong> แบบค้นหาได้ &mdash; พิมพ์ชื่องานแล้วเลือกจากรายการ ระบบจะ <strong>เติม Event Date ให้อัตโนมัติ</strong> (ยังแก้ไขวันที่เองได้ภายหลัง) รายการที่ผูกแล้วจะมีป้ายชื่องานแสดงในตารางรายการ</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventBulk">
                                    <i class="bi bi-collection me-2"></i> ผูกข้อมูลเก่าแบบกลุ่ม
                                </button>
                            </h2>
                            <div id="eventBulk" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p>สำหรับรายการเก่าที่มี Event Date อยู่แล้วแต่ยังไม่ได้ผูกกับอีเวนต์ที่ตั้งชื่อ มี 2 วิธี:</p>
                                    <ul class="mb-0">
                                        <li><strong>Auto-assign</strong> &mdash; ในหน้า Events แต่ละแถวจะมีปุ่มแสดงจำนวนรายการที่มีวันที่ตรงกันแต่ยังไม่ผูก กดปุ่มเดียวผูกให้ทั้งหมด</li>
                                        <li><strong>เลือกแล้วผูกเอง</strong> &mdash; ในหน้า Items ติ๊กเลือกหลายรายการ (checkbox ซ้ายสุดของตาราง) แล้วกด <strong>Assign to Event</strong> เพื่อเลือกอีเวนต์ปลายทาง</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#eventFilter">
                                    <i class="bi bi-funnel me-2"></i> กรองรายการตามอีเวนต์
                                </button>
                            </h2>
                            <div id="eventFilter" class="accordion-collapse collapse" data-bs-parent="#accEvents">
                                <div class="accordion-body">
                                    <p class="mb-0">ในหน้า Items ตัวกรอง <strong>Event</strong> เลือกได้หลายอีเวนต์พร้อมกัน (เหมือนตัวกรอง Idol/Type) และคลิกจำนวนรายการในหน้า Events ก็พาไปหน้า Items ที่กรองตามอีเวนต์นั้นทันที</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tip-box mb-0">
                        <i class="bi bi-info-circle"></i> ลบอีเวนต์จะแค่ <strong>ยกเลิกการผูก</strong> รายการที่เชื่อมอยู่ (event_id กลับเป็นว่าง) &mdash; ไม่ลบรายการสินค้า
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
                    <p class="text-muted">จัดการโครงสร้างลำดับชั้นของไอดอล &mdash; ตั้งแต่ v1.5 รองรับการย้ายวงและชื่อซ้ำ</p>

                    <h6>โครงสร้างลำดับชั้น</h6>
                    <div class="border rounded p-3 mb-3" style="background:#f9fafb; font-family: monospace; font-size:13px;">
                        <i class="bi bi-building"></i> <strong>Company</strong> (ค่าย)<br>
                        <span class="ms-3"><i class="bi bi-people"></i> <strong>Group / Unit</strong> (กลุ่ม / ยูนิต)</span><br>
                        <span class="ms-5"><i class="bi bi-person"></i> <strong>Member</strong> (สมาชิก) &mdash; ผูกกับวงผ่าน <strong>Membership</strong> (มีช่วงเวลา)</span>
                    </div>

                    <div class="accordion" id="accIdols">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#idolAdd">
                                    <i class="bi bi-plus-circle me-2"></i> เพิ่ม / แก้ไข Entity
                                </button>
                            </h2>
                            <div id="idolAdd" class="accordion-collapse collapse show" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <ol>
                                        <li>กดปุ่ม <span class="shortcut-key">Add Entity</span></li>
                                        <li>กรอก <strong>Name</strong>, เลือก <strong>Category</strong> (company / group / unit / member)</li>
                                        <li>เลือก <strong>Parent</strong> (สังกัดเริ่มต้น) &mdash; สำหรับ Member จะใช้เป็นกลุ่มหลักอัตโนมัติ</li>
                                        <li><strong>Display Hint</strong> (ทางเลือก) &mdash; ใส่ label สั้น ๆ เช่น <code>ITZY</code>, <code>AKB48 Team A</code> เพื่อแยกเวลามีชื่อซ้ำ</li>
                                        <li>กดปุ่ม <strong>Save</strong> &mdash; ถ้าเป็น Member ระบบจะสร้าง Primary Membership ให้อัตโนมัติจาก Parent</li>
                                    </ol>
                                    <div class="tip-box">
                                        <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> ถ้าตั้งชื่อซ้ำกับ Member ที่มีอยู่แล้ว ระบบจะเตือนและช่วยเติม Display Hint จาก Parent ให้อัตโนมัติ (แก้ได้)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#idolMembership">
                                    <i class="bi bi-arrow-left-right me-2"></i> Membership &mdash; ย้ายวง / ติดตามประวัติสังกัด <span class="badge bg-info ms-2">v1.5</span>
                                </button>
                            </h2>
                            <div id="idolMembership" class="accordion-collapse collapse" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <p>ตั้งแต่ v1.5 ทุก Member เก็บ <strong>ประวัติการสังกัด</strong> เป็นช่วงเวลา (start_date &rarr; end_date) ทำให้รายงาน By Group แยก items ก่อน/หลังย้ายวงได้อย่างถูกต้องโดยอัตโนมัติ ตามวันที่ใน Order Date ของแต่ละ item</p>

                                    <h6 class="mt-3">ดูและจัดการ Memberships</h6>
                                    <ol>
                                        <li>เข้าหน้า <strong>Idols</strong> แล้วกดไอคอน <i class="bi bi-pencil"></i> แก้ไขที่ Member ที่ต้องการ</li>
                                        <li>เลื่อนลงไปส่วน <strong>Memberships</strong> ในฟอร์ม &mdash; แสดงรายการ (Group, ช่วงวันที่, primary/sub-unit)</li>
                                    </ol>

                                    <h6>กรณีย้ายวง (วิธีเร็วสุด)</h6>
                                    <ol>
                                        <li>กดปุ่ม <span class="shortcut-key">Move to new group</span></li>
                                        <li>เลือกวงใหม่ + ระบุวันที่ที่เริ่มสังกัดวงใหม่ (Move date)</li>
                                        <li>กด <strong>Save</strong> &mdash; ระบบจะปิด membership ปัจจุบันที่ <em>Move date &minus; 1</em> และเปิดอันใหม่ที่ Move date โดยอัตโนมัติ</li>
                                    </ol>

                                    <h6>เพิ่ม Membership แบบกำหนดเอง</h6>
                                    <ul>
                                        <li>กด <span class="shortcut-key">Add membership</span> เพื่อเพิ่ม membership คู่ขนาน (เช่น sub-unit, project group)</li>
                                        <li><strong>Primary</strong> (ติ๊กถูก) = วงหลัก ใช้คิดในรายงาน By Group / By Company &mdash; ต้องมีแค่ 1 ช่วง ณ เวลาเดียวเท่านั้น</li>
                                        <li><strong>Sub-unit</strong> (ไม่ติ๊ก) = แสดงเฉพาะใน drill-down ของวง (ไม่นับซ้ำในยอดรวม)</li>
                                    </ul>
                                    <div class="tip-box">
                                        <i class="bi bi-info-circle"></i> <strong>คำเตือน Overlap:</strong> ถ้ามี Primary 2 ช่วงซ้อนกัน ระบบจะเตือนแต่ยังบันทึกได้ (เพื่อให้แก้ไขย้อนหลังได้สะดวก)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#idolDuplicate">
                                    <i class="bi bi-people-fill me-2"></i> ชื่อซ้ำ / Display Hint <span class="badge bg-info ms-2">v1.5</span>
                                </button>
                            </h2>
                            <div id="idolDuplicate" class="accordion-collapse collapse" data-bs-parent="#accIdols">
                                <div class="accordion-body">
                                    <p>v1.5 อนุญาตให้มี Member ชื่อเดียวกันได้หลายคน (เช่น "Yuna" ใน ITZY และ AKB48) ระบบใช้ <strong>Display Hint</strong> ในการแยกแยะใน UI และใช้ <strong>idol_id</strong> ภายใต้ใน DB</p>

                                    <h6>เวลาเพิ่ม Item ที่ชื่อ Idol ซ้ำ</h6>
                                    <ul>
                                        <li>ในฟอร์ม Add Item ถ้าพิมพ์ชื่อที่ตรงกับสมาชิกหลายคน ระบบจะขึ้นปุ่มให้เลือกว่าหมายถึงคนไหน เช่น <code>Yuna [ITZY]</code> หรือ <code>Yuna [AKB48]</code></li>
                                        <li>เมื่อเลือกแล้วระบบจะผูก item เข้ากับ entity ที่ถูกต้องและ save อัตโนมัติ</li>
                                    </ul>

                                    <h6>Ambiguous Mappings (รายชื่อค้าง)</h6>
                                    <p>หาก items เก่าที่ import มาก่อนหน้านี้ใช้ชื่อกำกวม จะค้างอยู่ใน <strong>Ambiguous Mappings panel</strong> ในหน้า Idols (ฝั่งขวา) และมี <em>banner เตือน</em> ที่หน้า Items ด้วย</p>
                                    <ol>
                                        <li>กดปุ่ม <span class="shortcut-key">Resolve Conflicts</span></li>
                                        <li>ระบบจะแสดงรายชื่อกำกวมพร้อม candidate ทุกตัว</li>
                                        <li>คลิกที่ candidate ที่ต้องการเพื่อ bulk-remap items ทุกอันที่มีชื่อเดียวกันไปยัง entity นั้น</li>
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
                                    <p>ระบบตรวจจับชื่อ Idol ใน items ที่ยังไม่มี entity และแสดงเป็นรายการ <strong>Quick Add</strong> เพื่อสร้าง Member ใหม่ได้ในคลิกเดียว เมื่อสร้างแล้วระบบจะ <em>auto-backfill</em> idol_id ของ items ที่ใช้ชื่อตรงกันให้อัตโนมัติ (เฉพาะกรณีไม่กำกวม)</p>
                                    <h6>Tree Stats</h6>
                                    <ul class="mb-0">
                                        <li>แต่ละ entity แสดงสถิติ <strong>จำนวนรายการ</strong> และ <strong>ยอดใช้จ่ายรวม</strong></li>
                                        <li>Member ที่มีประวัติ membership มากกว่า 1 จะมีไอคอน <i class="bi bi-arrow-left-right text-info"></i> ข้างชื่อ</li>
                                        <li>Member ที่ตั้งค่า Display Hint จะแสดงเป็น <code>Name [hint]</code> ในต้นไม้</li>
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
                        <h4 class="mb-0">จัดการประเภทสินค้า</h4>
                    </div>
                    <p class="text-muted">จัดการหมวดหมู่ประเภทสินค้า เช่น Photocard, T-Shirt, Lightstick</p>

                    <ol>
                        <li>กดปุ่ม <span class="shortcut-key">Add Type</span></li>
                        <li>กรอก <strong>Name</strong> (ชื่อประเภท), <strong>Description</strong> (คำอธิบาย), <strong>Sort Order</strong> (ลำดับ)</li>
                        <li>กดปุ่ม <strong>Save</strong></li>
                    </ol>

                    <p class="small text-muted mb-2">แต่ละประเภทจะแสดงสถิติ: จำนวนแถว, จำนวนชิ้น, ยอดใช้จ่ายรวม นอกจากนี้ยังมีระบบ <strong>Unmapped Names</strong> เพื่อตรวจจับชื่อ Type ที่ยังไม่ได้เพิ่มในระบบ</p>
                    <h6 class="mt-2">Members by Type</h6>
                    <p class="small text-muted mb-0">ด้านล่างของหน้า Types มีส่วน <strong>Members by Type</strong> แสดง accordion รายชื่อสมาชิก กลุ่ม และค่ายที่ซื้อสินค้าในแต่ละประเภท พร้อมสถิติจำนวนรายการ จำนวนชิ้น และยอดใช้จ่าย</p>
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
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqMove">
                                    <span class="badge bg-info me-2">v1.5</span> ไอดอลย้ายวง ทำยังไงให้รายงานก่อน/หลังย้ายแยกกัน?
                                </button>
                            </h2>
                            <div id="faqMove" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <ol class="mb-2">
                                        <li>ไปหน้า <strong>Idols</strong> &rarr; กดไอคอน <i class="bi bi-pencil"></i> ที่ Member ที่ย้ายวง</li>
                                        <li>เลื่อนลงไปส่วน <strong>Memberships</strong> &rarr; กดปุ่ม <span class="shortcut-key">Move to new group</span></li>
                                        <li>เลือกวงใหม่ + ระบุวันที่เริ่มสังกัด (Move date) &rarr; กด Save</li>
                                    </ol>
                                    <p class="mb-0">ระบบจะปิด membership ปัจจุบันที่ <em>Move date &minus; 1</em> และเปิดอันใหม่ที่ Move date รายงาน By Group/By Company จะใช้ <code>order_date</code> ของแต่ละ item เทียบกับช่วง membership เพื่อ map เข้าวงที่ถูกต้องโดยอัตโนมัติ &mdash; <strong>ไม่ต้องแก้ items เก่าเอง</strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqDuplicate">
                                    <span class="badge bg-info me-2">v1.5</span> มีไอดอลชื่อซ้ำกัน 2 คน คนละวง ทำยังไง?
                                </button>
                            </h2>
                            <div id="faqDuplicate" class="accordion-collapse collapse" data-bs-parent="#accFaq">
                                <div class="accordion-body">
                                    <p>v1.5 รองรับชื่อซ้ำได้ ใช้ <strong>Display Hint</strong> แยกใน UI:</p>
                                    <ol>
                                        <li>เพิ่มทั้ง 2 Member ตามปกติ ใช้ชื่อจริง (เช่น "Yuna" 2 entity)</li>
                                        <li>กรอก <strong>Display Hint</strong> ของแต่ละคน เช่น <code>ITZY</code> และ <code>AKB48</code></li>
                                        <li>ใน Tree View จะเห็นเป็น <code>Yuna [ITZY]</code> และ <code>Yuna [AKB48]</code></li>
                                        <li>เวลาเพิ่ม Item ที่ชื่อกำกวม ฟอร์มจะให้เลือกว่าหมายถึงคนไหน</li>
                                    </ol>
                                    <p class="mb-0">หาก items เก่ามีชื่อกำกวมอยู่แล้ว ระบบจะเตือนผ่าน <strong>Ambiguous Mappings panel</strong> ที่หน้า Idols และมี banner ที่หน้า Items กดปุ่ม <span class="shortcut-key">Resolve Conflicts</span> เพื่อ map ทีละรายการ</p>
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
