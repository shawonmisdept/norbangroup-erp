@extends('layouts.admin')
@section('title', 'Increment Bulk')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Increment Bulk',
    'subtitle' => 'Apply increment rule to selected employees — gross recalculates from grade',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'increment-bulk'])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.salary.increment-bulk.index') }}" class="erp-filter-bar">
            <div class="erp-filter-field">
                <label class="erp-form-label">Grade</label>
                <select name="salary_grade_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    @foreach($grades as $grade)
                        <option value="{{ $grade->id }}" {{ $selectedGradeId === (string) $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-filter-field-grow">
                <label class="erp-form-label">Increment Rule</label>
                <select name="rule_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    @forelse($rules as $rule)
                        <option value="{{ $rule->id }}" {{ $selectedRuleId === (string) $rule->id ? 'selected' : '' }}>{{ $rule->name }} ({{ $rule->valueLabel() }})</option>
                    @empty
                        <option value="">No rules — create one first</option>
                    @endforelse
                </select>
            </div>
        </form>
    </div>
</div>

@if($canManage && $selectedRule && $employees->isNotEmpty())
<form method="POST" action="{{ route('admin.hrm.salary.increment-bulk.apply') }}" id="bulk-form"
      data-confirm="Apply {{ $selectedRule->name }} to selected employees?"
      data-confirm-variant="warning"
      data-confirm-ok="Yes, apply">
    @csrf
    <input type="hidden" name="rule_id" value="{{ $selectedRule->id }}">

    <div class="erp-panel mb-4 overflow-hidden">
        <div class="erp-panel-head flex-wrap gap-2">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Employees — {{ $selectedGrade?->name }}</h2>
            <button type="submit" class="erp-btn-primary !py-1.5 !px-3 text-xs"
                   >
                Apply Increment
            </button>
        </div>
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-10"><input type="checkbox" id="select-all" class="rounded border-gray-300"></th>
                    <th>Employee</th>
                    <th>Tenure</th>
                    <th class="text-right">Current Gross</th>
                    <th class="text-right">New Gross</th>
                    <th>Eligible</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $row)
                    <tr class="{{ $row['eligible'] ? '' : 'opacity-50' }}">
                        <td>
                            @if($row['eligible'])
                                <input type="checkbox" name="employee_ids[]" value="{{ $row['employee']->id }}" class="emp-check rounded border-gray-300" checked>
                            @endif
                        </td>
                        <td>
                            <span class="font-mono text-[10px] text-gray-400">{{ $row['employee']->employee_code }}</span>
                            <span class="block text-sm font-medium">{{ $row['employee']->name }}</span>
                        </td>
                        <td class="text-xs">{{ $row['tenure'] }} mo</td>
                        <td class="text-right tabular-nums text-xs">৳{{ number_format($row['gross'], 2) }}</td>
                        <td class="text-right tabular-nums text-xs text-green-700">৳{{ number_format($row['new_gross'], 2) }}</td>
                        <td class="text-xs {{ $row['eligible'] ? 'text-green-600' : 'text-gray-400' }}">{{ $row['eligible'] ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>

<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.emp-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
@elseif($employees->isEmpty())
    <div class="erp-panel"><div class="erp-panel-body text-center text-gray-400 py-8">No salaried employees on this grade.</div></div>
@endif

@if($recentLogs->isNotEmpty())
<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Recent Increments</h2></div>
    <table class="erp-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Rule</th>
                <th class="text-right">Previous</th>
                <th class="text-right">New</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentLogs as $log)
                <tr>
                    <td class="text-xs">{{ $log->applied_at?->format('M d, Y') }}</td>
                    <td class="text-xs">{{ $log->employee?->name }}</td>
                    <td class="text-xs">{{ $log->rule?->name ?? '—' }}</td>
                    <td class="text-right tabular-nums text-xs">৳{{ number_format((float) $log->previous_gross, 2) }}</td>
                    <td class="text-right tabular-nums text-xs">৳{{ number_format((float) $log->new_gross, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
