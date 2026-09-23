<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Stock Movement History') }}
        </h2>
    </x-slot>

    @include('components.live-filter')

    <script>
    window.stockMovementsConfig = {
        endpoint: {{ Illuminate\Support\Js::from(route('stock-movements.search')) }},
        pageUrl: {{ Illuminate\Support\Js::from(route('stock-movements.index')) }},
        filters: {{ Illuminate\Support\Js::from([
            'store_id' => (string) ($filters['store_id'] ?? ''),
            'type' => (string) ($filters['type'] ?? ''),
            'search' => (string) ($filters['search'] ?? ''),
            'from' => (string) ($filters['from'] ?? ''),
            'to' => (string) ($filters['to'] ?? ''),
        ]) }},
        count: {{ $movements->total() }},
    };

    function stockMovementsPage(config) {
        return {
            ...liveFilter({
                endpoint: config.endpoint,
                pageUrl: config.pageUrl,
                initialFilters: config.filters,
                immediateKeys: ['store_id', 'type', 'from', 'to'],
            }),

            init() {
                this.count = config.count;
                this.$watch('filters.store_id', () => this.onFilterChange('store_id'));
                this.$watch('filters.type', () => this.onFilterChange('type'));
                this.$watch('filters.search', () => this.onFilterChange('search'));
                this.$watch('filters.from', () => this.onFilterChange('from'));
                this.$watch('filters.to', () => this.onFilterChange('to'));
            },

            render(rows) {
                const tbody = document.getElementById('movements-body');
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No stock movements match your filters.</td></tr>';
                    return;
                }
                tbody.innerHTML = rows.map(r => this.rowHtml(r)).join('');
            },

            rowHtml(r) {
                const typeMap = {
                    opening: ['Opening', 'bg-slate-100 text-slate-700 border-slate-200'],
                    sale: ['Sale', 'bg-blue-50 text-blue-800 border-blue-200'],
                    transfer_in: ['Transfer In', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                    transfer_out: ['Transfer Out', 'bg-amber-50 text-amber-800 border-amber-200'],
                    adjustment: ['Adjustment', 'bg-slate-100 text-slate-700 border-slate-200'],
                    receipt: ['Receipt', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                };
                const typeInfo = typeMap[r.type] || [r.type, 'bg-slate-100 text-slate-600 border-slate-200'];
                const label = typeInfo[0];
                const classes = typeInfo[1];

                let delta;
                if (r.quantity_delta > 0) {
                    delta = '<span class="text-emerald-600">+' + r.quantity_delta.toLocaleString() + '</span>';
                } else {
                    delta = '<span class="text-red-600">' + r.quantity_delta.toLocaleString() + '</span>';
                }

                const notes = r.notes ? this.escape(r.notes) : '—';

                return '<tr class="border-t border-slate-100 hover:bg-slate-50">'
                    + '<td class="px-5 py-3 text-slate-600 text-xs whitespace-nowrap">' + this.escape(r.created_at) + '</td>'
                    + '<td class="px-5 py-3 text-slate-700">' + this.escape(r.store_name) + '</td>'
                    + '<td class="px-5 py-3 text-slate-800">' + this.escape(r.product_name) + '<span class="text-slate-400 text-xs ml-1">' + this.escape(r.sku) + '</span></td>'
                    + '<td class="px-5 py-3 text-center"><span class="inline-block px-2 py-0.5 text-xs font-medium border rounded ' + classes + '">' + label + '</span></td>'
                    + '<td class="px-5 py-3 text-right font-medium whitespace-nowrap">' + delta + '</td>'
                    + '<td class="px-5 py-3 text-slate-600">' + this.escape(r.user_name) + '</td>'
                    + '<td class="px-5 py-3 text-slate-500 text-xs">' + notes + '</td>'
                    + '</tr>';
            },
        };
    }
    </script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4"
             x-data="stockMovementsPage(window.stockMovementsConfig)">

            {{-- Filters --}}
            <form method="GET" action="{{ route('stock-movements.index') }}" autocomplete="off"
                  @submit.prevent="run()"
                  class="bg-white border border-slate-200 rounded p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Store</label>
                        <select name="store_id" x-model="filters.store_id" class="w-full border-slate-300 rounded text-sm">
                            <option value="">All stores</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                        <select name="type" x-model="filters.type" class="w-full border-slate-300 rounded text-sm">
                            <option value="">All types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
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
                            x-show="filters.store_id || filters.type || filters.search || filters.from || filters.to"
                            class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                        Clear
                    </button>
                </div>
            </form>

            <div class="text-sm text-slate-500 h-5"
                 x-text="loading ? 'Filtering…' : (count + ' movement' + (count === 1 ? '' : 's'))"></div>

            {{-- Table --}}
            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">When</th>
                            <th class="text-left px-5 py-3 font-medium">Store</th>
                            <th class="text-left px-5 py-3 font-medium">Product</th>
                            <th class="text-center px-5 py-3 font-medium">Type</th>
                            <th class="text-right px-5 py-3 font-medium">Change</th>
                            <th class="text-left px-5 py-3 font-medium">By</th>
                            <th class="text-left px-5 py-3 font-medium">Notes</th>
                        </tr>
                    </thead>
                    <tbody id="movements-body">
                        @forelse ($movements as $movement)
                            @php
                                $typeLabels = [
                                    'opening' => ['Opening', 'bg-slate-100 text-slate-700 border-slate-200'],
                                    'sale' => ['Sale', 'bg-blue-50 text-blue-800 border-blue-200'],
                                    'transfer_in' => ['Transfer In', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                    'transfer_out' => ['Transfer Out', 'bg-amber-50 text-amber-800 border-amber-200'],
                                    'adjustment' => ['Adjustment', 'bg-slate-100 text-slate-700 border-slate-200'],
                                    'receipt' => ['Receipt', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                ];
                                [$typeLabel, $typeClasses] = $typeLabels[$movement->type] ?? [$movement->type, 'bg-slate-100 text-slate-600 border-slate-200'];
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 text-slate-600 text-xs whitespace-nowrap">
                                    {{ $movement->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-3 text-slate-700">{{ $movement->store->name }}</td>
                                <td class="px-5 py-3 text-slate-800">
                                    {{ $movement->product->name }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $movement->product->sku }}</span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded {{ $typeClasses }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium whitespace-nowrap
                                    {{ $movement->quantity_delta > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->quantity_delta) }}
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $movement->user->name }}</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ $movement->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No stock movements match your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <noscript>
                <div>{{ $movements->links() }}</div>
            </noscript>
        </div>
    </div>
</x-app-layout>