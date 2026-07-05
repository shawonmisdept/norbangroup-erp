@extends('layouts.admin')

@section('title', 'Employee Salary — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Employee Salary</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Employee Salary',
    'subtitle' => 'Select grade & employee, enter gross — other heads calculate automatically',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'employee-salary'])

<div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
    {{-- Left: Grade + Employee list --}}
    <div class="xl:col-span-3 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Employees</h2></div>
        <div class="erp-panel-body space-y-3">
            <form method="GET" action="{{ route('admin.hrm.salary.employee-salary.index') }}" class="space-y-2">
                <div>
                    <label class="erp-form-label">Grade</label>
                    <select name="salary_grade_id" class="erp-input !text-xs" onchange="this.form.submit()">
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" {{ $selectedGradeId === (string) $grade->id ? 'selected' : '' }}>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Search</label>
                    <input type="search" name="search" value="{{ $filterSearch }}" placeholder="Code or name…" class="erp-input !text-xs">
                </div>
                @if($selectedEmployee)
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                @endif
                <button type="submit" class="erp-btn-primary w-full">Filter</button>
            </form>

            <div class="max-h-[420px] overflow-y-auto border border-erp-border rounded">
                @forelse($employees as $emp)
                    <a href="{{ route('admin.hrm.salary.employee-salary.index', ['salary_grade_id' => $selectedGradeId, 'employee_id' => $emp->id, 'search' => $filterSearch]) }}"
                       class="block px-3 py-2 text-xs border-b border-erp-border hover:bg-gray-50 {{ ($selectedEmployee?->id ?? 0) === $emp->id ? 'bg-brand/5 border-l-2 border-l-brand' : '' }}">
                        <span class="font-mono text-[10px] text-gray-400">{{ $emp->employee_code }}</span>
                        <span class="block font-medium text-sm">{{ $emp->name }}</span>
                        @if($emp->salaryStructure)
                            <span class="text-[10px] text-green-600">৳{{ number_format($emp->salaryStructure->monthlyGross(), 0) }}</span>
                        @endif
                    </a>
                @empty
                    <p class="p-4 text-center text-gray-400 text-xs">No employees assigned to this grade yet. Set grade on Employee edit/create first.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Middle: Entry form --}}
    <div class="xl:col-span-5 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Salary Entry</h2></div>
        <div class="erp-panel-body">
            @if(!$selectedEmployee)
                <p class="text-sm text-gray-400 py-8 text-center">Select an employee from the list.</p>
            @elseif(!$selectedGrade || $gradeDetails->isEmpty())
                <p class="text-sm text-amber-600 py-8 text-center">Configure grade details for this grade first.</p>
            @elseif($canManage)
                <form method="POST" action="{{ route('admin.hrm.salary.employee-salary.store') }}" id="salary-form" class="space-y-3">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                    <input type="hidden" name="salary_grade_id" value="{{ $selectedGrade->id }}">

                    <p class="text-sm font-medium">{{ $selectedEmployee->name }} <code class="text-xs text-gray-400">{{ $selectedEmployee->employee_code }}</code></p>

                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Salary Head</th>
                                <th class="py-2 w-28">Amount</th>
                                <th class="py-2 w-14">Fixed</th>
                            </tr>
                        </thead>
                        <tbody id="entry-rows">
                            @foreach($gradeDetails as $detail)
                                @php
                                    $code = strtoupper($detail->salaryHead?->code ?? '');
                                    $saved = $structure?->head_amounts[$code] ?? null;
                                    $isGross = $code === 'GROSS';
                                    $isFixed = $detail->detail_type === 'F' && $detail->is_fixed;
                                @endphp
                                <tr class="border-b border-erp-border/60" data-code="{{ $code }}" data-type="{{ $detail->detail_type }}" data-fixed="{{ $isFixed ? '1' : '0' }}">
                                    <td class="py-2 pr-2">
                                        <span class="font-medium">{{ $detail->salaryHead?->name }}</span>
                                        <span class="text-[10px] text-gray-400 block">{{ $detail->detail_type }}{{ $detail->detail_type === 'P' && $detail->percentageOfHead ? ' of '.$detail->percentageOfHead->name : '' }}</span>
                                    </td>
                                    <td class="py-2">
                                        @if($isGross)
                                            <input type="number" step="0.01" min="0" name="gross_salary" id="gross_salary"
                                                   value="{{ old('gross_salary', $structure?->gross_salary ?? $saved ?? 0) }}"
                                                   class="erp-input !text-xs w-full calc-trigger" required>
                                        @elseif($isFixed)
                                            <input type="number" step="0.01" min="0"
                                                   name="overrides[{{ $code }}]"
                                                   value="{{ old('overrides.'.$code, $saved ?? $detail->amount) }}"
                                                   class="erp-input !text-xs w-full calc-trigger override-input">
                                        @else
                                            <input type="text" readonly
                                                   class="erp-input !text-xs w-full bg-gray-50 calc-result"
                                                   data-code="{{ $code }}"
                                                   value="{{ $saved !== null ? number_format($saved, 2) : '—' }}">
                                        @endif
                                    </td>
                                    <td class="py-2 text-center">
                                        <input type="checkbox" disabled {{ $isFixed ? 'checked' : '' }} class="rounded border-gray-300">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="erp-form-label">Payment</label>
                            <select name="payment_method" class="erp-input !text-xs">
                                @foreach(\App\Models\Hrm\SalaryStructure::PAYMENT_METHODS as $k => $l)
                                    <option value="{{ $k }}" {{ old('payment_method', $structure?->payment_method ?? 'bank') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="erp-form-label">Bank Account</label>
                            <input type="text" name="bank_account" value="{{ old('bank_account', $structure?->bank_account) }}" class="erp-input !text-xs">
                        </div>
                    </div>

                    <button type="submit" class="erp-btn-primary">Save Salary</button>
                </form>
            @else
                <p class="text-sm text-gray-400 py-8 text-center">You do not have permission to manage salaries.</p>
            @endif
        </div>
    </div>

    {{-- Right: Preview --}}
    <div class="xl:col-span-4 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Calculated Preview</h2></div>
        <div class="erp-panel-body">
            <table class="w-full text-xs" id="preview-table">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Head</th>
                        <th class="py-2 text-right">Amount (৳)</th>
                        <th class="py-2 w-14 text-center">Fixed</th>
                    </tr>
                </thead>
                <tbody id="preview-body">
                    @if($selectedEmployee && $structure?->head_amounts)
                        @foreach($gradeDetails as $detail)
                            @php $code = strtoupper($detail->salaryHead?->code ?? ''); @endphp
                            <tr class="border-b border-erp-border/60">
                                <td class="py-2">{{ $detail->salaryHead?->name }}</td>
                                <td class="py-2 text-right tabular-nums">{{ number_format($structure->head_amounts[$code] ?? 0, 2) }}</td>
                                <td class="py-2 text-center">{{ $detail->is_fixed ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr><td colspan="3" class="py-6 text-center text-gray-400">Enter gross to preview.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($selectedEmployee && $selectedGrade && $canManage && $gradeDetails->isNotEmpty())
<script>
(function () {
    const gradeId = {{ $selectedGrade->id }};
    const calcUrl = @json(route('admin.hrm.salary.employee-salary.calculate'));
    const csrf = @json(csrf_token());
    let timer = null;

    function gatherOverrides() {
        const overrides = {};
        document.querySelectorAll('.override-input').forEach(el => {
            const m = el.name.match(/overrides\[([^\]]+)\]/);
            if (m) overrides[m[1]] = parseFloat(el.value) || 0;
        });
        return overrides;
    }

    function renderPreview(rows) {
        const body = document.getElementById('preview-body');
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="3" class="py-6 text-center text-gray-400">No data.</td></tr>';
            return;
        }
        body.innerHTML = rows.map(r => `
            <tr class="border-b border-erp-border/60">
                <td class="py-2">${r.name}</td>
                <td class="py-2 text-right tabular-nums">${Number(r.amount).toLocaleString('en-BD', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                <td class="py-2 text-center">${r.is_fixed ? 'Yes' : 'No'}</td>
            </tr>`).join('');

        document.querySelectorAll('.calc-result').forEach(el => {
            const code = el.dataset.code;
            const row = rows.find(r => r.code === code);
            if (row) el.value = Number(row.amount).toFixed(2);
        });
    }

    function calculate() {
        const gross = parseFloat(document.getElementById('gross_salary')?.value) || 0;
        if (gross <= 0) return;

        fetch(calcUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ salary_grade_id: gradeId, gross_salary: gross, overrides: gatherOverrides() }),
        })
        .then(r => r.json())
        .then(data => renderPreview(data.rows || []))
        .catch(() => {});
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(calculate, 300);
    }

    document.querySelectorAll('.calc-trigger').forEach(el => el.addEventListener('input', schedule));
    schedule();
})();
</script>
@endif
@endsection
