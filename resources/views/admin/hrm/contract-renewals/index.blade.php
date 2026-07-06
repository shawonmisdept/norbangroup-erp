@extends('layouts.admin')

@section('title', 'Contract Renewals')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.employee.hub') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Contract Renewals</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Contract Renewals',
    'subtitle' => 'Extend contract end dates for contract workers',
])

<form method="GET" class="erp-panel mb-4">
    <div class="erp-panel-body grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Employee code or name" class="erp-input">
        <select name="status" class="erp-input">
            <option value="">All statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="erp-btn-secondary">Filter</button>
    </div>
</form>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Previous end</th>
                    <th>New end</th>
                    <th>Status</th>
                    <th>Notes</th>
                    @if($canManage)<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($renewals as $renewal)
                    <tr>
                        <td>
                            <a href="{{ route('admin.hrm.employees.show', $renewal->employee) }}" class="text-brand hover:underline">
                                {{ $renewal->employee->employee_code }} — {{ $renewal->employee->name }}
                            </a>
                        </td>
                        <td>{{ $renewal->previous_end_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $renewal->new_end_date->format('d M Y') }}</td>
                        <td>{{ $renewal->statusLabel() }}</td>
                        <td class="max-w-xs truncate">{{ $renewal->notes ?? '—' }}</td>
                        @if($canManage)
                            <td class="text-right whitespace-nowrap">
                                @if($renewal->isPending())
                                    <form method="POST" action="{{ route('admin.hrm.contract-renewals.approve', $renewal) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="erp-btn-sm">Approve</button>
                                    </form>
                                    <details class="inline-block ml-1">
                                        <summary class="erp-btn-sm-secondary cursor-pointer">Reject</summary>
                                        <form method="POST" action="{{ route('admin.hrm.contract-renewals.reject', $renewal) }}" class="mt-2 p-2 border rounded bg-white">
                                            @csrf
                                            <textarea name="rejection_reason" required class="erp-input text-xs" rows="2" placeholder="Reason"></textarea>
                                            <button type="submit" class="erp-btn-sm-secondary mt-1 w-full">Confirm reject</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="text-center text-gray-500 py-8">No contract renewals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($renewals->hasPages())
        <div class="erp-panel-foot">{{ $renewals->links() }}</div>
    @endif
</div>
@endsection
