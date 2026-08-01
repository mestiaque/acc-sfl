<?php

return [
    'ACCOUNTS MANAGEMENT' => [
        'ac_dashboard' => [
            'label' => 'Accounting Dashboard',
            'permissions' => ['view' => 'View', 'all' => 'All'],
        ],
        'ac_branch' => [
            'label' => 'Branch',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_payment_method' => [
            'label' => 'Payment Methods',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_fiscal_year' => [
            'label' => 'Fiscal Year',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_account' => [
            'label' => 'Accounts',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_master_particular' => [
            'label' => 'Master Particular',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_particular' => [
            'label' => 'Particular',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_balance_receive' => [
            'label' => 'Balance Receive',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'import' => 'Import', 'all' => 'All'],
        ],
        'ac_expense' => [
            'label' => 'Expense',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'import' => 'Import', 'all' => 'All'],
        ],
        'ac_expense_iou' => [
            'label' => 'Expense IOU',
            'permissions' => ['list' => 'List', 'add' => 'Create', 'edit' => 'Edit', 'view' => 'View', 'delete' => 'Delete', 'all' => 'All'],
        ],
        'ac_transaction' => [
            'label' => 'Transactions',
            'permissions' => ['list' => 'List', 'view' => 'View', 'all' => 'All'],
        ],
        'ac_report' => [
            'label' => 'Reports',
            'permissions' => ['view' => 'View', 'export' => 'Export', 'all' => 'All'],
        ],
    ],
];
