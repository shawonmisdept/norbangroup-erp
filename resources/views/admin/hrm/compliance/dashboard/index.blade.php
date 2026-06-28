@extends('layouts.admin')

@section('title', 'Compliance Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Compliance Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Compliance Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.compliance.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.compliance.bonus.index') . '" class="erp-btn-secondary">Festival Bonus</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.compliance.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis])

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Bonus Runs</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($recent_bonus_runs as $row)
                <a href="{{ route('admin.hrm.compliance.bonus.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->factory?->name }} — {{ $row->year }}</p>
                    <p class="text-xs text-gray-500">{{ $row->bonus_type ?? 'Bonus' }} · {{ $row->status }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No bonus runs in this period.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Gratuity Pending Payment</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_gratuity as $row)
                <a href="{{ route('admin.hrm.compliance.gratuity.show', $row) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">৳{{ number_format($row->gratuity_amount, 0) }} · {{ \App\Models\Hrm\GratuitySettlement::STATUSES[$row->status] ?? ucfirst($row->status) }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No pending gratuity settlements.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
