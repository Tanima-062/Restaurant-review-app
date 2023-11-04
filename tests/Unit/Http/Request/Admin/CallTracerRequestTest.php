<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\CallTracerRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CallTracerRequestTest extends TestCase
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
        $request  = new CallTracerRequest();
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
        $request  = new CallTracerRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new CallTracerRequest();
        $result = $request->attributes();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('advertiser_id', $result);
        $this->assertSame('広告主ID', $result['advertiser_id']);
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
                    'advertiser_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'advertiser_id' => ['広告主IDは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'advertiser_id' => '1',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'advertiser_id' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'advertiser_id' => ['広告主IDは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
