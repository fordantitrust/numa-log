<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

function seedIdolEntities(): array
{
    $pdo = getDB();

    // Clear existing
    $pdo->exec('DELETE FROM idol_entities');

    // Hierarchy definition: [name, category, parent_name, sort_order]
    $entities = [
        // === Companies / Labels ===
        ['Catsolute',       'company', null,          1],
        ['IC45',            'company', null,          2],
        ['Solo',            'company', null,          2],

        // === Groups (under Catsolute) ===
        ['Sora Sora',       'group', 'Catsolute',     1],
        ['Yami Yami',       'group', 'Catsolute',     2],
        ['Mirai Mirai',     'group', 'Catsolute',     3],
        ['The Glass Girls', 'group', 'IC45',     4],
        ['Nikko Nikko',     'group', 'IC45',     5],

        // === Members: Sora Sora ===
        ['Kitty',           'member', 'Sora Sora',    1],
        ['Jennie',          'member', 'Sora Sora',    2],
        ['Pin',             'member', 'Sora Sora',    3],
        ['Ame',             'member', 'Sora Sora',    4],
        ['Yiwha',           'member', 'Sora Sora',    5],
        ['Minmin',          'member', 'Sora Sora',    6],
        ['Best',            'member', 'Sora Sora',    7],

        // === Members: Yami Yami ===
        ['Jan',             'member', 'Yami Yami',    1],
        ['Eri',             'member', 'Yami Yami',    2],
        ['Haru',            'member', 'Yami Yami',    3],

        // === Members: Mirai Mirai ===
        ['Gracenae',        'member', 'Mirai Mirai',   1],
        ['Kris',            'member', 'Mirai Mirai',   2],

        // === Members: The Glass Girls ===
        ['Mint',            'member', 'The Glass Girls', 1],
        ['Kaimook',         'member', 'The Glass Girls', 2],

        // === Members: Nikko Nikko ===
        ['Eye',             'member', 'Nikko Nikko',   1],

        // === Solo / Catsolute direct ===
        ['Mahnmook',        'member', 'Solo',     1],
        ['Tita',            'member', 'Solo',     2],
        ['Pim',             'member', 'Solo',     3],
        ['Knomwhan',        'member', 'Solo',     4],
    ];

    // First pass: insert all without parent_id
    $insertStmt = $pdo->prepare('INSERT INTO idol_entities (name, category, sort_order) VALUES (:name, :category, :sort)');
    foreach ($entities as [$name, $category, $parent, $sort]) {
        $insertStmt->execute([':name' => $name, ':category' => $category, ':sort' => $sort]);
    }

    // Second pass: set parent_id
    $updateStmt = $pdo->prepare('UPDATE idol_entities SET parent_id = (SELECT id FROM idol_entities WHERE name = :parent) WHERE name = :name');
    foreach ($entities as [$name, $category, $parent, $sort]) {
        if ($parent !== null) {
            $updateStmt->execute([':parent' => $parent, ':name' => $name]);
        }
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM idol_entities')->fetchColumn();
    return ['success' => true, 'message' => "Seeded {$count} idol entities."];
}

// Handle AJAX call
if (php_sapi_name() !== 'cli' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'seed') {
    requireAdmin();
    header('Content-Type: application/json');
    if (!ALLOW_RESEED) {
        echo json_encode(['success' => false, 'message' => 'Re-seed is disabled. Set ALLOW_RESEED to true in config.php']);
        exit;
    }
    echo json_encode(seedIdolEntities());
    exit;
}

// CLI
if (php_sapi_name() === 'cli') {
    $result = seedIdolEntities();
    echo $result['message'] . PHP_EOL;
}
