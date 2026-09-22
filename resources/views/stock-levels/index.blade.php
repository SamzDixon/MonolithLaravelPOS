<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Stock Levels') }}
        </h2>
    </x-slot>

    @include('components.live-filter')

    <script>
    window.stockLevelsConfig = {
        endpoint: {{ Illuminate\Support\Js::from(route('stock-levels.search')) }},
        filters: {{ Illuminate\Support\Js::from([
            'store_id' => (string) ($filters['store_id'] ?? ''),
            'search' => (string) ($filters['search'] ?? ''),
            'low_stock' => (bool) ($filters['low_stock'] ?? false),
        ]) }},
        count: {{ $levels->total() }},
    };

    function stockLevelsPage(config) {
        return {
            ...liveFilter({
                endpoint: config.endpoint,
                initialFilters: config.filters,
                immediateKeys: ['store_id', 'low_stock'],
            }),

            init() {
                this.count = config.count;
                this.$watch('filters.store_id', () => this.onFilterChange('store_id'));
                this.$watch('filters.search', () => this.onFilterChange('search'));
                this.$watch('filters.low_stock', () => this.onFilterChange('low_stock'));
            },

            render(rows) {
                const tbody = document.getElementById('stock-levels-body');
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No stock records match your filters.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(r => this.rowHtml(r)).join('');
            },

            rowHtml(r) {
                const isEmpty = r.quantity === 0;
                const isLow = r.reorder_level > 0 && r.quantity <= r.reorder_level;
                const value = r.quantity * r.cost_price;

                const qtyClass = isEmpty ? 'text-red-600' : (isLow ? 'text-amber-600' : 'text-slate-900');

                let status;
                if (isEmpty) {
                    status = '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-red-50 text-red-700 border-red-200">Out of stock</span>';
                } else if (isLow) {
                    status = '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-amber-50 text-amber-800 border-amber-200">Low</span>';
                } else {
                    status = '<span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Healthy</span>';
                }

                const reorder = r.reorder_level > 0 ? r.reorder_level.toLocaleString() : '—';

                return '<tr class="border-t border-slate-100 hover:bg-slate-50">'
                    + '<td class="px-5 py-3 text-slate-700">' + this.escape(r.store_name) + '</td>'
                    + '<td class="px-5 py-3 text-slate-500 font-mono text-xs">' + this.escape(r.sku) + '</td>'
                    + '<td class="px-5 py-3 font-medium text-slate-900">' + this.escape(r.product_name) + '</td>'
                    + '<td class="px-5 py-3 text-right font-medium ' + qtyClass + '">' + r.quantity.toLocaleString() + '<span class="text-slate-400 text-xs ml-1">' + this.escape(r.unit) + '</span></td>'
                    + '<td class="px-5 py-3 text-right text-slate-500">' + reorder + '</td>'
                    + '<td class="px-5 py-3 text-right text-slate-700">' + value.toFixed(2) + '</td>'
                    + '<td class="px-5 py-3 text-center">' + status + '</td>'
                    + '</tr>';
            },
        };
    }
    </script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4"
             x-data="stockLevelsPage(window.stockLevelsConfig)">

            {{-- Summary tiles --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Total Stock Value</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        KES {{ number_format($summary['total_value'], 2) }}
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Total Units on Hand</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ number_format($summary['total_units']) }}
                    </div>
                </div>
                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Items Below Reorder</div>
                    <div class="mt-2 text-2xl font-semibold {{ $summary['low_stock_count'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $summary['low_stock_count'] }}
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('stock-levels.index') }}" autocomplete="off"
                  @submit.prevent="run()"
                  class="bg-white border border-slate-200 rounded p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Store</label>
                    <select name="store_id" x-model="filters.store_id" class="w-full border-slate-300 rounded text-sm">
                        <option value="">All stores</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                    <div class="relative">
                        <input type="text" name="search" x-model="filters.search" autocomplete="off"
                               placeholder="Product name or SKU…"
                               class="w-full border-slate-300 rounded text-sm pl-3 pr-8">
                        <template x-if="loading">
                            <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-700 animate-spin"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </template>
                    </div>
                </div>

                <label class="flex items-center gap-2 pb-2">
                    <input type="checkbox" x-model="filters.low_stock"
                           class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                    <span class="text-sm text-slate-700">Low stock only</span>
                </label>

                <noscript>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Filter
                    </button>
                </noscript>

                <button type="button" @click="clear()"
                        x-show="filters.store_id || filters.search || filters.low_stock"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                    Clear
                </button>
            </form>

            <div class="text-sm text-slate-500 h-5"
                 x-text="loading ? 'Filtering…' : (count + ' record' + (count === 1 ? '' : 's'))"></div>

            {{-- Table --}}
            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Store</th>
                            <th class="text-left px-5 py-3 font-medium">SKU</th>
                            <th class="text-left px-5 py-3 font-medium">Product</th>
                            <th class="text-right px-5 py-3 font-medium">On Hand</th>
                            <th class="text-right px-5 py-3 font-medium">Reorder</th>
                            <th class="text-right px-5 py-3 font-medium">Stock Value</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody id="stock-levels-body">
                        @forelse ($levels as $level)
                            @php
                                $product = $level->product;
                                $isLow = $product->reorder_level > 0 && $level->quantity <= $product->reorder_level;
                                $isEmpty = $level->quantity === 0;
                                $value = $level->quantity * $product->cost_price;
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 text-slate-700">{{ $level->store->name }}</td>
                                <td class="px-5 py-3 text-slate-500 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                                <td class="px-5 py-3 text-right font-medium {{ $isEmpty ? 'text-red-600' : ($isLow ? 'text-amber-600' : 'text-slate-900') }}">
                                    {{ number_format($level->quantity) }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $product->unit }}</span>
                                </td>
                                <td class="px-5 py-3 text-right text-slate-500">
                                    {{ $product->reorder_level > 0 ? number_format($product->reorder_level) : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ number_format($value, 2) }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($isEmpty)
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-red-50 text-red-700 border-red-200">Out of stock</span>
                                    @elseif ($isLow)
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-amber-50 text-amber-800 border-amber-200">Low</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Healthy</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No stock records match your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <noscript>
                <div>{{ $levels->links() }}</div>
            </noscript>
        </div>
    </div>
</x-app-layout>