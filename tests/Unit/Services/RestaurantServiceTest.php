<?php

namespace Tests\Unit\Services;

use App\Models\Area;
use App\Models\Holiday;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Review;
use App\Models\Station;
use App\Models\Store;
use App\Models\Story;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RestaurantServiceTest extends TestCase
{
    private $restaurantService;

    public function setUp(): void
    {
        parent::setUp();
        $this->restaurantService = $this->app->make('App\Services\RestaurantService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testDetailMenu()
    {
        list($store, $menu, $image, $price, $price2, $option, $review, $reviewImage) = $this->_createMenuForTestDetailMenu();
        $this->_createdVacancy($store->id, '2022-10-01', '10:00:00', 1, 1, 0);

        // メニュー情報取得（在庫あり/メニュー価格は、指定日時点の価格情報が取得できていること）
        $param = [
            'visitDate' => '2022-10-01',
            'visitTime' => '10:00:00',
            'visitPeople' => '1',
        ];
        $result = $this->restaurantService->detailMenu($menu->id, $param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertArrayHasKey('id', $result['image']);
        $this->assertArrayHasKey('imageCd', $result['image']);
        $this->assertArrayHasKey('imageUrl', $result['image']);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('id', $result)['price'];
        $this->assertArrayHasKey('priceCd', $result['price']);
        $this->assertArrayHasKey('price', $result)['price'];
        $this->assertArrayHasKey('numberOfCource', $result);
        $this->assertArrayHasKey('availableNumberOfLowerLimit', $result);
        $this->assertArrayHasKey('availableNumberOfUpperLimit', $result);
        $this->assertArrayHasKey('freeDrinks', $result);
        $this->assertArrayHasKey('options', $result);
        $this->assertCount(1, $result['options']);
        $this->assertArrayHasKey('id', $result['options'][0]);
        $this->assertArrayHasKey('optionCd', $result['options'][0]);
        $this->assertArrayHasKey('required', $result['options'][0]);
        $this->assertArrayHasKey('keywordId', $result['options'][0]);
        $this->assertArrayHasKey('keyword', $result['options'][0]);
        $this->assertArrayHasKey('contentsId', $result['options'][0]);
        $this->assertArrayHasKey('contents', $result['options'][0]);
        $this->assertArrayHasKey('price', $result['options'][0]);
        $this->assertArrayHasKey('plan', $result);
        $this->assertArrayHasKey('reviews', $result);
        $this->assertCount(1, $result['reviews']);
        $this->assertArrayHasKey('id', $result['reviews'][0]);
        $this->assertArrayHasKey('userId', $result['reviews'][0]);
        $this->assertArrayHasKey('username', $result['reviews'][0]);
        $this->assertArrayHasKey('body', $result['reviews'][0]);
        $this->assertArrayHasKey('evaluationCd', $result['reviews'][0]);
        $this->assertArrayHasKey('createdAt', $result['reviews'][0]);
        $this->assertArrayHasKey('providedTime', $result);
        $this->assertArrayHasKey('onlySeat', $result);
        $this->assertArrayHasKey('notes', $result);
        $this->assertArrayHasKey('salesLunchStartTime', $result);
        $this->assertArrayHasKey('salesLunchEndTime', $result);
        $this->assertArrayHasKey('salesDinnerStartTime', $result);
        $this->assertArrayHasKey('salesDinnerEndTime', $result);
        $this->assertArrayHasKey('lowerOrdersTime', $result);
        $this->assertTrue($result['result']['status']);
        $this->assertSame('ok', $result['result']['message']);
        $this->assertSame($menu->id, $result['id']);
        $this->assertSame('テストメニュー名', $result['name']);
        $this->assertSame('テスト用メニューです', $result['description']);
        $this->assertSame($image->id, $result['image']['id']);
        $this->assertSame('MENU_MAIN', $result['image']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['image']['imageUrl']);
        $this->assertSame($price->id, $result['price']['id']);
        $this->assertSame('NORMAL', $result['price']['priceCd']);
        $this->assertSame('1000', $result['price']['price']);
        $this->assertSame('1', $result['numberOfCource']);
        $this->assertSame(1, $result['availableNumberOfLowerLimit']);
        $this->assertSame(10, $result['availableNumberOfUpperLimit']);
        $this->assertTrue($result['freeDrinks']);
        $this->assertSame($option->id, $result['options'][0]['id']);
        $this->assertSame('OKONOMI', $result['options'][0]['optionCd']);
        $this->assertSame(0, $result['options'][0]['required']);
        $this->assertSame(1, $result['options'][0]['keywordId']);
        $this->assertSame('テストオプション', $result['options'][0]['keyword']);
        $this->assertSame(1, $result['options'][0]['contentsId']);
        $this->assertSame('テストオプション内容', $result['options'][0]['contents']);
        $this->assertSame(100, $result['options'][0]['price']);
        $this->assertSame('テストプランです', $result['plan']);
        $this->assertSame($review->id, $result['reviews'][0]['id']);
        $this->assertSame(1, $result['reviews'][0]['userId']);
        $this->assertSame('グルメ太郎', $result['reviews'][0]['username']);
        $this->assertSame('テストクチコミ', $result['reviews'][0]['body']);
        $this->assertSame('GOOD_DEAL', $result['reviews'][0]['evaluationCd']);
        $this->assertSame(60, $result['providedTime']);
        $this->assertFalse($result['onlySeat']);
        $this->assertSame('テストです', $result['notes']);
        $this->assertSame('09:00:00', $result['salesLunchStartTime']);
        $this->assertSame('14:00:00', $result['salesLunchEndTime']);
        $this->assertSame('17:00:00', $result['salesDinnerStartTime']);
        $this->assertSame('21:00:00', $result['salesDinnerEndTime']);
        $this->assertSame(180, $result['lowerOrdersTime']);

        // メニュー情報取得（在庫なし/メニュー価格は、指定日期間の価格が未設定の為、現時点の価格情報が取得できていること）
        $param = [
            'visitDate' => '2022-11-01',
            'visitTime' => '10:00:00',
            'visitPeople' => '1',
        ];
        $result = $this->restaurantService->detailMenu($menu->id, $param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('price', $result);
        $this->assertArrayHasKey('id', $result)['price'];
        $this->assertArrayHasKey('priceCd', $result['price']);
        $this->assertArrayHasKey('price', $result)['price'];
        $this->assertFalse($result['result']['status']);
        $this->assertSame('空席がありません。', $result['result']['message']);
        $this->assertSame($price2->id, $result['price']['id']);
        $this->assertSame('NORMAL', $result['price']['priceCd']);
        $this->assertSame('1500', $result['price']['price']);
    }

    public function testGetStory()
    {
        list($story, $storyImage) = $this->_createStoryForTestGetStory();
        $storyId = $story->id;

        $request = new Request();
        $request->merge([
            'page' => 1,
        ]);
        $result = $this->restaurantService->getStory($request);
        $this->assertTrue(in_array($storyId, array_column($result, 'id')));
        $target = Arr::first($result, function ($value, $key) use ($storyId) {
            return ($value['id'] == $storyId);
        });
        $this->assertIsArray($target);
        $this->assertSame($storyId, $target['id']);
        $this->assertSame('テストタイトル', $target['title']);
        $this->assertSame('RS', $target['appCd']);
        $this->assertSame('https://test.jp/story/111', $target['guideUrl']);
        $this->assertSame($storyImage->id, $target['image']['id']);
        $this->assertSame('TEST', $target['image']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $target['image']['imageUrl']);
    }

    public function testSearchBox()
    {
        list($parentArea, $area) = $this->_createAreaStoreForTestSearchBox();
        $parentAreaId = $parentArea->id;
        $areaId = $area->id;

        $result = $this->restaurantService->searchBox();
        $this->assertIsArray($result);
        // テストエリアが取得可能
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($parentAreaId, array_column($result['areas'], 'id')));
        $target = Arr::first($result['areas'], function ($value, $key) use ($parentAreaId) {
            return ($value['id'] == $parentAreaId);
        });
        $this->assertSame($parentAreaId, $target['id']);
        $this->assertSame('テストエリア', $target['name']);
        $this->assertSame('testarea', $target['areaCd']);
        $this->assertSame('/', $target['path']);
        $this->assertSame(100.0, $target['weight']);
        // テストエリア２が取得可能
        $this->assertTrue(in_array($areaId, array_column($result['areas'], 'id')));
        $target2 = Arr::first($result['areas'], function ($value, $key) use ($areaId) {
            return ($value['id'] == $areaId);
        });
        $this->assertSame($areaId, $target2['id']);
        $this->assertSame('テストエリア2', $target2['name']);
        $this->assertSame('testarea2', $target2['areaCd']);
        $this->assertSame('/testarea', $target2['path']);
        $this->assertSame(110.0, $target2['weight']);
    }

    public function testGetRecommendation()
    {
        list($store, $image) = $this->_createStoreForTestGetRecommendation();

        // 検索結果あり（テスト店舗が取得できる）
        {
            $param = [
                'areaCd' => 'testarea',
                'stationCd' => 'teststation',
            ];
            $result = $this->restaurantService->getRecommendation($param);
            $this->assertIsArray($result);
            $this->assertCount(1, $result);
            $this->assertArrayHasKey('result', $result);
            $this->assertCount(1, $result['result']);
            $this->assertSame($store->id, $result['result'][0]['id']);
            $this->assertSame('テスト店舗', $result['result'][0]['name']);
            $this->assertSame('テスト説明', $result['result'][0]['description']);
            $this->assertIsArray($result['result'][0]['thumbImage']);
            $this->assertSame($image->id, $result['result'][0]['thumbImage']['id']);
            $this->assertSame('RESTAURANT_LOGO', $result['result'][0]['thumbImage']['imageCd']);
            $this->assertSame('https://test.jp/test.jpg', $result['result'][0]['thumbImage']['imageUrl']);
        }
        // 検索結果なし（テスト店舗が取得できない）
        {
            $updateStore = $store::find($store->id);
            $updateStore->published = 0;
            $updateStore->save();

            $param = [
                'areaCd' => 'testarea',
                'stationCd' => 'teststation',
            ];
            $result = $this->restaurantService->getRecommendation($param);
            $this->assertIsArray($result);
            $this->assertCount(0, $result);
        }
    }

    public function testMenuVacancy()
    {
        list($store, $menu) = $this->_createMenuForTestMenuVacancy();
        $reservation = $this-> _createReservation($store->id, $menu->id, '2022-10-01 09:00:00');
        $this->_createHoliday('2999-01-01');
        $this->_createdVacancy($store->id, '2999-10-02', '08:00:00', 1, 1, 1); // 在庫1（headcount：1）＆販売停止
        $this->_createdVacancy($store->id, '2999-10-02', '09:00:00', 2, 1, 0); // 在庫2（headcount：1）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-02', '09:00:00', 1, 2, 0); // 在庫1（headcount：2）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-02', '10:00:00', 0, 1, 0); // 在庫0（headcount：1）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-03', '18:00:00', 1, 1, 0); // 在庫1（headcount：1）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-04', '09:00:00', 1, 1, 0); // 在庫1（headcount：1）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-04', '18:00:00', 1, 1, 0); // 在庫1（headcount：1）＆販売可能
        $this->_createdVacancy($store->id, '2999-10-05', '00:00:00', 0, 1, 0); // 在庫0（headcount：1）＆販売可能

        // 在庫なし（menuId指定＆過去の日付）
        $param = [
            'menuId' => $menu->id,
            'visitDate' => '2022-10-01',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['stocks']);

        // 在庫なし（reservationId指定&営業日外（平日））
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-01',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['stocks']);

        // 在庫なし（reservationId指定&営業日外(祝日））
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-01-01',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['stocks']);

        // 在庫なし（reservationId指定&在庫レコードなし）
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-01',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['stocks']);

        // 在庫あり（在庫：ランチのみ）
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-02',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(2, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('09:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(1, $result['stocks'][0]['people']);
        $this->assertSame(2, $result['stocks'][0]['sets']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][1]);
        $this->assertArrayHasKey('people', $result['stocks'][1]);
        $this->assertArrayHasKey('sets', $result['stocks'][1]);
        $this->assertSame('09:00', $result['stocks'][1]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][1]['people']);
        $this->assertSame(1, $result['stocks'][1]['sets']);

        // 在庫あり（在庫：ディナーのみ）
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-03',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('18:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(1, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);

        // 在庫あり（在庫：ランチ＆ディナー両方）
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-04',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(2, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('09:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(1, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][1]);
        $this->assertArrayHasKey('people', $result['stocks'][1]);
        $this->assertArrayHasKey('sets', $result['stocks'][1]);
        $this->assertSame('18:00', $result['stocks'][1]['vacancyTime']);
        $this->assertSame(1, $result['stocks'][1]['people']);
        $this->assertSame(1, $result['stocks'][1]['sets']);

        // 在庫なし（営業時間外の在庫レコードあり、販売可能数0）
        $param = [
            'reservationId' => $reservation->id,
            'visitDate' => '2999-10-05',
        ];
        $result = $this->restaurantService->menuVacancy($param);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['stocks']);

        // 在庫なし（現在時刻+最低注文時間が過去の時間になってしまう）
        {
            // メニュー最低注文時間と営業日設定を∂変更
            $menu = Menu::find($menu->id);
            $menu->lower_orders_time = 2880;    // メニュー最低注文時間を2日前
            $menu->save();
            $openingHour = OpeningHour::where('store_id', $store->id)->first();
            $openingHour->week = '11111111';    // 全日営業
            $openingHour->save();

            $dt = Carbon::now();
            $dt->addDay(1);
            $this->_createdVacancy($store->id, $dt->toDateString(), '09:00:00', 1, 2, 0); // 在庫1（headcount：2）＆販売可能

            $param = [
                'reservationId' => $reservation->id,
                'visitDate' => $dt->toDateString(),
            ];
            $result = $this->restaurantService->menuVacancy($param);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('stocks', $result);
            $this->assertCount(0, $result['stocks']);
        }
    }

    public function testCheckPast()
    {
        // 来店日が予約日より過去の場合は、True
        $today = new Carbon(); // 今日
        $pickUpDate = $today->copy()->addDay(1); // 明日
        $this->assertTrue($this->restaurantService->checkPast($today, $pickUpDate));

        // 来店日が予約日と同じの場合は、True
        $today = new Carbon(); // 今日
        $pickUpDate = $today->copy(); // 今日
        $this->assertTrue($this->restaurantService->checkPast($today, $pickUpDate));

        // 来店日が予約日より過去ではない場合は、False
        $today = new Carbon(); // 今日
        $pickUpDate = $today->copy()->subDay(); // 昨日
        $this->assertFalse($this->restaurantService->checkPast($today, $pickUpDate));
    }

    public function testLunchDinnerAt()
    {
        $menu = new Menu();
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->provided_time = '60';
        $arrayVisitDate = ['2022', '10', '01'];

        // 戻り値が期待値（$target）と一致するかを確認
        // check method lunchStartAt
        $target = new Carbon('2022-10-01 09:00:00');
        $this->assertTrue($target->eq($this->restaurantService->lunchStartAt($menu, $arrayVisitDate)));
        // check method lunchEndAt
        $target = new Carbon('2022-10-01 13:00:00');    // メニュー提供時間60分を引く
        $this->assertTrue($target->eq($this->restaurantService->lunchEndAt($menu, $arrayVisitDate)));
        // check method dinnerStartAt
        $target = new Carbon('2022-10-01 17:00:00');
        $this->assertTrue($target->eq($this->restaurantService->dinnerStartAt($menu, $arrayVisitDate)));
        // check method dinnerEndAt
        $target = new Carbon('2022-10-01 20:00:00');    // メニュー提供時間60分を引く
        $this->assertTrue($target->eq($this->restaurantService->dinnerEndAt($menu, $arrayVisitDate)));
    }

    public function testCheckHeadcount()
    {
        // 利用可能範囲内（headcount:1, lowerLimit:1, uppserLimit:10）
        $this->assertTrue($this->restaurantService->checkHeadcount(1, 1, 10));
        // 利用可能範囲内（headcount:99, lowerLimit:1, uppserLimit:200）
        $this->assertTrue($this->restaurantService->checkHeadcount(99, 1, 200));
        // 利用可能範囲外（headcount:1, lowerLimit:2, uppserLimit:10）
        $this->assertFalse($this->restaurantService->checkHeadcount(1, 2, 10));
        // 利用可能範囲外（headcount:20, lowerLimit:1, uppserLimit:10）
        $this->assertFalse($this->restaurantService->checkHeadcount(20, 1, 10));
        // 利用可能範囲外（headcount:100, lowerLimit:1, uppserLimit:200） ※上限を９９に設定されるため100以上は範囲外となる
        $this->assertFalse($this->restaurantService->checkHeadcount(100, 1, 200));
    }

    public function testNoSalesTimeCanSale()
    {
        $openingHour = new OpeningHour();
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '21:00:00';
        $openingHours = collect([$openingHour]);
        $menu = new Menu();
        $menu->provided_time = 60;
        $arrayVisitDate = ['2022', '10', '01'];
        $openingHourStartAt = new Carbon('2022-10-01 09:00:00');
        $openingHourEndAt = new Carbon('2022-10-01 21:00:00');

        // 販売可（営業時間内の時間を指定する）
        $startAt = new Carbon('2022-10-01 10:00:00');
        $this->assertSame(1, $this->restaurantService->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, 1));
        // 販売可（営業時間内の時間を指定する。提供時間を加味しても営業時間内）
        $startAt = new Carbon('2022-10-01 20:00:00');
        $this->assertSame(1, $this->restaurantService->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, 1));
        // 販売不可（営業時間外の時間を指定する）
        $startAt = new Carbon('2022-10-01 08:00:00');
        $this->assertNull($this->restaurantService->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, 1));
        // 販売不可（営業時間内の時間を指定するが、提供時間を加味すると営業時間外となる）
        $startAt = new Carbon('2022-10-01 20:30:00');
        $this->assertNull($this->restaurantService->noSalesTimeCanSale($openingHours, $arrayVisitDate, $menu, $startAt, $openingHourStartAt, $openingHourEndAt, 1));
    }

    private function _createMenuForTestDetailMenu()
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->app_cd = 'TO';
        $menu->name = 'テストメニュー名';
        $menu->description = 'テスト用メニューです';
        $menu->store_id = $store->id;
        $menu->number_of_course = 1;
        $menu->available_number_of_lower_limit = 1;
        $menu->available_number_of_upper_limit = 10;
        $menu->free_drinks = 1;
        $menu->provided_time = 60;
        $menu->lower_orders_time = 180;
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->notes = 'テストです';
        $menu->plan = 'テストプランです';
        $menu->save();

        $image = new Image();
        $image->menu_id = $menu->id;
        $image->image_cd = 'MENU_MAIN';
        $image->url = 'https://test.jp/test.jpg';
        $image->save();

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->start_date = '2022-01-01';
        $price->end_date = '2022-10-30';
        $price->price_cd = 'NORMAL';
        $price->price = 1000;
        $price->save();

        $price2 = new Price();
        $price2->menu_id = $menu->id;
        $price2->start_date = '2022-12-01';
        $price2->end_date = '2999-12-31';
        $price2->price_cd = 'NORMAL';
        $price2->price = 1500;
        $price2->save();

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->option_cd = 'OKONOMI';
        $option->required = 0;
        $option->keyword_id = 1;
        $option->keyword = 'テストオプション';
        $option->contents_id = 1;
        $option->contents = 'テストオプション内容';
        $option->price = 100;
        $option->save();

        $reviewImage = new Image();
        $reviewImage->url = 'https://test.jp/test2.jpg';
        $reviewImage->save();

        $review = new Review();
        $review->store_id = $store->id;
        $review->menu_id = $menu->id;
        $review->user_id = 1;
        $review->user_name = 'グルメ太郎';
        $review->body = 'テストクチコミ';
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->image_id = $reviewImage->id;
        $review->published = 1;
        $review->save();

        return [$store, $menu, $image, $price, $price2, $option, $review, $reviewImage];
    }

    private function _createStoryForTestGetStory()
    {
        $storyImage = new Image();
        $storyImage->image_cd = 'TEST';
        $storyImage->url = 'https://test.jp/test.jpg';
        $storyImage->save();

        $story = new Story();
        $story->title = 'テストタイトル';
        $story->app_cd = 'RS';
        $story->guide_url = 'https://test.jp/story/111';
        $story->published = 1;
        $story->image_id = $storyImage->id;
        $story->save();

        return [$story, $storyImage];
    }

    private function _createAreaStoreForTestSearchBox()
    {
        $parentArea = new Area();
        $parentArea->name = 'テストエリア';
        $parentArea->area_cd = 'testarea';
        $parentArea->path = '/';
        $parentArea->level = 1;
        $parentArea->weight = 100.0;
        $parentArea->published = 1;
        $parentArea->save();

        $area = new Area();
        $area->name = 'テストエリア2';
        $area->area_cd = 'testarea2';
        $area->path = '/' . $parentArea->area_cd;
        $area->level = 2;
        $area->weight = 110.0;
        $area->published = 1;
        $area->save();

        $store = new Store();
        $store->app_cd = 'RS';
        $store->area_id = $area->id;
        $store->published = 1;
        $store->save();

        return [$parentArea, $area];
    }

    private function _createStoreForTestGetRecommendation()
    {
        $area = new Area();
        $area->name = 'テストエリア';
        $area->area_cd = 'testarea';
        $area->path = '/';
        $area->level = 1;
        $area->weight = 110.0;
        $area->published = 1;
        $area->save();

        $station = new Station();
        $station->station_cd = 'teststation';
        $station->save();

        $store = new Store();
        $store->app_cd = 'RS';
        $store->area_id = $area->id;
        $store->station_id = $station->id;
        $store->published = 1;
        $store->name = 'テスト店舗';
        $store->description = 'テスト説明';
        $store->save();

        $image = new Image();
        $image->store_id = $store->id;
        $image->image_cd = 'RESTAURANT_LOGO';
        $image->url = 'https://test.jp/test.jpg';
        $image->save();

        return [$store, $image];
    }

    private function _createMenuForTestMenuVacancy()
    {
        $store = new Store();
        $store->regular_holiday = '11111111';
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '10111110';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->start_at = '07:00:00';
        $openingHour->end_at = '21:00:00';
        $openingHour->last_order_time = '20:30:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->name = 'テストメニュー名';
        $menu->description = 'テスト用メニューです';
        $menu->store_id = $store->id;
        $menu->number_of_course = 1;
        $menu->available_number_of_lower_limit = 1;
        $menu->available_number_of_upper_limit = 10;
        $menu->provided_time = 60;
        $menu->lower_orders_time = 180;
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->provided_day_of_week = '11111111';
        $menu->save();

        return [$store, $menu];
    }

    private function _createReservation($storeId, $menuId, $pickUpDatetime)
    {
        $reservation = new Reservation();
        $reservation->total = 2000;
        $reservation->persons = 2;
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $storeId;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menuId;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->count = 2;
        $reservationMenu->price = 2000;
        $reservationMenu->save();

        return $reservation;
    }

    private function _createdVacancy($storeId, $date, $time, $stock, $headcount, $isStopSale)
    {
        $vacancy = new Vacancy();
        $vacancy->store_id = $storeId;
        $vacancy->date = $date;
        $vacancy->time = $time;
        $vacancy->headcount = $headcount;
        $vacancy->base_stock = $stock;
        $vacancy->stock = $stock;
        $vacancy->is_stop_sale = $isStopSale;
        $vacancy->save();
    }

    private function _createHoliday($date)
    {
        $holiday = new Holiday();
        $holiday->date = $date;
        $holiday->save();
    }
}
