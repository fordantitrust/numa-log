<?php
require __DIR__ . '/config.php';
requireAuth();
$me = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Numa Log</title>
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
        .badge-admin { background: #dc2626; color: white; }
        .badge-user { background: #16a34a; color: white; }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<nav class="navbar navbar-dark" style="background:var(--primary)">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-people-fill"></i> User Management <span class="badge bg-light text-dark fw-normal" style="font-size:.6rem;vertical-align:middle">v<?= APP_VERSION ?></span></span>
        <a href="index.php" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-people-fill"></i> Users</strong>
                    <?php if ($me['role'] === 'admin'): ?>
                    <button class="btn btn-primary btn-sm" onclick="showForm()">
                        <i class="bi bi-plus-lg"></i> Add User
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Username</th>
                                <th>Display Name</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody id="userList">
                            <tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Current User</strong></div>
                <div class="card-body py-2">
                    <div><strong><?= htmlspecialchars($me['display_name']) ?></strong></div>
                    <div class="small text-muted"><?= htmlspecialchars($me['username']) ?></div>
                    <div class="mt-1"><span class="badge badge-<?= $me['role'] ?>"><?= $me['role'] ?></span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header py-2"><strong>Change My Password</strong></div>
                <div class="card-body py-2">
                    <form id="pwForm">
                        <div class="mb-2">
                            <input type="password" class="form-control form-control-sm" id="pwCurrent" placeholder="Current password" required>
                        </div>
                        <div class="mb-2">
                            <input type="password" class="form-control form-control-sm" id="pwNew" placeholder="New password" required>
                        </div>
                        <div class="mb-2">
                            <input type="password" class="form-control form-control-sm" id="pwConfirm" placeholder="Confirm new password" required>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100" onclick="changeMyPassword()">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="uId">
                    <div class="mb-2">
                        <label class="form-label small">Username</label>
                        <input type="text" class="form-control form-control-sm" id="uUsername" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Display Name</label>
                        <input type="text" class="form-control form-control-sm" id="uDisplayName" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Password <span class="text-muted" id="pwHint">(required)</span></label>
                        <input type="password" class="form-control form-control-sm" id="uPassword">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Role</label>
                        <select class="form-select form-select-sm" id="uRole">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveUser()">
                    <i class="bi bi-check-lg"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete user <strong id="delName"></strong>?</p>
                <input type="hidden" id="delId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash"></i> Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const $ = id => document.getElementById(id);
const ME = <?= json_encode($me) ?>;
let allUsers = [];

document.addEventListener('DOMContentLoaded', loadUsers);

async function loadUsers() {
    const res = await fetch('api_users.php?action=list').then(r => r.json());
    allUsers = res.users;
    renderTable();
}

function renderTable() {
    if (allUsers.length === 0) {
        $('userList').innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No users.</td></tr>';
        return;
    }
    $('userList').innerHTML = allUsers.map((u, i) => {
        const isMe = u.id == ME.id;
        const badge = u.role === 'admin' ? '<span class="badge badge-admin">admin</span>' : '<span class="badge badge-user">user</span>';
        const btns = ME.role === 'admin' ? `
            <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editUser(${u.id})" title="Edit"><i class="bi bi-pencil"></i></button>
            ${!isMe ? `<button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteUser(${u.id}, '${escJs(u.username)}')" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
        ` : '';
        return `<tr${isMe ? ' class="table-active"' : ''}>
            <td class="text-muted">${i + 1}</td>
            <td><strong>${esc(u.username)}</strong>${isMe ? ' <span class="badge bg-secondary">you</span>' : ''}</td>
            <td>${esc(u.display_name)}</td>
            <td>${badge}</td>
            <td class="small text-muted">${u.last_login || '-'}</td>
            <td>${btns}</td>
        </tr>`;
    }).join('');
}

function showForm() {
    $('uId').value = '';
    $('userForm').reset();
    $('uRole').value = 'user';
    $('formTitle').textContent = 'Add User';
    $('uUsername').readOnly = false;
    $('uPassword').required = true;
    $('pwHint').textContent = '(required)';
    new bootstrap.Modal($('formModal')).show();
}

function editUser(id) {
    const u = allUsers.find(x => x.id == id);
    if (!u) return;
    $('uId').value = u.id;
    $('uUsername').value = u.username;
    $('uUsername').readOnly = true;
    $('uDisplayName').value = u.display_name;
    $('uPassword').value = '';
    $('uPassword').required = false;
    $('pwHint').textContent = '(leave blank to keep)';
    $('uRole').value = u.role;
    $('formTitle').textContent = 'Edit: ' + u.username;
    new bootstrap.Modal($('formModal')).show();
}

async function saveUser() {
    const form = $('userForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const id = $('uId').value;
    if (!id && !$('uPassword').value) {
        alert('Password is required for new users.');
        return;
    }

    const body = new FormData();
    body.append('action', 'save');
    if (id) body.append('id', id);
    body.append('username', $('uUsername').value);
    body.append('display_name', $('uDisplayName').value);
    if ($('uPassword').value) body.append('password', $('uPassword').value);
    body.append('role', $('uRole').value);

    const res = await fetch('api_users.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadUsers();
}

function deleteUser(id, name) {
    $('delId').value = id;
    $('delName').textContent = name;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'delete');
    body.append('id', $('delId').value);
    const res = await fetch('api_users.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadUsers();
}

async function changeMyPassword() {
    const current = $('pwCurrent').value;
    const pw = $('pwNew').value;
    const confirm = $('pwConfirm').value;
    if (!current || !pw || !confirm) { alert('Please fill all fields.'); return; }
    if (pw !== confirm) { alert('New passwords do not match.'); return; }
    if (pw.length < 4) { alert('Password must be at least 4 characters.'); return; }

    const body = new FormData();
    body.append('action', 'change_password');
    body.append('current_password', current);
    body.append('new_password', pw);

    const res = await fetch('api_users.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    alert('Password changed successfully.');
    $('pwForm').reset();
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
</script>
</body>
</html>
