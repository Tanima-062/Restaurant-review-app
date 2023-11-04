<?php

return [
    'siteName' => 'skyticket.com',
    'appCd' => [
        'to' => ['TO' => 'テイクアウト'],
        'rs' => ['RS' => 'レストラン'],
        'tors' => ['TORS' => 'テイクアウト/レストラン'],
    ],
    'suggestCd' => [
        'current' => 'CURRENT_LOC',
        'station' => 'STATION',
        'area' => 'AREA',
        'shop' => 'SHOP',
    ],
    'genreCd' => [
        'cooking' => 'COOKING',
        'menu' => 'MEMU',
        'smallMenu' => 'SMALL_MENU',
        'detailed' => 'DETAILED',
        'smallDetailed' => 'SMALL_DETAILED',
    ],
    'evaluationCd' => [
        'cooking' => 'COOKING',
        'goodDeal' => 'GOOD_DEAL',
        'shine' => 'SHINE',
        'repeat' => 'REPEAT',
    ],
    'optionCd' => [
        'okonomi' => 'OKONOMI',
        'topping' => 'TOPPING',
    ],
    'priceCd' => [
        'normal' => 'NORMAL',
    ],
    'imageCd' => [
        // 画像コード(メニュー)
        'menuThumbnail' => 'MENU_THUMB',
        'menuRecommendThumb' => 'MENU_RECOMMEND_THUMB',
        'menuMain' => 'MENU_MAIN',
        'menuUserPost' => 'MENU_USER_POST',
        // 画像コード(店舗)
        'storeMain' => 'STORE_MAIN',
        'storeThumb' => 'STORE_THUMB',
        'storeUserpost' => 'STORE_USER_POST',
        // 画像コード(ストーリー)
        'storyThumb' => 'STORY_THUBM',
        'foodLogo' => 'FOOD_LOGO',
        'restaurantLogo' => 'RESTAURANT_LOGO',
    ],
    'externalApiCd' => [
        'ebica' => 'EBICA'
    ],
    'reservationStatus' => [
        'reserve' => ['key' => 'RESERVE', 'value' => '申込'],
        'ensure' => ['key' => 'ENSURE', 'value' => '受注確定'],
        'cancel' => ['key' => 'CANCEL', 'value' => 'キャンセル'],
    ],
    'paymentStatus' => [
        'unpaid' => ['key' => 'UNPAID', 'value' => '未入金'],
        'auth' => ['key' => 'AUTH', 'value' => '与信'],
        'cancel' => ['key' => 'CANCEL', 'value' => '与信キャンセル'],
        'payed' => ['key' => 'PAYED', 'value' => '計上'],
        'wait_refund' => ['key' => 'WAIT_REFUND', 'value' => '返金待ち'],
        'refunded' => ['key' => 'REFUNDED', 'value' => '返金済み'],
        'wait_payment' => ['key' => 'WAIT_PAYMENT', 'value' => '決済待ち'],
        'no_refund' => ['key' => 'NO_REFUND', 'value' => '返金なし'],
    ],
    'refundStatus' => [
        'scheduled' => ['key' => 'SCHEDULED', 'value' => '返金予定'],
        'refunding' => ['key' => 'REFUNDING', 'value' => '返金要求'],
        'refunded' => ['key' => 'REFUNDED', 'value' => '返金済み'],
    ],
    'serviceCd' => 'gm',
    'gmServiceCd' => [
        'to' => 'TO',
        'rs' => 'RS',
        'tors' => 'TORS',
    ],
    'gender' => [
        'MAN' => ['code' => 'MAN', 'name_jp' => '男'],
        'WOMAN' => ['code' => 'WOMAN', 'name_jp' => '女'],
        'BOTH' => ['code' => '-', 'name_jp' => '両方'],
    ],
    'reservationNecessity' => [
        'reservationYes' => 'RESERVATION_YES',
        'reservationNo' => 'RESERVATION_NO',
        'reservationAlways' => 'RESERVATION_ALWAYS',
    ],
    'cardTypes' => [
        'visa' => 'VISA',
        'master' => 'MASTER',
        'jcb' => 'JCB',
        'amex' => 'AMEX',
        'diners' => 'DINERS',
        'other' => 'OTHER',
    ],
    'digitalMoneyTypes' => [
        'suica' => 'SUICA',
        'nanaco' => 'NANACO',
        'waon' => 'WAON',
        'id' => 'ID',
        'quicpay' => 'QUICPAY',
        'edy' => 'EDY',
        'paypay' => 'PAYPAY',
        'merpay' => 'MERPAY',
        'other' => 'OTHER',
    ],
    'privateRoomTypes' => [
        '2People' => '2_PEOPLE',
        '4People' => '4_PEOPLE',
        '6People' => '6_PEOPLE',
        '8People' => '8_PEOPLE',
        '10-20People' => '10-20_PEOPLE',
        '20-30People' => '20-30_PEOPLE',
        '30OverPeople' => '30_OVER_PEOPLE',
    ],
    'charterTypes' => [
        '20UnderPeopleC' => '20_UNDER_PEOPLE_C',
        '20-50PeopleC' => '20-50_PEOPLE_C',
        '50PeopleC' => '50_PEOPLE_C',
    ],
    'smokingTypes' => [
        'allOk' => 'ALL_OK',
        'separate' => 'SEPARATE',
        'noSmoking' => 'NO_SMOKING',
    ],
    'openingHourCd' => [
        'morning' => 'MORNING',
        'daytime' => 'DAYTIME',
        'night' => 'NIGHT',
        'all_day' => 'ALL_DAY',
    ],
    'faxStatus' => [
        'ready' => 'READY',
        'delivered' => 'DELIVERED',
        'failed' => 'FAILED',
        'retry' => 'RETRY',
        'cancel' => 'CANCEL',
    ],
    'maintenances' => [
        'type' => [
            'stopSale' => 'STOP_SALE',
            'stopEcon' => 'STOP_ECON',
        ],
    ],
    'tmpReservationStatus' => [
        'complete' => 'COMPLETE',
        'fail_reserve' => 'FAIL_RESERVE',
        'in_process' => 'IN_PROCESS',
    ],
    'cancelPolicy' => [
        'visit' => [
            'after' => 'AFTER',
            'before' => 'BEFORE',
        ],
        'cancel_limit_unit' => [
            'day' => 'DAY',
            'time' => 'TIME',
        ],
        'cancel_fee_unit' => [
            'fixedRate' => 'FIXED_RATE',
            'flatRate' => 'FLAT_RATE'
        ],
        'fraction_round' => [
            'roundUp' => 'ROUND_UP',
            'roundDown' => 'ROUND_DOWN'
        ],
    ],
    'skyticketPayment' => [
        'progress' => [
            'AUTHORIZED' => [
                'progress' => 2,
                'progressName' => '与信',
            ],
            'CAPTURED' => [
                'progress' => 3,
                'progressName' => '計上',
            ],
            'CANCELLED' => [
                'progress' => 4,
                'progressName' => 'キャンセル',
            ],
        ],
    ]
];
