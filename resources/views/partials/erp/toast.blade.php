<div x-data="toastHub()" x-init="init()" class="fixed top-4 right-4 z-[100] space-y-2 w-full max-w-sm pointer-events-none">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-4"
             class="pointer-events-auto bg-white border rounded-sm shadow-lg p-3.5 flex items-start gap-3"
             :class="{
                'border-emerald-200': toast.type === 'success',
                'border-red-200': toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
                'border-blue-200': toast.type === 'info',
             }">
            <span class="text-base shrink-0"
                  :class="{
                    'text-emerald-500': toast.type === 'success',
                    'text-red-500': toast.type === 'error',
                    'text-amber-500': toast.type === 'warning',
                    'text-blue-500': toast.type === 'info',
                  }"
                  x-text="toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : toast.type === 'warning' ? '!' : 'i'"></span>
            <p class="text-sm text-gray-700 flex-1 leading-snug" x-text="toast.message"></p>
            <button type="button" @click="dismiss(toast.id)" class="text-gray-400 hover:text-gray-600 text-xs shrink-0">✕</button>
        </div>
    </template>
</div>

@if(session('success'))
    <span id="flash-success" class="hidden">{{ session('success') }}</span>
@endif
@if(session('error'))
    <span id="flash-error" class="hidden">{{ session('error') }}</span>
@endif
@if($errors->any())
    <span id="flash-error" class="hidden">{{ $errors->first() }}</span>
@endif
