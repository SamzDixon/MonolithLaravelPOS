<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Sale {{ $sale->reference }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $sale->store->name }} · {{ $sale->created_at->format('d M Y, H:i') }}
                </p>
            </div>
            <a href="{{ route('sales.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                ← Back to sales
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($sale->status === 'voided')
                <div class="px-4 py-3 text-sm text-slate-700 bg-slate-100 border border-slate-200 rounded">
                    This sale was voided on {{ $sale->deleted_at?->format('d M Y, H:i') }}. Stock has been returned.
                </div>
            @endif

            {{-- Header card --}}
            <div class="bg-white border border-slate-200 rounded p-6">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-slate-500">Reference</dt>
                    <dd class="font-mono text-slate-800">{{ $sale->reference }}</dd>

                    <dt class="text-slate-500">Store</dt>
                    <dd class="text-slate-800">{{ $sale->store->name }}</dd>

                    <dt class="text-slate-500">Recorded by</dt>
                    <dd class="text-slate-800">{{ $sale->user->name }}</dd>

                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        @if ($sale->status === 'voided')
                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">Voided</span>
                        @else
                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Completed</span>
                        @endif
                    </dd>
                </dl>
            </div>

            {{-- Items --}}
            <div class="bg-white border border-slate-200 rounded">
                <div class="px-5 py-3 border-b border-slate-200 text-sm font-semibold text-slate-700">
                    Items
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">Product</th>
                            <th class="text-right px-5 py-2 font-medium">Qty</th>
                            <th class="text-right px-5 py-2 font-medium">Unit Price</th>
                            <th class="text-right px-5 py-2 font-medium">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2 text-slate-800">
                                    {{ $item->product->name }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                </td>
                                <td class="px-5 py-2 text-right text-slate-700">{{ number_format($item->quantity) }}</td>
                                <td class="px-5 py-2 text-right text-slate-700">KES {{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-5 py-2 text-right text-slate-900 font-medium">KES {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-semibold text-slate-700">Total</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">
                                KES {{ number_format($sale->total_amount, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Actions --}}
            @can('delete', $sale)
                @if ($sale->status !== 'voided')
                    <div class="flex justify-end">
                        <x-confirm-delete
                            :action="route('sales.destroy', $sale)"
                            title="Void sale {{ $sale->reference }}?"
                            message="Voiding returns the sold quantities to stock and marks the sale as voided. The audit trail is preserved."
                            confirm-label="Void Sale"
                            trigger-label="Void this sale"
                        />
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>