<?php require __DIR__ . '/config.php'; requireAuth(); ?>
<!DOCTYPE html>
<html lang="<?= currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('idols.title') ?> - Numa Log</title>
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
        .badge-company { background: #dc2626; color: white; }
        .badge-group { background: #7c3aed; color: white; }
        .badge-unit { background: #0891b2; color: white; }
        .badge-member { background: #16a34a; color: white; }
        .tree-item { border-left: 2px solid #e5e7eb; padding-left: 1rem; margin-left: 0.5rem; }
        .tree-item.depth-0 { border-left: none; padding-left: 0; margin-left: 0; }
        .tree-row { padding: 6px 8px; border-radius: 6px; margin-bottom: 2px; transition: background .15s; }
        .tree-row:hover { background: #f3f0ff; }
        .tree-children { margin-top: 2px; }
        .stat-muted { color: #9ca3af; font-size: 12px; }
        /* Mobile */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 575.98px) {
            input[type="text"], input[type="date"], input[type="password"],
            select, textarea { font-size: 16px !important; }
            .tree-item { padding-left: .5rem; margin-left: .25rem; }
            .container-fluid { padding-left: .75rem; padding-right: .75rem; }
        }
    </style>
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken()) ?>">
</head>
<body>
<script>
window.fetch = (function(origFetch) { return function(url, opts = {}) { if (opts.body instanceof FormData) { const t = document.querySelector('meta[name="csrf-token"]')?.content; if (t && !opts.body.has('csrf_token')) opts.body.append('csrf_token', t); } if (!opts.cache) opts.cache = 'no-store'; return origFetch.call(this, url, opts); }; })(window.fetch);
</script>

<?php $navActive = 'idols'; $navIcon = 'bi-people'; $navTitle = t('idols.title'); require __DIR__ . '/navbar.php'; ?>

<div class="container-fluid py-3">
    <div class="row g-3">
        <!-- Tree View -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-diagram-3"></i> <?= t('idols.hierarchy') ?></strong>
                    <div>
                        <?php if (ALLOW_RESEED): ?>
                        <button class="btn btn-outline-secondary btn-sm me-1" onclick="seedData()">
                            <i class="bi bi-arrow-clockwise"></i> <?= t('idols.reseed') ?>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-primary btn-sm" onclick="showForm()">
                            <i class="bi bi-plus-lg"></i> <?= t('common.add') ?>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="treeContainer">
                    <div class="text-center text-muted py-4"><?= t('common.loading') ?></div>
                </div>
            </div>
        </div>

        <!-- Legend + Stats -->
        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2"><strong><?= t('idols.legend') ?></strong></div>
                <div class="card-body py-2">
                    <div class="mb-1"><span class="badge badge-company"><?= t('idols.legend_company') ?></span> - <?= t('idols.desc_company') ?></div>
                    <div class="mb-1"><span class="badge badge-group"><?= t('idols.legend_group') ?></span> - <?= t('idols.desc_group') ?></div>
                    <div class="mb-1"><span class="badge badge-unit"><?= t('idols.legend_unit') ?></span> - <?= t('idols.desc_unit') ?></div>
                    <div><span class="badge badge-member"><?= t('idols.legend_member') ?></span> - <?= t('idols.desc_member') ?></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2"><strong><?= t('idols.summary') ?></strong></div>
                <div class="card-body py-2" id="statsPanel">-</div>
            </div>

            <div class="card mb-3" id="ambiguousCard" style="display:none">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong class="text-warning"><i class="bi bi-exclamation-triangle"></i> <?= t('idols.ambiguous_title') ?></strong>
                    <span class="badge bg-warning text-dark" id="ambiguousBadge">0</span>
                </div>
                <div class="card-body py-2" id="ambiguousPanel">
                    <div class="text-muted small"><?= t('idols.ambiguous_desc') ?></div>
                    <button class="btn btn-outline-warning btn-sm w-100 mt-2" onclick="showAmbiguousModal()">
                        <i class="bi bi-tools"></i> <?= t('idols.resolve_conflicts') ?>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header py-2"><strong><?= t('idols.unmapped_title') ?></strong></div>
                <div class="card-body py-2" id="unmappedPanel">
                    <div class="text-muted"><?= t('common.loading') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formTitle"><?= t('idols.add_entity') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="entityForm">
                    <input type="hidden" id="eId">
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.name') ?></label>
                        <input type="text" class="form-control form-control-sm" id="eName" required onblur="suggestDisplayHint()">
                        <div class="form-text small text-warning" id="dupHintNotice" style="display:none">
                            <i class="bi bi-exclamation-circle"></i> <?= t('idols.dup_notice') ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.category') ?></label>
                        <select class="form-select form-select-sm" id="eCategory">
                            <option value="company"><?= t('idols.legend_company') ?></option>
                            <option value="group"><?= t('idols.legend_group') ?></option>
                            <option value="unit"><?= t('idols.legend_unit') ?></option>
                            <option value="member" selected><?= t('idols.legend_member') ?></option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.parent') ?></label>
                        <select class="form-select form-select-sm" id="eParent">
                            <option value=""><?= t('idols.none_top') ?></option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.display_hint') ?> <span class="text-muted"><?= t('idols.display_hint_help') ?></span></label>
                        <input type="text" class="form-control form-control-sm" id="eDisplayHint" placeholder="<?= t('idols.display_hint_ph') ?>">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.sort_order') ?></label>
                        <input type="number" class="form-control form-control-sm" id="eSort" value="0">
                    </div>
                </form>

                <!-- Membership panel — visible only for existing member entities -->
                <div id="membershipSection" style="display:none">
                    <hr class="my-3">
                    <h6 class="small text-muted mb-2"><i class="bi bi-people"></i> <?= t('idols.memberships') ?></h6>
                    <div id="membershipList" class="small mb-2"></div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="showAddMembershipForm()">
                            <i class="bi bi-plus-lg"></i> <?= t('idols.add_membership') ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showMoveForm()">
                            <i class="bi bi-arrow-right-circle"></i> <?= t('idols.move_group') ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveEntity()">
                    <i class="bi bi-check-lg"></i> <?= t('common.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Membership Modal (Add / Edit / Move) -->
<div class="modal fade" id="membershipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mbTitle"><?= t('idols.add_membership_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="membershipForm">
                    <input type="hidden" id="mbId">
                    <input type="hidden" id="mbMemberId">
                    <input type="hidden" id="mbMode" value="add">
                    <div class="mb-2">
                        <label class="form-label small"><?= t('common.group') ?></label>
                        <select class="form-select form-select-sm" id="mbGroup" required>
                            <option value=""><?= t('idols.select_group') ?></option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small"><?= t('idols.start_date') ?> <span class="text-muted"><?= t('idols.start_hint') ?></span></label>
                            <input type="date" class="form-control form-control-sm" id="mbStart">
                        </div>
                        <div class="col-6">
                            <label class="form-label small"><?= t('idols.end_date') ?> <span class="text-muted"><?= t('idols.end_hint') ?></span></label>
                            <input type="date" class="form-control form-control-sm" id="mbEnd">
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="mbPrimary" checked>
                        <label class="form-check-label small" for="mbPrimary"><?= t('idols.primary_label') ?></label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small"><?= t('idols.note') ?></label>
                        <input type="text" class="form-control form-control-sm" id="mbNote" placeholder="<?= t('idols.note_ph') ?>">
                    </div>
                    <div id="mbWarnings" class="alert alert-warning small py-1 px-2" style="display:none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveMembership()">
                    <i class="bi bi-check-lg"></i> <?= t('common.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ambiguous Mappings Modal -->
<div class="modal fade" id="ambiguousModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('idols.resolve_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted small mb-3">
                    <?= t('idols.resolve_desc') ?>
                </div>
                <div id="ambiguousContent">
                    <div class="text-muted"><?= t('common.loading') ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.close') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= t('idols.confirm_delete') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= t('idols.delete_prefix') ?> <strong id="delName"></strong>? <?= t('idols.delete_children') ?></p>
                <input type="hidden" id="delId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= t('common.cancel') ?></button>
                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete()"><i class="bi bi-trash"></i> <?= t('common.delete') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.I18N=<?= json_encode(loadLang(), JSON_UNESCAPED_UNICODE) ?>;window.LANG='<?= currentLang() ?>';</script>
<script src="assets/i18n.js?v=<?= APP_VERSION ?>"></script>
<script>
const $ = id => document.getElementById(id);
const fmt = n => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
let allEntities = [];
let allParents = [];

document.addEventListener('DOMContentLoaded', loadTree);

async function loadTree() {
    const res = await fetch('api.php?action=idol_entities_tree').then(r => r.json());
    allEntities = res.entities;
    allParents = res.parents;
    renderTree();
    renderStats();
    loadUnmapped();
    populateParentSelect();
    renderAmbiguousBadge(res.ambiguous_count || 0);
}

function renderAmbiguousBadge(count) {
    const card = $('ambiguousCard');
    if (count > 0) {
        $('ambiguousBadge').textContent = count;
        card.style.display = '';
    } else {
        card.style.display = 'none';
    }
}

function renderTree() {
    const byParent = {};
    const roots = [];
    allEntities.forEach(e => {
        const pid = e.parent_id || 'root';
        if (!byParent[pid]) byParent[pid] = [];
        byParent[pid].push(e);
        if (!e.parent_id) roots.push(e);
    });

    function buildNode(entity, depth) {
        const children = byParent[entity.id] || [];
        const badge = `<span class="badge badge-${entity.category}">${entity.category}</span>`;
        const stats = entity.total_price > 0
            ? `<span class="stat-muted ms-2">${entity.items_count} ${t('idols.items_label')} / ฿${fmt(entity.total_price)}</span>`
            : '';
        const mbIcon = (entity.category === 'member' && (entity.membership_count || 0) > 1)
            ? `<span class="ms-1 text-info" title="${entity.membership_count} memberships"><i class="bi bi-arrow-left-right"></i></span>`
            : '';
        const hint = (entity.display_hint || '').length
            ? ` <span class="stat-muted">[${escHtml(entity.display_hint)}]</span>`
            : '';
        const btns = `
            <button class="btn btn-outline-primary btn-sm px-1 py-0 ms-1" onclick="editEntity(${entity.id})" title="${t('common.edit')}"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteEntity(${entity.id}, '${escJs(entity.name)}')" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
        `;

        let html = `<div class="tree-item depth-${depth}">`;
        html += `<div class="tree-row d-flex align-items-center">
            ${badge}
            <strong class="ms-2">${escHtml(entity.name)}</strong>${hint}${mbIcon}
            ${stats}
            <span class="ms-auto">${btns}</span>
        </div>`;

        if (children.length > 0) {
            html += `<div class="tree-children">`;
            children.forEach(c => { html += buildNode(c, depth + 1); });
            html += `</div>`;
        }
        html += `</div>`;
        return html;
    }

    let html = '';
    roots.forEach(r => { html += buildNode(r, 0); });
    $('treeContainer').innerHTML = html || `<div class="text-muted text-center py-4">${t('idols.tree_empty')}</div>`;
}

function renderStats() {
    const cats = {};
    allEntities.forEach(e => {
        cats[e.category] = (cats[e.category] || 0) + 1;
    });
    const totalSpend = allEntities.reduce((s, e) => s + (e.total_price || 0), 0);
    $('statsPanel').innerHTML = `
        <div>${t('idols.stat_company')} <strong>${cats.company || 0}</strong></div>
        <div>${t('idols.stat_group')} <strong>${cats.group || 0}</strong></div>
        <div>${t('idols.stat_unit')} <strong>${cats.unit || 0}</strong></div>
        <div>${t('idols.stat_member')} <strong>${cats.member || 0}</strong></div>
        <div class="mt-2 pt-2 border-top">${t('idols.stat_total')} <strong>${allEntities.length}</strong></div>
    `;
}

async function loadUnmapped() {
    const mapped = new Set(allEntities.map(e => e.name));
    const res = await fetch('api.php?action=filters').then(r => r.json());
    const unmapped = res.idols.filter(n => n && n !== '-' && !mapped.has(n));

    if (unmapped.length === 0) {
        $('unmappedPanel').innerHTML = `<div class="text-success"><i class="bi bi-check-circle"></i> ${t('idols.all_mapped')}</div>`;
    } else {
        $('unmappedPanel').innerHTML = unmapped.map(n =>
            `<div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                <span>${escHtml(n)}</span>
                <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="quickAdd('${escJs(n)}')" title="${t('common.add')}"><i class="bi bi-plus"></i></button>
            </div>`
        ).join('');
    }
}

function populateParentSelect() {
    const sel = $('eParent');
    const val = sel.value;
    sel.innerHTML = `<option value="">${t('idols.none_top')}</option>`;
    allParents.forEach(p => {
        const cat = p.category.charAt(0).toUpperCase() + p.category.slice(1);
        sel.innerHTML += `<option value="${p.id}">[${cat}] ${escHtml(p.name)}</option>`;
    });
    sel.value = val;
}

// --- CRUD ---
function showForm(id = null) {
    $('eId').value = '';
    $('entityForm').reset();
    $('eCategory').value = 'member';
    $('eDisplayHint').value = '';
    $('formTitle').textContent = t('idols.add_entity');
    $('membershipSection').style.display = 'none';
    $('dupHintNotice').style.display = 'none';
    populateParentSelect();
    new bootstrap.Modal($('formModal')).show();
}

function editEntity(id) {
    const e = allEntities.find(x => x.id == id);
    if (!e) return;
    $('eId').value = e.id;
    $('eName').value = e.name;
    $('eCategory').value = e.category;
    $('eDisplayHint').value = e.display_hint || '';
    $('eSort').value = e.sort_order;
    $('formTitle').textContent = t('idols.edit_prefix', { name: e.name });
    populateParentSelect();
    $('eParent').value = e.parent_id || '';
    $('dupHintNotice').style.display = 'none';

    // Show membership panel for existing member entities
    if (e.category === 'member') {
        $('membershipSection').style.display = '';
        loadMemberships(e.id);
    } else {
        $('membershipSection').style.display = 'none';
    }
    new bootstrap.Modal($('formModal')).show();
}

function quickAdd(name) {
    showForm();
    $('eName').value = name;
    $('eCategory').value = 'member';
    suggestDisplayHint();
}

/**
 * Soft-suggest a display_hint when the entity name collides with an existing one.
 * Fires on blur of the name field. Non-blocking — user can ignore.
 */
async function suggestDisplayHint() {
    const name = $('eName').value.trim();
    if (!name) { $('dupHintNotice').style.display = 'none'; return; }

    // Skip if editing the same entity (id matches)
    const editingId = $('eId').value;
    const dup = allEntities.find(e =>
        e.category === 'member' && e.name === name && String(e.id) !== String(editingId)
    );
    if (!dup) { $('dupHintNotice').style.display = 'none'; return; }

    $('dupHintNotice').style.display = '';
    // Auto-fill hint from selected parent's name, if hint is currently empty
    if (!$('eDisplayHint').value.trim()) {
        const pid = $('eParent').value;
        const parent = allParents.find(p => String(p.id) === String(pid));
        if (parent) $('eDisplayHint').value = parent.name;
    }
}

async function saveEntity() {
    const form = $('entityForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    const body = new FormData();
    body.append('action', 'idol_entity_save');
    const id = $('eId').value;
    if (id) body.append('id', id);
    body.append('name', $('eName').value);
    body.append('category', $('eCategory').value);
    body.append('parent_id', $('eParent').value);
    body.append('sort_order', $('eSort').value);
    body.append('display_hint', $('eDisplayHint').value);

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    bootstrap.Modal.getInstance($('formModal')).hide();
    loadTree();
}

function deleteEntity(id, name) {
    $('delId').value = id;
    $('delName').textContent = name;
    new bootstrap.Modal($('deleteModal')).show();
}

async function confirmDelete() {
    const body = new FormData();
    body.append('action', 'idol_entity_delete');
    body.append('id', $('delId').value);
    await fetch('api.php', { method: 'POST', body });
    bootstrap.Modal.getInstance($('deleteModal')).hide();
    loadTree();
}

async function seedData() {
    if (!confirm(t('idols.reseed_confirm'))) return;
    const body = new FormData();
    body.append('action', 'seed');
    const res = await fetch('seed_idols.php', { method: 'POST', body }).then(r => r.json());
    alert(res.message);
    loadTree();
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escJs(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

// ─── Membership management ───────────────────────────────────────────────────
let currentMemberships = [];

async function loadMemberships(memberId) {
    const res = await fetch(`api.php?action=membership_list&member_id=${memberId}`).then(r => r.json());
    currentMemberships = res.data || [];
    renderMemberships();
}

function renderMemberships() {
    const list = $('membershipList');
    if (currentMemberships.length === 0) {
        list.innerHTML = `<div class="text-muted small">${t('idols.no_memberships')}</div>`;
        return;
    }
    list.innerHTML = currentMemberships.map(m => {
        const start = m.start_date || '<span class="text-muted">—</span>';
        const end   = m.end_date   || `<span class="text-success">${t('idols.current')}</span>`;
        const primary = m.is_primary ? `<span class="badge bg-primary">${t('idols.primary_badge')}</span>` : `<span class="badge bg-secondary">${t('idols.sub_badge')}</span>`;
        return `
            <div class="d-flex align-items-center justify-content-between py-1 border-bottom">
                <div>
                    <strong>${escHtml(m.group_display || m.group_name)}</strong>
                    <span class="text-muted ms-2">${start} → ${end}</span>
                    <span class="ms-2">${primary}</span>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm px-1 py-0" onclick="editMembership(${m.id})" title="${t('common.edit')}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-outline-danger btn-sm px-1 py-0" onclick="deleteMembership(${m.id})" title="${t('common.delete')}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    }).join('');
}

function populateGroupSelect() {
    const sel = $('mbGroup');
    sel.innerHTML = `<option value="">${t('idols.select_group')}</option>`;
    allParents.forEach(p => {
        if (p.category === 'group' || p.category === 'unit' || p.category === 'company') {
            const cat = p.category.charAt(0).toUpperCase() + p.category.slice(1);
            sel.innerHTML += `<option value="${p.id}">[${cat}] ${escHtml(p.name)}</option>`;
        }
    });
}

function showAddMembershipForm() {
    $('mbId').value = '';
    $('mbMemberId').value = $('eId').value;
    $('mbMode').value = 'add';
    $('mbTitle').textContent = t('idols.add_membership_title');
    $('mbWarnings').style.display = 'none';
    $('membershipForm').reset();
    $('mbPrimary').checked = true;
    populateGroupSelect();
    new bootstrap.Modal($('membershipModal')).show();
}

function showMoveForm() {
    $('mbId').value = '';
    $('mbMemberId').value = $('eId').value;
    $('mbMode').value = 'move';
    $('mbTitle').textContent = t('idols.move_title');
    $('mbWarnings').style.display = 'none';
    $('membershipForm').reset();
    $('mbPrimary').checked = true;
    $('mbStart').valueAsDate = new Date();        // default move date = today
    $('mbStart').focus();
    populateGroupSelect();
    new bootstrap.Modal($('membershipModal')).show();
}

function editMembership(id) {
    const m = currentMemberships.find(x => x.id == id);
    if (!m) return;
    $('mbId').value = m.id;
    $('mbMemberId').value = m.member_id;
    $('mbMode').value = 'edit';
    $('mbTitle').textContent = t('idols.edit_membership_title');
    $('mbWarnings').style.display = 'none';
    populateGroupSelect();
    $('mbGroup').value = m.group_id;
    $('mbStart').value = m.start_date || '';
    $('mbEnd').value   = m.end_date   || '';
    $('mbPrimary').checked = m.is_primary == 1;
    $('mbNote').value  = m.note || '';
    new bootstrap.Modal($('membershipModal')).show();
}

async function saveMembership() {
    const memberId = $('mbMemberId').value;
    const groupId  = $('mbGroup').value;
    if (!memberId || !groupId) { alert(t('idols.group_required')); return; }

    const mode = $('mbMode').value;
    const body = new FormData();

    if (mode === 'move') {
        const moveDate = $('mbStart').value;
        if (!moveDate) { alert(t('idols.move_date_required')); return; }
        body.append('action', 'membership_move');
        body.append('member_id',    memberId);
        body.append('new_group_id', groupId);
        body.append('move_date',    moveDate);
    } else {
        body.append('action', 'membership_save');
        if ($('mbId').value) body.append('id', $('mbId').value);
        body.append('member_id',  memberId);
        body.append('group_id',   groupId);
        body.append('start_date', $('mbStart').value);
        body.append('end_date',   $('mbEnd').value);
        body.append('is_primary', $('mbPrimary').checked ? 1 : 0);
        body.append('note',       $('mbNote').value);
    }

    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    if (res.warnings && res.warnings.length > 0) {
        $('mbWarnings').textContent = res.warnings.join(' ');
        $('mbWarnings').style.display = '';
    }
    bootstrap.Modal.getInstance($('membershipModal')).hide();
    loadMemberships(memberId);
    loadTree();
}

async function deleteMembership(id) {
    if (!confirm(t('idols.del_membership_confirm'))) return;
    const body = new FormData();
    body.append('action', 'membership_delete');
    body.append('id', id);
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    loadMemberships($('mbMemberId').value || $('eId').value);
    loadTree();
}

// ─── Ambiguous mappings ──────────────────────────────────────────────────────

async function showAmbiguousModal() {
    new bootstrap.Modal($('ambiguousModal')).show();
    $('ambiguousContent').innerHTML = `<div class="text-muted">${t('common.loading')}</div>`;
    const res = await fetch('api.php?action=ambiguous_list').then(r => r.json());
    const items = res.data || [];
    if (items.length === 0) {
        $('ambiguousContent').innerHTML = `<div class="text-success"><i class="bi bi-check-circle"></i> ${t('idols.no_ambiguous')}</div>`;
        return;
    }
    $('ambiguousContent').innerHTML = items.map(it => `
        <div class="border rounded p-2 mb-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>${escHtml(it.name)}</strong>
                <span class="badge bg-warning text-dark">${it.items_count} ${t('idols.items_label')}</span>
            </div>
            <div class="small text-muted mb-1">${t('idols.pick_entity')}</div>
            <div class="d-flex flex-wrap gap-1">
                ${it.candidates.map(c => `
                    <button class="btn btn-outline-primary btn-sm"
                            onclick="bulkRemap('${escJs(it.name)}', ${c.id})">
                        ${escHtml(c.display)}
                    </button>
                `).join('')}
            </div>
        </div>
    `).join('');
}

async function bulkRemap(name, idolId) {
    if (!confirm(t('idols.bulk_confirm', { name, id: idolId }))) return;
    const body = new FormData();
    body.append('action', 'item_bulk_remap');
    body.append('idol_name', name);
    body.append('idol_id',   idolId);
    const res = await fetch('api.php', { method: 'POST', body }).then(r => r.json());
    if (res.error) { alert(res.error); return; }
    alert(t('idols.remapped', { n: res.updated }));
    showAmbiguousModal();
    loadTree();
}
</script>
</body>
</html>
