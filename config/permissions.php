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
    ],

    'master_global' => [
        'masters.view'   => 'View all master modules',
        'masters.manage' => 'Manage all master modules',
    ],

    'master_actions' => [
        'view'   => 'View',
        'manage' => 'Manage',
    ],

];
