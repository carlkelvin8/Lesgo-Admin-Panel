<?php

return [
    'required_permissions' => [
        'dashboard.view',
    ],

    'permissions' => [
        'dashboard.view' => [
            'label' => 'View dashboard',
            'group' => 'Dashboard & Access',
            'description' => 'Open the admin dashboard and view its operational summary.',
        ],
        'roles.manage' => [
            'label' => 'Manage roles & permissions',
            'group' => 'Dashboard & Access',
            'description' => 'Change the module access assigned to other administrator roles.',
        ],
        'users.view' => [
            'label' => 'View users',
            'group' => 'User Management',
            'description' => 'View user accounts and account details.',
        ],
        'users.manage' => [
            'label' => 'Manage users',
            'group' => 'User Management',
            'description' => 'Create, edit, activate, deactivate, and delete user accounts.',
        ],
        'drivers.manage' => [
            'label' => 'Manage drivers',
            'group' => 'Service Operations',
            'description' => 'Manage driver profiles, documents, and account status.',
        ],
        'partners.manage' => [
            'label' => 'Manage partners',
            'group' => 'Service Operations',
            'description' => 'Manage partner accounts, menus, and staff access.',
        ],
        'orders.view' => [
            'label' => 'View orders',
            'group' => 'Service Operations',
            'description' => 'View orders and their tracking history.',
        ],
        'orders.manage' => [
            'label' => 'Manage orders',
            'group' => 'Service Operations',
            'description' => 'Update order status and operational details.',
        ],
        'services.manage' => [
            'label' => 'Manage services',
            'group' => 'Service Operations',
            'description' => 'Create and update the services offered by LesGo.',
        ],
        'payments.view' => [
            'label' => 'View payments',
            'group' => 'Finance & Wallets',
            'description' => 'View payment records and transaction details.',
        ],
        'payments.manage' => [
            'label' => 'Manage payments',
            'group' => 'Finance & Wallets',
            'description' => 'Record refunds and reconcile payments.',
        ],
        'wallets.view' => [
            'label' => 'View wallets',
            'group' => 'Finance & Wallets',
            'description' => 'View wallet balances, transactions, and top-ups.',
        ],
        'wallets.manage' => [
            'label' => 'Manage wallets',
            'group' => 'Finance & Wallets',
            'description' => 'Adjust balances and review wallet top-ups.',
        ],
        'tickets.manage' => [
            'label' => 'Manage support tickets',
            'group' => 'Support & Communication',
            'description' => 'Review, assign, and respond to support tickets.',
        ],
        'ratings.manage' => [
            'label' => 'Manage ratings & reviews',
            'group' => 'Support & Communication',
            'description' => 'Review and moderate customer ratings and feedback.',
        ],
        'faq.manage' => [
            'label' => 'Manage FAQ',
            'group' => 'Support & Communication',
            'description' => 'Create and update FAQ categories and articles.',
        ],
        'notifications.manage' => [
            'label' => 'Manage notifications',
            'group' => 'Support & Communication',
            'description' => 'Publish, retry, and remove administrator notifications.',
        ],
        'verifications.manage' => [
            'label' => 'Manage verifications',
            'group' => 'Security & Reporting',
            'description' => 'Review and decide document verification requests.',
        ],
        'security.manage' => [
            'label' => 'Manage security',
            'group' => 'Security & Reporting',
            'description' => 'Manage security settings, network rules, and security events.',
        ],
        'audit.view' => [
            'label' => 'View audit logs',
            'group' => 'Security & Reporting',
            'description' => 'View the history of administrator actions.',
        ],
        'reports.view' => [
            'label' => 'View analytics & reports',
            'group' => 'Security & Reporting',
            'description' => 'View analytics and generate operational reports.',
        ],
    ],

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
