<?php

namespace Tests\Unit\Rules;

use App\Models\CommissionRate;
use App\Models\SettlementCompany;
use App\Rules\CommissionRatePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CommissionRatePolicyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCommissionRatePolicy()
    {
        $settlementCompany = $this->_createCommitionRate();

        // 指定期間内にポリシー登録がない場合、OK
        $params = [
            'apply_term_from_year' => 2026,
            'apply_term_from_month' => 1,
            'apply_term_to_year' => 2028,
            'apply_term_to_month' => 12,
            'app_cd' => 'RS',
            'settlement_company_id' => $settlementCompany->id,
            'only_seat' => 1,
        ];
        $validator = Validator::make(
            ['test' => 1],
            ['test' => new CommissionRatePolicy($params)]
        );
        $this->assertTrue($validator->passes());
        $this->assertCount(0, $validator->errors()->get('test'));   // エラーメッセージなし

        // 指定期間内にポリシー登録がある場合、NG
        $params = [
            'apply_term_from_year' => 2022,
            'apply_term_from_month' => 1,
            'apply_term_to_year' => 2023,
            'apply_term_to_month' => 12,
            'app_cd' => 'RS',
            'settlement_company_id' => $settlementCompany->id,
            'only_seat' => 1,
        ];
        $validator = Validator::make(
            ['test' => 1],
            ['test' => new CommissionRatePolicy($params)]
        );
        $this->assertFalse($validator->passes());
        $this->assertCount(1, $validator->errors()->get('test'));   // エラーメッセージあり
        $this->assertSame('ポリシーが重複しています。', $validator->errors()->get('test')[0]);
    }

    private function _createCommitionRate()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();

        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $settlementCompany->id;
        $commissionRate->app_cd = 'RS';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2025-12-31 23:59:59';
        $commissionRate->fee = '10.0';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->only_seat = 1;
        $commissionRate->published = 1;
        $commissionRate->save();

        return $settlementCompany;
    }
}
