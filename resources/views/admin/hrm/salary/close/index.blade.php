@extends('layouts.admin')
@section('title', 'Salary Close')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>'Salary Close','subtitle'=>'Mark cash disbursed, freeze periods, export bank/cash files'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'salary','current'=>'close'])
<div class="erp-panel overflow-hidden"><table class="erp-table"><thead><tr><th>Period</th><th>Factory</th><th>Status</th><th>Employees</th><th>Cash Pending</th><th>Payslips</th><th class="text-right">Actions</th></tr></thead><tbody>
@forelse($periods as $period)
@php $pendingCash = $period->pendingCashDisbursementCount(); @endphp
<tr>
    <td class="font-medium">{{ $period->periodLabel() }}</td>
    <td class="text-xs">{{ $period->factory?->name }}</td>
    <td><span class="erp-badge {{ $period->status==='frozen'?'bg-green-100 text-green-800':'bg-blue-100 text-blue-800' }}">{{ $period->statusLabel() }}</span></td>
    <td>{{ $period->items_count }}</td>
    <td>
        @if($period->status === 'calculated' && $pendingCash > 0)
            <span class="erp-badge bg-amber-100 text-amber-800">{{ $pendingCash }}</span>
        @elseif($period->status === 'calculated')
            <span class="text-xs text-emerald-600">Ready</span>
        @else
            <span class="text-xs text-gray-400">—</span>
        @endif
    </td>
    <td class="text-xs text-gray-500">{{ $period->payslips_sent_at ? $period->payslips_sent_at->format('M d, Y') : '—' }}</td>
    <td class="text-right">
        <div class="inline-flex flex-wrap gap-2 justify-end">
            @if(in_array($period->status, ['calculated', 'frozen'], true))
            <a href="{{ route('admin.hrm.salary.disbursement.show', $period) }}" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Disbursement</a>
            @endif
            @if($period->isFrozen())
            <a href="{{ route('admin.hrm.salary.bank-ledger.index', ['payroll_period_id' => $period->id, 'factory_id' => $period->factory_id]) }}" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Bank Ledger</a>
            @endif
            @if($period->status==='calculated' && auth()->user()->hasPermission('hrm.salary.approve') && $pendingCash === 0)
            <form method="POST" action="{{ route('admin.hrm.salary.close.freeze',$period) }}" class="inline"
                  data-confirm="Close {{ $period->periodLabel() }} and email payslips?"
                  data-confirm-variant="warning"
                  data-confirm-ok="Yes, close">
                @csrf
                <input type="hidden" name="send_payslips" value="1">
                <button type="submit" class="erp-btn-primary !py-1 !px-2 text-[11px]">Close</button>
            </form>
            @endif
            @if($period->isFrozen() && auth()->user()->hasPermission('hrm.salary.approve'))
            <a href="{{ route('admin.hrm.salary.close.bank-advise',$period) }}" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Bank CSV</a>
            <a href="{{ route('admin.hrm.salary.close.cash-list',$period) }}" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Cash CSV</a>
            <form method="POST" action="{{ route('admin.hrm.salary.close.send-payslips',$period) }}" class="inline"
                  data-confirm="Email payslips to all employees for {{ $period->periodLabel() }}?"
                  data-confirm-variant="warning"
                  data-confirm-ok="Yes, send">
                @csrf
                <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Email Payslips</button>
            </form>
            @endif
            <a href="{{ route('admin.hrm.salary.process.show',$period) }}" class="erp-btn-sm-secondary">View</a>
        </div>
    </td>
</tr>
@empty<tr><td colspan="7" class="text-center py-8 text-gray-400">No periods ready to close.</td></tr>@endforelse</tbody></table></div>
@if($periods->hasPages())<div class="mt-3">{{ $periods->links() }}</div>@endif
@endsection
