@extends('layouts.admin')
@section('title', 'Loan #' . $loan->id)
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $loan->loanTypeLabel() . ' — ' . $loan->employee?->name,
    'actions' => '<a href="' . route('admin.hrm.finance.loans.statement', $loan) . '?download=1" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">Loan Statement PDF</a>'
        . '<a href="' . route('admin.hrm.finance.loans.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel mb-4"><div class="erp-panel-body flex flex-wrap gap-4 text-xs items-center">
    <span>Principal: <strong>৳{{ number_format($loan->principal,2) }}</strong></span>
    <span>Balance: <strong>৳{{ number_format($loan->balance,2) }}</strong></span>
    <span>EMI: <strong>৳{{ number_format($loan->emi_amount,2) }}</strong></span>
    <span>Status: <strong>{{ ucfirst($loan->status) }}</strong></span>
    @if($canManage && $loan->status === 'pending')
    <div class="ml-auto flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.hrm.finance.loans.approve', $loan) }}"
              data-confirm="Approve this loan and create repayment schedule?"
              data-confirm-variant="primary"
              data-confirm-ok="Yes, approve">@csrf
            <button type="submit" class="erp-btn-primary !text-xs">Approve & Create Schedule</button>
        </form>
        <form method="POST" action="{{ route('admin.hrm.finance.loans.reject', $loan) }}" data-confirm="Reject this loan application?">@csrf
            <input type="hidden" name="reject_reason" id="reject-reason">
            <button type="submit" class="erp-btn-secondary !text-xs text-red-700"
                    onclick="document.getElementById('reject-reason').value = prompt('Rejection reason (optional):') || '';">
                Reject
            </button>
        </form>
    </div>
    @endif
</div></div>

@if($canManage && $loan->status === 'active' && (float) $loan->balance > 0)
<div class="erp-panel mb-4">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Early Settlement</h2></div>
    <form method="POST" action="{{ route('admin.hrm.finance.loans.settle', $loan) }}" class="erp-panel-body grid grid-cols-1 md:grid-cols-3 gap-4 items-start" data-confirm="Record early settlement for this loan?">
        @csrf
        <div>
            <label class="erp-form-label">Settlement amount (৳)</label>
            <input type="number" step="0.01" name="settlement_amount" value="{{ number_format((float) $loan->balance, 2, '.', '') }}"
                   max="{{ $loan->balance }}" min="0.01" class="erp-input">
            <p class="text-[10px] text-gray-400 mt-1">Leave as full balance for complete closure.</p>
        </div>
        <div class="md:col-span-2">
            <label class="erp-form-label">Notes</label>
            <input type="text" name="notes" class="erp-input" maxlength="500" placeholder="Reason for early settlement">
        </div>
        <button type="submit" class="erp-btn-primary !text-xs md:col-span-3 md:w-auto">Settle Loan</button>
    </form>
</div>
@endif

@if($loan->notes)
<div class="erp-panel mb-4"><div class="erp-panel-body text-xs text-gray-600 whitespace-pre-line">{{ $loan->notes }}</div></div>
@endif

<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>#</th><th>Due</th><th>Amount</th><th>Status</th></tr></thead>
<tbody>@forelse($loan->installments as $inst)
<tr><td>{{ $inst->installment_no }}</td><td>{{ $inst->due_date->format('d M Y') }}</td>
<td>৳{{ number_format($inst->amount,2) }}</td><td>{{ ucfirst($inst->status) }}</td></tr>
@empty<tr><td colspan="4" class="text-center py-8 text-gray-400">{{ $loan->status === 'pending' ? 'Approve loan to generate EMI schedule.' : 'No installments.' }}</td></tr>@endforelse</tbody></table></div></div>
@endsection
