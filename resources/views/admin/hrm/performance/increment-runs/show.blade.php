@extends('layouts.admin')

@section('title', 'Increment Run #' . $run->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.increment-runs.index') }}" class="hover:text-brand">Increment Runs</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $run->id }}</span>
@endsection

@section('admin-content')
@php
    $statusBadge = match($run->status) {
        'draft' => 'bg-gray-100 text-gray-700',
        'calculated' => 'bg-amber-100 text-amber-800',
        'applied' => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => $run->name,
    'subtitle' => $run->factory?->name . ' · ' . $run->year,
])

<div class="flex flex-wrap items-center justify-end gap-2 mb-4">
    @if($run->isApplied())
        <a href="{{ route('admin.hrm.performance.increment-runs.export', $run) }}" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>
    @endif
    <a href="{{ route('admin.hrm.performance.increment-runs.index') }}" class="erp-btn-secondary">← Back</a>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body flex flex-wrap gap-4 text-xs items-center">
        <span>Status: <span class="erp-badge {{ $statusBadge }}">{{ $run->statusLabel() }}</span></span>
        <span>Employees: <strong>{{ $run->items->count() }}</strong></span>
        <span>Total Increment: <strong>৳{{ number_format($run->items->sum('increment_amount'), 2) }}</strong></span>
        @if($run->cycle)
            <span>Cycle: <a href="{{ route('admin.hrm.performance.cycles.show', $run->cycle) }}" class="text-brand">{{ $run->cycle->name }}</a></span>
        @endif

        @if($canManage)
            <div class="ml-auto flex flex-wrap gap-2">
                @if($run->status === 'draft')
                    <form method="POST" action="{{ route('admin.hrm.performance.increment-runs.calculate', $run) }}"
                          data-confirm="Calculate increment suggestions for all eligible employees?">@csrf
                        <button type="submit" class="erp-btn-primary !text-xs">Calculate Increments</button>
                    </form>
                @elseif($run->status === 'calculated')
                    <form method="POST" action="{{ route('admin.hrm.performance.increment-runs.calculate', $run) }}" data-confirm="Recalculate all suggestions?">@csrf
                        <button type="submit" class="erp-btn-secondary !text-xs">Recalculate</button>
                    </form>
                    <form method="POST" action="{{ route('admin.hrm.performance.increment-runs.apply', $run) }}" data-confirm="Apply increments to employee salary structures? This cannot be undone.">@csrf
                        <button type="submit" class="erp-btn-primary !text-xs">Apply to Salary</button>
                    </form>
                @elseif($run->isApplied())
                    <a href="{{ route('admin.hrm.performance.increment-runs.export', $run) }}" class="erp-btn-secondary !text-xs">Export CSV</a>
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
                    <th>Prev. Gross</th>
                    <th>New Gross</th>
                    <th>Increment</th>
                    @if($canManage && $run->status === 'calculated')<th>Override</th>@endif
                    <th>Status</th>
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
                        <td>{{ $item->band_name }} ({{ number_format($item->resolvedIncrementPercent(), 1) }}%)</td>
                        <td>৳{{ number_format($item->previous_gross, 2) }}</td>
                        <td>৳{{ number_format($item->final_new_gross, 2) }}</td>
                        <td class="font-medium">৳{{ number_format($item->increment_amount, 2) }}</td>
                        @if($canManage && $run->status === 'calculated')
                            <td>
                                <form method="POST" action="{{ route('admin.hrm.performance.increment-runs.items.update', [$run, $item]) }}" class="flex gap-1 items-center">
                                    @csrf @method('PUT')
                                    <input type="number" name="override_new_gross" value="{{ $item->override_new_gross }}" placeholder="{{ number_format($item->suggested_new_gross, 0) }}" class="erp-input !text-xs w-24" min="0" step="1">
                                    <button type="submit" class="erp-btn-secondary !py-1 !px-2 text-[10px]">Set</button>
                                </form>
                            </td>
                        @endif
                        <td>
                            <span class="erp-badge text-[10px] {{ $item->status === 'applied' ? 'bg-green-100 text-green-700' : ($item->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ $item->statusLabel() }}</span>
                            @if($item->error_message)<p class="text-[10px] text-red-600 mt-1">{{ $item->error_message }}</p>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage && $run->status === 'calculated' ? 8 : 7 }}" class="text-center py-8 text-gray-400">Calculate to generate increment suggestions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
