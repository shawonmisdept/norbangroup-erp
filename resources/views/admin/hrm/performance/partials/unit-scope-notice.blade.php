@if(auth()->user()?->factory_id && ($scopedFactoryName ?? null))
    <div class="erp-panel mb-4 border-brand/20 bg-brand/5">
        <div class="erp-panel-body text-xs text-gray-600 leading-relaxed">
            <strong>Unit scope:</strong> You are assigned to <strong>{{ $scopedFactoryName }}</strong>.
            HR / admin users tied to one unit cannot view or manage another unit’s data unless their role grants cross-unit access (no unit assignment on the user account).
        </div>
    </div>
@elseif(! auth()->user()?->factory_id && count($factories ?? []) > 1)
    <div class="erp-panel mb-4 border-gray-200 bg-gray-50/80">
        <div class="erp-panel-body text-xs text-gray-600 leading-relaxed">
            <strong>Group access:</strong> You can work across all factories your permissions allow. Use the factory filter to narrow lists by unit.
        </div>
    </div>
@endif
