<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Record Sale') }}
        </h2>
    </x-slot>

    <script>
    window.saleCreateConfig = {
        stores: {{ Illuminate\Support\Js::from($stores) }},
        selectedStoreId: {{ Illuminate\Support\Js::from($selectedStoreId) }},
        products: {{ Illuminate\Support\Js::from($products) }},
        stockEndpointTemplate: {{ Illuminate\Support\Js::from(route('sales.stock-for-store', ['store' => '__STORE__'])) }},
        storeEndpoint: {{ Illuminate\Support\Js::from(route('sales.store')) }},
    };
    </script>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sales.store') }}" id="sale-form" autocomplete="off"
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
                            <label class="block text-sm font-medium text-slate-700 mb-1">Store</label>
                            @if ($stores->count() === 1)
                                <input type="hidden" name="store_id" id="store_id" value="{{ $stores->first()->id }}">
                                <div class="px-3 py-2 bg-slate-50 border border-slate-200 rounded text-sm text-slate-700">
                                    {{ $stores->first()->name }}
                                </div>
                            @else
                                <select name="store_id" id="store_id" required class="w-full border-slate-300 rounded text-sm">
                                    @foreach ($stores as $store)
                                        <option value="{{ $store->id }}" @selected($selectedStoreId == $store->id)>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="flex items-end">
                            <div class="text-sm text-slate-500">
                                <span id="stock-status">Loading stock…</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Line items --}}
                <div class="p-6 space-y-4">
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
                        <p class="mt-1 text-xs text-slate-500">
                            Press Enter to add the highlighted product, or use the picker below and click Add Item.
                        </p>
                    </div>

                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Product</label>
                            <select id="product-picker" size="5" class="w-full border-slate-300 rounded text-sm">
                                <option value="">Loading…</option>
                            </select>
                        </div>
                        <button type="button" id="add-row"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800 self-end">
                            Add Item
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded overflow-hidden">
                        <table class="w-full text-sm" id="items-table">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-4 py-2 font-medium">Product</th>
                                    <th class="text-right px-4 py-2 font-medium w-24">Qty</th>
                                    <th class="text-right px-4 py-2 font-medium w-32">Unit Price</th>
                                    <th class="text-right px-4 py-2 font-medium w-32">Line Total</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr id="empty-row">
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">
                                        No items yet. Add a product to begin.
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

                    <p id="stock-warning" class="text-sm text-amber-700 hidden"></p>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('sales.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit" id="submit-sale"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function ($) {
        $(function () {
            const state = {
                products: [],
                rowIndex: 0,
            };

            function money(n) {
                return 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function filteredProducts(term) {
                if (!term) return state.products;
                const q = term.toLowerCase();
                return state.products.filter(p =>
                    p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
                );
            }

            function refreshProductPicker(term) {
                const picker = $('#product-picker');
                const matches = filteredProducts(term);

                if (matches.length === 0) {
                    picker.html('<option value="">No products match "' + (term || '') + '"</option>');
                    return;
                }

                let html = '';
                matches.forEach(p => {
                    html += '<option value="' + p.id + '"'
                        + ' data-price="' + p.selling_price + '"'
                        + ' data-name="' + p.name + '"'
                        + ' data-sku="' + p.sku + '"'
                        + ' data-unit="' + p.unit + '"'
                        + ' data-available="' + p.available + '">'
                        + p.name + ' (' + p.sku + ') — KES ' + p.selling_price.toFixed(2)
                        + ' · ' + p.available + ' ' + p.unit + ' available'
                        + '</option>';
                });
                picker.html(html);

                // If there's a search term and exactly one match, select it.
                if (term && matches.length === 1) {
                    picker.val(matches[0].id);
                }
            }

            function loadStock(storeId) {
                if (!storeId) return;
                const url = window.saleCreateConfig.stockEndpointTemplate.replace('__STORE__', storeId);
                $('#stock-status').text('Loading stock…');
                $('#product-picker').html('<option value="">Loading…</option>');

                $.getJSON(url, function (data) {
                    state.products = data.products || [];
                    refreshProductPicker('');
                    if (state.products.length === 0) {
                        $('#stock-status').text('No products currently in stock at this store.');
                    } else {
                        $('#stock-status').text(state.products.length + ' product' + (state.products.length === 1 ? '' : 's') + ' in stock');
                    }
                }).fail(function () {
                    $('#stock-status').text('Could not load stock for this store.');
                    $('#product-picker').html('<option value="">Failed to load</option>');
                });
            }

            function addRow() {
                const $opt = $('#product-picker').find('option:selected');
                if (!$opt.val()) {
                    return;
                }

                const id = $opt.val();
                const name = $opt.data('name');
                const sku = $opt.data('sku');
                const price = parseFloat($opt.data('price')) || 0;
                const unit = $opt.data('unit');
                const available = parseInt($opt.data('available')) || 0;

                $('#empty-row').remove();

                const idx = state.rowIndex++;

                const row = $('<tr>')
                    .addClass('border-t border-slate-100')
                    .attr('data-available', available)
                    .attr('data-unit', unit);

                row.append(
                    '<td class="px-4 py-2 text-slate-800">' + name +
                    ' <span class="text-slate-400 text-xs ml-1">' + sku + '</span>' +
                    '<input type="hidden" name="items[' + idx + '][product_id]" value="' + id + '"></td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right">' +
                    '<input type="number" name="items[' + idx + '][quantity]" value="1" min="1" required ' +
                    'class="w-20 text-right border-slate-300 rounded text-sm qty"></td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right">' +
                    '<input type="number" name="items[' + idx + '][unit_price]" value="' + price.toFixed(2) + '" step="0.01" readonly ' +
                    'class="w-28 text-right bg-slate-50 border-slate-300 rounded text-sm price"></td>'
                );
                row.append('<td class="px-4 py-2 text-right font-medium line-total">' + money(price) + '</td>');
                row.append('<td class="px-4 py-2 text-center"><button type="button" class="text-red-600 hover:text-red-800 remove-row">×</button></td>');

                $('#items-body').append(row);

                // Reset the picker and focus the search so the operator can type the next item immediately.
                $('#product-search').val('').focus();
                refreshProductPicker('');

                recalculate();
            }

            function recalculate() {
                let total = 0;
                let warning = null;

                $('#items-body tr').each(function () {
                    const $row = $(this);
                    if ($row.attr('id') === 'empty-row') return;

                    const qty = parseInt($row.find('.qty').val()) || 0;
                    const price = parseFloat($row.find('.price').val()) || 0;
                    const available = parseInt($row.attr('data-available')) || 0;

                    const lineTotal = qty * price;
                    $row.find('.line-total').text(money(lineTotal));
                    total += lineTotal;

                    if (qty > available) {
                        $row.addClass('bg-amber-50');
                        warning = warning || 'One or more rows exceed available stock. The server will reject this sale.';
                    } else {
                        $row.removeClass('bg-amber-50');
                    }
                });

                $('#grand-total').text(money(total));

                if (warning) {
                    $('#stock-warning').text(warning).removeClass('hidden');
                } else {
                    $('#stock-warning').addClass('hidden');
                }
            }

            // Initial load
            loadStock($('#store_id').val());

            // Store change reloads stock and clears items
            $('#store_id').on('change', function () {
                $('#items-body').empty().append(
                    '<tr id="empty-row"><td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No items yet. Add a product to begin.</td></tr>'
                );
                state.rowIndex = 0;
                recalculate();
                loadStock($(this).val());
            });

            // Search filters the picker
            $('#product-search').on('input', function () {
                refreshProductPicker($(this).val().trim());
            });

            // Enter in the search field adds the selected product
            $('#product-search').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if ($('#product-picker').val()) {
                        addRow();
                    }
                }
            });

            $('#add-row').on('click', addRow);

            $('#items-body').on('input', '.qty', recalculate);

            $('#items-body').on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
                if ($('#items-body tr').not('#empty-row').length === 0 && $('#empty-row').length === 0) {
                    $('#items-body').append(
                        '<tr id="empty-row"><td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No items yet. Add a product to begin.</td></tr>'
                    );
                }
                recalculate();
            });

            $('#sale-form').on('submit', function (e) {
                if ($('#items-body tr').not('#empty-row').length === 0) {
                    e.preventDefault();
                    alert('Add at least one product before recording the sale.');
                    return false;
                }
            });
        });
    })(window.jQuery);
    </script>
</x-app-layout>