<?php

return [
    'route' => [
        'prefix' => 'admin/acc-sfl',
        'as' => 'acc-sfl.',
        'middleware' => ['web', 'auth'],
    ],

    // Day end report mail (acc-sfl:day-end-report) — sent every day at this time (needs the host cron: schedule:run).
    'day_end_report_at' => env('ACC_DAY_END_REPORT_AT', '21:00'),

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
