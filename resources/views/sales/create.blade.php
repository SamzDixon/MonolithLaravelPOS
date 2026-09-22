<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            Record Sale — {{ $store->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sales.store') }}" id="sale-form"
                  class="bg-white border border-slate-200 rounded">
                @csrf
                <input type="hidden" name="store_id" value="{{ $store->id }}">

                <div class="p-5 border-b border-slate-200">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Add product</label>
                    <select id="product-picker" class="w-full border-slate-300 rounded">
                        <option value="">Select a product…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                    data-price="{{ $product->selling_price }}"
                                    data-sku="{{ $product->sku }}">
                                {{ $product->name }} ({{ $product->sku }}) — KES {{ number_format($product->selling_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <table class="w-full text-sm" id="line-items">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-2 font-medium">Product</th>
                            <th class="text-right px-5 py-2 font-medium w-24">Qty</th>
                            <th class="text-right px-5 py-2 font-medium w-32">Unit Price</th>
                            <th class="text-right px-5 py-2 font-medium w-32">Line Total</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body"></tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50">
                            <td colspan="3" class="px-5 py-3 text-right font-semibold text-slate-700">Total</td>
                            <td class="px-5 py-3 text-right font-semibold text-slate-900" id="total">KES 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                @error('items')
                    <div class="px-5 py-3 text-sm text-red-700 bg-red-50 border-t border-red-200">
                        {{ $message }}
                    </div>
                @enderror

                <div class="px-5 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <a href="{{ route('dashboard') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Record Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Line item management. No framework, just enough jQuery to keep the
    // total in sync with the table. If JS is broken, the server still
    // validates and rejects an empty sale — the form degrades safely.
    $(function () {
        const $body = $('#items-body');
        const $total = $('#total');
        let rowIndex = 0;

        function money(n) {
            return 'KES ' + n.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function recalcTotal() {
            let sum = 0;
            $body.find('tr').each(function () {
                const qty = parseInt($(this).find('input[name$="[quantity]"]').val()) || 0;
                const price = parseFloat($(this).find('input[name$="[unit_price]"]').val()) || 0;
                sum += qty * price;
            });
            $total.text(money(sum));
        }

        $('#product-picker').on('change', function () {
            const $opt = $(this).find('option:selected');
            if (!$opt.val()) return;

            const id = $opt.val();
            const name = $opt.text().split(' (')[0];
            const price = parseFloat($opt.data('price'));

            const row = `
                <tr class="border-t border-slate-100">
                    <td class="px-5 py-2 text-slate-800">
                        ${name}
                        <input type="hidden" name="items[${rowIndex}][product_id]" value="${id}">
                    </td>
                    <td class="px-5 py-2 text-right">
                        <input type="number" name="items[${rowIndex}][quantity]" value="1" min="1"
                               class="w-20 text-right border-slate-300 rounded qty">
                    </td>
                    <td class="px-5 py-2 text-right">
                        <input type="number" name="items[${rowIndex}][unit_price]" value="${price}" step="0.01" readonly
                               class="w-28 text-right bg-slate-50 border-slate-300 rounded price">
                    </td>
                    <td class="px-5 py-2 text-right font-medium line-total">${money(price)}</td>
                    <td class="px-2 text-center">
                        <button type="button" class="text-red-600 hover:text-red-800 remove-row">×</button>
                    </td>
                </tr>`;

            $body.append(row);
            rowIndex++;
            $(this).val('');
            recalcTotal();
        });

        $body.on('input', '.qty', function () {
            const $row = $(this).closest('tr');
            const qty = parseInt($(this).val()) || 0;
            const price = parseFloat($row.find('.price').val()) || 0;
            $row.find('.line-total').text(money(qty * price));
            recalcTotal();
        });

        $body.on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            recalcTotal();
        });
    });
    </script>
</x-app-layout>