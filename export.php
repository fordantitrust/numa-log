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
$eventIds = array_values(array_filter(array_map('intval', (array) ($_GET['event_id'] ?? []))));
if (!empty($eventIds)) {
    $phs = implode(',', array_map(fn($i) => ":evid$i", array_keys($eventIds)));
    $where[] = "i.event_id IN ($phs)";
    foreach ($eventIds as $i => $v) { $params[":evid$i"] = $v; }
}

// Same carve-out as api.php handleList: an explicit type[] filter wins, so exporting
// a filtered view of the excluded type still works.
if (empty($types)) {
    $p = excludedTypesPredicate('i.type');
    if ($p !== '') {
        $where[] = $p;
    }
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT i.order_date, i.event_date, i.title, i.idol, i.type,
           i.price_per_qty, i.qty, i.idol_id, ev.name AS event_name
    FROM items i
    LEFT JOIN events ev ON ev.id = i.event_id
    {$whereSQL}
    ORDER BY i.order_date ASC, i.id ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Build spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Items');

// Header row — same column order as import format + Price Total + Event Name + Idol ID
$headers = ['Order Date', 'Event Date', 'Title', 'Idol', 'Type', 'Price per Qty', 'Qty', 'Price Total', 'Event Name', 'Idol ID'];
foreach ($headers as $col => $header) {
    $sheet->setCellValue(chr(65 + $col) . '1', $header);
}

// Style header row
$sheet->getStyle('A1:J1')->applyFromArray([
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
    $sheet->setCellValue('I' . $r, $row['event_name'] ?? '');
    if ($row['idol_id'] !== null) {
        $sheet->setCellValue('J' . $r, (int) $row['idol_id']);
    }
}

// Auto-size columns
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
// Idol ID column is informational — hide by default; users can unhide if needed.
$sheet->getColumnDimension('J')->setVisible(false);

// Freeze header row
$sheet->freezePane('A2');

// Validate and sanitize date params before using in filename
$safeFrom = (isset($_GET['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from']))
    ? $_GET['date_from'] : '';
$safeTo = (isset($_GET['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to']))
    ? $_GET['date_to'] : '';

// Filename: include date range or current date
$parts = ['numa-log'];
if ($safeFrom !== '' && $safeTo !== '') {
    $parts[] = $safeFrom . '_' . $safeTo;
} elseif ($safeFrom !== '') {
    $parts[] = 'from-' . $safeFrom;
} elseif ($safeTo !== '') {
    $parts[] = 'to-' . $safeTo;
} else {
    $parts[] = date('Y-m-d');
}
// Distinguish the two exports on disk — otherwise a full and a filtered export of the
// same range are indistinguishable once downloaded.
if (includeExcludedTypes()) {
    $parts[] = 'with-excluded';
}
// Only allow safe characters in filename to prevent header injection
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', implode('_', $parts)) . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0, no-store');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
