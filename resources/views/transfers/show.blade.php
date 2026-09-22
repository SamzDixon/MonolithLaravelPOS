<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                    Transfer {{ $transfer->reference }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $transfer->fromStore->name }} → {{ $transfer->toStore->name }}
                </p>
            </div>
            <a href="{{ route('transfers.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                ← Back to transfers
            </a>
        </div>
    </x-slot>

    @php
        $canReceive = $transfer->status === 'dispatched' && auth()->user()->can('receive', $transfer);
    @endphp

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

            {{-- Summary --}}
            <div class="bg-white border border-slate-200 rounded p-6">
                <dl class="grid grid-cols-2 gap-y-3 text-sm">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        @php
                            $statusMap = [
                                'pending' => ['Pending', 'bg-amber-50 text-amber-800 border-amber-200'],
                                'dispatched' => ['Dispatched', 'bg-blue-50 text-blue-800 border-blue-200'],
                                'received' => ['Received', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                'cancelled' => ['Cancelled', 'bg-slate-100 text-slate-600 border-slate-200'],
                            ];
                            [$statusLabel, $statusClasses] = $statusMap[$transfer->status] ?? [$transfer->status, 'bg-slate-100 text-slate-600 border-slate-200'];
                        @endphp
                        <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded {{ $statusClasses }}">
                            {{ $statusLabel }}
                        </span>
                    </dd>

                    <dt class="text-slate-500">From</dt>
                    <dd class="text-slate-800">{{ $transfer->fromStore->name }}</dd>

                    <dt class="text-slate-500">To</dt>
                    <dd class="text-slate-800">{{ $transfer->toStore->name }}</dd>

                    <dt class="text-slate-500">Requested by</dt>
                    <dd class="text-slate-800">
                        {{ $transfer->requestedBy->name }} · {{ $transfer->created_at->format('d M Y, H:i') }}
                    </dd>

                    @if ($transfer->dispatchedBy)
                        <dt class="text-slate-500">Dispatched by</dt>
                        <dd class="text-slate-800">
                            {{ $transfer->dispatchedBy->name }} · {{ $transfer->dispatched_at->format('d M Y, H:i') }}
                        </dd>
                    @endif

                    @if ($transfer->receivedBy)
                        <dt class="text-slate-500">Received by</dt>
                        <dd class="text-slate-800">
                            {{ $transfer->receivedBy->name }} · {{ $transfer->received_at->format('d M Y, H:i') }}
                        </dd>
                    @endif

                    @if ($transfer->notes)
                        <dt class="text-slate-500">Notes</dt>
                        <dd class="text-slate-800">{{ $transfer->notes }}</dd>
                    @endif
                </dl>
            </div>

            {{-- Items (form when receiving, read-only otherwise) --}}
            @if ($canReceive)
                <form method="POST" action="{{ route('transfers.receive', $transfer) }}"
                      id="receive-form" autocomplete="off"
                      class="bg-white border border-slate-200 rounded">
                    @csrf

                    <div class="px-5 py-3 border-b border-slate-200 flex justify-between items-center">
                        <div class="text-sm font-semibold text-slate-700">
                            Confirm Received Quantities
                        </div>
                        <div class="text-xs text-slate-500">
                            Adjust any line where the actual arrival differs.
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-5 py-2 font-medium">Product</th>
                                <th class="text-right px-5 py-2 font-medium w-28">Dispatched</th>
                                <th class="text-right px-5 py-2 font-medium w-32">Received</th>
                                <th class="text-right px-5 py-2 font-medium w-28">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->items as $item)
                                <tr class="border-t border-slate-100 receive-row"
                                    data-dispatched="{{ $item->quantity }}">
                                    <td class="px-5 py-2 text-slate-800">
                                        {{ $item->product->name }}
                                        <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="px-5 py-2 text-right text-slate-600">
                                        {{ number_format($item->quantity) }}
                                    </td>
                                    <td class="px-5 py-2 text-right">
                                        <input type="number"
                                               name="items[{{ $loop->index }}][received_quantity]"
                                               value="{{ $item->quantity }}"
                                               min="0" max="{{ $item->quantity }}" required
                                               class="w-24 text-right border-slate-300 rounded text-sm received-qty">
                                    </td>
                                    <td class="px-5 py-2 text-right font-medium difference text-slate-400">0</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="discrepancy-note"
                         class="hidden px-5 py-3 text-sm text-amber-800 bg-amber-50 border-t border-amber-200">
                        One or more lines differ from what was dispatched. The difference will be recorded and the stock ledger will reflect the actual quantities received.
                    </div>

                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                        <a href="{{ route('transfers.index') }}"
                           class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700">
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            @else
                <div class="bg-white border border-slate-200 rounded">
                    <div class="px-5 py-3 border-b border-slate-200 text-sm font-semibold text-slate-700">
                        Items
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-5 py-2 font-medium">Product</th>
                                <th class="text-right px-5 py-2 font-medium">Dispatched</th>
                                <th class="text-right px-5 py-2 font-medium">Received</th>
                                <th class="text-right px-5 py-2 font-medium">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->items as $item)
                                @php
                                    $received = $item->received_quantity;
                                    $difference = $received !== null ? $received - $item->quantity : null;
                                @endphp
                                <tr class="border-t border-slate-100">
                                    <td class="px-5 py-2 text-slate-800">
                                        {{ $item->product->name }}
                                        <span class="text-slate-400 text-xs ml-1">{{ $item->product->sku }}</span>
                                    </td>
                                    <td class="px-5 py-2 text-right text-slate-600">
                                        {{ number_format($item->quantity) }}
                                    </td>
                                    <td class="px-5 py-2 text-right font-medium text-slate-800">
                                        {{ $received !== null ? number_format($received) : '—' }}
                                    </td>
                                    <td class="px-5 py-2 text-right font-medium
                                        {{ $difference === null || $difference === 0
                                            ? 'text-slate-400'
                                            : ($difference < 0 ? 'text-red-600' : 'text-emerald-600') }}">
                                        @if ($difference === null)
                                            —
                                        @elseif ($difference === 0)
                                            0
                                        @else
                                            {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex justify-end gap-2">
                @if ($transfer->status === 'pending' && auth()->user()->can('dispatch', $transfer))
                    <form method="POST" action="{{ route('transfers.dispatch', $transfer) }}">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                            Dispatch Transfer
                        </button>
                    </form>
                @endif

                @if ($transfer->status === 'pending' && auth()->user()->can('delete', $transfer))
                    <x-confirm-delete
                        :action="route('transfers.destroy', $transfer)"
                        title="Cancel transfer {{ $transfer->reference }}?"
                        message="Only pending transfers can be cancelled. No stock has moved yet, so this is safe."
                        confirm-label="Cancel Transfer"
                        trigger-label="Cancel transfer"
                    />
                @endif
            </div>

        </div>
    </div>

    <script>
    (function ($) {
        $(function () {
            function recalcRow($row) {
                const dispatched = parseInt($row.data('dispatched')) || 0;
                const received = parseInt($row.find('.received-qty').val());
                const valid = !isNaN(received);

                const $diff = $row.find('.difference');
                if (!valid) {
                    $diff.text('—').attr('class', 'difference px-5 py-2 text-right font-medium text-slate-400');
                    $row.removeClass('bg-amber-50');
                    return;
                }

                const difference = received - dispatched;
                if (difference === 0) {
                    $diff.text('0').attr('class', 'difference px-5 py-2 text-right font-medium text-slate-400');
                    $row.removeClass('bg-amber-50');
                } else {
                    $diff.text((difference > 0 ? '+' : '') + difference)
                         .attr('class', 'difference px-5 py-2 text-right font-medium ' + (difference < 0 ? 'text-red-600' : 'text-emerald-600'));
                    $row.addClass('bg-amber-50');
                }
            }

            function recalcAll() {
                let anyDiscrepancy = false;
                $('.receive-row').each(function () {
                    recalcRow($(this));
                    if ($(this).hasClass('bg-amber-50')) anyDiscrepancy = true;
                });
                $('#discrepancy-note').toggleClass('hidden', !anyDiscrepancy);
            }

            $('.received-qty').on('input change', function () {
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            recalcAll();
        });
    })(window.jQuery);
    </script>
</x-app-layout>