<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\CallTracerRequest;
use App\Http\Requests\Admin\TelSupportRequest;
use App\Models\CallTrackers;
use App\Models\ExternalApi;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\TelSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Feature\Controller\Admin\TestCase;

class StoreApiControllerTest extends TestCase
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

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Api.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'externalApi',
            'externalApiCd',
            'callTracker',
            'telSupport',
            'hasTelSupport',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('externalApiCd', ['ebica' => 'EBICA']);
        $response->assertViewHas('hasTelSupport', [1 => '要', 0 => '不要',]);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Api.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'externalApi',
            'externalApiCd',
            'callTracker',
            'telSupport',
            'hasTelSupport',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('externalApiCd', ['ebica' => 'EBICA']);
        $response->assertViewHas('hasTelSupport', [1 => '要', 0 => '不要',]);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Api.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'externalApi',
            'externalApiCd',
            'callTracker',
            'telSupport',
            'hasTelSupport',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('externalApiCd', ['ebica' => 'EBICA']);
        $response->assertViewHas('hasTelSupport', [1 => '要', 0 => '不要',]);

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Api.edit');        // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'externalApi',
            'externalApiCd',
            'callTracker',
            'telSupport',
            'hasTelSupport',
        ]);                                                     // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('externalApiCd', ['ebica' => 'EBICA']);
        $response->assertViewHas('hasTelSupport', [1 => '要', 0 => '不要',]);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = ExternalApi::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('12345', $result[0]['api_cd']);
        $this->assertSame(67890, $result[0]['api_store_id']);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = ExternalApi::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('12345', $result[0]['api_cd']);
        $this->assertSame(67890, $result[0]['api_store_id']);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = ExternalApi::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('12345', $result[0]['api_cd']);
        $this->assertSame(67890, $result[0]['api_store_id']);

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = ExternalApi::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('12345', $result[0]['api_cd']);
        $this->assertSame(67890, $result[0]['api_store_id']);

        $this->logout();
    }

    public function testEditThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditThrowable($store);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', '更新に失敗しました。');

        $this->assertFalse(ExternalApi::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(ExternalApi::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(ExternalApi::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗のAPI設定を削除できることを確認
        {
            $response = $this->_callDelete($store);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 削除されていることを確認する
            $this->assertFalse(ExternalApi::where('store_id', $store->id)->exists());
        }

        // 担当外店舗のAPI設定を削除できないことを確認
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2);
            $response->assertStatus(500);

            // 削除されていないことを確認する
            $this->assertTrue(ExternalApi::where('store_id', $store2->id)->exists());
        }

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(ExternalApi::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCallEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $advertiserId = Str::random(15);
        $response = $this->_callCallEdit($store, $advertiserId);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = CallTrackers::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($advertiserId, $result[0]['advertiser_id']);

        $this->logout();
    }

    public function testCallEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $advertiserId = Str::random(15);
        $response = $this->_callCallEdit($store, $advertiserId);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = CallTrackers::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($advertiserId, $result[0]['advertiser_id']);

        $this->logout();
    }

    public function testCallEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗のAPI設定を操作できることを確認
        {
            $advertiserId = Str::random(15);
            $response = $this->_callCallEdit($store, $advertiserId);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
            $response->assertSessionHas('message', '更新しました。');

            $result = CallTrackers::where('store_id', $store->id)->get();
            $this->assertCount(1, $result);
            $this->assertSame($advertiserId, $result[0]['advertiser_id']);
        }

        // 担当外店舗のAPI設定を操作できないことを確認
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $advertiserId = Str::random(15);
            $response = $this->_callCallEdit($store2, $advertiserId);
            $response->assertStatus(403);

            $this->assertFalse(CallTrackers::where('store_id', $store2->id)->exists());
        }

        $this->logout();
    }

    public function testCallEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $advertiserId = Str::random(15);
        $response = $this->_callCallEdit($store, $advertiserId);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        $result = CallTrackers::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($advertiserId, $result[0]['advertiser_id']);

        $this->logout();
    }

    public function testCallEditThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // CallTracerRequestのinput関数呼び出しで例外発生させるようにする
        $callTracerRequest = \Mockery::mock(CallTracerRequest::class)->makePartial();
        $callTracerRequest->shouldReceive('input')->andThrow(new \Exception());
        $this->app->instance(CallTracerRequest::class, $callTracerRequest);

        $advertiserId = Str::random(15);
        $response = $this->_callCallEdit($store, $advertiserId);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', '更新に失敗しました。');

        $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCallDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCallDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCallDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callCallDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCallDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗のAPI設定を操作できることを確認
        {
            $response = $this->_callCallDelete($store);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 削除されていることを確認する
            $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());
        }

        // 担当外店舗のAPI設定を操作できないことを確認(try-catchのThorewableに入る)
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);

            $response = $this->_callCallDelete($store2);
            $response->assertStatus(500);

            // 削除されていないことを確認する
            $this->assertTrue(CallTrackers::where('store_id', $store2->id)->exists());
        }

        $this->logout();
    }

    public function testCallDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callCallDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 削除されていることを確認する
        $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testTelSupportEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callTelSupportEdit($store);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // 登録されていることを確認する
        $result = TelSupport::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['is_tel_support']);

        $this->logout();
    }

    public function testTelSupportEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callTelSupportEdit($store);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // 登録されていることを確認する
        $result = TelSupport::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['is_tel_support']);

        $this->logout();
    }

    public function testTelSupportEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗のAPI設定を操作できることを確認
        {
            $response = $this->_callTelSupportEdit($store);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
            $response->assertSessionHas('message', '更新しました。');

            // 登録されていることを確認する
            $result = TelSupport::where('store_id', $store->id)->get();
            $this->assertCount(1, $result);
            $this->assertSame(1, $result[0]['is_tel_support']);
        }

        // 担当外店舗のAPI設定を操作できないことを確認
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);

            $response = $this->_callTelSupportEdit($store2);
            $response->assertStatus(403);

            // 登録されていないことを確認する
            $this->assertFalse(TelSupport::where('store_id', $store2->id)->exists());
        }

        $this->logout();
    }

    public function testTelSupportEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callTelSupportEdit($store);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // 登録されていることを確認する
        $result = TelSupport::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['is_tel_support']);

        $this->logout();
    }

    public function testTelSupportEditThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // CallTracerRequestのinput関数呼び出しで例外発生させるようにする
        $telSupportRequest = \Mockery::mock(TelSupportRequest::class)->makePartial();
        $telSupportRequest->shouldReceive('input')->andThrow(new \Exception());
        $this->app->instance(TelSupportRequest::class, $telSupportRequest);

        $response = $this->_callTelSupportEdit($store);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/store/' . $store->id . '/api/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', '更新に失敗しました。');

        $this->assertFalse(CallTrackers::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testStoreApiControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        // target method callDelete
        $response = $this->_callCallDelete($store);
        $response->assertStatus(404);

        // target method telSupportEdit
        $response = $this->_callTelSupportEdit($store);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreApiControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        // target method callDelete
        $response = $this->_callCallDelete($store);
        $response->assertStatus(404);

        // target method telSupportEdit
        $response = $this->_callTelSupportEdit($store);
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

    private function _createExternalApi($storeId, $apiCd = '12345', $apiStoreId = '67890')
    {
        $externalApi = new ExternalApi();
        $externalApi->store_id = $storeId;
        $externalApi->api_cd = $apiCd;
        $externalApi->api_store_id = $apiStoreId;
        $externalApi->save();
    }

    private function _createCallTrackers($storeId, $advertiserId)
    {
        $callTrackers = new CallTrackers();
        $callTrackers->store_id = $storeId;
        $callTrackers->advertiser_id = $advertiserId;
        $callTrackers->save();
    }

    private function _callEditForm($store)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get('/admin/store/' . $store->id . '/api/edit');
    }

    private function _callEdit($store)
    {
        return $this->post('/admin/store/' . $store->id . '/api/edit', [
            'api_cd' => '12345',
            'api_store_id' => '67890',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditThrowable($store)
    {
        return $this->post('/admin/store/' . $store->id . '/api/edit', [
            'api_cd' => ['12345'],
            'api_store_id' => '67890',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store)
    {
        $externalApi = $this->_createExternalApi($store->id);
        return $this->post('/admin/store/' . $store->id . '/api/delete', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCallEdit($store, $advertiserId)
    {
        return $this->post('/admin/store/' . $store->id . '/call_tracker/edit', [
            'advertiser_id' => $advertiserId,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCallDelete($store)
    {
        $advertiserId = Str::random(15);
        $callTrackers = $this->_createCallTrackers($store->id, $advertiserId);
        return $this->post('/admin/store/' . $store->id . '/call_tracker/delete', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callTelSupportEdit($store)
    {
        return $this->post('/admin/store/' . $store->id . '/tel_support/edit', [
            'tel_support' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
