@extends('layouts.admin')
@section('title', 'Final Settlement')
@section('admin-content')
@php
    $clearance = array_merge(\App\Models\Hrm\FinalSettlement::defaultClearance(), $settlement->clearance ?? []);
    $statusBadge = match($settlement->status) {
        'paid' => 'bg-green-100 text-green-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'calculated' => 'bg-amber-100 text-amber-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => 'F&F — ' . $settlement->employee?->name,
    'actions' => '<a href="' . route('admin.hrm.finance.final-settlement.print', $settlement) . '?download=1" class="erp-btn-primary !py-2 !px-4 text-xs mr-2">Print F&F Sheet</a>'
        . '<a href="' . route('admin.hrm.finance.final-settlement.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel lg:col-span-2">
        <div class="erp-panel-body text-xs space-y-2">
            <div class="flex flex-wrap gap-x-6 gap-y-1">
                <span>Code: <strong>{{ $settlement->employee?->employee_code }}</strong></span>
                <span>Separation: <strong>{{ ucfirst($settlement->separation_type) }}</strong></span>
                <span>Last day: <strong>{{ $settlement->last_working_day->format('d M Y') }}</strong></span>
                <span>Status: <span class="erp-badge {{ $statusBadge }}">{{ $settlement->statusLabel() }}</span></span>
            </div>
            @if($settlement->employee?->department)
                <p class="text-gray-500">{{ $settlement->employee->department->name }} · {{ $settlement->employee->designation?->name }}</p>
            @endif
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body text-center">
            <p class="text-[10px] uppercase text-gray-400">Net Payable</p>
            <p class="text-2xl font-bold tabular-nums text-brand">৳{{ number_format($settlement->net_payable, 2) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Earnings</h2></div>
        <table class="erp-table w-full text-xs">
            <tbody>
                <tr><td>Unpaid salary (pro-rata)</td><td class="text-right tabular-nums">৳{{ number_format($settlement->unpaid_salary, 2) }}</td></tr>
                <tr><td>Leave encashment</td><td class="text-right tabular-nums">৳{{ number_format($settlement->leave_encashment, 2) }}</td></tr>
                <tr><td>Gratuity</td><td class="text-right tabular-nums">৳{{ number_format($settlement->gratuity_amount, 2) }}</td></tr>
                <tr><td>PF withdrawal</td><td class="text-right tabular-nums">৳{{ number_format($settlement->pf_withdrawal, 2) }}</td></tr>
                <tr><td>Other earnings</td><td class="text-right tabular-nums">৳{{ number_format($settlement->other_earnings, 2) }}</td></tr>
                <tr class="font-semibold bg-brand/5"><td>Total earnings</td><td class="text-right tabular-nums">৳{{ number_format($settlement->totalEarnings(), 2) }}</td></tr>
            </tbody>
        </table>
    </div>
    <div class="erp-panel overflow-hidden">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Deductions</h2></div>
        <table class="erp-table w-full text-xs">
            <tbody>
                <tr><td>Outstanding loans</td><td class="text-right tabular-nums">৳{{ number_format($settlement->loan_deduction, 2) }}</td></tr>
                <tr><td>Tax / TDS</td><td class="text-right tabular-nums">৳{{ number_format($settlement->tax_deduction, 2) }}</td></tr>
                <tr><td>Other deductions</td><td class="text-right tabular-nums">৳{{ number_format($settlement->other_deductions, 2) }}</td></tr>
                <tr class="font-semibold bg-red-50"><td>Total deductions</td><td class="text-right tabular-nums">৳{{ number_format($settlement->totalDeductions(), 2) }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

@if(!empty($settlement->breakdown['leave_details']))
<div class="erp-panel mb-4 overflow-hidden">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Leave Encashment Detail</h2></div>
    <table class="erp-table w-full text-xs">
        <thead><tr><th>Leave Type</th><th class="text-right">Days</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
        <tbody>
            @foreach($settlement->breakdown['leave_details'] as $row)
                <tr>
                    <td>{{ $row['leave_type'] }}</td>
                    <td class="text-right">{{ number_format($row['days'], 1) }}</td>
                    <td class="text-right">৳{{ number_format($row['rate'], 2) }}</td>
                    <td class="text-right tabular-nums">৳{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($canManage)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    @if(in_array($settlement->status, ['draft', 'calculated'], true))
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Actions</h2></div>
        <div class="erp-panel-body space-y-3">
            <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.calculate', $settlement) }}">
                @csrf
                <button type="submit" class="erp-btn-primary w-full !py-2 text-xs">
                    {{ $settlement->status === 'draft' ? 'Calculate F&F' : 'Recalculate' }}
                </button>
            </form>
        </div>
    </div>
    @endif

    @if(in_array($settlement->status, ['draft', 'calculated'], true))
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Manual Adjustments</h2></div>
        <div class="erp-panel-body">
            <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.adjustments', $settlement) }}" class="space-y-3">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="erp-form-label">Other earnings (৳)</label>
                        <input type="number" step="0.01" name="other_earnings" value="{{ number_format((float)$settlement->other_earnings, 2, '.', '') }}" class="erp-input !text-xs">
                    </div>
                    <div>
                        <label class="erp-form-label">Other deductions (৳)</label>
                        <input type="number" step="0.01" name="other_deductions" value="{{ number_format((float)$settlement->other_deductions, 2, '.', '') }}" class="erp-input !text-xs">
                    </div>
                </div>
                <div>
                    <label class="erp-form-label">Tax / TDS adjustment (৳)</label>
                    <input type="number" step="0.01" name="tax_deduction" value="{{ number_format((float)$settlement->tax_deduction, 2, '.', '') }}" class="erp-input !text-xs">
                </div>
                <div>
                    <label class="erp-form-label">Notes</label>
                    <textarea name="notes" rows="2" class="erp-input !text-xs">{{ $settlement->notes }}</textarea>
                </div>
                <button type="submit" class="erp-btn-secondary !py-1.5 text-xs">Save Adjustments</button>
            </form>
        </div>
    </div>
    @endif
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase text-gray-600">Exit Clearance</h2></div>
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.clearance', $settlement) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mb-3">
                @foreach(\App\Models\Hrm\FinalSettlement::CLEARANCE_KEYS as $key => $label)
                    <label class="flex items-center gap-2 text-xs cursor-pointer">
                        <input type="hidden" name="clearance[{{ $key }}]" value="0">
                        <input type="checkbox" name="clearance[{{ $key }}]" value="1" {{ !empty($clearance[$key]) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-brand focus:ring-brand/30"
                               {{ !in_array($settlement->status, ['draft', 'calculated', 'approved'], true) ? 'disabled' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @if(in_array($settlement->status, ['draft', 'calculated', 'approved'], true))
                <button type="submit" class="erp-btn-secondary !py-1.5 text-xs">Update Clearance</button>
            @endif
        </form>
    </div>
</div>

@if($settlement->status === 'calculated' && $settlement->clearanceComplete())
<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.approve', $settlement) }}" data-confirm="Approve this final settlement for disbursement?">
            @csrf
            <button type="submit" class="erp-btn-primary !py-2 text-xs">Approve for Disbursement</button>
        </form>
    </div>
</div>
@endif

@if($settlement->status === 'approved')
<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.finance.final-settlement.paid', $settlement) }}" data-confirm="Mark as paid? This will close outstanding loans and deactivate PF account.">
            @csrf
            <button type="submit" class="erp-btn-primary !py-2 text-xs">Mark Paid & Close Accounts</button>
        </form>
    </div>
</div>
@endif
@endif

@if($settlement->paid_at)
<div class="erp-panel">
    <div class="erp-panel-body text-xs text-gray-500">
        Paid on @portalDateTime($settlement->paid_at)
        @if($settlement->approver) · Approved by {{ $settlement->approver->name }} @endif
    </div>
</div>
@endif
@endsection
