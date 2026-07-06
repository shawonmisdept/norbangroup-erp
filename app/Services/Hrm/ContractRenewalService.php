<?php

namespace App\Services\Hrm;

use App\Models\Hrm\ContractRenewal;
use App\Models\Hrm\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContractRenewalService
{
    public function submit(Employee $employee, array $data, User $user): ContractRenewal
    {
        if (! $employee->contract_end_date) {
            throw new \InvalidArgumentException('Employee has no contract end date on file.');
        }

        if (ContractRenewal::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->exists()) {
            throw new \InvalidArgumentException('A contract renewal is already pending for this employee.');
        }

        return ContractRenewal::create([
            'factory_id'         => $employee->factory_id,
            'employee_id'        => $employee->id,
            'previous_end_date'  => $employee->contract_end_date,
            'new_end_date'       => $data['new_end_date'],
            'status'             => 'pending',
            'notes'              => $data['notes'] ?? null,
            'created_by'         => $user->id,
        ]);
    }

    public function approve(ContractRenewal $renewal, User $user): ContractRenewal
    {
        if (! $renewal->isPending()) {
            throw new \InvalidArgumentException('Only pending renewals can be approved.');
        }

        return DB::transaction(function () use ($renewal, $user) {
            $employee = $renewal->employee()->lockForUpdate()->firstOrFail();
            $original = $employee->only(['contract_end_date']);

            $employee->update(['contract_end_date' => $renewal->new_end_date]);

            app(EmployeeServiceHistoryService::class)->recordChanges(
                $employee->fresh(),
                $original,
                $renewal->new_end_date->toDateString(),
            );

            $renewal->update([
                'status'      => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $renewal->fresh();
        });
    }

    public function reject(ContractRenewal $renewal, User $user, string $reason): ContractRenewal
    {
        if (! $renewal->isPending()) {
            throw new \InvalidArgumentException('Only pending renewals can be rejected.');
        }

        $renewal->update([
            'status'           => 'rejected',
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        return $renewal->fresh();
    }
}
