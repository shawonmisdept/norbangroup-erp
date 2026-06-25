<?php

namespace App\Services\Hrm;

use App\Models\Hrm\AttendanceDailyLog;
use App\Models\Hrm\ShiftRoster;
use App\Models\Hrm\ShiftRosterEntry;
use Illuminate\Support\Collection;

class ShiftRosterVarianceService
{
    /**
     * @return Collection<int, array{
     *     employee: \App\Models\Hrm\Employee,
     *     roster_date: string,
     *     roster_shift: string,
     *     actual_shift: string|null,
     *     attendance_status: string|null,
     *     variance_type: string
     * }>
     */
    public function buildReport(int $factoryId, ?int $rosterId = null): Collection
    {
        $query = ShiftRosterEntry::query()
            ->with(['employee', 'shift', 'roster'])
            ->whereHas('roster', fn ($q) => $q
                ->where('factory_id', $factoryId)
                ->where('status', 'published'));

        if ($rosterId) {
            $query->where('roster_id', $rosterId);
        }

        $entries = $query->orderBy('roster_date')->orderBy('employee_id')->get();

        return $entries->map(function (ShiftRosterEntry $entry) {
            $log = AttendanceDailyLog::query()
                ->with('shift')
                ->where('employee_id', $entry->employee_id)
                ->whereDate('attendance_date', $entry->roster_date)
                ->first();

            $rosterShiftId = $entry->shift_id;
            $actualShiftId = $log?->shift_id;

            $varianceType = null;

            if (! $log) {
                $varianceType = 'no_attendance';
            } elseif (! in_array($log->status, ['present', 'late', 'half_day'], true)) {
                $varianceType = null;
            } elseif ($actualShiftId !== $rosterShiftId) {
                $varianceType = 'shift_mismatch';
            }

            return [
                'employee'           => $entry->employee,
                'roster_date'        => $entry->roster_date->toDateString(),
                'roster_shift'       => $entry->shift?->name ?? '—',
                'actual_shift'       => $log?->shift?->name,
                'attendance_status'  => $log?->status,
                'variance_type'      => $varianceType,
            ];
        })->filter(fn ($row) => $row['variance_type'] !== null)->values();
    }

    /** @return list<ShiftRoster> */
    public function publishedRosters(int $factoryId): Collection
    {
        return ShiftRoster::query()
            ->where('factory_id', $factoryId)
            ->where('status', 'published')
            ->orderByDesc('start_date')
            ->get();
    }
}
