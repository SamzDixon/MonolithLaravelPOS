@php
    $margin = $product->cost_price > 0
        ? (($product->selling_price - $product->cost_price) / $product->cost_price) * 100
        : null;
@endphp
<tr class="border-t border-slate-100 hover:bg-slate-50">
    <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ $product->sku }}</td>
    <td class="px-5 py-3 font-medium text-slate-900">{{ $product->name }}</td>
    <td class="px-5 py-3 text-slate-600">{{ $product->unit }}</td>
    <td class="px-5 py-3 text-right text-slate-700">{{ number_format($product->cost_price, 2) }}</td>
    <td class="px-5 py-3 text-right text-slate-800 font-medium">{{ number_format($product->selling_price, 2) }}</td>
    <td class="px-5 py-3 text-right font-medium
        {{ $margin === null ? 'text-slate-400' : ($margin >= 15 ? 'text-emerald-600' : ($margin >= 5 ? 'text-amber-600' : 'text-red-600')) }}">
        {{ $margin === null ? '—' : number_format($margin, 1) . '%' }}
    </td>
    <td class="px-5 py-3 text-right text-slate-700">{{ $product->reorder_level }}</td>
    <td class="px-5 py-3 text-center">
        @if ($product->is_active)
            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-emerald-50 text-emerald-800 border-emerald-200">
                Active
            </span>
        @else
            <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded bg-slate-100 text-slate-600 border-slate-200">
                Inactive
            </span>
        @endif
    </td>
    <td class="px-5 py-3 text-right space-x-3">
        @can('update', $product)
            <a href="{{ route('products.edit', $product) }}"
               class="text-blue-700 hover:text-blue-900 font-medium">Edit</a>
        @endcan
        @can('delete', $product)
            <x-confirm-delete
                :action="route('products.destroy', $product)"
                title="Delete {{ $product->name }}?"
                message="This product will be removed from the catalogue. You can restore it later if needed."
                confirm-label="Delete Product"
            />
        @endcan
    </td>
</tr>