<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\TakeoutCompleteRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TakeoutCompleteRequestTest extends TestCase
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
        $request  = new TakeoutCompleteRequest();
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
        $request  = new TakeoutCompleteRequest();
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
                    'cd3secResFlg' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sessionToken' => ['session tokenは必ず指定してください。'],
                    'cd3secResFlg' => ['cd3sec res flgは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'sessionToken' => '123456',
                    'cd3secResFlg' => 123,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'sessionToken' => 123,
                    'cd3secResFlg' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sessionToken' => ['session tokenは文字列を指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'sessionToken' => '123456',
                    'cd3secResFlg' => '１２３４',         // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cd3secResFlg' => ['cd3sec res flgは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'sessionToken' => '123456',
                    'cd3secResFlg' => 'cd3secResFlg',          // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cd3secResFlg' => ['cd3sec res flgは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'sessionToken' => '123456',
                    'cd3secResFlg' => 'あああああ',           // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cd3secResFlg' => ['cd3sec res flgは整数で指定してください。'],
                ],
            ],
        ];
    }
}
