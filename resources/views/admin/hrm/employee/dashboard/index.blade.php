@extends('layouts.admin')

@section('title', 'Employee Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Employee Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Employee Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.employee.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.employees.index') . '" class="erp-btn-secondary">Employees</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.employee.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis, 'columns' => 'grid-cols-2 md:grid-cols-3 xl:grid-cols-6'])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Joinings</h2></div>
        <div class="erp-panel-body divide-y divide-erp-border">
            @forelse($recent_joinings as $row)
                <a href="{{ route('admin.hrm.employees.show', $row) }}" class="flex items-center justify-between py-2 text-sm hover:text-brand">
                    <div>
                        <p class="font-medium">{{ $row->name }}</p>
                        <p class="text-xs text-gray-500">{{ $row->employee_code }} · {{ $row->department?->name ?? '—' }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ optional($row->joining_date)->format('d M Y') }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-400">No joinings in this period.</p>
            @endforelse
        </div>
    </div>
    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Separations</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($pending_separations as $row)
                    <a href="{{ route('admin.hrm.separations.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $row->employee?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $row->separation_type }} · {{ $row->statusLabel() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No pending exit requests.</p>
                @endforelse
            </div>
        </div>
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Promotions</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($pending_promotions as $row)
                    <a href="{{ route('admin.hrm.promotions.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $row->employee?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $row->movement_type }} · {{ $row->statusLabel() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No pending promotion requests.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
