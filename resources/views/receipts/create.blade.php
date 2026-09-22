<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Receive Stock from Supplier') }}
        </h2>
    </x-slot>

    <script>
    window.receiptCreateConfig = {
        products: {{ Illuminate\Support\Js::from($products) }},
    };
    </script>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('receipts.store') }}" id="receipt-form" autocomplete="off"
                  class="bg-white border border-slate-200 rounded">
                @csrf

                <div class="p-6 space-y-5 border-b border-slate-200">
                    @if ($errors->any())
                        <div class="px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Supplier</label>
                            <select name="supplier_id" required class="w-full border-slate-300 rounded text-sm">
                                <option value="">Select a supplier…</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Receiving Store</label>
                            @if ($stores->count() === 1)
                                <input type="hidden" name="store_id" value="{{ $stores->first()->id }}">
                                <div class="px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm text-slate-700">
                                    {{ $stores->first()->name }}
                                </div>
                            @else
                                <select name="store_id" required class="w-full border-slate-300 rounded text-sm">
                                    <option value="">Select a store…</option>
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Product picker --}}
                <div class="p-6 space-y-4 border-b border-slate-200">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Search products</label>
                        <div class="relative">
                            <input type="text" id="product-search" autocomplete="off"
                                   placeholder="Type a product name or SKU…"
                                   class="w-full border-slate-300 rounded text-sm pl-9 pr-3">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <div id="product-results"
                         class="hidden border border-slate-200 rounded max-h-64 overflow-y-auto divide-y divide-slate-100">
                    </div>

                    <div class="pt-2 border-t border-slate-200">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Or pick from the list</label>
                        <select id="product-picker" class="w-full border-slate-300 rounded text-sm">
                            <option value="">Select a product…</option>
                        </select>
                    </div>
                </div>

                {{-- Line items --}}
                <div class="p-6 space-y-4">
                    <div class="border border-slate-200 rounded overflow-hidden">
                        <table class="w-full text-sm" id="items-table">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-4 py-2 font-medium">Product</th>
                                    <th class="text-right px-4 py-2 font-medium w-24">Qty</th>
                                    <th class="text-right px-4 py-2 font-medium w-32">Unit Cost</th>
                                    <th class="text-right px-4 py-2 font-medium w-32">Line Total</th>
                                    <th class="w-12"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr id="empty-row">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">
                                        No items yet. Search or pick a product above.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 border-t border-slate-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-slate-700">Total</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900" id="grand-total">KES 0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" autocomplete="off"
                              class="w-full border-slate-300 rounded text-sm"
                              placeholder="Optional — any notes about this delivery">{{ old('notes') }}</textarea>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('receipts.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-6 py-2 text-sm font-semibold text-white bg-emerald-600 rounded hover:bg-emerald-700">
                        Record Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function ($) {
        $(function () {
            const state = {
                products: window.receiptCreateConfig.products || [],
                rowIndex: 0,
            };

            function money(n) {
                return 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function escapeHtml(s) {
                return String(s ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function filteredProducts(term) {
                if (!term) return [];
                const q = term.toLowerCase();
                return state.products.filter(p =>
                    p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
                );
            }

            function renderResults(term) {
                const $results = $('#product-results');

                if (!term) {
                    $results.addClass('hidden').empty();
                    return;
                }

                const matches = filteredProducts(term);
                $results.removeClass('hidden');

                if (matches.length === 0) {
                    $results.html(
                        '<div class="px-4 py-6 text-center text-sm text-slate-500">'
                        + 'No products match "' + escapeHtml(term) + '".'
                        + '</div>'
                    );
                    return;
                }

                $results.html(matches.map(p =>
                    '<button type="button" data-product-id="' + p.id + '"'
                    + ' class="w-full text-left px-4 py-2 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none flex justify-between items-center gap-3">'
                    + '<span>'
                    +   '<span class="font-medium text-slate-800">' + escapeHtml(p.name) + '</span>'
                    +   '<span class="text-slate-400 text-xs ml-2">' + escapeHtml(p.sku) + '</span>'
                    + '</span>'
                    + '<span class="text-slate-600 text-xs whitespace-nowrap">'
                    +   'Cost KES ' + Number(p.cost_price).toFixed(2)
                    + '</span>'
                    + '</button>'
                ).join(''));
            }

            function renderPicker() {
                let html = '<option value="">Select a product…</option>';
                state.products.forEach(p => {
                    html += '<option value="' + p.id + '"'
                        + ' data-cost="' + p.cost_price + '"'
                        + ' data-name="' + escapeHtml(p.name) + '"'
                        + ' data-sku="' + escapeHtml(p.sku) + '"'
                        + ' data-unit="' + escapeHtml(p.unit) + '">'
                        + escapeHtml(p.name) + ' (' + escapeHtml(p.sku) + ')'
                        + '</option>';
                });
                $('#product-picker').html(html);
            }

            function addProduct(productId) {
                const product = state.products.find(p => String(p.id) === String(productId));
                if (!product) return;

                // If already in the list, focus the qty field rather than
                // adding a duplicate row.
                let existing = null;
                $('#items-body tr').each(function () {
                    if ($(this).find('input[name$="[product_id]"]').val() == product.id) {
                        existing = $(this);
                    }
                });
                if (existing) {
                    existing.find('.qty').focus().select();
                    $('#product-search').val('');
                    renderResults('');
                    $('#product-picker').val('');
                    return;
                }

                $('#empty-row').remove();

                const idx = state.rowIndex++;
                const cost = parseFloat(product.cost_price) || 0;

                const row = $('<tr>').addClass('border-t border-slate-100').attr('data-unit', product.unit);

                row.append(
                    '<td class="px-4 py-2 text-slate-800">' + escapeHtml(product.name) +
                    ' <span class="text-slate-400 text-xs ml-1">' + escapeHtml(product.sku) + '</span>' +
                    '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '"></td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right">' +
                    '<input type="number" name="items[' + idx + '][quantity]" value="1" min="1" required ' +
                    'class="w-20 text-right border-slate-300 rounded text-sm qty"></td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right">' +
                    '<input type="number" name="items[' + idx + '][unit_cost]" value="' + cost.toFixed(2) + '" step="0.01" min="0" required ' +
                    'class="w-28 text-right border-slate-300 rounded text-sm cost"></td>'
                );
                row.append('<td class="px-4 py-2 text-right font-medium line-total">' + money(cost) + '</td>');
                row.append(
                    '<td class="px-4 py-2 text-center">'
                    + '<button type="button" class="remove-row text-slate-400 hover:text-red-600 p-1 rounded hover:bg-red-50" title="Remove item">'
                    + '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />'
                    + '</svg>'
                    + '</button>'
                    + '</td>'
                );

                $('#items-body').append(row);

                $('#product-search').val('').focus();
                $('#product-picker').val('');
                renderResults('');

                recalculate();
            }

            function recalculate() {
                let total = 0;

                $('#items-body tr').each(function () {
                    const $row = $(this);
                    if ($row.attr('id') === 'empty-row') return;

                    const qty = parseInt($row.find('.qty').val()) || 0;
                    const cost = parseFloat($row.find('.cost').val()) || 0;
                    const lineTotal = qty * cost;

                    $row.find('.line-total').text(money(lineTotal));
                    total += lineTotal;
                });

                $('#grand-total').text(money(total));
            }

            renderPicker();

            $('#product-search').on('input', function () {
                renderResults($(this).val().trim());
            });

            $('#product-search').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const matches = filteredProducts($(this).val().trim());
                    if (matches.length > 0) {
                        addProduct(matches[0].id);
                    }
                }
            });

            $('#product-results').on('click', 'button[data-product-id]', function () {
                addProduct($(this).data('product-id'));
            });

            $('#product-picker').on('change', function () {
                const id = $(this).val();
                if (id) addProduct(id);
            });

            $('#items-body').on('input', '.qty, .cost', recalculate);

            $('#items-body').on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
                if ($('#items-body tr').not('#empty-row').length === 0 && $('#empty-row').length === 0) {
                    $('#items-body').append(
                        '<tr id="empty-row"><td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No items yet. Search or pick a product above.</td></tr>'
                    );
                }
                recalculate();
            });

            $('#receipt-form').on('submit', function (e) {
                if ($('#items-body tr').not('#empty-row').length === 0) {
                    e.preventDefault();
                    alert('Add at least one product before recording the receipt.');
                    return false;
                }
            });
        });
    })(window.jQuery);
    </script>
</x-app-layout>