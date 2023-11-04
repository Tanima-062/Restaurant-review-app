<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreSearchRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreSearchRequestTest extends TestCase
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
        $request  = new StoreSearchRequest();
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
        $request  = new StoreSearchRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreSearchRequest();
        $result = $request->attributes();
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('ID', $result['id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('店舗名', $result['name']);
        $this->assertArrayHasKey('code', $result);
        $this->assertSame('店舗コード', $result['code']);
        $this->assertArrayHasKey('settlement_company_name', $result);
        $this->assertSame('精算会社名', $result['settlement_company_name']);
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
                    'id' => '',
                    'name' => '',
                    'code' => '',
                    'settlement_company_name' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => 1,
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'id' => '１',                  // 全角数字
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['IDは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'id' => 'aaa',                  // 半角文字
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['IDは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'id' => 'ああ',                 // 全角文字
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['IDは整数で指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'id' => 0,                      // min:1
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['IDには、1以上の数字を指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'id' => 1,                      // min:1
                    'name' => 'テスト店舗',
                    'code' => 'testtest123',
                    'settlement_company_name' => '精算会社名',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'id' => 123,
                    'name' => Str::random(64),                      // max:64
                    'code' => Str::random(64),                      // max:64
                    'settlement_company_name' => Str::random(64),   // max:64
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'id' => 123,
                    'name' => Str::random(65),                      // max:64
                    'code' => Str::random(65),                      // max:64
                    'settlement_company_name' => Str::random(65),   // max:64
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['店舗名は、64文字以下で指定してください。'],
                    'code' => ['店舗コードは、64文字以下で指定してください。'],
                    'settlement_company_name' => ['精算会社名は、64文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
