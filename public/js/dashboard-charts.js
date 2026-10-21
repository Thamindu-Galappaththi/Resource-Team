(function () {
    const node = document.getElementById('rd-dash-data');
    if (!node || typeof window.Chart === 'undefined') { return; }

    let payload;
    try { payload = JSON.parse(node.textContent || '{}'); } catch (e) { return; }

    const tones = {
        success: '#16a77a',
        warning: '#d97706',
        danger:  '#e53e3e',
        info:    '#0284c7',
        primary: '#1769c2',
        neutral: '#94a3b8',
    };

    /* ── Area chart — total reservations per day ──────────── */
    const bar    = document.getElementById('rd-bar-chart');
    const chart  = payload.chart || {};
    const points = Array.isArray(chart.points) ? chart.points : [];

    if (bar && points.length) {
        const series = Array.isArray(chart.series) ? chart.series : [];

        const totals = points.map(function (_, i) {
            if (!series.length) { return points[i].value || 0; }
            return series.reduce(function (sum, s) {
                return sum + (Array.isArray(s.values) ? (s.values[i] || 0) : 0);
            }, 0);
        });

        const ctx  = bar.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 220);
        grad.addColorStop(0,   'rgba(23,105,194,0.28)');
        grad.addColorStop(0.6, 'rgba(23,105,194,0.08)');
        grad.addColorStop(1,   'rgba(23,105,194,0.00)');

        new window.Chart(bar, {
            type: 'line',
            data: {
                labels:   points.map(function (p) { return p.label; }),
                datasets: [{
                    label:               'Reservations',
                    data:                totals,
                    borderColor:         '#1769c2',
                    borderWidth:         2.5,
                    backgroundColor:     grad,
                    fill:                true,
                    tension:             0.42,
                    pointRadius:         4,
                    pointHoverRadius:    7,
                    pointBackgroundColor: '#fff',
                    pointBorderColor:    '#1769c2',
                    pointBorderWidth:    2.5,
                    pointHoverBackgroundColor: '#1769c2',
                    pointHoverBorderColor:     '#fff',
                }],
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                interaction:         { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        titleColor:      '#f1f5f9',
                        bodyColor:       '#cbd5e1',
                        borderColor:     '#1e293b',
                        borderWidth:     1,
                        cornerRadius:    10,
                        padding:         12,
                        callbacks: {
                            label: function (ctx) {
                                return '  Total: ' + ctx.parsed.y;
                            },
                            afterBody: function (items) {
                                if (!series.length) { return []; }
                                const idx   = items[0].dataIndex;
                                const lines = [''];
                                series.forEach(function (s) {
                                    const v = Array.isArray(s.values) ? (s.values[idx] || 0) : 0;
                                    lines.push('  ' + (s.label || '?') + ':  ' + v);
                                });
                                return lines;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid:   { display: false },
                        border: { display: false },
                        ticks:  { color: '#94a3b8', font: { size: 11, weight: '600' } },
                    },
                    y: {
                        beginAtZero: true,
                        border:      { display: false },
                        grid:        { color: '#f1f5f9' },
                        ticks: {
                            precision: 0,
                            color:     '#94a3b8',
                            font:      { size: 11 },
                            stepSize:  1,
                        },
                    },
                },
            },
        });
    }

    /* ── Doughnut ───────────────────────────────────────────
       Custom HTML tooltip rendered on document.body so it is
       NEVER clipped by the canvas or panel boundary.
    ─────────────────────────────────────────────────────── */
    const mix   = document.getElementById('rd-mix-chart');
    const items = Array.isArray(payload.breakdown) ? payload.breakdown : [];

    function getTooltipEl() {
        let el = document.getElementById('rd-donut-tt');
        if (!el) {
            el = document.createElement('div');
            el.id = 'rd-donut-tt';
            el.style.cssText = [
                'position:fixed',
                'pointer-events:none',
                'z-index:9999',
                'opacity:0',
                'transition:opacity .12s',
                'background:rgba(15,23,42,0.93)',
                'color:#f1f5f9',
                'border:1px solid #1e293b',
                'border-radius:10px',
                'padding:9px 14px',
                'font-size:12px',
                'font-weight:600',
                'font-family:inherit',
                'white-space:nowrap',
                'box-shadow:0 6px 20px rgba(0,0,0,.28)',
            ].join(';');
            document.body.appendChild(el);
        }
        return el;
    }

    function externalTooltipHandler(context) {
        const { chart, tooltip } = context;
        const el = getTooltipEl();

        if (tooltip.opacity === 0) { el.style.opacity = '0'; return; }

        const dp = tooltip.dataPoints && tooltip.dataPoints[0];
        if (!dp) { el.style.opacity = '0'; return; }

        const val   = dp.parsed || 0;
        const total = dp.dataset.data.reduce(function (s, v) { return s + v; }, 0) || 1;
        const pct   = Math.round((val / total) * 100);
        const label = dp.label || '';
        const color = Array.isArray(dp.dataset.backgroundColor)
            ? dp.dataset.backgroundColor[dp.dataIndex]
            : (dp.dataset.backgroundColor || '#1769c2');

        el.innerHTML =
            '<div style="display:flex;align-items:center;gap:7px;margin-bottom:4px">' +
                '<span style="display:inline-block;width:10px;height:10px;border-radius:3px;' +
                      'background:' + color + ';flex-shrink:0"></span>' +
                '<span style="color:#f1f5f9;font-weight:700;font-size:12px">' + label + '</span>' +
            '</div>' +
            '<div style="color:#94a3b8;font-size:11px;font-weight:500;padding-left:17px">' +
                val + ' reservations &nbsp;·&nbsp; ' + pct + '%' +
            '</div>';

        el.style.opacity = '1';

        /* Position next to caret; flip left if near right viewport edge */
        const rect = chart.canvas.getBoundingClientRect();
        const ttW  = el.offsetWidth  || 200;
        const ttH  = el.offsetHeight || 50;

        let left = rect.left + tooltip.caretX + 14;
        let top  = rect.top  + tooltip.caretY - ttH / 2;

        if (left + ttW > window.innerWidth - 10) {
            left = rect.left + tooltip.caretX - ttW - 14;
        }
        if (top < 8) { top = 8; }

        el.style.left = left + 'px';
        el.style.top  = top  + 'px';
    }

    if (mix && items.length) {
        new window.Chart(mix, {
            type: 'doughnut',
            data: {
                labels: items.map(function (it) { return it.label; }),
                datasets: [{
                    data:            items.map(function (it) { return it.value; }),
                    backgroundColor: items.map(function (it) { return tones[it.tone] || tones.primary; }),
                    borderWidth:     3,
                    borderColor:     '#fff',
                    hoverOffset:     8,
                }],
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                cutout:              '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled:  false,
                        external: externalTooltipHandler,
                    },
                },
            },
        });
    }
})();
