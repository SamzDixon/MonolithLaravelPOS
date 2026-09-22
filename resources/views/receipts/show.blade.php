<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Receipt {{ $receipt->reference }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $receipt->supplier->name }} → {{ $receipt->store->name }}
                </p>
            </div>
            <a href="{{ route('receipts.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                ← Back to receipts
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            @if ($receipt->status === 'reversed')
                <div class="px-4 py-3 text-sm text-slate-700 bg-slate-100 border border-slate-200 rounded">
                    This receipt has been reversed. Stock has been returned to the ledger's negative side and the receipt is closed.
                </div>
            @endif

            {{-- Summary --}}
            <div class="bg-white border border-slate-200 rounded p-6">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        @if ($receipt->status === 'reversed')
                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">Reversed</span>
                        @else
                            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">Received</span>
                        @endif
                    </dd>

                    <dt class="text-slate-500">Supplier</dt>
                    <dd class="text-slate-800">
                        {{ $receipt->supplier->name }}
                        @if ($receipt->supplier->contact_person)
                            <span class="text-slate-400 text-xs">· {{ $receipt->supplier->contact_person }}</span>
                        @endif
                    </dd>

                    @if ($receipt->supplier->phone)
                        <dt class="text-slate-500">Supplier phone</dt>
                        <dd class="text-slate-800 font-mono text-xs">{{ $receipt->supplier->phone }}</dd>
                    @endif

                    <dt class="text-slate-500">Received at</dt>
                    <dd class="text-slate-800">{{ $receipt->store->name }}</dd>

                    <dt class="text-slate-500">Received by</dt>
                    <dd class="text-slate-800">{{ $receipt->user->name }}</dd>

                    <dt class="text-slate-500">Date</dt>
                    <dd class="text-slate-800">{{ $receipt->received_at->format('d M Y, H:i') }}</dd>

                    @if ($receipt->notes)
                        <dt class="text-slate-500">Notes</dt>
                        <dd class="text-slate-800">{{ $receipt->notes }}</dd>
                    @endif
                </dl>
            </div>

            {{-- Items --}}
            <div class="bg-white border border-slate-200 rounded">
                <div class="px-5 py-3 border-b border-slate-200 text-sm font-semibold text-slate-700">
                    Items Received
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">Product</th>
                            <th class="text-right px-5 py-2 font-medium">Qty</th>
                            <th class="text-right px-5 py-2 font-medium">Unit Cost</th>
                            <th class="text-right px-5 py-2 font-medium">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receipt->items as $item)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2 text-slate-800">
                                    {{ $item->product->name }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                </td>
                                <td class="px-5 py-2 text-right text-slate-700">
                                    {{ number_format($item->quantity) }}
                                    <span class="text-slate-400 text-xs ml-1">{{ $item->product->unit }}</span>
                                </td>
                                <td class="px-5 py-2 text-right text-slate-700">KES {{ number_format($item->unit_cost, 2) }}</td>
                                <td class="px-5 py-2 text-right font-medium text-slate-900">KES {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="px-5 py-3 text-right font-semibold text-slate-700">Total</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900">
                                KES {{ number_format($receipt->total_cost, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Actions --}}
            @if ($receipt->status !== 'reversed')
                @can('delete', $receipt)
                    <div class="flex justify-end">
                        <x-confirm-delete
                            :action="route('receipts.destroy', $receipt)"
                            title="Reverse receipt {{ $receipt->reference }}?"
                            message="Reversing removes the received quantities from store stock and records a compensating ledger entry. It cannot be undone."
                            confirm-label="Reverse Receipt"
                            trigger-label="Reverse this receipt"
                        />
                    </div>
                @endcan
            @endif

        </div>
    </div>
</x-app-layout>