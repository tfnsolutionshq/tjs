export function registerTjsSelect(Alpine) {
    Alpine.data('tjsSelect', (cfg) => ({
        open: false,
        hot: 0,
        options: cfg.options || [],
        selectedValue: cfg.value ?? '',
        submitOnChange: !!cfg.submitOnChange,
        placeholder: cfg.placeholder || 'Select…',
        variant: cfg.variant || 'default',

        get selectedOption() {
            return this.options.find((option) => option.value === this.selectedValue) || null;
        },

        get selectedLabel() {
            return this.selectedOption ? this.selectedOption.label : this.placeholder;
        },

        get selectedIcon() {
            return this.selectedOption?.icon || '';
        },

        get selectedHint() {
            return this.selectedOption?.hint || '';
        },

        init() {
            this.$el.addEventListener('select-set', (event) => {
                this.selectedValue = String(event.detail ?? '');
            });
        },

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.hot = this.indexOfSelected();
            }
        },

        indexOfSelected() {
            const index = this.options.findIndex((option) => option.value === this.selectedValue);

            return index >= 0 ? index : 0;
        },

        select(value) {
            this.selectedValue = value;
            this.open = false;
            this.hot = 0;

            const hidden = this.$refs.hidden;

            if (hidden) {
                hidden.value = value;
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            this.$dispatch('picker-change', value);

            if (this.submitOnChange) {
                const form = this.$el.closest('form');

                if (form) {
                    form.requestSubmit();
                }
            }
        },

        pickHighlighted() {
            const option = this.options[this.hot];

            if (option) {
                this.select(option.value);
            }
        },

        highlightNext() {
            if (!this.options.length) {
                return;
            }

            this.hot = (this.hot + 1) % this.options.length;
        },

        highlightPrev() {
            if (!this.options.length) {
                return;
            }

            this.hot = (this.hot - 1 + this.options.length) % this.options.length;
        },
    }));
}
