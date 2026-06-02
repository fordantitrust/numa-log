/**
 * Shared budget rendering helpers. Load AFTER i18n.js (uses global t()).
 * Used by budget.php, index.php (dashboard card) and report.php (Budget tab).
 */
(function () {
    const STATUS_COLOR = { ok: 'success', near: 'warning', over: 'danger' };
    const SCOPE_BADGE  = {
        overall: 'bg-dark', type: 'bg-info', group: 'bg-primary',
        company: 'bg-danger', member: 'bg-warning text-dark',
    };

    function fmtMoney(n) {
        return '฿' + new Intl.NumberFormat('th-TH', { maximumFractionDigits: 0 }).format(Math.round(n || 0));
    }
    function escHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function scopeTypeLabel(t_) { return t('budget.scope_' + t_); }

    function scopeLabel(b) {
        if (b.scope_type === 'overall') return t('budget.scope_overall');
        let label = b.label || b.scope_ref_name || '';
        if (b.display_hint) label += ' (' + b.display_hint + ')';
        return label;
    }

    function statusText(s) {
        return s === 'over' ? t('budget.status_over')
             : s === 'near' ? t('budget.status_near')
             : t('budget.status_ok');
    }

    /**
     * One budget progress block (label + bar + spent/remaining line).
     * opts.actions: optional HTML appended to the header (edit/delete buttons).
     * opts.tag: optional HTML appended after the label (e.g. default/custom badge).
     */
    function renderBudgetBar(b, opts) {
        opts = opts || {};
        const color = STATUS_COLOR[b.status] || 'success';
        const pct   = Math.max(0, Math.min(b.pct, 100));
        const over  = b.remaining < 0;
        const badge = SCOPE_BADGE[b.scope_type] || 'bg-secondary';

        const remainHtml = over
            ? `<span class="text-danger fw-semibold">${t('budget.over_by')} ${fmtMoney(-b.remaining)}</span>`
            : `<span class="text-muted">${t('budget.remaining')} ${fmtMoney(b.remaining)}</span>`;

        return `
        <div class="budget-bar mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-truncate">
                    <span class="badge ${badge}" style="font-weight:500">${escHtml(scopeTypeLabel(b.scope_type))}</span>
                    <strong>${escHtml(scopeLabel(b))}</strong>
                    ${opts.tag || ''}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="small">${fmtMoney(b.spent)} / ${fmtMoney(b.amount)}</span>
                    ${opts.actions || ''}
                </span>
            </div>
            <div class="progress" style="height:8px">
                <div class="progress-bar bg-${color}" role="progressbar" style="width:${pct}%"></div>
            </div>
            <div class="d-flex justify-content-between small mt-1">
                <span class="text-${color}">${t('budget.pct_used', { n: b.pct })} · ${statusText(b.status)}</span>
                ${remainHtml}
            </div>
        </div>`;
    }

    window.Budget = { fmtMoney, escHtml, scopeTypeLabel, scopeLabel, statusText, renderBudgetBar, STATUS_COLOR };
})();
