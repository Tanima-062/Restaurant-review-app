<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\SettlementAggregateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SettlementAggregateRequestTest extends TestCase
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
        $request  = new SettlementAggregateRequest();
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
        $request  = new SettlementAggregateRequest();
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
                    'monthOne' => '',
                    'monthTwo' => '',
                    'termYear' => '',
                    'termMonth' => '',
                    'settlementCompanyId' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'monthOne' => 1,
                    'monthTwo' => 2,
                    'termYear' => 2022,
                    'termMonth' => 10,
                    'settlementCompanyId' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'monthOne' => '１２３４',               // 全角数字
                    'monthTwo' => '１２３４',               // 全角数字
                    'termYear' => '１２３４',               // 全角数字
                    'termMonth' => '１２３４',              // 全角数字
                    'settlementCompanyId' => '１２３４',    // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'monthOne' => ['month oneは整数で指定してください。'],
                    'monthTwo' => ['month twoは整数で指定してください。'],
                    'termYear' => ['term yearは整数で指定してください。'],
                    'termMonth' => ['term monthは整数で指定してください。'],
                    'settlementCompanyId' => ['settlement company idは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'monthOne' => 'test',               // 半角文字
                    'monthTwo' => 'test',               // 半角文字
                    'termYear' => 'test',               // 半角文字
                    'termMonth' => 'test',              // 半角文字
                    'settlementCompanyId' => 'test',    // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'monthOne' => ['month oneは整数で指定してください。'],
                    'monthTwo' => ['month twoは整数で指定してください。'],
                    'termYear' => ['term yearは整数で指定してください。'],
                    'termMonth' => ['term monthは整数で指定してください。'],
                    'settlementCompanyId' => ['settlement company idは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'monthOne' => 'あああああ',               // 全角文字
                    'monthTwo' => 'いいいいい',               // 全角文字
                    'termYear' => 'ううううう',               // 全角文字
                    'termMonth' => 'えええええ',              // 全角文字
                    'settlementCompanyId' => 'おおおおお',    // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'monthOne' => ['month oneは整数で指定してください。'],
                    'monthTwo' => ['month twoは整数で指定してください。'],
                    'termYear' => ['term yearは整数で指定してください。'],
                    'termMonth' => ['term monthは整数で指定してください。'],
                    'settlementCompanyId' => ['settlement company idは整数で指定してください。'],
                ],
            ],
        ];
    }
}
