@extends('layouts.admin')

@section('title', 'Edit Attendance Policy')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.attendance.hub') }}" class="hover:text-brand">Attendance</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.attendance.policy.index') }}" class="hover:text-brand">Policy</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $policy->factory?->name }}</span>
@endsection

@section('admin-content')
@include('admin.hrm.partials.submodule-nav', ['section' => 'attendance', 'current' => 'policy'])

@include('partials.erp.page-header', [
    'title' => 'Edit Policy — ' . ($policy->factory?->name ?? ''),
    'subtitle' => 'Configure late detection and salary deduction rules',
    'actions' => '<a href="' . route('admin.hrm.attendance.policy.index') . '" class="erp-btn-secondary">← Back</a>',
])

<form method="POST" action="{{ route('admin.hrm.attendance.policy.update', $policy) }}" class="erp-panel max-w-2xl">
    @csrf
    @method('PUT')
    <div class="erp-panel-body space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="erp-form-label">Late grace (minutes)</label>
                <input type="number" name="late_grace_minutes" value="{{ old('late_grace_minutes', $policy->late_grace_minutes) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">After shift start + this many minutes = late</p>
            </div>
            <div>
                <label class="erp-form-label">Consecutive late grace (days)</label>
                <input type="number" name="consecutive_late_grace_days" value="{{ old('consecutive_late_grace_days', $policy->consecutive_late_grace_days) }}" required min="0" max="10" class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Default 3 — no cut for first 3 consecutive lates</p>
            </div>
            <div>
                <label class="erp-form-label">Late deduction basis</label>
                <select name="late_deduction_basis" class="erp-input !text-xs">
                    @foreach($bases as $value => $label)
                        <option value="{{ $value }}" {{ old('late_deduction_basis', $policy->late_deduction_basis) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Half-day minimum (minutes)</label>
                <input type="number" name="min_half_day_minutes" value="{{ old('min_half_day_minutes', $policy->min_half_day_minutes) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Below this work time = half day (auto)</p>
            </div>
            <div>
                <label class="erp-form-label">Full day (minutes)</label>
                <input type="number" name="full_day_minutes" value="{{ old('full_day_minutes', $policy->full_day_minutes ?? 480) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Regular hours before OT kicks in</p>
            </div>
            <div>
                <label class="erp-form-label">Max monthly OT (hours)</label>
                <input type="number" name="max_monthly_ot_hours" value="{{ old('max_monthly_ot_hours', $policy->max_monthly_ot_hours ?? 104) }}" required min="0" class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Alert HR when exceeded during payroll (0 = disabled)</p>
            </div>
            <div>
                <label class="erp-form-label">OT multiplier — normal</label>
                <input type="number" step="0.01" min="1" max="5" name="ot_multiplier_normal" value="{{ old('ot_multiplier_normal', $policy->ot_multiplier_normal ?? 2) }}" required class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">OT multiplier — holiday/weekend</label>
                <input type="number" step="0.01" min="1" max="5" name="ot_multiplier_holiday" value="{{ old('ot_multiplier_holiday', $policy->ot_multiplier_holiday ?? 2) }}" required class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">OT multiplier — night shift</label>
                <input type="number" step="0.01" min="1" max="5" name="ot_multiplier_night" value="{{ old('ot_multiplier_night', $policy->ot_multiplier_night ?? 2) }}" required class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Max daily hours</label>
                <input type="number" step="0.1" min="0" max="24" name="max_daily_hours" value="{{ old('max_daily_hours', $policy->max_daily_hours ?? 10) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">BD labour law: 10 hrs/day incl. OT (0 = disabled)</p>
            </div>
            <div>
                <label class="erp-form-label">Max weekly hours</label>
                <input type="number" step="0.1" min="0" max="168" name="max_weekly_hours" value="{{ old('max_weekly_hours', $policy->max_weekly_hours ?? 60) }}" required class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Minimum employment age</label>
                <input type="number" min="14" max="25" name="min_employment_age" value="{{ old('min_employment_age', $policy->min_employment_age ?? 18) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">Child labour prevention — default 18 years</p>
            </div>
            <div>
                <label class="erp-form-label">Default half-day pay ratio</label>
                <input type="number" step="0.01" min="0.01" max="1" name="default_half_day_pay_ratio"
                    value="{{ old('default_half_day_pay_ratio', $policy->default_half_day_pay_ratio ?? 0.5) }}" required class="erp-input !text-xs">
                <p class="text-[10px] text-gray-400 mt-1">0.5 = half day wage unless overridden per employee</p>
            </div>
            <div>
                <label class="erp-form-label">Early leave grace (minutes)</label>
                <input type="number" name="early_leave_grace_minutes" value="{{ old('early_leave_grace_minutes', $policy->early_leave_grace_minutes) }}" required class="erp-input !text-xs">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="hidden" name="late_streak_resets_on_absent" value="0">
                    <input type="checkbox" name="late_streak_resets_on_absent" value="1" {{ old('late_streak_resets_on_absent', $policy->late_streak_resets_on_absent) ? 'checked' : '' }} class="rounded border-gray-300 text-brand">
                    Reset late streak on absent day
                </label>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-xs text-gray-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $policy->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-brand">
                    Active
                </label>
            </div>
        </div>
        <div class="pt-2">
            <button type="submit" class="erp-btn-primary">Save Policy</button>
        </div>
    </div>
</form>
@endsection
