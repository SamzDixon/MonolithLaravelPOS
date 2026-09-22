<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Stock Value</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        KES {{ number_format($stockValue ?? 0, 2) }}
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Sales Today</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        KES {{ number_format($salesToday ?? 0, 2) }}
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded p-5">
                    <div class="text-sm uppercase tracking-wide text-slate-500">Low Stock Items</div>
                    <div class="mt-2 text-2xl font-semibold {{ ($lowStockCount ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                        {{ $lowStockCount ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded">
                <div class="px-5 py-3 border-b border-slate-200 text-sm font-semibold text-slate-700">
                    Recent Stock Movements
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">When</th>
                            <th class="text-left px-5 py-2 font-medium">Store</th>
                            <th class="text-left px-5 py-2 font-medium">Product</th>
                            <th class="text-right px-5 py-2 font-medium">Change</th>
                            <th class="text-left px-5 py-2 font-medium">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($recentMovements ?? collect()) as $movement)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2 text-slate-600">
                                    {{ $movement->created_at->diffForHumans() }}
                                </td>
                                <td class="px-5 py-2 text-slate-800">{{ $movement->store->name }}</td>
                                <td class="px-5 py-2 text-slate-800">
                                    {{ $movement->product->name }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $movement->product->sku }}</span>
                                </td>
                                <td class="px-5 py-2 text-right font-medium {{ $movement->quantity_delta > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                </td>
                                <td class="px-5 py-2 text-slate-600">{{ $movement->user->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-slate-500">
                                    No stock movements yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>