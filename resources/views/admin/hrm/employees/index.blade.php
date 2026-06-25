@extends('layouts.admin')

@section('title', 'Employees — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Employees</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Employee Registry',
    'subtitle' => 'Enrolled workers across all factory units',
    'actions' => auth()->user()->hasPermission('hrm.employees.manage')
        ? '<a href="' . route('admin.hrm.employees.create') . '" class="erp-btn-primary">+ Enroll Employee</a>'
        : '',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" action="{{ route('admin.hrm.employees.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Employee ID, name, NID, phone…"
                       class="erp-input !text-xs">
            </div>
            @if(count($factories) > 1)
                <div class="w-48">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-40">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary">Filter</button>
            @if(array_filter($filters ?? []))
                <a href="{{ route('admin.hrm.employees.index') }}" class="erp-btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employees</h2>
        <span class="text-[11px] text-gray-400">{{ $employees->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-12 text-center">SN</th>
                    <th class="w-14">Photo</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th class="hidden md:table-cell">Department</th>
                    <th class="hidden lg:table-cell">Designation</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                    <tr>
                        <td class="text-center text-xs text-gray-400 tabular-nums">
                            {{ ($employees->firstItem() ?? 0) + $loop->index }}
                        </td>
                        <td>
                            @if($employee->photoUrl())
                                <img src="{{ $employee->photoUrl() }}" alt="{{ $employee->name }}" class="erp-employee-index-photo">
                            @else
                                <div class="erp-employee-index-photo-fallback">{{ $employee->initials() }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded-sm font-mono">{{ $employee->employee_code }}</code>
                        </td>
                        <td class="font-medium text-gray-900">{{ $employee->name }}</td>
                        <td class="hidden md:table-cell text-xs text-gray-600">{{ $employee->department?->name ?? '—' }}</td>
                        <td class="hidden lg:table-cell text-xs text-gray-600">{{ $employee->designation?->name ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match($employee->status) {
                                    'active' => 'bg-green-100 text-green-800',
                                    'probation' => 'bg-amber-100 text-amber-800',
                                    'suspended' => 'bg-orange-100 text-orange-800',
                                    default => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <span class="erp-badge {{ $badge }}">{{ $employee->statusLabel() }}</span>
                        </td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.hrm.employees.show', $employee) }}" class="erp-btn-sm-secondary">View</a>
                                @if(auth()->user()->hasPermission('hrm.employees.manage'))
                                    <a href="{{ route('admin.hrm.employees.edit', $employee) }}" class="erp-btn-sm-primary">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-10 text-gray-400">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $employees->links() }}</div>
    @endif
</div>
@endsection
