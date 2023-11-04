<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\SettlementCompanyRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettlementCompanyRequestTest extends TestCase
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
        $request  = new SettlementCompanyRequest();
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
        $request  = new SettlementCompanyRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new SettlementCompanyRequest();
        $result = $request->attributes();
        $this->assertCount(11, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('会社名', $result['name']);
        $this->assertArrayHasKey('tel', $result);
        $this->assertSame('電話番号', $result['tel']);
        $this->assertArrayHasKey('postal_code', $result);
        $this->assertSame('郵便番号', $result['postal_code']);
        $this->assertArrayHasKey('address', $result);
        $this->assertSame('住所', $result['address']);
        $this->assertArrayHasKey('tel', $result);
        $this->assertSame('電話番号', $result['tel']);
        $this->assertArrayHasKey('bank_name', $result);
        $this->assertSame('銀行名', $result['bank_name']);
        $this->assertArrayHasKey('branch_name', $result);
        $this->assertSame('支店名', $result['branch_name']);
        $this->assertArrayHasKey('branch_number', $result);
        $this->assertSame('支店番号', $result['branch_number']);
        $this->assertArrayHasKey('account_number', $result);
        $this->assertSame('口座番号', $result['account_number']);
        $this->assertArrayHasKey('account_name_kana', $result);
        $this->assertSame('口座名義カナ', $result['account_name_kana']);
        $this->assertArrayHasKey('billing_email_1', $result);
        $this->assertSame('通知書送付先メールアドレス1', $result['billing_email_1']);
        $this->assertArrayHasKey('billing_email_2', $result);
        $this->assertSame('通知書送付先メールアドレス2', $result['billing_email_2']);
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
                    'name' => '',
                    'postal_code' => '',
                    'tel' => '',
                    'address' => '',
                    'payment_cycle' => '',
                    'result_base_amount' => '',
                    'tax_calculation' => '',
                    'bank_name' => '',
                    'branch_name' => '',
                    'branch_number' => '',
                    'account_type' => '',
                    'account_number' => '',
                    'account_name_kana' => '',
                    'billing_email_1' => '',
                    'billing_email_2' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['会社名は必ず指定してください。'],
                    'tel' => ['電話番号は必ず指定してください。'],
                    'address' => ['住所は必ず指定してください。'],
                    'payment_cycle' => ['payment cycleは必ず指定してください。'],
                    'result_base_amount' => ['result base amountは必ず指定してください。'],
                    'tax_calculation' => ['tax calculationは必ず指定してください。'],
                    'bank_name' => ['銀行名は必ず指定してください。'],
                    'branch_name' => ['支店名は必ず指定してください。'],
                    'branch_number' => ['支店番号は必ず指定してください。'],
                    'account_type' => ['account typeは必ず指定してください。'],
                    'account_number' => ['口座番号は必ず指定してください。'],
                    'account_name_kana' => ['口座名義カナは必ず指定してください。'],
                    'billing_email_1' => ['通知書送付先メールアドレス1は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'name' => 'テスト精算会社',
                    'postal_code' => '1234567',
                    'tel' => '0311112222',
                    'address' => 'テスト住所',
                    'payment_cycle' => 'TWICE_A_MONTH',
                    'result_base_amount' => 'TAX_INCLUDED',
                    'tax_calculation' => 'EXCLUSIVE',
                    'bank_name' => 'テスト銀行',
                    'branch_name' => 'テスト支店',
                    'branch_number' => '012',
                    'account_type' => 'SAVINGS',
                    'account_number' => '01234567',
                    'account_name_kana' => 'テストタロウ',
                    'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
                    'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'name' => "あアァｱ-aA0123",
                    'postal_code' => '１２３４５６７',  // 全角数字
                    'tel' => '０３１１１１２２２２',     // 全角数字
                    'address' => 'テスト住所',
                    'payment_cycle' => 'TWICE_A_MONTH',
                    'result_base_amount' => 'TAX_INCLUDED',
                    'tax_calculation' => 'EXCLUSIVE',
                    'bank_name' => 'テスト銀行',
                    'branch_name' => 'テスト支店',
                    'branch_number' => 'テスト',
                    'account_type' => 'SAVINGS',
                    'account_number' => '１２３４５６７８', // 全角数字
                    'account_name_kana' => 'テストタロウTEATTARO',
                    'billing_email_1' => 'gourmet-test1adventure-inc.co.jp',
                    'billing_email_2' => 'gourmet-test2adventure-inc.co.jp',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['会社名に正しい形式を指定してください。'],
                    'postal_code' => ['郵便番号に正しい形式を指定してください。'],
                    'tel' => ['電話番号に正しい形式を指定してください。'],
                    'branch_number' => ['支店番号に正しい形式を指定してください。'],
                    'account_number' => ['口座番号に正しい形式を指定してください。'],
                    'account_name_kana' => ['口座名義カナに正しい形式を指定してください。'],
                    'billing_email_1' => ['通知書送付先メールアドレス1に正しい形式を指定してください。'],
                    'billing_email_2' => ['通知書送付先メールアドレス2に正しい形式を指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'name' => 'テスト精算会社',
                    'postal_code' => '1234567',
                    'tel' => '03111122221',                                     // max:11桁
                    'address' => Str::random(256),                              // max:256
                    'payment_cycle' => 'TWICE_A_MONTH',
                    'result_base_amount' => 'TAX_INCLUDED',
                    'tax_calculation' => 'EXCLUSIVE',
                    'bank_name' => Str::random(128),                            // max:128
                    'branch_name' => Str::random(128),                          // max:128
                    'branch_number' => '012',
                    'account_type' => 'SAVINGS',
                    'account_number' => '012345678901234',                      // max:15桁
                    'account_name_kana' => str_repeat('テストタロウテス', 16),     // 128桁、max:128桁
                    'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
                    'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'name' => 'テスト精算会社',
                    'postal_code' => '1234567',
                    'tel' => '031111222211',                                    // 12桁、max:11桁
                    'address' => Str::random(257),
                    'payment_cycle' => 'TWICE_A_MONTH',
                    'result_base_amount' => 'TAX_INCLUDED',
                    'tax_calculation' => 'EXCLUSIVE',
                    'bank_name' => Str::random(129),                            // max:128
                    'branch_name' => Str::random(129),                          // max:128
                    'branch_number' => '012',
                    'account_type' => 'SAVINGS',
                    'account_number' => '0123456789012345',                     // 16桁、max:15桁
                    'account_name_kana' => str_repeat('テスト', 43),            // 129桁、max:128桁
                    'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
                    'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['電話番号は、11文字以下で指定してください。'],
                    'address' => ['住所は、256文字以下で指定してください。'],
                    'bank_name' => ['銀行名は、128文字以下で指定してください。'],
                    'branch_name' => ['支店名は、128文字以下で指定してください。'],
                    'account_number' => ['口座番号は、15文字以下で指定してください。'],
                    'account_name_kana' => ['口座名義カナは、128文字以下で指定してください。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'name' => 'テスト精算会社',
                    'postal_code' => '1234567',
                    'tel' => '0311112222',
                    'address' => 123,
                    'payment_cycle' => 'TWICE_A_MONTH',
                    'result_base_amount' => 'TAX_INCLUDED',
                    'tax_calculation' => 'EXCLUSIVE',
                    'bank_name' => 123,
                    'branch_name' => 123,
                    'branch_number' => '012',
                    'account_type' => 'SAVINGS',
                    'account_number' => '01234567',
                    'account_name_kana' => 'テストタロウ',
                    'billing_email_1' => 'gourmet-test1@adventure-inc.co.jp',
                    'billing_email_2' => 'gourmet-test2@adventure-inc.co.jp',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'address' => ['住所は文字列を指定してください。'],
                    'bank_name' => ['銀行名は文字列を指定してください。'],
                    'branch_name' => ['支店名は文字列を指定してください。'],
                ],
            ],
        ];
    }
}
