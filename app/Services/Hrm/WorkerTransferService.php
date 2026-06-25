<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\WorkerTransfer;
use Illuminate\Support\Facades\DB;

class WorkerTransferService
{
    public function __construct(private EmployeeServiceHistoryService $history) {}

    public function approve(WorkerTransfer $transfer, int $userId): Employee
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $employee = $transfer->employee()->firstOrFail();
            $original = $employee->getAttributes();

            $employee->update(array_filter([
                'factory_id'  => $transfer->to_factory_id,
                'line_id'     => $transfer->to_line_id,
                'floor_id'    => $transfer->to_floor_id,
                'building_id' => $transfer->to_building_id,
            ], fn ($v) => $v !== null));

            $employee->refresh();
            $this->history->recordChanges($employee, $original);

            $transfer->update([
                'status'      => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            return $employee;
        });
    }
}
