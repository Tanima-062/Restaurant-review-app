<?php

return [

    'module' => 'Econ\PaymentEcon',

    'payment_type' => [
        'econ' => [
            'CASH' => 12,
            'CREDIT' => 13,
        ]
    ],

    'payment_method' => [
        'credit' => 'CREDIT'
    ],

    'show_conveni' => true,

    'tax' => [
        [
            'start'  => '201910',
            'end'    => '',
            'rate'   => 10
        ],
        [
            'start'  => '201404',
            'end'    => '201909',
            'rate'   => 8
        ],
        [
            'start'  => '199704',
            'end'    => '201403',
            'rate'   => 5
        ],
        [
            'start'  => '198904',
            'end'    => '199703',
            'rate'   => 3
        ],
        [
            'start'  => '',
            'end'    => '198903',
            'rate'   => 0
        ],
    ],

    'credit_fee' => [
        'dev' => 0,
        'pro' => 0,
    ],

    'log_status' => [
        'success'     => '決済正常完了',
        'hold'        => '決済通知待ち',
        'error'       => '決済失敗',
        'cancel'      => '決済キャンセル',
    ],

    'account_code' => [
        'MENU' => 'メニュー',
        'RESTAURANT' => 'オプション(レストラン)',
        'OKONOMI' => 'オプション(お好み)',
        'TOPPING' => 'オプション(トッピング)',
        'OTHER' => 'その他',
    ],

    'app_url' => [
        'local' => 'https://jp.skyticket.jp/gourmet/',
        'develop' => 'https://jp.skyticket.jp/gourmet/',
        'staging' => 'https://test.skyticket.jp/gourmet/',
        'production' => 'https://skyticket.jp/gourmet/',
    ],
];
