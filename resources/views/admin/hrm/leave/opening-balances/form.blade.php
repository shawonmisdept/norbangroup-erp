@extends('layouts.admin')

@section('title', $balance->exists ? 'Edit Opening Balance' : 'Add Opening Balance')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.leave.hub') }}" class="hover:text-brand">Leave</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.leave.opening-balances.index', ['year' => $balance->year ?? now()->year]) }}" class="hover:text-brand">Balances</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $balance->exists ? 'Edit' : 'Add' }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($balance->exists ? 'Edit' : 'Add') . ' Opening Balance',
    'subtitle' => $balance->exists
        ? $balance->employee?->name . ' · ' . $balance->leaveType?->name . ' · ' . $balance->year
        : 'Set year-start or join entitlement for an employee',
    'actions' => '<a href="' . route('admin.hrm.leave.opening-balances.index', ['year' => $balance->year ?? now()->year]) . '" class="erp-btn-secondary">← Back</a>',
])

@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'opening-balances'])

<div class="erp-panel max-w-xl">
    <div class="erp-panel-body">
        <form method="POST"
              action="{{ $balance->exists ? route('admin.hrm.leave.opening-balances.update', $balance) : route('admin.hrm.leave.opening-balances.store') }}"
              class="space-y-4">
            @csrf
            @if($balance->exists)
                @method('PUT')
            @endif

            @if($balance->exists)
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="erp-form-label">Employee</p>
                        <p class="font-medium">{{ $balance->employee?->name }}</p>
                        <code class="text-[10px] text-gray-400">{{ $balance->employee?->employee_code }}</code>
                    </div>
                    <div>
                        <p class="erp-form-label">Leave Type</p>
                        <p class="font-medium">{{ $balance->leaveType?->name }}</p>
                    </div>
                    <div>
                        <p class="erp-form-label">Year</p>
                        <p class="font-medium">{{ $balance->year }}</p>
                    </div>
                    <div>
                        <p class="erp-form-label">Used / Pending</p>
                        <p class="font-medium tabular-nums">{{ number_format($balance->used_days, 1) }} / {{ number_format($balance->pending_days, 1) }}</p>
                    </div>
                </div>
            @else
                <div>
                    <label class="erp-form-label">Employee</label>
                    <select name="employee_id" required class="erp-input !text-xs">
                        <option value="">Select employee…</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) old('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_code }} — {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Leave Type</label>
                    <select name="leave_type_id" required class="erp-input !text-xs">
                        <option value="">Select leave type…</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}" {{ (string) old('leave_type_id') === (string) $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Year</label>
                    <input type="number" name="year" min="2020" max="2100" required
                           value="{{ old('year', $balance->year) }}" class="erp-input !text-xs">
                </div>
            @endif

            <div>
                <label class="erp-form-label">Entitled Days</label>
                <input type="number" name="entitled_days" step="0.5" min="0" max="365" required
                       value="{{ old('entitled_days', $balance->entitled_days) }}" class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Annual entitlement for this leave type</p>
            </div>

            <button type="submit" class="erp-btn-primary">Save Balance</button>
        </form>
    </div>
</div>
@endsection
