<?php
require __DIR__ . '/config.php';
requireAuth();
requireAdmin();
?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('backup.title') ?> - Numa Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #7c3aed; --primary-hover: #6d28d9; }
        body { background: #f3f4f6; font-size: 14px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .table th { font-size: 12px; text-transform: uppercase; color: #6b7280; }
        /* --- Mobile --- */
        .card-body.p-0 { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="password"],
            select, textarea { font-size: 16px !important; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } if (!opts.cache) opts.cache = 'no-store'; return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-database"></i> <?= t('backup.title') ?> <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <div class="d-flex align-items-center">
            <?= langSwitcher() ?>
            <a href="index.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-speedometer2"></i><span class="d-none d-sm-inline"> <?= t('nav.dashboard') ?></span></a>
            <a href="items.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-list-ul"></i><span class="d-none d-sm-inline"> <?= t('nav.items') ?></span></a>
            <a href="report.php" class="btn btn-outline-light btn-sm me-2"><i class="bi bi-bar-chart-line"></i><span class="d-none d-sm-inline"> <?= t('nav.report') ?></span></a>
            <?php $u = currentUser(); if (AUTH_ENABLED && $u): ?>
            <div class="btn-group">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i><span class="d-none d-sm-inline"> <?= htmlspecialchars($u['display_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($u['username']) ?> (<?= $u['role'] ?>)</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="users.php"><i class="bi bi-people-fill"></i> <?= t('nav.users') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= currentLang() === 'th' ? 'help.php' : 'help_en.php' ?>"><i class="bi bi-question-circle"></i> <?= t('nav.help') ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="bi bi-box-arrow-right"></i> <?= t('nav.logout') ?></a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-clock-history"></i> <?= t('backup.snapshots') ?></strong>
                    <button class="btn btn-primary btn-sm" onclick="showCreateModal()">
                        <i class="bi bi-plus-lg"></i> <?= t('backup.create') ?>
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th><?= t('backup.filename') ?></th>
                                <th class="text-end" style="width:100px"><?= t('backup.size') ?></th>
                                <th style="width:160px"><?= t('backup.created') ?></th>
                                <th style="width:160px"></th>
                            </tr>
                        </thead>
                        <tbody id="backupList">
                            <tr><td colspan="5" class="text-center text-muted py-4"><?= t('common.loading') ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong><?= t('backup.info') ?></strong></div>
                <div class="card-body py-2 small">
                    <div class="mb-2"><i class="bi bi-info-circle text-primary"></i> <?= t('backup.info1') ?></div>
                    <div class="mb-2"><i class="bi bi-shield-check text-success"></i> <?= t('backup.info2') ?></div>
                    <div><i class="bi bi-exclamation-triangle text-warning"></i> <?= t('backup.info3') ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header py-2"><strong><?= t('backup.upload_title') ?></strong></div>
                <div class="card-body py-2">
                    <form id="uploadForm">
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm" id="uploadFile" accept=".sqlite">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="uploadBackup()">
                            <i class="bi bi-upload"></i> <?= t('backup.upload') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('backup.create') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small"><?= t('backup.label') ?></label>
                    <input type="text" class="form-control form-control-sm" id="backupLabel" placeholder="<?= t('backup.label_ph') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="createBackup()">
                    <i class="bi bi-database-add"></i> <?= t('backup.create_btn') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirm Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="bi bi-exclamation-triangle"></i> <?= t('backup.confirm_restore') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= t('backup.restore_from') ?> <strong id="restoreName"></strong>?</p>
                <p class="small text-muted mb-0"><?= t('backup.restore_warn') ?></p>
                <input type="hidden" id="restoreFile">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-warning btn-sm" onclick="confirmRestore()">
                    <i class="bi bi-arrow-counterclockwise"></i> <?= t('backup.restore') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('backup.confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= t('backup.delete_q') ?> <strong id="deleteName"></strong>?</p>
                <input type="hidden" id="deleteFile">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> <?= t('common.delete') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js"></script>
<script>
const $ = id => document.getElementById(id);

document.addEventListener('DOMContentLoaded', loadBackups);

async function loadBackups() {
    const res = await fetch('api.php?action=backup_list').then(r => r.json());
    const backups = res.backups;

    if (backups.length === 0) {
        $('backupList').innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">${t('backup.none')}</td></tr>`;
        return;
    }

    $('backupList').innerHTML = backups.map((b, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td><i class="bi bi-file-earmark-zip text-primary"></i> <strong>${esc(b.filename)}</strong></td>
            <td class="text-end">${formatSize(b.size)}</td>
            <td>${b.created}</td>
            <td class="text-end">
                <a href="api.php?action=backup_download&filename=${encodeURIComponent(b.filename)}" class="btn btn-outline-primary btn-sm px-1 py-0" title="${t('backup.download')}"><i class="bi bi-download"></i></a>
                <button class="btn btn-outline-warning btn-sm px-1 py-0" onclick="showRestore('${escJs(b.filename)}')" title="${t('backup.restore')}"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="showDelete('${escJs(b.filename)}')" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function showCreateModal() {
    $('backupLabel').value = '';
    new bootstrap.Modal($('createModal')).show();
}

async function createBackup() {
    const body = new FormData();
    body.append('action', 'backup_create');
    body.append('label', $('backupLabel').value);
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('createModal')).hide();
    alert(t('backup.created_alert', { name: res.filename }));
    loadBackups();
}

function showRestore(filename) {
    $('restoreFile').value = filename;
    $('restoreName').textContent = filename;
    new bootstrap.Modal($('restoreModal')).show();
}

async function confirmRestore() {
    const body = new FormData();
    body.append('action', 'backup_restore');
    body.append('filename', $('restoreFile').value);
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('restoreModal')).hide();
    alert(res.message);
    loadBackups();
}

function showDelete(filename) {
    $('deleteFile').value = filename;
    $('deleteName').textContent = filename;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'backup_delete');
    body.append('filename', $('deleteFile').value);
    await fetch('api.php', { method: 'POST', body });
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadBackups();
}

async function uploadBackup() {
    const file = $('uploadFile').files[0];
    if (!file) { alert(t('backup.select_file')); return; }
    if (!file.name.endsWith('.sqlite')) { alert(t('backup.only_sqlite')); return; }

    const body = new FormData();
    body.append('action', 'backup_upload');
    body.append('file', file);
    const res = await fetch('backup_upload.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    alert(t('backup.uploaded_alert', { name: res.filename }));
    $('uploadFile').value = '';
    loadBackups();
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
