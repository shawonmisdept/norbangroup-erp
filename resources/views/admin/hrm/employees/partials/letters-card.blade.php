@if(auth()->user()?->hasPermission('hrm.employees.letters.view') && $employee->issuedLetters->isNotEmpty())
    <div class="erp-panel">
        <div class="erp-panel-head flex items-center justify-between">
            <h2 class="text-xs font-semibold text-gray-700 uppercase">HR Letters</h2>
            @if(auth()->user()?->hasPermission('hrm.employees.letters.manage'))
                <a href="{{ route('admin.hrm.letters.create', ['employee_id' => $employee->id]) }}" class="text-[10px] text-brand hover:underline">Issue</a>
            @endif
        </div>
        <div class="erp-panel-body space-y-2">
            @foreach($employee->issuedLetters->take(5) as $letter)
                <a href="{{ route('admin.hrm.letters.show', $letter) }}" class="block border border-erp-border rounded-sm p-2 hover:bg-gray-50">
                    <p class="text-xs font-medium text-gray-800">{{ $letter->typeLabel() }}</p>
                    <p class="text-[10px] text-gray-500">{{ $letter->issued_at->format('d M Y') }} · {{ $letter->reference_no }}</p>
                </a>
            @endforeach
            @if($employee->issuedLetters->count() > 5)
                <a href="{{ route('admin.hrm.letters.index', ['search' => $employee->employee_code]) }}" class="text-xs text-brand hover:underline">View all letters</a>
            @endif
        </div>
    </div>
@elseif(auth()->user()?->hasPermission('hrm.employees.letters.manage'))
    <div class="erp-panel">
        <div class="erp-panel-body">
            <a href="{{ route('admin.hrm.letters.create', ['employee_id' => $employee->id]) }}" class="erp-btn-sm-secondary w-full justify-center">Issue HR Letter</a>
        </div>
    </div>
@endif
