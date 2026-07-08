@extends('layouts.admin')

@section('title', 'Bonus Run #' . $run->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.bonus-runs.index') }}" class="hover:text-brand">Bonus Runs</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $run->id }}</span>
@endsection

@section('admin-content')
@php
    $statusBadge = match($run->status) {
        'draft' => 'bg-gray-100 text-gray-700',
        'calculated' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => $run->name,
    'subtitle' => $run->factory?->name . ' · ' . $run->year,
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    @if($run->isApproved())
        <a href="{{ route('admin.hrm.performance.bonus-runs.export', $run) }}" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>
    @endif
    <a href="{{ route('admin.hrm.performance.bonus-runs.index') }}" class="erp-btn-secondary">← Back</a>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body flex flex-wrap gap-4 text-xs items-center">
        <span>Base: <strong>{{ $run->bonusBaseLabel() }}</strong></span>
        <span>Status: <span class="erp-badge {{ $statusBadge }}">{{ $run->statusLabel() }}</span></span>
        <span>Employees: <strong>{{ $run->items->count() }}</strong></span>
        <span>Total Bonus: <strong>৳{{ number_format($run->items->sum('final_amount'), 2) }}</strong></span>
        @if($run->cycle)
            <span>Cycle: <a href="{{ route('admin.hrm.performance.cycles.show', $run->cycle) }}" class="text-brand">{{ $run->cycle->name }}</a></span>
        @endif

        @if($canManage)
            <div class="ml-auto flex flex-wrap gap-2">
                @if($run->status === 'draft')
                    <form method="POST" action="{{ route('admin.hrm.performance.bonus-runs.calculate', $run) }}"
                          data-confirm="Calculate bonus for all eligible employees?">@csrf
                        <button type="submit" class="erp-btn-primary !text-xs">Calculate Bonus</button>
                    </form>
                @elseif($run->status === 'calculated')
                    <form method="POST" action="{{ route('admin.hrm.performance.bonus-runs.calculate', $run) }}" data-confirm="Recalculate all items?">@csrf
                        <button type="submit" class="erp-btn-secondary !text-xs">Recalculate</button>
                    </form>
                    <form method="POST" action="{{ route('admin.hrm.performance.bonus-runs.approve', $run) }}" data-confirm="Approve for payroll export?">@csrf
                        <button type="submit" class="erp-btn-primary !text-xs">Approve</button>
                    </form>
                @elseif($run->isApproved())
                    <a href="{{ route('admin.hrm.performance.bonus-runs.export', $run) }}" class="erp-btn-secondary !text-xs">Export CSV</a>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table text-sm">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Score</th>
                    <th>Band</th>
                    <th>Base</th>
                    <th>Calculated</th>
                    @if($canManage && $run->status === 'calculated')<th>Override</th>@endif
                    <th>Final</th>
                </tr>
            </thead>
            <tbody>
                @forelse($run->items as $item)
                    <tr>
                        <td>
                            <p class="font-medium">{{ $item->employee?->name }}</p>
                            <code class="text-[10px] text-gray-400">{{ $item->employee?->employee_code }}</code>
                        </td>
                        <td>{{ number_format($item->overall_score, 1) }}%</td>
                        <td>{{ $item->band_name }}</td>
                        <td>৳{{ number_format($item->base_amount, 2) }}</td>
                        <td>৳{{ number_format($item->bonus_amount, 2) }}</td>
                        @if($canManage && $run->status === 'calculated')
                            <td>
                                <form method="POST" action="{{ route('admin.hrm.performance.bonus-runs.items.update', [$run, $item]) }}" class="flex gap-1 items-center">
                                    @csrf @method('PUT')
                                    <input type="number" name="override_amount" value="{{ $item->override_amount }}" placeholder="{{ number_format($item->bonus_amount, 2) }}" class="erp-input !text-xs w-24" min="0" step="0.01">
                                    <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px]">Set</button>
                                </form>
                            </td>
                        @endif
                        <td class="font-medium">৳{{ number_format($item->final_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage && $run->status === 'calculated' ? 7 : 6 }}" class="text-center py-8 text-gray-400">Run calculate to generate bonus from approved mid-year reviews.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
