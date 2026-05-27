<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

if (php_sapi_name() !== 'cli') {
    requireAdmin();
}

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

function excelDateToString(mixed $value): string
{
    if ($value === null || $value === '' || $value === '-') {
        return '';
    }
    if (is_numeric($value)) {
        $timestamp = ExcelDate::excelToTimestamp((int) $value);
        return date('Y-m-d', $timestamp);
    }
    return (string) $value;
}

function importExcel(string $filePath): array
{
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => "File not found: {$filePath}"];
    }

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    // Detect optional idol_id column by header.
    // Export uses column I; legacy/manual imports may put it in column H.
    $idolIdCol = null;
    foreach (['H', 'I'] as $col) {
        if (strcasecmp($highestCol, $col) < 0) break;
        $header = trim((string) $sheet->getCell("{$col}1")->getValue());
        if (stripos($header, 'idol id') !== false || stripos($header, 'idol_id') !== false) {
            $idolIdCol = $col;
            break;
        }
    }

    $pdo = getDB();

    // Clear existing data
    $pdo->exec('DELETE FROM items');

    $stmt = $pdo->prepare('
        INSERT INTO items (order_date, event_date, title, idol, type, price_per_qty, qty, idol_id)
        VALUES (:order_date, :event_date, :title, :idol, :type, :price_per_qty, :qty, :idol_id)
    ');

    $imported  = 0;
    $skipped   = 0;
    $ambiguous = 0;     // items with idol name matching multiple entities → idol_id left NULL

    $pdo->beginTransaction();

    for ($row = 2; $row <= $highestRow; $row++) {
        $title = $sheet->getCell("C{$row}")->getValue();
        if ($title instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $title = $title->getPlainText();
        }

        // Skip empty rows
        if (empty($title)) {
            $skipped++;
            continue;
        }

        $orderDate    = excelDateToString($sheet->getCell("A{$row}")->getValue());
        $eventDate    = excelDateToString($sheet->getCell("B{$row}")->getValue());
        $idol         = (string) ($sheet->getCell("D{$row}")->getValue() ?? '');
        $type         = (string) ($sheet->getCell("E{$row}")->getValue() ?? '');
        $pricePerQty  = (float)  ($sheet->getCell("F{$row}")->getValue() ?? 0);
        $qty          = (int)    ($sheet->getCell("G{$row}")->getValue() ?? 1);

        // Idol resolution
        $idolId = null;
        if ($idolIdCol !== null) {
            $explicitId = (int) ($sheet->getCell("{$idolIdCol}{$row}")->getValue() ?? 0);
            if ($explicitId > 0) $idolId = $explicitId;
        }
        if ($idolId === null && $idol !== '') {
            $r = resolveIdolByName($pdo, $idol);
            if ($r['ambiguous']) {
                $ambiguous++;     // queued for resolution UI, leave idol_id NULL
            } else {
                $idolId = $r['id'];
            }
        }

        $stmt->execute([
            ':order_date'    => $orderDate,
            ':event_date'    => $eventDate,
            ':title'         => $title,
            ':idol'          => $idol,
            ':type'          => $type,
            ':price_per_qty' => $pricePerQty,
            ':qty'           => $qty,
            ':idol_id'       => $idolId,
        ]);

        $imported++;
    }

    $pdo->commit();

    $msg = "Import complete: {$imported} rows imported, {$skipped} rows skipped";
    if ($ambiguous > 0) {
        $msg .= ". {$ambiguous} row(s) had ambiguous idol names — open Idol Management to resolve.";
    }
    return [
        'success'         => true,
        'message'         => $msg,
        'imported'        => $imported,
        'skipped'         => $skipped,
        'ambiguous_queued' => $ambiguous,
    ];
}

// Handle direct access (AJAX call from web UI)
if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'import') {
    verifyCsrf();
    header('Content-Type: application/json');
    if (!ALLOW_IMPORT) {
        echo json_encode(['success' => false, 'message' => 'Import is disabled. Set ALLOW_IMPORT to true in config.php']);
        exit;
    }
    $file = __DIR__ . '/idols.xlsx';
    echo json_encode(importExcel($file));
    exit;
}
