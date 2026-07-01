<?php

namespace App\Support;

class RolePermissionCatalog
{
    /** @return list<string> */
    public static function allPermissionKeys(): array
    {
        $permissions = [];

        foreach (config('permissions.groups', []) as $group) {
            $permissions = array_merge($permissions, array_keys($group));
        }

        $permissions = array_merge($permissions, array_keys(config('permissions.master_global', [])));

        foreach (config('masters.groups', []) as $modules) {
            foreach ($modules as $moduleKey) {
                if (! config("masters.modules.{$moduleKey}")) {
                    continue;
                }

                $permissions[] = "masters.{$moduleKey}.view";
                $permissions[] = "masters.{$moduleKey}.manage";
            }
        }

        foreach (config('hrm.permissions', []) as $group) {
            $permissions = array_merge($permissions, array_keys($group));
        }

        foreach (self::hrmSubmoduleConfigKeys() as $configKey) {
            $permissions = array_merge($permissions, self::permissionsFromSubmoduleConfig(config($configKey, [])));
        }

        foreach (config('hrm.modules', []) as $moduleKey => $module) {
            if (! $module) {
                continue;
            }

            $permissions[] = "hrm.{$moduleKey}.view";
            $permissions[] = "hrm.{$moduleKey}.manage";
        }

        foreach (config('tms.permissions', []) as $group) {
            $permissions = array_merge($permissions, array_keys($group));
        }

        $permissions = array_merge($permissions, self::permissionsFromSubmoduleConfig(config('tms.submodules', [])));

        $permissions = array_merge($permissions, self::recruitmentPermissions());

        return array_values(array_unique($permissions));
    }

    /** @return list<string> */
    public static function administratorPermissions(): array
    {
        return self::allPermissionKeys();
    }

    /** @return list<string> */
    public static function transportAuthorityPermissions(): array
    {
        return array_values(array_unique([
            'tms.dashboard.view',
            'tms.settings.view',
            'tms.settings.manage',
            'tms.requests.view',
            'tms.requests.approve',
            'tms.vehicles.view',
            'tms.vehicles.manage',
            'tms.drivers.view',
            'tms.drivers.manage',
            'tms.trips.view',
            'tms.trips.manage',
            'tms.fuel.view',
            'tms.fuel.manage',
            'tms.reports.view',
            'tms.overtime.manage',
            'tms.rental_vendors.view',
            'tms.rental_vendors.manage',
            'tms.rental_charges.manage',
            'tms.rental_drivers.view',
            'tms.rental_drivers.manage',
            'tms.maintenance.view',
            'tms.maintenance.manage',
        ]));
    }

    /** @return list<string> */
    public static function hrManagerPermissions(): array
    {
        $manager = array_merge(
            self::managerPermissions(),
            [
                'hrm.employees.manage',
                'hrm.leave.manage',
                'hrm.leave.approve',
                'hrm.employees.separation.view',
                'hrm.employees.separation.manage',
                'hrm.employees.separation.approve',
                'hrm.employees.promotion.view',
                'hrm.employees.promotion.manage',
                'hrm.employees.promotion.approve',
                'hrm.recruitment.postings.view',
                'hrm.recruitment.postings.manage',
                'hrm.recruitment.applications.view',
                'hrm.recruitment.applications.manage',
                'hrm.recruitment.applications.convert',
                'hrm.performance.approve',
            ]
        );

        return array_values(array_unique($manager));
    }

    /** @return list<string> */
    public static function managerPermissions(): array
    {
        return array_values(array_unique([
            'orders.view',
            'orders.update',
            'orders.download',
            'masters.view',
            'hrm.masters.view',
            'hrm.employees.view',
            'hrm.attendance.view',
            'hrm.attendance.sync',
            'hrm.attendance.policy.view',
            'hrm.attendance.late-acceptance.view',
            'hrm.attendance.approve',
            'hrm.attendance.manual-punch.view',
            'hrm.attendance.gate-points.view',
            'hrm.leave.view',
            'hrm.leave.maternity-transactions.view',
            'hrm.salary.view',
            'hrm.compliance.view',
            'hrm.finance.view',
            'hrm.finance.settlement.view',
            'hrm.performance.view',
            'hrm.performance.rate',
            'hrm.performance.bonus.view',
            'hrm.performance.increment.view',
            'hrm.recruitment.postings.view',
            'hrm.recruitment.applications.view',
            'hrm.employees.separation.view',
            'hrm.employees.promotion.view',
            'hrm.employees.letters.view',
            'hrm.employees.discipline.view',
        ]));
    }

    /** @return list<string> */
    public static function viewerPermissions(): array
    {
        return ['orders.view', 'orders.download'];
    }

    /** @return list<string> */
    public static function recruitmentPermissions(): array
    {
        return array_keys(config('hrm.permissions.recruitment', []));
    }

    /** @return list<string> */
    private static function hrmSubmoduleConfigKeys(): array
    {
        return [
            'hrm.leave_submodules',
            'hrm.salary_submodules',
            'hrm.attendance_submodules',
            'hrm.compliance_submodules',
            'hrm.finance_submodules',
            'hrm.rmg_submodules',
            'hrm.performance_submodules',
            'hrm.employee_submodules',
            'hrm.recruitment_submodules',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $submodules
     * @return list<string>
     */
    private static function permissionsFromSubmoduleConfig(array $submodules): array
    {
        $permissions = [];

        foreach ($submodules as $sub) {
            if (($sub['status'] ?? 'active') !== 'active') {
                continue;
            }

            if (! empty($sub['permission'])) {
                $permissions[] = $sub['permission'];
            }

            if (! empty($sub['manage'])) {
                $permissions[] = $sub['manage'];
            }

            if (! empty($sub['also'])) {
                $permissions[] = $sub['also'];
            }
        }

        return $permissions;
    }
}
