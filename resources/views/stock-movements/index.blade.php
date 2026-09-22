<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Stock Movement History') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filters --}}
            <form method="GET" action="{{ route('stock-movements.index') }}" autocomplete="off"
                  class="bg-white border border-slate-200 rounded p-4 space-y-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Store</label>
                        <select name="store_id" class="w-full border-slate-300 rounded text-sm">
                            <option value="">All stores</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected($filters['store_id'] == $store->id)>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                        <select name="type" class="w-full border-slate-300 rounded text-sm">
                            <option value="">All types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected($filters['type'] === $type)>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" autocomplete="off"
                               placeholder="Product name or SKU…"
                               class="w-full border-slate-300 rounded text-sm">
                    </div>

                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] }}"
                               class="w-full border-slate-300 rounded text-sm">
                    </div>

                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-slate-600 mb-1">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] }}"
                               class="w-full border-slate-300 rounded text-sm">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Filter
                    </button>

                    @if (array_filter($filters))
                        <a href="{{ route('stock-movements.index') }}"
                           class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Ledger --}}
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
                    <tbody>
                        @forelse ($movements as $movement)
                            @php
                                $typeLabels = [
                                    'opening' => ['Opening', 'bg-slate-100 text-slate-700 border-slate-200'],
                                    'sale' => ['Sale', 'bg-blue-50 text-blue-800 border-blue-200'],
                                    'transfer_in' => ['Transfer In', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                    'transfer_out' => ['Transfer Out', 'bg-amber-50 text-amber-800 border-amber-200'],
                                    'adjustment' => ['Adjustment', 'bg-slate-100 text-slate-700 border-slate-200'],
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

            <div>{{ $movements->links() }}</div>
        </div>
    </div>
</x-app-layout>