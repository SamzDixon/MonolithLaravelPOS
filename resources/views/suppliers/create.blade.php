<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('New Supplier') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('suppliers.store') }}" autocomplete="off"
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
                        <label class="block text-sm font-medium text-slate-700 mb-1">Supplier Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="off"
                               class="w-full border-slate-300 rounded"
                               placeholder="e.g. Bidco Africa Ltd">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contact Person</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person') }}" autocomplete="off"
                                   class="w-full border-slate-300 rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" autocomplete="off"
                                   class="w-full border-slate-300 rounded font-mono"
                                   placeholder="+2547...">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="off"
                               class="w-full border-slate-300 rounded">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" autocomplete="off"
                               class="w-full border-slate-300 rounded"
                               placeholder="e.g. Thika, Kenya">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3" autocomplete="off"
                                  class="w-full border-slate-300 rounded">{{ old('notes') }}</textarea>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Active</span>
                    </label>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-2">
                    <a href="{{ route('suppliers.index') }}"
                       class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded hover:bg-blue-800">
                        Create Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>