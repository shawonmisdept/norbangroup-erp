@if($canViewSettlement && $employee->finalSettlement)
    <div class="erp-panel">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Final Settlement (F&F)</h2>
        </div>
        <div class="erp-panel-body space-y-3 text-sm">
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-500">Net payable</span>
                <strong class="text-brand tabular-nums">৳{{ number_format((float) $employee->finalSettlement->net_payable, 2) }}</strong>
            </div>
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-gray-500">Last working day</span>
                <span>{{ $employee->finalSettlement->last_working_day->format('d M Y') }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-gray-500">Status</span>
                @php
                    $fnfBadge = match($employee->finalSettlement->status) {
                        'paid' => 'bg-green-100 text-green-800',
                        'approved' => 'bg-blue-100 text-blue-800',
                        default => 'bg-amber-100 text-amber-800',
                    };
                @endphp
                <span class="erp-badge {{ $fnfBadge }}">{{ $employee->finalSettlement->statusLabel() }}</span>
            </div>
            <a href="{{ route('admin.hrm.finance.final-settlement.show', $employee->finalSettlement) }}" class="erp-btn-sm-secondary w-full justify-center">View F&F Sheet</a>
        </div>
    </div>
@elseif($canManageSettlement && in_array($employee->status, ['resigned', 'terminated'], true) && ! $employee->finalSettlement)
    <div class="erp-panel">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Final Settlement</h2>
        </div>
        <div class="erp-panel-body">
            <p class="text-xs text-gray-500 mb-3">Employee separated — create full & final settlement.</p>
            <a href="{{ route('admin.hrm.finance.final-settlement.create', ['employee_id' => $employee->id]) }}" class="erp-btn-sm-primary w-full justify-center">Start F&F</a>
        </div>
    </div>
@endif
