<?php

namespace Tests\Unit\Models;

use App\Models\CommissionRate;
use App\Models\SettlementCompany;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommissionRateTest extends TestCase
{
    private $commissionRate;
    private $testSettlementCompanyId;
    private $testCommissionRateId;
    private $testCommissionRateId2;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->commissionRate = new CommissionRate();

        $this->_createCommissionRate();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeSettlementCompanyId()
    {
        $result = $this->commissionRate::SettlementCompanyId($this->testSettlementCompanyId)->get();
        $this->assertIsObject($result);
        $this->assertCount(2, $result->toArray());
        $this->assertSame($this->testCommissionRateId, $result[0]['id']);
        $this->assertSame($this->testCommissionRateId2, $result[1]['id']);
    }

    public function testScopePolicyCheckFilter()
    {
        $request = [
            'apply_term_from_year' => '2022',
            'apply_term_from_month' => '1',
            'apply_term_to_year' => '2022',
            'apply_term_to_month' => '10',
            'settlement_company_id' => $this->testSettlementCompanyId,
            'app_cd' => 'RS',
            'only_seat' => '1',
        ];
        $result = $this->commissionRate::PolicyCheckFilter($request)->get();
        $this->assertIsObject($result);
        $this->assertCount(2, $result->toArray());
        $this->assertSame($this->testCommissionRateId, $result[0]['id']);
        $this->assertSame($this->testCommissionRateId2, $result[1]['id']);

        // 指定IDを除く
        $request['id'] = $this->testCommissionRateId;
        $result = $this->commissionRate::PolicyCheckFilter($request)->get();
        $this->assertIsObject($result);
        $this->assertCount(1, $result->toArray());
        $this->assertSame($this->testCommissionRateId2, $result[0]['id']);
    }

    public function testScopeSearchApplyRecord()
    {
        $result = $this->commissionRate::SearchApplyRecord('RS', '2022-10-01', $this->testSettlementCompanyId)->get();
        $this->assertIsObject($result);
        $this->assertCount(2, $result->toArray());
        $this->assertSame($this->testCommissionRateId, $result[0]['id']);
        $this->assertSame($this->testCommissionRateId2, $result[1]['id']);
    }

    private function _createCommissionRate()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $this->testSettlementCompanyId;
        $commissionRate->app_cd = 'RS';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2999-12-31 23:59:59';
        $commissionRate->fee = '10.0';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->only_seat = 1;
        $commissionRate->published = 1;
        $commissionRate->save();
        $this->testCommissionRateId = $commissionRate->id;

        $commissionRate = new CommissionRate();
        $commissionRate->settlement_company_id = $this->testSettlementCompanyId;
        $commissionRate->app_cd = 'RS';
        $commissionRate->apply_term_from = '2022-01-01 00:00:00';
        $commissionRate->apply_term_to = '2999-12-31 23:59:59';
        $commissionRate->fee = '10.0';
        $commissionRate->accounting_condition = 'FIXED_RATE';
        $commissionRate->only_seat = 1;
        $commissionRate->published = 1;
        $commissionRate->save();
        $this->testCommissionRateId2 = $commissionRate->id;
    }
}
