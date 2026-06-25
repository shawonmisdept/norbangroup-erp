@extends('layouts.admin')
@section('title', 'Salary Close')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>'Salary Close','subtitle'=>'Freeze calculated periods, email payslips, export bank advise'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'salary','current'=>'close'])
<div class="erp-panel overflow-hidden"><table class="erp-table"><thead><tr><th>Period</th><th>Factory</th><th>Status</th><th>Employees</th><th>Payslips</th><th class="text-right">Actions</th></tr></thead><tbody>
@forelse($periods as $period)
<tr>
    <td class="font-medium">{{ $period->periodLabel() }}</td>
    <td class="text-xs">{{ $period->factory?->name }}</td>
    <td><span class="erp-badge {{ $period->status==='frozen'?'bg-green-100 text-green-800':'bg-blue-100 text-blue-800' }}">{{ $period->statusLabel() }}</span></td>
    <td>{{ $period->items_count }}</td>
    <td class="text-xs text-gray-500">{{ $period->payslips_sent_at ? $period->payslips_sent_at->format('M d, Y') : '—' }}</td>
    <td class="text-right">
        <div class="inline-flex flex-wrap gap-2 justify-end">
            @if($period->status==='calculated' && auth()->user()->hasPermission('hrm.salary.approve'))
            <form method="POST" action="{{ route('admin.hrm.salary.close.freeze',$period) }}" class="inline">
                @csrf
                <input type="hidden" name="send_payslips" value="1">
                <button type="submit" class="erp-btn-primary !py-1 !px-2 text-[11px]" onclick="return confirm('Close {{ $period->periodLabel() }} and email payslips?')">Close</button>
            </form>
            @endif
            @if($period->isFrozen() && auth()->user()->hasPermission('hrm.salary.approve'))
            <a href="{{ route('admin.hrm.salary.close.bank-advise',$period) }}" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Bank CSV</a>
            <form method="POST" action="{{ route('admin.hrm.salary.close.send-payslips',$period) }}" class="inline">
                @csrf
                <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[11px]">Email Payslips</button>
            </form>
            @endif
            <a href="{{ route('admin.hrm.salary.process.show',$period) }}" class="erp-btn-sm-secondary">View</a>
        </div>
    </td>
</tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No periods ready to close.</td></tr>@endforelse</tbody></table></div>
@if($periods->hasPages())<div class="mt-3">{{ $periods->links() }}</div>@endif
@endsection
