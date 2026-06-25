<?php

namespace App\Services\Hrm;

use App\Models\Hrm\DisciplinaryRecord;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisciplinaryService
{
    public function __construct(private EmployeeServiceHistoryService $serviceHistory) {}

    public function record(Employee $employee, array $data, User $recorder): DisciplinaryRecord
    {
        if (! in_array($employee->status, ['active', 'probation', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Disciplinary action cannot be recorded for separated employees.',
            ]);
        }

        return DB::transaction(function () use ($employee, $data, $recorder) {
            $record = DisciplinaryRecord::create([
                'factory_id'      => $employee->factory_id,
                'employee_id'     => $employee->id,
                'action_type'     => $data['action_type'],
                'incident_date'   => $data['incident_date'],
                'description'     => $data['description'],
                'action_taken'    => $data['action_taken'] ?? null,
                'suspension_from' => $data['suspension_from'] ?? null,
                'suspension_to'   => $data['suspension_to'] ?? null,
                'status'          => 'open',
                'recorded_by'     => $recorder->id,
            ]);

            if ($data['action_type'] === 'suspension' && ! empty($data['suspension_from'])) {
                $from = \Carbon\Carbon::parse($data['suspension_from']);
                if ($from->lte(now()->startOfDay())) {
                    $original = $employee->getOriginal();
                    $employee->update(['status' => 'suspended']);
                    $this->serviceHistory->recordChanges($employee->fresh(), $original);
                }
            }

            return $record->load(['employee', 'recorder']);
        });
    }

    public function close(DisciplinaryRecord $record): DisciplinaryRecord
    {
        $record->update(['status' => 'closed']);

        if ($record->action_type === 'suspension' && $record->employee?->status === 'suspended') {
            $hasActiveSuspension = DisciplinaryRecord::query()
                ->where('employee_id', $record->employee_id)
                ->where('action_type', 'suspension')
                ->where('status', 'open')
                ->where('id', '!=', $record->id)
                ->exists();

            if (! $hasActiveSuspension) {
                $employee = $record->employee;
                $original = $employee->getOriginal();
                $employee->update(['status' => 'active']);
                $this->serviceHistory->recordChanges($employee->fresh(), $original);
            }
        }

        return $record->fresh();
    }
}
