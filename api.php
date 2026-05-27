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
        'report_group_detail' => handleReportGroupDetail($pdo),
        'idol_entities_tree' => handleIdolEntitiesTree($pdo),
        'idol_entity_save' => handleIdolEntitySave($pdo),
        'idol_entity_delete' => handleIdolEntityDelete($pdo),
        'idol_search' => handleIdolSearch($pdo),
        'idol_resolve_name' => handleIdolResolveName($pdo),
        'item_remap' => handleItemRemap($pdo),
        'item_bulk_remap' => handleItemBulkRemap($pdo),
        'ambiguous_list' => handleAmbiguousList($pdo),
        'membership_list' => handleMembershipList($pdo),
        'membership_save' => handleMembershipSave($pdo),
        'membership_delete' => handleMembershipDelete($pdo),
        'membership_move' => handleMembershipMove($pdo),
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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'])) {
            jsonResponse(['error' => 'Invalid date_from format'], 400);
        }
        $where[] = 'order_date >= :date_from';
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'])) {
            jsonResponse(['error' => 'Invalid date_to format'], 400);
        }
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
    $idolResolved = resolveItemIdol($pdo, $data);
    if (!$idolResolved['ok']) return;     // handler already sent response
    $data[':idol_id'] = $idolResolved['idol_id'];

    $stmt = $pdo->prepare('
        INSERT INTO items (order_date, event_date, title, idol, type, price_per_qty, qty, idol_id)
        VALUES (:order_date, :event_date, :title, :idol, :type, :price_per_qty, :qty, :idol_id)
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
    $idolResolved = resolveItemIdol($pdo, $data);
    if (!$idolResolved['ok']) return;
    $data[':idol_id'] = $idolResolved['idol_id'];
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
            idol_id = :idol_id,
            updated_at = datetime('now','localtime')
        WHERE id = :id
    ");
    $stmt->execute($data);
    jsonResponse(['success' => true]);
}

/**
 * Hybrid idol resolution for item create/update.
 * - If `idol_id` is provided in $_POST, use it (and overwrite :idol text with entity name).
 * - Else if `idol` text resolves unambiguously to one entity, use that id.
 * - Else if ambiguous → respond 409 with candidates (returns ok=false).
 * - Else (no match or empty name) → idol_id stays null.
 *
 * Mutates $data[':idol'] when an explicit idol_id was provided.
 */
function resolveItemIdol(PDO $pdo, array &$data): array
{
    $idolId = isset($_POST['idol_id']) && $_POST['idol_id'] !== '' ? (int) $_POST['idol_id'] : 0;

    if ($idolId > 0) {
        $stmt = $pdo->prepare("SELECT name FROM idol_entities WHERE id = :id AND category = 'member'");
        $stmt->execute([':id' => $idolId]);
        $row = $stmt->fetch();
        if (!$row) {
            jsonResponse(['error' => "idol_id {$idolId} does not reference a member entity"], 400);
            return ['ok' => false, 'idol_id' => null];
        }
        $data[':idol'] = $row['name'];     // snapshot the canonical name
        return ['ok' => true, 'idol_id' => $idolId];
    }

    $idolText = $data[':idol'] ?? '';
    if ($idolText === '' || $idolText === '-') {
        return ['ok' => true, 'idol_id' => null];
    }

    $r = resolveIdolByName($pdo, $idolText);
    if ($r['ambiguous']) {
        jsonResponse([
            'error'      => 'Ambiguous idol name — please specify idol_id',
            'name'       => $idolText,
            'candidates' => $r['candidates'],
        ], 409);
        return ['ok' => false, 'idol_id' => null];
    }
    return ['ok' => true, 'idol_id' => $r['id']];
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

    // Type breakdown for this month
    $stmtType = $pdo->prepare("
        SELECT type, COUNT(*) as items, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE strftime('%Y-%m', order_date) = :month AND order_date != '' AND type != '' AND type != '-'
        GROUP BY type ORDER BY total_price DESC
    ");
    $stmtType->execute([':month' => $month]);
    $byType = $stmtType->fetchAll();

    // Idol breakdown for this month (mapped via idol_id + unmapped fallback)
    $stmtIdol = $pdo->prepare("
        SELECT
            i.idol_id                                   AS idol_id,
            COALESCE(m.name, i.idol)                    AS idol,
            COALESCE(m.display_hint, '')                AS display_hint,
            COUNT(*)                                    AS items,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price
        FROM items i
        LEFT JOIN idol_entities m ON m.id = i.idol_id AND m.category = 'member'
        WHERE strftime('%Y-%m', i.order_date) = :month
          AND i.order_date != '' AND i.idol != '' AND i.idol != '-'
        GROUP BY COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY total_price DESC
    ");
    $stmtIdol->execute([':month' => $month]);
    $byIdol = array_map(fn($r) => enrichIdolRow($r), $stmtIdol->fetchAll());

    jsonResponse(['data' => $rows, 'months' => $months, 'by_type' => $byType, 'by_idol' => $byIdol]);
}

/**
 * Normalize an idol-aggregate row for API response:
 *   - cast types
 *   - add 'display' = formatIdolDisplay(...)
 *   - convert idol_id to int or null
 */
function enrichIdolRow(array $r): array
{
    $r['idol_id']     = isset($r['idol_id']) && $r['idol_id'] !== null ? (int) $r['idol_id'] : null;
    $r['display']     = formatIdolDisplay(['name' => $r['idol'] ?? '', 'display_hint' => $r['display_hint'] ?? '']);
    $r['items']       = (int) ($r['items'] ?? 0);
    $r['total_qty']   = (int) ($r['total_qty'] ?? 0);
    $r['total_price'] = (float) ($r['total_price'] ?? 0);
    return $r;
}

function handleReportIdol(PDO $pdo): void
{
    // Mapped (idol_id set) + unmapped (idol_id NULL) in one pass.
    // For unmapped rows we group by raw idol text so they appear as "(Unassigned)" entries.
    $rows = $pdo->query("
        SELECT
            i.idol_id                                   AS idol_id,
            COALESCE(m.name, i.idol)                    AS idol,
            COALESCE(m.display_hint, '')                AS display_hint,
            COUNT(*)                                    AS items,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price
        FROM items i
        LEFT JOIN idol_entities m ON m.id = i.idol_id AND m.category = 'member'
        WHERE i.idol != '' AND i.idol != '-'
        GROUP BY COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY total_price DESC
    ")->fetchAll();

    $rows = array_map(fn($r) => enrichIdolRow($r), $rows);
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
    // Accept idol_id (preferred) or idol name (legacy).
    $idolId = isset($_GET['idol_id']) && $_GET['idol_id'] !== '' ? (int) $_GET['idol_id'] : 0;
    $idol   = $_GET['idol'] ?? '';

    if ($idolId === 0 && $idol === '') {
        jsonResponse(['error' => 'idol or idol_id is required'], 400);
    }

    if ($idolId > 0) {
        $filterSql    = 'i.idol_id = :idol_id';
        $filterParams = [':idol_id' => $idolId];
    } else {
        // Match by text against items.idol OR by entity name → idol_id of items
        $filterSql    = '(i.idol = :idol OR i.idol_id IN (SELECT id FROM idol_entities WHERE name = :idol AND category = \'member\'))';
        $filterParams = [':idol' => $idol];
    }

    // Breakdown by type
    $stmt = $pdo->prepare("
        SELECT
            i.type,
            COUNT(*)                       AS items,
            SUM(i.qty)                     AS total_qty,
            SUM(i.price_per_qty * i.qty)   AS total_price
        FROM items i
        WHERE {$filterSql} AND i.type != '' AND i.type != '-'
        GROUP BY i.type
        ORDER BY total_price DESC
    ");
    $stmt->execute($filterParams);
    $byType = $stmt->fetchAll();

    // Breakdown by month
    $stmt2 = $pdo->prepare("
        SELECT
            strftime('%Y-%m', i.order_date) AS month,
            COUNT(*)                        AS items,
            SUM(i.qty)                      AS total_qty,
            SUM(i.price_per_qty * i.qty)    AS total_price
        FROM items i
        WHERE {$filterSql} AND i.order_date != ''
        GROUP BY month
        ORDER BY month
    ");
    $stmt2->execute($filterParams);
    $byMonth = $stmt2->fetchAll();

    jsonResponse(['by_type' => $byType, 'by_month' => $byMonth]);
}

function handleReportByGroup(PDO $pdo): void
{
    // Aggregate items by primary group (resolved via idol_memberships at item.order_date).
    // Items without idol_id, without matching membership, or matching a non-group entity
    // are excluded — they appear in the unmapped/ambiguous panels instead.
    $rows = $pdo->query("
        SELECT
            g.id                                          AS group_id,
            g.name                                        AS group_name,
            g.category                                    AS group_category,
            c.name                                        AS company_name,
            COUNT(*)                                      AS items,
            SUM(i.qty)                                    AS total_qty,
            SUM(i.price_per_qty * i.qty)                  AS total_price,
            GROUP_CONCAT(DISTINCT m.name)                 AS member_names
        FROM items i
        JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        JOIN idol_memberships ms
            ON ms.member_id = m.id
            AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities g
            ON g.id = ms.group_id AND g.category IN ('group','unit')
        LEFT JOIN idol_entities c
            ON c.id = g.parent_id AND c.category = 'company'
        GROUP BY g.id
        ORDER BY total_price DESC
    ")->fetchAll();

    $result = array_map(fn($r) => [
        'group_id'    => (int) $r['group_id'],
        'name'        => $r['group_name'],
        'category'    => $r['group_category'],
        'parent'      => $r['company_name'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
        'members'     => $r['member_names'] !== null ? explode(',', $r['member_names']) : [],
    ], $rows);

    jsonResponse(['data' => $result]);
}

/**
 * Drill-down for a group: list members + sub-units (non-primary memberships) under it.
 * Accepts ?group_id=N (preferred) or ?group=name.
 */
function handleReportGroupDetail(PDO $pdo): void
{
    $groupId = isset($_GET['group_id']) && $_GET['group_id'] !== '' ? (int) $_GET['group_id'] : 0;
    $group   = trim($_GET['group'] ?? '');

    if ($groupId === 0 && $group === '') {
        jsonResponse(['error' => 'group or group_id is required'], 400);
    }
    if ($groupId === 0) {
        $r = $pdo->prepare("SELECT id FROM idol_entities WHERE name = :n AND category IN ('group','unit') LIMIT 1");
        $r->execute([':n' => $group]);
        $groupId = (int) ($r->fetchColumn() ?: 0);
        if ($groupId === 0) jsonResponse(['error' => 'Group not found'], 404);
    }

    // Members under this group (primary memberships)
    $members = $pdo->prepare("
        SELECT
            m.id                              AS idol_id,
            m.name                            AS idol,
            m.display_hint                    AS display_hint,
            COUNT(i.id)                       AS items,
            COALESCE(SUM(i.qty), 0)           AS total_qty,
            COALESCE(SUM(i.price_per_qty * i.qty), 0) AS total_price
        FROM idol_memberships ms
        JOIN idol_entities m ON m.id = ms.member_id AND m.category = 'member'
        LEFT JOIN items i
            ON i.idol_id = m.id
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        WHERE ms.group_id = :g AND ms.is_primary = 1
        GROUP BY m.id
        ORDER BY total_price DESC
    ");
    $members->execute([':g' => $groupId]);
    $memberRows = array_map(fn($r) => enrichIdolRow($r), $members->fetchAll());

    // Sub-units / project groups under this entity (non-primary memberships)
    $subunits = $pdo->prepare("
        SELECT
            ms.id                             AS membership_id,
            m.id                              AS idol_id,
            m.name                            AS idol,
            m.display_hint                    AS display_hint,
            ms.start_date,
            ms.end_date,
            ms.is_primary
        FROM idol_memberships ms
        JOIN idol_entities m ON m.id = ms.member_id AND m.category = 'member'
        WHERE ms.group_id = :g AND ms.is_primary = 0
        ORDER BY m.name
    ");
    $subunits->execute([':g' => $groupId]);

    // Monthly breakdown
    $monthly = $pdo->prepare("
        SELECT
            strftime('%Y-%m', i.order_date) AS month,
            COUNT(*) AS items,
            SUM(i.qty) AS total_qty,
            SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        JOIN idol_memberships ms
            ON ms.member_id = i.idol_id
            AND ms.group_id = :g
            AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= i.order_date)
            AND (ms.end_date   IS NULL OR ms.end_date   >= i.order_date)
        WHERE i.order_date != ''
        GROUP BY month
        ORDER BY month
    ");
    $monthly->execute([':g' => $groupId]);

    jsonResponse([
        'members'    => $memberRows,
        'sub_units'  => $subunits->fetchAll(),
        'by_month'   => $monthly->fetchAll(),
    ]);
}

function handleReportByCompany(PDO $pdo): void
{
    // Aggregate at the company level — same membership-aware join as handleReportByGroup,
    // but rolled up one more level via group.parent_id → company.
    $rows = $pdo->query("
        SELECT
            c.id                                          AS company_id,
            c.name                                        AS company_name,
            g.id                                          AS group_id,
            g.name                                        AS group_name,
            g.category                                    AS group_category,
            COUNT(*)                                      AS items,
            SUM(i.qty)                                    AS total_qty,
            SUM(i.price_per_qty * i.qty)                  AS total_price
        FROM items i
        JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        JOIN idol_memberships ms
            ON ms.member_id = m.id
            AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities g
            ON g.id = ms.group_id AND g.category IN ('group','unit')
        JOIN idol_entities c
            ON c.id = g.parent_id AND c.category = 'company'
        GROUP BY c.id, g.id
        ORDER BY c.name
    ")->fetchAll();

    // Pivot: company → list of groups + totals
    $byCompany = [];
    foreach ($rows as $r) {
        $cid = (int) $r['company_id'];
        if (!isset($byCompany[$cid])) {
            $byCompany[$cid] = [
                'name'        => $r['company_name'],
                'items'       => 0,
                'total_qty'   => 0,
                'total_price' => 0.0,
                'groups'      => [],
            ];
        }
        $byCompany[$cid]['items']       += (int) $r['items'];
        $byCompany[$cid]['total_qty']   += (int) $r['total_qty'];
        $byCompany[$cid]['total_price'] += (float) $r['total_price'];
        $byCompany[$cid]['groups'][] = [
            'name'        => $r['group_name'],
            'category'    => $r['group_category'],
            'items'       => (int) $r['items'],
            'total_qty'   => (int) $r['total_qty'],
            'total_price' => (float) $r['total_price'],
        ];
    }

    $result = array_values($byCompany);
    foreach ($result as &$c) {
        usort($c['groups'], fn($a, $b) => $b['total_price'] <=> $a['total_price']);
    }
    unset($c);
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

    // Stats per member entity (via idol_id — handles duplicate names correctly).
    $memberStats = $pdo->query("
        SELECT idol_id, COUNT(*) as items, SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE idol_id IS NOT NULL
        GROUP BY idol_id
    ")->fetchAll();
    $statsByMember = [];
    foreach ($memberStats as $s) {
        $statsByMember[(int) $s['idol_id']] = $s;
    }

    // Membership count per member (for tree icon)
    $mbCounts = $pdo->query("
        SELECT member_id, COUNT(*) as mb_count FROM idol_memberships GROUP BY member_id
    ")->fetchAll();
    $mbCountByMember = [];
    foreach ($mbCounts as $r) {
        $mbCountByMember[(int) $r['member_id']] = (int) $r['mb_count'];
    }

    // For groups/units/companies: roll up stats from descendant members (via membership graph).
    // We compute member→group/company rollups by scanning items.
    $groupSums = $pdo->query("
        SELECT g.id AS group_id,
               COUNT(*) AS items, SUM(i.qty) AS total_qty, SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        JOIN idol_memberships ms ON ms.member_id = i.idol_id AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities g ON g.id = ms.group_id
        GROUP BY g.id
    ")->fetchAll();
    $statsByGroup = [];
    foreach ($groupSums as $g) {
        $statsByGroup[(int) $g['group_id']] = $g;
    }

    $companySums = $pdo->query("
        SELECT c.id AS company_id,
               COUNT(*) AS items, SUM(i.qty) AS total_qty, SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        JOIN idol_memberships ms ON ms.member_id = i.idol_id AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities g ON g.id = ms.group_id
        JOIN idol_entities c ON c.id = g.parent_id AND c.category = 'company'
        GROUP BY c.id
    ")->fetchAll();
    $statsByCompany = [];
    foreach ($companySums as $c) {
        $statsByCompany[(int) $c['company_id']] = $c;
    }

    // Attach stats
    foreach ($entities as &$e) {
        $eid = (int) $e['id'];
        $s = null;
        if ($e['category'] === 'member') {
            $s = $statsByMember[$eid] ?? null;
            $e['membership_count'] = $mbCountByMember[$eid] ?? 0;
        } elseif ($e['category'] === 'group' || $e['category'] === 'unit') {
            $s = $statsByGroup[$eid] ?? null;
        } elseif ($e['category'] === 'company') {
            $s = $statsByCompany[$eid] ?? null;
        }
        $e['items_count'] = $s ? (int) $s['items'] : 0;
        $e['total_qty']   = $s ? (int) $s['total_qty'] : 0;
        $e['total_price'] = $s ? (float) $s['total_price'] : 0;
        $e['display']     = formatIdolDisplay($e);
    }
    unset($e);

    $parents = $pdo->query("SELECT id, name, category, display_hint FROM idol_entities WHERE category IN ('company','group','unit') ORDER BY category, sort_order, name")->fetchAll();

    // Ambiguous mappings — distinct items.idol values where multiple member entities exist
    $ambiguous = $pdo->query("
        SELECT i.idol AS name, COUNT(DISTINCT i.id) AS items_count
        FROM items i
        WHERE i.idol_id IS NULL AND i.idol != '' AND i.idol != '-'
          AND (SELECT COUNT(*) FROM idol_entities e WHERE e.name = i.idol AND e.category = 'member') > 1
        GROUP BY i.idol
    ")->fetchAll();

    jsonResponse([
        'entities'         => $entities,
        'parents'          => $parents,
        'ambiguous_count'  => count($ambiguous),
    ]);
}

function handleIdolEntitySave(PDO $pdo): void
{
    $id          = (int) ($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? 'member');
    $parentId    = ($_POST['parent_id'] ?? '') !== '' ? (int) $_POST['parent_id'] : null;
    $sortOrder   = (int) ($_POST['sort_order'] ?? 0);
    $displayHint = trim($_POST['display_hint'] ?? '');

    if ($name === '') {
        jsonResponse(['error' => 'Name is required'], 400);
    }
    if (!in_array($category, ['company', 'group', 'unit', 'member'], true)) {
        jsonResponse(['error' => 'Invalid category'], 400);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE idol_entities SET name = :name, category = :category, parent_id = :parent_id, sort_order = :sort, display_hint = :hint WHERE id = :id");
        $stmt->execute([':name' => $name, ':category' => $category, ':parent_id' => $parentId, ':sort' => $sortOrder, ':hint' => $displayHint, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO idol_entities (name, category, parent_id, sort_order, display_hint) VALUES (:name, :category, :parent_id, :sort, :hint)");
        $stmt->execute([':name' => $name, ':category' => $category, ':parent_id' => $parentId, ':sort' => $sortOrder, ':hint' => $displayHint]);
        $id = (int) $pdo->lastInsertId();
    }

    // For members with a parent group: ensure a default primary membership exists.
    if ($category === 'member' && $parentId !== null) {
        $check = $pdo->prepare("SELECT id FROM idol_memberships WHERE member_id = :m AND is_primary = 1 AND end_date IS NULL");
        $check->execute([':m' => $id]);
        if ($check->fetchColumn() === false) {
            $pdo->prepare("INSERT INTO idol_memberships (member_id, group_id, is_primary, note) VALUES (:m, :g, 1, 'auto-created with entity')")
                ->execute([':m' => $id, ':g' => $parentId]);
        }
    }

    // Auto-backfill items.idol_id for previously-unmapped items matching this name.
    $backfilled = ($category === 'member') ? autoBackfillIdolId($pdo, $id) : 0;

    jsonResponse(['success' => true, 'id' => $id, 'backfilled_items' => $backfilled]);
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
    // Resolve group/company per item via memberships at order_date.
    $rows = $pdo->query("
        SELECT
            i.type                                      AS type,
            COALESCE(m.name, i.idol)                    AS member_name,
            i.idol_id                                   AS idol_id,
            COALESCE(m.display_hint, '')                AS display_hint,
            g.name                                      AS group_name,
            g.category                                  AS group_category,
            c.name                                      AS company_name,
            COUNT(*)                                    AS items_count,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price
        FROM items i
        LEFT JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        LEFT JOIN idol_memberships ms
            ON ms.member_id = m.id AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        LEFT JOIN idol_entities g
            ON g.id = ms.group_id
        LEFT JOIN idol_entities c
            ON c.id = g.parent_id AND c.category = 'company'
        WHERE i.type != '' AND i.idol != '' AND i.idol != '-'
        GROUP BY i.type, COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY i.type, total_price DESC
    ")->fetchAll();

    $byType = [];
    foreach ($rows as $r) {
        $type = $r['type'];
        if (!isset($byType[$type])) $byType[$type] = [];

        $byType[$type][] = [
            'member'      => $r['member_name'],
            'idol_id'     => $r['idol_id'] !== null ? (int) $r['idol_id'] : null,
            'display'     => formatIdolDisplay(['name' => $r['member_name'], 'display_hint' => $r['display_hint']]),
            'group'       => $r['group_name'],
            'company'     => $r['company_name'],
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

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(m.name, i.idol)                    AS member_name,
            i.idol_id                                   AS idol_id,
            COALESCE(m.display_hint, '')                AS display_hint,
            g.name                                      AS group_name,
            c.name                                      AS company_name,
            COUNT(*)                                    AS items_count,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price
        FROM items i
        LEFT JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        LEFT JOIN idol_memberships ms
            ON ms.member_id = m.id AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        LEFT JOIN idol_entities g ON g.id = ms.group_id
        LEFT JOIN idol_entities c ON c.id = g.parent_id AND c.category = 'company'
        WHERE i.type = :type AND i.idol != '' AND i.idol != '-'
        GROUP BY COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY total_price DESC
    ");
    $stmt->execute([':type' => $type]);
    $rows = $stmt->fetchAll();

    $members = array_map(fn($r) => [
        'member'      => $r['member_name'],
        'idol_id'     => $r['idol_id'] !== null ? (int) $r['idol_id'] : null,
        'display'     => formatIdolDisplay(['name' => $r['member_name'], 'display_hint' => $r['display_hint']]),
        'group'       => $r['group_name'],
        'company'     => $r['company_name'],
        'items_count' => (int) $r['items_count'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $rows);

    // Monthly breakdown for this type
    $stmtMonth = $pdo->prepare("
        SELECT strftime('%Y-%m', order_date) as month, COUNT(*) as items,
               SUM(qty) as total_qty, SUM(price_per_qty * qty) as total_price
        FROM items
        WHERE type = :type AND order_date != ''
        GROUP BY month ORDER BY month
    ");
    $stmtMonth->execute([':type' => $type]);
    $byMonth = $stmtMonth->fetchAll();

    jsonResponse(['members' => $members, 'by_month' => $byMonth]);
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

// ─────────────────────────────────────────────────────────────────────────────
// v5 endpoints: idol search, item remap, ambiguous list, memberships
// ─────────────────────────────────────────────────────────────────────────────

function handleIdolSearch(PDO $pdo): void
{
    $q        = trim($_GET['q'] ?? '');
    $category = trim($_GET['category'] ?? 'member');
    if (!in_array($category, ['company', 'group', 'unit', 'member', 'any'], true)) {
        $category = 'member';
    }

    $where  = [];
    $params = [];
    if ($q !== '') {
        $where[] = 'name LIKE :q';
        $params[':q'] = '%' . $q . '%';
    }
    if ($category !== 'any') {
        $where[] = 'category = :cat';
        $params[':cat'] = $category;
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("
        SELECT id, name, category, display_hint, parent_id
        FROM idol_entities
        {$whereSql}
        ORDER BY name
        LIMIT 50
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
        $r['id']           = (int) $r['id'];
        $r['parent_id']    = $r['parent_id'] !== null ? (int) $r['parent_id'] : null;
        $r['display_hint'] = $r['display_hint'] ?? '';
        $r['display']      = formatIdolDisplay($r);
    }
    unset($r);

    jsonResponse(['data' => $rows]);
}

function handleIdolResolveName(PDO $pdo): void
{
    $name = trim($_GET['name'] ?? '');
    if ($name === '') {
        jsonResponse(['error' => 'name is required'], 400);
    }
    jsonResponse(resolveIdolByName($pdo, $name));
}

function handleItemRemap(PDO $pdo): void
{
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $idolId = isset($_POST['idol_id']) && $_POST['idol_id'] !== '' ? (int) $_POST['idol_id'] : null;
    if ($itemId === 0) {
        jsonResponse(['error' => 'item_id is required'], 400);
    }
    if ($idolId !== null) {
        $check = $pdo->prepare("SELECT name FROM idol_entities WHERE id = :id AND category = 'member'");
        $check->execute([':id' => $idolId]);
        $row = $check->fetch();
        if (!$row) {
            jsonResponse(['error' => 'idol_id does not reference a member entity'], 400);
        }
        $stmt = $pdo->prepare("UPDATE items SET idol_id = :iid, idol = :iname, updated_at = datetime('now','localtime') WHERE id = :id");
        $stmt->execute([':iid' => $idolId, ':iname' => $row['name'], ':id' => $itemId]);
    } else {
        $stmt = $pdo->prepare("UPDATE items SET idol_id = NULL, updated_at = datetime('now','localtime') WHERE id = :id");
        $stmt->execute([':id' => $itemId]);
    }
    jsonResponse(['success' => true]);
}

function handleItemBulkRemap(PDO $pdo): void
{
    $name     = trim($_POST['idol_name'] ?? '');
    $idolId   = (int) ($_POST['idol_id'] ?? 0);
    $dateFrom = trim($_POST['date_from'] ?? '');
    $dateTo   = trim($_POST['date_to'] ?? '');

    if ($name === '' || $idolId === 0) {
        jsonResponse(['error' => 'idol_name and idol_id are required'], 400);
    }
    if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        jsonResponse(['error' => 'Invalid date_from format'], 400);
    }
    if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        jsonResponse(['error' => 'Invalid date_to format'], 400);
    }

    $check = $pdo->prepare("SELECT name FROM idol_entities WHERE id = :id AND category = 'member'");
    $check->execute([':id' => $idolId]);
    $row = $check->fetch();
    if (!$row) {
        jsonResponse(['error' => 'idol_id does not reference a member entity'], 400);
    }

    $where  = ['idol = :name', 'idol_id IS NULL'];
    $params = [':iid' => $idolId, ':iname' => $row['name'], ':name' => $name];
    if ($dateFrom !== '') { $where[] = 'order_date >= :dfrom'; $params[':dfrom'] = $dateFrom; }
    if ($dateTo   !== '') { $where[] = 'order_date <= :dto';   $params[':dto']   = $dateTo;   }
    $whereSql = implode(' AND ', $where);

    $stmt = $pdo->prepare("UPDATE items SET idol_id = :iid, idol = :iname, updated_at = datetime('now','localtime') WHERE {$whereSql}");
    $stmt->execute($params);
    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
}

function handleAmbiguousList(PDO $pdo): void
{
    // For each distinct unmapped idol name, list candidate entities + item count.
    $rows = $pdo->query("
        SELECT i.idol AS name, COUNT(*) AS items_count
        FROM items i
        WHERE i.idol_id IS NULL AND i.idol != '' AND i.idol != '-'
          AND (SELECT COUNT(*) FROM idol_entities e WHERE e.name = i.idol AND e.category = 'member') > 1
        GROUP BY i.idol
        ORDER BY items_count DESC
    ")->fetchAll();

    $candStmt = $pdo->prepare("SELECT id, name, display_hint FROM idol_entities WHERE name = :n AND category = 'member' ORDER BY id");

    $result = [];
    foreach ($rows as $r) {
        $candStmt->execute([':n' => $r['name']]);
        $candidates = array_map(fn($c) => [
            'id'           => (int) $c['id'],
            'name'         => $c['name'],
            'display_hint' => $c['display_hint'] ?? '',
            'display'      => formatIdolDisplay($c),
        ], $candStmt->fetchAll());

        $result[] = [
            'name'        => $r['name'],
            'items_count' => (int) $r['items_count'],
            'candidates'  => $candidates,
        ];
    }

    jsonResponse(['data' => $result]);
}

// ─── Memberships ─────────────────────────────────────────────────────────────

function handleMembershipList(PDO $pdo): void
{
    $memberId = (int) ($_GET['member_id'] ?? 0);
    if ($memberId === 0) {
        jsonResponse(['error' => 'member_id is required'], 400);
    }
    $stmt = $pdo->prepare("
        SELECT ms.id, ms.member_id, ms.group_id, ms.start_date, ms.end_date,
               ms.is_primary, ms.note,
               g.name AS group_name, g.category AS group_category, g.display_hint AS group_display_hint
        FROM idol_memberships ms
        JOIN idol_entities g ON g.id = ms.group_id
        WHERE ms.member_id = :mid
        ORDER BY COALESCE(ms.start_date, '0000-00-00') ASC, ms.id ASC
    ");
    $stmt->execute([':mid' => $memberId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id']            = (int) $r['id'];
        $r['member_id']     = (int) $r['member_id'];
        $r['group_id']      = (int) $r['group_id'];
        $r['is_primary']    = (int) $r['is_primary'];
        $r['group_display'] = formatIdolDisplay(['name' => $r['group_name'], 'display_hint' => $r['group_display_hint'] ?? '']);
    }
    unset($r);
    jsonResponse(['data' => $rows]);
}

function handleMembershipSave(PDO $pdo): void
{
    $id        = (int) ($_POST['id'] ?? 0);
    $memberId  = (int) ($_POST['member_id'] ?? 0);
    $groupId   = (int) ($_POST['group_id'] ?? 0);
    $start     = trim($_POST['start_date'] ?? '');
    $end       = trim($_POST['end_date'] ?? '');
    $isPrimary = isset($_POST['is_primary']) ? (int) (bool) $_POST['is_primary'] : 1;
    $note      = trim($_POST['note'] ?? '');

    if ($memberId === 0 || $groupId === 0) {
        jsonResponse(['error' => 'member_id and group_id are required'], 400);
    }
    foreach (['start' => $start, 'end' => $end] as $k => $v) {
        if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            jsonResponse(['error' => "Invalid {$k}_date format"], 400);
        }
    }
    if ($start !== '' && $end !== '' && $start > $end) {
        jsonResponse(['error' => 'start_date must be <= end_date'], 400);
    }

    // Validate that member_id is a member and group_id is a group/unit/company
    $mRow = $pdo->prepare("SELECT category FROM idol_entities WHERE id = :id");
    $mRow->execute([':id' => $memberId]);
    if (($mRow->fetchColumn() ?: '') !== 'member') {
        jsonResponse(['error' => 'member_id must reference a member entity'], 400);
    }
    $gRow = $pdo->prepare("SELECT category FROM idol_entities WHERE id = :id");
    $gRow->execute([':id' => $groupId]);
    if (!in_array(($gRow->fetchColumn() ?: ''), ['group', 'unit', 'company'], true)) {
        jsonResponse(['error' => 'group_id must reference a group/unit/company entity'], 400);
    }

    $params = [
        ':mid'     => $memberId,
        ':gid'     => $groupId,
        ':sd'      => $start !== '' ? $start : null,
        ':ed'      => $end   !== '' ? $end   : null,
        ':primary' => $isPrimary,
        ':note'    => $note,
    ];

    if ($id > 0) {
        $params[':id'] = $id;
        $pdo->prepare("
            UPDATE idol_memberships
            SET member_id = :mid, group_id = :gid, start_date = :sd, end_date = :ed,
                is_primary = :primary, note = :note
            WHERE id = :id
        ")->execute($params);
    } else {
        $pdo->prepare("
            INSERT INTO idol_memberships (member_id, group_id, start_date, end_date, is_primary, note)
            VALUES (:mid, :gid, :sd, :ed, :primary, :note)
        ")->execute($params);
        $id = (int) $pdo->lastInsertId();
    }

    // Detect overlapping primary memberships and surface as a warning (loose policy).
    $warnings = [];
    if ($isPrimary) {
        $overlap = $pdo->prepare("
            SELECT id FROM idol_memberships
            WHERE member_id = :mid AND is_primary = 1 AND id != :id
              AND (
                  (start_date IS NULL OR :ed IS NULL OR start_date <= :ed)
                  AND
                  (end_date IS NULL OR :sd IS NULL OR end_date >= :sd)
              )
            LIMIT 1
        ");
        $overlap->execute([':mid' => $memberId, ':id' => $id, ':sd' => $params[':sd'], ':ed' => $params[':ed']]);
        if ($overlap->fetchColumn() !== false) {
            $warnings[] = 'Primary membership overlaps with an existing primary period for this member.';
        }
    }

    jsonResponse(['success' => true, 'id' => $id, 'warnings' => $warnings]);
}

function handleMembershipDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === 0) {
        jsonResponse(['error' => 'id is required'], 400);
    }
    $pdo->prepare("DELETE FROM idol_memberships WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

function handleMembershipMove(PDO $pdo): void
{
    $memberId  = (int) ($_POST['member_id'] ?? 0);
    $newGroup  = (int) ($_POST['new_group_id'] ?? 0);
    $moveDate  = trim($_POST['move_date'] ?? '');

    if ($memberId === 0 || $newGroup === 0 || $moveDate === '') {
        jsonResponse(['error' => 'member_id, new_group_id, and move_date are required'], 400);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $moveDate)) {
        jsonResponse(['error' => 'Invalid move_date format'], 400);
    }

    $endDate = date('Y-m-d', strtotime($moveDate . ' -1 day'));

    $pdo->beginTransaction();
    try {
        // Close current open primary membership (end_date IS NULL)
        $pdo->prepare("
            UPDATE idol_memberships
            SET end_date = :ed
            WHERE member_id = :m AND is_primary = 1 AND end_date IS NULL
        ")->execute([':ed' => $endDate, ':m' => $memberId]);

        // Insert new membership
        $pdo->prepare("
            INSERT INTO idol_memberships (member_id, group_id, start_date, end_date, is_primary, note)
            VALUES (:m, :g, :sd, NULL, 1, 'moved via UI')
        ")->execute([':m' => $memberId, ':g' => $newGroup, ':sd' => $moveDate]);

        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    jsonResponse(['success' => true, 'new_membership_id' => $newId]);
}
