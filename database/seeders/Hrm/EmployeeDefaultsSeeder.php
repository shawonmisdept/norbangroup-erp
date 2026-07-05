<?php

namespace Database\Seeders\Hrm;

use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Services\Hrm\EmployeeSalarySetupService;
use Illuminate\Database\Seeder;

class EmployeeDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $shiftName = (string) config('hrm.employee_defaults.shift_name', 'Day Shift');
        $gradeCode = (string) config('hrm.employee_defaults.salary_grade_code', 'SR-01');
        $salarySetup = app(EmployeeSalarySetupService::class);

        $shiftUpdated = 0;
        $gradeUpdated = 0;

        foreach (Factory::where('is_active', true)->get() as $factory) {
            $dayShift = Shift::query()
                ->where('factory_id', $factory->id)
                ->where('name', $shiftName)
                ->where('is_active', true)
                ->first();

            $grade = SalaryGrade::query()
                ->where('factory_id', $factory->id)
                ->where('code', $gradeCode)
                ->where('is_active', true)
                ->first();

            if ($dayShift) {
                $shiftUpdated += Employee::query()
                    ->where('factory_id', $factory->id)
                    ->whereNull('shift_id')
                    ->update(['shift_id' => $dayShift->id]);
            }

            if ($grade) {
                Employee::query()
                    ->where('factory_id', $factory->id)
                    ->whereDoesntHave('salaryStructure', fn ($q) => $q->where('salary_grade_id', $grade->id))
                    ->each(function (Employee $employee) use ($grade, $salarySetup, &$gradeUpdated) {
                        $structure = $employee->salaryStructure;

                        if ($structure?->salary_grade_id === $grade->id) {
                            return;
                        }

                        $salarySetup->assignGrade($employee, $grade->id);
                        $gradeUpdated++;
                    });
            }
        }

        $this->command?->info("Employee defaults applied: {$shiftUpdated} shift assignment(s), {$gradeUpdated} salary grade assignment(s) ({$shiftName}, {$gradeCode}).");
    }
}
