<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\DisciplinaryRecord;
use App\Models\Hrm\Employee;
use Carbon\Carbon;

class PerformanceAutoMetricsService
{
    /** @return array{metrics: array<string, mixed>, scores: array<string, float|null>, manual_fallback: bool} */
    public function compute(Employee $employee, Carbon $from, Carbon $to): array
    {
        $factoryId = $employee->factory_id;

        $logs = AttendanceDailyLog::query()
            ->where('employee_id', $employee->id)
            ->where('factory_id', $factoryId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $workingLogs = $logs->filter(fn ($log) => ! in_array($log->status, ['off_day'], true));
        $workingDays = $workingLogs->count();

        if ($workingDays === 0) {
            return [
                'metrics'         => [
                    'working_days' => 0,
                    'present_days' => 0,
                    'late_days'    => 0,
                    'leave_days'   => 0,
                    'discipline'   => $this->disciplineSummary($employee, $from, $to),
                ],
                'scores'          => [
                    'attendance'  => null,
                    'punctuality' => null,
                    'discipline'  => $this->disciplineScore($employee, $from, $to),
                ],
                'manual_fallback' => true,
            ];
        }

        $presentDays = $workingLogs->filter(fn ($log) => in_array($log->status, ['present', 'late'], true))->count();
        $lateDays = $workingLogs->where('status', 'late')->count();
        $leaveDays = $workingLogs->where('status', 'leave')->count();
        $halfDays = $workingLogs->where('status', 'half_day')->count();

        $attendanceEquivalent = $presentDays + $leaveDays + ($halfDays * 0.5);
        $attendancePct = round(min(100, ($attendanceEquivalent / max($workingDays, 1)) * 100), 2);

        $latePenalty = (float) config('hrm.performance.late_day_penalty', 5);
        $punctualityScore = round(max(0, 100 - ($lateDays * $latePenalty)), 2);

        $disciplineScore = $this->disciplineScore($employee, $from, $to);

        return [
            'metrics' => [
                'working_days'    => $workingDays,
                'present_days'    => $presentDays,
                'late_days'       => $lateDays,
                'leave_days'      => $leaveDays,
                'half_days'       => $halfDays,
                'attendance_pct'  => $attendancePct,
                'punctuality_pct' => $punctualityScore,
                'discipline'      => $this->disciplineSummary($employee, $from, $to),
            ],
            'scores' => [
                'attendance'  => $attendancePct,
                'punctuality' => $punctualityScore,
                'discipline'  => $disciplineScore,
            ],
            'manual_fallback' => false,
        ];
    }

    /** @return array{count: int, penalty: float} */
    private function disciplineSummary(Employee $employee, Carbon $from, Carbon $to): array
    {
        $records = DisciplinaryRecord::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('incident_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $penalties = config('hrm.performance.discipline_penalties', []);
        $totalPenalty = 0.0;

        foreach ($records as $record) {
            $totalPenalty += (float) ($penalties[$record->action_type] ?? 5);
        }

        return [
            'count'   => $records->count(),
            'penalty' => $totalPenalty,
        ];
    }

    private function disciplineScore(Employee $employee, Carbon $from, Carbon $to): float
    {
        $summary = $this->disciplineSummary($employee, $from, $to);

        return round(max(0, 100 - $summary['penalty']), 2);
    }
}
