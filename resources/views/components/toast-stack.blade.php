<div x-data="toastStack()"
     class="fixed top-4 right-4 z-50 space-y-2 w-80 pointer-events-none">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto px-4 py-3 rounded border shadow-sm text-sm"
             :class="{
                 'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
                 'bg-red-50 text-red-800 border-red-200': toast.type === 'error',
                 'bg-amber-50 text-amber-800 border-amber-200': toast.type === 'warning',
                 'bg-white text-slate-800 border-slate-200': toast.type === 'info',
             }">
            <div class="flex items-start gap-3">
                <div class="flex-1" x-text="toast.message"></div>
                <button type="button" @click="dismiss(toast.id)"
                        class="text-slate-400 hover:text-slate-600 leading-none">&times;</button>
            </div>
        </div>
    </template>
</div>

<script>
function toastStack() {
    return {
        toasts: [],
        init() {
            @if (session('status'))
                this.push({ type: 'success', message: @json(session('status')) });
            @endif
            @if (session('error'))
                this.push({ type: 'error', message: @json(session('error')) });
            @endif
            @if (session('warning'))
                this.push({ type: 'warning', message: @json(session('warning')) });
            @endif
        },
        push(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({
                id,
                type: detail.type || 'info',
                message: detail.message,
                visible: true,
            });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;
            toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 200);
        },
    };
}
</script>