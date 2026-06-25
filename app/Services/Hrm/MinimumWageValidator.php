<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Validation\ValidationException;

class MinimumWageValidator
{
    public function validateDailyWage(Employee $employee, float $dailyWage): void
    {
        if ($dailyWage <= 0) {
            return;
        }

        $employee->loadMissing('workerCategory');

        if (! $employee->worker_category_id) {
            return;
        }

        $category = WorkerCategory::find($employee->worker_category_id);

        if (! $category || $category->minimum_wage === null || (float) $category->minimum_wage <= 0) {
            return;
        }

        if ($dailyWage < (float) $category->minimum_wage) {
            throw ValidationException::withMessages([
                'daily_wage' => sprintf(
                    'Daily wage ৳%s is below the minimum wage ৳%s for category "%s".',
                    number_format($dailyWage, 2),
                    number_format((float) $category->minimum_wage, 2),
                    $category->name
                ),
            ]);
        }
    }
}
