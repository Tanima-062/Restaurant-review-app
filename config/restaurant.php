<?php

return [
    'tax' => 10,
    'module' => [
        'stockModule' => 'Ebica\EbicaStockSave',
        'reservationModule' => 'Ebica\EbicaReservation',
    ],
    'smokingType' => [
        'ALL_OK' => '喫煙',
        'NO_SMOKING' => '禁煙',
        'SEPARATE' => '分煙',
    ],
    'recommendApiCdn' => [
        'expiration' => '900', //秒
    ],
    'favorite' => [
        'limit' => 100,
    ],
    'ebica' => [
        'reservationStatus' => [
            'reservation' => [
                'key' => 'reservation',
                'value' => '予約中',
            ],
            'visiting' => [
                'key' => 'visiting',
                'value' => '来店中',
            ],
        ],
        'changeReservation' => [
            'force' => true,
        ],
    ],
    'mail' => [
        'from' => 'gourmet@skyticket.com',
        'devClientFrom' => 'info@skyticket.jp',
        'prdClientFrom' => 'info@skyticket.com',
    ],
];
