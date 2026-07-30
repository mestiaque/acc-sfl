<?php

return [
    [
        'group_title' => '',
        [
            'title' => 'Accounts Management',
            'icon' => 'fa-solid fa-cash-register',
            'icon_color' => 'text-primary',
            'permission' => '',
            'order' => 8,
            'children' => [
                ['title' => 'Dashboard', 'icon' => 'fa-solid fa-gauge', 'route' => '/admin/acc-sfl', 'icon_color' => 'text-primary', 'permission' => 'ac_dashboard'],
                [
                    'title' => 'Masters',
                    'icon' => 'fa-solid fa-layer-group',
                    'icon_color' => 'text-secondary',
                    'permission' => '',
                    'children' => [
                        ['title' => 'Branches', 'icon' => 'fa-solid fa-code-branch', 'route' => '/admin/acc-sfl/branches', 'icon_color' => 'text-secondary', 'permission' => 'ac_branch'],
                        ['title' => 'Accounts', 'icon' => 'fa-solid fa-wallet', 'route' => '/admin/acc-sfl/accounts', 'icon_color' => 'text-secondary', 'permission' => 'ac_account'],
                        ['title' => 'Payment Methods', 'icon' => 'fa-solid fa-credit-card', 'route' => '/admin/acc-sfl/payment-methods', 'icon_color' => 'text-secondary', 'permission' => 'ac_payment_method'],
                        ['title' => 'Master Particulars', 'icon' => 'fa-solid fa-list-check', 'route' => '/admin/acc-sfl/master-particulars', 'icon_color' => 'text-secondary', 'permission' => 'ac_master_particular'],
                        ['title' => 'Particulars', 'icon' => 'fa-solid fa-list', 'route' => '/admin/acc-sfl/particulars', 'icon_color' => 'text-secondary', 'permission' => 'ac_particular'],
                    ],
                ],
                ['title' => 'Balance Receive', 'icon' => 'fa-solid fa-hand-holding-dollar', 'route' => '/admin/acc-sfl/balance-receives', 'icon_color' => 'text-success', 'permission' => 'ac_balance_receive'],
                ['title' => 'Expenses', 'icon' => 'fa-solid fa-money-bill-wave', 'route' => '/admin/acc-sfl/expenses', 'icon_color' => 'text-warning', 'permission' => 'ac_expense'],
                ['title' => 'Expense IOU', 'icon' => 'fa-solid fa-file-invoice-dollar', 'route' => '/admin/acc-sfl/expense-ious', 'icon_color' => 'text-warning', 'permission' => 'ac_expense_iou'],
                ['title' => 'Transactions', 'icon' => 'fa-solid fa-right-left', 'route' => '/admin/acc-sfl/transactions', 'icon_color' => 'text-info', 'permission' => 'ac_transaction'],
                [
                    'title' => 'Reports',
                    'icon' => 'fa-solid fa-chart-line',
                    'icon_color' => 'text-info',
                    'permission' => '',
                    'children' => [
                        ['title' => 'Monthly Cash Flow', 'icon' => 'fa-solid fa-calendar-days', 'route' => '/admin/acc-sfl/reports/cash-flow/monthly', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Yearly Cash Flow', 'icon' => 'fa-solid fa-calendar-week', 'route' => '/admin/acc-sfl/reports/cash-flow/yearly', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Balance Receive Report', 'icon' => 'fa-solid fa-hand-holding-dollar', 'route' => '/admin/acc-sfl/reports/balance-receive', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Expense Report', 'icon' => 'fa-solid fa-money-bill-wave', 'route' => '/admin/acc-sfl/reports/expense', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Expense IOU Report', 'icon' => 'fa-solid fa-file-invoice-dollar', 'route' => '/admin/acc-sfl/reports/expense-iou', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Transaction Report', 'icon' => 'fa-solid fa-right-left', 'route' => '/admin/acc-sfl/reports/transaction', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                        ['title' => 'Statement Report', 'icon' => 'fa-solid fa-file-lines', 'route' => '/admin/acc-sfl/reports/statement', 'icon_color' => 'text-info', 'permission' => 'ac_report'],
                    ],
                ],
            ],
        ],
    ],
];
