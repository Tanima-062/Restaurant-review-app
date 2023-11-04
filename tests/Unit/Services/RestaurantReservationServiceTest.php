<?php

namespace Tests\Unit\Services;

use App\Models\CancelFee;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantReservationServiceTest extends TestCase
{
    private $restaurantReservationService;

    public function setUp(): void
    {
        parent::setUp();
        $this->restaurantReservationService = $this->app->make('App\Services\RestaurantReservationService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCalcCancelFee()
    {
        list($store, $menu, $option) = $this->_createStoreMenu();

        // 予約日後のキャンセル（店舗のキャンセル料金設定が未設定）
        $reservation = $this->_createReservation($store->id, $menu->id, '2022-10-01');
        $resValues = [];
        $this->assertFalse($this->restaurantReservationService->calcCancelFee($reservation->id, $resValues));
        $this->assertFalse($resValues['status']);
        $this->assertSame('該当するキャンセル料の設定がありません。', $resValues['message']);
        $this->assertNull($resValues['cancelPrice']);
        $this->assertNull($resValues['refundPrice']);

        // 予約二日前キャンセル
        $dt = Carbon::now();
        $reservation = $this->_createReservation($store->id, $menu->id, $dt->addDays(2));
        $resValues = [];
        $this->assertTrue($this->restaurantReservationService->calcCancelFee($reservation->id, $resValues));
        $this->assertTrue($resValues['status']);
        $this->assertEmpty($resValues['message']);
        $this->assertSame(2000, $resValues['cancelPrice']);
        $this->assertSame(0, $resValues['refundPrice']);
    }

    public function testCalcPriceMenu()
    {
        list($store, $menu, $option) = $this->_createStoreMenu();
        $dt = Carbon::now();
        $reservation = $this->_createReservation($store->id, $menu->id, $dt->addDays(2));
        $params = [
            'reservationId' => $reservation->id,
            'persons' => 3,
        ];
        $resValues = [];
        $this->assertTrue($this->restaurantReservationService->calcPriceMenu($params, $resValues));
        $this->assertTrue($resValues['status']);
        $this->assertEmpty($resValues['message']);
        $this->assertSame(3000, $resValues['price']);
    }

    public function testIsSalesTime()
    {
        // memo::外部API連携の処理は、連携先でのテストデータが作れないため、テスト対象外としておく
        // テストデータ取得（ランチ＆ディナー営業）
        $menu = $this->_createRestrantStore();

        // 予約が二日後の11：00：00
        // $dt = Carbon::now();         // 日付は、想定外の日（テスト結果がNGになる可能性のある日）を避けるため、実行日ではなく固定にしておく
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(11, 0, 0);
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertNull($msg);

        // 予約が二日後の19：00：00
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(19, 0, 0);
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertNull($msg);

        // 予約が二日後の20：01：00
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(20, 1, 0);
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertSame('プラン提供時間外です。', $msg); // 最終予約可能時間前かチェック

        // 予約がプラン提供時間外
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(7, 0, 0);
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertSame('プラン提供時間外です。', $msg);

        // 予約がプラン提供時間外(ラストオーダーと営業終了時間の間)
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(20, 40, 0);
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertSame('プラン提供時間外です。', $msg);

        // 予約が過去日の営業外の日(祝日）
        $dt = new Carbon('2022-11-03 07:00:00');
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertSame('プラン提供時間外です。', $msg);

        // 予約が過去日の営業外の日(平日：火曜日）
        $dt = new Carbon('2022-11-01 11:00:00');
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu, $dt, $msg));
        $this->assertSame('過去の時間は指定できません。', $msg);

        // テストデータ変更（ランチ営業のみ）
        $menu2 = $this->_createRestrantStore(true, false);

        // 予約が二日後の11：00：00
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(11, 0, 0);
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isSalesTime($menu2, $dt, $msg));
        $this->assertNull($msg);

        // 予約が二日後の営業時間外
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(15, 0, 0);
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu2, $dt, $msg));
        $this->assertSame('ランチ提供時間外です。', $msg);

        // テストデータ変更（ディナー営業のみ）
        $menu3 = $this->_createRestrantStore(false, true);

        // 予約が二日後の19：00：00
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(19, 0, 0);
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isSalesTime($menu3, $dt, $msg));
        $this->assertNull($msg);

        // 予約が二日後の営業時間外
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(11, 0, 0);
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isSalesTime($menu3, $dt, $msg));
        $this->assertSame('ディナー提供時間外です。', $msg);

        // テストデータ変更（メニューの提供時間未設定）
        $menu4 = $this->_createRestrantStore(false, false);

        // 予約が二日後の11：00：00
        $dt = new Carbon('2099-02-03');
        $dt->addDay(2)->setTime(11, 0, 0);
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isSalesTime($menu4, $dt, $msg));
        $this->assertNull($msg);
    }

    public function testIsAvailableNumber()
    {
        // テストデータ（利用可能範囲（2-5人）)
        $menu = $this->_createRestrantStore(false, true, false);

        // 利用可能
        $msg = null;
        $this->assertTrue($this->restaurantReservationService->isAvailableNumber($menu, 2, $msg));
        $this->assertNull($msg);

        // 利用不可（下限より下)
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isAvailableNumber($menu, 1, $msg));
        $this->assertSame('このコースの予約人数は2人からです。', $msg);

        // 利用不可（上限より上)
        $msg = null;
        $this->assertFalse($this->restaurantReservationService->isAvailableNumber($menu, 10, $msg));
        $this->assertSame('このコースの予約人数は5人までです。', $msg);
    }

    public function testHasRestaurantStock()
    {
        $menu = $this->_createRestrantStore(false, true, false);
        $this->_createdVacancy($menu->store_id, '2022-10-01', '10:00:00', 1, 0);
        $this->_createdVacancy($menu->store_id, '2022-10-02', '10:00:00', 0, 0);
        $this->_createdVacancy($menu->store_id, '2022-10-03', '10:00:00', 1, 1);

        // 在庫あり
        $msg = null;
        $dt = new Carbon('2022-10-01 10:00:00');
        // $this->assertFalse($this->restaurantReservationService->hasRestaurantStock($menu, $dt, 1, $msg));
        $this->assertTrue($this->restaurantReservationService->hasRestaurantStock($menu, $dt, 1, $msg));
        $this->assertNull($msg);

        // 在庫なし(vacancyテーブルのレコード自体なし)
        $msg = null;
        $dt = new Carbon('2022-10-01 17:00:00');
        $this->assertFalse($this->restaurantReservationService->hasRestaurantStock($menu, $dt, 1, $msg));
        $this->assertSame('空席がありません。', $msg);

        // 在庫なし(vacancyテーブルの対象レコードがstock=0)
        $msg = null;
        $dt = new Carbon('2022-10-02 10:00:00');
        $this->assertFalse($this->restaurantReservationService->hasRestaurantStock($menu, $dt, 1, $msg));
        $this->assertSame('空席がありません。', $msg);

        // 在庫なし(vacancyテーブルの対象レコードが販売停止)
        $msg = null;
        $dt = new Carbon('2022-10-03 10:00:00');
        $this->assertFalse($this->restaurantReservationService->hasRestaurantStock($menu, $dt, 1, $msg));
        $this->assertSame('空席がありません。', $msg);
    }

    public function testCreateInfo()
    {
        list($store, $menu, $option) = $this->_createStoreMenu();
        $dt = new Carbon('2022-10-01 10:00:00');
        $reservation = $this->_createReservationOption($store->id, $menu->id, $option->id, $dt);

        $result = $this->restaurantReservationService->createInfo($reservation, $dt, 2);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('customer', $result);
        $this->assertArrayHasKey('firstName', $result['customer']);
        $this->assertArrayHasKey('lastName', $result['customer']);
        $this->assertArrayHasKey('email', $result['customer']);
        $this->assertArrayHasKey('tel', $result['customer']);
        $this->assertArrayHasKey('request', $result['customer']);
        $this->assertArrayHasKey('application', $result);
        $this->assertArrayHasKey('persons', $result['application']);
        $this->assertArrayHasKey('visitDate', $result['application']);
        $this->assertArrayHasKey('visitTime', $result['application']);
        $this->assertArrayHasKey('menus', $result['application']);
        $this->assertCount(1, $result['application']['menus']);
        $this->assertArrayHasKey('menu', $result['application']['menus'][0]);
        $this->assertArrayHasKey('id', $result['application']['menus'][0]['menu']);
        $this->assertArrayHasKey('count', $result['application']['menus'][0]['menu']);
        $this->assertArrayHasKey('options', $result['application']['menus'][0]);
        $this->assertCount(1, $result['application']['menus'][0]['options']);
        $this->assertArrayHasKey('id', $result['application']['menus'][0]['options'][0]);
        $this->assertArrayHasKey('keywordId', $result['application']['menus'][0]['options'][0]);
        $this->assertArrayHasKey('contentsId', $result['application']['menus'][0]['options'][0]);
        $this->assertArrayHasKey('count', $result['application']['menus'][0]['options'][0]);
        $this->assertSame('太郎', $result['customer']['firstName']);
        $this->assertSame('グルメ', $result['customer']['lastName']);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result['customer']['email']);
        $this->assertSame('0311112222', $result['customer']['tel']);
        $this->assertSame('卵アレルギーです。', $result['customer']['request']);
        $this->assertSame(2, $result['application']['persons']);
        $this->assertSame('2022-10-01', $result['application']['visitDate']);
        $this->assertSame('10:00', $result['application']['visitTime']);
        $this->assertSame($menu->id, $result['application']['menus'][0]['menu']['id']);
        $this->assertSame(2, $result['application']['menus'][0]['menu']['count']);
        $this->assertSame($option->id, $result['application']['menus'][0]['options'][0]['id']);
        $this->assertSame(1, $result['application']['menus'][0]['options'][0]['keywordId']);
        $this->assertSame(1, $result['application']['menus'][0]['options'][0]['contentsId']);
        $this->assertSame(2, $result['application']['menus'][0]['options'][0]['count']);
    }

    public function testGetStoreEmails()
    {
        $store = $this->_createStore();

        $result = $this->restaurantReservationService->getStoreEmails($store->id);
        $this->assertCount(3, $result);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[1]);
        $this->assertSame('gourmet-test3@adventure-inc.co.jp', $result[2]);
    }

    private function _createStoreMenu()
    {
        $store = new Store();
        $store->save();

        $cancelFee = new CancelFee();
        $cancelFee->store_id = $store->id;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = '2022-10-01 00:00:00';
        $cancelFee->apply_term_to = '2999-12-31 23:59:59';
        $cancelFee->visit = 'BEFORE';
        $cancelFee->cancel_limit_unit = 'DAY';
        $cancelFee->cancel_limit = '1';
        $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        $cancelFee->cancel_fee = '100';
        $cancelFee->cancel_fee_max = '100000';
        $cancelFee->cancel_fee_min = '100';
        $cancelFee->fraction_unit = '1';
        $cancelFee->fraction_round = 'ROUND_UP';
        $cancelFee->published = 1;
        $cancelFee->save();

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->store_id = $store->id;
        $menu->save();

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->start_date = '2022-01-01';
        $price->end_date = '2999-12-31';
        $price->price = 1000;
        $price->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->keyword_id = 1;
        $option->contents_id = 1;
        $option->save();

        return [$store, $menu, $option];
    }

    private function _createRestrantStore($addLunchTime = true, $addDinnerTime = true, $addOpeningHour = true)
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->save();

        if ($addOpeningHour) {
            $openingHour = new OpeningHour();
            $openingHour->store_id = $store->id;
            $openingHour->week = '10111110';
            $openingHour->opening_hour_cd = 'ALL_DAY';
            $openingHour->start_at = '07:00:00';
            $openingHour->end_at = '09:00:00';
            $openingHour->last_order_time = '08:30:00';
            $openingHour->save();

            $openingHour = new OpeningHour();
            $openingHour->store_id = $store->id;
            $openingHour->week = '11011111';
            $openingHour->opening_hour_cd = 'ALL_DAY';
            $openingHour->start_at = '10:00:00';
            $openingHour->end_at = '21:00:00';
            $openingHour->last_order_time = '20:30:00';
            $openingHour->save();
        }

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->store_id = $store->id;
        if ($addLunchTime) {
            $menu->sales_lunch_start_time = '09:00:00';
            $menu->sales_lunch_end_time = '14:00:00';
        }
        if ($addDinnerTime) {
            $menu->sales_dinner_start_time = '17:00:00';
            $menu->sales_dinner_end_time = '22:00:00';
        }
        $menu->provided_time = '60';
        $menu->lower_orders_time = '60';
        $menu->available_number_of_lower_limit = 2;
        $menu->available_number_of_upper_limit = 5;
        $menu->save();

        return $menu;
    }

    private function _createReservation($storeId, $menuId, $pickUpDateTime)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->total = 2000;
        $reservation->persons = 2;
        $reservation->pick_up_datetime = $pickUpDateTime;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $storeId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id = $menuId;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->count = 2;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        return $reservation;
    }

    private function _createReservationOption($storeId, $menuId, $optionId, $pickUpDateTime)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->total = 2200;
        $reservation->persons = 2;
        $reservation->first_name = '太郎';
        $reservation->last_name = 'グルメ';
        $reservation->email = 'gourmet-test1@adventure-inc.co.jp';
        $reservation->tel = '0311112222';
        $reservation->request = '卵アレルギーです。';
        $reservation->pick_up_datetime = $pickUpDateTime;
        $reservation->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id = $menuId;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->count = 2;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->option_id = $optionId;
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->unit_price = 1000;
        $reservationOption->count = 2;
        $reservationOption->price = 2000;
        $reservationOption->save();

        return $reservation;
    }

    private function _createdVacancy($storeId, $date, $time, $stock, $isStopSale)
    {
        $vacancy = new Vacancy();
        $vacancy->store_id = $storeId;
        $vacancy->date = $date;
        $vacancy->time = $time;
        $vacancy->headcount = 1;
        $vacancy->base_stock = $stock;
        $vacancy->stock = $stock;
        $vacancy->is_stop_sale = $isStopSale;
        $vacancy->save();
    }

    private function _createStore()
    {
        $store = new Store();
        $store->email_1 = 'gourmet-test1@adventure-inc.co.jp';
        $store->email_2 = 'gourmet-test2@adventure-inc.co.jp';
        $store->email_3 = 'gourmet-test3@adventure-inc.co.jp';
        $store->save();
        return $store;
    }
}
