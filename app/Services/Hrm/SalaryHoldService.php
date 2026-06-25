<?php

namespace App\Services\Hrm;

use App\Models\Hrm\SalaryHold;

class SalaryHoldService
{
    public function isHeld(int $employeeId, string $date): bool
    {
        return SalaryHold::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'active')
            ->get()
            ->contains(fn (SalaryHold $hold) => $hold->isActiveOn($date));
    }
}
