<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuPriceRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuPriceRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAuthorize()
    {
        $request  = new MenuPriceRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        // テスト実施
        $request  = new MenuPriceRequest();
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testGetValidator()
    {
        $request  = new MenuPriceRequest();
        $this->assertNull($request->getValidator());
    }

    /**
     * データプロバイダ
     *
     * @return データプロバイダ
     *
     * @dataProvider dataprovider
     */
    public function dataprovider(): array
    {
        // testRules関数で順番にテストされる
        return [
            'error-empty' => [
                // テスト条件
                [
                    'menu' => [[
                        'price_cd' => '',
                        'price_start_date' => '',
                        'price_end_date' => '',
                        'price' => '',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu.0.price_cd' =>  ['料金コードは必ず指定してください。'],
                    'menu.0.price_start_date' =>  ['開始日は必ず指定してください。'],
                    'menu.0.price_end_date' =>  ['終了日は必ず指定してください。'],
                    'menu.0.price' =>  ['金額（税込）は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'menu' => [[
                        'price_cd' => 'NORMAL',
                        'price_start_date' => '2022-01-01',
                        'price_end_date' => '2999-12-31',
                        'price' => '100',
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notDate' => [
                // テスト条件
                [
                    'menu' => [
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => 'yyyymmdd',
                            'price_end_date' => 'yyyymmdd',
                            'price' => '100',
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => 'あああああ',
                            'price_end_date' => 'いいいいいいい',
                            'price' => '100',
                        ],
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu.0.price_start_date' =>  ['開始日には有効な日付を指定してください。'],
                    'menu.1.price_start_date' =>  ['開始日には有効な日付を指定してください。'],
                    'menu.0.price_end_date' =>  ['終了日には有効な日付を指定してください。'],
                    'menu.1.price_end_date' =>  ['終了日には有効な日付を指定してください。'],
                ],
            ],
            'success-setDateEnabled' => [
                // テスト条件
                [
                    'menu' => [
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2025-01-01',         // 開始日 = 終了日
                            'price_end_date' => '2025-01-01',
                            'price' => '100',
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2025-01-01',         // 開始日 < 終了日
                            'price_end_date' => '2025-01-02',
                            'price' => '100',
                        ]
                    ],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-setDateDisabled' => [
                // テスト条件
                [
                    'menu' => [[
                        'price_cd' => 'NORMAL',
                        'price_start_date' => '2025-01-01',
                        'price_end_date' => '2022-12-31',
                        'price' => '100',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu.0.price_end_date' =>  ['終了日には、開始日以降の日付を指定してください。'],
                ],
            ],
            'success-regex' => [
                // テスト条件
                [
                    'menu' => [
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => '1',                     // min:1桁
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => '12345',
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => '34567890',              // max:8桁
                        ],
                    ],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'menu' => [
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => '123456789',             // 8桁より多い数字
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => 'ああああああ',              // 数値以外
                        ],
                        [
                            'price_cd' => 'NORMAL',
                            'price_start_date' => '2022-01-01',
                            'price_end_date' => '2999-12-31',
                            'price' => 'aaaaaaa',               // 数値以外
                        ],
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu.0.price' =>  ['金額（税込）に正しい形式を指定してください。'],
                    'menu.1.price' =>  ['金額（税込）に正しい形式を指定してください。'],
                    'menu.2.price' =>  ['金額（税込）に正しい形式を指定してください。'],
                ],
            ],
        ];
    }
}
