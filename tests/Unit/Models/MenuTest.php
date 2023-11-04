<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\OrderInterval;
use App\Models\Price;
use App\Models\Review;
use App\Models\Staff;
use App\Models\Station;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MenuTest extends TestCase
{
    private $menu;
    private $testGenreId;
    private $testImageId;
    private $testMenuId;
    private $testPriceId;
    private $testOptionId;
    private $testOrderInterval;
    private $testReviewId;
    private $testStaffId;
    private $testStationId;
    private $testStockId;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->menu = new Menu();

        $this->_createStoreMenu();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testImage()
    {
        // 画像コード指定あり
        $result = $this->menu::find($this->testMenuId)->image('MENU_MAIN');
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId, $result['id']);

        // 画像コード指定なし
        $result = $this->menu::find($this->testMenuId)->image();
        $this->assertIsObject($result);
        $this->assertSame($this->testImageId, $result['id']);
    }

    public function testStore()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->menu::whereHas('store', function ($query) use ($testStoreId) {
            $query->where('id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testAnotherStaff()
    {
        $testStaffId = $this->testStaffId;
        $result = $this->menu::whereHas('anotherStaff', function ($query) use ($testStaffId) {
            $query->where('id', $testStaffId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testMenuPrices()
    {
        $testPriceId = $this->testPriceId;
        $result = $this->menu::whereHas('menuPrices', function ($query) use ($testPriceId) {
            $query->where('id', $testPriceId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testOderInterval()
    {
        $testOrderInterval = $this->testOrderInterval;
        $result = $this->menu::whereHas('orderInterval', function ($query) use ($testOrderInterval) {
            $query->where('id', $testOrderInterval);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testReviews()
    {
        $testReviewId = $this->testReviewId;
        $result = $this->menu::whereHas('reviews', function ($query) use ($testReviewId) {
            $query->where('id', $testReviewId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testReviewsPublished()
    {
        // 該当データあり
        {
            $review = Review::find($this->testReviewId);
            $review->published = 1;
            $review->save();

            $testReviewId = $this->testReviewId;
            $result = $this->menu::whereHas('reviews_published', function ($query) use ($testReviewId) {
                $query->where('id', $testReviewId);
            })->get();
            $this->assertIsObject($result);
            $this->assertSame(1, $result->count());
            $this->assertSame($this->testMenuId, $result[0]['id']);
        }

        // 該当データなし
        {
            $review = Review::find($this->testReviewId);
            $review->published = 0;
            $review->save();

            $testReviewId = $this->testReviewId;
            $result = $this->menu::whereHas('reviews_published', function ($query) use ($testReviewId) {
                $query->where('id', $testReviewId);
            })->get();
            $this->assertIsObject($result);
            $this->assertSame(0, $result->count());


            $review = Review::find($this->testReviewId);
            $review->published = 1;
            $review->save();
        }
    }

    public function testMenuPrice()
    {
        // 日付指定なし（現時点）
        $result = $this->menu::find($this->testMenuId)->menuPrice();
        $this->assertSame('1500', $result['price']);

        //日付指定あり
        $result = $this->menu::find($this->testMenuId)->menuPrice('2022-09-01');
        $this->assertSame('1000', $result['price']);
    }

    public function testStocks()
    {
        $testStockId = $this->testStockId;
        $result = $this->menu::whereHas('stocks', function ($query) use ($testStockId) {
            $query->where('id', $testStockId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testOptions()
    {
        $testOptionId = $this->testOptionId;
        $result = $this->menu::whereHas('options', function ($query) use ($testOptionId) {
            $query->where('id', $testOptionId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testStock()
    {
        // 該当データあり：日付指定なし（現時点）
        $result = $this->menu::find($this->testMenuId)->stock();
        $this->assertSame(10, $result['stock_number']);

        //該当データあり：日付指定あり
        $result = $this->menu::find($this->testMenuId)->stock('2022-10-01');
        $this->assertSame(5, $result['stock_number']);

        //該当データなし：日付指定あり
        $result = $this->menu::find($this->testMenuId)->stock('2022-09-01');
        $this->assertNull($result);
    }

    public function testGenres()
    {
        $testGenreId = $this->testGenreId;
        $result = $this->menu::whereHas('genres', function ($query) use ($testGenreId) {
            $query->where('genre_id', $testGenreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testLatestReviews()
    {
        // 最新１件
        $result = $this->menu::find($this->testMenuId)->latestReviews(1);
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame('テストbody3', $result[0]['body']);

        //　 最新10件
        $result = $this->menu::find($this->testMenuId)->latestReviews(10);
        $this->assertIsObject($result);
        $this->assertSame(3, $result->count());               // 登録数は３つしかないので３件取れる
        $this->assertSame('テストbody3', $result[0]['body']);
        $this->assertSame('テストbody2', $result[1]['body']);
        $this->assertSame('テストbody', $result[2]['body']);
    }

    public function testGetRecommendation()
    {
        $result = $this->menu->getRecommendation(['areaCd' => 'shinjuku']);
        $this->assertIsObject($result);
    }

    public function testSearch()
    {
        $genres = [];

        // 現在地検索（結果：該当データあり）
        $param = [
            'suggestCd' => 'CURRENT_LOC',
            'latitude' => '100',
            'longitude' => '100',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(1, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1.0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertSame($this->testMenuId, $result['list'][0]['id']);

        // 駅検索（結果：該当データあり）
        $param = [
            'suggestCd' => 'STATION',
            'suggestText' => 'テストステーション',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(1, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1.0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertSame($this->testMenuId, $result['list'][0]['id']);

        // 駅検索（結果：該当駅なし）
        $param = [
            'suggestCd' => 'STATION',
            'suggestText' => 'テストてすとステーション',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(0, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertEquals(collect([]), $result['list']);

        // 駅検索（結果：該当データなし）
        {
            $station = Station::find($this->testStationId);
            $station->latitude = '999';
            $station->longitude = '999';
            $station->save();

            $param = [
                'suggestCd' => 'STATION',
                'suggestText' => 'テストステーション',
            ];
            $result = $this->menu->search($param, $genres);
            $this->assertCount(3, $result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('list', $result);
            $this->assertEquals(collect([]), $result['list']);

            $station = Station::find($this->testStationId);
            $station->latitude = '100';
            $station->longitude = '100';
            $station->save();
        }

        // 駅検索&料理ジャンル検索
        $param = [
            'suggestCd' => 'STATION',
            'suggestText' => 'テストステーション',
            'cookingGenreCd' => 'test-cookingmenu',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(1, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1.0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertSame($this->testMenuId, $result['list'][0]['id']);

        // こだわり検索（結果：該当データあり）
        $param = [
            'menuGenreCd' => 'searchtestmenu02',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(1, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1.0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertSame($this->testMenuId, $result['list'][0]['id']);

        // こだわり検索（結果：該当データなし）
        $param = [
            'menuGenreCd' => 'searchtestmenu03',
        ];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertSame(0, $result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(0, $result['pageMax']);
        $this->assertArrayHasKey('list', $result);
        $this->assertEquals(collect([]), $result['list']);

        // その他検索（結果：該当データあり）
        $param = [];
        $result = $this->menu->search($param, $genres);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertIsNumeric($result['count']);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertIsNumeric($result['pageMax']);
        $this->assertArrayHasKey('list', $result);
    }

    public function testGet()
    {
        // 該当データなし
        $result = $this->menu->get([$this->testMenuId], Carbon::now()->format('Y-m-d'), '08:00:00');
        $this->assertIsObject($result);
        $this->assertCount(0, $result);

        // 該当データあり
        $result = $this->menu->get([$this->testMenuId], Carbon::now()->format('Y-m-d'), '09:00:00');
        $this->assertIsObject($result);
    }

    public function testDetail()
    {
        $result = $this->menu->detail($this->testMenuId, Carbon::now()->format('Y-m-d'));
        $this->assertIsObject($result);
    }

    public function testScopeAdminSearchFilter()
    {
        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmettaroutest',
        ]);
        $valid = [
            'id' => $this->testMenuId,
            'app_cd' => 'TO',
            'name' => 'テストメニュー',
            'store_name' => 'テスト',
        ];
        $result = $this->menu::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testMenuId, $result[0]['id']);
    }

    public function testGetMenuFromInfo()
    {
        // メニュー情報取得可能
        $param = [];
        $param['application']['menus'][0]['menu']['id'] = $this->testMenuId;
        $param['application']['pickUpDate'] = '2022-10-01';
        $param['application']['pickUpTime'] = '09:00:00';
        $result = $this->menu->getMenuFromInfo($param);
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testMenuId, $result[0]['id']);

        // 例外エラー
        $param['application']['pickUpDate'] = '2022-10-04';   // 定休日
        try {
            $result = $this->menu->getMenuFromInfo($param);
        } catch (\Exception $e) {
            $this->assertSame(sprintf(Lang::get('message.weekFailure2'), '火曜日'), $e->getMessage());
        }
    }

    public function testGetRestaurantMenuFromInfo()
    {
        // メニュー情報取得可能
        $param = [];
        $param['application']['menus'][0]['menu']['id'] = $this->testMenuId;
        $param['application']['visitDate'] = '2022-10-01';
        $param['application']['visitTime'] = '09:00:00';
        $result = $this->menu->getRestaurantMenuFromInfo($param);
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testMenuId, $result[0]['id']);

        // 例外エラー
        $param['application']['visitDate'] = '2022-10-04';   // 定休日
        try {
            $result = $this->menu->getRestaurantMenuFromInfo($param);
        } catch (\Exception $e) {
            $this->assertSame(sprintf(Lang::get('message.weekFailure2'), '火曜日'), $e->getMessage());
        }
    }

    public function testCanSale()
    {
        // 営業日（月曜日）/メニュー提供可
        $msg = null;
        $date = new Carbon('2022-10-03 09:00:00');
        $result = $this->menu->canSale($this->testMenuId, $this->testStoreId, $date, $msg);
        $this->assertTrue($result);
        $this->assertNull($msg);

        // 営業日/メニュー提供不可
        $msg = null;
        $date = new Carbon('2022-10-04 09:00:00');
        $result = $this->menu->canSale($this->testMenuId, $this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(sprintf(Lang::get('message.weekFailure2'), '火曜日'), $msg);

        // 定休日
        $date = new Carbon('2022-10-05 09:00:00');
        $msg = null;
        $result = $this->menu->canSale($this->testMenuId, $this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(sprintf(Lang::get('message.weekFailure1'), '水曜日'), $msg);

        // 営業日（祝日）だが、営業時間外
        $date = new Carbon('2022-10-10 08:00:00');
        $msg = null;
        $result = $this->menu->canSale($this->testMenuId, $this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(Lang::get('message.weekFailure0'), $msg);
    }

    public function testCheckOpeningHours()
    {
        // 定休日（水曜日）
        $msg = null;
        $date = new Carbon('2022-10-05 09:00:00');
        $result = $this->menu->checkOpeningHours($this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(Lang::get('message.weekFailure0'), $msg);

        // 営業日だが、営業時間外
        $msg = null;
        $date = new Carbon('2022-10-04 08:00:00');
        $result = $this->menu->checkOpeningHours($this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(Lang::get('message.weekFailure0'), $msg);

        // 祝日休み
        $msg = null;
        $date = new Carbon('2022-10-10 18:00:00');
        $result = $this->menu->checkOpeningHours($this->testStoreId, $date, $msg);
        $this->assertFalse($result);
        $this->assertSame(Lang::get('message.weekFailure0'), $msg);
    }

    public function testCheckFromWeek()
    {
        // 祝日定休日
        $msg = null;
        $dateStr = '2022-10-10';
        $type = 1;
        $date = new Carbon($dateStr);
        $result = $this->menu->checkFromWeek('11011110', $date, $type, $msg);
        $this->assertFalse($result);
        $this->assertSame(sprintf(Lang::get('message.weekFailure' . $type), $dateStr), $msg);
    }

    public function testGetWeek()
    {
        // 日曜日
        $date = new Carbon('2022-10-02');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(6, $week);
        $this->assertSame('日曜日', $weekName);

        // 月曜日
        $date = new Carbon('2022-10-03');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(0, $week);
        $this->assertSame('月曜日', $weekName);

        // 火曜日
        $date = new Carbon('2022-10-04');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(1, $week);
        $this->assertSame('火曜日', $weekName);

        // 水曜日
        $date = new Carbon('2022-10-05');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(2, $week);
        $this->assertSame('水曜日', $weekName);

        // 木曜日
        $date = new Carbon('2022-10-06');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(3, $week);
        $this->assertSame('木曜日', $weekName);

        // 金曜日
        $date = new Carbon('2022-10-07');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(4, $week);
        $this->assertSame('金曜日', $weekName);

        // 土曜日
        $date = new Carbon('2022-10-08');
        list($week, $weekName) = $this->menu->getWeek($date);
        $this->assertSame(5, $week);
        $this->assertSame('土曜日', $weekName);
    }

    public function testCheckExistsOpeningHours()
    {
        // 祝日ではない営業日
        $msg = null;
        $date = new Carbon('2022-10-03');
        $result = $this->menu->checkExistsOpeningHours($this->testStoreId, $date, 1, $msg);
        $this->assertTrue($result);
        $this->assertNull($msg);

        // 祝日の営業日
        $msg = null;
        $date = new Carbon('2022-10-10');
        $result = $this->menu->checkExistsOpeningHours($this->testStoreId, $date, 1, $msg);
        $this->assertTrue($result);
        $this->assertNull($msg);

        // 休業日
        $msg = null;
        $date = new Carbon('2022-10-05');
        $result = $this->menu->checkExistsOpeningHours($this->testStoreId, $date, 1, $msg);
        $this->assertFalse($result);
        $this->assertSame(Lang::get('message.weekFailure0'), $msg);
    }

    public function testCheckLowerOrderTime()
    {
        $menu = Menu::find($this->testMenuId);

        // 注文可
        $date = new Carbon('2999-12-31 10:00:00');
        $result = $this->menu->checkLowerOrderTime($menu, $date);
        $this->assertTrue($result);

        // 注文不可
        $date = new Carbon('2022-10-05 09:00:00');
        $result = $this->menu->checkLowerOrderTime($menu, $date);
        $this->assertFalse($result);
    }

    private function _createStoreMenu()
    {
        $station = new Station();
        $station->station_cd = 'test';
        $station->name = 'テストステーション';
        $station->name_roma = 'teststation';
        $station->latitude = '100';
        $station->longitude = '100';
        $station->prefecture_id = 13;               // 都道府県は東京にする（StoreSearch関数の検索範囲は東京固定なので）
        $station->save();
        $this->testStationId = $station->id;

        $staff = new Staff();
        $staff->save();
        $this->testStaffId = $staff->id;

        $store = new Store();
        $store->regular_holiday = '11011111';       // 営業日は月火木金土日祝（水曜休み）
        $store->name = 'テスト';
        $store->latitude = '100';
        $store->longitude = '100';
        $store->station_id = $this->testStationId;
        $store->published = 1;
        $store->save();
        $this->testStoreId = $store->id;

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11011111';            // 営業日は月火木金土日祝（水曜休み）
        $openingHour->start_at = '09:00:00';        // 営業時間開始
        $openingHour->end_at = '14:59:59';          // 営業時間終了
        $openingHour->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11011110';            // 営業日は月火木金土日祝（水曜休み）
        $openingHour->start_at = '17:00:00';        // 営業時間開始
        $openingHour->end_at = '23:59:59';          // 営業時間終了
        $openingHour->save();

        $menu = new Menu();
        $menu->app_cd = 'TO';
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 30;
        $menu->store_id = $store->id;
        $menu->provided_day_of_week = '10011111';   // 月木金土日祝は提供可
        $menu->published = 1;
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '15:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->staff_id = $this->testStaffId;
        $menu->save();
        $this->testMenuId = $menu->id;

        $image = new Image();
        $image->image_cd = 'MENU_MAIN';
        $image->menu_id = $this->testMenuId;
        $image->save();
        $this->testImageId = $image->id;

        $price = new Price();
        $price->price_cd = 'NORMAL';
        $price->price = 1000;
        $price->start_date = '2021-10-01';
        $price->end_date = '2022-09-30';
        $price->menu_id = $this->testMenuId;
        $price->save();

        $price = new Price();
        $price->price_cd = 'NORMAL';
        $price->price = 1500;
        $price->start_date = '2022-10-01';
        $price->end_date = '2999-12-31';
        $price->menu_id = $this->testMenuId;
        $price->save();
        $this->testPriceId = $price->id;

        $orderInterval = new OrderInterval();
        $orderInterval->date = Carbon::now()->format('Y-m-d');  //本日
        $orderInterval->start = '09:00:00';
        $orderInterval->end = '09:15:00';
        $orderInterval->orderable_item = 10;
        $orderInterval->menu_id = $this->testMenuId;
        $orderInterval->save();
        $this->testOrderInterval = $orderInterval->id;

        $review = new Review();
        $review->menu_id = $this->testMenuId;
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->body = 'テストbody';
        $review->user_id = 1;
        $review->user_name = 'グルメ 太郎';
        $review->published = 1;
        $review->created_at = '2022-10-01 10:00:00';
        $review->save();
        $this->testReviewId = $review->id;

        $review = new Review();
        $review->menu_id = $this->testMenuId;
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->body = 'テストbody2';
        $review->user_id = 1;
        $review->user_name = 'グルメ 太郎';
        $review->published = 1;
        $review->created_at = '2022-10-10 10:00:00';
        $review->save();
        $this->testReviewId = $review->id;

        $review = new Review();
        $review->menu_id = $this->testMenuId;
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->body = 'テストbody3';
        $review->user_id = 1;
        $review->user_name = 'グルメ 太郎';
        $review->published = 1;
        $review->created_at = '2022-10-15 10:00:00';
        $review->save();
        $this->testReviewId = $review->id;

        $stock = new Stock();
        $stock->stock_number = 5;
        $stock->date = '2022-10-01';
        $stock->menu_id = $this->testMenuId;
        $stock->save();

        $stock = new Stock();
        $stock->stock_number = 10;
        $stock->date = Carbon::now()->format('Y-m-d');  //本日
        $stock->menu_id = $this->testMenuId;
        $stock->save();
        $this->testStockId = $stock->id;

        $option = new Option();
        $option->menu_id = $this->testMenuId;
        $option->save();
        $this->testOptionId = $option->id;

        // メニュージャンル
        $genre = new Genre();
        $genre->genre_cd = 'test-cookingmenu';
        $genre->path = '/b-cooking';
        $genre->level = 2;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->menu_id = $this->testMenuId;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // メニュージャンル
        $genre = new Genre();
        $genre->genre_cd = 'searchtestmenu02';
        $genre->path = '/b-cooking/test-cookingmenu';
        $genre->level = 3;
        $genre->save();
        $this->testGenreId = $genre->id;

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->menu_id = $this->testMenuId;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // 精算会社アカウント
        $staff = new Staff();
        $staff->store_id = $this->testStoreId;
        $staff->staff_authority_id = '3';
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmettaroutest');
        $staff->save();
    }
}
