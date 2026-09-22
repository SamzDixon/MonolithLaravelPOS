<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Suppliers') }}
            </h2>
            @can('create', App\Models\Supplier::class)
                <a href="{{ route('suppliers.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                    New Supplier
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($errors->any())
                <div class="px-4 py-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Name</th>
                            <th class="text-left px-5 py-3 font-medium">Contact</th>
                            <th class="text-left px-5 py-3 font-medium">Phone</th>
                            <th class="text-left px-5 py-3 font-medium">Email</th>
                            <th class="text-right px-5 py-3 font-medium">Receipts</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="w-32"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $supplier->name }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $supplier->contact_person ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ $supplier->phone ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $supplier->email ?? '—' }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $supplier->stock_receipts_count }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($supplier->is_active)
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
                                    @can('update', $supplier)
                                        <a href="{{ route('suppliers.edit', $supplier) }}"
                                           class="text-blue-700 hover:text-blue-900 font-medium">Edit</a>
                                    @endcan
                                    @can('delete', $supplier)
                                        <x-confirm-delete
                                            :action="route('suppliers.destroy', $supplier)"
                                            title="Delete {{ $supplier->name }}?"
                                            :message="$supplier->stock_receipts_count > 0
                                                ? 'This supplier has receipts on file. Deactivate them instead.'
                                                : 'This will remove the supplier from the list.'"
                                            confirm-label="Delete Supplier"
                                        />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No suppliers yet.
                                    @can('create', App\Models\Supplier::class)
                                        <a href="{{ route('suppliers.create') }}" class="text-blue-700 hover:underline">Add the first one</a>.
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $suppliers->links() }}</div>
        </div>
    </div>
</x-app-layout>