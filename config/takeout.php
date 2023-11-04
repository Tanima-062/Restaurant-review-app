<?php

return [
    'tax' => 8,
    'menu' => [
        'search' => [
            'perPage' => 20,
        ],
        'recommend' => [
            'perPage' => 5,
        ],
    ],
    'store' => [
        'search' => [
            'perPage' => 20,
        ],
    ],
    'story' => [
        'perPage' => 10,
    ],
    'api' => [
        'getStoreImage' => [
            'response' => [
                'review' => [
                    'max' => 3,
                ],
            ],
        ],
    ],
    'batch' => [
        'cacheRecommend' => [
            // おすすめで取得する上位ジャンルの数
            'numberOfGenres' => 5,
            'cache' => [
                'nameSearchApi' => 'takeoutRecommendationSearchCache',
                'nameRecommendApi' => 'takeoutRecommendationCache',
            ],
            // おすすめで取得するジャンルごとのメニュー数
            'numberOfMenusPerGenre' => 10,
        ],
    ],

    'mail' => [
        'status' => [
            'auto' => 4,
        ],
        'siteCd' => 'skyticket.com',
        'from' => 'gourmet@skyticket.com',
        'devClientFrom' => 'info@skyticket.jp',
        'prdClientFrom' => 'info@skyticket.com',
    ],

    'genreCd' => [
        'level' => [
            'b' => 1,
            'm' => 2,
            's' => 3,
            'i' => 4,
        ],
    ],

    'cancelFeeRatio' => 100, // キャンセル料率

    'apiToken' => [
        'prefix' => 'gourmetTakeoutUser',
        'expiration' => '604800', //秒 1週間
    ],
    'staffApiToken' => [
        'prefix' => 'gourmetTakeoutStaff',
        'expiration' => '604800', //秒 1週間
    ],
    'searchApiCache' => [
        'prefix' => 'gm-takeout-search-',
        // 1時間毎にキャッシュを作り直しますがバッチ処理時間を考慮して2時間にしておきます
        'expiration' => '7200', //秒
    ],
    'searchApiCdn' => [
        'expiration' => '30', //分
    ],
    'recommendApiCdn' => [
        'expiration' => '900', //秒
    ],
    'favorite' => [
        'limit' => 100,
    ],
];
