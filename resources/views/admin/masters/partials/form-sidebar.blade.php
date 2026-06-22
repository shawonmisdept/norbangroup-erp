@php
    $isEdit = isset($record);
    $isActive = (bool) old('is_active', $isEdit ? $record->is_active : true);
@endphp

<div class="erp-panel xl:sticky xl:top-[4.5rem]">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
            {{ $isEdit ? 'Record' : 'Settings' }}
        </h2>
    </div>
    <div class="erp-panel-body space-y-4">

        @if($isEdit)
            <div>
                <p class="erp-form-label">Reference Code</p>
                <code class="block text-sm font-mono bg-gray-100 border border-erp-border px-3 py-2 rounded-sm text-brand">{{ $record->code }}</code>
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="erp-form-label !mb-0.5">Created</p>
                    <p class="text-gray-600 tabular-nums">{{ $record->created_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="erp-form-label !mb-0.5">Updated</p>
                    <p class="text-gray-600 tabular-nums">{{ $record->updated_at->format('d M Y') }}</p>
                </div>
            </div>
            <hr class="border-erp-border">
        @endif

        {{-- Status toggle --}}
        <div x-data="{ active: {{ $isActive ? 'true' : 'false' }} }">
            <p class="erp-form-label">Status</p>
            <input type="hidden" name="is_active" :value="active ? 1 : 0">
            <button type="button" @click="active = !active"
                    class="w-full flex items-center justify-between p-3 rounded-sm border cursor-pointer transition"
                    :class="active ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-erp-border'">
                <div class="text-left">
                    <p class="text-sm font-medium text-gray-800" x-text="active ? 'Active' : 'Inactive'"></p>
                    <p class="text-[11px] text-gray-400" x-text="active ? 'Visible in dropdowns' : 'Hidden from use'"></p>
                </div>
                <div class="w-10 h-5 rounded-full relative transition-colors shrink-0"
                     :class="active ? 'bg-emerald-500' : 'bg-gray-300'">
                    <div class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"
                         :class="active ? 'left-[22px]' : 'left-0.5'"></div>
                </div>
            </button>
        </div>

        {{-- Actions --}}
        <div class="pt-2 space-y-2">
            <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">
                {{ $isEdit ? 'Save Changes' : 'Create ' . $config['label'] }}
            </button>
            @if($isEdit)
                <a href="{{ route('admin.masters.show', [$module, $record]) }}" class="erp-btn-secondary w-full justify-center !py-2">
                    Cancel
                </a>
            @else
                <a href="{{ route('admin.masters.index', $module) }}" class="erp-btn-secondary w-full justify-center !py-2">
                    Cancel
                </a>
            @endif
        </div>

        @if($isEdit)
            <hr class="border-erp-border">
            <a href="{{ route('admin.masters.show', [$module, $record]) }}"
               class="flex items-center gap-2 text-xs text-brand hover:underline">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                View record
            </a>
        @endif
    </div>
</div>
