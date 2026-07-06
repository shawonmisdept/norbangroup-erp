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
        'name'              => 'Maternity Leave',
        'is_paid'           => true,
        'max_days_per_year' => 112,
        'description'       => '16 weeks as per Bangladesh labour law',
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
];
