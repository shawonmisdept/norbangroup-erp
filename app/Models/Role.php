<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        if (preg_match('/^masters\.([a-z0-9-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('masters.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.([a-z0-9-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.masters.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.leave\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.leave.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.attendance\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            if (in_array('hrm.attendance.' . $matches[2], $permissions, true)) {
                return true;
            }

            if ($matches[1] === 'late-acceptance' && $matches[2] === 'view') {
                return in_array('hrm.attendance.approve', $permissions, true);
            }

            if ($matches[1] === 'late-acceptance' && $matches[2] === 'manage') {
                return in_array('hrm.attendance.approve', $permissions, true);
            }

            return false;
        }

        if ($permission === 'hrm.attendance.approve') {
            return in_array('hrm.attendance.late-acceptance.manage', $permissions, true);
        }

        if (preg_match('/^hrm\.salary\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.salary.' . $matches[2], $permissions, true)
                || in_array('hrm.payroll.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.rmg\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.rmg.' . $matches[2], $permissions, true);
        }

        $legacyPayroll = [
            'hrm.payroll.view'    => 'hrm.salary.view',
            'hrm.payroll.manage'  => 'hrm.salary.manage',
            'hrm.payroll.approve' => 'hrm.salary.approve',
        ];

        if (isset($legacyPayroll[$permission]) && in_array($legacyPayroll[$permission], $permissions, true)) {
            return true;
        }

        $legacySalary = [
            'hrm.salary.view'    => 'hrm.payroll.view',
            'hrm.salary.manage'  => 'hrm.payroll.manage',
            'hrm.salary.approve' => 'hrm.payroll.approve',
        ];

        if (isset($legacySalary[$permission]) && in_array($legacySalary[$permission], $permissions, true)) {
            return true;
        }

        return false;
    }

    public static function permissionGroups(): array
    {
        $groups = [
            'Operations' => config('permissions.groups.Operations', []),
        ];

        $groups['Master Data — All Modules'] = config('permissions.master_global', []);

        foreach (config('masters.groups') as $groupName => $modules) {
            $items = [];

            foreach ($modules as $moduleKey) {
                $mod = config("masters.modules.{$moduleKey}");

                if (! $mod) {
                    continue;
                }

                $items["masters.{$moduleKey}.view"] = 'View ' . $mod['label_plural'];
                $items["masters.{$moduleKey}.manage"] = 'Manage ' . $mod['label_plural'];
            }

            if ($items !== []) {
                $groups[$groupName] = $items;
            }
        }

        $groups['HRM — All Modules'] = array_merge(
            config('hrm.permissions.global', []),
            config('hrm.permissions.employees', []),
            config('hrm.permissions.recruitment', []),
            config('hrm.permissions.attendance', []),
            config('hrm.permissions.leave', []),
            config('hrm.permissions.salary', []),
            config('hrm.permissions.compliance', []),
            config('hrm.permissions.finance', []),
            config('hrm.permissions.rmg', []),
            config('hrm.permissions.payroll', [])
        );

        foreach (config('hrm.leave_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Leave — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.salary_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Salary — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.attendance_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Attendance — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.compliance_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Compliance — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.finance_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Finance — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.rmg_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['RMG — ' . $sub['label']] = $items;
            }
        }

        foreach (config('hrm.groups') as $groupName => $modules) {
            $items = [];

            foreach ($modules as $moduleKey) {
                $mod = config("hrm.modules.{$moduleKey}");

                if (! $mod) {
                    continue;
                }

                $items["hrm.{$moduleKey}.view"] = 'View ' . $mod['label_plural'];
                $items["hrm.{$moduleKey}.manage"] = 'Manage ' . $mod['label_plural'];
            }

            if ($items !== []) {
                $groups['HRM — ' . $groupName] = $items;
            }
        }

        $groups['Administration'] = config('permissions.groups.Administration', []);

        return $groups;
    }

    public static function permissionLabel(string $key): string
    {
        foreach (static::permissionGroups() as $permissions) {
            if (isset($permissions[$key])) {
                return $permissions[$key];
            }
        }

        return $key;
    }

    public static function permissionOptions(): array
    {
        $options = [];

        foreach (static::permissionGroups() as $permissions) {
            $options = array_merge($options, $permissions);
        }

        return $options;
    }
}
