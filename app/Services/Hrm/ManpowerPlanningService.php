<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\Employee;
use App\Models\Hrm\ManpowerPlan;

class ManpowerPlanningService
{
    public function lineSummary(int $factoryId, string $date): array
    {
        $plans = ManpowerPlan::query()
            ->with('line')
            ->where('factory_id', $factoryId)
            ->whereDate('plan_date', $date)
            ->get()
            ->keyBy('line_id');

        $present = AttendanceDailyLog::query()
            ->where('factory_id', $factoryId)
            ->whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'half_day'])
            ->with('employee:id,line_id')
            ->get()
            ->groupBy(fn ($log) => $log->employee?->line_id ?? 0)
            ->map->count();

        $lineIds = $plans->keys()->merge($present->keys())->unique()->filter();

        return $lineIds->map(function ($lineId) use ($plans, $present) {
            $plan = $plans->get($lineId);

            return [
                'line_id'        => $lineId,
                'line_name'      => $plan?->line?->name ?? 'Line #' . $lineId,
                'required_count' => $plan?->required_count ?? 0,
                'present_count'  => (int) ($present[$lineId] ?? 0),
                'variance'       => (int) ($present[$lineId] ?? 0) - (int) ($plan?->required_count ?? 0),
            ];
        })->values()->all();
    }
}
