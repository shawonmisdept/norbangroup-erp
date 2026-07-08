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
        <div class="erp-panel-head flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase">Pending Gate Pass</h2>
            <a href="{{ route('admin.hrm.rmg.gate-pass.index', ['status' => 'pending']) }}" class="text-[10px] text-brand hover:underline">View all</a>
        </div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_gate_passes as $row)
                <div class="block border border-erp-border rounded-sm p-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->destination ?? '—' }} · {{ $row->statusLabel() }}</p>
                        </div>
                        @if(auth()->user()->canManageRmgSubmodule('gate-pass'))
                            <div class="flex flex-wrap gap-1 shrink-0">
                                <form method="POST" action="{{ route('admin.hrm.rmg.gate-pass.approve', $row) }}" class="inline"
                                      data-confirm="Approve gate pass for {{ $row->employee?->name }}?"
                                      data-confirm-variant="primary"
                                      data-confirm-ok="Yes, approve">@csrf
                                    <button type="submit" class="erp-btn-primary !py-0.5 !px-2 text-[10px]">Approve</button>
                                </form>
                                <a href="{{ route('admin.hrm.rmg.gate-pass.edit', $row) }}" class="erp-btn-secondary !py-0.5 !px-2 text-[10px]">Edit</a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No pending gate passes.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase">Pending Transfers</h2>
            <a href="{{ route('admin.hrm.rmg.worker-transfer.index', ['status' => 'pending']) }}" class="text-[10px] text-brand hover:underline">View all</a>
        </div>
        <div class="erp-panel-body space-y-2">
            @forelse($pending_transfers as $row)
                <div class="block border border-erp-border rounded-sm p-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $row->employee?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->toLine?->name ?? '—' }} · {{ $row->statusLabel() }}</p>
                        </div>
                        @if(auth()->user()->canManageRmgSubmodule('worker-transfer'))
                            <div class="flex flex-wrap gap-1 shrink-0">
                                <form method="POST" action="{{ route('admin.hrm.rmg.worker-transfer.approve', $row) }}" class="inline"
                                      data-confirm="Approve transfer for {{ $row->employee?->name }}?"
                                      data-confirm-variant="primary"
                                      data-confirm-ok="Yes, approve">@csrf
                                    <button type="submit" class="erp-btn-primary !py-0.5 !px-2 text-[10px]">Approve</button>
                                </form>
                                <a href="{{ route('admin.hrm.rmg.worker-transfer.edit', $row) }}" class="erp-btn-secondary !py-0.5 !px-2 text-[10px]">Edit</a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No pending worker transfers.</p>
            @endforelse
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head flex items-center justify-between">
            <h2 class="text-xs font-semibold uppercase">Open Proxy Flags</h2>
            <a href="{{ route('admin.hrm.rmg.proxy-punch.index', ['status' => 'open']) }}" class="text-[10px] text-brand hover:underline">View all</a>
        </div>
        <div class="erp-panel-body space-y-2">
            @forelse($open_proxy_flags as $row)
                <a href="{{ route('admin.hrm.rmg.proxy-punch.index', ['status' => 'open']) }}" class="block border border-erp-border rounded-sm p-2 text-sm hover:border-brand/30 transition-colors">
                    <p class="font-medium">{{ $row->employee?->name }}</p>
                    <p class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($row->reason ?? '—', 40) }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No open proxy punch flags.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
