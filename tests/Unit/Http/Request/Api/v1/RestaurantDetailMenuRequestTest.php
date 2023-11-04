<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\RestaurantDetailMenuRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RestaurantDetailMenuRequestTest extends TestCase
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
        $request  = new RestaurantDetailMenuRequest();
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
        $request  = new RestaurantDetailMenuRequest();
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
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateは必ず指定してください。'],
                    'visitTime' => ['visit timeは必ず指定してください。'],
                    'visitPeople' => ['visit peopleは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
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
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => '１２３',     // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 'visitPeople',     // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 'あああああ',     // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                ],
            ],
        ];
    }
}
