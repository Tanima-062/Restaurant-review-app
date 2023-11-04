<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\AuthMypageRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuthMypageRequestTest extends TestCase
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
        $request  = new AuthMypageRequest();
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
        $request  = new AuthMypageRequest();
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
                    'reservationNo' => '',
                    'tel' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationNo' => ['reservation noは必ず指定してください。'],
                    'tel' => ['telは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'tel' => '0311112222',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'reservationNo' => 123,
                    'tel' => 0311112222,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationNo' => ['reservation noは文字列を指定してください。'],
                    'tel' => ['telは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
