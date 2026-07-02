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
        'report_dashboard' => handleReportDashboard($pdo),
        'report_idol' => handleReportIdol($pdo),
        'report_type' => handleReportType($pdo),
        'report_idol_detail' => handleReportIdolDetail($pdo),
        'report_by_group' => handleReportByGroup($pdo),
        'report_by_company' => handleReportByCompany($pdo),
        'report_by_unit' => handleReportByUnit($pdo),
        'report_event' => handleReportEvent($pdo),
        'report_event_summary' => handleReportEventSummary($pdo),
        'report_top_items' => handleReportTopItems($pdo),
        'report_seasonality' => handleReportSeasonality($pdo),
        'report_inactive' => handleReportInactive($pdo),
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
        'event_list' => handleEventList($pdo),
        'event_save' => handleEventSave($pdo),
        'event_delete' => handleEventDelete($pdo),
        'event_bulk_assign' => handleEventBulkAssign($pdo),
        'event_auto_assign' => handleEventAutoAssign($pdo),
        'budget_list' => handleBudgetList($pdo),
        'budget_save' => handleBudgetSave($pdo),
        'budget_delete' => handleBudgetDelete($pdo),
        'budget_progress' => handleBudgetProgress($pdo),
        'budget_matrix' => handleBudgetMatrix($pdo),
        'budget_analytics' => handleBudgetAnalytics($pdo),
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
    $perPage = max(1, min(200, (int) ($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;

    $dateField = $_GET['date_field'] ?? 'order_date';
    if (!in_array($dateField, ['order_date', 'event_date'], true)) {
        $dateField = 'order_date';
    }

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
        $where[] = "i.{$dateField} >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'])) {
            jsonResponse(['error' => 'Invalid date_to format'], 400);
        }
        $where[] = "i.{$dateField} <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    $eventIds = array_values(array_filter(array_map('intval', (array) ($_GET['event_id'] ?? []))));
    if (!empty($eventIds)) {
        $phs = implode(',', array_map(fn($i) => ":evid$i", array_keys($eventIds)));
        $where[] = "i.event_id IN ($phs)";
        foreach ($eventIds as $i => $v) { $params[":evid$i"] = $v; }
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sortCol = $_GET['sort'] ?? 'order_date';
    $sortDir = (strtolower($_GET['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
    $allowedSort = ['order_date', 'event_date', 'title', 'idol', 'type', 'price_per_qty', 'qty', 'id'];
    if (!in_array($sortCol, $allowedSort, true)) {
        $sortCol = 'order_date';
    }
    $sortColSQL = "i.{$sortCol}";

    // Count total (alias items as i so event_id filter works)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i {$whereSQL}");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Summary
    $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(i.price_per_qty * i.qty), 0) as total_price, COALESCE(SUM(i.qty), 0) as total_qty FROM items i {$whereSQL}");
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch();

    // Fetch rows — JOIN events for event_name
    $stmt = $pdo->prepare("
        SELECT i.id, i.order_date, i.event_date, i.event_id, ev.name AS event_name,
               i.title, i.idol, i.type, i.price_per_qty, i.qty,
               (i.price_per_qty * i.qty) as total_price
        FROM items i
        LEFT JOIN events ev ON ev.id = i.event_id
        {$whereSQL}
        ORDER BY {$sortColSQL} {$sortDir}, i.id DESC
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
    $stmt = $pdo->prepare('
        SELECT i.*, ev.name AS event_name
        FROM items i
        LEFT JOIN events ev ON ev.id = i.event_id
        WHERE i.id = :id
    ');
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
        INSERT INTO items (order_date, event_date, event_id, title, idol, type, price_per_qty, qty, idol_id)
        VALUES (:order_date, :event_date, :event_id, :title, :idol, :type, :price_per_qty, :qty, :idol_id)
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
            event_id = :event_id,
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

/**
 * Consolidated payload for the Dashboard landing page (index.php).
 * Returns KPIs, monthly trend, top members/groups, and type/company breakdowns
 * in a single round-trip. Accepts optional date_from / date_to (YYYY-MM-DD) that
 * filter every aggregate by items.order_date. The membership-aware joins mirror
 * handleReportByGroup / handleReportByCompany so numbers match the Report page.
 */
function handleReportDashboard(PDO $pdo): void
{
    $from = trim($_GET['date_from'] ?? '');
    $to   = trim($_GET['date_to'] ?? '');
    if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = '';
    if ($to   !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = '';

    // Date filter fragment applied to items alias `i`. All dashboard queries use the
    // same alias, so a single clause + params array is reused for every prepare.
    $dateClause = '';
    $dateParams = [];
    if ($from !== '') { $dateClause .= " AND i.order_date >= :df"; $dateParams[':df'] = $from; }
    if ($to   !== '') { $dateClause .= " AND i.order_date <= :dt"; $dateParams[':dt'] = $to; }

    $run = function (string $sql) use ($pdo, $dateParams) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dateParams);
        return $stmt->fetchAll();
    };

    // Monthly trend (in range)
    $monthly = $run("
        SELECT
            strftime('%Y-%m', i.order_date) AS month,
            COUNT(*)                        AS items,
            SUM(i.qty)                      AS total_qty,
            SUM(i.price_per_qty * i.qty)    AS total_price
        FROM items i
        WHERE i.order_date != '' $dateClause
        GROUP BY month
        ORDER BY month
    ");
    $monthly = array_map(fn($r) => [
        'month'       => $r['month'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $monthly);

    // Overall KPI totals (in range)
    $totals = $run("
        SELECT
            COUNT(*)                                    AS total_items,
            COALESCE(SUM(i.qty), 0)                     AS total_qty,
            COALESCE(SUM(i.price_per_qty * i.qty), 0)   AS total_spent
        FROM items i
        WHERE i.order_date != '' $dateClause
    ")[0];

    // Top members (mapped + unmapped), top 5
    $topMembers = array_map(fn($r) => enrichIdolRow($r), $run("
        SELECT
            i.idol_id                                   AS idol_id,
            COALESCE(m.name, i.idol)                    AS idol,
            COALESCE(m.display_hint, '')                AS display_hint,
            COUNT(*)                                    AS items,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price
        FROM items i
        LEFT JOIN idol_entities m ON m.id = i.idol_id AND m.category = 'member'
        WHERE i.idol != '' AND i.idol != '-' $dateClause
        GROUP BY COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY total_price DESC
        LIMIT 5
    "));

    // By type
    $byType = array_map(fn($r) => [
        'type'        => $r['type'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $run("
        SELECT i.type AS type, COUNT(*) AS items, SUM(i.qty) AS total_qty, SUM(i.price_per_qty * i.qty) AS total_price
        FROM items i
        WHERE i.type != '' AND i.type != '-' $dateClause
        GROUP BY i.type
        ORDER BY total_price DESC
    "));

    // Top groups (membership-aware, top 5)
    $topGroups = array_map(fn($r) => [
        'group_id'    => (int) $r['group_id'],
        'name'        => $r['group_name'],
        'category'    => $r['group_category'],
        'parent'      => $r['company_name'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $run("
        SELECT
            g.id            AS group_id,
            g.name          AS group_name,
            g.category      AS group_category,
            c.name          AS company_name,
            COUNT(*)        AS items,
            SUM(i.qty)      AS total_qty,
            SUM(i.price_per_qty * i.qty) AS total_price
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
        WHERE 1=1 $dateClause
        GROUP BY g.id
        ORDER BY total_price DESC
        LIMIT 5
    "));

    // By company (membership-aware)
    $byCompany = array_map(fn($r) => [
        'name'        => $r['company_name'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $run("
        SELECT
            c.name          AS company_name,
            COUNT(*)        AS items,
            SUM(i.qty)      AS total_qty,
            SUM(i.price_per_qty * i.qty) AS total_price
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
        WHERE 1=1 $dateClause
        GROUP BY c.id
        ORDER BY total_price DESC
    "));

    // Available years for the period selector (unfiltered).
    $years = $pdo->query("
        SELECT DISTINCT strftime('%Y', order_date) AS y
        FROM items WHERE order_date != ''
        ORDER BY y DESC
    ")->fetchAll(PDO::FETCH_COLUMN);

    // KPIs derived from totals + monthly series
    $activeMonths = count($monthly);
    $totalSpent   = (float) $totals['total_spent'];
    $latestMonth  = $activeMonths ? $monthly[$activeMonths - 1] : null;
    $prevMonth    = $activeMonths >= 2 ? $monthly[$activeMonths - 2] : null;
    $momChange    = null;
    if ($latestMonth && $prevMonth && $prevMonth['total_price'] > 0) {
        $momChange = ($latestMonth['total_price'] - $prevMonth['total_price']) / $prevMonth['total_price'] * 100;
    }

    $kpis = [
        'total_items'       => (int) $totals['total_items'],
        'total_qty'         => (int) $totals['total_qty'],
        'total_spent'       => $totalSpent,
        'active_months'     => $activeMonths,
        'avg_per_month'     => $activeMonths ? $totalSpent / $activeMonths : 0.0,
        'latest_month'      => $latestMonth['month'] ?? null,
        'latest_month_spent'=> $latestMonth['total_price'] ?? 0.0,
        'prev_month_spent'  => $prevMonth['total_price'] ?? 0.0,
        'mom_change_pct'    => $momChange,
        'top_member'        => $topMembers[0]['display'] ?? null,
        'top_group'         => $topGroups[0]['name'] ?? null,
        'top_type'          => $byType[0]['type'] ?? null,
    ];

    jsonResponse([
        'kpis'        => $kpis,
        'monthly'     => $monthly,
        'top_members' => $topMembers,
        'top_groups'  => $topGroups,
        'by_type'     => $byType,
        'by_company'  => $byCompany,
        'years'       => $years,
    ]);
}

/**
 * Spending rolled up by UNIT-category entities (category = 'unit').
 * Unlike the By Group report (primary memberships only), this includes
 * BOTH primary and non-primary (sub-unit / project) memberships, so it
 * surfaces sub-unit activity the group report rolls into the parent group.
 * Membership window is matched against item.order_date.
 */
function handleReportByUnit(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT
            u.id                                          AS unit_id,
            u.name                                        AS unit_name,
            c.name                                        AS parent_name,
            COUNT(*)                                      AS items,
            SUM(i.qty)                                    AS total_qty,
            SUM(i.price_per_qty * i.qty)                  AS total_price,
            COUNT(DISTINCT m.id)                          AS members
        FROM items i
        JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        JOIN idol_memberships ms
            ON ms.member_id = m.id
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities u
            ON u.id = ms.group_id AND u.category = 'unit'
        LEFT JOIN idol_entities c
            ON c.id = u.parent_id
        GROUP BY u.id
        ORDER BY total_price DESC
    ")->fetchAll();

    $result = array_map(fn($r) => [
        'unit_id'     => (int) $r['unit_id'],
        'name'        => $r['unit_name'],
        'parent'      => $r['parent_name'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
        'members'     => (int) $r['members'],
    ], $rows);

    jsonResponse(['data' => $result]);
}

/**
 * Spending grouped by event_date (the date of the concert/event the item is
 * tied to), plus order→event lead-time statistics. event_date is otherwise
 * unused by the other reports, which all key off order_date.
 */
function handleReportEvent(PDO $pdo): void
{
    // Named events: aggregate items linked via event_id
    $named = $pdo->query("
        SELECT
            e.id                                AS event_id,
            e.name                              AS event_name,
            e.event_date                        AS event_date,
            e.end_date                          AS end_date,
            COUNT(i.id)                         AS items,
            COALESCE(SUM(i.qty), 0)             AS total_qty,
            COALESCE(SUM(i.price_per_qty*i.qty),0) AS total_price,
            COUNT(DISTINCT i.idol_id)           AS idols,
            COUNT(DISTINCT i.type)              AS types
        FROM events e
        LEFT JOIN items i ON i.event_id = e.id
        GROUP BY e.id
        ORDER BY e.event_date DESC, e.name
    ")->fetchAll();

    $named = array_map(fn($r) => [
        'event_id'    => (int) $r['event_id'],
        'event_name'  => $r['event_name'],
        'event_date'  => $r['event_date'],
        'end_date'    => $r['end_date'],
        'is_named'    => true,
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
        'idols'       => (int) $r['idols'],
        'types'       => (int) $r['types'],
    ], $named);

    // Unlinked: items with event_date but no event_id (legacy / not yet assigned)
    $unlinked = $pdo->query("
        SELECT
            event_date                          AS event_date,
            COUNT(*)                            AS items,
            SUM(qty)                            AS total_qty,
            SUM(price_per_qty * qty)            AS total_price,
            COUNT(DISTINCT idol)                AS idols,
            COUNT(DISTINCT type)                AS types
        FROM items
        WHERE event_date IS NOT NULL AND event_date != ''
          AND event_id IS NULL
        GROUP BY event_date
        ORDER BY event_date DESC
    ")->fetchAll();

    $unlinked = array_map(fn($r) => [
        'event_id'    => null,
        'event_name'  => null,
        'event_date'  => $r['event_date'],
        'end_date'    => null,
        'is_named'    => false,
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
        'idols'       => (int) $r['idols'],
        'types'       => (int) $r['types'],
    ], $unlinked);

    // Lead-time stats across all items with both dates
    $lead = $pdo->query("
        SELECT
            COUNT(*)                                                       AS n,
            AVG(julianday(event_date) - julianday(order_date))             AS avg_days,
            MIN(julianday(event_date) - julianday(order_date))             AS min_days,
            MAX(julianday(event_date) - julianday(order_date))             AS max_days
        FROM items
        WHERE event_date IS NOT NULL AND event_date != ''
          AND order_date IS NOT NULL AND order_date != ''
    ")->fetch();

    $noEvent = (int) $pdo->query("
        SELECT COUNT(*) FROM items WHERE event_date IS NULL OR event_date = ''
    ")->fetchColumn();

    jsonResponse([
        'named'     => $named,
        'unlinked'  => $unlinked,
        'lead_time' => [
            'n'        => (int) ($lead['n'] ?? 0),
            'avg_days' => $lead['avg_days'] !== null ? round((float) $lead['avg_days'], 1) : null,
            'min_days' => $lead['min_days'] !== null ? (int) round((float) $lead['min_days']) : null,
            'max_days' => $lead['max_days'] !== null ? (int) round((float) $lead['max_days']) : null,
        ],
        'no_event'  => $noEvent,
    ]);
}

/**
 * Event-entity report: one row per named event with its date range, duration
 * (days), upcoming/ongoing/past status, linked-item totals and average spend
 * per event-day. Distinct from handleReportEvent, which aggregates item
 * spending and lead-time across events + unlinked dates.
 */
function handleReportEventSummary(PDO $pdo): void
{
    $today = date('Y-m-d');

    $rows = $pdo->query("
        SELECT
            e.id, e.name,
            e.event_date                           AS start_date,
            e.end_date                             AS end_date,
            COUNT(i.id)                            AS items,
            COALESCE(SUM(i.qty), 0)                AS total_qty,
            COALESCE(SUM(i.price_per_qty*i.qty),0) AS total_price
        FROM events e
        LEFT JOIN items i ON i.event_id = e.id
        GROUP BY e.id
        ORDER BY e.event_date DESC, COALESCE(e.end_date, e.event_date) DESC, e.name
    ")->fetchAll();

    $events = [];
    $totalDays = 0;
    $multiDay  = 0;
    $upcoming  = 0;
    $ongoing   = 0;

    foreach ($rows as $r) {
        $start = $r['start_date'];
        $end   = ($r['end_date'] !== null && $r['end_date'] !== '') ? $r['end_date'] : $start;
        $days  = (int) round((strtotime($end) - strtotime($start)) / 86400) + 1;
        if ($days < 1) $days = 1;
        $isMulti = ($r['end_date'] !== null && $r['end_date'] !== '' && $r['end_date'] !== $start);

        if ($start > $today)    { $status = 'upcoming'; $upcoming++; }
        elseif ($end < $today)  { $status = 'past'; }
        else                    { $status = 'ongoing'; $ongoing++; }

        if ($isMulti) $multiDay++;
        $totalDays += $days;

        $total = (float) $r['total_price'];
        $events[] = [
            'id'          => (int) $r['id'],
            'name'        => $r['name'],
            'start_date'  => $start,
            'end_date'    => $r['end_date'],
            'days'        => $days,
            'status'      => $status,
            'items'       => (int) $r['items'],
            'total_qty'   => (int) $r['total_qty'],
            'total_price' => $total,
            'avg_per_day' => $days > 0 ? round($total / $days, 2) : $total,
        ];
    }

    jsonResponse([
        'events'  => $events,
        'summary' => [
            'total'      => count($events),
            'multi_day'  => $multiDay,
            'total_days' => $totalDays,
            'upcoming'   => $upcoming,
            'ongoing'    => $ongoing,
        ],
    ]);
}

/**
 * "Top / extremes" report: the most expensive single line items, the most
 * frequently bought titles, and average unit price per type.
 */
function handleReportTopItems(PDO $pdo): void
{
    // Most expensive single line items (price_per_qty * qty)
    $expensive = $pdo->query("
        SELECT
            id, title, idol, type, order_date, event_date,
            price_per_qty, qty,
            (price_per_qty * qty) AS line_total
        FROM items
        ORDER BY line_total DESC
        LIMIT 20
    ")->fetchAll();
    $expensive = array_map(fn($r) => [
        'id'            => (int) $r['id'],
        'title'         => $r['title'],
        'idol'          => $r['idol'],
        'type'          => $r['type'],
        'order_date'    => $r['order_date'],
        'event_date'    => $r['event_date'],
        'price_per_qty' => (float) $r['price_per_qty'],
        'qty'           => (int) $r['qty'],
        'line_total'    => (float) $r['line_total'],
    ], $expensive);

    // Most frequently purchased titles (by number of line items)
    $frequent = $pdo->query("
        SELECT
            title,
            COUNT(*)                       AS items,
            SUM(qty)                       AS total_qty,
            SUM(price_per_qty * qty)       AS total_price
        FROM items
        WHERE title != '' AND title != '-'
        GROUP BY title
        ORDER BY items DESC, total_price DESC
        LIMIT 20
    ")->fetchAll();
    $frequent = array_map(fn($r) => [
        'title'       => $r['title'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $frequent);

    // Average unit price per type
    $avgPrice = $pdo->query("
        SELECT
            type,
            COUNT(*)                       AS items,
            SUM(qty)                       AS total_qty,
            AVG(price_per_qty)             AS avg_price,
            MIN(price_per_qty)             AS min_price,
            MAX(price_per_qty)             AS max_price,
            SUM(price_per_qty * qty)       AS total_price
        FROM items
        WHERE type != '' AND type != '-'
        GROUP BY type
        ORDER BY avg_price DESC
    ")->fetchAll();
    $avgPrice = array_map(fn($r) => [
        'type'        => $r['type'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'avg_price'   => round((float) $r['avg_price'], 2),
        'min_price'   => (float) $r['min_price'],
        'max_price'   => (float) $r['max_price'],
        'total_price' => (float) $r['total_price'],
    ], $avgPrice);

    jsonResponse([
        'expensive' => $expensive,
        'frequent'  => $frequent,
        'avg_price' => $avgPrice,
    ]);
}

/**
 * Seasonality: spending by day-of-week (0=Sun..6=Sat) and by month-of-year
 * (01..12), aggregated across all years. Keyed off order_date.
 */
function handleReportSeasonality(PDO $pdo): void
{
    $weekday = $pdo->query("
        SELECT
            CAST(strftime('%w', order_date) AS INTEGER) AS dow,
            COUNT(*)                       AS items,
            SUM(qty)                       AS total_qty,
            SUM(price_per_qty * qty)       AS total_price
        FROM items
        WHERE order_date != ''
        GROUP BY dow
        ORDER BY dow
    ")->fetchAll();
    $weekday = array_map(fn($r) => [
        'dow'         => (int) $r['dow'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $weekday);

    $monthOfYear = $pdo->query("
        SELECT
            CAST(strftime('%m', order_date) AS INTEGER) AS moy,
            COUNT(*)                       AS items,
            SUM(qty)                       AS total_qty,
            SUM(price_per_qty * qty)       AS total_price
        FROM items
        WHERE order_date != ''
        GROUP BY moy
        ORDER BY moy
    ")->fetchAll();
    $monthOfYear = array_map(fn($r) => [
        'moy'         => (int) $r['moy'],
        'items'       => (int) $r['items'],
        'total_qty'   => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $monthOfYear);

    jsonResponse(['weekday' => $weekday, 'month_of_year' => $monthOfYear]);
}

/**
 * Members and their last purchase date, with days since. The client decides
 * the "inactive" threshold (e.g. 30 / 90 days). Only members that have at
 * least one item are returned.
 */
function handleReportInactive(PDO $pdo): void
{
    $rows = $pdo->query("
        SELECT
            i.idol_id                                   AS idol_id,
            COALESCE(m.name, i.idol)                    AS idol,
            COALESCE(m.display_hint, '')                AS display_hint,
            COUNT(*)                                    AS items,
            SUM(i.qty)                                  AS total_qty,
            SUM(i.price_per_qty * i.qty)                AS total_price,
            MAX(i.order_date)                           AS last_order,
            CAST(julianday('now','localtime') - julianday(MAX(i.order_date)) AS INTEGER) AS days_since
        FROM items i
        LEFT JOIN idol_entities m ON m.id = i.idol_id AND m.category = 'member'
        WHERE i.idol != '' AND i.idol != '-' AND i.order_date != ''
        GROUP BY COALESCE(CAST(i.idol_id AS TEXT), 'NA:' || i.idol)
        ORDER BY days_since DESC
    ")->fetchAll();

    $rows = array_map(function ($r) {
        $row = enrichIdolRow($r);
        $row['last_order'] = $r['last_order'];
        $row['days_since'] = $r['days_since'] !== null ? (int) $r['days_since'] : null;
        return $row;
    }, $rows);

    jsonResponse(['data' => $rows]);
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
    $isTicket = !empty($_POST['is_ticket']) ? 1 : 0;

    if ($name === '') {
        jsonResponse(['error' => 'Name is required'], 400);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE type_categories SET name = :name, description = :desc, sort_order = :sort, is_ticket = :ticket WHERE id = :id");
        $stmt->execute([':name' => $name, ':desc' => $description, ':sort' => $sortOrder, ':ticket' => $isTicket, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO type_categories (name, description, sort_order, is_ticket) VALUES (:name, :desc, :sort, :ticket)");
        $stmt->execute([':name' => $name, ':desc' => $description, ':sort' => $sortOrder, ':ticket' => $isTicket]);
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

// --- Events ---

function handleEventList(PDO $pdo): void
{
    $events = $pdo->query("
        SELECT e.*,
               COUNT(i.id)                              AS items_count,
               COALESCE(SUM(i.price_per_qty * i.qty), 0) AS total_price,
               (SELECT COUNT(*) FROM items x
                WHERE x.event_date BETWEEN e.event_date AND COALESCE(e.end_date, e.event_date)
                  AND x.event_id IS NULL) AS unassigned_same_date,
               (SELECT COUNT(*) FROM items x
                JOIN type_categories tc ON tc.name = x.type AND tc.is_ticket = 1
                WHERE x.event_id = e.id) AS ticket_items_count
        FROM events e
        LEFT JOIN items i ON i.event_id = e.id
        GROUP BY e.id
        ORDER BY e.event_date DESC, COALESCE(e.end_date, e.event_date) DESC, e.name
    ")->fetchAll();

    $events = array_map(fn($r) => [
        'id'                   => (int) $r['id'],
        'name'                 => $r['name'],
        'event_date'           => $r['event_date'],
        'end_date'             => $r['end_date'],
        'description'          => $r['description'],
        'created_at'           => $r['created_at'],
        'is_free_entry'        => (int) $r['is_free_entry'],
        'items_count'          => (int) $r['items_count'],
        'total_price'          => (float) $r['total_price'],
        'unassigned_same_date' => (int) $r['unassigned_same_date'],
        'ticket_items_count'   => (int) $r['ticket_items_count'],
    ], $events);

    $ticketTypesCount = (int) $pdo->query("SELECT COUNT(*) FROM type_categories WHERE is_ticket = 1")->fetchColumn();

    jsonResponse(['events' => $events, 'ticket_types_count' => $ticketTypesCount]);
}

function handleEventSave(PDO $pdo): void
{
    $id          = (int) ($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $endDate     = trim($_POST['end_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isFreeEntry = !empty($_POST['is_free_entry']) ? 1 : 0;

    if ($name === '') {
        jsonResponse(['error' => 'Name is required'], 400);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        jsonResponse(['error' => 'Invalid event_date format'], 400);
    }
    // end_date is optional; NULL (or equal to start) means a single-day event.
    if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        jsonResponse(['error' => 'Invalid end_date format'], 400);
    }
    if ($endDate !== '' && $endDate < $eventDate) {
        jsonResponse(['error' => 'End date must be on or after the start date'], 400);
    }
    $endDateVal = ($endDate === '' || $endDate === $eventDate) ? null : $endDate;

    if ($id > 0) {
        $pdo->prepare("UPDATE events SET name = :name, event_date = :date, end_date = :end, description = :desc, is_free_entry = :free WHERE id = :id")
            ->execute([':name' => $name, ':date' => $eventDate, ':end' => $endDateVal, ':desc' => $description, ':free' => $isFreeEntry, ':id' => $id]);
    } else {
        $pdo->prepare("INSERT INTO events (name, event_date, end_date, description, is_free_entry) VALUES (:name, :date, :end, :desc, :free)")
            ->execute([':name' => $name, ':date' => $eventDate, ':end' => $endDateVal, ':desc' => $description, ':free' => $isFreeEntry]);
        $id = (int) $pdo->lastInsertId();
    }
    jsonResponse(['success' => true, 'id' => $id]);
}

function handleEventDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    $pdo->prepare("DELETE FROM events WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

function handleEventBulkAssign(PDO $pdo): void
{
    // An empty/zero event_id means "unassign" — clear the event link (set NULL).
    $eventRaw = $_POST['event_id'] ?? '';
    $unassign = ($eventRaw === '' || (int) $eventRaw === 0);
    $eventId  = (int) $eventRaw;
    $rawIds  = $_POST['ids'] ?? [];
    if (is_string($rawIds)) {
        $rawIds = array_filter(array_map('trim', explode(',', $rawIds)));
    }
    $ids = array_values(array_filter(array_map('intval', (array) $rawIds)));

    if (!$ids) {
        jsonResponse(['error' => 'No item IDs provided'], 400);
    }

    $phs = implode(',', array_fill(0, count($ids), '?'));

    if ($unassign) {
        $stmt = $pdo->prepare("UPDATE items SET event_id = NULL WHERE id IN ($phs)");
        $stmt->execute($ids);
        jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
    }

    $ev = $pdo->prepare("SELECT id FROM events WHERE id = :id");
    $ev->execute([':id' => $eventId]);
    if (!$ev->fetchColumn()) {
        jsonResponse(['error' => 'Event not found'], 404);
    }

    $stmt = $pdo->prepare("UPDATE items SET event_id = ? WHERE id IN ($phs)");
    $stmt->execute([$eventId, ...$ids]);
    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
}

function handleEventAutoAssign(PDO $pdo): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    if (!$eventId) {
        jsonResponse(['error' => 'event_id is required'], 400);
    }

    $ev = $pdo->prepare("SELECT event_date, end_date FROM events WHERE id = :id");
    $ev->execute([':id' => $eventId]);
    $row = $ev->fetch();
    if (!$row) {
        jsonResponse(['error' => 'Event not found'], 404);
    }

    // Match unlinked items whose event_date falls within the event's date range
    // (single-day events have end_date NULL, so the range collapses to one day).
    $stmt = $pdo->prepare("
        UPDATE items SET event_id = :eid
        WHERE event_date BETWEEN :start AND :end AND event_id IS NULL
    ");
    $stmt->execute([
        ':eid'   => $eventId,
        ':start' => $row['event_date'],
        ':end'   => $row['end_date'] ?? $row['event_date'],
    ]);
    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
}

// --- Budgets / Spending Goals ---
// NB: arrays are inlined rather than file-level `const`s — the action dispatch at the
// top of this file runs before any mid-file const statement would be evaluated.

/** Validate a YYYY-MM month string, falling back to the current month. */
function validBudgetMonth(string $m): string
{
    return preg_match('/^\d{4}-\d{2}$/', $m) ? $m : date('Y-m');
}

/** Colour status for a budget: ok < warn_pct <= near < danger_pct <= over. */
function budgetStatus(float $spent, float $amount, int $warn, int $danger): string
{
    if ($amount <= 0) return 'ok';
    $pct = $spent / $amount * 100;
    if ($pct >= $danger) return 'over';
    if ($pct >= $warn)   return 'near';
    return 'ok';
}

/**
 * Spending for one budget scope within a calendar month (YYYY-MM).
 * Entity scopes (member/group/company) use the same membership-aware join as the
 * By-Member / By-Group / By-Company reports so the numbers stay consistent.
 */
function budgetSpentForMonth(PDO $pdo, string $scopeType, ?int $refId, string $refName, string $month): float
{
    $monthClause = "strftime('%Y-%m', i.order_date) = :month";
    $params      = [':month' => $month];

    $membershipJoin = "
        JOIN idol_entities m
            ON m.id = i.idol_id AND m.category = 'member'
        JOIN idol_memberships ms
            ON ms.member_id = m.id
            AND ms.is_primary = 1
            AND (ms.start_date IS NULL OR ms.start_date <= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
            AND (ms.end_date   IS NULL OR ms.end_date   >= COALESCE(NULLIF(i.order_date,''), date('now','localtime')))
        JOIN idol_entities g
            ON g.id = ms.group_id AND g.category IN ('group','unit')";

    switch ($scopeType) {
        case 'overall':
            $sql = "SELECT COALESCE(SUM(i.price_per_qty * i.qty),0)
                    FROM items i WHERE i.order_date != '' AND $monthClause";
            break;
        case 'type':
            $sql = "SELECT COALESCE(SUM(i.price_per_qty * i.qty),0)
                    FROM items i WHERE i.type = :ref AND $monthClause";
            $params[':ref'] = $refName;
            break;
        case 'member':
            $sql = "SELECT COALESCE(SUM(i.price_per_qty * i.qty),0)
                    FROM items i WHERE i.idol_id = :ref AND $monthClause";
            $params[':ref'] = $refId;
            break;
        case 'group':
            $sql = "SELECT COALESCE(SUM(i.price_per_qty * i.qty),0)
                    FROM items i $membershipJoin
                    WHERE g.id = :ref AND $monthClause";
            $params[':ref'] = $refId;
            break;
        case 'company':
            $sql = "SELECT COALESCE(SUM(i.price_per_qty * i.qty),0)
                    FROM items i $membershipJoin
                    JOIN idol_entities c ON c.id = g.parent_id AND c.category = 'company'
                    WHERE c.id = :ref AND $monthClause";
            $params[':ref'] = $refId;
            break;
        default:
            return 0.0;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (float) $stmt->fetchColumn();
}

/** Resolve a fresh display label + hint for a scope (entity names may have changed). */
function budgetResolveLabel(PDO $pdo, string $scopeType, ?int $refId, string $refName): array
{
    $label = $refName;
    $hint  = '';
    if (in_array($scopeType, ['group', 'company', 'member'], true) && $refId) {
        $stmt = $pdo->prepare("SELECT name, display_hint FROM idol_entities WHERE id = :id");
        $stmt->execute([':id' => $refId]);
        $e = $stmt->fetch();
        if ($e) { $label = $e['name']; $hint = (string) ($e['display_hint'] ?? ''); }
    }
    return [$label, $hint];
}

/** Stable key identifying a budget scope (independent of period). */
function budgetScopeKey(array $b): string
{
    return $b['scope_type'] . '|' . ($b['scope_ref_id'] ?? '') . '|' . ($b['scope_ref_name'] ?? '');
}

/** Display ordering for scope types. */
function budgetScopeOrder(string $s): int
{
    return ['overall' => 0, 'company' => 1, 'group' => 2, 'member' => 3, 'type' => 4][$s] ?? 5;
}

/** Enrich a raw budgets row with a fresh scope label, spending and colour status. */
function budgetRow(PDO $pdo, array $b, string $month): array
{
    $scopeType = $b['scope_type'];
    $refId     = $b['scope_ref_id'] !== null ? (int) $b['scope_ref_id'] : null;
    $refName   = (string) ($b['scope_ref_name'] ?? '');
    [$label, $hint] = budgetResolveLabel($pdo, $scopeType, $refId, $refName);

    $amount = (float) $b['amount'];
    $warn   = (int) $b['warn_pct'];
    $danger = (int) $b['danger_pct'];
    $spent  = budgetSpentForMonth($pdo, $scopeType, $refId, $refName, $month);

    return [
        'id'             => (int) $b['id'],
        'scope_type'     => $scopeType,
        'scope_ref_id'   => $refId,
        'scope_ref_name' => $refName,
        'period'         => $b['period'] ?? null,
        'label'          => $label,
        'display_hint'   => $hint,
        'amount'         => $amount,
        'warn_pct'       => $warn,
        'danger_pct'     => $danger,
        'note'           => (string) ($b['note'] ?? ''),
        'spent'          => $spent,
        'remaining'      => $amount - $spent,
        'pct'            => $amount > 0 ? round($spent / $amount * 100, 1) : 0.0,
        'status'         => budgetStatus($spent, $amount, $warn, $danger),
    ];
}

/**
 * Effective budgets for a month: per scope, a month override (period = month)
 * wins over the recurring default (period IS NULL). Each row carries flags so the
 * UI can show the source and offer "edit this month" / "reset to default".
 */
function loadEffectiveBudgets(PDO $pdo, string $month): array
{
    $defaults = $pdo->query("SELECT * FROM budgets WHERE is_active = 1 AND period IS NULL")->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE is_active = 1 AND period = :m");
    $stmt->execute([':m' => $month]);
    $overrides = $stmt->fetchAll();

    $map = [];
    foreach ($defaults as $d)  { $map[budgetScopeKey($d)]['default']  = $d; }
    foreach ($overrides as $o) { $map[budgetScopeKey($o)]['override'] = $o; }

    $out = [];
    foreach ($map as $pair) {
        $eff = $pair['override'] ?? $pair['default'];
        $row = budgetRow($pdo, $eff, $month);
        $row['is_override'] = isset($pair['override']);
        $row['has_default'] = isset($pair['default']);
        $row['default_id']  = isset($pair['default'])  ? (int) $pair['default']['id']  : null;
        $row['override_id'] = isset($pair['override']) ? (int) $pair['override']['id'] : null;
        $out[] = $row;
    }

    usort($out, fn($a, $b) => [budgetScopeOrder($a['scope_type']), $a['label']]
                          <=> [budgetScopeOrder($b['scope_type']), $b['label']]);
    return $out;
}

/** Recurring default budgets only (period IS NULL) — for the Manage view. */
function loadDefaultBudgets(PDO $pdo): array
{
    $rows = $pdo->query("SELECT * FROM budgets WHERE is_active = 1 AND period IS NULL")->fetchAll();
    $out  = [];
    foreach ($rows as $b) {
        $refId = $b['scope_ref_id'] !== null ? (int) $b['scope_ref_id'] : null;
        [$label, $hint] = budgetResolveLabel($pdo, $b['scope_type'], $refId, (string) ($b['scope_ref_name'] ?? ''));
        $out[] = [
            'id'             => (int) $b['id'],
            'scope_type'     => $b['scope_type'],
            'scope_ref_id'   => $refId,
            'scope_ref_name' => (string) ($b['scope_ref_name'] ?? ''),
            'period'         => null,
            'label'          => $label,
            'display_hint'   => $hint,
            'amount'         => (float) $b['amount'],
            'warn_pct'       => (int) $b['warn_pct'],
            'danger_pct'     => (int) $b['danger_pct'],
            'note'           => (string) ($b['note'] ?? ''),
        ];
    }
    usort($out, fn($a, $b) => [budgetScopeOrder($a['scope_type']), $a['label']]
                          <=> [budgetScopeOrder($b['scope_type']), $b['label']]);
    return $out;
}

function handleBudgetList(PDO $pdo): void
{
    // ?mode=defaults → recurring definitions (Manage tab); otherwise effective-for-month.
    if (($_GET['mode'] ?? '') === 'defaults') {
        jsonResponse(['budgets' => loadDefaultBudgets($pdo)]);
    }
    $month = validBudgetMonth(trim($_GET['month'] ?? ''));
    jsonResponse(['month' => $month, 'budgets' => loadEffectiveBudgets($pdo, $month)]);
}

// Effective budgets for a month — used by the dashboard card and report tab.
function handleBudgetProgress(PDO $pdo): void
{
    $month = validBudgetMonth(trim($_GET['month'] ?? ''));
    jsonResponse(['month' => $month, 'budgets' => loadEffectiveBudgets($pdo, $month)]);
}

/**
 * Settings overview matrix: one row per budget scope, with the recurring default
 * and an effective amount for every month in [from, to]. No spend is computed —
 * this is purely about the budget *amounts*, so it stays a couple of cheap queries.
 * Month cells expose override_id so the UI can edit/reset a single month in place.
 */
function handleBudgetMatrix(PDO $pdo): void
{
    $from = validBudgetMonth(trim($_GET['from'] ?? ''));
    $to   = validBudgetMonth(trim($_GET['to'] ?? ''));
    if ($from > $to) { [$from, $to] = [$to, $from]; }

    // Enumerate the months in the inclusive range.
    $months = [];
    [$y, $m] = array_map('intval', explode('-', $from));
    while (sprintf('%04d-%02d', $y, $m) <= $to) {
        $months[] = sprintf('%04d-%02d', $y, $m);
        if (++$m > 12) { $m = 1; $y++; }
        if (count($months) > 240) break; // hard safety cap (20 years)
    }

    $defaults  = $pdo->query("SELECT * FROM budgets WHERE is_active = 1 AND period IS NULL")->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE is_active = 1 AND period BETWEEN :a AND :b");
    $stmt->execute([':a' => $from, ':b' => $to]);
    $overrides = $stmt->fetchAll();

    // Index by scope key: defaults plus each scope's per-month overrides.
    $scopes = [];
    $ensure = function (array $b) use (&$scopes, $pdo): string {
        $key = budgetScopeKey($b);
        if (!isset($scopes[$key])) {
            $refId = $b['scope_ref_id'] !== null ? (int) $b['scope_ref_id'] : null;
            [$label, $hint] = budgetResolveLabel($pdo, $b['scope_type'], $refId, (string) ($b['scope_ref_name'] ?? ''));
            $scopes[$key] = [
                'scope_type'     => $b['scope_type'],
                'scope_ref_id'   => $refId,
                'scope_ref_name' => (string) ($b['scope_ref_name'] ?? ''),
                'label'          => $label,
                'display_hint'   => $hint,
                'default'        => null,
                'cells'          => [],
            ];
        }
        return $key;
    };

    foreach ($defaults as $d) {
        $key = $ensure($d);
        $scopes[$key]['default'] = [
            'id'         => (int) $d['id'],
            'amount'     => (float) $d['amount'],
            'warn_pct'   => (int) $d['warn_pct'],
            'danger_pct' => (int) $d['danger_pct'],
            'note'       => (string) ($d['note'] ?? ''),
        ];
    }
    foreach ($overrides as $o) {
        $key = $ensure($o);
        $scopes[$key]['cells'][(string) $o['period']] = [
            'override_id' => (int) $o['id'],
            'amount'      => (float) $o['amount'],
            'warn_pct'    => (int) $o['warn_pct'],
            'danger_pct'  => (int) $o['danger_pct'],
            'note'        => (string) ($o['note'] ?? ''),
        ];
    }

    $out = array_values($scopes);
    usort($out, fn($a, $b) => [budgetScopeOrder($a['scope_type']), $a['label']]
                          <=> [budgetScopeOrder($b['scope_type']), $b['label']]);

    jsonResponse(['from' => $from, 'to' => $to, 'months' => $months, 'scopes' => $out]);
}

function handleBudgetSave(PDO $pdo): void
{
    $id        = (int) ($_POST['id'] ?? 0);
    $scopeType = trim($_POST['scope_type'] ?? 'overall');
    if (!in_array($scopeType, ['overall', 'type', 'group', 'company', 'member'], true)) {
        jsonResponse(['error' => 'Invalid scope_type'], 400);
    }

    $amount = (float) ($_POST['amount'] ?? 0);
    if ($amount < 0) {
        jsonResponse(['error' => 'Amount must be >= 0'], 400);
    }

    $warn   = (int) ($_POST['warn_pct'] ?? 80);
    $danger = (int) ($_POST['danger_pct'] ?? 100);
    if ($warn < 1 || $danger < 1 || $warn > $danger || $danger > 1000) {
        jsonResponse(['error' => 'Invalid thresholds: require 1 <= warn_pct <= danger_pct <= 1000'], 400);
    }

    // period: '' → recurring default; 'YYYY-MM' → override for that month
    $period = trim($_POST['period'] ?? '');
    if ($period !== '' && !preg_match('/^\d{4}-\d{2}$/', $period)) {
        jsonResponse(['error' => 'Invalid period format'], 400);
    }
    $periodVal = $period === '' ? null : $period;

    $note    = trim($_POST['note'] ?? '');
    $refId   = null;
    $refName = '';

    if ($scopeType === 'type') {
        $refName = trim($_POST['scope_ref_name'] ?? '');
        if ($refName === '') {
            jsonResponse(['error' => 'Type is required'], 400);
        }
    } elseif (in_array($scopeType, ['group', 'company', 'member'], true)) {
        $refId = (int) ($_POST['scope_ref_id'] ?? 0);
        if ($refId <= 0) {
            jsonResponse(['error' => 'Entity is required'], 400);
        }
        $allowed = match ($scopeType) {
            'company' => ['company'],
            'group'   => ['group', 'unit'],
            default   => ['member'],
        };
        $stmt = $pdo->prepare("SELECT name, category FROM idol_entities WHERE id = :id");
        $stmt->execute([':id' => $refId]);
        $ent = $stmt->fetch();
        if (!$ent || !in_array($ent['category'], $allowed, true)) {
            jsonResponse(['error' => 'Invalid entity for this scope'], 400);
        }
        $refName = $ent['name']; // snapshot
    }

    // Reject a second active budget for the same scope AND period
    // (one recurring default per scope; one override per scope per month).
    $dupCond   = "is_active = 1 AND scope_type = :st AND id != :id";
    $dupParams = [':st' => $scopeType, ':id' => $id];
    if ($scopeType === 'type') {
        $dupCond .= " AND scope_ref_name = :rn";
        $dupParams[':rn'] = $refName;
    } elseif (in_array($scopeType, ['group', 'company', 'member'], true)) {
        $dupCond .= " AND scope_ref_id = :rid";
        $dupParams[':rid'] = $refId;
    }
    if ($periodVal === null) {
        $dupCond .= " AND period IS NULL";
    } else {
        $dupCond .= " AND period = :pd";
        $dupParams[':pd'] = $periodVal;
    }
    $stmt = $pdo->prepare("SELECT id FROM budgets WHERE $dupCond LIMIT 1");
    $stmt->execute($dupParams);
    if ($stmt->fetchColumn() !== false) {
        jsonResponse(['error' => 'A budget for this scope and period already exists'], 409);
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE budgets
            SET scope_type = :st, scope_ref_id = :rid, scope_ref_name = :rn,
                amount = :amt, warn_pct = :warn, danger_pct = :danger, note = :note, period = :pd
            WHERE id = :id
        ");
        $stmt->execute([
            ':st' => $scopeType, ':rid' => $refId, ':rn' => $refName,
            ':amt' => $amount, ':warn' => $warn, ':danger' => $danger,
            ':note' => $note, ':pd' => $periodVal, ':id' => $id,
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO budgets (scope_type, scope_ref_id, scope_ref_name, amount, warn_pct, danger_pct, note, period)
            VALUES (:st, :rid, :rn, :amt, :warn, :danger, :note, :pd)
        ");
        $stmt->execute([
            ':st' => $scopeType, ':rid' => $refId, ':rn' => $refName,
            ':amt' => $amount, ':warn' => $warn, ':danger' => $danger, ':note' => $note, ':pd' => $periodVal,
        ]);
        $id = (int) $pdo->lastInsertId();
    }

    jsonResponse(['success' => true, 'id' => $id]);
}

function handleBudgetDelete(PDO $pdo): void
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }
    $pdo->prepare("DELETE FROM budgets WHERE id = :id")->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

/** Distinct selectable budget scopes: Overall + every scope with an active budget. */
function budgetScopeOptions(PDO $pdo): array
{
    $out = [[
        'scope_type'     => 'overall',
        'scope_ref_id'   => null,
        'scope_ref_name' => '',
        'label'          => '',
        'display_hint'   => '',
    ]];
    $seen = [];
    $rows = $pdo->query("SELECT DISTINCT scope_type, scope_ref_id, scope_ref_name FROM budgets WHERE is_active = 1")->fetchAll();
    foreach ($rows as $r) {
        if ($r['scope_type'] === 'overall') continue; // already present
        $key = budgetScopeKey($r);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $refId = $r['scope_ref_id'] !== null ? (int) $r['scope_ref_id'] : null;
        [$label, $hint] = budgetResolveLabel($pdo, $r['scope_type'], $refId, (string) ($r['scope_ref_name'] ?? ''));
        $out[] = [
            'scope_type'     => $r['scope_type'],
            'scope_ref_id'   => $refId,
            'scope_ref_name' => (string) ($r['scope_ref_name'] ?? ''),
            'label'          => $label,
            'display_hint'   => $hint,
        ];
    }
    usort($out, fn($a, $b) => [budgetScopeOrder($a['scope_type']), $a['label']]
                          <=> [budgetScopeOrder($b['scope_type']), $b['label']]);
    return $out;
}

/**
 * Rule-based budget recommendations as {code, severity, params}. Text is rendered
 * client-side via t('budget.rec_'+code, params) so it stays bilingual.
 */
function buildBudgetRecommendations(array $series, string $currentMonth): array
{
    $recs         = [];
    $budgetMonths = array_values(array_filter($series, fn($s) => $s['has_budget']));
    $n            = count($budgetMonths);

    // No budget defined anywhere in the window
    if ($n === 0) {
        if (array_sum(array_column($series, 'spent')) > 0) {
            $recs[] = ['code' => 'no_budget', 'severity' => 'info', 'params' => []];
        }
        return $recs;
    }

    // Over budget frequently
    $overCount = count(array_filter($budgetMonths, fn($s) => $s['over']));
    if ($overCount >= 2) {
        $recs[] = ['code' => 'over_frequent', 'severity' => 'danger', 'params' => ['m' => $overCount, 'n' => $n]];
    }

    // Latest month already over
    $last = end($series);
    if ($last && $last['has_budget'] && $last['over']) {
        $recs[] = ['code' => 'over_recent', 'severity' => 'danger', 'params' => []];
    }

    // Pace projection for the in-progress current month
    if ($last && $last['month'] === $currentMonth && $last['has_budget'] && $last['budget'] > 0 && !$last['over']) {
        $day = (int) date('j');
        $daysInMonth = (int) date('t');
        if ($day >= 1 && $day < $daysInMonth) {
            $projected = $last['spent'] / ($day / $daysInMonth);
            if ($projected > $last['budget'] * 1.05) {
                $recs[] = ['code' => 'projection', 'severity' => 'warning', 'params' => ['projected' => round($projected)]];
            }
        }
    }

    // Spending trend: recent half vs earlier half (needs >= 4 months of data)
    $spents = array_column($series, 'spent');
    $cnt    = count($spents);
    if ($cnt >= 4) {
        $half    = intdiv($cnt, 2);
        $earlier = array_slice($spents, 0, $half);
        $recent  = array_slice($spents, $cnt - $half);
        $ea = array_sum($earlier) / count($earlier);
        $ra = array_sum($recent) / count($recent);
        if ($ea > 0) {
            $delta = ($ra - $ea) / $ea * 100;
            if ($delta >= 15) {
                $recs[] = ['code' => 'trending_up', 'severity' => 'warning', 'params' => ['pct' => round($delta)]];
            } elseif ($delta <= -15) {
                $recs[] = ['code' => 'trending_down', 'severity' => 'success', 'params' => ['pct' => round(abs($delta))]];
            }
        }
    }

    // Consistently well under budget → suggest a tighter limit
    if ($n >= 3) {
        $allUnder = true;
        foreach ($budgetMonths as $s) {
            if ($s['budget'] <= 0 || $s['pct'] >= 60) { $allUnder = false; break; }
        }
        if ($allUnder) {
            $maxSpent  = max(array_column($budgetMonths, 'spent'));
            $suggested = (int) (ceil(($maxSpent * 1.1) / 100) * 100);
            $recs[] = ['code' => 'consistently_under', 'severity' => 'info', 'params' => ['suggested' => $suggested]];
        }
    }

    // All clear
    if (empty($recs)) {
        $recs[] = ['code' => 'on_track', 'severity' => 'success', 'params' => []];
    }

    return $recs;
}

/**
 * Multi-month spending-vs-budget analytics for one scope: a per-month series
 * (spent, effective limit, % used, colour status), summary stats, the selectable
 * scope list, and rule-based recommendations. Powers the Budget Insights view.
 */
function handleBudgetAnalytics(PDO $pdo): void
{
    $scopeType = (string) ($_GET['scope_type'] ?? 'overall');
    if (!in_array($scopeType, ['overall', 'type', 'group', 'company', 'member'], true)) {
        $scopeType = 'overall';
    }
    $refId   = isset($_GET['scope_ref_id']) && $_GET['scope_ref_id'] !== '' ? (int) $_GET['scope_ref_id'] : null;
    $refName = (string) ($_GET['scope_ref_name'] ?? '');

    $months = (int) ($_GET['months'] ?? 12);
    if ($months < 1)  $months = 12;
    if ($months > 36) $months = 36;

    // Ascending month list ending at the current month.
    $monthKeys = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $monthKeys[] = date('Y-m', strtotime("first day of -{$i} month"));
    }
    $currentMonth = date('Y-m');

    // Load this scope's recurring default + per-month overrides once.
    // Match on the relevant identifier — id for entities, name for type — rather than
    // the full scope key: the client sends only scope_ref_id for entities (no name),
    // so comparing the name snapshot would never match the stored row.
    $default  = null;
    $overrides = [];
    $stmt = $pdo->prepare("SELECT * FROM budgets WHERE is_active = 1 AND scope_type = :st");
    $stmt->execute([':st' => $scopeType]);
    foreach ($stmt->fetchAll() as $r) {
        $match = match ($scopeType) {
            'overall' => true,
            'type'    => (string) ($r['scope_ref_name'] ?? '') === $refName,
            default   => $r['scope_ref_id'] !== null && (int) $r['scope_ref_id'] === $refId,
        };
        if (!$match) continue;
        if ($r['period'] === null) $default = $r;
        else                       $overrides[$r['period']] = $r;
    }

    $series           = [];
    $monthsWithBudget = 0;
    foreach ($monthKeys as $mk) {
        $def       = $overrides[$mk] ?? $default;
        $hasBudget = $def !== null;
        $amount    = $hasBudget ? (float) $def['amount']   : 0.0;
        $warn      = $hasBudget ? (int) $def['warn_pct']   : 80;
        $danger    = $hasBudget ? (int) $def['danger_pct'] : 100;
        $spent     = budgetSpentForMonth($pdo, $scopeType, $refId, $refName, $mk);
        if ($hasBudget) $monthsWithBudget++;
        $series[] = [
            'month'      => $mk,
            'budget'     => $amount,
            'spent'      => $spent,
            'pct'        => $amount > 0 ? round($spent / $amount * 100, 1) : 0.0,
            'status'     => $hasBudget ? budgetStatus($spent, $amount, $warn, $danger) : 'none',
            'has_budget' => $hasBudget,
            'over'       => $hasBudget && $amount > 0 && $spent > $amount,
        ];
    }

    // Summary stats
    $n            = count($series);
    $totalSpent   = array_sum(array_column($series, 'spent'));
    $budgetMonths = array_values(array_filter($series, fn($s) => $s['has_budget']));
    $bm           = count($budgetMonths);
    $maxMonth = null; $maxAmt = 0.0;
    foreach ($series as $s) {
        if ($s['spent'] > $maxAmt) { $maxAmt = $s['spent']; $maxMonth = $s['month']; }
    }
    $summary = [
        'months_tracked'     => $n,
        'months_with_budget' => $monthsWithBudget,
        'total_spent'        => $totalSpent,
        'avg_spent'          => $n  ? $totalSpent / $n : 0.0,
        'avg_budget'         => $bm ? array_sum(array_column($budgetMonths, 'budget')) / $bm : 0.0,
        'avg_pct'            => $bm ? round(array_sum(array_column($budgetMonths, 'pct')) / $bm, 1) : 0.0,
        'over_count'         => count(array_filter($budgetMonths, fn($s) => $s['over'])),
        'near_count'         => count(array_filter($budgetMonths, fn($s) => $s['status'] === 'near')),
        'max_spent'          => ['month' => $maxMonth, 'amount' => $maxAmt],
    ];

    [$selLabel, $selHint] = budgetResolveLabel($pdo, $scopeType, $refId, $refName);

    jsonResponse([
        'scope' => [
            'scope_type'     => $scopeType,
            'scope_ref_id'   => $refId,
            'scope_ref_name' => $refName,
            'label'          => $scopeType === 'overall' ? '' : $selLabel,
            'display_hint'   => $selHint,
        ],
        'months'          => $series,
        'scopes'          => budgetScopeOptions($pdo),
        'summary'         => $summary,
        'recommendations' => buildBudgetRecommendations($series, $currentMonth),
    ]);
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
    $eventId = (isset($_POST['event_id']) && $_POST['event_id'] !== '') ? (int) $_POST['event_id'] : null;
    return [
        ':order_date'    => trim($_POST['order_date'] ?? ''),
        ':event_date'    => trim($_POST['event_date'] ?? ''),
        ':event_id'      => $eventId,
        ':title'         => trim($_POST['title'] ?? ''),
        ':idol'          => trim($_POST['idol'] ?? ''),
        ':type'          => trim($_POST['type'] ?? ''),
        ':price_per_qty' => (float) ($_POST['price_per_qty'] ?? 0),
        ':qty'           => (int) ($_POST['qty'] ?? 1),
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
