<?php

/**
 * Demo performance cycles, reviews & run scenarios for Head Office.
 *
 * @see DemoPerformanceSeeder
 */
return [
    'factory' => 'Head Office',
    'year'    => 2026,
    'prefix'  => '[Demo]',

    'cycles' => [
        'probation' => [
            'name'        => '[Demo] Probation Reviews H1 2026',
            'cycle_type'  => 'probation_6m',
            'period_from' => '2025-12-01',
            'period_to'   => '2026-06-30',
            'status'      => 'open',
        ],
        'mid_year' => [
            'name'        => '[Demo] Mid-Year Performance 2026',
            'cycle_type'  => 'mid_year_6m',
            'period_from' => '2025-07-01',
            'period_to'   => '2025-12-31',
            'status'      => 'closed',
        ],
        'annual' => [
            'name'        => '[Demo] Annual Increment Reviews 2026',
            'cycle_type'  => 'annual_12m',
            'period_from' => '2025-01-01',
            'period_to'   => '2025-12-31',
            'status'      => 'closed',
        ],
    ],

    /**
     * Review rows keyed by employee_code.
     * Scores are 0–100 per criterion code (see default_criteria in config/hrm.php).
     */
    'reviews' => [
        // Probation — Rokeya (still on probation, pending line chief rating)
        [
            'cycle'  => 'probation',
            'code'   => 'NCL-D008',
            'status' => 'pending_rating',
            'scores' => [
                'attendance'   => 88,
                'punctuality'  => 82,
                'discipline'   => 90,
                'work_quality' => null,
                'behaviour'    => null,
            ],
        ],
        // Mid-year bonus cycle
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D002',
            'status' => 'approved',
            'scores' => [
                'attendance' => 95, 'punctuality' => 92, 'discipline' => 90,
                'work_quality' => 94, 'behaviour' => 88,
            ],
        ],
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D003',
            'status' => 'approved',
            'scores' => [
                'attendance' => 78, 'punctuality' => 72, 'discipline' => 80,
                'work_quality' => 76, 'behaviour' => 74,
            ],
        ],
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D005',
            'status' => 'approved',
            'scores' => [
                'attendance' => 65, 'punctuality' => 62, 'discipline' => 68,
                'work_quality' => 64, 'behaviour' => 58,
            ],
        ],
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D013',
            'status' => 'approved',
            'scores' => [
                'attendance' => 82, 'punctuality' => 78, 'discipline' => 85,
                'work_quality' => 80, 'behaviour' => 76,
            ],
        ],
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D016',
            'status' => 'pending_hr',
            'scores' => [
                'attendance' => 90, 'punctuality' => 88, 'discipline' => 92,
                'work_quality' => 86, 'behaviour' => 84,
            ],
        ],
        [
            'cycle'  => 'mid_year',
            'code'   => 'NCL-D017',
            'status' => 'pending_rating',
            'scores' => [
                'attendance' => 85, 'punctuality' => 80, 'discipline' => 88,
                'work_quality' => null, 'behaviour' => null,
            ],
        ],
        // Annual increment cycle
        [
            'cycle'  => 'annual',
            'code'   => 'NCL-D001',
            'status' => 'approved',
            'scores' => [
                'attendance' => 92, 'punctuality' => 88, 'discipline' => 90,
                'work_quality' => 86, 'behaviour' => 85,
            ],
        ],
        [
            'cycle'  => 'annual',
            'code'   => 'NCL-D002',
            'status' => 'approved',
            'scores' => [
                'attendance' => 94, 'punctuality' => 91, 'discipline' => 93,
                'work_quality' => 90, 'behaviour' => 88,
            ],
        ],
        [
            'cycle'  => 'annual',
            'code'   => 'NCL-D016',
            'status' => 'approved',
            'scores' => [
                'attendance' => 88, 'punctuality' => 85, 'discipline' => 90,
                'work_quality' => 82, 'behaviour' => 80,
            ],
        ],
        [
            'cycle'  => 'annual',
            'code'   => 'NCL-D020',
            'status' => 'pending_hr',
            'scores' => [
                'attendance' => 76, 'punctuality' => 74, 'discipline' => 78,
                'work_quality' => 72, 'behaviour' => 70,
            ],
        ],
    ],

    /** Employee codes to enable portal login for performance demo */
    'portal_codes' => ['NCL-D002', 'NCL-D008', 'NCL-D003'],
];
