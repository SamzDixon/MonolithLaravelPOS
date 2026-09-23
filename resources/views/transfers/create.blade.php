<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('New Stock Transfer') }}
        </h2>
    </x-slot>

    <script>
    window.transferCreateConfig = {
        stockEndpointTemplate: {{ Illuminate\Support\Js::from(route('transfers.stock-for-store', ['store' => '__STORE__'])) }},
        stores: {{ Illuminate\Support\Js::from($toStores) }},
    };
    </script>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('transfers.store') }}" id="transfer-form" autocomplete="off"
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
                            <label class="block text-sm font-medium text-slate-700 mb-1">From store</label>
                            <select name="from_store_id" id="from_store_id" required class="w-full border-slate-300 rounded text-sm">
                                <option value="">Select source…</option>
                                @foreach ($fromStores as $store)
                                    <option value="{{ $store->id }}" @selected(old('from_store_id') == $store->id)>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">To store</label>
                            <select name="to_store_id" id="to_store_id" required class="w-full border-slate-300 rounded text-sm">
                                <option value="">Select destination…</option>
                                @foreach ($toStores as $store)
                                    <option value="{{ $store->id }}" @selected(old('to_store_id') == $store->id)>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-sm text-slate-500">
                        <span id="stock-status">Select a source store to load its stock.</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="2" autocomplete="off"
                                  class="w-full border-slate-300 rounded text-sm"
                                  placeholder="Context for the receiving store">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Product selection --}}
                <div class="p-6 space-y-4 border-b border-slate-200">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Search products</label>
                        <div class="relative">
                            <input type="text" id="product-search" autocomplete="off"
                                   placeholder="Type a product name or SKU…"
                                   class="w-full border-slate-300 rounded text-sm pl-9 pr-3" disabled>
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
                        <select id="product-picker" class="w-full border-slate-300 rounded text-sm" disabled>
                            <option value="">Select a source store first…</option>
                        </select>
                    </div>
                </div>

                {{-- Items --}}
                <div class="p-6 space-y-4">
                    <div class="border border-slate-200 rounded overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="text-left px-4 py-2 font-medium">Product</th>
                                    <th class="text-right px-4 py-2 font-medium w-32">Available</th>
                                    <th class="text-right px-4 py-2 font-medium w-28">Transfer Qty</th>
                                    <th class="w-12"></th>
                                </tr>
                            </thead>
                            <tbody id="items-body">
                                <tr id="empty-row">
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">
                                        No items yet. Select a source store and add products.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p id="stock-warning" class="text-sm text-amber-700 hidden"></p>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('transfers.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit" id="submit-transfer"
                            class="px-6 py-2 text-sm font-semibold text-white bg-blue-700 rounded hover:bg-blue-800">
                        Create Transfer
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
                    +   p.available + ' ' + escapeHtml(p.unit) + ' available'
                    + '</span>'
                    + '</button>'
                ).join(''));
            }

            function renderPicker() {
                let html = '<option value="">Select a product…</option>';
                state.products.forEach(p => {
                    html += '<option value="' + p.id + '">'
                        + escapeHtml(p.name) + ' (' + escapeHtml(p.sku) + ') — '
                        + p.available + ' ' + escapeHtml(p.unit) + ' available'
                        + '</option>';
                });
                $('#product-picker').html(html);
            }

            function loadStock(storeId) {
                if (!storeId) {
                    state.products = [];
                    $('#product-search').prop('disabled', true).val('');
                    $('#product-picker').prop('disabled', true).html('<option value="">Select a source store first…</option>');
                    $('#stock-status').text('Select a source store to load its stock.');
                    renderResults('');
                    return;
                }

                const url = window.transferCreateConfig.stockEndpointTemplate.replace('__STORE__', storeId);
                $('#stock-status').text('Loading stock…');
                $('#product-search').prop('disabled', true);
                $('#product-picker').prop('disabled', true);

                $.getJSON(url, function (data) {
                    state.products = data.products || [];
                    renderPicker();
                    $('#product-search').prop('disabled', false);
                    $('#product-picker').prop('disabled', false);

                    if (state.products.length === 0) {
                        $('#stock-status').text('No products currently in stock at this store.');
                    } else {
                        $('#stock-status').text(state.products.length + ' product' + (state.products.length === 1 ? '' : 's') + ' in stock');
                    }
                }).fail(function () {
                    state.products = [];
                    $('#stock-status').text('Could not load stock for this store.');
                });
            }

            function addProduct(productId) {
                const product = state.products.find(p => String(p.id) === String(productId));
                if (!product) return;

                // Already in the list? Just focus its qty.
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
                    return;
                }

                $('#empty-row').remove();

                const idx = state.rowIndex++;
                const row = $('<tr>')
                    .addClass('border-t border-slate-100')
                    .attr('data-available', product.available)
                    .attr('data-unit', product.unit);

                row.append(
                    '<td class="px-4 py-2 text-slate-800">' + escapeHtml(product.name) +
                    ' <span class="text-slate-400 text-xs ml-1">' + escapeHtml(product.sku) + '</span>' +
                    '<input type="hidden" name="items[' + idx + '][product_id]" value="' + product.id + '"></td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right text-slate-500 text-xs">'
                    + product.available + ' ' + escapeHtml(product.unit)
                    + '</td>'
                );
                row.append(
                    '<td class="px-4 py-2 text-right">' +
                    '<input type="number" name="items[' + idx + '][quantity]" value="1" min="1" required ' +
                    'class="w-20 text-right border-slate-300 rounded text-sm qty"></td>'
                );
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
                let warning = null;

                $('#items-body tr').each(function () {
                    const $row = $(this);
                    if ($row.attr('id') === 'empty-row') return;

                    const qty = parseInt($row.find('.qty').val()) || 0;
                    const available = parseInt($row.attr('data-available')) || 0;

                    if (qty > available) {
                        $row.addClass('bg-amber-50');
                        warning = warning || 'One or more rows exceed available stock at the source store. The dispatch will fail.';
                    } else {
                        $row.removeClass('bg-amber-50');
                    }
                });

                if (warning) {
                    $('#stock-warning').text(warning).removeClass('hidden');
                } else {
                    $('#stock-warning').addClass('hidden');
                }
            }

            function refreshDestinationOptions() {
                const fromId = $('#from_store_id').val();
                const $to = $('#to_store_id');
                const currentTo = $to.val();

                // Rebuild the destination list, excluding the selected source.
                let html = '<option value="">Select destination…</option>';
                const options = window.transferCreateConfig.stores || [];

                options.forEach(s => {
                    if (String(s.id) === String(fromId)) return;
                    html += '<option value="' + s.id + '"' + (String(s.id) === String(currentTo) ? ' selected' : '') + '>' + s.name + '</option>';
                });

                $to.html(html);
            }

            $('#from_store_id').on('change', function () {
                // Clear items — they belong to the previous source store.
                $('#items-body').empty().append(
                    '<tr id="empty-row"><td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">No items yet. Select a source store and add products.</td></tr>'
                );
                state.rowIndex = 0;
                $('#stock-warning').addClass('hidden');

                refreshDestinationOptions();
                loadStock($(this).val());
            });

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
                if (id) {
                    addProduct(id);
                }
            });

            $('#items-body').on('input', '.qty', recalculate);

            $('#items-body').on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
                if ($('#items-body tr').not('#empty-row').length === 0 && $('#empty-row').length === 0) {
                    $('#items-body').append(
                        '<tr id="empty-row"><td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">No items yet. Select a source store and add products.</td></tr>'
                    );
                }
                recalculate();
            });

            $('#transfer-form').on('submit', function (e) {
                if ($('#items-body tr').not('#empty-row').length === 0) {
                    e.preventDefault();
                    alert('Add at least one product before creating the transfer.');
                    return false;
                }
                if ($('#from_store_id').val() === $('#to_store_id').val()) {
                    e.preventDefault();
                    alert('Source and destination stores must be different.');
                    return false;
                }
            });
        });
    })(window.jQuery);
    </script>
</x-app-layout>