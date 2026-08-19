<?php

return [
    'roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'permissions' => ['*'],
        ],
        'operations' => [
            'label' => 'Operations',
            'permissions' => [
                'dashboard.view', 'users.view', 'drivers.manage', 'partners.manage',
                'orders.view', 'orders.manage', 'services.manage', 'verifications.manage',
                'notifications.manage', 'ratings.manage', 'reports.view',
            ],
        ],
        'finance' => [
            'label' => 'Finance',
            'permissions' => [
                'dashboard.view', 'users.view', 'orders.view', 'payments.view',
                'payments.manage', 'wallets.view', 'wallets.manage',
                'reports.view', 'audit.view',
            ],
        ],
        'support' => [
            'label' => 'Support',
            'permissions' => [
                'dashboard.view', 'users.view', 'orders.view', 'tickets.manage',
                'ratings.manage', 'faq.manage', 'notifications.manage',
            ],
        ],
    ],
];
