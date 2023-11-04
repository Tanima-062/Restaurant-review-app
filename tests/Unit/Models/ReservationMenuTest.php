<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\Stock;
use App\Models\Store;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationMenuTest extends TestCase
{
    private $reservationMenu;
    private $testMenuId;    //TO用
    private $testMenuId2;   //RS用
    private $testReservationId;
    private $testReservationMenuId;
    private $testReservationOptionId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservationMenu = new ReservationMenu();

        $this->_createReservationMenu();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testReservationMenu()
    {
        $testReservationOptionId = $this->testReservationOptionId;
        $result = $this->reservationMenu::whereHas('reservationOptions', function ($query) use ($testReservationOptionId) {
            $query->where('id', $testReservationOptionId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationMenuId, $result[0]['id']);
    }

    public function testReservation()
    {
        $testReservationId = $this->testReservationId;
        $result = $this->reservationMenu::whereHas('reservation', function ($query) use ($testReservationId) {
            $query->where('id', $testReservationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationMenuId, $result[0]['id']);
    }

    public function testMenu()
    {
        $testMenuId = $this->testMenuId;
        $result = $this->reservationMenu::whereHas('menu', function ($query) use ($testMenuId) {
            $query->where('menu_id', $testMenuId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationMenuId, $result[0]['id']);
    }

    public function testSaveTakeout()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), ['count' => 2]);
        $menus = [[
            'menu' => $menu,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0], $menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;

        // 在庫なしエラー
        {
            try {
                $reservation = $this->_createReservation('TO');

                $errMsg = '';
                $result = $this->reservationMenu->saveTakeout($info, $reservation, $menuInfo, $errMsg);
            } catch (Exception $e) {
                $this->assertSame('在庫がありません。', $errMsg);
            }
        }

        // 在庫あり
        {
            $this->_createStock(10, '2022-10-01', $this->testMenuId);
            $reservation = $this->_createReservation('TO');

            $errMsg = '';
            $result = $this->reservationMenu->saveTakeout($info, $reservation, $menuInfo, $errMsg);
            $this->assertSame('', $errMsg);
            $this->assertIsArray($result);
            $reservationMenu = $this->reservationMenu::find($result[0]);
            $this->assertSame($reservation->id, $reservationMenu->reservation_id);    //データが登録されているか確認
        }
    }

    public function testSaveRestaurant()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId2)->first()->toArray();
        $menus = [[
            'menu' => $menu,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId2] = $menu;
        $menuInfo[$this->testMenuId2]['menuPrice']['price'] = 2000;

        $reservation = $this->_createReservation('RS');

        // reservationMenu insert
        $this->reservationMenu->saveRestaurant($info, $reservation, $menuInfo);
        $reservationMenu = $this->reservationMenu::where('reservation_id', $reservation->id)->get();
        $this->assertIsObject($reservationMenu);
        $this->assertSame(1, $reservationMenu->count());
        $this->assertSame($this->testMenuId2, $reservationMenu[0]['menu_id']);
    }

    public function testSaveRestaurantThrowable()
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId2)->first()->toArray();
        $menus = [[
            'menu' => $menu,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'persons' => '2',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = []; // 空配列にする事で、例外エラーを発生させる
        $reservation = $this->_createReservation('RS');

        try {
            $this->reservationMenu->saveRestaurant($info, $reservation, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    private function _createReservationMenu()
    {
        $store = new Store();
        $store->save();

        // TOメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'TO';
            $menu->save();
            $this->testMenuId = $menu->id;
        }

        // RSメニュー
        {
            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->app_cd = 'RS';
            $menu->save();
            $this->testMenuId2 = $menu->id;
        }

        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->is_close = 1;
        $reservation->reservation_status = 'ENSURE';
        $reservation->payment_status = 'PAYED';
        $reservation->payment_method = 'CREDIT';
        $reservation->tel = '0312345678';
        $reservation->created_at = '2022-10-01 10:00:00';
        $reservation->pick_up_datetime = '2022-10-01 15:00:00';
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $this->testMenuId;
        $reservationMenu->save();
        $this->testReservationMenuId = $reservationMenu->id;

        $reservationOption = new ReservationOption();
        $reservationOption->reservation_menu_id = $this->testReservationMenuId;
        $reservationOption->save();
        $this->testReservationOptionId = $reservationOption->id;
    }

    private function _createReservation($appCd)
    {
        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->save();
        return $reservation;
    }

    private function _createStock($stock_number, $date, $menuId)
    {
        $stock = new Stock();
        $stock->stock_number = $stock_number;
        $stock->date = $date;
        $stock->menu_id = $menuId;
        $stock->save();
    }
}
