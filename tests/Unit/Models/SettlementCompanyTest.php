<?php

namespace Tests\Unit\Models;

use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettlementCompanyTest extends TestCase
{
    private $settlementCompany;
    private $testSettlementCompanyId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->settlementCompany = new SettlementCompany();

        $this->_createSettlementCompany();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStaffAuthority()
    {
        $result = $this->settlementCompany::whereHas('stores', function ($query) {
            $query->where('stores.name', 'testテスト');
        })->where('id', $this->testSettlementCompanyId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementCompanyId, $result[0]['id']);
    }

    public function testScopeAdminSearchFilter()
    {
        // ID検索
        $valid = [
            'id' => $this->testSettlementCompanyId,
        ];
        $result = $this->settlementCompany::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementCompanyId, $result[0]['id']);

        // 名称検索
        $valid = [
            'name' => 'testテストtest精算会社',
        ];
        $result = $this->settlementCompany::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementCompanyId, $result[0]['id']);

        // 電話番号検索
        $valid = [
            'tel' => '0698765432',
        ];
        $result = $this->settlementCompany::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementCompanyId, $result[0]['id']);

        // 郵便番号検索
        $valid = [
            'postal_code' => '1111123',
        ];
        $result = $this->settlementCompany::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementCompanyId, $result[0]['id']);
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        $store = new Store();
        $store->name = 'testテスト';
        $store->settlement_company_id = $this->testSettlementCompanyId;
        $store->save();
    }
}
