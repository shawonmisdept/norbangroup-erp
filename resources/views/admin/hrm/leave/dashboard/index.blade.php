@extends('layouts.admin')

@section('title', 'Leave Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Leave Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Leave Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.leave.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.leave.transactions.index') . '" class="erp-btn-secondary">Applications</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.leave.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pipeline</h2></div>
            <div class="erp-panel-body grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($statuses as $key => $label)
                    <div class="border border-erp-border rounded-sm p-3 text-center">
                        <p class="text-xl font-bold text-gray-800">{{ $pipeline[$key] ?? 0 }}</p>
                        <p class="text-[10px] uppercase text-gray-500 mt-1">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Applications</h2></div>
            <div class="erp-panel-body divide-y divide-erp-border">
                @forelse($recent_applications as $row)
                    <a href="{{ route('admin.hrm.leave.transactions.show', $row) }}" class="flex items-center justify-between py-2 text-sm hover:text-brand">
                        <div>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->leaveType?->name }} · {{ $row->statusLabel() }}</p>
                        </div>
                        <span class="text-xs text-gray-400">{{ $row->total_days }} day(s)</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No applications in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Approval</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_approvals as $row)
                <a href="{{ route('admin.hrm.leave.transactions.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ optional($row->start_date)->format('d M') }} – {{ optional($row->end_date)->format('d M Y') }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No pending leave applications.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
