<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\Shift;
use App\Models\Hrm\ShiftRosterEntry;
use Carbon\Carbon;

class ShiftRosterService
{
    public function resolveShift(Employee $employee, Carbon $date): ?Shift
    {
        $entry = ShiftRosterEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('roster_date', $date->toDateString())
            ->whereHas('roster', fn ($q) => $q
                ->where('factory_id', $employee->factory_id)
                ->where('status', 'published'))
            ->with('shift')
            ->first();

        if ($entry?->shift) {
            return $entry->shift;
        }

        return $employee->shift;
    }

    public function resolveLineId(Employee $employee, Carbon $date): ?int
    {
        $entry = ShiftRosterEntry::query()
            ->where('employee_id', $employee->id)
            ->whereDate('roster_date', $date->toDateString())
            ->whereHas('roster', fn ($q) => $q
                ->where('factory_id', $employee->factory_id)
                ->where('status', 'published'))
            ->first();

        return $entry?->line_id ?? $employee->line_id;
    }
}
