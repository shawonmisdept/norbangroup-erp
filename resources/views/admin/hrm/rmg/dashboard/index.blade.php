@extends('layouts.admin')

@section('title', 'RMG Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">RMG Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'RMG Extras Dashboard',
    'subtitle' => $period_label,
    'actions' => '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary">Hub</a> <a href="' . route('admin.hrm.rmg.gate-pass.index') . '" class="erp-btn-secondary">Gate Pass</a>',
])

@include('admin.hrm.partials.dashboard-filters', ['routeName' => 'admin.hrm.rmg.dashboard'])
@include('admin.hrm.partials.dashboard-kpis', ['kpis' => $kpis])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Gate Pass</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_gate_passes as $row)
                <div class="block border border-erp-border rounded-sm p-2 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ $row->destination ?? '—' }} · {{ $row->statusLabel() }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">No pending gate passes.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pending Transfers</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_transfers as $row)
                <div class="block border border-erp-border rounded-sm p-2 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ $row->statusLabel() }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">No pending worker transfers.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Open Proxy Flags</h2></div>
        <div class="erp-panel-body space-y-2">
            @forelse($open_proxy_flags as $row)
                <div class="block border border-erp-border rounded-sm p-2 text-sm">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($row->reason ?? '—', 40) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">No open proxy punch flags.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
