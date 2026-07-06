<?php

return [

    'groups' => [
        'Operations' => [
            'orders.view'     => 'View requirements dashboard',
            'orders.update'   => 'Update requirement status',
            'orders.download' => 'Download & preview requirement files',
            'orders.delete'   => 'Delete requirements',
        ],
        'Administration' => [
            'users.manage'    => 'Manage users',
            'roles.manage'    => 'Manage custom roles',
            'settings.manage' => 'Manage application settings',
        ],
        'Knowledge Base' => [
            'kb.view'   => 'View knowledge base articles',
            'kb.manage' => 'Create and edit knowledge base articles',
        ],
    ],

    'master_global' => [
        'masters.view'   => 'View all master modules',
        'masters.manage' => 'Manage all master modules',
    ],

    'master_actions' => [
        'view'   => 'View',
        'manage' => 'Manage',
    ],

    /*
    | Users with any of these permissions can access all factory / unit data even when
    | their user account has factory_id set (group-level operators).
    */
    'cross_unit_factory_permissions' => [
        'users.manage',
        'roles.manage',
        'settings.manage',
    ],

];
