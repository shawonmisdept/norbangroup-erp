@extends('layouts.admin')

@section('title', 'Add Manual Punch')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.manual-punch.index') }}" class="hover:text-brand">Manual Punch</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Add</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'manual-punch'])

@include('partials.erp.page-header', [
    'title' => 'Add Manual Punch',
    'subtitle' => 'Record a missed check-in or check-out',
])

<div class="erp-panel max-w-lg">
    <div class="erp-panel-body">
        <form method="POST" action="{{ route('admin.hrm.attendance.manual-punch.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="erp-form-label">Employee</label>
                <select name="employee_id" required class="erp-input !text-xs">
                    <option value="">Select employee…</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->employee_code }} — {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="erp-form-label">Date</label>
                    <input type="date" name="attendance_date" value="{{ old('attendance_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="erp-input !text-xs">
                    @error('attendance_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="erp-form-label">Time</label>
                    <input type="time" name="punch_time" value="{{ old('punch_time', '08:00') }}" required class="erp-input !text-xs">
                    @error('punch_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="erp-form-label">Punch Type</label>
                <select name="punch_type" class="erp-input !text-xs">
                    <option value="in" {{ old('punch_type') === 'in' ? 'selected' : '' }}>Check In</option>
                    <option value="out" {{ old('punch_type') === 'out' ? 'selected' : '' }}>Check Out</option>
                </select>
            </div>
            <div>
                <label class="erp-form-label">Reason</label>
                <textarea name="reason" rows="3" required class="erp-input !text-xs" placeholder="e.g. Device offline, missed punch at gate…">{{ old('reason') }}</textarea>
                @error('reason')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2">
                <button type="submit" class="erp-btn-primary">Save Punch</button>
                <a href="{{ route('admin.hrm.attendance.manual-punch.index') }}" class="erp-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
