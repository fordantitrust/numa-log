<?php

declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json');
requireAuth();

// CSRF check for state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
}

$pdo = getDB();
$action = $_REQUEST['action'] ?? '';

try {
    match ($action) {
        'list' => handleList($pdo),
        'get' => handleGet($pdo),
        'create' => handleCreate($pdo),
        'update' => handleUpdate($pdo),
        'delete' => handleDelete($pdo),
        'filters' => handleFilters($pdo),
        'report_monthly' => handleReportMonthly($pdo),
        'report_daily' => handleReportDaily($pdo),
        'report_idol' => handleReportIdol($pdo),
        'report_type' => handleReportType($pdo),
        'report_idol_detail' => handleReportIdolDetail($pdo),
        'report_by_group' => handleReportByGroup($pdo),
        'report_by_company' => handleReportByCompany($pdo),
        'idol_entities_tree' => handleIdolEntitiesTree($pdo),
        'idol_entity_save' => handleIdolEntitySave($pdo),
        'idol_entity_delete' => handleIdolEntityDelete($pdo),
        'type_list' => handleTypeList($pdo),
        'type_members_report' => handleTypeByMembers($pdo),
        'report_type_detail' => handleReportTypeDetail($pdo),
        'type_save' => handleTypeSave($pdo),
        'type_delete' => handleTypeDelete($pdo),
        'backup_list' => handleBackupList(),
        'backup_create' => handleBackupCreate(),
        'backup_restore' => handleBackupRestore(),
        'backup_delete' => handleBackupDelete(),
        'backup_download' => handleBackupDownload(),
        default => jsonResponse(['error' => 'Unknown action'], 400),
    };
} catch (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    jsonResponse(['error' => 'An internal error occurred'], 500);
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function handleList(PDO $pdo): void
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if (!empty($_GET['idol'])) {
        $where[] = 'idol = :idol';
        $params[':idol'] = $_GET['idol'];
    }
    if (!empty($_GET['type'])) {
        $where[] = 'type = :type';
        $params[':type'] = $_GET['type'];
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

    $sortCol = $_GET['sort'] ?? 'order_date';
    $sortDir = (strtolower($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
    $allowedSort = ['order_date', 'event_date', 'title', 'idol', 'type', 'price_per_qty', 'qty', 'id'];
    if (!in_array($sortCol, $allowedSort, true)) {
        $sortCol = 'order_date';
    }

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM items {$whereSQL}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Summary
    $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(price_per_qty * qty), 0) as total_price, COALESCE(SUM(qty), 0) as total_qty FROM items {$whereSQL}");
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch();

    // Fetch rows
    $stmt = $pdo->prepare("
        SELECT id, order_date, event_date, title, idol, type, price_per_qty, qty,
               (price_per_qty * qty) as total_price
        FROM items {$whereSQL}
        ORDER BY {$sortCol} {$sortDir}, id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse([
        'data' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => (int) ceil($total / $perPage),
        'summary' => [
            'total_price' => (float) $summary['total_price'],
            'total_qty' => (int) $summary['total_qty'],
        ],
    ]);
}

function handleGet(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();
    if (!$item) {
        jsonResponse(['error' => 'Item not found'], 404);
    }
    jsonResponse(['data' => $item]);
}

function handleCreate(PDO $pdo): void
{
    $data = getInputData();
    $stmt = $pdo->prepare('
        INSERT INTO items (order_date, event_date, title, idol, type, price_per_qty, qty)
        VALUES (:order_date, :event_date, :title, :idol, :type, :price_per_qty, :qty)
    ');
    $stmt->execute($data);
    jsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
}

function handleUpdate(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    $data = getInputData();
    $data[':id'] = $id;
    $stmt = $pdo->prepare("
        UPDATE items SET
            order_date = :order_date,
            event_date = :event_date,
            title = :title,
            idol = :idol,
            type = :type,
            price_per_qty = :price_per_qty,
            qty = :qty,
            updated_at = datetime('now','localtime')
        WHERE id = :id
    ");
    $stmt->execute($data);
    jsonResponse(['success' => true]);
}

function handleDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
    $stmt->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

function handleFilters(PDO $pdo): void
{
    $idols = $pdo->query("SELECT DISTINCT idol FROM items WHERE idol != '' ORDER BY idol")->fetchAll(PDO::FETCH_COLUMN);
    $types = $pdo->query("SELECT DISTINCT type FROM items WHERE type != '' ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
    jsonResponse(['idols' => $idols, 'types' => $types]);
}

function handleReportMonthly(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT
            strftime('%Y-%m', order_date) as month,
            COUNT(*) as items,
            SUM(qty) as total_qty,
            SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE order_date != ''
        GROUP BY month
        ORDER BY month
    ")->fetchAll();
    jsonResponse(['data' => $rows]);
}

function handleReportDaily(PDO $pdo): void
{
    $month = $_GET['month'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        jsonResponse(['error' => 'month parameter required (YYYY-MM)'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT
            order_date as day,
            COUNT(*) as items,
            SUM(qty) as total_qty,
            SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE strftime('%Y-%m', order_date) = :month AND order_date != ''
        GROUP BY order_date
        ORDER BY order_date
    ");
    $stmt->execute([':month' => $month]);
    $rows = $stmt->fetchAll();

    // Also get available months for dropdown
    $months = $pdo->query("
        SELECT DISTINCT strftime('%Y-%m', order_date) as month
        FROM items WHERE order_date != ''
        ORDER BY month DESC
    ")->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse(['data' => $rows, 'months' => $months]);
}

function handleReportIdol(PDO $pdo): void
{
    $hasMemberEntities = (int) $pdo->query("SELECT COUNT(*) FROM idol_entities WHERE category = 'member'")->fetchColumn() > 0;

    if ($hasMemberEntities) {
        $rows = $pdo->query("
            SELECT
                i.idol,
                COUNT(*) as items,
                SUM(i.qty) as total_qty,
                SUM(i.price_per_qty * i.qty) as total_price
            FROM items i
            JOIN idol_entities e ON e.name = i.idol AND e.category = 'member'
            GROUP BY i.idol
            ORDER BY total_price DESC
        ")->fetchAll();
    } else {
        // Fallback: show all if no idol_entities defined
        $rows = $pdo->query("
            SELECT
                idol,
                COUNT(*) as items,
                SUM(qty) as total_qty,
                SUM(price_per_qty * qty) as total_price
            FROM items
            WHERE idol != '' AND idol != '-'
            GROUP BY idol
            ORDER BY total_price DESC
        ")->fetchAll();
    }

    jsonResponse(['data' => $rows]);
}

function handleReportType(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT
            type,
            COUNT(*) as items,
            SUM(qty) as total_qty,
            SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE type != '' AND type != '-'
        GROUP BY type
        ORDER BY total_price DESC
    ")->fetchAll();
    jsonResponse(['data' => $rows]);
}

function handleReportIdolDetail(PDO $pdo): void
{
    $idol = $_GET['idol'] ?? '';
    if ($idol === '') {
        jsonResponse(['error' => 'idol is required'], 400);
    }

    // Breakdown by type
    $stmt = $pdo->prepare("
        SELECT
            type,
            COUNT(*) as items,
            SUM(qty) as total_qty,
            SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE idol = :idol AND type != '' AND type != '-'
        GROUP BY type
        ORDER BY total_price DESC
    ");
    $stmt->execute([':idol' => $idol]);
    $byType = $stmt->fetchAll();

    // Breakdown by month
    $stmt2 = $pdo->prepare("
        SELECT
            strftime('%Y-%m', order_date) as month,
            COUNT(*) as items,
            SUM(qty) as total_qty,
            SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE idol = :idol AND order_date != ''
        GROUP BY month
        ORDER BY month
    ");
    $stmt2->execute([':idol' => $idol]);
    $byMonth = $stmt2->fetchAll();

    jsonResponse(['by_type' => $byType, 'by_month' => $byMonth]);
}

function handleReportByGroup(PDO $pdo): void
{
    // Get all entities with their hierarchy
    $entities = $pdo->query("
        SELECT e.id, e.name, e.category, e.parent_id,
               p.name as parent_name, p.category as parent_category
        FROM idol_entities e
        LEFT JOIN idol_entities p ON e.parent_id = p.id
        ORDER BY e.category, e.sort_order
    ")->fetchAll();

    // Build lookup: entity name -> list of all matching idol names (self + children recursively)
    $entityById = [];
    $childrenOf = [];
    foreach ($entities as $e) {
        $entityById[$e['id']] = $e;
        $pid = $e['parent_id'] ?? 'root';
        $childrenOf[$pid][] = $e;
    }

    // For each entity, collect all idol names that should be summed
    // (the entity name itself + all descendant names)
    function collectNames(array $entity, array &$childrenOf, array &$entityById): array {
        $names = [$entity['name']];
        $eid = $entity['id'];
        if (isset($childrenOf[$eid])) {
            foreach ($childrenOf[$eid] as $child) {
                $names = array_merge($names, collectNames($child, $childrenOf, $entityById));
            }
        }
        return $names;
    }

    // Get spending per idol name
    $spendingRaw = $pdo->query("
        SELECT idol, COUNT(*) as items, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items WHERE idol != '' AND idol != '-'
        GROUP BY idol
    ")->fetchAll();

    $spendByName = [];
    foreach ($spendingRaw as $r) {
        $spendByName[$r['idol']] = $r;
    }

    // Build result per group-level entity (groups + companies without group)
    $result = [];
    foreach ($entities as $e) {
        if ($e['category'] === 'group' || $e['category'] === 'unit') {
            $names = collectNames($e, $childrenOf, $entityById);
            $items = 0; $qty = 0; $price = 0;
            foreach ($names as $n) {
                if (isset($spendByName[$n])) {
                    $items += (int) $spendByName[$n]['items'];
                    $qty += (int) $spendByName[$n]['total_qty'];
                    $price += (float) $spendByName[$n]['total_price'];
                }
            }
            if ($price > 0) {
                $result[] = [
                    'name' => $e['name'],
                    'category' => $e['category'],
                    'parent' => $e['parent_name'],
                    'items' => $items,
                    'total_qty' => $qty,
                    'total_price' => $price,
                    'members' => array_values(array_filter($names, fn($n) => $n !== $e['name'])),
                ];
            }
        }
    }

    // Add company-level direct members (not in any group)
    foreach ($entities as $e) {
        if ($e['category'] === 'company') {
            // Direct member children of company (not group/unit children)
            $directMembers = [];
            if (isset($childrenOf[$e['id']])) {
                foreach ($childrenOf[$e['id']] as $child) {
                    if ($child['category'] === 'member') {
                        $directMembers[] = $child['name'];
                    }
                }
            }
            if (!empty($directMembers)) {
                $items = 0; $qty = 0; $price = 0;
                foreach ($directMembers as $n) {
                    if (isset($spendByName[$n])) {
                        $items += (int) $spendByName[$n]['items'];
                        $qty += (int) $spendByName[$n]['total_qty'];
                        $price += (float) $spendByName[$n]['total_price'];
                    }
                }
                if ($price > 0) {
                    $result[] = [
                        'name' => $e['name'] . ' (Solo)',
                        'category' => 'solo',
                        'parent' => $e['name'],
                        'items' => $items,
                        'total_qty' => $qty,
                        'total_price' => $price,
                        'members' => $directMembers,
                    ];
                }
            }
        }
    }

    // Sort by total_price desc
    usort($result, fn($a, $b) => $b['total_price'] <=> $a['total_price']);

    jsonResponse(['data' => $result]);
}

function handleReportByCompany(PDO $pdo): void
{
    $entities = $pdo->query("SELECT id, name, category, parent_id FROM idol_entities ORDER BY sort_order")->fetchAll();

    $entityById = [];
    $childrenOf = [];
    foreach ($entities as $e) {
        $entityById[$e['id']] = $e;
        $pid = $e['parent_id'] ?? 'root';
        $childrenOf[$pid][] = $e;
    }

    // Collect all descendant names for a given entity
    $collectAll = function (array $entity) use (&$collectAll, &$childrenOf): array {
        $names = [$entity['name']];
        if (isset($childrenOf[$entity['id']])) {
            foreach ($childrenOf[$entity['id']] as $child) {
                $names = array_merge($names, $collectAll($child));
            }
        }
        return $names;
    };

    // Get spending per idol name
    $spendingRaw = $pdo->query("
        SELECT idol, COUNT(*) as items, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items WHERE idol != '' AND idol != '-'
        GROUP BY idol
    ")->fetchAll();
    $spendByName = [];
    foreach ($spendingRaw as $r) {
        $spendByName[$r['idol']] = $r;
    }

    // Build per-company aggregation
    $result = [];
    foreach ($entities as $e) {
        if ($e['category'] !== 'company') continue;

        $names = $collectAll($e);
        $items = 0; $qty = 0; $price = 0;
        foreach ($names as $n) {
            if (isset($spendByName[$n])) {
                $items += (int) $spendByName[$n]['items'];
                $qty += (int) $spendByName[$n]['total_qty'];
                $price += (float) $spendByName[$n]['total_price'];
            }
        }

        // Also collect groups under this company
        $groups = [];
        if (isset($childrenOf[$e['id']])) {
            foreach ($childrenOf[$e['id']] as $child) {
                if ($child['category'] === 'group' || $child['category'] === 'unit') {
                    $cNames = $collectAll($child);
                    $gItems = 0; $gQty = 0; $gPrice = 0;
                    foreach ($cNames as $n) {
                        if (isset($spendByName[$n])) {
                            $gItems += (int) $spendByName[$n]['items'];
                            $gQty += (int) $spendByName[$n]['total_qty'];
                            $gPrice += (float) $spendByName[$n]['total_price'];
                        }
                    }
                    if ($gPrice > 0) {
                        $groups[] = [
                            'name' => $child['name'],
                            'category' => $child['category'],
                            'items' => $gItems,
                            'total_qty' => $gQty,
                            'total_price' => $gPrice,
                        ];
                    }
                }
            }
            usort($groups, fn($a, $b) => $b['total_price'] <=> $a['total_price']);
        }

        if ($price > 0) {
            $result[] = [
                'name' => $e['name'],
                'items' => $items,
                'total_qty' => $qty,
                'total_price' => $price,
                'groups' => $groups,
            ];
        }
    }

    usort($result, fn($a, $b) => $b['total_price'] <=> $a['total_price']);
    jsonResponse(['data' => $result]);
}

function handleIdolEntitiesTree(PDO $pdo): void
{
    $entities = $pdo->query("
        SELECT e.*, p.name as parent_name
        FROM idol_entities e
        LEFT JOIN idol_entities p ON e.parent_id = p.id
        ORDER BY e.sort_order, e.name
    ")->fetchAll();

    // Also get spending stats per entity name
    $stats = $pdo->query("
        SELECT idol as name, COUNT(*) as items, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items WHERE idol != '' AND idol != '-'
        GROUP BY idol
    ")->fetchAll();
    $statsMap = [];
    foreach ($stats as $s) {
        $statsMap[$s['name']] = $s;
    }

    // Attach stats
    foreach ($entities as &$e) {
        $s = $statsMap[$e['name']] ?? null;
        $e['items_count'] = $s ? (int) $s['items'] : 0;
        $e['total_qty'] = $s ? (int) $s['total_qty'] : 0;
        $e['total_price'] = $s ? (float) $s['total_price'] : 0;
    }
    unset($e);

    // Get all parents for dropdowns
    $parents = $pdo->query("SELECT id, name, category FROM idol_entities WHERE category IN ('company','group','unit') ORDER BY category, sort_order, name")->fetchAll();

    jsonResponse(['entities' => $entities, 'parents' => $parents]);
}

function handleIdolEntitySave(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'member');
    $parentId = ($_POST['parent_id'] ?? '') !== '' ? (int) $_POST['parent_id'] : null;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') {
        jsonResponse(['error' => 'Name is required'], 400);
    }
    if (!in_array($category, ['company', 'group', 'unit', 'member'], true)) {
        jsonResponse(['error' => 'Invalid category'], 400);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE idol_entities SET name = :name, category = :category, parent_id = :parent_id, sort_order = :sort WHERE id = :id");
        $stmt->execute([':name' => $name, ':category' => $category, ':parent_id' => $parentId, ':sort' => $sortOrder, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO idol_entities (name, category, parent_id, sort_order) VALUES (:name, :category, :parent_id, :sort)");
        $stmt->execute([':name' => $name, ':category' => $category, ':parent_id' => $parentId, ':sort' => $sortOrder]);
        $id = (int) $pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'id' => $id]);
}

function handleIdolEntityDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    // Move children to no parent
    $pdo->prepare("UPDATE idol_entities SET parent_id = NULL WHERE parent_id = :id")->execute([':id' => $id]);
    $pdo->prepare("DELETE FROM idol_entities WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

function handleTypeList(PDO $pdo): void
{
    // Single query: LEFT JOIN aggregation from items
    $types = $pdo->query("
        SELECT tc.*,
               COALESCE(u.cnt, 0)         as items_count,
               COALESCE(u.total_qty, 0)   as total_qty,
               COALESCE(u.total_price, 0) as total_price
        FROM type_categories tc
        LEFT JOIN (
            SELECT type, COUNT(*) as cnt, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
            FROM items WHERE type != ''
            GROUP BY type
        ) u ON u.type = tc.name
        ORDER BY tc.sort_order, tc.name
    ")->fetchAll();

    // Unmapped: types in items not present in type_categories
    $unmapped = $pdo->query("
        SELECT DISTINCT i.type
        FROM items i
        LEFT JOIN type_categories tc ON tc.name = i.type
        WHERE i.type != '' AND i.type != '-' AND tc.id IS NULL
        ORDER BY i.type
    ")->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse(['types' => $types, 'unmapped' => $unmapped]);
}

function handleTypeByMembers(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT
            i.type,
            i.idol AS member_name,
            m.category AS member_cat,
            p.name AS parent_name,
            p.category AS parent_cat,
            gp.name AS gparent_name,
            gp.category AS gparent_cat,
            COUNT(*) AS items_count,
            SUM(i.qty) AS total_qty,
            SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        LEFT JOIN idol_entities m ON m.name = i.idol
        LEFT JOIN idol_entities p ON m.parent_id = p.id
        LEFT JOIN idol_entities gp ON p.parent_id = gp.id
        WHERE i.type != '' AND i.idol != '' AND i.idol != '-'
        GROUP BY i.type, i.idol
        ORDER BY i.type, total_price DESC
    ")->fetchAll();

    $byType = [];
    foreach ($rows as $r) {
        $type = $r['type'];
        if (!isset($byType[$type])) {
            $byType[$type] = [];
        }

        $group = null;
        $company = null;
        if ($r['gparent_cat'] === 'company') {
            $company = $r['gparent_name'];
            $group = $r['parent_name'];
        } elseif ($r['parent_cat'] === 'company') {
            $company = $r['parent_name'];
        } elseif ($r['parent_cat'] === 'group' || $r['parent_cat'] === 'unit') {
            $group = $r['parent_name'];
        }

        $byType[$type][] = [
            'member'      => $r['member_name'],
            'group'       => $group,
            'company'     => $company,
            'items_count' => (int) $r['items_count'],
            'total_qty'   => (int) $r['total_qty'],
            'total_price' => (float) $r['total_price'],
        ];
    }

    jsonResponse(['by_type' => $byType]);
}

function handleReportTypeDetail(PDO $pdo): void
{
    $type = trim($_GET['type'] ?? '');
    if ($type === '') {
        jsonResponse(['error' => 'type is required'], 400);
    }

    $rows = $pdo->prepare("
        SELECT
            i.idol AS member_name,
            m.category AS member_cat,
            p.name AS parent_name,
            p.category AS parent_cat,
            gp.name AS gparent_name,
            gp.category AS gparent_cat,
            COUNT(*) AS items_count,
            SUM(i.qty) AS total_qty,
            SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        LEFT JOIN idol_entities m ON m.name = i.idol
        LEFT JOIN idol_entities p ON m.parent_id = p.id
        LEFT JOIN idol_entities gp ON p.parent_id = gp.id
        WHERE i.type = :type AND i.idol != '' AND i.idol != '-'
        GROUP BY i.idol
        ORDER BY total_price DESC
    ");
    $rows->execute([':type' => $type]);
    $rows = $rows->fetchAll();

    $members = [];
    foreach ($rows as $r) {
        $group = null;
        $company = null;
        if ($r['gparent_cat'] === 'company') {
            $company = $r['gparent_name'];
            $group = $r['parent_name'];
        } elseif ($r['parent_cat'] === 'company') {
            $company = $r['parent_name'];
        } elseif ($r['parent_cat'] === 'group' || $r['parent_cat'] === 'unit') {
            $group = $r['parent_name'];
        }

        $members[] = [
            'member'      => $r['member_name'],
            'group'       => $group,
            'company'     => $company,
            'items_count' => (int) $r['items_count'],
            'total_qty'   => (int) $r['total_qty'],
            'total_price' => (float) $r['total_price'],
        ];
    }

    jsonResponse(['members' => $members]);
}

function handleTypeSave(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') {
        jsonResponse(['error' => 'Name is required'], 400);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE type_categories SET name = :name, description = :desc, sort_order = :sort WHERE id = :id");
        $stmt->execute([':name' => $name, ':desc' => $description, ':sort' => $sortOrder, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO type_categories (name, description, sort_order) VALUES (:name, :desc, :sort)");
        $stmt->execute([':name' => $name, ':desc' => $description, ':sort' => $sortOrder]);
        $id = (int) $pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'id' => $id]);
}

function handleTypeDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    $pdo->prepare("DELETE FROM type_categories WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

// --- Backup/Restore ---
function handleBackupList(): void
{
    requireAdmin();
    $files = glob(BACKUP_DIR . '/*.sqlite');
    $backups = [];
    foreach ($files as $f) {
        $backups[] = [
            'filename' => basename($f),
            'size' => filesize($f),
            'created' => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
    usort($backups, fn($a, $b) => $b['created'] <=> $a['created']);
    jsonResponse(['backups' => $backups]);
}

function handleBackupCreate(): void
{
    requireAdmin();
    $label = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($_POST['label'] ?? ''));
    $timestamp = date('Ymd_His');
    $filename = $label ? "backup_{$timestamp}_{$label}.sqlite" : "backup_{$timestamp}.sqlite";
    $dest = BACKUP_DIR . '/' . $filename;

    if (!copy(DB_PATH, $dest)) {
        jsonResponse(['error' => 'Failed to create backup'], 500);
    }
    jsonResponse(['success' => true, 'filename' => $filename, 'size' => filesize($dest)]);
}

function handleBackupRestore(): void
{
    requireAdmin();
    $filename = basename(trim($_POST['filename'] ?? ''));
    if ($filename === '' || !file_exists(BACKUP_DIR . '/' . $filename)) {
        jsonResponse(['error' => 'Backup file not found'], 404);
    }

    // Create auto-backup before restore
    $autoBackup = BACKUP_DIR . '/pre_restore_' . date('Ymd_His') . '.sqlite';
    copy(DB_PATH, $autoBackup);

    // Close existing connection
    $pdo = getDB();
    $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    unset($pdo);

    if (!copy(BACKUP_DIR . '/' . $filename, DB_PATH)) {
        jsonResponse(['error' => 'Failed to restore backup'], 500);
    }
    jsonResponse(['success' => true, 'message' => "Restored from {$filename}. Auto-backup created."]);
}

function handleBackupDelete(): void
{
    requireAdmin();
    $filename = basename(trim($_POST['filename'] ?? ''));
    if ($filename === '' || !file_exists(BACKUP_DIR . '/' . $filename)) {
        jsonResponse(['error' => 'Backup file not found'], 404);
    }
    unlink(BACKUP_DIR . '/' . $filename);
    jsonResponse(['success' => true]);
}

function handleBackupDownload(): void
{
    requireAdmin();
    $filename = basename(trim($_GET['filename'] ?? ''));
    if ($filename === '' || !str_ends_with($filename, '.sqlite')) {
        jsonResponse(['error' => 'Invalid file type'], 400);
    }
    $filepath = BACKUP_DIR . '/' . $filename;
    if (!file_exists($filepath)) {
        jsonResponse(['error' => 'Backup file not found'], 404);
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    exit;
}

function getInputData(): array
{
    return [
        ':order_date' => trim($_POST['order_date'] ?? ''),
        ':event_date' => trim($_POST['event_date'] ?? ''),
        ':title' => trim($_POST['title'] ?? ''),
        ':idol' => trim($_POST['idol'] ?? ''),
        ':type' => trim($_POST['type'] ?? ''),
        ':price_per_qty' => (float) ($_POST['price_per_qty'] ?? 0),
        ':qty' => (int) ($_POST['qty'] ?? 1),
    ];
}
