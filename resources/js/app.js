import Alpine from 'alpinejs';

/**
 * Dropdown ber-search yang ringan (tanpa dependency).
 * Dipakai untuk input dengan banyak pilihan (pelanggan, supplier, kategori, dll).
 *
 * options: [{ value, label, meta }]  — meta opsional untuk filter (mis. customer_id kendaraan)
 * selected: nilai awal
 * filterBy: () => nilai — jika di-set, opsi difilter agar meta === nilai (mis. kendaraan per pelanggan)
 */
Alpine.data('searchSelect', ({ options = [], selected = '', filterBy = null } = {}) => ({
    options,
    selected: selected ? String(selected) : '',
    filterBy,
    open: false,
    q: '',

    get available() {
        let list = this.options;
        if (this.filterBy) {
            const f = this.filterBy();
            list = list.filter((o) => !f || String(o.meta) === String(f));
        }
        const q = this.q.toLowerCase().trim();
        if (!q) return list;
        return list.filter((o) => o.label.toLowerCase().includes(q));
    },

    label() {
        const o = this.options.find((x) => String(x.value) === String(this.selected));
        return o ? o.label : '';
    },

    toggle() {
        this.open = !this.open;
        if (this.open) this.$nextTick(() => this.$refs.q?.focus());
    },

    pick(o) {
        this.selected = String(o.value);
        this.open = false;
        this.q = '';
        this.$dispatch('select-change', { value: this.selected });
    },

    clear() {
        this.selected = '';
        this.$dispatch('select-change', { value: '' });
    },
}));

window.Alpine = Alpine;
Alpine.start();
