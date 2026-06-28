<?php

namespace Database\Seeders\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Line;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;
use App\Services\Hrm\SalaryFormulaCalculator;
use Illuminate\Database\Seeder;

class DemoEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/demo_employees.php');

        $factory = Factory::where('name', $data['factory'])->where('is_active', true)->first();

        if (! $factory) {
            $this->command?->warn("Factory \"{$data['factory']}\" not found. Run php artisan db:seed first.");

            return;
        }

        $shift = Shift::where('factory_id', $factory->id)->where('is_active', true)->orderBy('name')->first();
        $line = Line::where('factory_id', $factory->id)->where('is_active', true)->orderBy('name')->first();

        $staffGrade = SalaryGrade::where('factory_id', $factory->id)->where('code', 'SR-01')->first();

        if (! $staffGrade) {
            $this->command?->warn('SR-01 grade not found. Run SalaryLegacySeeder first.');
        }

        $calculator = app(SalaryFormulaCalculator::class);
        $employeeIds = [];

        foreach ($data['employees'] as $row) {
            $department = Department::where('factory_id', $factory->id)
                ->where('name', $row['department'])
                ->first();

            $designation = null;

            if ($department) {
                $designation = Designation::query()
                    ->where('name', $row['designation'])
                    ->where('department_id', $department->id)
                    ->first();
            }

            if (! $designation) {
                $designation = Designation::query()
                    ->where('name', $row['designation'])
                    ->whereNull('department_id')
                    ->first();
            }

            $workerCategory = WorkerCategory::where('name', $row['worker_category'])->where('is_active', true)->first();
            $employmentType = EmploymentType::where('name', $row['employment_type'])->where('is_active', true)->first();

            $employee = Employee::withTrashed()->updateOrCreate(
                ['employee_code' => $row['employee_code']],
                [
                    'factory_id'          => $factory->id,
                    'department_id'       => $department?->id,
                    'designation_id'      => $designation?->id,
                    'worker_category_id'  => $workerCategory?->id,
                    'employment_type_id'  => $employmentType?->id,
                    'shift_id'            => $shift?->id,
                    'line_id'             => $line?->id,
                    'name'                => $row['name'],
                    'name_bangla'         => $row['name_bangla'] ?? null,
                    'gender'              => $row['gender'] ?? null,
                    'phone'               => $row['phone'] ?? null,
                    'biometric_user_id'   => $row['biometric_user_id'] ?? null,
                    'joining_date'        => $row['joining_date'] ?? now()->subYear()->toDateString(),
                    'status'              => $row['status'] ?? 'active',
                    'weekend_days'        => $row['weekend_days'] ?? [0],
                    'weekend_ot_allowed'  => $row['weekend_ot_allowed'] ?? false,
                    'half_day_pay_ratio'  => $row['half_day_pay_ratio'] ?? null,
                    'deleted_at'          => null,
                ]
            );

            $employeeIds[$row['employee_code']] = $employee->id;

            $salary = $row['salary'];
            $payType = $salary['pay_type'] ?? 'wages';

            if ($payType === 'salary' && $staffGrade) {
                $gross = (float) ($salary['gross_salary'] ?? $this->legacyGrossTotal($salary));
                $amounts = $calculator->calculate($staffGrade, $gross);

                $structure = SalaryStructure::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'factory_id'      => $factory->id,
                        'salary_grade_id' => $staffGrade->id,
                        'gross_salary'    => $gross,
                        'payment_method'  => $salary['payment_method'] ?? 'bank',
                        'bank_account'    => $salary['bank_account'] ?? null,
                        'effective_from'  => $row['joining_date'] ?? null,
                        'is_active'       => true,
                    ]
                );

                $structure->syncLegacyFromHeads($amounts);
                $structure->save();
            } else {
                SalaryStructure::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'factory_id'        => $factory->id,
                        'salary_grade_id'   => null,
                        'gross_salary'      => 0,
                        'head_amounts'      => null,
                        'pay_type'          => 'wages',
                        'basic_salary'      => 0,
                        'daily_wage'        => $salary['daily_wage'] ?? 0,
                        'hra'               => $salary['hra'] ?? 0,
                        'medical'           => $salary['medical'] ?? 0,
                        'conveyance'        => $salary['conveyance'] ?? 0,
                        'other_allowance'   => $salary['other_allowance'] ?? 0,
                        'payment_method'    => $salary['payment_method'] ?? 'bank',
                        'bank_account'      => $salary['bank_account'] ?? null,
                        'effective_from'    => $row['joining_date'] ?? null,
                        'is_active'         => true,
                    ]
                );
            }
        }

        foreach ($data['employees'] as $row) {
            if (empty($row['reporting_to'])) {
                continue;
            }

            $employeeId = $employeeIds[$row['employee_code']] ?? null;
            $managerId = $employeeIds[$row['reporting_to']] ?? null;

            if ($employeeId && $managerId) {
                Employee::where('id', $employeeId)->update(['reporting_to_id' => $managerId]);
            }
        }

        $this->command?->info('Seeded ' . count($data['employees']) . " demo employees (SR-01 staff + wages workers) for {$factory->name}.");
    }

    /** @param array<string, mixed> $salary */
    private function legacyGrossTotal(array $salary): float
    {
        return round(
            (float) ($salary['basic_salary'] ?? 0)
            + (float) ($salary['hra'] ?? 0)
            + (float) ($salary['medical'] ?? 0)
            + (float) ($salary['conveyance'] ?? 0)
            + (float) ($salary['other_allowance'] ?? 0),
            2
        );
    }
}
