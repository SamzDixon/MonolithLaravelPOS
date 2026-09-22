<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('New Store') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stores.store') }}" autocomplete="off"
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

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Branch</label>
                        <select name="branch_id" required class="w-full border-slate-300 rounded">
                            <option value="">Select a branch…</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Store Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="off"
                               class="w-full border-slate-300 rounded"
                               placeholder="e.g. Nairobi Main">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Store Code</label>
                        <input type="text" name="code" value="{{ old('code') }}" required autocomplete="off"
                               class="w-full border-slate-300 rounded font-mono"
                               placeholder="e.g. ST-001">
                        <p class="mt-1 text-xs text-slate-500">Short unique identifier used in reports.</p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('stores.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Create Store
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>