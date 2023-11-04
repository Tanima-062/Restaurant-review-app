<?php

return [
    'bigGenre' => [
        'b-cooking' => [
            'key' => 'B-COOKING',
            'value' => '料理ジャンル',
            'word' => 'b-cooking',
        ],

        'b-detailed' => [
            'key' => 'B-DETAILED',
            'value' => 'こだわり大ジャンル',
            'word' => 'b-detailed',
        ],
    ],
    //'COOKING' => '料理ジャンル',
    //'DETAILED' => 'こだわり大ジャンル',
    //'MENU' => 'メニュー大ジャンル',
    //'SMALL_MENU' => 'メニュー小ジャンル',
    //'DETAILED' => 'こだわり大ジャンル',
    //'SMALL_DETAILED' => 'こだわり小ジャンル',
    'delegate' => [
        'normal' => [
            0 => '通常',
        ],
        'main' => [
            1 => 'メイン',
        ],
    ]
];
