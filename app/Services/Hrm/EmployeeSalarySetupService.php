<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use Illuminate\Validation\ValidationException;

class EmployeeSalarySetupService
{
    public function assignGrade(Employee $employee, ?int $gradeId): void
    {
        if (! $gradeId) {
            return;
        }

        $grade = SalaryGrade::findOrFail($gradeId);

        if ((int) $grade->factory_id !== (int) $employee->factory_id) {
            throw ValidationException::withMessages([
                'salary_grade_id' => 'Selected salary grade does not belong to this employee factory.',
            ]);
        }

        SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'factory_id'      => $employee->factory_id,
                'salary_grade_id' => $grade->id,
            ]
        );
    }
}
