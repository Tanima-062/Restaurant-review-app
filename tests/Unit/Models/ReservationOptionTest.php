<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationOptionTest extends TestCase
{
    private $reservationOption;
    private $testOptionId;
    private $testMenuId;
    private $testReservationMenuId;
    private $testReservationOptionId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservationOption = new ReservationOption();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testOption()
    {
        $this->_createReservation('RS', true);

        $testOptionId = $this->testOptionId;
        $result = $this->reservationOption::whereHas('option', function ($query) use ($testOptionId) {
            $query->where('id', $testOptionId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testReservationOptionId, $result[0]['id']);
    }

    public function testSaveTakeout()
    {
        $this->_createReservation('TO');
        $reservationMenuIdsWithMenuIdAsKey = [$this->testReservationMenuId];

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
                'options' => [Option::find($this->testOptionId)->toArray()],
            ],
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

        // reservationOption insert
        $this->reservationOption->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey);

        $reservationOption = $this->reservationOption::where('reservation_menu_id', $this->testReservationMenuId)->get();
        $this->assertSame(1, $reservationOption->count());
        $this->assertSame($this->testOptionId, $reservationOption[0]['option_id']);
    }

    public function testSaveTakeoutThrowable()
    {
        $this->_createReservation('TO');
        $reservationMenuIdsWithMenuIdAsKey = [$this->testReservationMenuId];

        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), []); // count要素を入れないようにし、例外エラーを発生させる
        $menus = [
            [
                'menu' => $menu,
                'options' => [Option::find($this->testOptionId)->toArray()],
            ],
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

        try {
            $this->reservationOption->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    public function testSaveRestaurant()
    {
        $this->_createReservation('RS');
        $reservationMenuIdsWithMenuIdAsKey = [$this->testMenuId => $this->testReservationMenuId];

        // reservationOption insert(オプション指定なし)
        $info = $this->_createSaveRestaurantParams(false);
        $result = $this->reservationOption->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey);
        $this->assertNull($result);
        $reservationOption = $this->reservationOption::where('reservation_menu_id', $this->testReservationMenuId)->get();
        $this->assertSame(0, $reservationOption->count());    // 該当データなし

        // reservationOption insert(オプション指定あり)
        $info = $this->_createSaveRestaurantParams(true);
        $this->reservationOption->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey);
        $reservationOption = $this->reservationOption::where('reservation_menu_id', $this->testReservationMenuId)->get();
        $this->assertSame(1, $reservationOption->count());    // 該当データあり
        $this->assertSame($this->testOptionId, $reservationOption[0]['option_id']);
    }

    public function testSaveRestaurantThrowable()
    {
        $this->_createReservation('RS');
        $reservationMenuIdsWithMenuIdAsKey = [$this->testMenuId => $this->testReservationMenuId];

        try {
            $info = []; // 空配列にし、例外エラーを発生させる
            $result = $this->reservationOption->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    private function _createReservation($appCd, $addReservationOption = false)
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = $appCd;
        $menu->save();
        $this->testMenuId = $menu->id;

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->price = 100;
        $option->save();
        $this->testOptionId = $option->id;

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

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $this->testMenuId;
        $reservationMenu->save();
        $this->testReservationMenuId = $reservationMenu->id;

        if ($addReservationOption) {
            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $this->testReservationMenuId;
            $reservationOption->option_id = $this->testOptionId;
            $reservationOption->save();
            $this->testReservationOptionId = $reservationOption->id;
        }
    }

    private function _createSaveRestaurantParams($optionFlg)
    {
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = Menu::where('id', $this->testMenuId)->first()->toArray();
        $options = ($optionFlg)? [array_merge(Option::find($this->testOptionId)->toArray(), ['count' => 2])] : null;
        $menus = [[
            'menu' => $menu,
            'options' => $options,
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

        return $info;
    }
}
