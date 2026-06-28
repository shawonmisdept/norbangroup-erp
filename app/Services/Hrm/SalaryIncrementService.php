<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\Hrm\SalaryIncrementRule;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryIncrementService
{
    public function __construct(
        private readonly SalaryFormulaCalculator $calculator
    ) {}

    public function employeeTenureMonths(Employee $employee): int
    {
        if (! $employee->joining_date) {
            return 0;
        }

        return (int) $employee->joining_date->diffInMonths(now());
    }

    public function eligibleForRule(Employee $employee, SalaryIncrementRule $rule): bool
    {
        if ($employee->factory_id !== $rule->factory_id) {
            return false;
        }

        if ($rule->salary_grade_id && $employee->salaryStructure?->salary_grade_id !== $rule->salary_grade_id) {
            return false;
        }

        if ($this->employeeTenureMonths($employee) < $rule->min_tenure_months) {
            return false;
        }

        $structure = $employee->salaryStructure;

        return $structure
            && $structure->is_active
            && $structure->pay_type === 'salary'
            && (float) $structure->gross_salary > 0
            && $structure->salary_grade_id;
    }

    /**
     * @param  Collection<int, Employee>|array<int, Employee>  $employees
     * @return array{applied: int, skipped: int, errors: list<string>}
     */
    public function applyRule(SalaryIncrementRule $rule, Collection|array $employees, User $user): array
    {
        $applied = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rule, $employees, $user, &$applied, &$skipped, &$errors) {
            foreach ($employees as $employee) {
                $employee->loadMissing('salaryStructure.salaryGrade');

                if (! $this->eligibleForRule($employee, $rule)) {
                    $skipped++;

                    continue;
                }

                try {
                    $this->applyToEmployee($employee, $rule, $user);
                    $applied++;
                } catch (\Throwable $e) {
                    $errors[] = "{$employee->employee_code}: {$e->getMessage()}";
                    $skipped++;
                }
            }
        });

        return compact('applied', 'skipped', 'errors');
    }

    public function applyToEmployee(Employee $employee, SalaryIncrementRule $rule, User $user): SalaryStructure
    {
        $structure = $employee->salaryStructure;

        if (! $structure || ! $structure->salary_grade_id) {
            throw ValidationException::withMessages([
                'employee' => 'Employee has no grade-based salary structure.',
            ]);
        }

        $grade = $structure->salaryGrade;

        if (! $grade) {
            $grade = $structure->salaryGrade()->first();
        }

        if (! $grade) {
            throw ValidationException::withMessages([
                'employee' => 'Salary grade not found.',
            ]);
        }

        $previousGross = (float) $structure->gross_salary;
        $newGross = $rule->applyToGross($previousGross);

        if ($newGross <= $previousGross) {
            throw ValidationException::withMessages([
                'employee' => 'Increment would not increase gross salary.',
            ]);
        }

        $overrides = $this->existingFixedOverrides($structure);
        $amounts = $this->calculator->calculate($grade, $newGross, $overrides);

        $structure->gross_salary = $newGross;
        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        SalaryIncrementLog::create([
            'factory_id'               => $employee->factory_id,
            'salary_increment_rule_id' => $rule->id,
            'employee_id'              => $employee->id,
            'previous_gross'           => $previousGross,
            'new_gross'                => $newGross,
            'applied_by'               => $user->id,
            'applied_at'               => now(),
        ]);

        return $structure->fresh();
    }

    public function applyDirectGross(
        Employee $employee,
        float $newGross,
        User $user,
        ?int $performanceReviewId = null,
        ?int $performanceIncrementRunId = null,
    ): SalaryStructure {
        $employee->loadMissing('salaryStructure.salaryGrade');
        $structure = $employee->salaryStructure;

        if (! $structure || $structure->pay_type !== 'salary' || ! $structure->salary_grade_id) {
            throw ValidationException::withMessages([
                'employee' => 'Employee must have an active grade-based salary structure.',
            ]);
        }

        if ($newGross <= 0) {
            throw ValidationException::withMessages([
                'employee' => 'New gross must be greater than zero.',
            ]);
        }

        $grade = $structure->salaryGrade;

        if (! $grade) {
            throw ValidationException::withMessages([
                'employee' => 'Salary grade not found.',
            ]);
        }

        $previousGross = (float) $structure->gross_salary;
        $overrides = $this->existingFixedOverrides($structure);
        $amounts = $this->calculator->calculate($grade, $newGross, $overrides);

        $structure->gross_salary = $newGross;
        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        SalaryIncrementLog::create([
            'factory_id'                   => $employee->factory_id,
            'salary_increment_rule_id'     => null,
            'employee_id'                  => $employee->id,
            'performance_review_id'        => $performanceReviewId,
            'performance_increment_run_id' => $performanceIncrementRunId,
            'previous_gross'               => $previousGross,
            'new_gross'                    => $newGross,
            'applied_by'                   => $user->id,
            'applied_at'                   => now(),
        ]);

        return $structure->fresh();
    }

    /** @return array<string, float> */
    private function existingFixedOverrides(SalaryStructure $structure): array
    {
        if (! $structure->head_amounts || ! $structure->salary_grade_id) {
            return [];
        }

        $overrides = [];
        $details = \App\Models\Hrm\SalaryGradeDetail::query()
            ->where('salary_grade_id', $structure->salary_grade_id)
            ->where('detail_type', 'F')
            ->where('is_fixed', true)
            ->with('salaryHead')
            ->get();

        foreach ($details as $detail) {
            $code = strtoupper($detail->salaryHead?->code ?? '');

            if ($code && isset($structure->head_amounts[$code])) {
                $overrides[$code] = (float) $structure->head_amounts[$code];
            }
        }

        return $overrides;
    }
}
