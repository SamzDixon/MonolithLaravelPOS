@props([
    'action',
    'title' => 'Confirm Delete',
    'message' => 'This action cannot be undone.',
    'confirmLabel' => 'Delete',
    'triggerLabel' => 'Delete',
    'triggerClass' => 'text-red-700 hover:text-red-900 font-medium',
])

<div x-data="{ open: false }" class="inline-block">
    {{-- Trigger --}}
    <button type="button" @click="open = true" class="{{ $triggerClass }}">
        {{ $triggerLabel }}
    </button>

    {{-- Modal backdrop + dialog --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-slate-900/50" @click="open = false"></div>

        {{-- Dialog --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white border border-slate-200 rounded shadow-lg max-w-md w-full">

            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2h-5l-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-slate-900 text-left">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600 text-left leading-relaxed">{{ $message }}</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ $action }}">
                @csrf
                @method('DELETE')

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-2 rounded-b">
                    <button type="button" @click="open = false"
                            class="px-4 py-2 text-sm text-slate-700 border border-slate-300 rounded hover:bg-white">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700">
                        {{ $confirmLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>