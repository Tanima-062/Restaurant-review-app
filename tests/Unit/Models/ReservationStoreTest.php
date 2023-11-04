<?php

namespace Tests\Unit\Models;

use App\Models\CancelFee;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationStoreTest extends TestCase
{
    private $reservationStore;
    private $testStoreId;
    private $testMenuId;
    private $testReservationId;
    private $testReservationSoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservationStore = new ReservationStore();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStore()
    {
        $this->_createReservation('RS', false, true);

        $testStoreId = $this->testStoreId;
        $result = $this->reservationStore::whereHas('store', function ($query) use ($testStoreId) {
            $query->where('id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationSoreId, $result[0]['id']);
    }

    public function testReservatione()
    {
        $this->_createReservation('RS', false, true);

        $testReservationId = $this->testReservationId;
        $result = $this->reservationStore::whereHas('reservation', function ($query) use ($testReservationId) {
            $query->where('id', $testReservationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationSoreId, $result[0]['id']);
    }

    public function testCancelFeesPublished()
    {
        $this->_createReservation('RS', false, true, true);

        $testStoreId = $this->testStoreId;
        $result = $this->reservationStore::whereHas('cancelFeesPublished', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationSoreId, $result[0]['id']);
    }

    public function testSaveTakeout()
    {
        $this->_createReservation('TO', true);

        $store = Store::find($this->testStoreId);
        $reservation = Reservation::find($this->testReservationId);

        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), ['count' => 2]);
        $menus = [
            [
                'menu' => $menu,
                'options' => null,
            ],
        ];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => $menus,
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;
        $menuInfo[$this->testMenuId]['store'] = $store->toArray();

        // reservationStore insert
        $this->reservationStore->saveTakeout($info, $reservation, $menuInfo);

        $reservationStore = $this->reservationStore::where('reservation_id', $this->testReservationId)->get();
        $this->assertSame(1, $reservationStore->count());
        $this->assertSame($this->testStoreId, $reservationStore[0]['store_id']);
    }

    public function testSaveTakeoutThrowable()
    {
        $this->_createReservation('TO', true);

        $reservation = Reservation::find($this->testReservationId);

        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), ['count' => 2]);
        $menus = [
            [
                'menu' => $menu,
                'options' => null,
            ],
        ];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => $menus,
            ],
        ];
        $menuInfo = []; // 空配列にする事で、例外エラーを発生させる

        try {
            $this->reservationStore->saveTakeout($info, $reservation, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    public function testSaveRestaurant()
    {
        $this->_createReservation('RS', true);

        $store = Store::find($this->testStoreId);
        $reservation = Reservation::find($this->testReservationId);

        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId)->first()->toArray();
        $menus = [[
            'menu' => $menu,
            'options' => null,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'visitDate' => '2022-10-01',
                'visitTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;
        $menuInfo[$this->testMenuId]['store'] = $store->toArray();

        // reservationStore insert
        $this->reservationStore->saveRestaurant($info, $reservation, $menuInfo);

        $reservationStore = $this->reservationStore::where('reservation_id', $this->testReservationId)->get();
        $this->assertSame(1, $reservationStore->count());
        $this->assertSame($this->testStoreId, $reservationStore[0]['store_id']);
    }

    public function testSaveRestaurantThrowable()
    {
        $this->_createReservation('RS', true);

        $store = Store::find($this->testStoreId);
        $reservation = Reservation::find($this->testReservationId);

        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId)->first()->toArray();
        $menus = [[
            'menu' => $menu,
            'options' => null,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'visitDate' => '2022-10-01',
                'visitTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = []; // 空配列にする事で、例外エラーを発生させる

        try {
            $this->reservationStore->saveRestaurant($info, $reservation, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    private function _createReservation($appCd, $addMenu, $addReservationStore = false, $addCancelFee = false)
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;

        if ($addCancelFee) {
            $cancelFee = new CancelFee();
            $cancelFee->store_id = $this->testStoreId;
            $cancelFee->published = 1;
            $cancelFee->save();
        }

        if ($addMenu) {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = $appCd;
            $menu->save();
            $this->testMenuId = $menu->id;
        }

        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->reservation_status = 'RESERVE';
        $reservation->payment_status = 'AUTH';
        $reservation->payment_method = 'CREDIT';
        $reservation->tel = '0356785678';
        $reservation->created_at = '2022-10-02 12:00:00';
        $reservation->pick_up_datetime = '2022-10-02 15:00:00';
        $reservation->is_close = 0;
        $reservation->save();
        $this->testReservationId = $reservation->id;

        if ($addReservationStore) {
            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $this->testStoreId;
            $reservationStore->reservation_id = $this->testReservationId;
            $reservationStore->save();
            $this->testReservationSoreId = $reservationStore->id;
        }
    }
}
