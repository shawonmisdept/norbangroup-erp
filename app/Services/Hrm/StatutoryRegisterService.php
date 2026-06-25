<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveApplication;
use App\Models\Hrm\PayrollItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatutoryRegisterService
{
    public function attendanceRegister(int $factoryId, Carbon $from, Carbon $to): Collection
    {
        return AttendanceDailyLog::query()
            ->where('factory_id', $factoryId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->with(['employee:id,employee_code,name,department_id', 'employee.department:id,name'])
            ->orderBy('attendance_date')
            ->orderBy('employee_id')
            ->get()
            ->map(fn ($log) => [
                'date'           => $log->attendance_date->format('Y-m-d'),
                'employee_code'  => $log->employee?->employee_code,
                'employee_name'  => $log->employee?->name,
                'department'     => $log->employee?->department?->name,
                'status'         => $log->status,
                'check_in'       => $log->check_in?->format('H:i'),
                'check_out'      => $log->check_out?->format('H:i'),
                'work_hours'     => round((int) $log->work_minutes / 60, 2),
                'late_minutes'   => (int) $log->late_minutes,
            ]);
    }

    public function wageRegister(int $factoryId, int $year, int $month): Collection
    {
        return PayrollItem::query()
            ->where('factory_id', $factoryId)
            ->whereHas('period', fn ($q) => $q->where('year', $year)->where('month', $month))
            ->with(['employee:id,employee_code,name,worker_category_id', 'employee.workerCategory:id,name'])
            ->orderBy('employee_id')
            ->get()
            ->map(fn ($item) => [
                'employee_code'    => $item->employee?->employee_code,
                'employee_name'    => $item->employee?->name,
                'worker_category'  => $item->employee?->workerCategory?->name,
                'pay_type'         => $item->pay_type,
                'present_days'     => $item->present_days,
                'absent_days'      => $item->absent_days,
                'leave_days'       => $item->leave_days,
                'ot_hours'         => $item->ot_hours,
                'basic_amount'     => $item->basic_amount,
                'allowances'       => $item->allowances,
                'ot_amount'        => $item->ot_amount,
                'gross_pay'        => $item->gross_pay,
                'total_deductions' => round((float) $item->absent_deduction + (float) $item->late_deduction + (float) $item->other_deduction, 2),
                'net_pay'          => $item->net_pay,
            ]);
    }

    public function leaveRegister(int $factoryId, Carbon $from, Carbon $to): Collection
    {
        return LeaveApplication::query()
            ->where('factory_id', $factoryId)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->with(['employee:id,employee_code,name', 'leaveType:id,name'])
            ->orderBy('start_date')
            ->get()
            ->map(fn ($app) => [
                'employee_code' => $app->employee?->employee_code,
                'employee_name' => $app->employee?->name,
                'leave_type'    => $app->leaveType?->name,
                'start_date'    => $app->start_date->format('Y-m-d'),
                'end_date'      => $app->end_date->format('Y-m-d'),
                'days'          => $app->total_days,
                'status'        => $app->status,
            ]);
    }

    public function otRegister(int $factoryId, int $year, int $month): Collection
    {
        return PayrollItem::query()
            ->where('factory_id', $factoryId)
            ->where('ot_hours', '>', 0)
            ->whereHas('period', fn ($q) => $q->where('year', $year)->where('month', $month))
            ->with('employee:id,employee_code,name')
            ->orderByDesc('ot_hours')
            ->get()
            ->map(fn ($item) => [
                'employee_code' => $item->employee?->employee_code,
                'employee_name' => $item->employee?->name,
                'ot_hours'      => $item->ot_hours,
                'ot_amount'     => $item->ot_amount,
                'breakdown'     => json_encode($item->head_breakdown['ot_breakdown'] ?? []),
            ]);
    }

    /** @return list<array<string, mixed>> */
    public function ageVerificationReport(int $factoryId, int $minAge = 18): array
    {
        $policy = AttendancePolicy::forFactory($factoryId);
        $minAge = max($minAge, (int) ($policy->min_employment_age ?? 18));
        $cutoff = Carbon::today()->subYears($minAge);

        return Employee::query()
            ->where('factory_id', $factoryId)
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('employee_code')
            ->get(['id', 'employee_code', 'name', 'date_of_birth', 'joining_date'])
            ->map(function (Employee $employee) use ($cutoff, $minAge) {
                $age = $employee->date_of_birth
                    ? (int) Carbon::parse($employee->date_of_birth)->age
                    : null;

                $compliant = $employee->date_of_birth
                    ? Carbon::parse($employee->date_of_birth)->lte($cutoff)
                    : false;

                return [
                    'employee_code' => $employee->employee_code,
                    'employee_name' => $employee->name,
                    'date_of_birth' => $employee->date_of_birth?->format('Y-m-d'),
                    'age'           => $age,
                    'joining_date'  => $employee->joining_date?->format('Y-m-d'),
                    'compliant'     => $compliant ? 'Yes' : 'No',
                    'min_age'       => $minAge,
                ];
            })
            ->all();
    }
}
