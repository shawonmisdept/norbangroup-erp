<?php

namespace App\Support;

class HeadOfficeRolePermissionCatalog
{
    /** @return list<string> */
    public static function permissionsFor(string $roleKey): array
    {
        $roleKey = trim($roleKey);

        if ($roleKey === '' || $roleKey === 'No Need') {
            return [];
        }

        $dash = strpos($roleKey, '-');

        if ($dash === false) {
            return self::filterValid(['orders.view']);
        }

        $departmentPrefix = substr($roleKey, 0, $dash);
        $designationLabel = substr($roleKey, $dash + 1);

        $overrideKey = $departmentPrefix . '|' . self::normalizeDesignation($designationLabel);
        $overrides = config('head_office_permissions.designation_overrides', []);
        $override = $overrides[$overrideKey] ?? null;

        if (is_array($override)) {
            $modules = $override['modules'] ?? [];
            $access = (string) ($override['access'] ?? 'view');

            return self::filterValid(self::expandModules($modules, $access));
        }

        $modules = config("head_office_permissions.department_modules.{$departmentPrefix}", []);
        $designationKey = self::normalizeDesignation($designationLabel);
        $access = config("head_office_permissions.designation_levels.{$designationKey}", 'view');

        if ($modules === []) {
            return self::filterValid(['orders.view', 'orders.download']);
        }

        return self::filterValid(self::expandModules($modules, $access));
    }

    /**
     * @param  list<string>  $modules
     * @return list<string>
     */
    private static function expandModules(array $modules, string $accessLevel): array
    {
        $permissions = [];

        foreach ($modules as $module) {
            $permissions = array_merge($permissions, self::modulePermissions($module, $accessLevel));
        }

        return array_values(array_unique($permissions));
    }

    /** @return list<string> */
    private static function modulePermissions(string $module, string $accessLevel): array
    {
        $actions = config("head_office_permissions.access_levels.{$accessLevel}", ['view']);

        return match ($module) {
            'orders' => self::ordersPermissions($actions),
            'masters' => self::mastersPermissions($actions),
            'hrm.dashboard' => in_array('view', $actions, true) ? ['hrm.dashboard.view'] : [],
            'admin.settings' => in_array('manage', $actions, true) || in_array('approve', $actions, true)
                ? ['settings.manage'] : [],
            'admin.users' => in_array('manage', $actions, true) || in_array('lead', $actions, true) || in_array('approve', $actions, true)
                ? ['users.manage'] : [],
            'admin.roles' => self::rolesPermissions($actions),
            'hrm.masters' => self::hrmModulePermissions('hrm.masters', $actions, approve: false),
            'hrm.employees' => self::hrmModulePermissions('hrm.employees', $actions, approve: self::accessAtLeast($accessLevel, 'lead'), extraApprove: ['hrm.employees.separation.approve', 'hrm.employees.promotion.approve']),
            'hrm.leave' => self::hrmModulePermissions('hrm.leave', $actions, approve: self::accessAtLeast($accessLevel, 'lead')),
            'hrm.attendance' => self::hrmModulePermissions('hrm.attendance', $actions, approve: self::accessAtLeast($accessLevel, 'operate'), extraApprove: ['hrm.attendance.approve'], extraManage: ['hrm.attendance.sync']),
            'hrm.recruitment' => self::hrmRecruitmentPermissions($actions),
            'hrm.compliance' => self::hrmModulePermissions('hrm.compliance', $actions, approve: false),
            'hrm.performance' => self::hrmPerformancePermissions($actions),
            'hrm.finance' => self::hrmModulePermissions('hrm.finance', $actions, approve: false),
            'hrm.salary' => self::hrmModulePermissions('hrm.salary', $actions, approve: self::accessAtLeast($accessLevel, 'full')),
            'hrm.rmg' => self::hrmModulePermissions('hrm.rmg', $actions, approve: false),
            'tms' => self::tmsPermissions($actions),
            'tms.accounts' => self::tmsAccountsPermissions($actions),
            default => str_starts_with($module, 'hrm.')
                ? self::hrmModulePermissions($module, $actions, approve: false)
                : [],
        };
    }

    /** @param  list<string>  $actions
     * @param  list<string>  $extraApprove
     * @param  list<string>  $extraManage
     * @return list<string>
     */
    private static function hrmModulePermissions(
        string $module,
        array $actions,
        bool $approve,
        array $extraApprove = [],
        array $extraManage = []
    ): array {
        $permissions = [];

        if (in_array('view', $actions, true) || in_array('operate', $actions, true) || in_array('manage', $actions, true)) {
            $permissions[] = "{$module}.view";
        }

        if (in_array('manage', $actions, true) || in_array('operate', $actions, true)) {
            $permissions[] = "{$module}.manage";
            $permissions = array_merge($permissions, $extraManage);
        }

        if ($approve || in_array('approve', $actions, true)) {
            $permissions[] = "{$module}.approve";
            $permissions = array_merge($permissions, $extraApprove);
        }

        return $permissions;
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function hrmRecruitmentPermissions(array $actions): array
    {
        $permissions = [];

        if (self::hasViewAccess($actions)) {
            $permissions[] = 'hrm.recruitment.postings.view';
            $permissions[] = 'hrm.recruitment.applications.view';
        }

        if (in_array('manage', $actions, true) || in_array('operate', $actions, true)) {
            $permissions[] = 'hrm.recruitment.postings.manage';
            $permissions[] = 'hrm.recruitment.applications.manage';
        }

        if (in_array('approve', $actions, true) || in_array('lead', $actions, true)) {
            $permissions[] = 'hrm.recruitment.postings.approve';
            $permissions[] = 'hrm.recruitment.applications.convert';
        }

        return $permissions;
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function tmsPermissions(array $actions): array
    {
        if (in_array('manage', $actions, true)
            || in_array('operate', $actions, true)
            || in_array('approve', $actions, true)) {
            return RolePermissionCatalog::tmsOperationalPermissions();
        }

        if (self::hasViewAccess($actions)) {
            return RolePermissionCatalog::tmsViewPermissions();
        }

        return [];
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function tmsAccountsPermissions(array $actions): array
    {
        $permissions = [];

        if (self::hasViewAccess($actions)) {
            $permissions = [
                'tms.dashboard.view',
                'tms.maintenance.view',
                'tms.reports.view',
            ];
        }

        if (in_array('manage', $actions, true)
            || in_array('operate', $actions, true)
            || in_array('approve', $actions, true)) {
            $permissions[] = 'tms.maintenance.manage';
        }

        return array_values(array_unique($permissions));
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function hrmPerformancePermissions(array $actions): array
    {
        $permissions = [];

        if (self::hasViewAccess($actions)) {
            $permissions[] = 'hrm.performance.view';
            $permissions[] = 'hrm.performance.bonus.view';
            $permissions[] = 'hrm.performance.increment.view';
        }

        if (in_array('manage', $actions, true) || in_array('operate', $actions, true)) {
            $permissions[] = 'hrm.performance.manage';
            $permissions[] = 'hrm.performance.rate';
        }

        if (in_array('approve', $actions, true)) {
            $permissions[] = 'hrm.performance.approve';
            $permissions[] = 'hrm.performance.bonus.manage';
            $permissions[] = 'hrm.performance.increment.manage';
        }

        return $permissions;
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function ordersPermissions(array $actions): array
    {
        $permissions = ['orders.view', 'orders.download'];

        if (in_array('operate', $actions, true) || in_array('manage', $actions, true) || in_array('approve', $actions, true)) {
            $permissions[] = 'orders.update';
        }

        return $permissions;
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function mastersPermissions(array $actions): array
    {
        if (in_array('manage', $actions, true) || in_array('approve', $actions, true)) {
            return ['masters.view', 'masters.manage'];
        }

        return ['masters.view'];
    }

    /** @param  list<string>  $actions
     * @return list<string>
     */
    private static function rolesPermissions(array $actions): array
    {
        if (! self::accessAtLeastLevel($actions, 'lead')) {
            return [];
        }

        return ['roles.manage'];
    }

    /** @param  list<string>  $actions */
    private static function hasViewAccess(array $actions): bool
    {
        return $actions !== [];
    }

    private static function accessAtLeast(string $accessLevel, string $minimum): bool
    {
        $ranks = ['view' => 1, 'operate' => 2, 'manage' => 3, 'lead' => 4, 'full' => 5];

        return ($ranks[$accessLevel] ?? 1) >= ($ranks[$minimum] ?? 99);
    }

    /** @param  list<string>  $actions */
    private static function accessAtLeastLevel(array $actions, string $minimum): bool
    {
        if (in_array('approve', $actions, true)) {
            return true;
        }

        if ($minimum === 'lead') {
            return in_array('manage', $actions, true);
        }

        return in_array('manage', $actions, true) || in_array('operate', $actions, true);
    }

    private static function normalizeDesignation(string $designation): string
    {
        $d = strtolower(trim($designation));

        if (str_contains($d, 'comp')) {
            return 'manager_comp';
        }

        if (preg_match('/\b(cfo)\b/', $d)) {
            return 'cfo';
        }

        if (preg_match('/\b(dgm|deputy general manager)\b/', $d)) {
            return 'dgm';
        }

        if (preg_match('/\b(agm|assistant general manager)\b/', $d)) {
            return 'agm';
        }

        if (preg_match('/\b(gm|general manager)\b/', $d)) {
            return 'gm';
        }

        if (preg_match('/\bsr\.?\s*manager\b/', $d)) {
            return 'sr_manager';
        }

        if (preg_match('/\bdeputy manager\b/', $d)) {
            return 'deputy_manager';
        }

        if (preg_match('/\basst\.?\s*manager\b/', $d)) {
            return 'asst_manager';
        }

        if (preg_match('/\bmanager\b/', $d)) {
            return 'manager';
        }

        if (preg_match('/\bsr\.?\s*merchandiser\b/', $d)) {
            return 'sr_merchandiser';
        }

        if (preg_match('/\basst\.?\s*merchandiser\b/', $d)) {
            return 'asst_merchandiser';
        }

        if (preg_match('/\bmerchandiser\b/', $d)) {
            return 'merchandiser';
        }

        if (preg_match('/\bassistant designer\b/', $d)) {
            return 'asst_designer';
        }

        if (preg_match('/\bdesigner\b/', $d)) {
            return 'designer';
        }

        if (preg_match('/\bdata entry\b/', $d)) {
            return 'data_entry';
        }

        if (preg_match('/\btrainee\b/', $d)) {
            return 'trainee';
        }

        if (preg_match('/\bsr\.?\s*executive\b/', $d)) {
            return 'sr_executive';
        }

        if (preg_match('/\bjr\.?\s*executive\b/', $d)) {
            return 'jr_executive';
        }

        if (preg_match('/\bexecutive\b/', $d)) {
            return 'executive';
        }

        return 'executive';
    }

    /** @param  list<string>  $permissions
     * @return list<string>
     */
    private static function filterValid(array $permissions): array
    {
        $valid = array_flip(RolePermissionCatalog::allPermissionKeys());

        return array_values(array_filter(
            array_unique($permissions),
            static fn (string $permission) => isset($valid[$permission])
        ));
    }
}
