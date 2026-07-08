@extends('layouts.admin')

@section('title', ($structure->exists ? 'Edit' : 'Add') . ' Salary Structure — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.salary.employee-salary.index') }}" class="hover:text-brand">Salary Structures</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $structure->exists ? 'Edit' : 'Add' }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($structure->exists ? 'Edit' : 'Add') . ' Salary Structure',
    'subtitle' => 'Configure pay type, amounts, and payment details',
    'actions' => '<a href="' . route('admin.hrm.salary.employee-salary.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel max-w-3xl">
    <div class="erp-panel-body">
        <form method="POST"
              action="{{ $structure->exists ? route('admin.hrm.salary.employee-salary.update', $structure) : route('admin.hrm.salary.employee-salary.store') }}"
              class="space-y-4">
            @csrf
            @if($structure->exists)
                @method('PUT')
            @endif

            <div>
                <label class="erp-form-label">Employee <span class="text-red-500">*</span></label>
                @if($structure->exists)
                    <input type="hidden" name="employee_id" value="{{ $structure->employee_id }}">
                    <p class="text-sm font-medium">{{ $structure->employee->name }} ({{ $structure->employee->employee_code }})</p>
                @else
                    <select name="employee_id" required class="erp-input !text-xs">
                        <option value="">Select employee…</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) old('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Pay Type <span class="text-red-500">*</span></label>
                    <select name="pay_type" required class="erp-input !text-xs">
                        @foreach($payTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('pay_type', $structure->pay_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Payment Method <span class="text-red-500">*</span></label>
                    <select name="payment_method" id="payment_method" required class="erp-input !text-xs">
                        @foreach($methods as $value => $label)
                            <option value="{{ $value }}" {{ old('payment_method', $structure->payment_method) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Monthly Basic Salary</label>
                    <input type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $structure->basic_salary) }}" class="erp-input !text-xs">
                    <p class="text-[11px] text-gray-400 mt-1">For salaried staff</p>
                </div>
                <div>
                    <label class="erp-form-label">Daily Wage</label>
                    <input type="number" step="0.01" min="0" name="daily_wage" value="{{ old('daily_wage', $structure->daily_wage) }}" class="erp-input !text-xs">
                    <p class="text-[11px] text-gray-400 mt-1">For wage workers (26-day month)</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="erp-form-label">HRA</label>
                    <input type="number" step="0.01" min="0" name="hra" value="{{ old('hra', $structure->hra) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Medical</label>
                    <input type="number" step="0.01" min="0" name="medical" value="{{ old('medical', $structure->medical) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Conveyance</label>
                    <input type="number" step="0.01" min="0" name="conveyance" value="{{ old('conveyance', $structure->conveyance) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Other</label>
                    <input type="number" step="0.01" min="0" name="other_allowance" value="{{ old('other_allowance', $structure->other_allowance) }}" class="erp-input !text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Bank Account</label>
                    <input type="text" name="bank_account" id="bank_account" maxlength="40" value="{{ old('bank_account', $structure->bank_account) }}" class="erp-input !text-xs">
                </div>
                <div id="bank-disbursement-wrap" class="hidden">
                    <label class="erp-form-label">Fixed Bank Amount (monthly)</label>
                    <input type="number" step="0.01" min="0" name="bank_disbursement_amount" id="bank_disbursement_amount"
                           value="{{ old('bank_disbursement_amount', $structure->bank_disbursement_amount) }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Effective From</label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', $structure->effective_from?->format('Y-m-d')) }}" class="erp-input !text-xs">
                </div>
            </div>

            <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $structure->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-brand">
                Active
            </label>

            <div class="pt-2">
                <button type="submit" class="erp-btn-primary">{{ $structure->exists ? 'Save Changes' : 'Create Structure' }}</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const paymentSelect = document.getElementById('payment_method');
    const splitWrap = document.getElementById('bank-disbursement-wrap');
    function syncPaymentFields() {
        if (!paymentSelect || !splitWrap) return;
        splitWrap.classList.toggle('hidden', paymentSelect.value !== 'split');
    }
    paymentSelect?.addEventListener('change', syncPaymentFields);
    syncPaymentFields();
})();
</script>
@endpush
@endsection
