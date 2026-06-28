<?php

namespace App\Services\Hrm;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\EmployeeServiceHistory;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Support\Facades\Auth;

class EmployeeServiceHistoryService
{
    private const TRACKED = [
        'factory_id'          => ['event' => 'transfer', 'label' => 'Factory / Unit'],
        'department_id'       => ['event' => 'transfer', 'label' => 'Department'],
        'designation_id'      => ['event' => 'promotion', 'label' => 'Designation'],
        'line_id'             => ['event' => 'transfer', 'label' => 'Line / Section'],
        'building_id'         => ['event' => 'transfer', 'label' => 'Building'],
        'floor_id'            => ['event' => 'transfer', 'label' => 'Floor'],
        'shift_id'            => ['event' => 'transfer', 'label' => 'Shift'],
        'reporting_to_id'     => ['event' => 'reporting', 'label' => 'Reporting To'],
        'worker_category_id'  => ['event' => 'category', 'label' => 'Worker Category'],
        'employment_type_id'  => ['event' => 'employment', 'label' => 'Employment Type'],
        'status'              => ['event' => 'status', 'label' => 'Employment Status'],
        'confirmation_date'   => ['event' => 'confirmation', 'label' => 'Confirmation Date'],
        'probation_end_date'  => ['event' => 'probation', 'label' => 'Probation End Date'],
        'contract_end_date'   => ['event' => 'contract', 'label' => 'Contract End Date'],
    ];

    public function recordEnrollment(Employee $employee): void
    {
        $this->write($employee, 'enrolled', null, null, null, 'Employee enrolled — ' . $employee->employee_code);
    }

    public function recordSeparation(Employee $employee, \App\Models\Hrm\EmployeeSeparation $separation): void
    {
        $this->write(
            $employee,
            'separation',
            'status',
            null,
            $employee->statusLabel(),
            sprintf(
                '%s approved — last working day %s',
                $separation->typeLabel(),
                $separation->last_working_day->format('d M Y')
            )
        );
    }

    public function recordChanges(Employee $employee, array $original, ?string $effectiveDate = null): void
    {
        foreach (self::TRACKED as $field => $meta) {
            $old = $original[$field] ?? null;
            $new = $employee->{$field};

            if ($this->normalize($old) === $this->normalize($new)) {
                continue;
            }

            $oldLabel = $this->resolveLabel($field, $old);
            $newLabel = $this->resolveLabel($field, $new);

            $this->write(
                $employee,
                $meta['event'],
                $field,
                $oldLabel,
                $newLabel,
                "{$meta['label']} changed from \"{$oldLabel}\" to \"{$newLabel}\"",
                $effectiveDate
            );
        }
    }

    public function recordApprovedMovement(Employee $employee, EmployeePromotion $promotion, array $original): void
    {
        $effectiveDate = $promotion->effective_date->toDateString();
        $eventType = $promotion->movement_type === 'demotion' ? 'demotion' : 'promotion';

        foreach (self::TRACKED as $field => $meta) {
            $old = $original[$field] ?? null;
            $new = $employee->{$field};

            if ($this->normalize($old) === $this->normalize($new)) {
                continue;
            }

            $oldLabel = $this->resolveLabel($field, $old);
            $newLabel = $this->resolveLabel($field, $new);
            $fieldEvent = $field === 'designation_id' ? $eventType : $meta['event'];

            $this->write(
                $employee,
                $fieldEvent,
                $field,
                $oldLabel,
                $newLabel,
                ucfirst($promotion->movement_type) . " — {$meta['label']} changed from \"{$oldLabel}\" to \"{$newLabel}\"",
                $effectiveDate
            );
        }

        if ($promotion->apply_salary_change && $promotion->to_gross_salary !== null) {
            $fromGross = $promotion->from_gross_salary !== null
                ? number_format((float) $promotion->from_gross_salary, 2)
                : '—';
            $toGross = number_format((float) $promotion->to_gross_salary, 2);

            $this->write(
                $employee,
                'salary_revision',
                'gross_salary',
                $fromGross,
                $toGross,
                ucfirst($promotion->movement_type) . " — Gross salary revised from {$fromGross} to {$toGross}",
                $effectiveDate
            );
        }
    }

    public function recordPerformanceReview(Employee $employee, \App\Models\Hrm\PerformanceReview $review): void
    {
        $score = $review->overall_score !== null ? number_format((float) $review->overall_score, 1) . '%' : '—';

        $this->write(
            $employee,
            'performance',
            null,
            null,
            $score,
            sprintf(
                '%s review approved — overall score %s',
                $review->cycleTypeLabel(),
                $score
            ),
            $review->period_to->toDateString()
        );
    }

    private function write(
        Employee $employee,
        string $eventType,
        ?string $field,
        ?string $oldValue,
        ?string $newValue,
        string $description,
        ?string $effectiveDate = null,
    ): void {
        EmployeeServiceHistory::create([
            'employee_id'    => $employee->id,
            'factory_id'     => $employee->factory_id,
            'event_type'     => $eventType,
            'field_name'     => $field,
            'old_value'      => $oldValue,
            'new_value'      => $newValue,
            'description'    => $description,
            'recorded_by'    => Auth::id(),
            'effective_date' => $effectiveDate ?? now()->toDateString(),
        ]);
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    private function resolveLabel(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {
            'factory_id'         => Factory::find($value)?->name ?? (string) $value,
            'department_id'      => Department::find($value)?->name ?? (string) $value,
            'designation_id'     => Designation::find($value)?->name ?? (string) $value,
            'building_id'        => Building::find($value)?->name ?? (string) $value,
            'floor_id'           => Floor::find($value)?->name ?? (string) $value,
            'line_id'            => Line::find($value)?->name ?? (string) $value,
            'shift_id'           => Shift::find($value)?->name ?? (string) $value,
            'reporting_to_id'    => Employee::find($value)?->name ?? (string) $value,
            'worker_category_id' => WorkerCategory::find($value)?->name ?? (string) $value,
            'employment_type_id' => EmploymentType::find($value)?->name ?? (string) $value,
            'status'             => Employee::STATUSES[$value] ?? (string) $value,
            'confirmation_date', 'probation_end_date', 'contract_end_date' => $value instanceof \DateTimeInterface
                ? $value->format('d M Y')
                : (string) $value,
            default => (string) $value,
        };
    }
}
