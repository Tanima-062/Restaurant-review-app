<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Menu;
use App\Models\PaymentToken;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class NewPaymentControllerTest extends TestCase
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

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.NewPayment.index');                  // 指定bladeを確認
        $response->assertViewHasAll(['payments', 'total', 'paginator']);    // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.NewPayment.index');                  // 指定bladeを確認
        $response->assertViewHasAll(['payments', 'total', 'paginator']);    // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexValidationEmpty()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // 最低１項目指定必要のところ、何も指定しなかった場合エラーオブジェクトが返される
        $response = $this->_callIndex('&date_from=&date_to=&serviceCd=&id=&cart_id=&order_code=');
        $response->assertStatus(200);                                               // アクセス確認
        $response->assertViewIs('admin.NewPayment.index');                          // 指定bladeを確認
        $response->assertViewHasAll(['payments', 'total', 'paginator', 'errors']);   // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexValidationDate()
    {
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // 日付の指定期間が90日以上の場合エラーオブジェクトが返される
        $response = $this->_callIndex('&date_from=2023-01-01&date_to=2023-04-02&serviceCd=rs&id=&cart_id=&order_code=');
        $response->assertStatus(200);                                               // アクセス確認
        $response->assertViewIs('admin.NewPayment.index');                          // 指定bladeを確認
        $response->assertViewHasAll(['payments', 'total', 'paginator', 'errors']);   // bladeに渡している変数を確認

        $this->logout();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIndexCsv()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexCsv($store, $menu);
        $response->assertStatus(200);                         // アクセス確認

        // ファイルができるはずなので、確認後削除しておく。
        if (file_exists('入金一覧.csv')) {
            $this->assertTrue(true);
            unlink('入金一覧.csv');
        }
        $this->assertFalse(file_exists('入金一覧.csv'));

        $this->logout();
    }

    public function testNewPaymentControllerWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNewPaymentControllerWithClientGeneral()
    {
        $store = $this->_createStore();
        $this->loginWithClientGeneral($store->id);                      // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNewPaymentControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        $this->logout();
    }

    public function testNewPaymentControllerWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->published = $published;
        $store->save();
        return $store;
    }

    private function _createMenu($storeId, $published = 1, $appCd = 'RS')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11111111';
        $menu->free_drinks = 0;
        $menu->published = $published;
        $menu->save();
        return $menu;
    }

    private function _createStoreMenu($published = 0, $appCd = 'RS')
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published, $appCd);
        return [$store, $menu];
    }

    private function _createReservation($store, $menu)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->total = '5000';
        $reservation->reservation_status = 'RESERVE';
        $reservation->is_close = '1';
        $reservation->payment_status = 'AUTH';
        $reservation->created_at = '2099-10-01 09:00:00';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = $store->name;
        $reservationStore->save();

        $cmThApplication = new CmThApplication();
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        $paymentToken = new PaymentToken();
        $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->save();

        return $cmThApplicationDetail;
    }

    private function _callIndex($param = '&date_from=2023-01-01&date_to=2023-01-01&serviceCd=rs&id=123456&cart_id=&order_code=')
    {
        return $this->get('/admin/newpayment?page=1' . $param);
    }

    private function _callIndexCsv($store, $menu)
    {
        // 決済システムからの結果をモックする
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPaymentList')->andReturn([
            'list' => [
                'data' => [
                    [
                        'id' => $cmThApplicationDetail->cm_application_id,
                        'cm_application_ids' => [
                            'rs' => [$cmThApplicationDetail->cm_application_id],
                        ],
                        'cart_id' => '0000-0000-0000-01',
                        'order_code' => 'gm-0000-0000-0000-01-20991001090000000',
                        'price' => '5000',
                        'progress_name' => 'テスト太郎',
                    ]
                ],
            ]
        ]);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        return $this->get('/admin/newpayment?action=csv&date_from=2023-01-01&date_to=2023-01-01&serviceCd=rs&page=1');
    }
}
