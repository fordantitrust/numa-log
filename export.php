<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

requireAuth();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$pdo = getDB();

// Build filters — same parameters as api.php handleList
$where = [];
$params = [];

$idols = array_values(array_filter((array) ($_GET['idol'] ?? [])));
if (!empty($idols)) {
    $phs = implode(',', array_map(fn($i) => ":idol$i", array_keys($idols)));
    $where[] = "idol IN ($phs)";
    foreach ($idols as $i => $v) { $params[":idol$i"] = $v; }
}
$types = array_values(array_filter((array) ($_GET['type'] ?? [])));
if (!empty($types)) {
    $phs = implode(',', array_map(fn($i) => ":type$i", array_keys($types)));
    $where[] = "type IN ($phs)";
    foreach ($types as $i => $v) { $params[":type$i"] = $v; }
}
if (!empty($_GET['search'])) {
    $where[] = 'title LIKE :search';
    $params[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['date_from'])) {
    $where[] = 'order_date >= :date_from';
    $params[':date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'order_date <= :date_to';
    $params[':date_to'] = $_GET['date_to'];
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT order_date, event_date, title, idol, type, price_per_qty, qty
    FROM items {$whereSQL}
    ORDER BY order_date ASC, id ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Build spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Items');

// Header row — same column order as import format + Price Total
$headers = ['Order Date', 'Event Date', 'Title', 'Idol', 'Type', 'Price per Qty', 'Qty', 'Price Total'];
foreach ($headers as $col => $header) {
    $sheet->setCellValue(chr(65 + $col) . '1', $header);
}

// Style header row
$sheet->getStyle('A1:H1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// Data rows
foreach ($rows as $i => $row) {
    $r = $i + 2;
    $sheet->setCellValue('A' . $r, $row['order_date'] ?: '');
    $sheet->setCellValue('B' . $r, $row['event_date'] ?: '');
    $sheet->setCellValue('C' . $r, $row['title']);
    $sheet->setCellValue('D' . $r, $row['idol']);
    $sheet->setCellValue('E' . $r, $row['type']);
    $sheet->setCellValue('F' . $r, (float) $row['price_per_qty']);
    $sheet->setCellValue('G' . $r, (int) $row['qty']);
    $sheet->setCellValue('H' . $r, '=F' . $r . '*G' . $r);
}

// Auto-size columns
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Freeze header row
$sheet->freezePane('A2');

// Filename: include date range or current date
$parts = ['numa-log'];
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $parts[] = $_GET['date_from'] . '_' . $_GET['date_to'];
} elseif (!empty($_GET['date_from'])) {
    $parts[] = 'from-' . $_GET['date_from'];
} elseif (!empty($_GET['date_to'])) {
    $parts[] = 'to-' . $_GET['date_to'];
} else {
    $parts[] = date('Y-m-d');
}
$filename = implode('_', $parts) . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-store');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
