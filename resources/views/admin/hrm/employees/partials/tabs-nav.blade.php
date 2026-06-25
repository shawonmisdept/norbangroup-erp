@php
    $tabs = $tabs ?? config('hrm.employee_tabs', []);
@endphp

<div class="flex border-b border-erp-border bg-gray-50/80 overflow-x-auto shrink-0">
    @foreach($tabs as $key => $label)
        <button type="button" @click="tab = '{{ $key }}'"
                class="px-4 py-3 text-xs font-semibold uppercase tracking-wide border-b-2 transition whitespace-nowrap"
                :class="tab === '{{ $key }}' ? 'border-gold text-brand bg-white' : 'border-transparent text-gray-400 hover:text-gray-600'">
            {{ $label }}
        </button>
    @endforeach
</div>
