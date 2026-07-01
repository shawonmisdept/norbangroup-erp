<?php

/**
 * Head Office organization master — generated from HRMS Contact Information.xlsx
 * Generated: 2026-07-01T10:02:54.224Z
 */

return [
    'factory' => 'Head Office',
    'buildings' => [
        [
            'name' => 'Main Building',
            'floors' => [
                [
                    'name' => '3rd Floor',
                    'floor_number' => 3,
                ],
                [
                    'name' => '4th Floor',
                    'floor_number' => 4,
                ],
                [
                    'name' => 'Ground Floor',
                    'floor_number' => 0,
                ],
            ],
        ],
        [
            'name' => 'Shwapno Building',
            'floors' => [
                [
                    'name' => '3rd Floor',
                    'floor_number' => 3,
                ],
                [
                    'name' => '4th Floor',
                    'floor_number' => 4,
                ],
            ],
        ],
    ],
    'departments' => [
        [
            'name' => 'Accounts & Finance',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'AGM',
                    'role' => 'Accounts-AGM',
                ],
                [
                    'name' => 'Asst.Manager',
                    'role' => 'Accounts-Asst.Manager',
                ],
                [
                    'name' => 'CFO',
                    'role' => 'Accounts-CFO',
                ],
                [
                    'name' => 'DGM',
                    'role' => 'Accounts-DGM',
                ],
                [
                    'name' => 'Manager',
                    'role' => 'Accounts-Manager',
                ],
                [
                    'name' => 'Sr.Executive',
                    'role' => 'Accounts-Sr.Executive',
                ],
            ],
        ],
        [
            'name' => 'Admin & HR',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'AGM',
                    'role' => 'Admin-AGM',
                ],
                [
                    'name' => 'GM',
                    'role' => 'Admin-GM',
                ],
                [
                    'name' => 'Manager (Comp)',
                    'role' => 'Admin-Manager (Comp)',
                ],
                [
                    'name' => 'Sr.Executive',
                    'role' => 'Admin-Sr.Executive',
                ],
                [
                    'name' => 'Sr.Manager',
                    'role' => 'Admin-Sr.Manager',
                ],
            ],
        ],
        [
            'name' => 'Admin (Assistant)',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => 'Ground Floor',
            'designations' => [
                [
                    'name' => 'Caretaker',
                    'role' => null,
                ],
                [
                    'name' => 'Cleaner',
                    'role' => null,
                ],
                [
                    'name' => 'Cook',
                    'role' => null,
                ],
                [
                    'name' => 'Executive (Electrical)',
                    'role' => null,
                ],
                [
                    'name' => 'Office Assistant',
                    'role' => null,
                ],
                [
                    'name' => 'Peon',
                    'role' => null,
                ],
            ],
        ],
        [
            'name' => 'Admin (Driver)',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => 'Ground Floor',
            'designations' => [
                [
                    'name' => 'Driver',
                    'role' => null,
                ],
            ],
        ],
        [
            'name' => 'Audit',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'Sr.Manager',
                    'role' => 'Audit-Sr.Manager',
                ],
            ],
        ],
        [
            'name' => 'CAD',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '4th Floor',
            'designations' => [
                [
                    'name' => 'Executive',
                    'role' => 'CAD-Executive',
                ],
            ],
        ],
        [
            'name' => 'Commercial',
            'native_name' => null,
            'default_building' => 'Shwapno Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'AGM',
                    'role' => 'Commercial-AGM',
                ],
                [
                    'name' => 'Asst.Manager',
                    'role' => 'Commercial-Asst.Manager',
                ],
                [
                    'name' => 'Deputy Manager',
                    'role' => 'Commercial-Deputy Manager',
                ],
                [
                    'name' => 'DGM',
                    'role' => 'Commercial-DGM',
                ],
                [
                    'name' => 'Executive',
                    'role' => 'Commercial-Executive',
                ],
                [
                    'name' => 'Jr.Executive',
                    'role' => 'Commercial-Jr.Executive',
                ],
                [
                    'name' => 'Manager',
                    'role' => 'Commercial-Manager',
                ],
                [
                    'name' => 'Massanger',
                    'role' => null,
                ],
                [
                    'name' => 'Sr.Executive',
                    'role' => 'Commercial-Sr.Executive',
                ],
                [
                    'name' => 'Sr.Executive (Custom Sarker)',
                    'role' => 'Commercial-Sr.Executive',
                ],
            ],
        ],
        [
            'name' => 'Design',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'Assistant Designer',
                    'role' => 'Design-Assistant Designer',
                ],
                [
                    'name' => 'Manager',
                    'role' => 'Design-Manager',
                ],
            ],
        ],
        [
            'name' => 'IT',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '4th Floor',
            'designations' => [
                [
                    'name' => 'Manager',
                    'role' => 'IT-Manager',
                ],
            ],
        ],
        [
            'name' => 'MIS',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '4th Floor',
            'designations' => [
                [
                    'name' => 'Data Entry Operator',
                    'role' => 'MIS-Data Entry Operator',
                ],
                [
                    'name' => 'Executive',
                    'role' => 'MIS-Executive',
                ],
                [
                    'name' => 'Management Trainee',
                    'role' => 'MIS-Management Trainee',
                ],
            ],
        ],
        [
            'name' => 'Merchandising',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '4th Floor',
            'designations' => [
                [
                    'name' => 'AGM',
                    'role' => 'Merchandising-AGM',
                ],
                [
                    'name' => 'Asst.Manager',
                    'role' => 'Merchandising-Asst.Manager',
                ],
                [
                    'name' => 'Asst.Merchandiser',
                    'role' => 'Merchandising-Asst.Merchandiser',
                ],
                [
                    'name' => 'Deputy Manager',
                    'role' => 'Merchandising-Deputy Manager',
                ],
                [
                    'name' => 'Executive (Data Entry)',
                    'role' => 'MIS-Executive',
                ],
                [
                    'name' => 'GM',
                    'role' => 'Merchandising-GM',
                ],
                [
                    'name' => 'Manager',
                    'role' => 'Merchandising-Manager',
                ],
                [
                    'name' => 'Merchandiser',
                    'role' => 'Merchandising-Merchandiser',
                ],
                [
                    'name' => 'Sr.Merchandiser',
                    'role' => 'Merchandising-Sr.Merchandiser',
                ],
            ],
        ],
        [
            'name' => 'Procurement',
            'native_name' => null,
            'default_building' => 'Shwapno Building',
            'default_floor' => '3rd Floor',
            'designations' => [
                [
                    'name' => 'AGM',
                    'role' => 'Procurement-AGM',
                ],
                [
                    'name' => 'Asst.Manager',
                    'role' => 'Procurement-Asst.Manager',
                ],
                [
                    'name' => 'Executive',
                    'role' => 'Procurement-Executive',
                ],
                [
                    'name' => 'Jr.Executive',
                    'role' => 'Procurement-Jr.Executive',
                ],
                [
                    'name' => 'Manager',
                    'role' => 'Procurement-Manager',
                ],
                [
                    'name' => 'Office Assistant',
                    'role' => null,
                ],
                [
                    'name' => 'Purchase Assistant',
                    'role' => null,
                ],
                [
                    'name' => 'Sr.Executive',
                    'role' => 'Procurement-Sr.Executive',
                ],
            ],
        ],
        [
            'name' => 'Production',
            'native_name' => null,
            'default_building' => 'Main Building',
            'default_floor' => '4th Floor',
            'designations' => [
                [
                    'name' => 'GM',
                    'role' => 'Production-GM',
                ],
                [
                    'name' => 'GM (Production and Technical)',
                    'role' => 'Production-GM',
                ],
            ],
        ],
    ],
    'roles' => [
        'Accounts-AGM',
        'Accounts-Asst.Manager',
        'Accounts-CFO',
        'Accounts-DGM',
        'Accounts-Manager',
        'Accounts-Sr.Executive',
        'Admin-AGM',
        'Admin-GM',
        'Admin-Manager (Comp)',
        'Admin-Sr.Executive',
        'Admin-Sr.Manager',
        'Audit-Sr.Manager',
        'CAD-Executive',
        'Commercial-AGM',
        'Commercial-Asst.Manager',
        'Commercial-DGM',
        'Commercial-Deputy Manager',
        'Commercial-Executive',
        'Commercial-Jr.Executive',
        'Commercial-Manager',
        'Commercial-Sr.Executive',
        'Design-Assistant Designer',
        'Design-Manager',
        'IT-Manager',
        'MIS-Data Entry Operator',
        'MIS-Executive',
        'MIS-Management Trainee',
        'Merchandising-AGM',
        'Merchandising-Asst.Manager',
        'Merchandising-Asst.Merchandiser',
        'Merchandising-Deputy Manager',
        'Merchandising-GM',
        'Merchandising-Manager',
        'Merchandising-Merchandiser',
        'Merchandising-Sr.Merchandiser',
        'Procurement-AGM',
        'Procurement-Asst.Manager',
        'Procurement-Executive',
        'Procurement-Jr.Executive',
        'Procurement-Manager',
        'Procurement-Sr.Executive',
        'Production-GM',
    ],
];
