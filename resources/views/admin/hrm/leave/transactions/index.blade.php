@extends('layouts.admin')

@section('title', 'Leave Transaction — ' . config('app.name'))

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.leave.hub') }}" class="hover:text-brand">Leave</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Leave Transaction</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Leave Transaction',
    'subtitle' => 'Applications, approvals, and leave movements',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'transactions'])

<div class="grid grid-cols-2 gap-3 mb-4 max-w-md">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $stats['pending_hr'] }}</p><p class="text-xs text-gray-500 uppercase">Awaiting HR</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-orange-600">{{ $stats['pending_reporting'] }}</p><p class="text-xs text-gray-500 uppercase">Awaiting Reporting</p></div></div>
</div>

@if(($stats['pending_my_team'] ?? 0) > 0)
    <div class="mb-4 bg-sky-50 border border-sky-200 rounded-sm p-3 text-xs text-sky-900 flex flex-wrap items-center justify-between gap-2">
        <span><strong>{{ $stats['pending_my_team'] }}</strong> leave {{ $stats['pending_my_team'] === 1 ? 'application' : 'applications' }} awaiting your approval as reporting person.</span>
        <a href="{{ route('admin.hrm.leave.transactions.index', ['status' => 'pending', 'approval_step' => \App\Services\Hrm\LeaveService::STEP_REPORTING]) }}" class="erp-btn-secondary !py-1.5 !px-3 text-xs">Review pending</a>
    </div>
@endif

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.leave.transactions.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee ID, name…" class="erp-input !text-xs">
            </div>
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
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="erp-form-label">Leave Type</label>
                <select name="leave_type_id" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($leaveTypes as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($filters['leave_type_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-44">
                <label class="erp-form-label">Approval Step</label>
                <select name="approval_step" class="erp-input !text-xs">
                    <option value="">All steps</option>
                    @foreach($approvalSteps as $step => $label)
                        <option value="{{ $step }}" {{ (string) ($filters['approval_step'] ?? '') === (string) $step ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Leave Type</th>
                <th>Dates</th>
                <th>Days</th>
                <th>Status</th>
                <th>Step</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $application)
                @php $badge = match($application->status) {
                    'pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800', default => 'bg-gray-100 text-gray-600',
                }; @endphp
                <tr>
                    <td><p class="font-medium text-sm">{{ $application->employee->name }}</p><code class="text-[10px] text-gray-400">{{ $application->employee->employee_code }}</code></td>
                    <td class="text-sm">{{ $application->leaveType->name }}</td>
                    <td class="text-sm tabular-nums">{{ $application->dateRangeLabel() }}</td>
                    <td class="text-sm tabular-nums">{{ number_format($application->total_days, 1) }}</td>
                    <td><span class="erp-badge {{ $badge }}">{{ $application->statusLabel() }}</span></td>
                    <td class="text-xs text-gray-500">{{ $application->pendingStepLabel() ?? '—' }}</td>
                    <td class="text-right">@include('partials.erp.table-actions', ['viewUrl' => route('admin.hrm.leave.transactions.show', $application)])</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-8 text-gray-400 text-sm">No leave transactions found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($applications->hasPages())<div class="px-4 py-3 border-t border-erp-border">{{ $applications->links() }}</div>@endif
</div>
@endsection
