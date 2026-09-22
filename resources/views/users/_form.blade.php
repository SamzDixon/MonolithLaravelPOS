@php
    $storesJson = $stores->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'branch_id' => $s->branch_id])->values();
@endphp

<div x-data="userForm({
        initialRole: '{{ old('role', $user->role ?? 'store_manager') }}',
        initialBranch: {{ old('branch_id', $user->branch_id ?? 'null') === null || old('branch_id', $user->branch_id ?? 'null') === '' ? 'null' : (int) old('branch_id', $user->branch_id ?? 'null') }},
        initialStore: {{ old('store_id', $user->store_id ?? 'null') === null || old('store_id', $user->store_id ?? 'null') === '' ? 'null' : (int) old('store_id', $user->store_id ?? 'null') }},
        stores: {{ Illuminate\Support\Js::from($storesJson) }}
     })"
     class="space-y-5">

    {{-- Name --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required autocomplete="off"
               class="w-full border-slate-300 rounded">
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required autocomplete="off"
               class="w-full border-slate-300 rounded">
    </div>

    {{-- Password --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Password
                @isset($user)
                    <span class="text-slate-400 font-normal">— leave blank to keep current</span>
                @endisset
            </label>
            <input type="password" name="password" autocomplete="new-password"
                   class="w-full border-slate-300 rounded" {{ isset($user) ? '' : 'required' }}>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" autocomplete="new-password"
                   class="w-full border-slate-300 rounded" {{ isset($user) ? '' : 'required' }}>
        </div>
    </div>

    {{-- Role --}}
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
        <select name="role" x-model="role" required class="w-full border-slate-300 rounded">
            <option value="admin">Administrator — full system access</option>
            <option value="branch_manager">Branch Manager — all stores in one branch</option>
            <option value="store_manager">Store Manager — one store only</option>
        </select>
    </div>

    {{-- Branch (shown for branch_manager and store_manager) --}}
    <div x-show="role !== 'admin'" x-cloak>
        <label class="block text-sm font-medium text-slate-700 mb-1">Branch</label>
        <select name="branch_id" x-model="branch" class="w-full border-slate-300 rounded">
            <option value="">Select a branch…</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Store (shown only for store_manager) --}}
    <div x-show="role === 'store_manager'" x-cloak>
        <label class="block text-sm font-medium text-slate-700 mb-1">Store</label>
        <select name="store_id" x-model="store" class="w-full border-slate-300 rounded">
            <option value="">Select a store…</option>
            <template x-for="s in filteredStores" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>
        <p class="mt-1 text-xs text-slate-500" x-show="!branch">
            Select a branch first to see the stores in it.
        </p>
    </div>

    {{-- Active --}}
    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1"
               @checked(old('is_active', $user->is_active ?? true))
               class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
        <span class="text-sm text-slate-700">Active</span>
    </label>
</div>

<script>
function userForm({ initialRole, initialBranch, initialStore, stores }) {
    return {
        role: initialRole,
        branch: initialBranch ? String(initialBranch) : '',
        store: initialStore ? String(initialStore) : '',
        stores: stores,

        init() {
            // When branch changes, reset store if the current selection
            // no longer belongs to the new branch.
            this.$watch('branch', () => {
                if (this.store) {
                    const stillValid = this.filteredStores.some(s => String(s.id) === String(this.store));
                    if (!stillValid) this.store = '';
                }
            });
        },

        get filteredStores() {
            if (!this.branch) return [];
            return this.stores.filter(s => String(s.branch_id) === String(this.branch));
        },
    };
}
</script>