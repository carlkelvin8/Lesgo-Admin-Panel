import Alpine from 'alpinejs';
import './dashboard-charts';

window.Alpine = Alpine;

function sanitizeHref(href) {
    if (typeof href !== 'string') return href;
    let clean = href.trim();
    clean = clean.replace(/^"+|"+$/g, '');
    return clean === href ? href : clean;
}

function repairInvalidLinks(root = document) {
    root.querySelectorAll('a[href]').forEach((a) => {
        const fixed = sanitizeHref(a.getAttribute('href'));
        if (fixed !== a.getAttribute('href')) {
            a.setAttribute('href', fixed);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => repairInvalidLinks());
window.addEventListener('alpine:init', () => repairInvalidLinks());

Alpine.store('toast', {
    items: [],
    success(message) { this.add(message, 'success'); },
    error(message) { this.add(message, 'error'); },
    info(message) { this.add(message, 'info'); },
    add(message, type = 'info') {
        const id = Date.now();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 5000);
    },
    remove(id) {
        this.items = this.items.filter(t => t.id !== id);
    }
});

Alpine.store('modal', {
    open: false,
    title: '',
    body: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmClass: 'bg-red-600 hover:bg-red-700',
    onConfirm: null,
    show(title, body, options = {}) {
        this.title = title;
        this.body = body;
        this.confirmText = options.confirmText || 'Confirm';
        this.cancelText = options.cancelText || 'Cancel';
        this.confirmClass = options.confirmClass || 'bg-red-600 hover:bg-red-700';
        this.onConfirm = options.onConfirm || null;
        this.open = true;
    },
    confirm() {
        if (this.onConfirm) this.onConfirm();
        this.open = false;
    },
    cancel() { this.open = false; }
});

Alpine.data('confirmAction', (options = {}) => ({
    open: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    confirmClass: 'bg-red-600 hover:bg-red-700',
    init() {
        this.title = options.title || 'Are you sure?';
        this.message = options.message || 'This action cannot be undone.';
        this.confirmText = options.confirmText || 'Confirm';
        this.confirmClass = options.confirmClass || 'bg-red-600 hover:bg-red-700';
    },
    trigger() { this.open = true; },
    confirm() {
        this.open = false;
        if (options.onConfirm) options.onConfirm();
        else this.$el.closest('form')?.submit();
    }
}));

Alpine.data('sortable', (url = '') => ({
    column: new URLSearchParams(window.location.search).get('sort') || 'created_at',
    direction: new URLSearchParams(window.location.search).get('dir') || 'desc',
    toggle(col) {
        if (this.column === col) {
            this.direction = this.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.column = col;
            this.direction = 'asc';
        }
        const params = new URLSearchParams(window.location.search);
        params.set('sort', this.column);
        params.set('dir', this.direction);
        window.location.href = url + '?' + params.toString();
    },
    icon(col) {
        if (this.column !== col) return 'fa-sort';
        return this.direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    }
}));

Alpine.data('bulkSelect', () => ({
    selected: [],
    get allSelected() {
        return this.items.length > 0 && this.selected.length === this.items.length;
    },
    get someSelected() {
        return this.selected.length > 0 && !this.allSelected;
    },
    items: [],
    toggleAll() {
        this.selected = this.allSelected ? [] : [...this.items];
    },
    toggle(id) {
        this.selected.includes(id)
            ? this.selected = this.selected.filter(i => i !== id)
            : this.selected.push(id);
    },
    isSelected(id) { return this.selected.includes(id); }
}));

Alpine.data('tabs', (defaultTab = '') => ({
    active: defaultTab,
    set(tab) { this.active = tab; },
    is(tab) { return this.active === tab; }
}));

Alpine.start();

/* ── Chart.js theme sync ────────────────────────────────────────────
   Chart instances use hardcoded light tick/grid colors. Keep them
   readable in dark mode by re-tinting on toggle + for charts created
   after load (dashboard charts lazy-load Chart.js). */
const CHART_LIGHT_TICK = '#6b7280';
const CHART_LIGHT_GRID = 'rgba(0,0,0,0.05)';
const CHART_LIGHT_GRID_ALT = 'rgba(107,114,128,0.1)';

function isDarkTheme() {
    return document.documentElement.classList.contains('dark');
}

function applyChartTheme() {
    if (!window.Chart) return;
    const dark = isDarkTheme();
    const tick = dark ? '#9ca3af' : CHART_LIGHT_TICK;
    const grid = dark ? 'rgba(255,255,255,0.08)' : CHART_LIGHT_GRID;
    const gridAlt = dark ? 'rgba(255,255,255,0.08)' : CHART_LIGHT_GRID_ALT;
    if (window.Chart.defaults) window.Chart.defaults.color = tick;
    document.querySelectorAll('canvas').forEach((canvas) => {
        let chart = null;
        try {
            chart = window.Chart.getChart ? window.Chart.getChart(canvas) : null;
        } catch (e) { chart = null; }
        if (!chart || !chart.options) return;
        const scales = chart.options.scales || {};
        Object.values(scales).forEach((scale) => {
            if (!scale) return;
            if (scale.ticks && (scale.ticks.color === CHART_LIGHT_TICK || scale.ticks.color === '#9ca3af')) {
                scale.ticks.color = tick;
            }
            if (scale.grid) {
                if (scale.grid.color === CHART_LIGHT_GRID || scale.grid.color === 'rgba(255,255,255,0.08)') {
                    scale.grid.color = grid;
                } else if (scale.grid.color === CHART_LIGHT_GRID_ALT) {
                    scale.grid.color = gridAlt;
                }
            }
        });
        const legendLabels = chart.options.plugins?.legend?.labels;
        if (legendLabels && (legendLabels.color === CHART_LIGHT_TICK || legendLabels.color === '#9ca3af')) {
            legendLabels.color = tick;
        }
        try { chart.update('none'); } catch (e) {}
    });
}

window.addEventListener('theme-changed', applyChartTheme);
document.addEventListener('DOMContentLoaded', () => {
    applyChartTheme();
    // Charts may be created lazily (dynamic import) — re-apply shortly after load.
    setTimeout(applyChartTheme, 1500);
    // Also observe class flips from any other toggle path.
    try {
        new MutationObserver((mutations) => {
            if (mutations.some((m) => m.attributeName === 'class')) applyChartTheme();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    } catch (e) {}
});
