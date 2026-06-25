@extends('layouts.admin')

@section('title', 'New Half Day Entry')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.half-day-entry.index') }}" class="hover:text-brand">Half Day Entry</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'half-day-entry'])

@include('partials.erp.page-header', [
    'title' => 'New Half Day Entry',
    'subtitle' => 'Mark an employee as first or second half day — overrides auto detection on reprocess',
    'actions' => '<a href="' . route('admin.hrm.attendance.half-day-entry.index') . '" class="erp-btn-secondary">← Back</a>',
])

<form method="POST" action="{{ route('admin.hrm.attendance.half-day-entry.store') }}" class="erp-panel max-w-xl">
    @csrf
    <div class="erp-panel-body space-y-4">
        <div>
            <label class="erp-form-label">Employee <span class="text-red-500">*</span></label>
            <select name="employee_id" required class="erp-input !text-xs">
                <option value="">Select employee…</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ (string) old('employee_id') === (string) $emp->id ? 'selected' : '' }}>
                        {{ $emp->employee_code }} — {{ $emp->name }}
                    </option>
                @endforeach
            </select>
            @error('employee_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Date <span class="text-red-500">*</span></label>
            <input type="date" name="attendance_date" value="{{ old('attendance_date', today()->toDateString()) }}" required class="erp-input !text-xs">
            @error('attendance_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Half <span class="text-red-500">*</span></label>
            <select name="half_day_type" required class="erp-input !text-xs">
                @foreach($types as $value => $label)
                    @if($value !== 'auto')
                        <option value="{{ $value }}" {{ old('half_day_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
            @error('half_day_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Pay ratio (optional)</label>
            <input type="number" step="0.01" min="0.01" max="1" name="half_day_pay_ratio"
                value="{{ old('half_day_pay_ratio') }}" placeholder="Default from employee / policy"
                class="erp-input !text-xs">
            <p class="text-[10px] text-gray-400 mt-1">0.5 = half day wage. Leave blank to use employee or policy default.</p>
            @error('half_day_pay_ratio')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="erp-form-label">Notes</label>
            <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Reason for manual entry…">{{ old('notes') }}</textarea>
            @error('notes')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="erp-btn-primary">Save Half Day</button>
        </div>
    </div>
</form>
@endsection
