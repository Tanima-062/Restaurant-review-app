<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\DishUpListRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DishUpListRequestTest extends TestCase
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
        $request  = new DishUpListRequest();
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
        $request  = new DishUpListRequest();
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
                    'reservationDate' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationDate' => ['reservation dateは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'reservationDate' => '2099-10-01',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'reservationDate' => '2099/10/01',  // Y/m/d形式
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationDate' => ['reservation dateはY-m-d形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'reservationDate' => '20991001',  // Ymd形式
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationDate' => ['reservation dateはY-m-d形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'reservationDate' => '２０９９／１０／０１',  // 全角
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationDate' => ['reservation dateはY-m-d形式で指定してください。'],
                ],
            ],
        ];
    }
}
