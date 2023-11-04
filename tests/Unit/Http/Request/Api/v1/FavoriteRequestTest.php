<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\FavoriteRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FavoriteRequestTest extends TestCase
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
        $request  = new FavoriteRequest();
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
        $request  = new FavoriteRequest();
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
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
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'menuIds' => '',
                    'dateUndecided' => null,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateは必ず指定してください。'],
                    'pickUpTime' => ['pick up timeは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'pickUpDate' => '2099-10-01',
                    'pickUpTime' => '09:00',
                    'menuIds' => 123,
                    'dateUndecided' => null,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-requiredIf' => [
                // テスト条件
                [
                    'pickUpDate' => '',         // dateUndecided項目がboolean以外なら必須
                    'pickUpTime' => '',         // dateUndecided項目がboolean以外なら必須
                    'menuIds' => 123,
                    'dateUndecided' => 'ああああ',  // boolean以外
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateは必ず指定してください。'],
                    'pickUpTime' => ['pick up timeは必ず指定してください。'],
                ],
            ],
            'error-requiredIf2' => [
                // テスト条件
                [
                    'pickUpDate' => '',         // dateUndecided項目がboolean以外なら必須
                    'pickUpTime' => '',         // dateUndecided項目がboolean以外なら必須
                    'menuIds' => 123,
                    'dateUndecided' => 2,       // boolean以外
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateは必ず指定してください。'],
                    'pickUpTime' => ['pick up timeは必ず指定してください。'],
                ],
            ],
            'success-requiredIf' => [
                // テスト条件
                [
                    'pickUpDate' => '',         // dateUndecided項目がboolean以外なら必須
                    'pickUpTime' => '',         // dateUndecided項目がboolean以外なら必須
                    'menuIds' => 123,
                    'dateUndecided' => true,    // boolean
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-requiredIf2' => [
                // テスト条件
                [
                    'pickUpDate' => '2099-10-01',   // dateUndecided項目がboolean以外なら必須
                    'pickUpTime' => '09:00',        // dateUndecided項目がboolean以外なら必須
                    'menuIds' => 123,
                    'dateUndecided' => true,        // boolean
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'pickUpDate' => '2099/10/01',  // Y/m/d形式
                    'pickUpTime' => '09-00',       // H-i形式
                    'menuIds' => 123,
                    'dateUndecided' => true,        // boolean
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'pickUpDate' => '20991001',  // Ymd形式
                    'pickUpTime' => '0900',       // Hi形式
                    'menuIds' => 123,
                    'dateUndecided' => true,        // boolean
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'pickUpDate' => '２０９９／１０／０１',   // 全角
                    'pickUpTime' => '０９：００',           // 全角
                    'menuIds' => 123,
                    'dateUndecided' => true,              // boolean
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
        ];
    }
}
