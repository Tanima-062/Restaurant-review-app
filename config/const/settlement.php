<?php
return [
    'payment_cycle' => [
        ['value' => 'TWICE_A_MONTH', 'label' => '月2回（15日・月末締め）', 'short' => '月2回'],
        ['value' => 'ONCE_A_MONTH', 'label' => '月1回（月末締め）', 'short' => '月1回'],
    ],

    'result_base_amount' => [
        ['value' => 'TAX_INCLUDED', 'label' => '成約金額（税込）'],
        ['value' => 'TAX_EXCLUDED', 'label' => '成約金額（税抜）'],
    ],

    'tax_calculation' => [
        ['value' => 'EXCLUSIVE', 'label' => '外税'],
        ['value' => 'INCLUSIVE', 'label' => '内税'],
    ],

    'account_type' => [
        ['value' => 'SAVINGS', 'label' => '普通'],
        ['value' => 'CURRENT', 'label' => '当座'],
    ],

    'settlement_type' => [
        ['value' => 'INVOICE', 'label' => '支払'],
        ['value' => 'RECEIPT', 'label' => '請求'],
    ],
];
