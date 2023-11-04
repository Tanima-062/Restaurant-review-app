<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\OpeningHour;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreOpeningHourControllerTest extends TestCase
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

        $response = $this->_callEditForm($store, $openingHour);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeOpeningHourExists',
            'storeOpeningHours',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeOpeningHourExists', true);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store, $openingHour);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeOpeningHourExists',
            'storeOpeningHours',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeOpeningHourExists', true);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEditForm($store, $openingHour);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.OpeningHour.edit');  // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'storeOpeningHourExists',
                'storeOpeningHours',
                'codes',
                'weeks',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('storeOpeningHourExists', true);
            $response->assertViewHas('codes', [
                'MORNING' => 'モーニング',
                'DAYTIME' => '昼',
                'NIGHT' => '夕',
                'ALL_DAY' => '通し営業（一日中）',
            ]);
            $response->assertViewHas('weeks', [
                '月' => '1',
                '火' => '1',
                '水' => '1',
                '木' => '1',
                '金' => '1',
                '土' => '1',
                '日' => '1',
                '祝' => '1',
            ]);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEditForm($store2, $openingHour);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store, $openingHour);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeOpeningHourExists',
            'storeOpeningHours',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeOpeningHourExists', true);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditFormNotExistsOpeningHour()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store, $openingHour, false);  // OpeningHourデータが未登録の場合を確認する
        $response->assertViewHas('storeOpeningHourExists', false);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store, $openingHour);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の営業時間を保存しました。');

        // 営業時間情報が保存されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('11111000', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('15:00:00', $result[0]['end_at']);
        $this->assertSame('DAYTIME', $result[0]['opening_hour_cd']);
        $this->assertSame('14:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111110', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:00:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store, $openingHour);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の営業時間を保存しました。');

        // 営業時間情報が保存されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('11111000', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('15:00:00', $result[0]['end_at']);
        $this->assertSame('DAYTIME', $result[0]['opening_hour_cd']);
        $this->assertSame('14:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111110', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:00:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEdit($store, $openingHour);
            $response->assertStatus(302);                                               // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
            $response->assertSessionHas('message', '「テスト店舗」の営業時間を保存しました。');

            // 営業時間情報が保存されていることを確認する
            $result = OpeningHour::where('store_id', $store->id)->get();
            $this->assertCount(2, $result);
            $this->assertSame($openingHour->id, $result[0]['id']);
            $this->assertSame('11111000', $result[0]['week']);
            $this->assertSame('09:00:00', $result[0]['start_at']);
            $this->assertSame('15:00:00', $result[0]['end_at']);
            $this->assertSame('DAYTIME', $result[0]['opening_hour_cd']);
            $this->assertSame('14:00:00', $result[0]['last_order_time']);
            $this->assertSame('11111110', $result[1]['week']);
            $this->assertSame('17:00:00', $result[1]['start_at']);
            $this->assertSame('21:00:00', $result[1]['end_at']);
            $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
            $this->assertSame('20:00:00', $result[1]['last_order_time']);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEdit($store2, $openingHour);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store, $openingHour);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の営業時間を保存しました。');

        // 営業時間情報が保存されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('11111000', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('15:00:00', $result[0]['end_at']);
        $this->assertSame('DAYTIME', $result[0]['opening_hour_cd']);
        $this->assertSame('14:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111110', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:00:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testEditWeekError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditWeekError($store, $openingHour);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHasErrors(['00000000']);                            // 入力エラーがあることを確認する

        // 営業時間情報が保存(更新)されていないことを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('21:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('20:30:00', $result[0]['last_order_time']);

        $this->logout();
    }

    public function testEditDuplication()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditDuplication($store, $openingHour);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHasErrors(['11111000']);                            // 入力エラーがあることを確認する

        // 営業時間情報が保存されていないことを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('21:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('20:30:00', $result[0]['last_order_time']);

        $this->logout();
    }

    public function testEditException()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditException($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/opening_hour/edit");   // リダイレクト先
        $response->assertSessionHas('custom_error', '「テスト店舗」の営業時間を保存できませんでした。');

        // 営業時間情報が保存されていないことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAddForm($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.OpeningHour.add');  // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'codes',
                'weeks',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('codes', [
                'MORNING' => 'モーニング',
                'DAYTIME' => '昼',
                'NIGHT' => '夕',
                'ALL_DAY' => '通し営業（一日中）',
            ]);
            $response->assertViewHas('weeks', [
                '月' => '1',
                '火' => '1',
                '水' => '1',
                '木' => '1',
                '金' => '1',
                '土' => '1',
                '日' => '1',
                '祝' => '1',
            ]);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAddForm($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.OpeningHour.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'codes',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('codes', [
            'MORNING' => 'モーニング',
            'DAYTIME' => '昼',
            'NIGHT' => '夕',
            'ALL_DAY' => '通し営業（一日中）',
        ]);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd($store, $openingHour);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」の営業時間を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/opening_hour/edit",
        ]);

        // 登録されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);          // 先に登録済みのデータに変わりがないことも確認
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('14:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('13:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111000', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:30:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAdd($store, $openingHour);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」の営業時間を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/opening_hour/edit",
        ]);

        // 登録されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);          // 先に登録済みのデータに変わりがないことも確認
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('14:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('13:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111000', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:30:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAdd($store, $openingHour);
            $response->assertStatus(200)->assertJson([
                'success' => '「テスト店舗」の営業時間を追加しました。',
                'url' =>  env('ADMIN_URL') . "/store/{$store->id}/opening_hour/edit",
            ]);

            // 登録されていることを確認する
            $result = OpeningHour::where('store_id', $store->id)->get();
            $this->assertCount(2, $result);
            $this->assertSame($openingHour->id, $result[0]['id']);          // 先に登録済みのデータに変わりがないことも確認
            $this->assertSame('10111110', $result[0]['week']);
            $this->assertSame('09:00:00', $result[0]['start_at']);
            $this->assertSame('14:00:00', $result[0]['end_at']);
            $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
            $this->assertSame('13:00:00', $result[0]['last_order_time']);
            $this->assertSame('11111000', $result[1]['week']);
            $this->assertSame('17:00:00', $result[1]['start_at']);
            $this->assertSame('21:00:00', $result[1]['end_at']);
            $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
            $this->assertSame('20:30:00', $result[1]['last_order_time']);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAdd($store2, $openingHour);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($store, $openingHour);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」の営業時間を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/opening_hour/edit",
        ]);

        // 登録されていることを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);          // 先に登録済みのデータに変わりがないことも確認
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('14:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('13:00:00', $result[0]['last_order_time']);
        $this->assertSame('11111000', $result[1]['week']);
        $this->assertSame('17:00:00', $result[1]['start_at']);
        $this->assertSame('21:00:00', $result[1]['end_at']);
        $this->assertSame('NIGHT', $result[1]['opening_hour_cd']);
        $this->assertSame('20:30:00', $result[1]['last_order_time']);

        $this->logout();
    }

    public function testAddWeekError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddWeekError($store);
        $response->assertStatus(200)->assertJson([
            'error' => ['営業曜日は、必ず一つ以上チェックしてください'],
        ]);

        // 営業時間情報が保存されていないことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testAddDuplication()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddDuplication($store, $openingHour);
        $response->assertStatus(200)->assertJson([
            'error' => ['営業曜日または、営業時間の設定が重複しています'],
        ]);

        // 営業時間情報が追加保存されていないことを確認する
        $result = OpeningHour::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($openingHour->id, $result[0]['id']);
        $this->assertSame('10111110', $result[0]['week']);
        $this->assertSame('09:00:00', $result[0]['start_at']);
        $this->assertSame('14:00:00', $result[0]['end_at']);
        $this->assertSame('ALL_DAY', $result[0]['opening_hour_cd']);
        $this->assertSame('13:00:00', $result[0]['last_order_time']);

        $this->logout();
    }

    public function testAddException()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddException($store);
        $response->assertStatus(200)->assertJson([
            'error' => ['「テスト店舗」の営業時間を追加できませんでした。'],
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/opening_hour/edit",
        ]);

        // 営業時間情報が保存されていないことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testAddLastOrderTimeError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddLastOrderTimeError($store);
        $response->assertStatus(200)->assertJson([
            'error' => ['ラストオーダーは営業開始時間と終了時間の間で設定してください。'],
        ]);

        // 営業時間情報が保存されていないことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testAddNotAjax()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddNotAjax($store);
        $response->assertStatus(200);

        // 営業時間情報が保存されていないことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 営業時間情報が削除されたことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 営業時間情報が削除されたことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、削除できること
        {
            $response = $this->_callDelete($store);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 営業時間情報が削除されたことを確認する
            $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());
        }

        // 担当外店舗の場合、削除できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2);
            $response->assertStatus(403);

            // 営業時間情報が削除されていないことを確認する
            $this->assertTrue(OpeningHour::where('store_id', $store2->id)->exists());
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

        // 営業時間情報が削除されたことを確認する
        $this->assertFalse(OpeningHour::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteNoData()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDeleteNoData($store);
        $response->assertStatus(500);

        $this->logout();
    }

    public function testStoreOpeningHourControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method editForm
        $response = $this->_callEditForm($store, $openingHour);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $openingHour);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $openingHour);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreOpeningHourControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method editForm
        $response = $this->_callEditForm($store, $openingHour);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $openingHour);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $openingHour);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store);
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

    private function _createStore($settlementCompanyId, $appCd = 'RS')
    {
        $store = new Store();
        $store->app_cd = $appCd;
        $store->code = 'test-code-test';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->area_id = 1;
        $store->published = 0;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _createOpeningHour($storeId, $startAt = '09:00', $endAt = '21:00', $lastOrderTime = '20:30')
    {
        $openingHour = new OpeningHour();
        $openingHour->store_id = $storeId;
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->week = '10111110';
        $openingHour->start_at = $startAt;
        $openingHour->end_at = $endAt;
        $openingHour->last_order_time = $lastOrderTime;
        $openingHour->save();
        return $openingHour;
    }

    private function _callEditForm($store, &$openingHour = null, $addOpeningHour = true)
    {
        if ($addOpeningHour) {
            $openingHour = $this->_createOpeningHour($store->id);
        }
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/{$store->id}/opening_hour/edit");
    }

    private function _callEdit($store, &$openingHour)
    {
        $openingHour = $this->_createOpeningHour($store->id);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url("/admin/store/{$store->id}/opening_hour/edit"),
        ])->post("/admin/store/{$store->id}/opening_hour/edit", [
            'store' => [
                [
                    'opening_hour_cd' => 'DAYTIME',
                    'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
                    'start_at' => '09:00',
                    'end_at' => '15:00',
                    'last_order_time' => '14:00',
                    'opening_hour_id' => $openingHour->id,
                ],
                [
                    'opening_hour_cd' => 'NIGHT',
                    'week' => ['1', '1', '1', '1', '1', '1', '1', '0'],
                    'start_at' => '17:00',
                    'end_at' => '21:00',
                    'last_order_time' => '20:00',
                    'opening_hour_id' => null,
                ]
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditWeekError($store, &$openingHour)
    {
        $openingHour = $this->_createOpeningHour($store->id);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url("/admin/store/{$store->id}/opening_hour/edit"),
        ])->post("/admin/store/{$store->id}/opening_hour/edit", [
            'store' => [
                [
                    'opening_hour_cd' => 'DAYTIME',
                    'week' => ['0', '0', '0', '0', '0', '0', '0', '0'],
                    'start_at' => '09:00',
                    'end_at' => '15:00',
                    'last_order_time' => '14:00',
                    'opening_hour_id' => $openingHour->id,
                ],
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditDuplication($store, &$openingHour)
    {
        $openingHour = $this->_createOpeningHour($store->id);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url("/admin/store/{$store->id}/opening_hour/edit"),
        ])->post("/admin/store/{$store->id}/opening_hour/edit", [
            'store' => [
                [
                    'opening_hour_cd' => 'DAYTIME',
                    'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
                    'start_at' => '09:00',
                    'end_at' => '15:00',
                    'last_order_time' => '14:00',
                    'opening_hour_id' => $openingHour->id,
                ],
                [
                    'opening_hour_cd' => 'NIGHT',
                    'week' => ['1', '1', '1', '1', '1', '1', '1', '0'],
                    'start_at' => '14:00',                              // 営業時間が上と重なるように設定
                    'end_at' => '21:00',
                    'last_order_time' => '20:00',
                    'opening_hour_id' => null,
                ]
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditException($store)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url("/admin/store/{$store->id}/opening_hour/edit"),
        ])->post("/admin/store/{$store->id}/opening_hour/edit", [
            'store' => [
                [
                    'opening_hour_cd' => ['DAYTIME'],                   // 例外発生させるため、文字列ではない値を渡す
                    'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
                    'start_at' => '09:00',
                    'end_at' => '15:00',
                    'last_order_time' => '14:00',
                    'opening_hour_id' => null,
                ],
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm($store)
    {
        return $this->get("/admin/store/{$store->id}/opening_hour/add");
    }

    private function _callAdd($store, &$openingHour)
    {
        $openingHour = $this->_createOpeningHour($store->id, '09:00', '14:00', '13:00');
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => 'NIGHT',
            'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
            'start_at' => '17:00',
            'end_at' => '21:00',
            'last_order_time' => '20:30',
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddWeekError($store)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => 'NIGHT',
            'week' => ['0', '0', '0', '0', '0', '0', '0', '0'],
            'start_at' => '17:00',
            'end_at' => '21:00',
            'last_order_time' => '20:30',
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddDuplication($store, &$openingHour)
    {
        $openingHour = $this->_createOpeningHour($store->id, '09:00', '14:00', '13:00');
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => 'NIGHT',
            'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
            'start_at' => '13:00',
            'end_at' => '21:00',
            'last_order_time' => '20:30',
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddException($store)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => ['NIGHT'],
            'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
            'start_at' => '13:00',
            'end_at' => '21:00',
            'last_order_time' => '20:30',
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddLastOrderTimeError($store)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => 'NIGHT',
            'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
            'start_at' => '13:00',
            'end_at' => '21:00',
            'last_order_time' => '21:01',       // 営業終了時間より後の時間に設定する
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotAjax($store)
    {
        return $this->post("/admin/store/opening_hour/add", [
            'opening_hour_cd' => 'NIGHT',
            'week' => ['1', '1', '1', '1', '1', '0', '0', '0'],
            'start_at' => '13:00',
            'end_at' => '21:00',
            'last_order_time' => '20:30',
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store)
    {
        $openingHour = $this->_createOpeningHour($store->id);
        return $this->post("/admin/store/{$store->id}/opening_hour/delete/{$openingHour->id}", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDeleteNoData($store)
    {
        return $this->post("/admin/store/{$store->id}/opening_hour/delete/1234567890", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
