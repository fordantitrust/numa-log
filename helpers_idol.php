<?php

declare(strict_types=1);

/**
 * Helper functions for idol-related resolution and display.
 *
 * Loaded by config.php on every request.
 *
 *   resolveMemberGroup  — group_id for a member at a given order_date (membership-aware)
 *   resolveIdolByName   — look up idol_id from a name string (handles ambiguity)
 *   formatIdolDisplay   — render "Name [hint]" for the UI
 *   autoBackfillIdolId  — best-effort fill of items.idol_id after a new entity is created
 */

/**
 * Return the group_id for a member on a specific date.
 * Picks the primary membership whose start/end range covers the date.
 * Falls back to any non-primary one if no primary is found.
 * Returns null if no membership applies.
 */
function resolveMemberGroup(PDO $pdo, int $memberId, string $date): ?int
{
    if ($date === '') {
        $stmt = $pdo->prepare("
            SELECT group_id FROM idol_memberships
            WHERE member_id = :mid AND end_date IS NULL
            ORDER BY is_primary DESC, start_date DESC
            LIMIT 1
        ");
        $stmt->execute([':mid' => $memberId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT group_id FROM idol_memberships
            WHERE member_id = :mid
              AND (start_date IS NULL OR start_date <= :d)
              AND (end_date   IS NULL OR end_date   >= :d)
            ORDER BY is_primary DESC, start_date DESC
            LIMIT 1
        ");
        $stmt->execute([':mid' => $memberId, ':d' => $date]);
    }
    $r = $stmt->fetchColumn();
    return $r ? (int) $r : null;
}

/**
 * Look up entities by name. Returns:
 *   ['id' => int|null, 'ambiguous' => bool, 'candidates' => array]
 *
 *   - 1 member match  → ['id' => N, 'ambiguous' => false, 'candidates' => [the one]]
 *   - 0 matches       → ['id' => null, 'ambiguous' => false, 'candidates' => []]
 *   - >1 matches      → ['id' => null, 'ambiguous' => true,  'candidates' => [...]]
 *
 * Candidates list each entry as {id, name, display_hint, display}.
 */
function resolveIdolByName(PDO $pdo, string $name): array
{
    $name = trim($name);
    if ($name === '') {
        return ['id' => null, 'ambiguous' => false, 'candidates' => []];
    }

    $stmt = $pdo->prepare("
        SELECT id, name, display_hint
        FROM idol_entities
        WHERE name = :name AND category = 'member'
        ORDER BY id
    ");
    $stmt->execute([':name' => $name]);
    $rows = $stmt->fetchAll();

    $candidates = array_map(fn($r) => [
        'id'           => (int) $r['id'],
        'name'         => $r['name'],
        'display_hint' => $r['display_hint'] ?? '',
        'display'      => formatIdolDisplay($r),
    ], $rows);

    $count = count($candidates);
    if ($count === 1) {
        return ['id' => $candidates[0]['id'], 'ambiguous' => false, 'candidates' => $candidates];
    }
    return [
        'id'         => null,
        'ambiguous'  => $count > 1,
        'candidates' => $candidates,
    ];
}

/**
 * Render display name for an entity row.
 * Accepts associative array (with 'name' and optional 'display_hint').
 */
function formatIdolDisplay(array $entity): string
{
    $name = $entity['name'] ?? '';
    $hint = $entity['display_hint'] ?? '';
    return $hint !== '' ? "{$name} [{$hint}]" : $name;
}

/**
 * After creating/updating an entity, fill in items.idol_id for any unmapped items
 * whose `idol` text matches this entity's name AND the match is unambiguous.
 *
 * Returns the number of items updated.
 */
function autoBackfillIdolId(PDO $pdo, int $entityId): int
{
    $entStmt = $pdo->prepare("SELECT name, category FROM idol_entities WHERE id = :id");
    $entStmt->execute([':id' => $entityId]);
    $entity = $entStmt->fetch();
    if (!$entity || $entity['category'] !== 'member') {
        return 0;
    }

    $dupCount = (int) $pdo->query("
        SELECT COUNT(*) FROM idol_entities
        WHERE name = " . $pdo->quote($entity['name']) . " AND category = 'member'
    ")->fetchColumn();

    if ($dupCount !== 1) {
        return 0;
    }

    $stmt = $pdo->prepare("
        UPDATE items
        SET idol_id = :id
        WHERE idol_id IS NULL
          AND idol = :name
    ");
    $stmt->execute([':id' => $entityId, ':name' => $entity['name']]);
    return $stmt->rowCount();
}
