<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Branches') }}
            </h2>
            <a href="{{ route('branches.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                New Branch
            </a>
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
                            <th class="text-left px-5 py-3 font-medium">Code</th>
                            <th class="text-left px-5 py-3 font-medium">Location</th>
                            <th class="text-right px-5 py-3 font-medium">Stores</th>
                            <th class="text-right px-5 py-3 font-medium">Users</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="w-32"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $branch->name }}</td>
                                <td class="px-5 py-3 text-slate-600 font-mono text-xs">{{ $branch->code }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $branch->location ?? '—' }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $branch->stores_count }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ $branch->users_count }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if ($branch->is_active)
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
                                    <a href="{{ route('branches.edit', $branch) }}"
                                       class="text-blue-700 hover:text-blue-900 font-medium">Edit</a>
                                    <x-confirm-delete
                                        :action="route('branches.destroy', $branch)"
                                        title="Delete {{ $branch->name }}?"
                                        :message="$branch->stores_count > 0 || $branch->users_count > 0
                                            ? 'This branch has ' . $branch->stores_count . ' store(s) and ' . $branch->users_count . ' user(s) assigned. Reassign them before deleting.'
                                            : 'This will remove the branch. You can restore it later if needed.'"
                                        confirm-label="Delete Branch"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No branches yet. <a href="{{ route('branches.create') }}" class="text-blue-700 hover:underline">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $branches->links() }}</div>
        </div>
    </div>
</x-app-layout>