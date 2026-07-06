<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\AttendanceRawPunch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceDailyLogPhotoService
{
    /** @param LengthAwarePaginator<int, AttendanceDailyLog>|Collection<int, AttendanceDailyLog> $logs */
    public function attachMobileCheckInPhotos(LengthAwarePaginator|Collection $logs): void
    {
        $items = $logs instanceof LengthAwarePaginator ? $logs->getCollection() : $logs;

        if ($items->isEmpty()) {
            return;
        }

        $employeeIds = $items->pluck('employee_id')->filter()->unique()->values();

        $punches = AttendanceRawPunch::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('punch_type', 'in')
            ->whereNotNull('photo_path')
            ->whereIn('source', ['mobile_gps', 'qr_scan'])
            ->orderByDesc('punched_at')
            ->get()
            ->groupBy(fn (AttendanceRawPunch $punch) => $punch->employee_id . '|' . $punch->punched_at->toDateString());

        foreach ($items as $log) {
            $key = $log->employee_id . '|' . $log->attendance_date->toDateString();
            $log->setRelation('mobileCheckInPhotoPunch', $punches->get($key)?->first());
        }
    }
}
