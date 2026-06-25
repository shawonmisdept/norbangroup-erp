<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeePromotionService
{
    public function __construct(
        private EmployeeServiceHistoryService $history,
        private SalaryFormulaCalculator $salaryCalculator,
    ) {}

    public function submit(Employee $employee, array $data, User $user): EmployeePromotion
    {
        if ($employee->pendingPromotion()->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => 'This employee already has a pending promotion/demotion request.',
            ]);
        }

        if (! in_array($employee->status, ['active', 'probation'], true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Only active or probation employees can be promoted or demoted.',
            ]);
        }

        $employee->loadMissing(['salaryStructure']);

        $fromDesignationId = $employee->designation_id;
        $toDesignationId = (int) $data['to_designation_id'];

        if ($fromDesignationId === $toDesignationId
            && empty($data['to_department_id'])
            && empty($data['to_worker_category_id'])
            && empty($data['to_reporting_to_id'])
            && empty($data['apply_salary_change'])) {
            throw ValidationException::withMessages([
                'to_designation_id' => 'At least one field must change (designation, department, category, reporting, or salary).',
            ]);
        }

        $applySalary = ! empty($data['apply_salary_change']);
        $structure = $employee->salaryStructure;

        return EmployeePromotion::create([
            'factory_id'              => $employee->factory_id,
            'employee_id'             => $employee->id,
            'movement_type'           => $data['movement_type'],
            'status'                  => 'pending',
            'from_designation_id'     => $fromDesignationId,
            'to_designation_id'       => $toDesignationId,
            'from_department_id'      => $employee->department_id,
            'to_department_id'        => $data['to_department_id'] ?? null,
            'from_worker_category_id' => $employee->worker_category_id,
            'to_worker_category_id'   => $data['to_worker_category_id'] ?? null,
            'from_reporting_to_id'    => $employee->reporting_to_id,
            'to_reporting_to_id'      => $data['to_reporting_to_id'] ?? null,
            'from_salary_grade_id'    => $structure?->salary_grade_id,
            'to_salary_grade_id'      => $applySalary ? ($data['to_salary_grade_id'] ?? null) : null,
            'from_gross_salary'       => $structure?->gross_salary,
            'to_gross_salary'         => $applySalary ? ($data['to_gross_salary'] ?? null) : null,
            'apply_salary_change'     => $applySalary,
            'effective_date'          => $data['effective_date'],
            'reason'                  => $data['reason'] ?? null,
            'remarks'                 => $data['remarks'] ?? null,
            'created_by'              => $user->id,
        ]);
    }

    public function approve(EmployeePromotion $promotion, User $user): Employee
    {
        if (! $promotion->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be approved.',
            ]);
        }

        return DB::transaction(function () use ($promotion, $user) {
            $employee = $promotion->employee()->lockForUpdate()->firstOrFail();
            $original = $employee->getAttributes();

            $updates = array_filter([
                'designation_id'     => $promotion->to_designation_id,
                'department_id'      => $promotion->to_department_id,
                'worker_category_id' => $promotion->to_worker_category_id,
                'reporting_to_id'    => $promotion->to_reporting_to_id,
            ], fn ($v) => $v !== null);

            if ($updates !== []) {
                $employee->update($updates);
            }

            if ($promotion->apply_salary_change && $promotion->to_salary_grade_id && $promotion->to_gross_salary !== null) {
                $this->applySalaryChange($employee, $promotion);
            }

            $employee->refresh();
            $this->history->recordApprovedMovement($employee, $promotion, $original);

            $promotion->update([
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $employee;
        });
    }

    public function reject(EmployeePromotion $promotion, User $user, string $reason): void
    {
        if (! $promotion->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be rejected.',
            ]);
        }

        $promotion->update([
            'status'           => 'rejected',
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(EmployeePromotion $promotion): void
    {
        if (! $promotion->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be cancelled.',
            ]);
        }

        $promotion->update(['status' => 'cancelled']);
    }

    /** @return array<int, string> */
    public function movementTypes(): array
    {
        return EmployeePromotion::MOVEMENT_TYPES;
    }

    private function applySalaryChange(Employee $employee, EmployeePromotion $promotion): void
    {
        $grade = SalaryGrade::findOrFail($promotion->to_salary_grade_id);
        $gross = (float) $promotion->to_gross_salary;
        $amounts = $this->salaryCalculator->calculate($grade, $gross);

        $structure = SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'factory_id'      => $employee->factory_id,
                'salary_grade_id' => $grade->id,
                'gross_salary'    => $gross,
                'effective_from'  => $promotion->effective_date,
                'is_active'       => true,
            ]
        );

        $structure->syncLegacyFromHeads($amounts);
        $structure->save();
    }
}
