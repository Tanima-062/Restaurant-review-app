<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\DishUpStartCookingRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DishUpStartCookingRequestTest extends TestCase
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
        $request  = new DishUpStartCookingRequest();
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
        $request  = new DishUpStartCookingRequest();
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
                    'reservationId' => 123,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'reservationId' => '１２３',     // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'reservationId' => 'reservationId',     // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'reservationId' => 'あああああ',     // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
            ],
        ];
    }
}
