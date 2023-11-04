<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\CalcPriceMenuRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CalcPriceMenuRequestTest extends TestCase
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
        $request  = new CalcPriceMenuRequest();
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
        $request  = new CalcPriceMenuRequest();
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
                    'persons' => '',
                    'reservationId' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['reservation idは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 2,
                    'reservationId' => 123,
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
                    'persons' => 2,
                    'reservationId' => 123,
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
                    'persons' => 2,
                    'reservationId' => 123,
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
                    'visitDate' => '２０９９／１０／０１',  // 全角
                    'visitTime' => '０９：００',       // 全角
                    'persons' => 2,
                    'reservationId' => 123,
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
                    'persons' => '１',              // 全角数字
                    'reservationId' => '１２３',     // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 'persons',                 // 半角文字
                    'reservationId' => 'reservationId',     // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 'あああああ',           // 全角文字
                    'reservationId' => 'いいいいい',     // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
        ];
    }
}
