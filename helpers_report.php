<?php

declare(strict_types=1);

/**
 * Report-exclusion helpers (schema v12).
 *
 * A type category flagged `exclude_from_reports = 1` represents spending the user
 * records but does not want counted in the normal totals — travel costs, gifts for
 * other people, resale stock. Its items are filtered out of aggregates by default
 * and put back when the request carries `include_excluded=1`.
 *
 * Nothing is ever hidden silently: every surface that filters also reports the
 * excluded slice (see excludedTypesTotals()).
 *
 * ── Deliberate carve-outs — do NOT "make these consistent" ────────────────────
 * The rule behind all three: when the user points at a type directly, they get
 * the full amount.
 *   1. budgetSpentForMonth() scope_type='type' — the budget targets that type.
 *   2. handleReportTypeDetail — a drill-down into one named type.
 *   3. handleList / export.php when `type[]` is supplied — the user filtered to it.
 *
 * ── Queries that must never be filtered ───────────────────────────────────────
 *   handleGet (single item for the edit modal), every write, handleFilters
 *   (the type dropdown must still offer excluded types or they are unreachable),
 *   the month/year selector enumerations, the ambiguous-idol data-quality panels,
 *   handleTypeList (Manage Types must show the excluded type's real totals — that
 *   is the point of the page), and handleEventList.
 *
 * ── events.php vs the Event report tabs ───────────────────────────────────────
 * handleEventList is deliberately untouched. Ticket detection is attendance
 * bookkeeping, not spend reporting: a type can be both a ticket type and excluded
 * (a ticket bought for a friend), and filtering it would make the event report a
 * false "missing ticket". Same for unassigned_same_date, which drives auto-assign.
 * Rule: events.php is operational and unfiltered; the Event tabs in report.php are
 * analytical and filtered.
 */

/**
 * Request-level opt-in: `include_excluded=1` turns the exclusion off.
 *
 * Read once from the request and memoised rather than threaded through every
 * handler — budgetSpentForMonth() is reached two call levels deep via two
 * different chains, handleReportDashboard builds its queries inside a closure,
 * and export.php is a separate entry point. A free function works in all three
 * without any plumbing.
 */
function includeExcludedTypes(): bool
{
    static $v = null;
    if ($v === null) {
        $raw = (string) ($_REQUEST['include_excluded'] ?? '');
        $v = ($raw === '1' || $raw === 'true');
    }
    return $v;
}

/**
 * Bare predicate for pushing into a $where[] array. Returns '' when the request
 * opted in to include excluded types.
 *
 * Parameter-free by design: it splices into any query without touching that
 * query's bind array and cannot collide with a named placeholder. Both
 * items.type and type_categories.name are NOT NULL, so the classic
 * "NOT IN (subquery containing NULL) matches nothing" trap cannot fire. The
 * subquery is unaliased, so it cannot collide with the outer `tc` alias used by
 * handleTypeList and handleEventList.
 */
function excludedTypesPredicate(string $col = 'i.type'): string
{
    if (includeExcludedTypes()) {
        return '';
    }
    return "{$col} NOT IN (SELECT name FROM type_categories WHERE exclude_from_reports = 1)";
}

/** The same predicate prefixed with AND (or WHERE), or '' when opted in. */
function excludedTypesClause(string $col = 'i.type', string $keyword = 'AND'): string
{
    $p = excludedTypesPredicate($col);
    return $p === '' ? '' : " {$keyword} {$p}";
}

/** Names of the flagged types — drives the banners and the client-side checks. */
function excludedTypeNames(PDO $pdo): array
{
    return $pdo->query("SELECT name FROM type_categories WHERE exclude_from_reports = 1 ORDER BY name")
        ->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Totals for the slice that WAS hidden — the counterpart that guarantees nothing
 * vanishes silently.
 *
 * $extraWhere is appended verbatim and must start with " AND "; it uses alias `i`.
 * Deliberately ignores includeExcludedTypes(): this is always the excluded-only
 * slice, so the banner can state the same number in both modes.
 */
function excludedTypesTotals(PDO $pdo, string $extraWhere = '', array $params = []): array
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS items,
               COALESCE(SUM(i.qty), 0) AS total_qty,
               COALESCE(SUM(i.price_per_qty * i.qty), 0) AS total_price
        FROM items i
        WHERE i.type IN (SELECT name FROM type_categories WHERE exclude_from_reports = 1)
        {$extraWhere}
    ");
    $stmt->execute($params);
    $r = $stmt->fetch() ?: [];
    return [
        'items' => (int) ($r['items'] ?? 0),
        'total_qty' => (int) ($r['total_qty'] ?? 0),
        'total_price' => (float) ($r['total_price'] ?? 0),
    ];
}

/** Per-type breakdown of the excluded slice — feeds the muted section on the By Type tab. */
function excludedTypesByType(PDO $pdo, string $extraWhere = '', array $params = []): array
{
    $stmt = $pdo->prepare("
        SELECT i.type,
               COUNT(*) AS items,
               COALESCE(SUM(i.qty), 0) AS total_qty,
               COALESCE(SUM(i.price_per_qty * i.qty), 0) AS total_price
        FROM items i
        WHERE i.type IN (SELECT name FROM type_categories WHERE exclude_from_reports = 1)
        {$extraWhere}
        GROUP BY i.type
        ORDER BY total_price DESC
    ");
    $stmt->execute($params);
    return array_map(fn($r) => [
        'type' => $r['type'],
        'items' => (int) $r['items'],
        'total_qty' => (int) $r['total_qty'],
        'total_price' => (float) $r['total_price'],
    ], $stmt->fetchAll());
}
