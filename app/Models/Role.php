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

        if (preg_match('/^hrm\.(hrm-[a-z0-9-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.masters.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.finance\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.finance.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.compliance\.([a-z-]+)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.compliance.' . $matches[2], $permissions, true);
        }

        if (preg_match('/^hrm\.employees\.(promotion|separation|letters|discipline)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.employees.' . $matches[2], $permissions, true);
        }

        if ($permission === 'hrm.employees.promotion.approve' || $permission === 'hrm.employees.separation.approve') {
            return in_array('hrm.employees.manage', $permissions, true);
        }

        if ($permission === 'hrm.performance.rate' || $permission === 'hrm.performance.approve') {
            return in_array('hrm.performance.manage', $permissions, true);
        }

        if (preg_match('/^hrm\.performance\.(bonus|increment)\.(view|manage)$/', $permission, $matches)) {
            return in_array('hrm.performance.' . $matches[2], $permissions, true);
        }

        if ($permission === 'hrm.dashboard.view') {
            foreach (array_keys(config('hrm.modules', [])) as $module) {
                if (in_array("hrm.{$module}.view", $permissions, true)) {
                    return true;
                }
            }

            $parentViews = [
                'hrm.masters.view', 'hrm.employees.view', 'hrm.attendance.view', 'hrm.attendance.sync',
                'hrm.leave.view', 'hrm.salary.view', 'hrm.payroll.view', 'hrm.compliance.view',
                'hrm.finance.view', 'hrm.rmg.view', 'hrm.performance.view', 'hrm.performance.bonus.view',
                'hrm.performance.increment.view', 'hrm.recruitment.postings.view', 'hrm.recruitment.applications.view',
            ];

            foreach ($parentViews as $parent) {
                if (in_array($parent, $permissions, true)) {
                    return true;
                }
            }
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
            config('hrm.permissions.payroll', []),
            config('hrm.permissions.performance', [])
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

        foreach (config('hrm.performance_submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['Performance — ' . $sub['label']] = $items;
            }
        }

        $groups['TMS — All Modules'] = array_merge(
            config('tms.permissions.global', []),
            config('tms.permissions.settings', []),
            config('tms.permissions.vehicles', []),
            config('tms.permissions.drivers', []),
            config('tms.permissions.requests', []),
            config('tms.permissions.trips', []),
            config('tms.permissions.fuel', []),
            config('tms.permissions.reports', []),
            config('tms.permissions.overtime', []),
            config('tms.permissions.rental_vendors', []),
            config('tms.permissions.rental_charges', []),
            config('tms.permissions.rental_drivers', []),
            config('tms.permissions.maintenance', [])
        );

        foreach (config('tms.submodules', []) as $key => $sub) {
            $items = [];
            if (! empty($sub['permission'])) {
                $items[$sub['permission']] = 'View ' . $sub['label'];
            }
            if (! empty($sub['manage'])) {
                $items[$sub['manage']] = 'Manage ' . $sub['label'];
            }
            if ($items !== []) {
                $groups['TMS — ' . $sub['label']] = $items;
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

    public function permissionCount(): int
    {
        return count($this->permissions ?? []);
    }

    /** @return list<string> */
    public function moduleAccessAreas(): array
    {
        $areas = [];

        foreach ($this->permissions ?? [] as $permission) {
            $label = static::permissionAreaLabel($permission);

            if ($label !== null) {
                $areas[$label] = true;
            }
        }

        $order = ['System Admin', 'Operations', 'Master Data', 'HRM', 'TMS'];
        $result = array_keys($areas);

        usort($result, function (string $a, string $b) use ($order): int {
            $indexA = array_search($a, $order, true);
            $indexB = array_search($b, $order, true);

            return ($indexA === false ? 99 : $indexA) <=> ($indexB === false ? 99 : $indexB);
        });

        return $result;
    }

    public static function permissionAreaLabel(string $permission): ?string
    {
        if (in_array($permission, ['users.manage', 'roles.manage', 'settings.manage'], true)) {
            return 'System Admin';
        }

        if (str_starts_with($permission, 'orders.')) {
            return 'Operations';
        }

        if (str_starts_with($permission, 'masters.')) {
            return 'Master Data';
        }

        if (str_starts_with($permission, 'hrm.')) {
            return 'HRM';
        }

        if (str_starts_with($permission, 'tms.')) {
            return 'TMS';
        }

        return null;
    }

    public static function moduleAreaBadgeClass(string $area): string
    {
        return match ($area) {
            'System Admin' => 'bg-brand/10 text-brand',
            'Operations'   => 'bg-blue-50 text-blue-700',
            'Master Data'  => 'bg-gray-100 text-gray-600',
            'HRM'          => 'bg-emerald-50 text-emerald-700',
            'TMS'          => 'bg-violet-50 text-violet-700',
            default        => 'bg-gray-100 text-gray-600',
        };
    }
}
