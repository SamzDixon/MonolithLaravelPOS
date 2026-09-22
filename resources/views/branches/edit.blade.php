<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Edit Branch') }} — {{ $branch->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('branches.update', $branch) }}"
                  class="bg-white border border-slate-200 rounded" autocomplete="off">
                @csrf
                @method('PUT')

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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name', $branch->name) }}" required
                               class="w-full border-slate-300 rounded" autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                        <input type="text" name="code" value="{{ old('code', $branch->code) }}" required
                               class="w-full border-slate-300 rounded font-mono" autocomplete="off">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                        <input type="text" name="location" value="{{ old('location', $branch->location) }}"
                               class="w-full border-slate-300 rounded" autocomplete="off">
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $branch->is_active))
                               class="rounded border-slate-300 text-blue-700 focus:ring-blue-500" autocomplete="off">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-between">
                    <a href="{{ route('branches.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>