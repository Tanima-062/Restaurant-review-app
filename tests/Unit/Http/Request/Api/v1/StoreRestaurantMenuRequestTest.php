<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\StoreRestaurantMenuRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRestaurantMenuRequestTest extends TestCase
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
        $request  = new StoreRestaurantMenuRequest();
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
        $request  = new StoreRestaurantMenuRequest($params);
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
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'dateUndecided' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateは必ず指定してください。'],
                    'dateUndecided' => [
                        'date undecidedは必ず指定してください。',
                        'dateUndecededには、trueかfalseを入力して下さい。',
                    ],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'visitDate' => '2099/10/01',  // Y/m/d形式
                    'visitTime' => '09-00',       // H-i形式
                    'visitPeople' => 2,
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'visitDate' => '20991001',  // Ymd形式
                    'visitTime' => '0900',       // Hi形式
                    'visitPeople' => 2,
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'visitDate' => '２０９９／１０／０１',   // 全角
                    'visitTime' => '０９：００',           // 全角
                    'visitPeople' => 2,
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-requiredIf' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '',              // dateUndecided項目が'false'なら必須
                    'visitPeople' => '',            // dateUndecided項目が'false'なら必須
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitTime' => ['date undecidedがありの場合は、visit timeも指定してください。'],
                    'visitPeople' => [
                        'date undecidedがありの場合は、visit peopleも指定してください。',
                    ],
                ],
            ],
            'success-requiredIf' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '',              // dateUndecided項目が'false'なら必須
                    'visitPeople' => '',            // dateUndecided項目が'false'なら必須
                    'dateUndecided' => 'true',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notNumeric' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => '１２３',     // 全角数字
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric2' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 'あああああ',     // 全角文字
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric3' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 'visitPeople',     // 半角文字
                    'dateUndecided' => 'false',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleには、数字を指定してください。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
                    'dateUndecided' => 123,     // 半角数値
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'dateUndecided' => [
                        'date undecidedは文字列を指定してください。',
                        'dateUndecededには、trueかfalseを入力して下さい。',
                    ],
                ],
            ],
            'error-withValidator' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
                    'dateUndecided' => 'test',     // 'true'か'false'のみOK
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'dateUndecided' => ['dateUndecededには、trueかfalseを入力して下さい。',],
                ],
            ],
        ];
    }
}
