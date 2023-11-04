<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Reservation;
use App\Models\ReservationGenre;
use App\Models\ReservationStore;
use App\Models\Store;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationGenreTest extends TestCase
{
    private $reservationGenre;
    private $testMenuId;
    private $testGenreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->reservationGenre = new ReservationGenre();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveTakeout()
    {
        $reservationStore = $this->_createReservationGenre('TO');

        $reservationStoreId = $reservationStore->id;
        $reservationMenuIdsWithMenuIdAsKey = [$reservationStoreId];

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
        $menuGenre = Genre::where('id', $this->testGenreId)->get();
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;
        $menuInfo[$this->testMenuId]['genres'] = $menuGenre->toArray();

        // reservationGenre insert
        $this->reservationGenre->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
        $reservationGenre = $this->reservationGenre::where('reservation_store_id', $reservationStoreId)->get();
        $this->assertIsObject($reservationGenre);
        $this->assertSame(1, $reservationGenre->count());
        $this->assertSame($reservationStoreId, $reservationGenre[0]['reservation_menu_id']);
        $this->assertSame('テストジャンル', $reservationGenre[0]['name']);
    }

    public function testSaveTakeoutThrowable()
    {
        $reservationStore = $this->_createReservationGenre('TO');

        $reservationStoreId = $reservationStore->id;
        $reservationMenuIdsWithMenuIdAsKey = [[$reservationStoreId]];   // IDを文字列から配列に変更し、例外エラーを発生させる

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
        $menuGenre = Genre::where('id', $this->testGenreId)->get();
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;
        $menuInfo[$this->testMenuId]['genres'] = $menuGenre->toArray();

        try {
            $this->reservationGenre->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    public function testSaveRestaurant()
    {
        $reservationStore = $this->_createReservationGenre('RS');

        $reservationStoreId = $reservationStore->id;
        $reservationMenuIdsWithMenuIdAsKey = [$this->testMenuId => $reservationStoreId];

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
        ]];
        $menuGenre = Genre::where('id', $this->testGenreId)->get();
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
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 2000;
        $menuInfo[$this->testMenuId]['genres'] = $menuGenre->toArray();

        // reservationGenre insert
        $this->reservationGenre->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
        $reservationGenre = $this->reservationGenre::where('reservation_store_id', $reservationStoreId)->get();
        $this->assertIsObject($reservationGenre);
        $this->assertSame(1, $reservationGenre->count());
        $this->assertSame($reservationStoreId, $reservationGenre[0]['reservation_menu_id']);
        $this->assertSame('テストジャンル', $reservationGenre[0]['name']);
    }

    public function testSaveRestaurantThrowable()
    {
        $reservationStore = $this->_createReservationGenre('RS');

        $reservationStoreId = $reservationStore->id;
        $reservationMenuIdsWithMenuIdAsKey = [$this->testMenuId => [$reservationStoreId]];   // IDを文字列から配列に変更し、例外エラーを発生させる

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
        ]];
        $menuGenre = Genre::where('id', $this->testGenreId)->get();
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
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 2000;
        $menuInfo[$this->testMenuId]['genres'] = $menuGenre->toArray();

        try {
            $this->reservationGenre->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
            $this->assertTrue(false);   // 上記処理でThrowable発生のため、ここは通過しない
        } catch (\Throwable $e) {
            $this->assertTrue(true);    // 例外発生し、ここを通過したことを確認
        }
    }

    private function _createReservationGenre($appCd)
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = $appCd;
        $menu->save();
        $this->testMenuId = $menu->id;

        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
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

        // メニュージャンル
        $genre = new Genre();
        $genre->genre_cd = 'test-cookingmenu';
        $genre->path = '/b-cooking';
        $genre->name = 'テストジャンル';
        $genre->level = 2;
        $genre->save();
        $this->testGenreId = $genre->id;

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $this->testGenreId;
        $genreGroup->menu_id = $this->testMenuId;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        return $reservationStore;
    }

}
