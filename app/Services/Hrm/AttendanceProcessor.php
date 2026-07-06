<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendancePeriod;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\AttendanceRawPunch;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\Shift;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceProcessor
{
    public function __construct(
        private EmployeeScheduleService $schedule,
        private ShiftWorkCalculator $shiftWork,
    ) {}

    public function processPeriod(AttendancePeriod $period, ?int $userId = null, bool $markAbsences = true): array
    {
        if ($period->isFrozen()) {
            return ['processed' => 0, 'absences' => 0, 'message' => 'Period is frozen.'];
        }

        $from = $period->start_date->copy();
        $to = $period->end_date->copy();

        if ($to->greaterThan(now()->startOfDay())) {
            $to = now()->startOfDay();
        }

        $processed = $this->processDateRange($period->factory_id, $from, $to, $period);
        $absences = $markAbsences
            ? $this->markAbsences($period->factory_id, $from, $to, $period)
            : 0;

        $period->update([
            'status'       => 'processed',
            'processed_at' => now(),
            'processed_by' => $userId,
        ]);

        return [
            'processed' => $processed,
            'absences'  => $absences,
            'message'   => "Processed {$processed} punch day(s), marked {$absences} absent.",
        ];
    }

    public function processDateRange(int $factoryId, Carbon $from, Carbon $to, ?AttendancePeriod $period = null): int
    {
        if ($period?->isFrozen()) {
            return 0;
        }

        $count = 0;

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $count += $this->processDate($factoryId, $date, $period);
        }

        return $count;
    }

    public function processDate(int $factoryId, Carbon $date, ?AttendancePeriod $period = null): int
    {
        if ($period?->isFrozen()) {
            return 0;
        }

        $policy = AttendancePolicy::forFactory($factoryId);
        $processed = 0;

        $punches = AttendanceRawPunch::query()
            ->where('factory_id', $factoryId)
            ->whereDate('punched_at', $date->toDateString())
            ->whereNotNull('employee_id')
            ->whereNull('processed_at')
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_id');

        foreach ($punches as $employeeId => $employeePunches) {
            $employee = Employee::with('shift')->find($employeeId);

            if (! $employee) {
                continue;
            }

            $existing = AttendanceDailyLog::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->first();

            if ($existing?->is_manual_half_day) {
                AttendanceRawPunch::whereIn('id', $employeePunches->pluck('id'))
                    ->update(['processed_at' => now()]);
                $processed++;

                continue;
            }

            $allPunches = AttendanceRawPunch::query()
                ->where('employee_id', $employee->id)
                ->whereDate('punched_at', $date->toDateString())
                ->orderBy('punched_at')
                ->get();

            $this->upsertDailyLog($employee, $date, $allPunches, $policy, $period, $existing);
            AttendanceRawPunch::query()
                ->where('employee_id', $employee->id)
                ->whereDate('punched_at', $date->toDateString())
                ->whereNull('processed_at')
                ->update(['processed_at' => now()]);
            $processed++;
        }

        return $processed;
    }

    public function markAbsences(int $factoryId, Carbon $from, Carbon $to, ?AttendancePeriod $period = null): int
    {
        if ($period?->isFrozen()) {
            return 0;
        }

        $count = 0;
        $employees = Employee::query()
            ->where('factory_id', $factoryId)
            ->whereIn('status', ['active', 'probation'])
            ->get(['id', 'factory_id', 'shift_id', 'weekend_days', 'weekend_ot_allowed', 'half_day_pay_ratio']);

        $holidays = $this->schedule->factoryHolidayDates($factoryId, $from, $to);

        foreach (CarbonPeriod::create($from, $to) as $date) {
            if (in_array($date->toDateString(), $holidays, true)) {
                continue;
            }

            if ($date->isFuture()) {
                continue;
            }

            $existingEmployeeIds = AttendanceDailyLog::query()
                ->where('factory_id', $factoryId)
                ->whereDate('attendance_date', $date->toDateString())
                ->pluck('employee_id');

            foreach ($employees as $employee) {
                if ($this->schedule->isWeekend($employee, $date)) {
                    if (! $existingEmployeeIds->contains($employee->id)) {
                        AttendanceDailyLog::create([
                            'factory_id'           => $factoryId,
                            'employee_id'          => $employee->id,
                            'attendance_period_id' => $period?->id,
                            'shift_id'             => $employee->shift_id,
                            'attendance_date'      => $date->toDateString(),
                            'status'               => 'off_day',
                            'punch_count'          => 0,
                        ]);
                        $count++;
                    }

                    continue;
                }

                if ($existingEmployeeIds->contains($employee->id)) {
                    continue;
                }

                if ($this->schedule->hasApprovedOsd($employee, $date)) {
                    AttendanceDailyLog::create([
                        'factory_id'           => $factoryId,
                        'employee_id'          => $employee->id,
                        'attendance_period_id' => $period?->id,
                        'shift_id'             => $employee->shift_id,
                        'attendance_date'      => $date->toDateString(),
                        'status'               => 'present',
                        'punch_count'          => 0,
                        'notes'                => 'Official duty (OSD)',
                        'is_manual'            => true,
                    ]);
                    $count++;

                    continue;
                }

                AttendanceDailyLog::create([
                    'factory_id'           => $factoryId,
                    'employee_id'          => $employee->id,
                    'attendance_period_id' => $period?->id,
                    'shift_id'             => $employee->shift_id,
                    'attendance_date'      => $date->toDateString(),
                    'status'               => 'absent',
                    'punch_count'          => 0,
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function freezePeriod(AttendancePeriod $period): void
    {
        $period->update([
            'status'    => 'frozen',
            'frozen_at' => now(),
        ]);
    }

    private function upsertDailyLog(
        Employee $employee,
        Carbon $date,
        Collection $punches,
        AttendancePolicy $policy,
        ?AttendancePeriod $period,
        ?AttendanceDailyLog $existing = null
    ): AttendanceDailyLog {
        $sorted = $punches->sortBy('punched_at')->values();
        $checkIn = $sorted->first()->punched_at;
        $checkOut = $sorted->count() > 1 ? $sorted->last()->punched_at : null;
        $shift = $employee->shift;

        $isWeekend = $this->schedule->isWeekend($employee, $date);
        $isHoliday = $this->schedule->isHoliday($employee->factory_id, $date);

        if (($isWeekend || $isHoliday) && ! $this->schedule->allowsWeekendOt($employee)) {
            $data = [
                'factory_id'           => $employee->factory_id,
                'attendance_period_id' => $period?->id,
                'shift_id'             => $shift?->id,
                'check_in'             => $checkIn,
                'check_out'            => $checkOut,
                'status'               => $isHoliday ? 'holiday' : 'off_day',
                'punch_count'          => $sorted->count(),
                'work_minutes'         => 0,
            ];

            return $this->saveDailyLog($employee, $date, $data, $existing);
        }

        $metrics = $this->calculateMetrics($employee, $date, $checkIn, $checkOut, $shift, $policy);

        return $this->saveDailyLog($employee, $date, [
            'factory_id'           => $employee->factory_id,
            'attendance_period_id' => $period?->id,
            'shift_id'             => $shift?->id,
            'check_in'             => $checkIn,
            'check_out'            => $checkOut,
            'expected_in'          => $shift?->start_time,
            'expected_out'         => $shift?->end_time,
            'work_minutes'         => $metrics['work_minutes'],
            'late_minutes'         => $metrics['late_minutes'],
            'early_leave_minutes'  => $metrics['early_leave_minutes'],
            'break_minutes'        => $metrics['break_minutes'],
            'punch_count'          => $sorted->count(),
            'status'               => $metrics['status'],
            'half_day_type'        => $metrics['half_day_type'],
            'half_day_pay_ratio'   => $metrics['status'] === 'half_day'
                ? $this->schedule->halfDayPayRatio($employee, null, $policy)
                : null,
            'is_manual_half_day'   => false,
        ], $existing);
    }

    /** @param array<string, mixed> $data */
    private function saveDailyLog(
        Employee $employee,
        Carbon $date,
        array $data,
        ?AttendanceDailyLog $existing = null
    ): AttendanceDailyLog {
        if ($existing) {
            $existing->update($data);

            return $existing->refresh();
        }

        return AttendanceDailyLog::create(array_merge([
            'employee_id'     => $employee->id,
            'attendance_date' => $date->toDateString(),
        ], $data));
    }

    private function calculateMetrics(
        Employee $employee,
        Carbon $date,
        Carbon $checkIn,
        ?Carbon $checkOut,
        ?Shift $shift,
        AttendancePolicy $policy
    ): array {
        $breakMinutes = $this->shiftWork->resolvedBreakMinutes($date, $shift);
        $workMinutes = $this->shiftWork->workMinutes($checkIn, $checkOut, $date, $shift);
        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $halfDayType = null;

        $isWeekendOt = $this->schedule->isWeekend($employee, $date)
            && $this->schedule->allowsWeekendOt($employee);

        if ($shift?->start_time && ! $isWeekendOt) {
            $expectedIn = Carbon::parse($date->toDateString() . ' ' . $shift->start_time);
            $graceEnd = $expectedIn->copy()->addMinutes($policy->late_grace_minutes);

            if ($checkIn->greaterThan($graceEnd)) {
                $lateMinutes = $expectedIn->diffInMinutes($checkIn);
            }
        }

        if ($shift?->end_time && $checkOut && ! $isWeekendOt) {
            $expectedOut = $this->shiftWork->shiftEndAt($date, $shift)
                ?? Carbon::parse($date->toDateString() . ' ' . $shift->end_time);
            $graceStart = $expectedOut->copy()->subMinutes($policy->early_leave_grace_minutes);

            if ($checkOut->lessThan($graceStart)) {
                $earlyLeaveMinutes = $checkOut->diffInMinutes($expectedOut);
            }
        }

        $status = 'present';

        if ($lateMinutes > 0) {
            $status = 'late';
        }

        $lunchHalfDay = $this->shiftWork->lunchHalfDay($date, $checkIn, $checkOut, $shift);

        if (! $isWeekendOt && $lunchHalfDay['is_half_day']) {
            $status = 'half_day';
            $halfDayType = $lunchHalfDay['type'];
        } elseif (! $isWeekendOt && $workMinutes > 0 && $workMinutes < (int) $policy->min_half_day_minutes) {
            $status = 'half_day';
            $halfDayType = $this->schedule->detectHalfDayType($date, $checkIn, $checkOut, $shift);
        }

        if ($isWeekendOt) {
            $status = 'present';
        }

        return [
            'work_minutes'        => $workMinutes,
            'late_minutes'        => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'break_minutes'       => $breakMinutes,
            'status'              => $status,
            'half_day_type'       => $halfDayType,
        ];
    }
}
