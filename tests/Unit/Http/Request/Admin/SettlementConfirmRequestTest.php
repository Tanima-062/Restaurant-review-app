<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\SettlementConfirmRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementConfirmRequestTest extends TestCase
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
        $request  = new SettlementConfirmRequest();
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
        $request  = new SettlementConfirmRequest();
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
            'success-empty' => [
                // テスト条件
                [
                    'settlementCompanyName' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'settlementCompanyName' => 'テスト精算会社名',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'settlementCompanyName' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlementCompanyName' => ['settlement company nameは文字列を指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'settlementCompanyName' => Str::random(128),    // max:128
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'settlementCompanyName' => Str::random(129),    // max:128
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlementCompanyName' => ['settlement company nameは、128文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
