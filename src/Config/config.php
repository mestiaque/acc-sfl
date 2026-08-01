<?php

return [
    'route' => [
        'prefix' => 'admin/acc-sfl',
        'as' => 'acc-sfl.',
        'middleware' => ['web', 'auth'],
    ],

    'voucher_prefixes' => [
        'expense' => 'EXP',
        'iou' => 'IOU',
        'balance_receive' => 'BR',
    ],

    'company' => [
        'name' => 'Suhana Fashions Limited',
        'subtitle' => '',
        'address' => 'Kathgora, Jirabo, Ashulia, Savar. Dhaka',
        'mobile' => '',
        'email' => '',
    ],
];
