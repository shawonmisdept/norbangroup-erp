<?php

return [

    /*
    | Module registry — metadata only. Article content lives in kb_articles.
    | submodules_config: Laravel config key for submodule labels/routes (optional).
    */
    'modules' => [
        [
            'code'               => 'commercial',
            'label_en'           => 'Commercial — Requirements',
            'label_bn'           => 'কমার্শিয়াল — Requirements',
            'view_permission'    => 'orders.view',
            'submodules_config'  => null,
            'sort_order'         => 10,
        ],
        [
            'code'               => 'masters',
            'label_en'           => 'Master Data (ERP)',
            'label_bn'           => 'মাস্টার ডেটা (ERP)',
            'view_permission'    => 'masters.view',
            'submodules_config'  => null,
            'sort_order'         => 20,
        ],
        [
            'code'               => 'hrm-employee',
            'label_en'           => 'HRM — Employee',
            'label_bn'           => 'HRM — কর্মী',
            'view_permission'    => 'hrm.employees.view',
            'submodules_config'  => 'hrm.employee_submodules',
            'sort_order'         => 30,
        ],
        [
            'code'               => 'hrm-recruitment',
            'label_en'           => 'HRM — Recruitment',
            'label_bn'           => 'HRM — নিয়োগ',
            'view_permission'    => 'hrm.recruitment.postings.view',
            'submodules_config'  => 'hrm.recruitment_submodules',
            'sort_order'         => 40,
        ],
        [
            'code'               => 'hrm-attendance',
            'label_en'           => 'HRM — Attendance',
            'label_bn'           => 'HRM — উপস্থিতি',
            'view_permission'    => 'hrm.attendance.view',
            'submodules_config'  => 'hrm.attendance_submodules',
            'sort_order'         => 50,
        ],
        [
            'code'               => 'hrm-leave',
            'label_en'           => 'HRM — Leave',
            'label_bn'           => 'HRM — ছুটি',
            'view_permission'    => 'hrm.leave.view',
            'submodules_config'  => 'hrm.leave_submodules',
            'sort_order'         => 60,
        ],
        [
            'code'               => 'hrm-performance',
            'label_en'           => 'HRM — Performance',
            'label_bn'           => 'HRM — পারফরম্যান্স',
            'view_permission'    => 'hrm.performance.view',
            'submodules_config'  => 'hrm.performance_submodules',
            'sort_order'         => 70,
        ],
        [
            'code'               => 'hrm-salary',
            'label_en'           => 'HRM — Salary / Payroll',
            'label_bn'           => 'HRM — বেতন / পে-রোল',
            'view_permission'    => 'hrm.salary.view',
            'submodules_config'  => 'hrm.salary_submodules',
            'sort_order'         => 80,
        ],
        [
            'code'               => 'hrm-compliance',
            'label_en'           => 'HRM — Compliance',
            'label_bn'           => 'HRM — কমপ্লায়েন্স',
            'view_permission'    => 'hrm.compliance.view',
            'submodules_config'  => 'hrm.compliance_submodules',
            'sort_order'         => 90,
        ],
        [
            'code'               => 'hrm-finance',
            'label_en'           => 'HRM — Finance',
            'label_bn'           => 'HRM — ফাইন্যান্স',
            'view_permission'    => 'hrm.finance.view',
            'submodules_config'  => 'hrm.finance_submodules',
            'sort_order'         => 100,
        ],
        [
            'code'               => 'hrm-rmg',
            'label_en'           => 'HRM — RMG Extras',
            'label_bn'           => 'HRM — RMG এক্সট্রা',
            'view_permission'    => 'hrm.rmg.view',
            'submodules_config'  => 'hrm.rmg_submodules',
            'sort_order'         => 110,
        ],
        [
            'code'               => 'hrm-masters',
            'label_en'           => 'HRM — Masters',
            'label_bn'           => 'HRM — মাস্টার',
            'view_permission'    => 'hrm.masters.view',
            'submodules_config'  => null,
            'sort_order'         => 120,
        ],
        [
            'code'               => 'tms',
            'label_en'           => 'Transport (TMS)',
            'label_bn'           => 'পরিবহন (TMS)',
            'view_permission'    => 'tms.dashboard.view',
            'submodules_config'  => 'tms.submodules',
            'sort_order'         => 130,
        ],
        [
            'code'               => 'admin-system',
            'label_en'           => 'System Administration',
            'label_bn'           => 'সিস্টেম অ্যাডমিন',
            'view_permission'    => 'users.manage',
            'submodules_config'  => null,
            'sort_order'         => 140,
        ],
    ],

];
