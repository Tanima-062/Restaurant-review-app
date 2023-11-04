<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreApiRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreApiRequestTest extends TestCase
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
        $request  = new StoreApiRequest();
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
        $request  = new StoreApiRequest($params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreApiRequest();
        $result = $request->attributes();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('api_cd', $result);
        $this->assertSame('接続先', $result['api_cd']);
        $this->assertArrayHasKey('api_store_id', $result);
        $this->assertSame('接続先のID', $result['api_store_id']);
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
                    'api_cd' => '',
                    'api_store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'api_cd' => ['接続先は必ず指定してください。'],
                    'api_store_id' => ['接続先のIDは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'api_cd' => 'EBICA',
                    'api_store_id' => 123,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notNumeric' => [
                // テスト条件
                [
                    'api_cd' => 'EBICA',
                    'api_store_id' => '１２３４５',   // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'api_store_id' => ['接続先のIDには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric2' => [
                // テスト条件
                [
                    'api_cd' => 'EBICA',
                    'api_store_id' => 'あああああ',   // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'api_store_id' => ['接続先のIDには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric3' => [
                // テスト条件
                [
                    'api_cd' => 'EBICA',
                    'api_store_id' => 'aaaaaa',   // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'api_store_id' => ['接続先のIDには、数字を指定してください。'],
                ],
            ],
            'success-numeric' => [
                // テスト条件
                [
                    'api_cd' => 'EBICA',
                    'api_store_id' => 1.0,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
