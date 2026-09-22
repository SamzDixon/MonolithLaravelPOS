<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Transfers') }}
            </h2>
            @can('create', App\Models\StockTransfer::class)
                <a href="{{ route('transfers.create') }}"
                   class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                    New Transfer
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

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

            <div class="bg-white border border-slate-200 rounded overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="text-left px-5 py-3 font-medium">Reference</th>
                            <th class="text-left px-5 py-3 font-medium">From</th>
                            <th class="text-left px-5 py-3 font-medium">To</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="text-left px-5 py-3 font-medium">Requested By</th>
                            <th class="text-right px-5 py-3 font-medium">Created</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            @php
                                $statusMap = [
                                    'pending' => ['Pending', 'bg-amber-50 text-amber-800 border-amber-200'],
                                    'dispatched' => ['Dispatched', 'bg-blue-50 text-blue-800 border-blue-200'],
                                    'received' => ['Received', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                    'cancelled' => ['Cancelled', 'bg-slate-100 text-slate-600 border-slate-200'],
                                ];
                                [$statusLabel, $statusClasses] = $statusMap[$transfer->status] ?? [$transfer->status, 'bg-slate-100 text-slate-600 border-slate-200'];
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $transfer->reference }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $transfer->fromStore->name }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $transfer->toStore->name }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded {{ $statusClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $transfer->requestedBy->name }}</td>
                                <td class="px-5 py-3 text-right text-slate-500 text-xs whitespace-nowrap">
                                    {{ $transfer->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('transfers.show', $transfer) }}"
                                       class="text-blue-700 hover:text-blue-900 font-medium">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No transfers yet.
                                    @can('create', App\Models\StockTransfer::class)
                                        <a href="{{ route('transfers.create') }}" class="text-blue-700 hover:underline">Create the first one</a>.
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $transfers->links() }}</div>
        </div>
    </div>
</x-app-layout>