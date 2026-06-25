@extends('layouts.admin')

@section('title', 'New Promotion / Demotion')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.promotions.index') }}" class="hover:text-brand">Promotions</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'New Promotion / Demotion',
    'subtitle' => 'Submit designation change with optional salary revision',
    'actions' => '<a href="' . route('admin.hrm.promotions.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel max-w-2xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.promotions.store') }}" class="space-y-4" id="promotion-form">
            @csrf
            <div>
                <label class="erp-form-label">Employee <span class="text-red-500">*</span></label>
                <select name="employee_id" id="employee_id" class="erp-input" required onchange="window.location='{{ route('admin.hrm.promotions.create') }}?employee_id='+this.value">
                    <option value="">Select employee</option>
                    @foreach($employees as $id => $label)
                        <option value="{{ $id }}" {{ (string) old('employee_id', $selectedEmployee?->id) === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            @if($selectedEmployee)
                <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm space-y-1">
                    <p class="text-xs font-semibold uppercase text-gray-500">Current Assignment</p>
                    <p><span class="text-gray-500">Designation:</span> {{ $selectedEmployee->designation?->name ?? '—' }}</p>
                    <p><span class="text-gray-500">Department:</span> {{ $selectedEmployee->department?->name ?? '—' }}</p>
                    <p><span class="text-gray-500">Category:</span> {{ $selectedEmployee->workerCategory?->name ?? '—' }}</p>
                    <p><span class="text-gray-500">Salary Grade:</span> {{ $selectedEmployee->salaryStructure?->salaryGrade?->name ?? '—' }}</p>
                    <p><span class="text-gray-500">Gross:</span> {{ $selectedEmployee->salaryStructure?->gross_salary ? number_format((float) $selectedEmployee->salaryStructure->gross_salary, 2) : '—' }}</p>
                </div>
            @endif

            <div>
                <label class="erp-form-label">Movement Type <span class="text-red-500">*</span></label>
                <select name="movement_type" class="erp-input" required>
                    @foreach($movementTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('movement_type', $promotion->movement_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label">New Designation <span class="text-red-500">*</span></label>
                <select name="to_designation_id" class="erp-input" required>
                    <option value="">Select designation</option>
                    @foreach($designations as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('to_designation_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                @error('to_designation_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">New Department</label>
                    <select name="to_department_id" class="erp-input">
                        <option value="">— No change —</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('to_department_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">New Worker Category</label>
                    <select name="to_worker_category_id" class="erp-input">
                        <option value="">— No change —</option>
                        @foreach($workerCategories as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('to_worker_category_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="erp-form-label">New Reporting To</label>
                <select name="to_reporting_to_id" class="erp-input">
                    <option value="">— No change —</option>
                    @foreach($reportingOptions as $id => $label)
                        <option value="{{ $id }}" {{ (string) old('to_reporting_to_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="apply_salary_change" value="1" id="apply_salary_change" class="rounded border-gray-300" {{ old('apply_salary_change') ? 'checked' : '' }}>
                    <span class="erp-form-label !mb-0">Revise salary on approval</span>
                </label>
                <div id="salary-fields" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 {{ old('apply_salary_change') ? '' : 'hidden' }}">
                    <div>
                        <label class="erp-form-label">New Salary Grade</label>
                        <select name="to_salary_grade_id" class="erp-input">
                            <option value="">Select grade</option>
                            @foreach($salaryGrades as $id => $label)
                                <option value="{{ $id }}" {{ (string) old('to_salary_grade_id') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('to_salary_grade_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">New Gross Salary</label>
                        <input type="number" name="to_gross_salary" value="{{ old('to_gross_salary') }}" step="0.01" min="0" class="erp-input" placeholder="0.00">
                        @error('to_gross_salary')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div>
                <label class="erp-form-label">Effective Date <span class="text-red-500">*</span></label>
                <input type="date" name="effective_date" value="{{ old('effective_date', $promotion->effective_date?->format('Y-m-d')) }}" class="erp-input" required>
            </div>
            <div>
                <label class="erp-form-label">Reason</label>
                <textarea name="reason" rows="3" class="erp-input" placeholder="Performance, vacancy, restructuring…">{{ old('reason') }}</textarea>
            </div>
            <div>
                <label class="erp-form-label">HR Remarks</label>
                <textarea name="remarks" rows="2" class="erp-input" placeholder="Internal notes…">{{ old('remarks') }}</textarea>
            </div>

            <button type="submit" class="erp-btn-primary" {{ $selectedEmployee ? '' : 'disabled' }}>Submit for Approval</button>
        </form>
    </div>
</div>

<script>
document.getElementById('apply_salary_change')?.addEventListener('change', function () {
    document.getElementById('salary-fields')?.classList.toggle('hidden', !this.checked);
});
</script>
@endsection
