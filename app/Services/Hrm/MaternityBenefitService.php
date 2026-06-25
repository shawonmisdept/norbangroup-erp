<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\MaternityRule;
use App\Models\Hrm\MaternityTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaternityBenefitService
{
    public function __construct(
        private LeaveService $leaveService,
    ) {}

    public function create(Employee $employee, array $data, User $user): MaternityTransaction
    {
        $employee->loadMissing('workerCategory');

        if ($employee->gender !== 'female') {
            throw ValidationException::withMessages([
                'employee_id' => 'Maternity benefit applies to female employees only.',
            ]);
        }

        $rule = MaternityRule::query()
            ->where('factory_id', $employee->factory_id)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            throw ValidationException::withMessages([
                'factory_id' => 'No active maternity rule configured for this factory.',
            ]);
        }

        if ($employee->joining_date) {
            $serviceDays = Carbon::parse($employee->joining_date)->diffInDays(now());

            if ($serviceDays < $rule->min_service_days) {
                throw ValidationException::withMessages([
                    'employee_id' => "Minimum {$rule->min_service_days} days of service required (current: {$serviceDays}).",
                ]);
            }
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }

        return DB::transaction(function () use ($employee, $data, $user, $rule, $startDate, $endDate) {
            $leaveType = LeaveType::query()
                ->where('is_active', true)
                ->where('name', 'like', '%Maternity%')
                ->first();

            $leaveApplication = null;

            if ($leaveType) {
                $this->leaveService->ensureEmployeeBalances($employee, (int) $startDate->year);

                $leaveApplication = $this->leaveService->recordBulkEntry($employee, [
                    'leave_type_id' => $leaveType->id,
                    'start_date'    => $startDate->toDateString(),
                    'end_date'      => $endDate->toDateString(),
                    'reason'        => 'Maternity benefit — auto-created from maternity transaction',
                ], $user);
            }

            return MaternityTransaction::create([
                'factory_id'             => $employee->factory_id,
                'employee_id'            => $employee->id,
                'leave_application_id'   => $leaveApplication?->id,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'start_date'             => $startDate,
                'end_date'               => $endDate,
                'paid_weeks'             => (int) ($data['paid_weeks'] ?? $rule->paid_weeks),
                'unpaid_weeks'           => (int) ($data['unpaid_weeks'] ?? $rule->unpaid_weeks),
                'status'                 => 'active',
                'notes'                  => $data['notes'] ?? null,
                'created_by'             => $user->id,
            ]);
        });
    }
}
