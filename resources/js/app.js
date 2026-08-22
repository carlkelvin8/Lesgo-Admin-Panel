import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

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
