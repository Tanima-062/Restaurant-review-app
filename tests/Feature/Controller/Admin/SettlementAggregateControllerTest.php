<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Menu;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class SettlementAggregateControllerTest extends TestCase
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
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementAggregate.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'allAggregate',
            'aggregate',
            'settlementCompanies',
            'partSettlementCompanies',
        ]);                                                    // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(200);
        $response->assertViewIs('admin.SettlementAggregate.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'allAggregate',
            'aggregate',
            'settlementCompanies',
            'partSettlementCompanies',
        ]);                                                    // bladeに渡している変数を確認
        $this->logout();
    }

    public function testSettlementAggregateControllerWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // target method index
        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementAggregateControllerWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompanyId);         // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementAggregateControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);               // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testSettlementAggregateControllerWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        list($store, $menu, $option) = $this->_createStoreMenu($settlementCompanyId);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex($store, $menu, $settlementCompanyId);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
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

    private function _createMenu($storeId, $published = 1, $appCd = 'TO', $lowerLimit = 1, $upperLimit = 10, $startAt = '09:00:00', $endAt = '21:00:00')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11011111';
        $menu->free_drinks = 0;
        $menu->available_number_of_lower_limit = $lowerLimit;
        $menu->available_number_of_upper_limit = $upperLimit;
        $menu->sales_lunch_start_time = $startAt;
        $menu->sales_lunch_end_time = $endAt;
        $menu->published = $published;
        $menu->save();

        $option = null;
        if ($appCd == 'TO') {
            $option = new Option();
            $option->menu_id = $menu->id;
            $option->option_cd = 'OKONOMI';
            $option->required = 1;
            $option->keyword_id = 1;
            $option->keyword = 'ご飯の量';
            $option->contents_id = 1;
            $option->contents = '多め';
            $option->price = 50;
            $option->save();
        }

        return [$menu, $option];
    }

    private function _createStoreMenu($settlementCompanyId)
    {
        $store = $this->_createStore($settlementCompanyId);
        list($menu, $option) = $this->_createMenu($store->id);
        return [$store, $menu, $option];
    }

    private function _createReservation($store, $menu, $pickUpDatetime = '2099-10-20 09:00:00', $menuPrice = 1000)
    {
        $reservation = new Reservation();
        $reservation->app_cd = $menu->app_cd;
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->persons = 2;
        $reservation->total = $menuPrice * 2;
        $reservation->reservation_status = 'RESERVE';
        $reservation->is_close = 1;
        $reservation->payment_status = 'PAYED';
        $reservation->created_at = '2099-10-01 09:00:00';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->tel = '0311112222';
        $reservation->email = 'gourmet-test1@adventure-inc.co.jp';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = $store->name;
        $reservationStore->save();

        if (!is_null($menu)) {
            $reservationMenu = new ReservationMenu();
            $reservationMenu->reservation_id = $reservation->id;
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->unit_price = $menuPrice;
            $reservationMenu->count = 2;
            $reservationMenu->price = $menuPrice * 2;
            $reservationMenu->save();

            $options = Option::where('menu_id', $menu->id)->get();
            if ($options->count() > 0) {
                foreach ($options as $option) {
                    $reservationOption = new ReservationOption();
                    $reservationOption->option_id = $option->id;
                    $reservationOption->reservation_menu_id = $reservationMenu->id;
                    $reservationOption->price = $option->price;
                    $reservationOption->count = 2;
                    $reservationOption->save();
                }
            }
        }

        $cmThApplication = new CmThApplication();
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        return $cmThApplicationDetail;
    }

    private function _callIndex($store, $menu, $settlementCompanyId = 0)
    {
        $this->_createReservation($store, $menu, '2099-10-01 09:00:00');        // 前期繰越が3000円未満になるようデータ追加
        $this->_createReservation($store, $menu, '2099-10-20 09:00:00');
        $this->_createReservation($store, $menu, '2099-10-21 09:00:00');
        return $this->get('/admin/settlement_aggregate?monthOne=1&monthTwo=2&termYear=2099&termMonth=10&settlementCompanyId=' . $settlementCompanyId);
    }
}
