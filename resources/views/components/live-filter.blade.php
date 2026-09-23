@once
<script>
function liveFilter({ endpoint, pageUrl, initialFilters, immediateKeys = [], debounceMs = 300 }) {
    return {
        filters: { ...initialFilters },
        loading: false,
        count: 0,
        abortController: null,
        timers: {},
        immediateKeys: immediateKeys,
        endpoint: endpoint,
        pageUrl: pageUrl,
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

            // Two URLs: one for the fetch (JSON endpoint), one for the browser bar
            // (the human-readable page). If we pushed the endpoint URL, a refresh
            // would hit the JSON route and render raw data.
            const fetchUrl = new URL(this.endpoint, window.location.origin);
            const pageUrl = new URL(this.pageUrl, window.location.origin);

            Object.entries(this.filters).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== false && v !== undefined) {
                    fetchUrl.searchParams.set(k, v);
                    pageUrl.searchParams.set(k, v);
                }
            });

            // Browser bar reflects the page URL with filters, not the JSON endpoint.
            window.history.replaceState({}, '', pageUrl.toString());

            fetch(fetchUrl, {
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