<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\SettlementCompanySearchRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementCompanySearchRequestTest extends TestCase
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
        $request  = new SettlementCompanySearchRequest();
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
        $request  = new SettlementCompanySearchRequest();
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
                    'id' => '',
                    'name' => '',
                    'tel' => '',
                    'postal_code' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => 123,
                    'name' => 'テスト',
                    'tel' => '0311112222',
                    'postal_code' => '1234567',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'id' => 'aaaaa',            // 全角数字
                    'name' => 'テスト',
                    'tel' => '0311112222',
                    'postal_code' => '1234567',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'id' => 'aaaaa',            // 半角文字
                    'name' => 'テスト',
                    'tel' => '0311112222',
                    'postal_code' => '1234567',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'id' => 'あああああ',            // 全角文字
                    'name' => 'テスト',
                    'tel' => '0311112222',
                    'postal_code' => '1234567',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'id' => 123,
                    'name' => Str::random(128),
                    'tel' => '031111222233333', // 15桁
                    'postal_code' => '1234567', // 7桁
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
                    'name' => Str::random(129),
                    'tel' => '0311112222333334', // 16桁
                    'postal_code' => '12345678', // 8桁
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['nameは、128文字以下で指定してください。'],
                    'tel' => ['telは、15文字以下で指定してください。'],
                    'postal_code' => ['postal codeは、7文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
