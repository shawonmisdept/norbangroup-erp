@props([
    'routeName',
    'filters',
    'factories' => [],
    'showDateRange' => true,
])

@if(count($factories) > 1 && ! auth()->user()->factory_id)
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route($routeName, array_merge($filters, ['factory_id' => null])) }}"
       class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ empty($filters['factory_id']) ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
        All Companies
    </a>
    @foreach($factories as $id => $name)
        <a href="{{ route($routeName, array_merge($filters, ['factory_id' => $id])) }}"
           class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ (int) ($filters['factory_id'] ?? 0) === (int) $id ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
            {{ $name }}
        </a>
    @endforeach
</div>
@endif

@if($showDateRange)
<div class="erp-panel mb-4">
    <form method="GET" class="erp-panel-body flex flex-wrap items-end gap-3">
        @if($filters['factory_id'] ?? null)
            <input type="hidden" name="factory_id" value="{{ $filters['factory_id'] }}">
        @endif
        <div>
            <label class="erp-form-label">From</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="erp-input !text-xs">
        </div>
        <div>
            <label class="erp-form-label">To</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="erp-input !text-xs">
        </div>
        <button type="submit" class="erp-btn-primary">Apply</button>
    </form>
</div>
@endif
