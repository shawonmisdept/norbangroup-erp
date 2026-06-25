@if(auth()->user()?->hasPermission('hrm.employees.discipline.view') && $employee->disciplinaryRecords->isNotEmpty())
    <div class="erp-panel">
        <div class="erp-panel-head flex items-center justify-between">
            <h2 class="text-xs font-semibold text-gray-700 uppercase">Disciplinary</h2>
            @if(auth()->user()?->hasPermission('hrm.employees.discipline.manage'))
                <a href="{{ route('admin.hrm.discipline.create', ['employee_id' => $employee->id]) }}" class="text-[10px] text-brand hover:underline">Add</a>
            @endif
        </div>
        <div class="erp-panel-body space-y-2">
            @foreach($employee->disciplinaryRecords->take(5) as $record)
                @php
                    $badge = $record->status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600';
                @endphp
                <a href="{{ route('admin.hrm.discipline.show', $record) }}" class="block border border-erp-border rounded-sm p-2 hover:bg-gray-50">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-medium text-gray-800">{{ $record->typeLabel() }}</p>
                        <span class="erp-badge {{ $badge }} text-[9px]">{{ $record->statusLabel() }}</span>
                    </div>
                    <p class="text-[10px] text-gray-500">{{ $record->incident_date->format('d M Y') }}</p>
                </a>
            @endforeach
            @if($employee->disciplinaryRecords->count() > 5)
                <a href="{{ route('admin.hrm.discipline.index', ['search' => $employee->employee_code]) }}" class="text-xs text-brand hover:underline">View all records</a>
            @endif
        </div>
    </div>
@elseif(auth()->user()?->hasPermission('hrm.employees.discipline.manage') && in_array($employee->status, ['active', 'probation', 'suspended'], true))
    <div class="erp-panel">
        <div class="erp-panel-body">
            <a href="{{ route('admin.hrm.discipline.create', ['employee_id' => $employee->id]) }}" class="erp-btn-sm-secondary w-full justify-center">Record Disciplinary Action</a>
        </div>
    </div>
@endif
