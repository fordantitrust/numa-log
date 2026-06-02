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

    // Localized "June 2026" label for a YYYY-MM value.
    function fmtMonthLabel(ym) {
        if (!ym) return '';
        const [y, m] = ym.split('-').map(Number);
        const months = (window.I18N && window.I18N['date.months_long']) || [];
        return (months[m - 1] || String(m)) + ' ' + y;
    }
    // Short axis label, e.g. "Jun 26".
    function fmtMonthShort(ym) {
        if (!ym) return '';
        const [y, m] = ym.split('-').map(Number);
        const months = (window.I18N && window.I18N['date.months_long']) || [];
        return (months[m - 1] || String(m)).slice(0, 3) + ' ' + String(y).slice(-2);
    }

    // ── Budget Insights: multi-month spend-vs-budget analytics ──────────────────
    // Self-contained module shared by budget.php (Insights tab) and report.php
    // (Budget tab). Mount once into a container div; it injects its own markup,
    // wires the scope/range selectors and renders KPIs, two charts and tips.
    const CHART_HEX = { ok: '#10b981', near: '#f59e0b', over: '#ef4444', none: '#94a3b8' };
    const REC_STYLE = {
        danger:  { cls: 'danger',  icon: 'exclamation-octagon' },
        warning: { cls: 'warning', icon: 'exclamation-triangle' },
        info:    { cls: 'primary', icon: 'info-circle' },
        success: { cls: 'success', icon: 'check-circle' },
    };

    const _states = new WeakMap();

    function scopeKey(s) {
        return s.scope_type + '|' + (s.scope_ref_id || '') + '|' + (s.scope_ref_name || '');
    }
    function scopeOptionLabel(s) {
        if (s.scope_type === 'overall') return t('budget.scope_overall');
        let label = (s.label || s.scope_ref_name || '') + '';
        if (s.display_hint) label += ' (' + s.display_hint + ')';
        return scopeTypeLabel(s.scope_type) + ' · ' + label;
    }

    function insightsMarkup() {
        return `
        <div class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <label class="small text-muted mb-0">${escHtml(t('budget.scope_select_label'))}</label>
                <select class="form-select form-select-sm bi-scope" style="width:auto"></select>
                <label class="small text-muted mb-0 ms-2">${escHtml(t('budget.range_label'))}</label>
                <select class="form-select form-select-sm bi-range" style="width:auto">
                    <option value="6">${escHtml(t('budget.range_6'))}</option>
                    <option value="12" selected>${escHtml(t('budget.range_12'))}</option>
                    <option value="24">${escHtml(t('budget.range_24'))}</option>
                </select>
            </div>
        </div>
        <div class="bi-kpis row g-2 mb-3"></div>
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header py-2"><strong><i class="bi bi-bar-chart-line"></i> ${escHtml(t('budget.chart_monthly_title'))}</strong></div>
                    <div class="card-body"><canvas class="bi-chart-monthly" height="130"></canvas></div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-header py-2"><strong><i class="bi bi-graph-up"></i> ${escHtml(t('budget.chart_trend_title'))}</strong></div>
                    <div class="card-body"><canvas class="bi-chart-trend" height="130"></canvas></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header py-2"><strong><i class="bi bi-lightbulb"></i> ${escHtml(t('budget.rec_title'))}</strong></div>
            <div class="card-body bi-recs"></div>
        </div>`;
    }

    function kpiCard(label, value, sub) {
        return `
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100"><div class="card-body py-2 px-3">
                <div class="text-muted" style="font-size:11px">${escHtml(label)}</div>
                <div class="fw-semibold" style="font-size:15px">${value}</div>
                ${sub ? `<div class="text-muted" style="font-size:11px">${sub}</div>` : ''}
            </div></div>
        </div>`;
    }

    function renderKpis(root, data) {
        const s = data.summary || {};
        const max = s.max_spent || {};
        root.querySelector('.bi-kpis').innerHTML = [
            kpiCard(t('budget.kpi_total_spent'), fmtMoney(s.total_spent)),
            kpiCard(t('budget.kpi_avg_spent'), fmtMoney(s.avg_spent)),
            kpiCard(t('budget.kpi_avg_budget'), fmtMoney(s.avg_budget)),
            kpiCard(t('budget.kpi_avg_pct'), (s.avg_pct || 0) + '%'),
            kpiCard(t('budget.kpi_over_months'),
                `<span class="${s.over_count ? 'text-danger' : ''}">${s.over_count || 0}</span>`,
                t('budget.kpi_months_tracked') + ': ' + (s.months_tracked || 0)),
            kpiCard(t('budget.kpi_peak'), max.amount ? fmtMoney(max.amount) : '—',
                max.month ? fmtMonthShort(max.month) : ''),
        ].join('');
    }

    function renderRecs(root, data) {
        const recs = data.recommendations || [];
        const el = root.querySelector('.bi-recs');
        if (!recs.length) { el.innerHTML = `<div class="text-muted small">${escHtml(t('budget.insights_none'))}</div>`; return; }
        el.innerHTML = recs.map(r => {
            const st = REC_STYLE[r.severity] || REC_STYLE.info;
            return `<div class="d-flex align-items-start gap-2 mb-2">
                <i class="bi bi-${st.icon} text-${st.cls} mt-1"></i>
                <span class="small">${escHtml(t('budget.rec_' + r.code, r.params || {}))}</span>
            </div>`;
        }).join('');
    }

    function drawCharts(root, data) {
        if (typeof Chart === 'undefined') return;
        const st = _states.get(root);
        const months = data.months || [];
        const labels = months.map(m => fmtMonthShort(m.month));

        // Monthly: spent bars coloured by status + budget line.
        const spentColors = months.map(m => CHART_HEX[m.status] || CHART_HEX.none);
        if (st.chartMonthly) st.chartMonthly.destroy();
        st.chartMonthly = new Chart(root.querySelector('.bi-chart-monthly'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: t('budget.legend_spent'), data: months.map(m => Math.round(m.spent)), backgroundColor: spentColors, order: 2 },
                    { label: t('budget.legend_budget'), data: months.map(m => m.has_budget ? Math.round(m.budget) : null),
                      type: 'line', borderColor: '#7c3aed', borderWidth: 2, pointRadius: 2, spanGaps: true, tension: 0, order: 1 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmtMoney(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => fmtMoney(v) } } },
            },
        });

        // Trend: % of budget used, with a 100% reference line.
        if (st.chartTrend) st.chartTrend.destroy();
        st.chartTrend = new Chart(root.querySelector('.bi-chart-trend'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: t('budget.chart_trend_title'), data: months.map(m => m.has_budget ? m.pct : null),
                      borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,.1)', borderWidth: 2,
                      pointRadius: 2, fill: true, spanGaps: true, tension: .25 },
                    { label: '100%', data: months.map(() => 100), borderColor: '#ef4444', borderWidth: 1,
                      borderDash: [5, 4], pointRadius: 0, fill: false },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false },
                    tooltip: { callbacks: { label: c => c.datasetIndex === 0 ? (c.parsed.y == null ? '—' : c.parsed.y + '%') : '100%' } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => v + '%' } } },
            },
        });
    }

    async function loadInsights(root) {
        const st = _states.get(root);
        const params = new URLSearchParams({
            action: 'budget_analytics',
            scope_type: st.selected.scope_type,
            months: st.range,
        });
        if (st.selected.scope_type === 'type') params.set('scope_ref_name', st.selected.scope_ref_name || '');
        else if (st.selected.scope_type !== 'overall') params.set('scope_ref_id', st.selected.scope_ref_id || '');

        const recsEl = root.querySelector('.bi-recs');
        if (recsEl) recsEl.innerHTML = `<div class="text-muted small">${escHtml(t('common.loading'))}</div>`;

        const data = await fetch('api.php?' + params.toString()).then(r => r.json()).catch(() => ({ error: true }));
        if (data.error) { if (recsEl) recsEl.innerHTML = `<div class="text-danger small">${escHtml(t('budget.err_load'))}</div>`; return; }

        // (Re)build the scope dropdown, preserving the current selection.
        st.scopes = data.scopes || [];
        const sel = root.querySelector('.bi-scope');
        const curKey = scopeKey(st.selected);
        sel.innerHTML = st.scopes.map((s, i) => `<option value="${i}">${escHtml(scopeOptionLabel(s))}</option>`).join('');
        const idx = st.scopes.findIndex(s => scopeKey(s) === curKey);
        sel.value = String(idx >= 0 ? idx : 0);

        renderKpis(root, data);
        drawCharts(root, data);
        renderRecs(root, data);
    }

    // Mount the Insights UI into a container element (idempotent).
    function mountInsights(root) {
        if (!root || _states.has(root)) return;
        _states.set(root, { selected: { scope_type: 'overall', scope_ref_id: null, scope_ref_name: '' }, range: 12, scopes: [], chartMonthly: null, chartTrend: null });
        root.innerHTML = insightsMarkup();
        const st = _states.get(root);
        root.querySelector('.bi-scope').addEventListener('change', e => {
            st.selected = st.scopes[+e.target.value] || st.selected;
            loadInsights(root);
        });
        root.querySelector('.bi-range').addEventListener('change', e => {
            st.range = parseInt(e.target.value, 10) || 12;
            loadInsights(root);
        });
        loadInsights(root);
    }

    window.Budget = {
        fmtMoney, escHtml, scopeTypeLabel, scopeLabel, statusText, renderBudgetBar, STATUS_COLOR,
        fmtMonthLabel, fmtMonthShort,
        Insights: { mount: mountInsights },
    };
})();
