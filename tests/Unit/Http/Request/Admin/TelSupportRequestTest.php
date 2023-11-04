<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\TelSupportRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TelSupportRequestTest extends TestCase
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
        $request  = new TelSupportRequest();
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
        $request  = new TelSupportRequest();
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
                    'tel_support' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel_support' => ['tel supportは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'tel_support' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
