<?php

return [
    'use_fax' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'regular_holiday' => [
        ['name' => '月', 'value' => '0'], // 0
        ['name' => '火', 'value' => '0'], // 1
        ['name' => '水', 'value' => '0'], // 2
        ['name' => '木', 'value' => '0'], // 3
        ['name' => '金', 'value' => '0'], // 4
        ['name' => '土', 'value' => '0'], // 5
        ['name' => '日', 'value' => '0'], // 6
        ['name' => '祝', 'value' => '0'], // 7
        ['name' => '無し', 'value' => '0'], // 8
        ['name' => '不定休', 'value' => '0'], // 9
    ],

    'can_card' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'card_types' => [
        'VISA' => 'VISA',
        'MASTER' => 'MASTER',
        'JCB' => 'JCB',
        'AMEX' => 'アメックス',
        'DINERS' => 'ダイナース',
        'OTHER' => 'その他',
    ],

    'can_digital_money' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'digital_money_types' => [
        'SUICA' => '交通電子マネー',
        'NANACO' => 'nanaco',
        'WAON' => 'WAON',
        'ID' => 'ID',
        'QUICPAY' => 'QUICPay',
        'EDY' => '楽天Edy',
        'PAYPAY' => 'PayPay',
        'MERPAY' => 'メルペイ',
        'OTHER' => 'その他',
    ],

    'smoking_types' => [
        'ALL_OK' => '全面喫煙可',
        'SEPARATE' => '分煙',
        'NO_SMOKING' => '完全禁煙',
    ],

    'can_charter' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'charter_types' => [
        '20_UNDER_PEOPLE_C' => '20人以下可',
        '20-50_PEOPLE_C' => '20〜50人可能',
        '50_PEOPLE_C' => '50人以上可',
    ],

    'has_private_room' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'private_room_types' => [
        '2_PEOPLE' => '2人可',
        '4_PEOPLE' => '4人可',
        '6_PEOPLE' => '6人可',
        '8_PEOPLE' => '8人可',
        '10-20_PEOPLE' => '10~20人可',
        '20-30_PEOPLE' => '20~30人可',
        '30_OVER_PEOPLE' => '30人以上可',
    ],

    'has_parking' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'has_coin_parking' => [
        1 => 'あり',
        0 => 'なし',
    ],

    'budget_lower_limit' => [
        0 => '1000',
        1 => '2000',
        2 => '3000',
        3 => '4000',
        4 => '5000',
        5 => '6000',
        6 => '8000',
        7 => '10000',
        8 => '15000',
        9 => '20000',
        10 => '30000',
    ],

    'budget_limit' => [
        0 => '999',
        1 => '1999',
        2 => '2999',
        3 => '3999',
        4 => '4999',
        5 => '5999',
        6 => '7999',
        7 => '9999',
        8 => '14999',
        9 => '19999',
        10 => '29999',
    ],

    'pick_up_time_interval' => [
        1 => '15',
        2 => '30',
        3 => '45',
        4 => '60',
    ],

    'roundUpTime' => [
        1 => 15,
    ],

    'rsvLimit' => [
        'bottom' => 0,
        'lower' => 1,
        'upper' => 99,
    ],

    'interval' => [
        'vacancyTime' => 30,
    ],

    'remarks' => [
        'upper' => 200,
    ],
    'description' => [
        'upper' => 80,
    ],
    'imgRequiredWeight' => [
        'lower' => 3,
    ],
    'tel_support' => [
        1 => '要',
        0 => '不要',
    ],
];
