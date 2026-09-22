<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Products') }}
            </h2>
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                    New Product
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($errors->any())
                <div class="px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Search --}}
            <div x-data="productSearch({
                    endpoint: '{{ route('products.search') }}',
                    initial: '{{ $search }}'
                 })"
                 x-init="init()">

                <form method="GET" action="{{ route('products.index') }}" autocomplete="off"
                      @submit.prevent="submitForm"
                      class="bg-white border border-slate-200 rounded p-4 flex gap-2 items-center">
                    <div class="relative flex-1">
                        <input type="text" name="search" x-model="query" @input.debounce.300ms="runSearch()"
                               autocomplete="off"
                               placeholder="Search by name or SKU…"
                               class="w-full border-slate-300 rounded text-sm pl-9 pr-9">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <template x-if="loading">
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-700 animate-spin"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                        <template x-if="!loading && query.length > 0">
                            <button type="button" @click="clear()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </template>
                    </div>
                    <noscript>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded hover:bg-slate-50">
                            Search
                        </button>
                    </noscript>
                </form>

                <div class="text-sm text-slate-500 mt-2 h-5"
                     x-text="loading ? 'Searching…' : (query.length > 0 ? count + ' result' + (count === 1 ? '' : 's') : '')"></div>

                <div class="bg-white border border-slate-200 rounded overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium">SKU</th>
                                <th class="text-left px-5 py-3 font-medium">Name</th>
                                <th class="text-left px-5 py-3 font-medium">Unit</th>
                                <th class="text-right px-5 py-3 font-medium">Cost</th>
                                <th class="text-right px-5 py-3 font-medium">Selling</th>
                                <th class="text-right px-5 py-3 font-medium">Margin</th>
                                <th class="text-right px-5 py-3 font-medium">Reorder</th>
                                <th class="text-center px-5 py-3 font-medium">Status</th>
                                <th class="w-32"></th>
                            </tr>
                        </thead>
                        <tbody x-ref="tbody">
                            @forelse ($products as $product)
                                @include('products._row', ['product' => $product])
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                        @if ($search)
                                            No products match "{{ $search }}".
                                        @else
                                            No products yet.
                                            @can('create', App\Models\Product::class)
                                                <a href="{{ route('products.create') }}" class="text-blue-700 hover:underline">Create the first one</a>.
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $products->links() }}</div>
        </div>
    </div>

    <script>
    function productSearch({ endpoint, initial }) {
        return {
            query: initial || '',
            loading: false,
            count: {{ $products->total() }},
            abortController: null,

            init() {
                // If the page loaded with a search in the URL, keep the
                // input in sync — no need to re-fetch, the server already did.
            },

            runSearch() {
                // Cancel any in-flight request. Without this, a slow response
                // for "su" could land after a fast response for "sugar" and
                // overwrite the correct results with stale ones.
                if (this.abortController) {
                    this.abortController.abort();
                }
                this.abortController = new AbortController();

                this.loading = true;

                const url = new URL(endpoint, window.location.origin);
                if (this.query.length > 0) {
                    url.searchParams.set('search', this.query);
                }

                // Keep the browser URL in sync without a page reload, so
                // refresh and back/forward still work.
                window.history.replaceState({}, '', url.toString());

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: this.abortController.signal,
                })
                    .then(r => r.json())
                    .then(data => {
                        this.count = data.count;
                        this.renderRows(data.rows);
                        this.loading = false;
                    })
                    .catch(err => {
                        if (err.name !== 'AbortError') {
                            this.loading = false;
                            console.error(err);
                        }
                    });
            },

            renderRows(rows) {
                const tbody = this.$refs.tbody;

                if (rows.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                No products match "${this.escape(this.query)}".
                            </td>
                        </tr>`;
                    return;
                }

                tbody.innerHTML = rows.map(r => this.rowHtml(r)).join('');
            },

            rowHtml(r) {
                const margin = r.cost_price > 0
                    ? ((r.selling_price - r.cost_price) / r.cost_price) * 100
                    : null;
                const marginText = margin === null ? '—' : margin.toFixed(1) + '%';
                const marginClass = margin === null ? 'text-slate-400'
                    : margin >= 15 ? 'text-emerald-600'
                    : margin >= 5 ? 'text-amber-600'
                    : 'text-red-600';

                const status = r.is_active
                    ? '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Active</span>'
                    : '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">Inactive</span>';

                const editLink = r.can_edit
                    ? `<a href="${r.edit_url}" class="text-blue-700 hover:text-blue-900 font-medium">Edit</a>`
                    : '';

                // Delete uses a link that submits a hidden form — the modal
                // is triggered by the user clicking, and we can't easily
                // render the Alpine component from a template string here,
                // so we keep it as a link to the confirm page or plain form.
                // For simplicity in live-search results, we just link to edit.
                const actions = editLink;

                return `
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-5 py-3 text-slate-600 font-mono text-xs">${this.escape(r.sku)}</td>
                        <td class="px-5 py-3 font-medium text-slate-900">${this.escape(r.name)}</td>
                        <td class="px-5 py-3 text-slate-600">${this.escape(r.unit)}</td>
                        <td class="px-5 py-3 text-right text-slate-700">${r.cost_price.toFixed(2)}</td>
                        <td class="px-5 py-3 text-right text-slate-800 font-medium">${r.selling_price.toFixed(2)}</td>
                        <td class="px-5 py-3 text-right font-medium ${marginClass}">${marginText}</td>
                        <td class="px-5 py-3 text-right text-slate-700">${r.reorder_level}</td>
                        <td class="px-5 py-3 text-center">${status}</td>
                        <td class="px-5 py-3 text-right space-x-3">${actions}</td>
                    </tr>`;
            },

            clear() {
                this.query = '';
                this.runSearch();
            },

            submitForm(e) {
                // Without JS, the form submits normally to the index route.
                // With JS, we intercept and let the live search handle it.
                // The debounced input has already run the search by this point,
                // so a manual submit is a no-op for typical use.
                e.preventDefault();
                this.runSearch();
            },

            escape(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            },
        };
    }
    </script>
</x-app-layout>