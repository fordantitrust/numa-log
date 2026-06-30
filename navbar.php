<?php

declare(strict_types=1);

/**
 * Shared top navbar partial — include after setting:
 *   $navActive  string  current page key: dashboard|report|budget|items|events|idols|types|users|backup|help
 *   $navIcon    string  bootstrap-icons class for the brand, without the "bi-" prefix removed (e.g. 'bi-list-ul')
 *   $navTitle   string  brand title text (already translated/escaped by the caller)
 *   $navExtra   string  (optional) raw HTML inserted after the main links, before the admin dropdown
 *
 * Requires config.php to already be loaded (t(), currentUser(), langSwitcher(), etc).
 */

$navLinks = [
    'dashboard' => ['index.php', 'bi-speedometer2', 'nav.dashboard'],
    'report'    => ['report.php', 'bi-bar-chart-line', 'nav.report'],
    'budget'    => ['budget.php', 'bi-piggy-bank', 'nav.budget'],
    'items'     => ['items.php', 'bi-list-ul', 'nav.items'],
    'events'    => ['events.php', 'bi-calendar-event', 'nav.events'],
    'idols'     => ['idols.php', 'bi-people', 'nav.idols'],
    'types'     => ['types.php', 'bi-tags', 'nav.types'],
];
$navUser = currentUser();
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi <?= $navIcon ?>"></i> <?= $navTitle ?> <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-2 gap-lg-0 ms-lg-auto mt-3 mt-lg-0">
            <?= langSwitcher() ?>
            <?php foreach ($navLinks as $key => [$href, $icon, $labelKey]):
                $cls = ($navActive === $key) ? 'btn btn-light btn-sm me-2 text-dark fw-semibold' : 'btn btn-outline-light btn-sm me-2';
            ?>
            <a href="<?= $href ?>" class="<?= $cls ?>"><i class="bi <?= $icon ?>"></i><span> <?= t($labelKey) ?></span></a>
            <?php endforeach; ?>
            <?= $navExtra ?? '' ?>
            <?php if (AUTH_ENABLED && $navUser): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i><span> <?= htmlspecialchars($navUser['display_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($navUser['username']) ?> (<?= $navUser['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($navUser['role'] === 'admin'): ?>
                    <?php if ($navActive !== 'users'): ?>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> <?= t('nav.users') ?></a></li>
                    <?php endif; ?>
                    <?php if ($navActive !== 'backup'): ?>
                    <li><a class="dropdown-item" href="backup.php"><i class="bi bi-database"></i> <?= t('nav.backup') ?></a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <?php if ($navActive !== 'help'): ?>
                    <li><a class="dropdown-item" href="<?= currentLang() === 'th' ? 'help.php' : 'help_en.php' ?>"><i class="bi bi-question-circle"></i> <?= t('nav.help') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> <?= t('nav.logout') ?></a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
</nav>
