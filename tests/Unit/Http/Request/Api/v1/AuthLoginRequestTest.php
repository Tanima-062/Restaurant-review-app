<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\AuthLoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuthLoginRequestTest extends TestCase
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
        $request  = new AuthLoginRequest();
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
        $request  = new AuthLoginRequest();
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
                    'loginId' => '',
                    'password' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'loginId' => ['login idは必ず指定してください。'],
                    'password' => ['passwordは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'loginId' => 'test_login-id123',
                    'password' => 'test_password-123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'loginId' => 123,
                    'password' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'loginId' => ['login idは文字列を指定してください。'],
                    'password' => ['passwordは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
