<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Stock Receipts') }}
            </h2>
            @can('create', App\Models\StockReceipt::class)
                <a href="{{ route('receipts.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                    Receive Stock
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Reference</th>
                            <th class="text-left px-5 py-3 font-medium">Supplier</th>
                            <th class="text-left px-5 py-3 font-medium">Store</th>
                            <th class="text-right px-5 py-3 font-medium">Items</th>
                            <th class="text-right px-5 py-3 font-medium">Total Cost</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="text-left px-5 py-3 font-medium">Received By</th>
                            <th class="text-right px-5 py-3 font-medium">Date</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($receipts as $receipt)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $receipt->reference }}</td>
                                <td class="px-5 py-3 text-slate-800">{{ $receipt->supplier->name }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $receipt->store->name }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $receipt->items_count }}</td>
                                <td class="px-5 py-3 text-right font-medium text-slate-900">
                                    KES {{ number_format($receipt->total_cost, 2) }}
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if ($receipt->status === 'reversed')
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">
                                            Reversed
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">
                                            Received
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $receipt->user->name }}</td>
                                <td class="px-5 py-3 text-right text-slate-500 text-xs whitespace-nowrap">
                                    {{ $receipt->received_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('receipts.show', $receipt) }}"
                                       class="text-blue-700 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-12 text-center text-slate-500">
                                    No receipts yet.
                                    @can('create', App\Models\StockReceipt::class)
                                        <a href="{{ route('receipts.create') }}" class="text-blue-700 hover:underline">Receive the first stock</a>.
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $receipts->links() }}</div>
        </div>
    </div>
</x-app-layout>