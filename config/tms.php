<?php

return [
    'permissions' => [
        'global' => [
            'tms.dashboard.view' => 'View TMS Dashboard',
        ],
        'settings' => [
            'tms.settings.view'   => 'View TMS Settings',
            'tms.settings.manage' => 'Manage TMS Settings',
        ],
        'vehicles' => [
            'tms.vehicles.view'   => 'View Vehicles',
            'tms.vehicles.manage' => 'Manage Vehicles',
        ],
        'drivers' => [
            'tms.drivers.view'   => 'View Drivers',
            'tms.drivers.manage' => 'Manage Drivers',
        ],
        'requests' => [
            'tms.requests.view'    => 'View Transport Requests',
            'tms.requests.approve' => 'Approve / Reject / Assign Requests',
        ],
        'trips' => [
            'tms.trips.view'   => 'View Trip Logs',
            'tms.trips.manage' => 'Manage Trips (Start/End, Odometer)',
        ],
        'fuel' => [
            'tms.fuel.view'   => 'View Fuel Logs',
            'tms.fuel.manage' => 'Manage Fuel Logs',
        ],
        'reports' => [
            'tms.reports.view' => 'View TMS Reports',
        ],
        'overtime' => [
            'tms.overtime.manage' => 'Mark Driver OT Paid',
        ],
        'rental_vendors' => [
            'tms.rental_vendors.view'   => 'View Rental Vendors',
            'tms.rental_vendors.manage' => 'Manage Rental Vendors',
        ],
        'rental_charges' => [
            'tms.rental_charges.manage' => 'Mark Rental Vehicle Charges Paid',
        ],
        'rental_drivers' => [
            'tms.rental_drivers.view'   => 'View Rental Drivers',
            'tms.rental_drivers.manage' => 'Manage Rental Drivers',
        ],
        'maintenance' => [
            'tms.maintenance.view'   => 'View Maintenance Logs',
            'tms.maintenance.manage' => 'Manage Maintenance Logs',
        ],
    ],

    'submodules' => [
        'dashboard' => [
            'label'       => 'Dashboard',
            'description' => 'Pending requests, active trips & payment summary',
            'permission'  => 'tms.dashboard.view',
            'route'       => 'admin.tms.dashboard',
            'icon'        => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            'status'      => 'active',
        ],
        'settings' => [
            'label'       => 'Settings',
            'description' => 'Transport policy, rates & system configuration',
            'permission'  => 'tms.settings.view',
            'manage'      => 'tms.settings.manage',
            'route'       => 'admin.tms.settings.index',
            'icon'        => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            'status'      => 'active',
        ],
        'destinations' => [
            'label'       => 'Destinations',
            'description' => 'Standard routes & destination master list',
            'permission'  => 'tms.settings.view',
            'manage'      => 'tms.settings.manage',
            'route'       => 'admin.tms.destinations.index',
            'icon'        => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
            'status'      => 'active',
        ],
        'vehicles' => [
            'label'       => 'Vehicles',
            'description' => 'Own & rental vehicle register & allocation',
            'permission'  => 'tms.vehicles.view',
            'manage'      => 'tms.vehicles.manage',
            'route'       => 'admin.tms.vehicles.index',
            'icon'        => 'M8 7h8m-8 4h8m-4 8V7m8 4v9a1 1 0 01-1 1H5a1 1 0 01-1-1V7l2-4h14l2 4v9a1 1 0 01-1 1h-3',
            'status'      => 'active',
        ],
        'rental_vendors' => [
            'label'       => 'Rental Vendors',
            'description' => 'Third-party rental vendor contracts & contacts',
            'permission'  => 'tms.rental_vendors.view',
            'manage'      => 'tms.rental_vendors.manage',
            'route'       => 'admin.tms.rental-vendors.index',
            'icon'        => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'status'      => 'active',
        ],
        'drivers' => [
            'label'       => 'Company Drivers',
            'description' => 'In-house driver roster & licence details',
            'permission'  => 'tms.drivers.view',
            'manage'      => 'tms.drivers.manage',
            'route'       => 'admin.tms.drivers.index',
            'icon'        => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'status'      => 'active',
        ],
        'rental_drivers' => [
            'label'       => 'Rental Drivers',
            'description' => 'Rental vendor drivers & trip assignment',
            'permission'  => 'tms.rental_drivers.view',
            'manage'      => 'tms.rental_drivers.manage',
            'route'       => 'admin.tms.rental-drivers.index',
            'icon'        => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            'status'      => 'active',
        ],
        'requests' => [
            'label'       => 'Requests',
            'description' => 'Employee transport requests, approval & assignment',
            'permission'  => 'tms.requests.view',
            'also'        => 'tms.requests.approve',
            'route'       => 'admin.tms.requests.index',
            'icon'        => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'status'      => 'active',
        ],
        'trips' => [
            'label'       => 'Trips',
            'description' => 'Trip log, start/end time & passenger tracking',
            'permission'  => 'tms.trips.view',
            'route'       => 'admin.tms.trips.index',
            'icon'        => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
            'status'      => 'active',
        ],
        'odometer' => [
            'label'       => 'Daily KM',
            'description' => 'Morning & evening km readings per vehicle',
            'permission'  => 'tms.trips.view',
            'manage'      => 'tms.trips.manage',
            'route'       => 'admin.tms.odometer.index',
            'icon'        => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
            'status'      => 'active',
        ],
        'fuel' => [
            'label'       => 'Fuel',
            'description' => 'Fuel issue, consumption & cost tracking',
            'permission'  => 'tms.fuel.view',
            'manage'      => 'tms.fuel.manage',
            'route'       => 'admin.tms.fuel.index',
            'icon'        => 'M13 10V3L4 14h7v7l9-11h-7z',
            'status'      => 'active',
        ],
        'maintenance' => [
            'label'       => 'Maintenance',
            'description' => 'Service bills, parts & workshop register',
            'permission'  => 'tms.maintenance.view',
            'manage'      => 'tms.maintenance.manage',
            'route'       => 'admin.tms.maintenance.index',
            'icon'        => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
            'status'      => 'active',
            'active_excludes' => [
                'admin.tms.maintenance.posting',
                'admin.tms.maintenance.posting.*',
            ],
        ],
        'maintenance_posting' => [
            'label'       => 'Bill For Posting',
            'description' => 'Pending maintenance bills queued for finance posting',
            'permission'  => 'tms.maintenance.view',
            'route'       => 'admin.tms.maintenance.posting',
            'icon'        => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'status'      => 'active',
        ],
        'maintenance_parts' => [
            'label'       => 'Parts Catalog',
            'description' => 'Reusable maintenance parts & services master',
            'permission'  => 'tms.maintenance.view',
            'manage'      => 'tms.maintenance.manage',
            'route'       => 'admin.tms.maintenance.parts.index',
            'icon'        => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
            'status'      => 'active',
        ],
        'rental_charges' => [
            'label'       => 'Rental Charges',
            'description' => 'Pending & paid rental vehicle KM charges',
            'permission'  => 'tms.rental_charges.manage',
            'route'       => 'admin.tms.rental-charges.index',
            'icon'        => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            'status'      => 'active',
        ],
        'reports' => [
            'label'       => 'Reports',
            'description' => 'Fleet cost, trips, fuel & odometer analytics',
            'permission'  => 'tms.reports.view',
            'route'       => 'admin.tms.reports.index',
            'icon'        => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'status'      => 'active',
        ],
        'device_api' => [
            'label'       => 'GPS Device / Telematics API',
            'description' => 'Vehicle location history via telematics API',
            'permission'  => 'tms.settings.view',
            'route'       => 'admin.tms.gps.index',
            'icon'        => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
            'status'      => 'active',
        ],
    ],

    'nav_groups' => [
        'Operations' => ['requests', 'trips', 'odometer', 'fuel'],
        'Vehicle Management'      => ['vehicles', 'drivers', 'rental_vendors', 'rental_drivers', 'maintenance', 'maintenance_parts', 'maintenance_posting', 'rental_charges'],
        'Setup'      => ['settings', 'destinations', 'gps_tracking'],
    ],

    'gps_providers' => [
        'none' => [
            'label'       => 'None',
            'description' => 'GPS tracking disabled — no positions recorded.',
        ],
        'device_api' => [
            'label'       => 'GPS Device / Telematics API',
            'description' => 'POST positions to /api/tms/gps/positions with TMS_GPS_API_TOKEN.',
        ],
        'browser' => [
            'label'       => 'Driver Mobile GPS',
            'description' => 'Capture coordinates from driver phone when trip starts/ends.',
        ],
    ],

    /*
    | Bearer token for telematics vendors POSTing to /api/tms/gps/positions
    */
    'gps_api_token' => env('TMS_GPS_API_TOKEN'),

    'request_statuses' => [
        'pending'     => 'Pending',
        'approved'    => 'Approved',
        'rejected'    => 'Rejected',
        'cancelled'   => 'Cancelled',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ],

    'request_status_colors' => [
        'pending'     => 'bg-amber-100 text-amber-800',
        'approved'    => 'bg-blue-100 text-blue-800',
        'rejected'    => 'bg-red-100 text-red-800',
        'cancelled'   => 'bg-gray-100 text-gray-600',
        'in_progress' => 'bg-sky-100 text-sky-800',
        'completed'   => 'bg-green-100 text-green-800',
    ],

    'vehicle_types' => [
        'own'    => 'Own',
        'rental' => 'Rental',
    ],

    'vehicle_statuses' => [
        'available'   => 'Available',
        'on_trip'     => 'On Trip',
        'maintenance' => 'Maintenance',
    ],

    'vehicle_status_colors' => [
        'available'   => 'bg-green-100 text-green-800',
        'on_trip'     => 'bg-sky-100 text-sky-800',
        'maintenance' => 'bg-amber-100 text-amber-800',
    ],

    'fuel_types' => [
        'gas'    => 'Gas',
        'petrol' => 'Petrol',
        'diesel' => 'Diesel',
    ],

    'fuel_paid_by' => [
        'company'       => 'Company',
        'rental_party'  => 'Rental Party',
    ],

    'ot_basis' => [
        'global_office_time'  => 'Global Office End Time',
        'employee_shift_end'  => 'Employee Shift End Time',
    ],

    'trip_statuses' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ],

    'trip_status_colors' => [
        'not_started' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-sky-100 text-sky-800',
        'completed'   => 'bg-green-100 text-green-800',
    ],

    'pickup_grace_minutes' => 0,

    'weekday_labels' => [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ],

    'maintenance_service_types' => [
        'routine'  => 'Routine Service',
        'repair'   => 'Repair',
        'accident' => 'Accident',
    ],

    'maintenance_statuses' => [
        'open'   => 'Open',
        'closed' => 'Closed',
    ],

    'maintenance_status_colors' => [
        'open'   => 'bg-amber-100 text-amber-800',
        'closed' => 'bg-green-100 text-green-800',
    ],

    'maintenance_item_units' => [
        'Pcs',
        'Set',
        'Pair',
        'Service',
        'Job',
        'Ltr',
        'Gal',
        'Kg',
        'Bottle',
        'Can',
        'Box',
        'Roll',
        'Tube',
        'Bag',
        'Side',
        'Ft',
    ],
];
