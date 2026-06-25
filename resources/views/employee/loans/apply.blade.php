@extends('layouts.employee')

@section('title', 'Apply for Loan')
@section('page-title', 'Apply for Loan / Advance')

@section('content')
<div class="pb-4">
    <form method="POST" action="{{ route('employee.loans.apply.store') }}" class="emp-card p-4 space-y-4"
          x-data="loanForm({{ old('principal', 0) }}, {{ old('total_installments', $loan->total_installments) }})">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select name="loan_type" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                @foreach($types as $k => $label)
                    <option value="{{ $k }}" {{ old('loan_type', $loan->loan_type) === $k ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Amount (৳)</label>
                <input type="number" step="0.01" name="principal" min="1" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm"
                       x-model.number="principal" @input="syncEmi">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">EMI (auto)</label>
                <input type="text" readonly class="w-full rounded-xl border border-gray-100 bg-gray-50 px-3 py-2.5 text-sm tabular-nums" x-model="emi">
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Installments</label>
            <input type="number" name="total_installments" min="1" max="60" required
                   value="{{ old('total_installments', $loan->total_installments) }}"
                   class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm"
                   x-model.number="installments" @input="syncEmi">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Reason / Notes</label>
            <textarea name="notes" rows="3" maxlength="1000" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm" placeholder="Optional">{{ old('notes') }}</textarea>
        </div>
        <button type="submit" class="emp-btn w-full">Submit Application</button>
        <a href="{{ route('employee.loans') }}" class="block text-center text-sm text-gray-500">Cancel</a>
    </form>
</div>

<script>
function loanForm(principal, installments) {
    return {
        principal: principal || 0,
        installments: installments || 1,
        emi: '৳0.00',
        init() { this.syncEmi(); },
        syncEmi() {
            const p = parseFloat(this.principal) || 0;
            const n = parseInt(this.installments, 10) || 1;
            const val = n > 0 ? (Math.round((p / n) * 100) / 100) : 0;
            this.emi = '৳' + val.toFixed(2);
        }
    };
}
</script>
@endsection
