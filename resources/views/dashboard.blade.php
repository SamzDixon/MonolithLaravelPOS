<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    @if ($user->isAdmin())
                        Full business overview
                    @elseif ($user->isBranchManager())
                        {{ $user->branch?->name ?? 'Your branch' }} — all stores
                    @else
                        {{ $user->store?->name ?? 'Your store' }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded
                    {{ $user->isAdmin()
                        ? 'bg-blue-50 text-blue-800 border-blue-200'
                        : ($user->isBranchManager()
                            ? 'bg-slate-100 text-slate-700 border-slate-200'
                            : 'bg-emerald-50 text-emerald-800 border-emerald-200') }}">
                    {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                </span>
                <div class="text-xs text-slate-500 mt-1">{{ now()->format('d M Y') }}</div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (empty($storeIds))
                <div class="bg-white border border-slate-200 rounded p-12 text-center text-slate-500">
                    No stores are assigned to your account. Contact an administrator.
                </div>
            @else

                {{-- KPI strip: same for everyone --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white border border-slate-200 rounded p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Stock Value</div>
                        <div class="mt-2 text-xl font-semibold text-slate-900">
                            KES {{ number_format($summary['stock_value'], 2) }}
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Sales Today</div>
                        <div class="mt-2 text-xl font-semibold text-slate-900">
                            KES {{ number_format($summary['sales_today'], 2) }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $summary['sales_today_count'] }} {{ Str::plural('sale', $summary['sales_today_count']) }}
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Units on Hand</div>
                        <div class="mt-2 text-xl font-semibold text-slate-900">
                            {{ number_format($summary['units_on_hand']) }}
                        </div>
                    </div>

                    <a href="{{ route('stock-levels.index', ['low_stock' => 1]) }}"
                       class="block bg-white border border-slate-200 rounded p-4 hover:border-blue-300 transition">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Low Stock</div>
                        <div class="mt-2 text-xl font-semibold {{ $summary['low_stock_count'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                            {{ $summary['low_stock_count'] }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">items at/below reorder</div>
                    </a>

                    <a href="{{ route('transfers.index') }}"
                       class="block bg-white border border-slate-200 rounded p-4 hover:border-blue-300 transition">
                        <div class="text-xs uppercase tracking-wide text-slate-500">Open Transfers</div>
                        <div class="mt-2 text-xl font-semibold {{ ($summary['pending_transfers'] + $summary['dispatched_transfers']) > 0 ? 'text-blue-700' : 'text-slate-900' }}">
                            {{ $summary['pending_transfers'] + $summary['dispatched_transfers'] }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $summary['pending_transfers'] }} pending
                            @if ($summary['dispatched_transfers'] > 0)
                                · {{ $summary['dispatched_transfers'] }} in transit
                            @endif
                        </div>
                    </a>
                </div>

                {{-- ============================================================= --}}
                {{-- ADMIN: branch breakdown --}}
                {{-- ============================================================= --}}
                @if ($user->isAdmin())
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-semibold text-slate-700">Branches</h3>
                            <a href="{{ route('branches.index') }}"
                               class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($branches as $branch)
                                <div class="bg-white border border-slate-200 rounded p-5">
                                    <div class="font-semibold text-slate-900">{{ $branch['name'] }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $branch['location'] ?? '—' }} · {{ $branch['store_count'] }} {{ Str::plural('store', $branch['store_count']) }}
                                    </div>
                                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <div class="text-xs text-slate-500">Stock value</div>
                                            <div class="font-medium text-slate-900">
                                                KES {{ number_format($branch['stock_value'], 2) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-slate-500">Sales today</div>
                                            <div class="font-medium text-slate-900">
                                                KES {{ number_format($branch['sales_today'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ============================================================= --}}
                {{-- BRANCH MANAGER: store breakdown --}}
                {{-- ============================================================= --}}
                @if ($user->isBranchManager())
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-semibold text-slate-700">Stores in your branch</h3>
                            <a href="{{ route('stores.index') }}"
                               class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @forelse ($stores as $store)
                                <div class="bg-white border border-slate-200 rounded p-5">
                                    <div class="font-semibold text-slate-900">{{ $store['name'] }}</div>
                                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <div class="text-xs text-slate-500">Stock value</div>
                                            <div class="font-medium text-slate-900">
                                                KES {{ number_format($store['stock_value'], 2) }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-slate-500">Sales today</div>
                                            <div class="font-medium text-slate-900">
                                                KES {{ number_format($store['sales_today'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
                                        {{ number_format($store['units']) }} units on hand
                                    </div>
                                </div>
                            @empty
                                <div class="bg-white border border-slate-200 rounded p-5 text-slate-500 text-sm">
                                    No stores in your branch.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- ============================================================= --}}
                {{-- STORE MANAGER: performance summary --}}
                {{-- ============================================================= --}}
                @if ($user->isStoreManager())
                    <div class="bg-white border border-slate-200 rounded p-5">
                        <h3 class="text-sm font-semibold text-slate-700 mb-4">Sales performance</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">Today</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    KES {{ number_format($summary['sales_today'], 2) }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $summary['sales_today_count'] }} {{ Str::plural('sale', $summary['sales_today_count']) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">Last 7 days</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    KES {{ number_format($salesWeek, 2) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">This month</div>
                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    KES {{ number_format($salesMonth, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ============================================================= --}}
                {{-- Low stock + Active transfers --}}
                {{-- Admin: business-wide; Branch manager: their branch --}}
                {{-- ============================================================= --}}
                @if ($user->isAdmin() || $user->isBranchManager())
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- Low stock --}}
                        <div class="bg-white border border-slate-200 rounded">
                            <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                                <div class="text-sm font-semibold text-slate-700">Low Stock Alerts</div>
                                <a href="{{ route('stock-levels.index', ['low_stock' => 1]) }}"
                                   class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse ($lowStockItems as $item)
                                    @php $isEmpty = $item->quantity === 0; @endphp
                                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-slate-800 truncate">
                                                {{ $item->product->name }}
                                                <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-0.5">{{ $item->store->name }}</div>
                                        </div>
                                        <div class="text-right whitespace-nowrap">
                                            <span class="text-sm font-semibold {{ $isEmpty ? 'text-red-600' : 'text-amber-600' }}">
                                                {{ number_format($item->quantity) }}
                                            </span>
                                            <span class="text-xs text-slate-400">/ {{ number_format($item->product->reorder_level) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                                        No items below their reorder level.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Active transfers --}}
                        <div class="bg-white border border-slate-200 rounded">
                            <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                                <div class="text-sm font-semibold text-slate-700">Transfers In Motion</div>
                                <a href="{{ route('transfers.index') }}"
                                   class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse ($activeTransfers as $transfer)
                                    @php
                                        $statusMap = [
                                            'pending' => ['Pending', 'bg-amber-50 text-amber-800 border-amber-200'],
                                            'dispatched' => ['In transit', 'bg-blue-50 text-blue-800 border-blue-200'],
                                        ];
                                        [$label, $classes] = $statusMap[$transfer->status] ?? [$transfer->status, 'bg-slate-100 text-slate-600 border-slate-200'];
                                    @endphp
                                    <a href="{{ route('transfers.show', $transfer) }}"
                                       class="block px-5 py-3 hover:bg-slate-50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-slate-800 truncate">
                                                    {{ $transfer->fromStore->name }} → {{ $transfer->toStore->name }}
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $transfer->reference }}</div>
                                            </div>
                                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded whitespace-nowrap {{ $classes }}">
                                                {{ $label }}
                                            </span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                                        No transfers in motion.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ============================================================= --}}
                {{-- STORE MANAGER: low stock + transfers --}}
                {{-- ============================================================= --}}
                @if ($user->isStoreManager())
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <div class="bg-white border border-slate-200 rounded">
                            <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                                <div class="text-sm font-semibold text-slate-700">Low Stock in Your Store</div>
                                <a href="{{ route('stock-levels.index', ['low_stock' => 1]) }}"
                                   class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse ($lowStockItems as $item)
                                    @php $isEmpty = $item->quantity === 0; @endphp
                                    <div class="px-5 py-3 flex items-center justify-between gap-4">
                                        <div class="text-sm font-medium text-slate-800 truncate">
                                            {{ $item->product->name }}
                                            <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                        </div>
                                        <div class="text-right whitespace-nowrap">
                                            <span class="text-sm font-semibold {{ $isEmpty ? 'text-red-600' : 'text-amber-600' }}">
                                                {{ number_format($item->quantity) }}
                                            </span>
                                            <span class="text-xs text-slate-400">/ {{ number_format($item->product->reorder_level) }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                                        Everything is above its reorder level.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="bg-white border border-slate-200 rounded">
                            <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                                <div class="text-sm font-semibold text-slate-700">Transfers</div>
                                <a href="{{ route('transfers.index') }}"
                                   class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @foreach ($incomingTransfers as $transfer)
                                    <a href="{{ route('transfers.show', $transfer) }}"
                                       class="block px-5 py-3 hover:bg-slate-50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-xs uppercase tracking-wide text-emerald-700 font-medium">Incoming</div>
                                                <div class="text-sm text-slate-800 mt-0.5 truncate">
                                                    from {{ $transfer->fromStore->name }}
                                                </div>
                                                <div class="text-xs text-slate-500 font-mono">{{ $transfer->reference }}</div>
                                            </div>
                                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded whitespace-nowrap
                                                {{ $transfer->status === 'dispatched'
                                                    ? 'bg-blue-50 text-blue-800 border-blue-200'
                                                    : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                                {{ $transfer->status === 'dispatched' ? 'In transit' : 'Pending' }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach

                                @foreach ($outgoingTransfers as $transfer)
                                    <a href="{{ route('transfers.show', $transfer) }}"
                                       class="block px-5 py-3 hover:bg-slate-50">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-xs uppercase tracking-wide text-amber-700 font-medium">Outgoing</div>
                                                <div class="text-sm text-slate-800 mt-0.5 truncate">
                                                    to {{ $transfer->toStore->name }}
                                                </div>
                                                <div class="text-xs text-slate-500 font-mono">{{ $transfer->reference }}</div>
                                            </div>
                                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded whitespace-nowrap
                                                {{ $transfer->status === 'dispatched'
                                                    ? 'bg-blue-50 text-blue-800 border-blue-200'
                                                    : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                                {{ $transfer->status === 'dispatched' ? 'In transit' : 'Pending' }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach

                                @if ($incomingTransfers->isEmpty() && $outgoingTransfers->isEmpty())
                                    <div class="px-5 py-8 text-center text-sm text-slate-500">
                                        No transfers touching your store.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ============================================================= --}}
                {{-- Recent activity: sales + movements --}}
                {{-- ============================================================= --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- Recent sales --}}
                    <div class="bg-white border border-slate-200 rounded">
                        <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                            <div class="text-sm font-semibold text-slate-700">Recent Sales</div>
                            <a href="{{ route('sales.index') }}"
                               class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse ($recentSales as $sale)
                                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                                        <td class="px-5 py-2">
                                            <a href="{{ route('sales.show', $sale) }}"
                                               class="font-mono text-xs text-blue-700 hover:text-blue-900">
                                                {{ $sale->reference }}
                                            </a>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                {{ $sale->store->name }} · {{ $sale->user->name }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-2 text-right">
                                            <div class="font-medium text-slate-900">KES {{ number_format($sale->total_amount, 2) }}</div>
                                            <div class="text-xs text-slate-500">{{ $sale->items_count }} items</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-5 py-8 text-center text-sm text-slate-500">No sales yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Recent movements --}}
                    <div class="bg-white border border-slate-200 rounded">
                        <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                            <div class="text-sm font-semibold text-slate-700">Recent Stock Movements</div>
                            <a href="{{ route('stock-movements.index') }}"
                               class="text-xs text-blue-700 hover:text-blue-900 font-medium">View all →</a>
                        </div>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse ($recentMovements as $movement)
                                    @php
                                        $typeLabels = [
                                            'opening' => 'Opening',
                                            'receipt' => 'Receipt',
                                            'sale' => 'Sale',
                                            'transfer_in' => 'Transfer in',
                                            'transfer_out' => 'Transfer out',
                                            'adjustment' => 'Adjustment',
                                        ];
                                    @endphp
                                    <tr class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer"
                                        onclick="window.location='{{ route('stock-movements.index', ['search' => $movement->product->sku]) }}'">
                                        <td class="px-5 py-2">
                                            <div class="text-slate-800 truncate">
                                                {{ $movement->product->name }}
                                                <span class="text-slate-400 text-xs ml-1">{{ $movement->product->sku }}</span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                {{ $typeLabels[$movement->type] ?? $movement->type }}
                                                · {{ $movement->store->name }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-2 text-right whitespace-nowrap">
                                            <div class="font-medium {{ $movement->quantity_delta > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->quantity_delta) }}
                                            </div>
                                            <div class="text-xs text-slate-500">{{ $movement->created_at->diffForHumans() }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-5 py-8 text-center text-sm text-slate-500">No movements yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            @endif
        </div>
    </div>
</x-app-layout>