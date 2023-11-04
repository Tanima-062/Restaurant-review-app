<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\RestaurantSaveRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RestaurantSaveRequestTest extends TestCase
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
        $request  = new RestaurantSaveRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        // rules関数でrequestを使用するため、request helperにparamをセットする
        request()->merge($params);

        // テスト実施
        $request  = new RestaurantSaveRequest($params);
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
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
                    'customer' => [
                        'firstName' => '',
                        'lastName' => '',
                        'email' => '',
                        'tel' => '',
                        'request' => '',
                    ],
                    'application' => [
                        'persons' => '',
                        'menus' => [[
                            'menu' => [
                                'id' => '',
                                'count' => '',
                            ],
                            'options' => [[
                                'id' => '',
                                'keywordId' => '',
                                'contentsId' => '',
                            ]],
                        ]],
                        'visitDate' => '',
                        'visitTime' => '',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'customer.firstName' => ['customer.first nameは必ず指定してください。'],
                    'customer.lastName' => ['customer.last nameは必ず指定してください。'],
                    'customer.email' => ['customer.emailは必ず指定してください。'],
                    'customer.tel' => ['customer.telは必ず指定してください。'],
                    'application.persons' => ['application.personsは必ず指定してください。'],
                    'application.visitDate' => ['application.visit dateは必ず指定してください。'],
                    'application.visitTime' => ['application.visit timeは必ず指定してください。'],
                    'application.menus.0.menu.id' => ['application.menus.0.menu.idは必ず指定してください。'],
                    'application.menus.0.menu.count' => ['application.menus.0.menu.countは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 123,             // 半角数値
                        'lastName' => 123,              // 半角数値
                        'email' => 123,                 // 半角数値
                        'tel' => 123,                   // 半角数値
                        'request' => 123,               // 半角数値
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'customer.firstName' => ['customer.first nameは文字列を指定してください。'],
                    'customer.lastName' => ['customer.last nameは文字列を指定してください。'],
                    'customer.email' => [
                        'customer.emailは文字列を指定してください。',
                        '不正な形式のメールアドレスです。',
                    ],
                    'customer.tel' => ['customer.telは文字列を指定してください。'],
                    'customer.request' => ['customer.requestは文字列を指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => '１２３',              // 全角数字
                        'menus' => [[
                            'menu' => [
                                'id' => '１２３',           // 全角数字,
                                'count' => '１',            // 全角数字
                            ],
                            'options' => [[
                                'id' => '１２３',           // 全角数字
                                'keywordId' => '１２３',    // 全角数字
                                'contentsId' => '１２３',   // 全角数字
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.persons' => ['application.personsは整数で指定してください。'],
                    'application.menus.0.menu.id' => ['application.menus.0.menu.idは整数で指定してください。'],
                    'application.menus.0.menu.count' => ['application.menus.0.menu.countは整数で指定してください。'],
                    'application.menus.0.options.0.id' => ['application.menus.0.options.0.idは整数で指定してください。'],
                    'application.menus.0.options.0.keywordId' => ['application.menus.0.options.0.keywordIdは整数で指定してください。'],
                    'application.menus.0.options.0.contentsId' => ['application.menus.0.options.0.contentsIdは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 'persons',                 // 半角文字
                        'menus' => [[
                            'menu' => [
                                'id' => 'id',                   // 半角文字
                                'count' => 'c',                 // 半角文字
                            ],
                            'options' => [[
                                'id' => 'id',                   // 半角文字
                                'keywordId' => 'keywordId',     // 半角文字
                                'contentsId' => 'contentsId',   // 半角文字
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.persons' => ['application.personsは整数で指定してください。'],
                    'application.menus.0.menu.id' => ['application.menus.0.menu.idは整数で指定してください。'],
                    'application.menus.0.menu.count' => ['application.menus.0.menu.countは整数で指定してください。'],
                    'application.menus.0.options.0.id' => ['application.menus.0.options.0.idは整数で指定してください。'],
                    'application.menus.0.options.0.keywordId' => ['application.menus.0.options.0.keywordIdは整数で指定してください。'],
                    'application.menus.0.options.0.contentsId' => ['application.menus.0.options.0.contentsIdは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 'あああああ',                  // 全角文字
                        'menus' => [[
                            'menu' => [
                                'id' => 'いいいいい',               // 全角文字
                                'count' => 'う',                   // 全角文字
                            ],
                            'options' => [[
                                'id' => 'えええええ',               // 全角文字
                                'keywordId' => 'おおおおお',        // 全角文字
                                'contentsId' => 'あいうえお',       // 全角文字
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.persons' => ['application.personsは整数で指定してください。'],
                    'application.menus.0.menu.id' => ['application.menus.0.menu.idは整数で指定してください。'],
                    'application.menus.0.menu.count' => ['application.menus.0.menu.countは整数で指定してください。'],
                    'application.menus.0.options.0.id' => ['application.menus.0.options.0.idは整数で指定してください。'],
                    'application.menus.0.options.0.keywordId' => ['application.menus.0.options.0.keywordIdは整数で指定してください。'],
                    'application.menus.0.options.0.contentsId' => ['application.menus.0.options.0.contentsIdは整数で指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 1,       // max:1
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 2,       // max:1
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.menus.0.menu.count' => ['application.menus.0.menu.countには、1以下の数字を指定してください。'],
                ],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099/10/01',  // Y/m/d形式
                        'visitTime' => '09-00',       // H-i形式
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.visitDate' => ['application.visit dateはY-m-d形式で指定してください。'],
                    'application.visitTime' => ['application.visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '20991001',  // Ymd形式
                        'visitTime' => '0900',       // Hi形式
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.visitDate' => ['application.visit dateはY-m-d形式で指定してください。'],
                    'application.visitTime' => ['application.visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'gourmet-test1@adventure-inc.co.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '２０９９／１０／０１',   // 全角
                        'visitTime' => '０９：００',           // 全角
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'application.visitDate' => ['application.visit dateはY-m-d形式で指定してください。'],
                    'application.visitTime' => ['application.visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-withValidator' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => '@docomo.ne.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'customer.email' => ['不正な形式のメールアドレスです。'],
                ],
            ],
            'error-withValidator' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => '@docomo.ne.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'customer.email' => ['不正な形式のメールアドレスです。'],
                ],
            ],
            'success-withValidator' => [
                // テスト条件
                [
                    'customer' => [
                        'firstName' => 'テスト名',
                        'lastName' => 'テスト姓',
                        'email' => 'test@b.jp',
                        'tel' => '0311112222',
                        'request' => '卵アレルギーです。',
                    ],
                    'application' => [
                        'persons' => 2,
                        'menus' => [[
                            'menu' => [
                                'id' => 123,
                                'count' => 0,
                            ],
                            'options' => [[
                                'id' => 123,
                                'keywordId' => 1,
                                'contentsId' => 2,
                            ]],
                        ]],
                        'visitDate' => '2099-10-01',
                        'visitTime' => '09:00',
                    ],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
