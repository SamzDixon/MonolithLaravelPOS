<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Sales') }}
            </h2>
            @can('create', App\Models\Sale::class)
                <a href="{{ route('sales.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                    POS Sale
                </a>
            @endcan
        </div>
    </x-slot>

    @include('components.live-filter')

    <script>
    window.salesIndexConfig = {
        endpoint: {{ Illuminate\Support\Js::from(route('sales.search')) }},
        filters: {{ Illuminate\Support\Js::from([
            'store_id' => (string) ($filters['store_id'] ?? ''),
            'search' => (string) ($filters['search'] ?? ''),
            'from' => (string) ($filters['from'] ?? ''),
            'to' => (string) ($filters['to'] ?? ''),
        ]) }},
        count: {{ $sales->total() }},
        total: {{ (float) $sales->sum('total_amount') }},
    };

    function salesIndexPage(config) {
        return {
            ...liveFilter({
                endpoint: config.endpoint,
                initialFilters: config.filters,
                immediateKeys: ['store_id', 'from', 'to'],
            }),

            init() {
                this.count = config.count;
                this.total = config.total;
                this.$watch('filters.store_id', () => this.onFilterChange('store_id'));
                this.$watch('filters.search', () => this.onFilterChange('search'));
                this.$watch('filters.from', () => this.onFilterChange('from'));
                this.$watch('filters.to', () => this.onFilterChange('to'));
            },

            run() {
                if (this.abortController) this.abortController.abort();
                this.abortController = new AbortController();
                this.loading = true;

                const url = new URL(this.endpoint, window.location.origin);
                Object.entries(this.filters).forEach(([k, v]) => {
                    if (v !== '' && v !== null && v !== false && v !== undefined) {
                        url.searchParams.set(k, v);
                    }
                });
                window.history.replaceState({}, '', url.toString());

                fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: this.abortController.signal,
                })
                    .then(r => r.json())
                    .then(data => {
                        this.count = data.count;
                        this.total = data.total_amount;
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

            render(rows) {
                const tbody = document.getElementById('sales-body');
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No sales match your filters.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(r => this.rowHtml(r)).join('');
            },

            rowHtml(r) {
                const amount = r.total_amount.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const statusBadge = r.status === 'voided'
                    ? '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">Voided</span>'
                    : '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Completed</span>';

                return '<tr class="border-t border-slate-100 hover:bg-slate-50">'
                    + '<td class="px-5 py-3 font-mono text-xs text-slate-600">' + this.escape(r.reference) + '</td>'
                    + '<td class="px-5 py-3 text-slate-700">' + this.escape(r.store_name) + '</td>'
                    + '<td class="px-5 py-3 text-slate-600">' + this.escape(r.user_name) + '</td>'
                    + '<td class="px-5 py-3 text-right text-slate-700">' + r.items_count + '</td>'
                    + '<td class="px-5 py-3 text-right font-medium text-slate-900">KES ' + amount + '</td>'
                    + '<td class="px-5 py-3 text-center">' + statusBadge + '</td>'
                    + '<td class="px-5 py-3 text-right text-slate-600 text-xs whitespace-nowrap">'
                    + this.escape(r.created_at)
                    + ' <a href="' + r.show_url + '" class="ml-2 text-blue-700 hover:text-blue-900 font-medium">View</a>'
                    + '</td>'
                    + '</tr>';
            },
        };
    }
    </script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4"
             x-data="salesIndexPage(window.salesIndexConfig)">

            @if ($errors->any())
                <div class="px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('sales.index') }}" autocomplete="off"
                  @submit.prevent="run()"
                  class="bg-white border border-slate-200 rounded p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Store</label>
                    <select name="store_id" x-model="filters.store_id" class="w-full border-slate-300 rounded text-sm">
                        <option value="">All stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Reference</label>
                    <input type="text" name="search" x-model="filters.search" autocomplete="off"
                           placeholder="SALE-…"
                           class="w-full border-slate-300 rounded text-sm">
                </div>

                <div class="min-w-[140px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">From</label>
                    <input type="date" name="from" x-model="filters.from"
                           class="w-full border-slate-300 rounded text-sm">
                </div>

                <div class="min-w-[140px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">To</label>
                    <input type="date" name="to" x-model="filters.to"
                           class="w-full border-slate-300 rounded text-sm">
                </div>

                <button type="button" @click="clear()"
                        x-show="filters.store_id || filters.search || filters.from || filters.to"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                    Clear
                </button>
            </form>

            <div class="text-sm text-slate-500 h-5"
                 x-text="loading ? 'Filtering…' : (count + ' sale' + (count === 1 ? '' : 's') + ' — KES ' + total.toLocaleString('en-KE', { minimumFractionDigits: 2 }))"></div>

            {{-- Table --}}
            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Reference</th>
                            <th class="text-left px-5 py-3 font-medium">Store</th>
                            <th class="text-left px-5 py-3 font-medium">Cashier</th>
                            <th class="text-right px-5 py-3 font-medium">Items</th>
                            <th class="text-right px-5 py-3 font-medium">Total</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="text-right px-5 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody id="sales-body">
                        @forelse ($sales as $sale)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $sale->reference }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $sale->store->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $sale->user->name }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $sale->items_count }}</td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900">
                                    KES {{ number_format($sale->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if ($sale->status === 'voided')
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">Voided</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Completed</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right text-slate-600 text-xs whitespace-nowrap">
                                    {{ $sale->created_at->format('d M Y, H:i') }}
                                    <a href="{{ route('sales.show', $sale) }}" class="ml-2 text-blue-700 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No sales yet.
                                    @can('create', App\Models\Sale::class)
                                        <a href="{{ route('sales.create') }}" class="text-blue-700 hover:underline">Record the first one</a>.
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <noscript>
                <div>{{ $sales->links() }}</div>
            </noscript>
        </div>
    </div>
</x-app-layout>