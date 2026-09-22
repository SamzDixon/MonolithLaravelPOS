@once
<script>
function liveFilter({ endpoint, initialFilters, immediateKeys = [], debounceMs = 300 }) {
    return {
        filters: { ...initialFilters },
        loading: false,
        count: 0,
        abortController: null,
        timers: {},
        immediateKeys: immediateKeys,
        endpoint: endpoint,
        debounceMs: debounceMs,

        init() {
            // Set the initial count from the server-rendered state. The
            // parent view passes it via x-data bindings.
        },

        onFilterChange(key) {
            // Fire immediately for selects/checkboxes/dates, debounce for text.
            if (this.immediateKeys.includes(key)) {
                this.run();
            } else {
                clearTimeout(this.timers[key]);
                this.timers[key] = setTimeout(() => this.run(), this.debounceMs);
            }
        },

        run() {
            // Cancel any in-flight request so a slow response for "sug"
            // can't overwrite the correct results for "sugar".
            if (this.abortController) {
                this.abortController.abort();
            }
            this.abortController = new AbortController();
            this.loading = true;

            const url = new URL(this.endpoint, window.location.origin);
            Object.entries(this.filters).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== false && v !== undefined) {
                    url.searchParams.set(k, v);
                }
            });

            // Update browser URL so refresh and back/forward still work.
            window.history.replaceState({}, '', url.toString());

            fetch(url, {
                headers: { 'Accept': 'application/json' },
                signal: this.abortController.signal,
            })
                .then(r => r.json())
                .then(data => {
                    this.count = data.count;
                    this.render(data.rows);
                    this.loading = false;
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        this.loading = false;
                        console.error(err);
                    }
                });
        },

        clear() {
            Object.keys(this.filters).forEach(k => this.filters[k] = '');
            this.run();
        },

        // Override in the parent view to define row rendering.
        render(rows) {
            throw new Error('render() must be implemented');
        },

        escape(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },
    };
}
</script>
@endonce