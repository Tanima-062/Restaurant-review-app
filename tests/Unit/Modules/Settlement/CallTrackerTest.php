<?php

namespace Tests\Unit\Modules\Settlement;

use App\Models\CallTrackers;
use App\Models\CallTrackerLogs;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Modules\Settlement\CallTracker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CallTrackerTest extends TestCase
{
    private $callTracker;
    private $testSettlementCompanyId;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->callTracker = new CallTracker();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetCommissionByMonth()
    {
        $this->_createCallTrackerLog();

        $now = new Carbon();
        $start = Carbon::create($now->year, $now->month, 1)->firstOfMonth();
        $end = Carbon::create($now->year, $now->month, 1)->lastOfMonth();

        $result = $this->callTracker->getCommissionByMonth($start, $end, $this->testSettlementCompanyId);
        $this->assertCount(1, $result);
        $this->assertSame($this->testStoreId, $result[0]['store_id']);      // 店舗ID
        $this->assertSame('グルメtestテスト店舗', $result[0]['storeName']);    // 店舗名
        $this->assertSame(2, $result[0]['count']);                          // 件数
        $this->assertSame(1050, $result[0]['amount']);                      // 金額（90秒÷30秒×350円=1050円）
    }

    private function _createCallTrackerLog()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        $store = new Store();
        $store->name = 'グルメtestテスト店舗';
        $store->settlement_company_id = $this->testSettlementCompanyId;
        $store->save();
        $this->testStoreId = $store->id;

        $callTrackers = new CallTrackers();
        $callTrackers->store_id = $this->testStoreId;
        $callTrackers->advertiser_id = 1000;
        $callTrackers->save();

        $callTrackerLogs = new CallTrackerLogs();
        $callTrackerLogs->valid_status = 1;
        $callTrackerLogs->call_secs = 60;       // 通話時間60秒
        $callTrackerLogs->client_id = $callTrackers->advertiser_id;
        $callTrackerLogs->save();

        $callTrackerLogs = new CallTrackerLogs();
        $callTrackerLogs->valid_status = 1;
        $callTrackerLogs->call_secs = 30;       // 通話時間30秒
        $callTrackerLogs->client_id = $callTrackers->advertiser_id;
        $callTrackerLogs->save();
    }

}
