<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\DishUpLoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DishUpLoginRequestTest extends TestCase
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
        $request  = new DishUpLoginRequest();
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
        $request  = new DishUpLoginRequest();
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
                    'userName' => '',
                    'password' => '',
                    'isRemember' => '',
                    'rememberToken' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'userName' => ['remember tokenを指定しない場合は、user nameを指定してください。'],
                    'password' => ['remember tokenを指定しない場合は、passwordを指定してください。'],
                    'rememberToken' => ['user nameを指定しない場合は、remember tokenを指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'userName' => 'test-username123',
                    'password' => 'test-password123',
                    'isRemember' => true,
                    'rememberToken' => 'tst-remember-token123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-without' => [
                // テスト条件
                [
                    'userName' => 'test',
                    'password' => '',
                    'isRemember' => '',
                    'rememberToken' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'password' => ['remember tokenを指定しない場合は、passwordを指定してください。'],
                    'rememberToken' => ['passwordを指定しない場合は、remember tokenを指定してください。'],
                ],
            ],
            'error-without2' => [
                // テスト条件
                [
                    'userName' => '',
                    'password' => 'test',
                    'isRemember' => '',
                    'rememberToken' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'userName' => ['remember tokenを指定しない場合は、user nameを指定してください。'],
                    'rememberToken' => ['user nameを指定しない場合は、remember tokenを指定してください。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'userName' => 123,
                    'password' => 123,
                    'isRemember' => true,
                    'rememberToken' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'userName' => ['user nameは文字列を指定してください。'],
                    'password' => ['passwordは文字列を指定してください。'],
                    'rememberToken' => ['remember tokenは文字列を指定してください。'],
                ],
            ],
            'error-notBoolean' => [
                // テスト条件
                [
                    'userName' => 'test-username123',
                    'password' => 'test-password123',
                    'isRemember' => '123',                          // 文字列
                    'rememberToken' => 'tst-remember-token123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRemember' => ['is rememberは、trueかfalseを指定してください。'],
                ],
            ],
            'error-notBoolean2' => [
                // テスト条件
                [
                    'userName' => 'test-username123',
                    'password' => 'test-password123',
                    'isRemember' => 2,                              // 0・1以外の数字
                    'rememberToken' => 'tst-remember-token123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRemember' => ['is rememberは、trueかfalseを指定してください。'],
                ],
            ],
            'error-notBoolean3' => [
                // テスト条件
                [
                    'userName' => 'test-username123',
                    'password' => 'test-password123',
                    'isRemember' => 'あああああ',                   // 全角文字
                    'rememberToken' => 'tst-remember-token123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRemember' => ['is rememberは、trueかfalseを指定してください。'],
                ],
            ],
            'success-boolean' => [
                // テスト条件
                [
                    'userName' => 'test-username123',
                    'password' => 'test-password123',
                    'isRemember' => false,
                    'rememberToken' => 'tst-remember-token123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
