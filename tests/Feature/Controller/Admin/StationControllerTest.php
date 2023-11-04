<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StationControllerTest extends TestCase
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

    public function testIndex()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Station.index');       // 指定bladeを確認
        $response->assertViewHasAll(['stations']);            // bladeに渡している変数を確認

        $this->logout();
    }

    public function testNowStatus()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 前回のCSVインポートが終了している場合
        $response = $this->_callNowStatus();
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/station');        // リダイレクト先
        $response->assertSessionHas('message', '前回のcsvインポートは終了しています。');

        $this->logout();
    }

    public function testStationControllerWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method nowStatus
        $response = $this->_callNowStatus();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStationControllerWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method nowStatus
        $response = $this->_callNowStatus();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStationControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method nowStatus
        $response = $this->_callNowStatus();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStationControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method nowStatus
        $response = $this->_callNowStatus();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStationControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $this->loginWithSettlementAdministrator($settlementCompanyId);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method nowStatus
        $response = $this->_callNowStatus();
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStore($settlementCompanyId)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->published = 1;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _callIndex()
    {
        return $this->get('/admin/station');
    }

    private function _callNowStatus()
    {
        return $this->get('/admin/station/status');
    }
}
