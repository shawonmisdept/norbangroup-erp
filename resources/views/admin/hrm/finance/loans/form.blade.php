@extends('layouts.admin')
@section('title', 'New Loan')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New Loan Application', 'actions' => '<a href="' . route('admin.hrm.finance.loans.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ route('admin.hrm.finance.loans.store') }}" class="erp-panel-body space-y-4"
          x-data="loanForm({{ old('principal', 0) }}, {{ old('total_installments', $loan->total_installments) }})">
        @csrf
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input" required><option value="">Select</option>@foreach($employees as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Type</label><select name="loan_type" class="erp-input">@foreach($types as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="erp-form-label">Principal (৳)</label>
                <input type="number" step="0.01" name="principal" class="erp-input" required min="1"
                       x-model.number="principal" @input="syncEmi">
            </div>
            <div>
                <label class="erp-form-label">EMI (৳) <span class="text-gray-400 font-normal">auto</span></label>
                <input type="number" step="0.01" name="emi_amount" class="erp-input bg-gray-50" readonly
                       x-model="emi" tabindex="-1">
            </div>
        </div>
        <div>
            <label class="erp-form-label">Installments</label>
            <input type="number" name="total_installments" value="{{ old('total_installments', $loan->total_installments) }}"
                   class="erp-input" min="1" max="60" required x-model.number="installments" @input="syncEmi">
        </div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea></div>
        <button type="submit" class="erp-btn-primary">Submit Application</button>
    </form>
</div>

<script>
function loanForm(principal, installments) {
    return {
        principal: principal || 0,
        installments: installments || 1,
        emi: '0.00',
        init() { this.syncEmi(); },
        syncEmi() {
            const p = parseFloat(this.principal) || 0;
            const n = parseInt(this.installments, 10) || 1;
            this.emi = n > 0 ? (Math.round((p / n) * 100) / 100).toFixed(2) : '0.00';
        }
    };
}
</script>
@endsection
