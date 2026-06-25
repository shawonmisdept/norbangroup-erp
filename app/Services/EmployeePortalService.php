<?php

namespace App\Services;

use App\Models\Hrm\Employee;
use App\Models\Hrm\EmployeePortalUser;
use Illuminate\Support\Str;

class EmployeePortalService
{
    public static function createForEmployee(Employee $employee, ?string $password = null): array
    {
        $plainPassword = $password ?? Str::password(10);

        $portalUser = EmployeePortalUser::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'password'  => $plainPassword,
                'is_active' => static::employeeEligibleForPortal($employee),
            ]
        );

        return [
            'portalUser'    => $portalUser,
            'plainPassword' => $plainPassword,
        ];
    }

    public static function resetPassword(Employee $employee, string $password): EmployeePortalUser
    {
        $portalUser = EmployeePortalUser::firstOrCreate(
            ['employee_id' => $employee->id],
            ['password' => $password, 'is_active' => true]
        );

        $portalUser->update([
            'password'  => $password,
            'is_active' => static::employeeEligibleForPortal($employee),
        ]);

        return $portalUser->fresh();
    }

    public static function syncPortalState(Employee $employee): void
    {
        $portalUser = $employee->portalUser;

        if (! $portalUser) {
            return;
        }

        $eligible = static::employeeEligibleForPortal($employee);

        if ($eligible && ! $portalUser->is_active) {
            $portalUser->update(['is_active' => true]);
        }

        if (! $eligible && ! in_array($employee->status, Employee::SEPARATED_STATUSES, true)) {
            $portalUser->update(['is_active' => false]);
        }
    }

    public static function employeeEligibleForPortal(Employee $employee): bool
    {
        return in_array($employee->status, ['active', 'probation'], true);
    }
}
