@if($employee->canInitiateSeparation() && auth()->user()?->hasPermission('hrm.employees.separation.manage') && ! $employee->pendingSeparation)
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Separation</h2></div>
        <div class="erp-panel-body">
            <p class="text-xs text-gray-500 mb-3">Initiate resignation, termination or other exit through the approval workflow.</p>
            <a href="{{ route('admin.hrm.separations.create', ['employee_id' => $employee->id]) }}" class="erp-btn-sm-primary w-full justify-center">Initiate Separation</a>
        </div>
    </div>
@elseif($employee->pendingSeparation)
    <div class="erp-panel border-amber-200">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Separation Pending</h2></div>
        <div class="erp-panel-body text-xs space-y-2">
            <p><strong>{{ $employee->pendingSeparation->typeLabel() }}</strong> — {{ $employee->pendingSeparation->statusLabel() }}</p>
            <p class="text-gray-500">Last day: {{ $employee->pendingSeparation->last_working_day->format('d M Y') }}</p>
            @if($employee->pendingSeparation->pendingStepLabel())
                <p class="text-amber-700">{{ $employee->pendingSeparation->pendingStepLabel() }}</p>
            @endif
            @if(auth()->user()?->hasPermission('hrm.employees.separation.view'))
                <a href="{{ route('admin.hrm.separations.show', $employee->pendingSeparation) }}" class="erp-btn-sm-secondary w-full justify-center mt-2">View Request</a>
            @endif
        </div>
    </div>
@elseif($employee->isSeparated())
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Separation</h2></div>
        <div class="erp-panel-body text-xs text-gray-500 space-y-1">
            <p>Status: <strong class="text-gray-800">{{ $employee->statusLabel() }}</strong></p>
            @if($employee->separation_date)
                <p>Separated: {{ $employee->separation_date->format('d M Y') }}</p>
            @endif
            @if($employee->last_working_day)
                <p>Last working day: {{ $employee->last_working_day->format('d M Y') }}</p>
            @endif
            @if($employee->latestSeparation && auth()->user()?->hasPermission('hrm.employees.separation.view'))
                <a href="{{ route('admin.hrm.separations.show', $employee->latestSeparation) }}" class="erp-btn-sm-secondary w-full justify-center mt-2">View Separation Record</a>
            @endif
        </div>
    </div>
@endif
