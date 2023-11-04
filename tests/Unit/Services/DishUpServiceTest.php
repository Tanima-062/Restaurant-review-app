<?php

namespace Tests\Unit\Services;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\MailDBQueue;
use App\Models\Menu;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DishUpServiceTest extends TestCase
{
    private $dishUpService;

    public function setUp(): void
    {
        parent::setUp();
        $this->dishUpService = $this->app->make('App\Services\DishUpService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStartCooking()
    {
        // 正常(MailDBQueueにデータが登録されているかも確認する)
        list($staff, $reservation, $cmThApplicationDetail) = $this->_createReservation('RESERVE');
        $this->assertTrue($this->dishUpService->startCooking($reservation->id));
        $this->assertTrue(MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->exists());

        // 異常（キャンセル済み予約で関数呼び出し）
        list($staff, $reservation, $cmThApplicationDetail) = $this->_createReservation('CANCEL');
        $this->assertFalse($this->dishUpService->startCooking($reservation->id));
        $this->assertFalse(MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->exists());
    }

    public function testList()
    {
        list($staff, $reservation, $cmThApplicationDetail) = $this->_createReservation('RESERVE', true);    // テストデータ１
        list($staff2, $reservation2, $cmThApplicationDetail2) = $this->_createReservation('CANCEL');        // テストデータ2

        // キャンセル以外の注文取得確認
        $result = $this->dishUpService->list($staff->id, '2022-10-02');
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // 結果が１件、かつ、取得結果がテストデータ１であることを確認
        $this->assertSame($reservation->id, $result[0]['id']);
        $this->assertSame($reservation->reservationMenus()->first()->id, $result[0]['reservationMenus'][0]['id']);
        $this->assertSame($reservation->reservationMenus()->first()->reservationOptions->first()->id, $result[0]['reservationMenus'][0]['reservationOptions'][0]['id']);
    }

    private function _createReservation($reservationStatus, $addStaff = false)
    {
        $store = new Store();
        $store->save();

        $staff = null;
        if ($addStaff) {
            $staff = new Staff();
            $staff->store_id = $store->id;
            $staff->save();
        }

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'RS';
        $menu->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->price = 100;
        $option->save();

        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->total = 2200;
        $reservation->tax = 10;
        $reservation->persons = 2;
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->tel = '0612345678';
        $reservation->reservation_status = $reservationStatus;
        $reservation->payment_status = 'AUTH';
        $reservation->payment_method = 'CREDIT';
        $reservation->created_at = '2022-10-02 12:00:00';
        $reservation->pick_up_datetime = '2022-10-02 15:00:00';
        $reservation->is_close = 0;
        $reservation->save();

        $reservationStore = new reservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = 'テスト店舗';
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menu->id;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->count = 2;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->option_id = $option->id;
        $reservationOption->unit_price = 100;
        $reservationOption->count = 2;
        $reservationOption->price = 200;
        $reservationOption->save();

        $cmThApplication = new CmThApplication();
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        return [$staff, $reservation, $cmThApplicationDetail];
    }
}
