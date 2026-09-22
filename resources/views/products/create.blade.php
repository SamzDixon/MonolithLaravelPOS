<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('New Product') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('products.store') }}" autocomplete="off"
                  class="bg-white border border-slate-200 rounded">
                @csrf

                <div class="p-6 space-y-5">
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
                            <label class="block text-sm font-medium text-slate-700 mb-1">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku') }}" required autocomplete="off"
                                   class="w-full border-slate-300 rounded font-mono"
                                   placeholder="e.g. SUG-2KG">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Unit</label>
                            <select name="unit" required class="w-full border-slate-300 rounded">
                                @foreach (['pcs', 'pkt', 'kg', 'ltr', 'box', 'bale', 'crate', 'bar', 'roll'] as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit') === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Product Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="off"
                               class="w-full border-slate-300 rounded"
                               placeholder="e.g. Sugar 2kg">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cost Price (KES)</label>
                            <input type="number" name="cost_price" value="{{ old('cost_price') }}" required
                                   step="0.01" min="0" autocomplete="off"
                                   class="w-full border-slate-300 rounded" placeholder="0.00">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Selling Price (KES)</label>
                            <input type="number" name="selling_price" value="{{ old('selling_price') }}" required
                                   step="0.01" min="0" autocomplete="off"
                                   class="w-full border-slate-300 rounded" placeholder="0.00">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Reorder Level</label>
                        <input type="number" name="reorder_level" value="{{ old('reorder_level', 0) }}" required
                               min="0" autocomplete="off"
                               class="w-full border-slate-300 rounded">
                        <p class="mt-1 text-xs text-slate-500">
                            The dashboard flags this product when total stock falls to or below this number. Set to 0 to disable.
                        </p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('products.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Create Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>