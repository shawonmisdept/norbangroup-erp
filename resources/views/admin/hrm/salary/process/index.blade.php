@extends('layouts.admin')

@section('title', 'Payroll Periods — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Periods</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Payroll Periods',
    'subtitle' => 'Draft → Calculate → Freeze cycle for payslips & bank advise',
    'actions' => '<div class="flex flex-wrap gap-2">'
        . '<a href="' . route('admin.hrm.salary.hub') . '" class="erp-btn-secondary">← Hub</a>'
        . '<a href="' . route('admin.hrm.salary.employee-salary.index') . '" class="erp-btn-secondary">Salary Structures</a>'
        . '</div>',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'process'])

@if(auth()->user()->hasPermission('hrm.salary.manage'))
<div class="erp-panel mb-4">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Calculate Payroll</h2>
    </div>
    <div class="erp-panel-body">
        <p class="text-xs text-gray-500 mb-3">Requires a <strong>frozen attendance period</strong> for the same factory and month.</p>
        <form method="POST" action="{{ route('admin.hrm.salary.process.run') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" required class="erp-input !text-xs">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ count($factories) === 1 ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Year</label>
                <input type="number" name="year" value="{{ old('year', now()->year) }}" required class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Month</label>
                <select name="month" required class="erp-input !text-xs">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int) old('month', now()->month) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <button type="submit" class="erp-btn-primary !py-2 !px-4 text-xs w-full md:w-auto">Calculate</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.salary.process.index') }}" class="flex flex-wrap items-end gap-3">
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Periods</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Factory</th>
                    <th>Status</th>
                    <th>Employees</th>
                    <th>Calculated</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                    @php
                        $badge = match($period->status) {
                            'draft' => 'bg-gray-100 text-gray-600',
                            'calculated' => 'bg-blue-100 text-blue-800',
                            'frozen' => 'bg-green-100 text-green-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $period->periodLabel() }}</td>
                        <td class="text-xs">{{ $period->factory?->name }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $period->statusLabel() }}</span></td>
                        <td class="text-sm tabular-nums">{{ $period->items_count }}</td>
                        <td class="text-xs text-gray-500">
                            {{ $period->calculated_at?->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="text-right space-x-2">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.salary.process.show', $period),
                            ])
                            @if($period->status === 'calculated')
                                <a href="{{ route('admin.hrm.salary.close.index') }}" class="text-xs text-gray-500">→ Close</a>
                            @elseif($period->isFrozen())
                                <span class="text-[11px] text-green-700">Closed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-gray-400">No payroll periods yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($periods->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $periods->links() }}</div>
    @endif
</div>
@endsection
