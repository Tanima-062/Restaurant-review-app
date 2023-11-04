<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreCancelFeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCancelFeeRequestTest extends TestCase
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
        $request  = new StoreCancelFeeRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $paramRequest = new Request();
        $paramRequest->merge($params);

        // テスト実施
        $request  = new StoreCancelFeeRequest($params);
        $rules = $request->rules($paramRequest);
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreCancelFeeRequest();
        $result = $request->attributes();
        $this->assertCount(11, $result);
        $this->assertArrayHasKey('apply_term_from', $result);
        $this->assertSame('適用開始日', $result['apply_term_from']);
        $this->assertArrayHasKey('apply_term_to', $result);
        $this->assertSame('適用終了日', $result['apply_term_to']);
        $this->assertArrayHasKey('visit', $result);
        $this->assertSame('来店前/来店後', $result['visit']);
        $this->assertArrayHasKey('cancel_limit', $result);
        $this->assertSame('期限', $result['cancel_limit']);
        $this->assertArrayHasKey('cancel_limit_unit', $result);
        $this->assertSame('期限単位', $result['cancel_limit_unit']);
        $this->assertArrayHasKey('cancel_fee', $result);
        $this->assertSame('キャンセル料', $result['cancel_fee']);
        $this->assertArrayHasKey('cancel_fee_unit', $result);
        $this->assertSame('計上単位', $result['cancel_fee_unit']);
        $this->assertArrayHasKey('fraction_unit', $result);
        $this->assertSame('端数処理', $result['fraction_unit']);
        $this->assertArrayHasKey('fraction_round', $result);
        $this->assertSame('端数処理(round)', $result['fraction_round']);
        $this->assertArrayHasKey('cancel_fee_max', $result);
        $this->assertSame('最高額', $result['cancel_fee_max']);
        $this->assertArrayHasKey('cancel_fee_min', $result);
        $this->assertSame('最低額', $result['cancel_fee_min']);
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
                    'app_cd' => '',
                    'apply_term_from' => '',
                    'apply_term_to' => '',
                    'visit' => '',
                    'cancel_limit' => '',
                    'cancel_limit_unit' => '',
                    'cancel_fee' => '',
                    'cancel_fee_unit' => '',
                    'fraction_unit' => '',
                    'fraction_round' => '',
                    'cancel_fee_max' => '',
                    'cancel_fee_min' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用コードは必ず指定してください。'],
                    'apply_term_from' => ['適用開始日は必ず指定してください。'],
                    'apply_term_to' => ['適用終了日は必ず指定してください。'],
                    'visit' => ['来店前/来店後は必ず指定してください。'],
                    'cancel_limit' => ['期限は必ず指定してください。'],
                    'cancel_limit_unit' => ['期限単位は必ず指定してください。'],
                    'cancel_fee' => ['キャンセル料は必ず指定してください。'],
                    'cancel_fee_unit' => ['計上単位は必ず指定してください。'],
                    'fraction_unit' => ['端数処理は必ず指定してください。'],
                    'fraction_round' => ['端数処理(round)は必ず指定してください。'],
                ],
            ],
            'error-empty-visitAfter' => [
                // テスト条件
                [
                    'app_cd' => '',
                    'apply_term_from' => '',
                    'apply_term_to' => '',
                    'visit' => 'AFTER',
                    'cancel_limit' => '',
                    'cancel_limit_unit' => '',
                    'cancel_fee' => '',
                    'cancel_fee_unit' => '',
                    'fraction_unit' => '',
                    'fraction_round' => '',
                    'cancel_fee_max' => '',
                    'cancel_fee_min' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用コードは必ず指定してください。'],
                    'apply_term_from' => ['適用開始日は必ず指定してください。'],
                    'apply_term_to' => ['適用終了日は必ず指定してください。'],
                    'cancel_limit_unit' => ['期限単位は必ず指定してください。'],
                    'cancel_fee' => ['キャンセル料は必ず指定してください。'],
                    'cancel_fee_unit' => ['計上単位は必ず指定してください。'],
                    'fraction_unit' => ['端数処理は必ず指定してください。'],
                    'fraction_round' => ['端数処理(round)は必ず指定してください。'],
                ],
            ],
            'error-empty-visitBefore' => [
                // テスト条件
                [
                    'app_cd' => '',
                    'apply_term_from' => '',
                    'apply_term_to' => '',
                    'visit' => 'BEFORE',
                    'cancel_limit' => '',
                    'cancel_limit_unit' => '',
                    'cancel_fee' => '',
                    'cancel_fee_unit' => '',
                    'fraction_unit' => '',
                    'fraction_round' => '',
                    'cancel_fee_max' => '',
                    'cancel_fee_min' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用コードは必ず指定してください。'],
                    'apply_term_from' => ['適用開始日は必ず指定してください。'],
                    'apply_term_to' => ['適用終了日は必ず指定してください。'],
                    'cancel_limit' => ['期限は必ず指定してください。'],
                    'cancel_limit_unit' => ['期限単位は必ず指定してください。'],
                    'cancel_fee' => ['キャンセル料は必ず指定してください。'],
                    'cancel_fee_unit' => ['計上単位は必ず指定してください。'],
                    'fraction_unit' => ['端数処理は必ず指定してください。'],
                    'fraction_round' => ['端数処理(round)は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000,
                    'cancel_fee_min' => 100,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022-01-01',  // Y-m-d形式
                    'apply_term_to' => '2999-12-31',    // Y-m-d形式
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000,
                    'cancel_fee_min' => 100,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from' => ['適用開始日はY/m/d形式で指定してください。'],
                    'apply_term_to' => ['適用終了日はY/m/d形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '20220101',    // Ymd形式
                    'apply_term_to' => '29991231',      // Ymd形式
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000,
                    'cancel_fee_min' => 100,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from' => ['適用開始日はY/m/d形式で指定してください。'],
                    'apply_term_to' => ['適用終了日はY/m/d形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '２０２２−０１−０１',    // 全角
                    'apply_term_to' => '２９９９−１２−３１',      // 全角
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000,
                    'cancel_fee_min' => 100,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from' => ['適用開始日はY/m/d形式で指定してください。'],
                    'apply_term_to' => ['適用終了日はY/m/d形式で指定してください。'],
                ],
            ],
            'error-notNumeric' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => '１２３４５',     // 全角数字
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => '１２３４５',       // 全角数字
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => '１２３４５',    // 全角数字
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => '１２３４５',   // 全角数字
                    'cancel_fee_min' => '１２３４５',   // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cancel_limit' => ['期限には、数字を指定してください。'],
                    'cancel_fee' => ['キャンセル料には、数字を指定してください。'],
                    'fraction_unit' => ['端数処理には、数字を指定してください。'],
                    'cancel_fee_max' => [
                        '最高額には、数字を指定してください。',
                        '最高額は最低額より高い金額にしてください。',
                    ],
                    'cancel_fee_min' => ['最低額には、数字を指定してください。'],
                ],
            ],
            'error-notNumeric2' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 'あああああ',     // 全角文字
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 'いいいいい',       // 全角文字
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 'ううううう',    // 全角文字
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 'えええええ',   // 全角文字
                    'cancel_fee_min' => 'おおおおお',   // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cancel_limit' => ['期限には、数字を指定してください。'],
                    'cancel_fee' => ['キャンセル料には、数字を指定してください。'],
                    'fraction_unit' => ['端数処理には、数字を指定してください。'],
                    'cancel_fee_max' => [
                        '最高額には、数字を指定してください。',
                        '最高額は最低額より高い金額にしてください。',
                    ],
                    'cancel_fee_min' => ['最低額には、数字を指定してください。'],
                ],
            ],
            'error-notNumeric3' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 'aaaaa',          // 半角文字
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 'bbbbb',            // 半角文字
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 'ccccc',         // 半角文字
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 'ddddd',        // 半角文字
                    'cancel_fee_min' => 'eeeee',        // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cancel_limit' => ['期限には、数字を指定してください。'],
                    'cancel_fee' => ['キャンセル料には、数字を指定してください。'],
                    'fraction_unit' => ['端数処理には、数字を指定してください。'],
                    'cancel_fee_max' => [
                        '最高額には、数字を指定してください。',
                        '最高額は最低額より高い金額にしてください。',
                    ],
                    'cancel_fee_min' => ['最低額には、数字を指定してください。'],
                ],
            ],
            'success-numeric' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1.0,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000.0,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1.0,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000.0,
                    'cancel_fee_min' => 100.0,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-NotBetween' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2021/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1.0,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 1000.0,
                    'cancel_fee_unit' => 'FLAT_RATE',
                    'fraction_unit' => 1.0,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000.0,
                    'cancel_fee_min' => 100.0,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from' => ['適用開始日は、適用終了日より前の時間を指定してください。'],
                ],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'app_cd' => 'TO',
                    'apply_term_from' => '2022/01/01',
                    'apply_term_to' => '2999/12/31',
                    'visit' => 'BEFORE',
                    'cancel_limit' => 1.0,
                    'cancel_limit_unit' => 'DAY',
                    'cancel_fee' => 100.1,
                    'cancel_fee_unit' => 'FIXED_RATE',
                    'fraction_unit' => 1.0,
                    'fraction_round' => 'ROUND_UP',
                    'cancel_fee_max' => 100000.0,
                    'cancel_fee_min' => 100.0,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cancel_fee' => ['定率の場合は100％以上設定できません。'],
                ],
            ],
        ];
    }
}
