@extends('layouts.admin')

@section('title', 'Salary Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Salary Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Salary Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.salary.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.salary.process.index') . '" class="erp-btn-secondary">Payroll Process</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.salary.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis])

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Open Payroll Periods</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($open_periods as $row)
                <a href="{{ route('admin.hrm.salary.process.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->factory?->name }} — {{ $row->month }}/{{ $row->year }}</p>
                    <p class="text-xs text-gray-500">{{ $row->statusLabel() }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No open payroll periods.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Periods</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($recent_periods as $row)
                <a href="{{ route('admin.hrm.salary.process.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->factory?->name }} — {{ $row->month }}/{{ $row->year }}</p>
                    <p class="text-xs text-gray-500">{{ $row->statusLabel() }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No payroll periods in this range.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
