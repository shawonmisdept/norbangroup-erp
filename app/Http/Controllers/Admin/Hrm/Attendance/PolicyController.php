<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\AttendancePolicy;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = AttendancePolicy::query()->with('factory')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $policies = $query->paginate(25)->withQueryString();

        return view('admin.hrm.attendance.policy.index', [
            'policies'  => $policies,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function edit(Request $request, AttendancePolicy $policy)
    {
        $this->authorizeFactoryAccess($request, $policy->factory_id);
        $policy->load('factory');

        return view('admin.hrm.attendance.policy.form', [
            'policy'    => $policy,
            'factories' => $this->factoryOptions($request),
            'bases'     => AttendancePolicy::LATE_DEDUCTION_BASES,
        ]);
    }

    public function update(Request $request, AttendancePolicy $policy)
    {
        $this->authorizeFactoryAccess($request, $policy->factory_id);

        $validated = $request->validate([
            'late_grace_minutes'           => ['required', 'integer', 'min:0', 'max:120'],
            'consecutive_late_grace_days'  => ['required', 'integer', 'min:0', 'max:10'],
            'late_deduction_basis'         => ['required', 'in:basic,gross'],
            'late_streak_resets_on_absent' => ['nullable', 'boolean'],
            'early_leave_grace_minutes'    => ['required', 'integer', 'min:0', 'max:120'],
            'min_half_day_minutes'         => ['required', 'integer', 'min:60', 'max:480'],
            'full_day_minutes'             => ['required', 'integer', 'min:240', 'max:720'],
            'max_monthly_ot_hours'         => ['required', 'integer', 'min:0', 'max:300'],
            'ot_multiplier_normal'         => ['required', 'numeric', 'min:1', 'max:5'],
            'ot_multiplier_holiday'        => ['required', 'numeric', 'min:1', 'max:5'],
            'ot_multiplier_night'          => ['required', 'numeric', 'min:1', 'max:5'],
            'max_daily_hours'              => ['required', 'numeric', 'min:0', 'max:24'],
            'max_weekly_hours'             => ['required', 'numeric', 'min:0', 'max:168'],
            'min_employment_age'           => ['required', 'integer', 'min:14', 'max:25'],
            'default_half_day_pay_ratio'   => ['required', 'numeric', 'min:0.01', 'max:1'],
            'is_active'                    => ['nullable', 'boolean'],
        ]);

        $policy->update($validated + [
            'late_streak_resets_on_absent' => $request->boolean('late_streak_resets_on_absent'),
            'is_active'                    => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.hrm.attendance.policy.index')
            ->with('success', 'Attendance policy updated.');
    }
}
