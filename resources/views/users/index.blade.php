<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Users') }}
            </h2>
            <a href="{{ route('users.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                New User
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
                            <th class="text-left px-5 py-3 font-medium">Email</th>
                            <th class="text-left px-5 py-3 font-medium">Role</th>
                            <th class="text-left px-5 py-3 font-medium">Scope</th>
                            <th class="text-center px-5 py-3 font-medium">Status</th>
                            <th class="w-32"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $roleLabels = [
                                    'admin' => ['Admin', 'bg-blue-50 text-blue-800 border-blue-200'],
                                    'branch_manager' => ['Branch Manager', 'bg-slate-100 text-slate-700 border-slate-200'],
                                    'store_manager' => ['Store Manager', 'bg-emerald-50 text-emerald-800 border-emerald-200'],
                                ];
                                [$roleLabel, $roleClasses] = $roleLabels[$user->role] ?? [$user->role, 'bg-slate-100 text-slate-600 border-slate-200'];
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium border rounded {{ $roleClasses }}">
                                        {{ $roleLabel }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-700">
                                    @if ($user->isAdmin())
                                        <span class="text-slate-500">Full system</span>
                                    @elseif ($user->store)
                                        {{ $user->store->name }}
                                        <span class="text-slate-400 text-xs">· {{ $user->branch?->name }}</span>
                                    @elseif ($user->branch)
                                        {{ $user->branch->name }}
                                        <span class="text-slate-400 text-xs">· all stores</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if ($user->is_active)
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
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="text-blue-700 hover:text-blue-900 font-medium">Edit</a>
                                    @endcan
                                    @can('delete', $user)
                                        <x-confirm-delete
                                            :action="route('users.destroy', $user)"
                                            title="Remove {{ $user->name }}?"
                                            message="The user will lose access immediately. Their historical sales and transfers remain attributed to them."
                                            confirm-label="Remove User"
                                        />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                    No users yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>