<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($ext !== 'sqlite') {
    echo json_encode(['error' => 'Only .sqlite files are allowed']);
    exit;
}

// Validate it's a real SQLite file
$header = file_get_contents($file['tmp_name'], false, null, 0, 16);
if (substr($header, 0, 13) !== 'SQLite format') {
    echo json_encode(['error' => 'Invalid SQLite file']);
    exit;
}

$filename = 'uploaded_' . date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
$dest = BACKUP_DIR . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

echo json_encode(['success' => true, 'filename' => $filename, 'size' => filesize($dest)]);
