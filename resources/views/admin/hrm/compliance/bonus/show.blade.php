@extends('layouts.admin')
@section('title', 'Bonus Run')
@section('admin-content')
@php
    $headerActions = '<a href="' . route('admin.hrm.compliance.bonus.index') . '" class="erp-btn-secondary ml-2">← Back</a>';
    if ($run->status === 'approved') {
        $headerActions = '<a href="' . route('admin.hrm.compliance.bonus.export', $run) . '" class="erp-btn-secondary !text-xs">Export CSV</a>' . $headerActions;
    }
@endphp
@include('partials.erp.page-header', ['title' => $run->bonusTypeLabel().' — '.$run->year, 'actions' => $headerActions])
<div class="erp-panel mb-4"><div class="erp-panel-body flex flex-wrap gap-4 text-xs items-center">
    <span>Factory: <strong>{{ $run->factory?->name }}</strong></span>
    @php
        $statusBadge = match($run->status) {
            'draft' => 'bg-gray-100 text-gray-700',
            'calculated' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-600',
        };
    @endphp
    <span>Status: <span class="erp-badge {{ $statusBadge }}">{{ ucfirst($run->status) }}</span></span>
    <span>Employees: <strong>{{ $run->items->count() }}</strong></span>
    <span>Total Bonus: <strong>৳{{ number_format($run->items->sum('bonus_amount'), 2) }}</strong></span>
    @if($canManage)
        <div class="ml-auto flex flex-wrap gap-2">
            @if($run->status === 'draft')
                <form method="POST" action="{{ route('admin.hrm.compliance.bonus.calculate', $run) }}"
                      data-confirm="Calculate festival bonus for all eligible employees?">@csrf
                    <button type="submit" class="erp-btn-primary !text-xs">Calculate Bonus</button>
                </form>
            @elseif($run->status === 'calculated')
                <form method="POST" action="{{ route('admin.hrm.compliance.bonus.calculate', $run) }}" data-confirm="Recalculate bonus for all eligible employees?">@csrf
                    <button type="submit" class="erp-btn-secondary !text-xs">Recalculate</button>
                </form>
                <form method="POST" action="{{ route('admin.hrm.compliance.bonus.approve', $run) }}" data-confirm="Approve this bonus run for export and payroll?">@csrf
                    <button type="submit" class="erp-btn-primary !text-xs">Approve</button>
                </form>
            @elseif($run->status === 'approved')
                <a href="{{ route('admin.hrm.compliance.bonus.export', $run) }}" class="erp-btn-sm-secondary">Export CSV</a>
            @endif
        </div>
    @endif
</div></div>
<div class="erp-panel"><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
<thead><tr><th>Code</th><th>Name</th><th>Basic Avg</th><th>Months</th><th>Bonus</th></tr></thead>
<tbody>@forelse($run->items as $item)
<tr><td>{{ $item->employee?->employee_code }}</td><td>{{ $item->employee?->name }}</td><td>৳{{ number_format($item->basic_avg,2) }}</td><td>{{ $item->months_worked }}</td><td>৳{{ number_format($item->bonus_amount,2) }}</td></tr>
@empty<tr><td colspan="5" class="text-center py-8 text-gray-400">Run calculate to generate bonus items.</td></tr>@endforelse</tbody></table></div></div>
@endsection
