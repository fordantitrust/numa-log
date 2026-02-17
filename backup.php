<?php
require __DIR__ . '/config.php';
requireAuth();
requireAdmin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Idol Items Purchased</title>
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
    </style>
</head>
<body>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-database"></i> Backup & Restore</span>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-clock-history"></i> Backup Snapshots</strong>
                    <button class="btn btn-primary btn-sm" onclick="showCreateModal()">
                        <i class="bi bi-plus-lg"></i> Create Backup
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Filename</th>
                                <th class="text-end" style="width:100px">Size</th>
                                <th style="width:160px">Created</th>
                                <th style="width:160px"></th>
                            </tr>
                        </thead>
                        <tbody id="backupList">
                            <tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Info</strong></div>
                <div class="card-body py-2 small">
                    <div class="mb-2"><i class="bi bi-info-circle text-primary"></i> Backups are full copies of the SQLite database file.</div>
                    <div class="mb-2"><i class="bi bi-shield-check text-success"></i> Before restore, an auto-backup is created automatically.</div>
                    <div><i class="bi bi-exclamation-triangle text-warning"></i> Restore will replace ALL current data.</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header py-2"><strong>Upload Backup</strong></div>
                <div class="card-body py-2">
                    <form id="uploadForm">
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm" id="uploadFile" accept=".sqlite">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="uploadBackup()">
                            <i class="bi bi-upload"></i> Upload
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
                <h5 class="modal-title">Create Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Label (optional)</label>
                    <input type="text" class="form-control form-control-sm" id="backupLabel" placeholder="e.g. before_update">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="createBackup()">
                    <i class="bi bi-database-add"></i> Create
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
                <h5 class="modal-title text-warning"><i class="bi bi-exclamation-triangle"></i> Confirm Restore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Restore from <strong id="restoreName"></strong>?</p>
                <p class="small text-muted mb-0">This will replace ALL current data. An auto-backup will be created first.</p>
                <input type="hidden" id="restoreFile">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning btn-sm" onclick="confirmRestore()">
                    <i class="bi bi-arrow-counterclockwise"></i> Restore
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
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete backup <strong id="deleteName"></strong>?</p>
                <input type="hidden" id="deleteFile">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);

document.addEventListener('DOMContentLoaded', loadBackups);

async function loadBackups() {
    const res = await fetch('api.php?action=backup_list').then(r => r.json());
    const backups = res.backups;

    if (backups.length === 0) {
        $('backupList').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No backups yet.</td></tr>';
        return;
    }

    $('backupList').innerHTML = backups.map((b, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td><i class="bi bi-file-earmark-zip text-primary"></i> <strong>${esc(b.filename)}</strong></td>
            <td class="text-end">${formatSize(b.size)}</td>
            <td>${b.created}</td>
            <td class="text-end">
                <a href="api.php?action=backup_download&filename=${encodeURIComponent(b.filename)}" class="btn btn-outline-primary btn-sm px-1 py-0" title="Download"><i class="bi bi-download"></i></a>
                <button class="btn btn-outline-warning btn-sm px-1 py-0" onclick="showRestore('${escJs(b.filename)}')" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="showDelete('${escJs(b.filename)}')" title="Delete"><i class="bi bi-trash"></i></button>
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
    alert('Backup created: ' + res.filename);
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
    if (!file) { alert('Please select a .sqlite file'); return; }
    if (!file.name.endsWith('.sqlite')) { alert('Only .sqlite files are allowed'); return; }

    const body = new FormData();
    body.append('action', 'backup_upload');
    body.append('file', file);
    const res = await fetch('backup_upload.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    alert('Uploaded: ' + res.filename);
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
