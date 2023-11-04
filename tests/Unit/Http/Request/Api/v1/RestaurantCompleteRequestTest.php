<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\RestaurantCompleteRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RestaurantCompleteRequestTest extends TestCase
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
        $request  = new RestaurantCompleteRequest();
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
        $request  = new RestaurantCompleteRequest();
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
                    'sessionToken' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sessionToken' => ['session tokenは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'sessionToken' => 'test-session_token',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'sessionToken' => 123,     // 半角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sessionToken' => ['session tokenは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
