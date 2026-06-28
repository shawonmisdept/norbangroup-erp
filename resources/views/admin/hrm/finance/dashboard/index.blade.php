@extends('layouts.admin')

@section('title', 'Finance Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Finance Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Finance Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.finance.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.finance.loans.index') . '" class="erp-btn-secondary">Loans</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.finance.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis, 'columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6'])

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Loan Applications</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($recent_loans as $row)
                <a href="{{ route('admin.hrm.finance.loans.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">৳{{ number_format($row->principal, 0) }} · {{ \App\Models\Hrm\LoanAccount::STATUSES[$row->status] ?? ucfirst($row->status) }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No loan activity in this period.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Final Settlement</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_settlements as $row)
                <a href="{{ route('admin.hrm.finance.final-settlement.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ $row->statusLabel() ?? ucfirst($row->status) }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No pending F&F cases.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
