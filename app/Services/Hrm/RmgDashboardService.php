<?php

namespace App\Services\Hrm;

use App\Models\Hrm\GatePass;
use App\Models\Hrm\ProxyPunchFlag;
use App\Models\Hrm\WorkerTransfer;
use App\Models\User;
use App\Services\Hrm\Concerns\ScopesDashboardFactory;
use Carbon\Carbon;

class RmgDashboardService
{
    use ScopesDashboardFactory;

    /** @return array<string, mixed> */
    public function build(User $user, ?int $factoryId, Carbon $from, Carbon $to): array
    {
        $gatePassBase = $this->scopeFactoryQuery(GatePass::query(), $user, $factoryId);
        $transferBase = $this->scopeFactoryQuery(WorkerTransfer::query(), $user, $factoryId);
        $proxyBase = $this->scopeFactoryQuery(ProxyPunchFlag::query(), $user, $factoryId);

        $pendingGatePasses = (clone $gatePassBase)
            ->with(['employee'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get();

        $pendingTransfers = (clone $transferBase)
            ->with(['employee'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(8)
            ->get();

        $openProxyFlags = (clone $proxyBase)
            ->with(['employee'])
            ->where('status', 'open')
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'kpis' => [
                ['label' => 'Gate Pass Pending', 'value' => (clone $gatePassBase)->where('status', 'pending')->count(), 'text' => 'text-amber-700', 'panel' => 'border-amber-200 bg-amber-50/60', 'url' => route('admin.hrm.rmg.gate-pass.index', ['status' => 'pending'])],
                ['label' => 'Transfers Pending', 'value' => (clone $transferBase)->where('status', 'pending')->count(), 'text' => 'text-blue-700', 'panel' => 'border-blue-200 bg-blue-50/60', 'url' => route('admin.hrm.rmg.worker-transfer.index', ['status' => 'pending'])],
                ['label' => 'Proxy Flags Open', 'value' => (clone $proxyBase)->where('status', 'open')->count(), 'text' => 'text-red-700', 'panel' => 'border-red-200 bg-red-50/60', 'url' => route('admin.hrm.rmg.proxy-punch.index', ['status' => 'open'])],
                ['label' => 'Gate Pass (period)', 'value' => (clone $gatePassBase)->whereBetween('created_at', [$from, $to->copy()->endOfDay()])->count(), 'text' => 'text-brand', 'panel' => 'border-brand/20 bg-brand/5'],
                ['label' => 'Transfers (period)', 'value' => (clone $transferBase)->whereBetween('created_at', [$from, $to->copy()->endOfDay()])->count(), 'text' => 'text-emerald-700', 'panel' => 'border-emerald-200 bg-emerald-50/60'],
            ],
            'pending_gate_passes' => $pendingGatePasses,
            'pending_transfers'   => $pendingTransfers,
            'open_proxy_flags'    => $openProxyFlags,
        ];
    }
}
