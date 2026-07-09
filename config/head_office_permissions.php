<?php

/**
 * Head Office role permissions — department modules × designation access level.
 *
 * Role key format: {DepartmentPrefix}-{Designation}
 * e.g. Admin-GM, Commercial-Sr.Executive, Accounts-CFO
 */
return [

    /*
    | Access levels applied to each module in a department bundle:
    | view → *.view only
    | operate → *.view + limited actions (orders.update, partial manage)
    | manage → *.view + *.manage
    | lead → manage + selected approve permissions for that department
    | full → manage + all approve flags for that department
    */
    'access_levels' => [
        'view'    => ['view'],
        'operate' => ['view', 'operate'],
        'manage'  => ['view', 'manage'],
        'lead'    => ['view', 'manage', 'approve'],
        'full'    => ['view', 'manage', 'approve'],
    ],

    /*
    | Designation → access level (within department module bundle).
    */
    'designation_levels' => [
        'gm'              => 'full',
        'cfo'             => 'full',
        'dgm'             => 'lead',
        'agm'             => 'lead',
        'sr_manager'      => 'lead',
        'manager'         => 'manage',
        'deputy_manager'  => 'operate',
        'asst_manager'    => 'operate',
        'sr_executive'    => 'operate',
        'executive'       => 'view',
        'jr_executive'    => 'view',
        'sr_merchandiser' => 'operate',
        'merchandiser'    => 'view',
        'asst_merchandiser' => 'view',
        'designer'        => 'operate',
        'asst_designer'   => 'view',
        'data_entry'      => 'view',
        'trainee'         => 'view',
        'cad_executive'   => 'view',
    ],

    /*
    | Department prefix (role key before first hyphen) → ERP modules.
    | Module keys map to permission prefixes in HeadOfficeRolePermissionCatalog.
    */
    'department_modules' => [
        'Admin' => [
            'hrm.employees',
            'hrm.leave',
            'hrm.attendance',
            'hrm.recruitment',
            'hrm.compliance',
            'hrm.performance',
            'tms',
        ],
        'IT' => [
            'admin.settings',
            'admin.users',
            'admin.roles',
            'masters',
            'hrm.masters',
        ],
        'Accounts' => [
            'hrm.finance',
            'hrm.salary',
            'hrm.compliance',
            'masters',
            'tms.accounts',
        ],
        'Audit' => [
            'hrm.finance',
            'hrm.salary',
            'hrm.compliance',
            'hrm.employees',
        ],
        'Commercial' => [
            'orders',
            'masters',
        ],
        'Procurement' => [
            'orders',
            'masters',
        ],
        'Merchandising' => [
            'orders',
            'masters',
        ],
        'Design' => [
            'orders',
            'masters',
        ],
        'CAD' => [
            'orders',
            'masters',
        ],
        'MIS' => [
            'orders',
            'hrm.dashboard',
            'hrm.employees',
        ],
        'Production' => [
            'orders',
            'masters',
            'hrm.rmg',
        ],
    ],

    /*
    | Special designation overrides (department prefix + normalized designation).
    */
    'designation_overrides' => [
        'Admin|manager_comp' => [
            'modules' => ['hrm.employees', 'hrm.compliance', 'hrm.attendance', 'hrm.leave'],
            'access'  => 'lead',
        ],
    ],

];
