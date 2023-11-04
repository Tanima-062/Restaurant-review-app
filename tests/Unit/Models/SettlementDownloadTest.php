<?php

namespace Tests\Unit\Models;

use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettlementDownloadTest extends TestCase
{
    private $settlementDownload;
    private $testSettlementDownloadId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->settlementDownload = new SettlementDownload();

        $this->_createSettlementDownload();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStaffAuthority()
    {
        $result = $this->settlementDownload::whereHas('settlementCompany', function ($query) {
            $query->where('settlement_companies.name', 'テスト精算会社');
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testSettlementDownloadId, $result[0]['id']);
    }

    private function _createSettlementDownload()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'テスト精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();

        $settlementDownload = new SettlementDownload();
        $settlementDownload->settlement_company_id = $settlementCompany->id;
        $settlementDownload->month = '299901';
        $settlementDownload->save();
        $this->testSettlementDownloadId = $settlementDownload->id;
    }
}
