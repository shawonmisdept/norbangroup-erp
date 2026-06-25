<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use App\Models\Hrm\PayrollItem;
use App\Models\Hrm\PayrollPeriod;
use App\Models\Hrm\PayrollRun;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollProcessor
{
    private const STANDARD_MONTH_DAYS = 26;

    private const STANDARD_DAY_MINUTES = 480;

    public function __construct(
        private LateDeductionCalculator $lateCalculator,
        private EmployeeScheduleService $schedule,
        private HrmNotificationService $notifications,
        private OtCalculator $otCalculator,
        private StatutoryPayrollService $statutoryPayroll,
        private SalaryHoldService $salaryHold,
    ) {}

    public function calculatePeriod(PayrollPeriod $period, User $user): PayrollRun
    {
        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'period' => 'Payroll period is frozen and cannot be recalculated.',
            ]);
        }

        $attendancePeriod = $period->attendancePeriod
            ?? AttendancePeriod::where('factory_id', $period->factory_id)
                ->where('year', $period->year)
                ->where('month', $period->month)
                ->first();

        if (! $attendancePeriod?->isFrozen()) {
            throw ValidationException::withMessages([
                'attendance' => 'Attendance for ' . $period->periodLabel() . ' must be frozen before payroll calculation.',
            ]);
        }

        return DB::transaction(function () use ($period, $user, $attendancePeriod) {
            $period->update(['attendance_period_id' => $attendancePeriod->id]);

            $run = PayrollRun::create([
                'payroll_period_id' => $period->id,
                'status'            => 'running',
                'started_at'        => now(),
                'run_by'            => $user->id,
            ]);

            $employees = Employee::query()
                ->where('factory_id', $period->factory_id)
                ->whereIn('status', ['active', 'probation'])
                ->with('salaryStructure')
                ->get();

            $processed = 0;

            foreach ($employees as $employee) {
                $structure = $employee->salaryStructure;

                if (! $structure || ! $structure->is_active) {
                    continue;
                }

                $holdDate = $period->end_date?->toDateString()
                    ?? sprintf('%04d-%02d-01', $period->year, $period->month);

                if ($this->salaryHold->isHeld($employee->id, $holdDate)) {
                    continue;
                }

                $stats = $this->attendanceStats($employee->id, $period);
                $built = $this->buildPayrollItem($period, $run, $employee, $structure, $stats);
                $statutory = $built['statutory'] ?? null;
                unset($built['statutory']);

                PayrollItem::updateOrCreate(
                    ['employee_id' => $employee->id, 'payroll_period_id' => $period->id],
                    $built
                );

                if ($statutory !== null) {
                    $this->statutoryPayroll->persistLedgers($employee, $period, $statutory);
                }

                $processed++;
            }

            $run->update([
                'status'         => 'completed',
                'employee_count' => $processed,
                'completed_at'   => now(),
            ]);

            $period->update([
                'status'        => 'calculated',
                'calculated_at' => now(),
                'calculated_by' => $user->id,
            ]);

            return $run->fresh(['period']);
        });
    }

    public function freezePeriod(PayrollPeriod $period, User $user): PayrollPeriod
    {
        if ($period->isFrozen()) {
            throw ValidationException::withMessages([
                'period' => 'Payroll period is already frozen.',
            ]);
        }

        if ($period->status !== 'calculated') {
            throw ValidationException::withMessages([
                'period' => 'Calculate payroll before freezing the period.',
            ]);
        }

        if ($period->items()->count() === 0) {
            throw ValidationException::withMessages([
                'period' => 'No payroll items found for this period.',
            ]);
        }

        $period->update([
            'status'    => 'frozen',
            'frozen_at' => now(),
            'frozen_by' => $user->id,
        ]);

        return $period->fresh();
    }

    private function attendanceStats(int $employeeId, PayrollPeriod $period): array
    {
        $logs = AttendanceDailyLog::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$period->start_date, $period->end_date])
            ->get();

        return [
            'present'  => $logs->where('status', 'present')->count(),
            'late'     => $logs->where('status', 'late')->count(),
            'absent'   => $logs->where('status', 'absent')->count(),
            'leave'    => $logs->where('status', 'leave')->count(),
            'half_day' => $logs->where('status', 'half_day')->count(),
            'logs'     => $logs,
        ];
    }

    private function buildPayrollItem(
        PayrollPeriod $period,
        PayrollRun $run,
        Employee $employee,
        SalaryStructure $structure,
        array $stats
    ): array {
        $logs = $stats['logs'];
        $policy = AttendancePolicy::forFactory($employee->factory_id);

        $regularLogs = $logs->filter(function ($log) use ($employee) {
            if ($this->schedule->isWeekend($employee, $log->attendance_date)) {
                return false;
            }

            if ($this->schedule->isHoliday($employee->factory_id, $log->attendance_date)) {
                return false;
            }

            return true;
        });

        $present = (int) $regularLogs->where('status', 'present')->count();
        $late = (int) $regularLogs->where('status', 'late')->count();
        $absent = (int) $regularLogs->where('status', 'absent')->count();
        $leave = (int) $regularLogs->where('status', 'leave')->count();
        $halfDayLogs = $logs->where('status', 'half_day');
        $halfSummary = $this->schedule->halfDaySummary($employee, $halfDayLogs, $policy);
        $halfDay = $halfSummary['total'];
        $paidDays = $present + $late + $leave + $halfSummary['paid_units'];
        $allowances = (float) $structure->totalAllowances();
        $otHours = 0.0;
        $otAmount = 0.0;
        $otBreakdown = [];

        if ($structure->pay_type === 'wages') {
            $dailyWage = (float) $structure->daily_wage;
            $basicAmount = round($paidDays * $dailyWage, 2);
            $allowanceAmount = round(($allowances / self::STANDARD_MONTH_DAYS) * $paidDays, 2);
            $hourlyRate = $dailyWage / 8;
            $otResult = $this->otCalculator->calculate($employee, $logs, $policy, $hourlyRate);
            $otHours = $otResult['ot_hours'];
            $otAmount = $otResult['ot_amount'];
            $otBreakdown = $otResult['breakdown'];

            if ($policy->max_monthly_ot_hours > 0 && $otHours > $policy->max_monthly_ot_hours) {
                $this->notifications->otLimitExceeded(
                    $employee,
                    $otHours,
                    (float) $policy->max_monthly_ot_hours,
                    $period->periodLabel()
                );
            }

            $grossPay = $basicAmount + $allowanceAmount + $otAmount;
            $absentDeduction = round($absent * $dailyWage, 2);
        } else {
            $basicAmount = $this->resolveBasicAmount($structure);
            $allowanceAmount = $allowances;
            $grossPay = $this->resolveFullMonthGross($structure, $basicAmount, $allowanceAmount);
            $dailyRate = $basicAmount > 0
                ? $basicAmount / self::STANDARD_MONTH_DAYS
                : $grossPay / self::STANDARD_MONTH_DAYS;
            $absentDeduction = round($absent * $dailyRate, 2);

            $halfDayDeduction = 0.0;

            foreach ($halfDayLogs as $halfLog) {
                $halfDayDeduction += round($dailyRate * $this->schedule->halfDayPayRatio($employee, $halfLog, $policy), 2);
            }

            $absentDeduction += $halfDayDeduction;
        }

        $lateResult = $this->lateCalculator->calculate($employee, $structure, $logs);
        $lateDeduction = $lateResult['amount'];
        $lateForgivenDays = $lateResult['forgiven_days'];
        $lateChargeDays = $lateResult['charged_days'];

        $headDeduction = $this->resolveHeadDeductions($structure);
        $statutory = $this->statutoryPayroll->apply($employee, $period, $structure, $grossPay, $basicAmount);
        $statutoryDeduction = $statutory['statutory_total'];
        $otherDeduction = round($headDeduction + $statutoryDeduction, 2);
        $netPay = max(0, round($grossPay - $absentDeduction - $lateDeduction - $otherDeduction, 2));

        $headBreakdown = $this->buildHeadBreakdown($structure, $otAmount, $absentDeduction, $lateDeduction, $headDeduction);
        $headBreakdown['late_summary'] = [
            'total_late'    => $late,
            'forgiven_days' => $lateForgivenDays,
            'charged_days'  => $lateChargeDays,
            'day_rate'      => $lateResult['day_rate'],
            'grace_days'    => $lateResult['grace_days'],
        ];
        $headBreakdown['half_summary'] = $halfSummary;

        if ($otBreakdown !== []) {
            $headBreakdown['ot_breakdown'] = $otBreakdown;
        }

        if ($statutory['tds_amount'] > 0) {
            $headBreakdown['deductions']['TDS'] = $statutory['tds_amount'];
        }
        if ($statutory['pf_employee_amount'] > 0) {
            $headBreakdown['deductions']['PF'] = $statutory['pf_employee_amount'];
        }
        if ($statutory['loan_deduction'] > 0) {
            $headBreakdown['deductions']['LOAN'] = $statutory['loan_deduction'];
        }

        unset($statutory['statutory_total']);

        return [
            'factory_id'        => $period->factory_id,
            'employee_id'       => $employee->id,
            'payroll_period_id' => $period->id,
            'payroll_run_id'    => $run->id,
            'pay_type'          => $structure->pay_type,
            'present_days'      => $present + $late,
            'absent_days'       => $absent,
            'leave_days'        => $leave,
            'late_days'         => $late,
            'late_forgiven_days'=> $lateForgivenDays,
            'late_charge_days'  => $lateChargeDays,
            'half_days'         => $halfDay,
            'half_day_first'    => $halfSummary['first_half'],
            'half_day_second'   => $halfSummary['second_half'],
            'half_day_paid_units'=> $halfSummary['paid_units'],
            'ot_hours'          => $otHours,
            'ot_amount'         => $otAmount,
            'basic_amount'      => $basicAmount,
            'allowances'        => $allowanceAmount ?? $allowances,
            'gross_pay'         => $grossPay,
            'absent_deduction'  => $absentDeduction,
            'late_deduction'    => $lateDeduction,
            'other_deduction'   => $otherDeduction,
            'tds_amount'        => $statutory['tds_amount'],
            'pf_employee_amount'=> $statutory['pf_employee_amount'],
            'pf_employer_amount'=> $statutory['pf_employer_amount'],
            'loan_deduction'    => $statutory['loan_deduction'],
            'net_pay'           => $netPay,
            'head_breakdown'    => $headBreakdown,
            'statutory'         => $statutory,
            'payment_method'    => $structure->payment_method,
            'bank_account'      => $structure->bank_account,
        ];
    }

    private function resolveBasicAmount(SalaryStructure $structure): float
    {
        $fromHead = $structure->headAmount('BASIC');

        if ($fromHead > 0) {
            return $fromHead;
        }

        return (float) $structure->basic_salary;
    }

    private function resolveFullMonthGross(SalaryStructure $structure, float $basic, float $allowances): float
    {
        if ((float) $structure->gross_salary > 0) {
            return (float) $structure->gross_salary;
        }

        return round($basic + $allowances, 2);
    }

    private function resolveHeadDeductions(SalaryStructure $structure): float
    {
        if (! $structure->head_amounts) {
            return 0.0;
        }

        $codes = $this->deductionHeadCodes($structure->factory_id);
        $total = 0.0;

        foreach ($codes as $code) {
            $total += $structure->headAmount($code);
        }

        return round($total, 2);
    }

    /** @return list<string> */
    private function deductionHeadCodes(int $factoryId): array
    {
        return Cache::remember(
            "hrm.salary.deduction_codes.{$factoryId}",
            300,
            fn () => SalaryHead::query()
                ->where('factory_id', $factoryId)
                ->whereIn('head_type', ['D', 'S'])
                ->where('is_active', true)
                ->pluck('code')
                ->map(fn ($c) => strtoupper(trim($c)))
                ->all()
        );
    }

    /** @return array{earnings: array<string, float>, deductions: array<string, float>} */
    private function buildHeadBreakdown(
        SalaryStructure $structure,
        float $otAmount,
        float $absentDeduction,
        float $lateDeduction,
        float $otherDeduction
    ): array {
        $earnings = [];
        $deductions = [];

        if ($structure->head_amounts) {
            $earningCodes = SalaryHead::query()
                ->where('factory_id', $structure->factory_id)
                ->where('head_type', 'E')
                ->where('is_active', true)
                ->pluck('code')
                ->map(fn ($c) => strtoupper(trim($c)));

            foreach ($earningCodes as $code) {
                $amount = $structure->headAmount($code);

                if ($amount > 0) {
                    $earnings[$code] = $amount;
                }
            }

            foreach ($this->deductionHeadCodes($structure->factory_id) as $code) {
                $amount = $structure->headAmount($code);

                if ($amount > 0) {
                    $deductions[$code] = $amount;
                }
            }
        }

        if ($otAmount > 0) {
            $earnings['OT'] = $otAmount;
        }

        if ($absentDeduction > 0) {
            $deductions['ABSENT'] = $absentDeduction;
        }

        if ($lateDeduction > 0) {
            $deductions['LATE'] = $lateDeduction;
        }

        if ($otherDeduction > 0 && empty($deductions)) {
            $deductions['OTHER'] = $otherDeduction;
        }

        return ['earnings' => $earnings, 'deductions' => $deductions];
    }
}

