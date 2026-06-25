<?php

return [
    [
        'name'              => 'Casual Leave (CL)',
        'is_paid'           => true,
        'max_days_per_year' => 10,
        'description'       => 'Short-notice personal leave',
    ],
    [
        'name'              => 'Sick Leave (SL)',
        'is_paid'           => true,
        'max_days_per_year' => 14,
        'description'       => 'Medical leave — certificate required after policy threshold',
    ],
    [
        'name'              => 'Earned Leave (EL)',
        'is_paid'           => true,
        'max_days_per_year' => 18,
        'description'       => 'Annual earned leave with monthly accrual',
    ],
    [
        'name'              => 'Maternity Leave',
        'is_paid'           => true,
        'max_days_per_year' => 112,
        'description'       => '16 weeks as per Bangladesh labour law',
    ],
    [
        'name'              => 'Paternity Leave',
        'is_paid'           => true,
        'max_days_per_year' => 3,
        'description'       => 'Policy-based paternity leave',
    ],
    [
        'name'              => 'Festival Leave',
        'is_paid'           => true,
        'max_days_per_year' => null,
        'description'       => 'Eid and other festival holidays',
    ],
    [
        'name'              => 'Leave Without Pay (LWP)',
        'is_paid'           => false,
        'max_days_per_year' => null,
        'description'       => 'Unpaid leave — payroll deduction applies',
    ],
    [
        'name'              => 'Compensatory Off',
        'is_paid'           => true,
        'max_days_per_year' => null,
        'description'       => 'Replacement day off for overtime / holiday work',
    ],
];
