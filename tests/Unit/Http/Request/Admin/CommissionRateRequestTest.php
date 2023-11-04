<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\CommissionRateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CommissionRateRequestTest extends TestCase
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
        $request  = new CommissionRateRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $request  = new CommissionRateRequest([], $params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testMessage()
    {
        $request  = new CommissionRateRequest();
        $result = $request->messages();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('fee.regex', $result);
        $this->assertSame(':attributeは正の整数または小数(小数点以下第1位まで)を入力してください。', $result['fee.regex']);
    }

    public function testAttributes()
    {
        $request  = new CommissionRateRequest();
        $result = $request->attributes();
        $this->assertCount(6, $result);
        $this->assertArrayHasKey('apply_term_from_year', $result);
        $this->assertSame('適用開始年', $result['apply_term_from_year']);
        $this->assertArrayHasKey('apply_term_from_month', $result);
        $this->assertSame('適用開始月', $result['apply_term_from_month']);
        $this->assertArrayHasKey('apply_term_to_year', $result);
        $this->assertSame('適用終了年', $result['apply_term_to_year']);
        $this->assertArrayHasKey('apply_term_to_month', $result);
        $this->assertSame('適用終了月', $result['apply_term_to_month']);
        $this->assertArrayHasKey('accounting_condition', $result);
        $this->assertSame('計上条件', $result['accounting_condition']);
        $this->assertArrayHasKey('fee', $result);
        $this->assertSame('販売手数料', $result['fee']);
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
                    'id' => '',
                    'settlement_company_id' => '',
                    'apply_term_from_year' => '',
                    'apply_term_from_month' => '',
                    'apply_term_to_year' => '',
                    'apply_term_to_month' => '',
                    'accounting_condition' => '',
                    'fee' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['settlement company idは必ず指定してください。'],
                    'apply_term_from_year' => ['適用開始年は必ず指定してください。'],
                    'apply_term_from_month' => ['適用開始月は必ず指定してください。'],
                    'apply_term_to_year' => ['適用終了年は必ず指定してください。'],
                    'apply_term_to_month' => ['適用終了月は必ず指定してください。'],
                    'accounting_condition' => ['計上条件は必ず指定してください。'],
                    'fee' => ['販売手数料は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2021',
                    'apply_term_from_month' => '1',
                    'apply_term_to_year' => '2021',
                    'apply_term_to_month' => '1',
                    'accounting_condition' => 'FIXED_RATE', // FIXED_RATE or FLAT_RATE
                    'fee' => '100.0',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2020',       // 2021~2099が正
                    'apply_term_from_month' => '0',         // 1~12が正
                    'apply_term_to_year' => '2020',         // 2021~2099が正
                    'apply_term_to_month' => '0',           // 1~12が正
                    'accounting_condition' => 'FLAT_RATE',  // FIXED_RATE or FLAT_RATE
                    'fee' => '100',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from_year' => ['適用開始年は、2021から2099の間で指定してください。'],
                    'apply_term_from_month' => ['適用開始月は、1から12の間で指定してください。'],
                    'apply_term_to_year' => ['適用終了年は、2021から2099の間で指定してください。'],
                    'apply_term_to_month' => ['適用終了月は、1から12の間で指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2021',       // 2021~2099が正
                    'apply_term_from_month' => '1',         // 1~12が正
                    'apply_term_to_year' => '2021',         // 2021~2099が正
                    'apply_term_to_month' => '1',           // 1~12が正
                    'accounting_condition' => 'FLAT_RATE',  // FIXED_RATE or FLAT_RATE
                    'fee' => '100',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2099',       // 2021~2099が正
                    'apply_term_from_month' => '11',        // 1~12が正
                    'apply_term_to_year' => '2099',         // 2021~2099が正
                    'apply_term_to_month' => '12',          // 1~12が正
                    'accounting_condition' => 'FLAT_RATE',  // FIXED_RATE or FLAT_RATE
                    'fee' => '100',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2100',       // 2021~2099が正
                    'apply_term_from_month' => '13',        // 1~12が正
                    'apply_term_to_year' => '2100',         // 2021~2099が正
                    'apply_term_to_month' => '13',          // 1~12が正
                    'accounting_condition' => 'FLAT_RATE',  // FIXED_RATE or FLAT_RATE
                    'fee' => '100.0',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'apply_term_from_year' => ['適用開始年は、2021から2099の間で指定してください。'],
                    'apply_term_from_month' => ['適用開始月は、1から12の間で指定してください。'],
                    'apply_term_to_year' => ['適用終了年は、2021から2099の間で指定してください。'],
                    'apply_term_to_month' => ['適用終了月は、1から12の間で指定してください。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2021',
                    'apply_term_from_month' => '1',
                    'apply_term_to_year' => '2021',
                    'apply_term_to_month' => '1',
                    'accounting_condition' => 123, // FIXED_RATE or FLAT_RATE
                    'fee' => '100.0',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'accounting_condition' => [
                        '計上条件は文字列を指定してください。',
                        '選択された計上条件は正しくありません。',
                    ],
                ],
            ],
            'error-notInList' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2021',
                    'apply_term_from_month' => '1',
                    'apply_term_to_year' => '2021',
                    'apply_term_to_month' => '1',
                    'accounting_condition' => 'aaaaaaa',    // FIXED_RATE or FLAT_RATE
                    'fee' => '100.0',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'accounting_condition' => ['選択された計上条件は正しくありません。'],
                ],
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'id' => '1',
                    'settlement_company_id' => '1',
                    'apply_term_from_year' => '2021',
                    'apply_term_from_month' => '1',
                    'apply_term_to_year' => '2021',
                    'apply_term_to_month' => '1',
                    'accounting_condition' => 'FIXED_RATE',    // FIXED_RATE or FLAT_RATE
                    'fee' => 'aaa100.0',
                    'app_cd' => 'TO',
                    'only_seat' => 1,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'fee' => ['販売手数料に正しい形式を指定してください。'],
                ],
            ],
        ];
    }
}
