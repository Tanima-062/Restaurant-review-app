<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\CancelDetailRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class CancelDetailRequestTest extends TestCase
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
        $request  = new CancelDetailRequest();
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
        $request  = new CancelDetailRequest();
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
                    'reservation_id' => '',
                    'account_code' => '',
                    'price' => '',
                    'count' => '',
                    'remarks' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは必ず指定してください。'],
                    'account_code' => ['account codeは必ず指定してください。'],
                    'price' => ['金額（税込）は必ず指定してください。'],
                    'count' => ['countは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'reservation_id' => '1',
                    'account_code' => 'abcd_efg',
                    'price' => 3,
                    'count' => 4,
                    'remarks' => 'テスト',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'reservation_id' => '１２３４',     // 全角数字
                    'account_code' => 'abcd_efg',
                    'price' => '１２３４',              // 全角数字
                    'count' => '１２３４',              // 全角数字
                    'remarks' => 'テスト',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'price' => ['金額（税込）は整数で指定してください。'],
                    'count' => ['countは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'reservation_id' => 'reservation_id',   // 半角文字
                    'account_code' => 'abcd_efg',
                    'price' => 'price',                     // 半角文字
                    'count' => 'count',                     // 半角文字
                    'remarks' => 'テスト',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'price' => ['金額（税込）は整数で指定してください。'],
                    'count' => ['countは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'reservation_id' => 'あああああ',           // 全角文字
                    'account_code' => 'abcd_efg',
                    'price' => 'いいいいい',                    // 全角文字
                    'count' => 'ううううう',                    // 全角文字
                    'remarks' => 'えええええ',                  // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'price' => ['金額（税込）は整数で指定してください。'],
                    'count' => ['countは整数で指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'reservation_id' => '1',
                    'account_code' => '2',
                    'price' => 3,
                    'count' => 100,                     // max:100
                    'remarks' => Str::random(100),      // max:100桁
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'reservation_id' => '1',
                    'account_code' => '2',
                    'price' => 3,
                    'count' => 101,                     // max:100
                    'remarks' => Str::random(101),      // 101桁、max:100桁
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'count' => ['countには、100以下の数字を指定してください。'],
                    'remarks' => ['remarksは、100文字以下で指定してください。'],
                ],
            ],
            'success-alphaDash' => [
                // テスト条件
                [
                    'reservation_id' => '1',
                    'account_code' => 'abcd123-_efg',         // 全部アルファベット文字と数字、ダッシュ(-)、下線(_)がOK
                    'price' => '1000',
                    'count' => '10',
                    'remarks' => 'テスト',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notAlphaDash' => [
                // テスト条件
                [
                    'reservation_id' => '1',
                    'account_code' => 'abcd123-_efg*',
                    'price' => '1000',
                    'count' => '10',
                    'remarks' => 'テスト',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'account_code' => ['account codeはアルファベットとダッシュ(-)及び下線(_)がご利用できます。'],
                ],
            ],
        ];
    }
}
