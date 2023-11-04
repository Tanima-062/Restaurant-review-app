<?php

namespace Tests\Unit\Services;

use App\Models\Area;
use App\Models\CancelFee;
use App\Models\CmTmUser;
use App\Models\ExternalApi;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Holiday;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\Review;
use App\Models\Station;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreServiceTest extends TestCase
{
    private $storeService;
    private $store;

    public function setUp(): void
    {
        parent::setUp();
        $this->storeService = $this->app->make('App\Services\StoreService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGet()
    {
        list($store, $genre, $image, $openingHour, $station) = $this->_createStoreForTestGet();
        $storeId = $this->store->id;

        // 未公開店舗
        $this->assertNull($this->storeService->get($storeId));

        // 公開店舗
        {
            $store = Store::find($storeId);
            $store->published = 1;  // 店舗を公開設定に変更
            $store->save();

            $result = $this->storeService->get($storeId);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('storeGenres', $result);
            $this->assertCount(1, $result['storeGenres']);
            $this->assertSame($genre->id, $result['storeGenres'][0]['id']);
            $this->assertSame('test3', $result['storeGenres'][0]['name']);
            $this->assertSame('test3', $result['storeGenres'][0]['genreCd']);
            $this->assertSame('TORS', $result['storeGenres'][0]['appCd']);
            $this->assertSame('/b-cooking/test2', $result['storeGenres'][0]['path']);
            $this->assertArrayHasKey('topImages', $result);
            $this->assertCount(1, $result['topImages']);
            $this->assertSame($image->id, $result['topImages'][0]['id']);
            $this->assertSame('RESTAURANT_LOGO', $result['topImages'][0]['imageCd']);
            $this->assertSame('https://test.jp/test.jpg', $result['topImages'][0]['imageUrl']);
            $this->assertArrayHasKey('openingHours', $result);
            $this->assertCount(1, $result['openingHours']);
            $this->assertSame($openingHour->id, $result['openingHours'][0]['id']);
            $this->assertSame('10111110', $result['openingHours'][0]['week']);
            $this->assertSame('07:00:00', $result['openingHours'][0]['openTime']);
            $this->assertSame('21:00:00', $result['openingHours'][0]['closeTime']);
            $this->assertSame('ALL_DAY', $result['openingHours'][0]['openingHourCd']);
            $this->assertSame('20:30:00', $result['openingHours'][0]['lastOrderTime']);
            $this->assertArrayHasKey('evaluations', $result);
            $this->assertCount(1, $result['evaluations']);
            $this->assertSame('GOOD_DEAL', $result['evaluations'][0]['evaluationCd']);
            $this->assertSame(100.0, $result['evaluations'][0]['percentage']);
            $this->assertArrayHasKey('station', $result);
            $this->assertCount(1, $result['station']);
            $this->assertSame($station->id, $result['station'][0]['id']);
            $this->assertSame('テスト駅', $result['station'][0]['name']);
            $this->assertSame(100.0, $result['station'][0]['latitude']);
            $this->assertSame(200.0, $result['station'][0]['longitude']);
            $this->assertSame($storeId, $result['id']);
            $this->assertSame('テスト店舗', $result['name']);
            $this->assertSame('testaliasname', $result['aliasName']);
            $this->assertSame('テスト住所1 テスト住所2 テスト住所3', $result['address']);
            $this->assertSame('1234567', $result['postalCode']);
            $this->assertSame('0311112222', $result['tel']);
            $this->assertSame('0311113333', $result['telOrder']);
            $this->assertSame(300.0, $result['latitude']);
            $this->assertSame(400.0, $result['longitude']);
            $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result['email_1']);
            $this->assertSame(1000, $result['daytimeBudgetLowerLimit']);
            $this->assertSame(1500, $result['daytimeBudgetLimit']);
            $this->assertSame('駅から徒歩5分です。', $result['access']);
            $this->assertSame('アカウント', $result['account']);
            $this->assertSame('備考です。', $result['remarks']);
            $this->assertSame('店舗情報説明です。', $result['description']);
            $this->assertSame('0311114444', $result['fax']);
            $this->assertSame(1, $result['useFax']);
            $this->assertSame('10111110', $result['regularHoliday']);
            $this->assertSame(2000, $result['nightBudgetLowerLimit']);
            $this->assertSame(3000, $result['nightBudgetLimit']);
            $this->assertSame(1, $result['canCard']);
            $this->assertSame('MASTER,OTHER', $result['cardTypes']);
            $this->assertSame(1, $result['canDigitalMoney']);
            $this->assertSame('NANACO,WAON,EDY,PAYPAY,MERPAY', $result['digitalMoneyTypes']);
            $this->assertSame(1, $result['hasPrivateRoom']);
            $this->assertSame('10-20_PEOPLE,20-30_PEOPLE,30_OVER_PEOPLE', $result['privateRoomTypes']);
            $this->assertSame(1, $result['hasParking']);
            $this->assertSame(1, $result['hasCoinParking']);
            $this->assertSame(100, $result['numberOfSeats']);
            $this->assertSame(1, $result['canCharter']);
            $this->assertSame('20-50_PEOPLE_C', $result['charterTypes']);
            $this->assertSame(1, $result['smoking']);
            $this->assertSame('SEPARATE', $result['smokingTypes']);
            $this->assertFalse($result['isFavorite']);

            // ログインして、メソッド呼び出し
            $user = $this->_createCmTmUser();
            $request = new Request();
            $request->merge([
                'loginId' => 'gourmet-test1@adventure-inc.co.jp',
                'password' =>  'gourmettest123',
            ]);
            $login = $this->app->make('App\Modules\UserLogin')->login($request);
            $result = $this->storeService->get($storeId);
            $this->assertFalse($result['isFavorite']);   // ログインユーザーのお気に入りに登録されていないこと

            // お気に入り登録して、メソッド再呼び出し
            $this->_createFavorite($user->user_id, $storeId);
            $result = $this->storeService->get($storeId);
            $this->assertTrue($result['isFavorite']);   // ログインユーザーのお気に入りに登録されていること
        }
    }

    public function testGetStoreTakeoutMenu()
    {
        list($store, $menu, $menuPrice, $image, $genre) = $this->_createDataForTestGetStoreTakeoutMenu();
        $result = $this->storeService->getStoreTakeoutMenu($store->id, '2022-10-01', '10:00:00');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('description', $result[0]);
        $this->assertArrayHasKey('stockNumber', $result[0]);
        $this->assertArrayHasKey('thumbImage', $result[0]);
        $this->assertArrayHasKey('id', $result[0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result[0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result[0]['thumbImage']);
        $this->assertArrayHasKey('price', $result[0]);
        $this->assertArrayHasKey('id', $result[0]['price']);
        $this->assertArrayHasKey('priceCd', $result[0]['price']);
        $this->assertArrayHasKey('price', $result[0]['price']);
        $this->assertArrayHasKey('takeoutGenres', $result[0]);
        $this->assertCount(1, $result[0]['takeoutGenres']);
        $this->assertArrayHasKey('id', $result[0]['takeoutGenres'][0]);
        $this->assertArrayHasKey('name', $result[0]['takeoutGenres'][0]);
        $this->assertArrayHasKey('genreCd', $result[0]['takeoutGenres'][0]);
        $this->assertArrayHasKey('appCd', $result[0]['takeoutGenres'][0]);
        $this->assertArrayHasKey('path', $result[0]['takeoutGenres'][0]);
        $this->assertArrayHasKey('level', $result[0]['takeoutGenres'][0]);
        $this->assertSame($menu->id, $result[0]['id']);
        $this->assertSame('テストメニュー名', $result[0]['name']);
        $this->assertSame('テストメニュー説明', $result[0]['description']);
        $this->assertSame(5, $result[0]['stockNumber']);
        $this->assertSame($image->id, $result[0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result[0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result[0]['thumbImage']['imageUrl']);
        $this->assertSame($menuPrice->id, $result[0]['price']['id']);
        $this->assertSame('NORMAL', $result[0]['price']['priceCd']);
        $this->assertSame('1500', $result[0]['price']['price']);
        $this->assertSame($genre->id, $result[0]['takeoutGenres'][0]['id']);
        $this->assertSame('test4', $result[0]['takeoutGenres'][0]['name']);
        $this->assertSame('test4', $result[0]['takeoutGenres'][0]['genreCd']);
        $this->assertSame('TORS', $result[0]['takeoutGenres'][0]['appCd']);
        $this->assertSame('/b-cooking/test2/test3', $result[0]['takeoutGenres'][0]['path']);
        $this->assertSame(4, $result[0]['takeoutGenres'][0]['level']);
    }

    public function testGetStoreRestaurantMenu()
    {
        // memo::外部API連携の処理は、連携先でのテストデータが作れないため、テスト対象外としておく

        // 存在しない店舗
        $result = $this->storeService->getStoreRestaurantMenu(0, '2022-10-01', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // テストデータ（ebicaAPI設定なし）を登録（店舗非公開）
        list($store, $menu, $menu2) = $this->_createDataFotTestGetStoreRestaurantMenu();
        $storeId = $store->id;
        $menuId = $menu->id;
        $menuId2 = $menu2->id;

        // 非公開店舗
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2022-10-01', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 公開店舗
        Store::find($storeId)->update(['published' => 1]);

        // 店舗の営業時間情報を登録
        $this->_createStoreDataForTestGetStoreRestaurantMenu($storeId);
        // 店舗メニューを公開
        Menu::whereIn('id', [$menuId, $menuId2])->update(['published' => 1]);

        // 店舗メニューの価格未登録
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2022-10-01', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // メニューの価格情報追加
        list($menuPrice, $menuImage) = $this->_createMenuDataForTestGetStoreRestaurantMenu($menuId);

        // 予約日が過去
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2022-10-01', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 予約日が未来＆店舗定休日(水曜日）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-06', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 予約日が未来＆営業時間定休日(火曜日)
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-05', '10:00:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 予約日が未来＆定休日以外＆メニュー提供不可日(木曜日)
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-07', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 予約日が未来＆定休日以外＆メニュー提供可能日(金曜日)＆在庫なし
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 在庫登録
        $this->_createVacancy($storeId, '2999-03-08', '09:00:00', 0, 1, 0);    // headcount:1 stock:0
        $this->_createVacancy($storeId, '2999-03-08', '10:00:00', 1, 2, 0);    // headcount:2 stock:1

        // 予約日が未来＆定休日以外＆メニュー提供可能日(金曜日)＆在庫あり
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(1, $result['restaurantMenu']);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('name', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('description', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('providedTime', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('thumbImage', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('numberOfCourse', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfLowerLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfUpperLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('freeDrinks', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('onlySeat', $result['restaurantMenu'][0]);
        $this->assertSame($menuId, $result['restaurantMenu'][0]['id']);
        $this->assertSame('テストメニュー名', $result['restaurantMenu'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['restaurantMenu'][0]['description']);
        $this->assertSame('90', $result['restaurantMenu'][0]['providedTime']);
        $this->assertSame($menuImage->id, $result['restaurantMenu'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['restaurantMenu'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restaurantMenu'][0]['thumbImage']['imageUrl']);
        $this->assertSame($menuPrice->id, $result['restaurantMenu'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['restaurantMenu'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['restaurantMenu'][0]['price']['price']);
        $this->assertSame('5', $result['restaurantMenu'][0]['numberOfCourse']);
        $this->assertNull($result['restaurantMenu'][0]['availableNumberOfLowerLimit']);     // メニューの上下制限人数が未設定
        $this->assertNull($result['restaurantMenu'][0]['availableNumberOfUpperLimit']);     // メニューの上下制限人数が未設定
        $this->assertTrue($result['restaurantMenu'][0]['freeDrinks']);
        $this->assertFalse($result['restaurantMenu'][0]['onlySeat']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);

        // メニューの上下制限人数を設定
        Menu::whereIn('id', [$menuId, $menuId2])->update(['available_number_of_lower_limit' => 1, 'available_number_of_upper_limit' => 1]);

        // 予約日が未来＆定休日以外＆メニュー提供可能日(金曜日)＆在庫あり（予約人数許容範囲外）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // メニューの上下制限人数を設定
        Menu::whereIn('id', [$menuId, $menuId2])->update(['available_number_of_lower_limit' => 2, 'available_number_of_upper_limit' => 10]);
        // 在庫登録（ただし、headcountは下限より下）
        $this->_createVacancy($storeId, '2999-03-08', '10:00:00', 2, 1, 0);    // headcount:1 stock:2

        // 予約日が未来＆定休日以外＆メニュー提供可能日(金曜日)＆在庫あり（予約人数許容範囲内）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(1, $result['restaurantMenu']);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('name', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('description', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('providedTime', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('thumbImage', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('numberOfCourse', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfLowerLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfUpperLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('freeDrinks', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('onlySeat', $result['restaurantMenu'][0]);
        $this->assertSame($menuId, $result['restaurantMenu'][0]['id']);
        $this->assertSame('テストメニュー名', $result['restaurantMenu'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['restaurantMenu'][0]['description']);
        $this->assertSame('90', $result['restaurantMenu'][0]['providedTime']);
        $this->assertSame($menuImage->id, $result['restaurantMenu'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['restaurantMenu'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restaurantMenu'][0]['thumbImage']['imageUrl']);
        $this->assertSame($menuPrice->id, $result['restaurantMenu'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['restaurantMenu'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['restaurantMenu'][0]['price']['price']);
        $this->assertSame('5', $result['restaurantMenu'][0]['numberOfCourse']);
        $this->assertSame(2, $result['restaurantMenu'][0]['availableNumberOfLowerLimit']);     // メニューの上下制限人数は設定値
        $this->assertSame(10, $result['restaurantMenu'][0]['availableNumberOfUpperLimit']);    // メニューの上下制限人数は設定値
        $this->assertTrue($result['restaurantMenu'][0]['freeDrinks']);
        $this->assertFalse($result['restaurantMenu'][0]['onlySeat']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);

        // テスト店舗(ebicaAPI設定あり）を登録。（ただし、在庫確認は4以上後とし、vacancyテーブルから情報取得し処理できることを確認する）
        list($store, $menu, $menu2) = $this->_createDataFotTestGetStoreRestaurantMenu(1, true);
        $storeId = $store->id;
        $menuId = $menu->id;
        $menuId2 = $menu2->id;
        $this->_createStoreDataForTestGetStoreRestaurantMenu($storeId);
        list($menuPrice, $menuImage) = $this->_createMenuDataForTestGetStoreRestaurantMenu($menuId);

        // テスト店舗(ebicaAPI設定あり）＆店舗在庫なし
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // 在庫登録
        $this->_createVacancy($storeId, '2999-03-08', '09:00:00', 0, 1, 0);    // headcount:1 stock:0
        $this->_createVacancy($storeId, '2999-03-08', '10:00:00', 1, 2, 0);    // headcount:2 stock:1

        // テスト店舗(ebicaAPI設定あり）＆店舗在庫あり（日付未定）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'true');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);

        // テスト店舗(ebicaAPI設定あり）＆店舗在庫あり（予約日が未来）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(1, $result['restaurantMenu']);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('name', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('description', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('providedTime', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('thumbImage', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('numberOfCourse', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfLowerLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfUpperLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('freeDrinks', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('onlySeat', $result['restaurantMenu'][0]);
        $this->assertSame($menuId, $result['restaurantMenu'][0]['id']);
        $this->assertSame('テストメニュー名', $result['restaurantMenu'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['restaurantMenu'][0]['description']);
        $this->assertSame('90', $result['restaurantMenu'][0]['providedTime']);
        $this->assertSame($menuImage->id, $result['restaurantMenu'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['restaurantMenu'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restaurantMenu'][0]['thumbImage']['imageUrl']);
        $this->assertSame($menuPrice->id, $result['restaurantMenu'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['restaurantMenu'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['restaurantMenu'][0]['price']['price']);
        $this->assertSame('5', $result['restaurantMenu'][0]['numberOfCourse']);
        $this->assertNull($result['restaurantMenu'][0]['availableNumberOfLowerLimit']);     // メニューの上下制限人数が未設定
        $this->assertNull($result['restaurantMenu'][0]['availableNumberOfUpperLimit']);     // メニューの上下制限人数が未設定
        $this->assertTrue($result['restaurantMenu'][0]['freeDrinks']);
        $this->assertFalse($result['restaurantMenu'][0]['onlySeat']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);

        // メニューの上下制限人数を設定
        Menu::whereIn('id', [$menuId, $menuId2])->update(['available_number_of_lower_limit' => 1, 'available_number_of_upper_limit' => 1]);

        // 予約日が未来＆定休日以外＆メニュー提供可能日(金曜日)＆在庫あり（予約人数許容範囲外）
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);

        // メニューの上下制限人数を設定
        Menu::whereIn('id', [$menuId, $menuId2])->update(['available_number_of_lower_limit' => 2, 'available_number_of_upper_limit' => 10]);
        // 在庫登録（ただし、headcountは下限より下）
        $this->_createVacancy($storeId, '2999-03-08', '10:00:00', 2, 1, 0);    // headcount:1 stock:2

        // テスト店舗(ebicaAPI設定あり）＆店舗在庫あり（予約日が未来）※headcount=1のデータは取得できないこと
        $result = $this->storeService->getStoreRestaurantMenu($storeId, '2999-03-08', '10:00', 2, 'false');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(1, $result['restaurantMenu']);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('name', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('description', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('providedTime', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('thumbImage', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['restaurantMenu'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('id', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('price', $result['restaurantMenu'][0]['price']);
        $this->assertArrayHasKey('numberOfCourse', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfLowerLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('availableNumberOfUpperLimit', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('freeDrinks', $result['restaurantMenu'][0]);
        $this->assertArrayHasKey('onlySeat', $result['restaurantMenu'][0]);
        $this->assertSame($menuId, $result['restaurantMenu'][0]['id']);
        $this->assertSame('テストメニュー名', $result['restaurantMenu'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['restaurantMenu'][0]['description']);
        $this->assertSame('90', $result['restaurantMenu'][0]['providedTime']);
        $this->assertSame($menuImage->id, $result['restaurantMenu'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['restaurantMenu'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restaurantMenu'][0]['thumbImage']['imageUrl']);
        $this->assertSame($menuPrice->id, $result['restaurantMenu'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['restaurantMenu'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['restaurantMenu'][0]['price']['price']);
        $this->assertSame('5', $result['restaurantMenu'][0]['numberOfCourse']);
        $this->assertSame(2, $result['restaurantMenu'][0]['availableNumberOfLowerLimit']);     // メニューの上下制限人数は設定値
        $this->assertSame(10, $result['restaurantMenu'][0]['availableNumberOfUpperLimit']);    // メニューの上下制限人数は設定値
        $this->assertTrue($result['restaurantMenu'][0]['freeDrinks']);
        $this->assertFalse($result['restaurantMenu'][0]['onlySeat']);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(1, $result['stocks'][0]['sets']);
    }

    public function testGetStoreReview()
    {
        list($store, $review, $reviewImage) = $this->_createDataForTestGetStoreReview();
        $reviewCreatedAt = new Carbon($review->created_at);

        $result = $this->storeService->getStoreReview($store->id);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('userId', $result[0]);
        $this->assertArrayHasKey('username', $result[0]);
        $this->assertArrayHasKey('body', $result[0]);
        $this->assertArrayHasKey('evaluationCd', $result[0]);
        $this->assertArrayHasKey('image', $result[0]);
        $this->assertIsArray($result[0]['image']);
        $this->assertArrayHasKey('id', $result[0]['image']);
        $this->assertArrayHasKey('imageCd', $result[0]['image']);
        $this->assertArrayHasKey('imageUrl', $result[0]['image']);
        $this->assertArrayHasKey('createdAt', $result[0]);
        $this->assertSame($review->id, $result[0]['id']);
        $this->assertSame(1, $result[0]['userId']);
        $this->assertSame('グルメ太郎', $result[0]['username']);
        $this->assertSame('テストクチコミ', $result[0]['body']);
        $this->assertSame('GOOD_DEAL', $result[0]['evaluationCd']);
        $this->assertSame($reviewImage->id, $result[0]['image']['id']);
        $this->assertSame('STORE_USER_POST', $result[0]['image']['imageCd']);
        $this->assertSame('https://test.jp/test2.jpg', $result[0]['image']['imageUrl']);
        $this->assertSame($reviewCreatedAt->format('Y-m-d H:i:s'), $result[0]['createdAt']);
    }

    public function testGetStoreImage()
    {
        list($store, $storeImage, $storeImage2, $storeImage3, $storeImage4, $storeImage5, $menu, $menuPrice, $menuImage, $review, $reviewImage) = $this->_createDateForTestGetStoreImage();

        $result = $this->storeService->getStoreImage($store->id);
        $this->assertCount(5, $result);
        // 店舗画像１つ目が取得できる
        $this->assertArrayHasKey('image', $result[0]);
        $this->assertIsArray($result[0]['image']);
        $this->assertArrayHasKey('id', $result[0]['image']);
        $this->assertArrayHasKey('imageCd', $result[0]['image']);
        $this->assertArrayHasKey('imageUrl', $result[0]['image']);
        $this->assertArrayHasKey('isPost', $result[0]);
        $this->assertSame($storeImage->id, $result[0]['image']['id']);
        $this->assertSame('STORE_INSIDE', $result[0]['image']['imageCd']);
        $this->assertSame('https://test.jp/test11.jpg', $result[0]['image']['imageUrl']);
        $this->assertFalse($result[0]['isPost']);
        // 店舗画像2つ目が取得できる
        $this->assertArrayHasKey('image', $result[1]);
        $this->assertIsArray($result[1]['image']);
        $this->assertArrayHasKey('id', $result[1]['image']);
        $this->assertArrayHasKey('imageCd', $result[1]['image']);
        $this->assertArrayHasKey('imageUrl', $result[1]['image']);
        $this->assertArrayHasKey('isPost', $result[1]);
        $this->assertSame($storeImage2->id, $result[1]['image']['id']);
        $this->assertSame('STORE_OUTSIDE', $result[1]['image']['imageCd']);
        $this->assertSame('https://test.jp/test12.jpg', $result[1]['image']['imageUrl']);
        $this->assertFalse($result[1]['isPost']);
        // 店舗画像3つ目が取得できる
        $this->assertArrayHasKey('image', $result[2]);
        $this->assertIsArray($result[2]['image']);
        $this->assertArrayHasKey('id', $result[2]['image']);
        $this->assertArrayHasKey('imageCd', $result[2]['image']);
        $this->assertArrayHasKey('imageUrl', $result[2]['image']);
        $this->assertArrayHasKey('isPost', $result[2]);
        $this->assertSame($storeImage3->id, $result[2]['image']['id']);
        $this->assertSame('OTHER', $result[2]['image']['imageCd']);
        $this->assertSame('https://test.jp/test13.jpg', $result[2]['image']['imageUrl']);
        $this->assertFalse($result[2]['isPost']);
        // 店舗画像４つ目は取得されない
        $this->assertNotSame($storeImage4->id, $result[0]['image']['id']);
        $this->assertNotSame($storeImage4->id, $result[1]['image']['id']);
        $this->assertNotSame($storeImage4->id, $result[2]['image']['id']);
        $this->assertNotSame($storeImage4->id, $result[3]['image']['id']);
        $this->assertNotSame($storeImage4->id, $result[4]['image']['id']);
        // 店舗画像5つ目は取得されない
        $this->assertNotSame($storeImage5->id, $result[0]['image']['id']);
        $this->assertNotSame($storeImage5->id, $result[1]['image']['id']);
        $this->assertNotSame($storeImage5->id, $result[2]['image']['id']);
        $this->assertNotSame($storeImage5->id, $result[3]['image']['id']);
        $this->assertNotSame($storeImage5->id, $result[4]['image']['id']);
        // メニュー画像が取得できる
        $this->assertArrayHasKey('image', $result[3]);
        $this->assertIsArray($result[3]['image']);
        $this->assertArrayHasKey('id', $result[3]['image']);
        $this->assertArrayHasKey('imageCd', $result[3]['image']);
        $this->assertArrayHasKey('imageUrl', $result[3]['image']);
        $this->assertArrayHasKey('isPost', $result[3]);
        $this->assertArrayHasKey('menu', $result[3]);
        $this->assertIsArray($result[3]['menu']);
        $this->assertArrayHasKey('id', $result[3]['menu']);
        $this->assertArrayHasKey('name', $result[3]['menu']);
        $this->assertArrayHasKey('appCd', $result[3]['menu']);
        $this->assertArrayHasKey('price', $result[3]['menu']);
        $this->assertIsArray($result[3]['menu']['price']);
        $this->assertArrayHasKey('id', $result[3]['menu']['price']);
        $this->assertArrayHasKey('priceCd', $result[3]['menu']['price']);
        $this->assertArrayHasKey('price', $result[3]['menu']['price']);
        $this->assertArrayHasKey('reviews', $result[3]);
        $this->assertCount(1, $result[3]['reviews']);
        $this->assertArrayHasKey('id', $result[3]['reviews'][0]);
        $this->assertArrayHasKey('userId', $result[3]['reviews'][0]);
        $this->assertArrayHasKey('username', $result[3]['reviews'][0]);
        $this->assertArrayHasKey('body', $result[3]['reviews'][0]);
        $this->assertArrayHasKey('evaluationCd', $result[3]['reviews'][0]);
        $this->assertArrayHasKey('createdAt', $result[3]['reviews'][0]);
        $this->assertSame($menuImage->id, $result[3]['image']['id']);
        $this->assertSame('MENU_MAIN', $result[3]['image']['imageCd']);
        $this->assertSame('https://test.jp/test2.jpg', $result[3]['image']['imageUrl']);
        $this->assertFalse($result[3]['isPost']);
        $this->assertSame($menu->id, $result[3]['menu']['id']);
        $this->assertSame('テストメニュー', $result[3]['menu']['name']);
        $this->assertSame('RS', $result[3]['menu']['appCd']);
        $this->assertSame($menuPrice->id, $result[3]['menu']['price']['id']);
        $this->assertSame('NORMAL', $result[3]['menu']['price']['priceCd']);
        $this->assertSame('1000', $result[3]['menu']['price']['price']);
        $this->assertSame($review->id, $result[3]['reviews'][0]['id']);
        $this->assertSame(1, $result[3]['reviews'][0]['userId']);
        $this->assertSame('テストクチコミ', $result[3]['reviews'][0]['body']);
        $this->assertSame('GOOD_DEAL', $result[3]['reviews'][0]['evaluationCd']);
        $this->assertSame($review->created_at, $result[3]['reviews'][0]['createdAt']);
        // レビュー分が取得できる
        $this->assertArrayHasKey('image', $result[4]);
        $this->assertIsArray($result[4]['image']);
        $this->assertArrayHasKey('id', $result[4]['image']);
        $this->assertArrayHasKey('imageCd', $result[4]['image']);
        $this->assertArrayHasKey('imageUrl', $result[4]['image']);
        $this->assertArrayHasKey('isPost', $result[4]);
        $this->assertArrayHasKey('menu', $result[4]);
        $this->assertIsArray($result[4]['menu']);
        $this->assertArrayHasKey('id', $result[4]['menu']);
        $this->assertArrayHasKey('name', $result[4]['menu']);
        $this->assertArrayHasKey('appCd', $result[4]['menu']);
        $this->assertArrayHasKey('price', $result[4]['menu']);
        $this->assertIsArray($result[4]['menu']['price']);
        $this->assertArrayHasKey('id', $result[4]['menu']['price']);
        $this->assertArrayHasKey('priceCd', $result[4]['menu']['price']);
        $this->assertArrayHasKey('price', $result[4]['menu']['price']);
        $this->assertArrayHasKey('reviews', $result[4]);
        $this->assertIsArray($result[4]['reviews']);
        $this->assertArrayHasKey('id', $result[4]['reviews']);
        $this->assertArrayHasKey('userId', $result[4]['reviews']);
        $this->assertArrayHasKey('username', $result[4]['reviews']);
        $this->assertArrayHasKey('body', $result[4]['reviews']);
        $this->assertArrayHasKey('evaluationCd', $result[4]['reviews']);
        $this->assertArrayHasKey('createdAt', $result[4]['reviews']);
        $this->assertSame($reviewImage->id, $result[4]['image']['id']);
        $this->assertSame('STORE_USER_POST', $result[4]['image']['imageCd']);
        $this->assertSame('https://test.jp/test3.jpg', $result[4]['image']['imageUrl']);
        $this->assertTrue($result[4]['isPost']);
        $this->assertSame($menu->id, $result[4]['menu']['id']);
        $this->assertSame('テストメニュー', $result[4]['menu']['name']);
        $this->assertSame('RS', $result[3]['menu']['appCd']);
        $this->assertSame($menuPrice->id, $result[4]['menu']['price']['id']);
        $this->assertSame('NORMAL', $result[4]['menu']['price']['priceCd']);
        $this->assertSame('1000', $result[4]['menu']['price']['price']);
        $this->assertSame($review->id, $result[4]['reviews']['id']);
        $this->assertSame(1, $result[4]['reviews']['userId']);
        $this->assertSame('テストクチコミ', $result[4]['reviews']['body']);
        $this->assertSame('GOOD_DEAL', $result[4]['reviews']['evaluationCd']);
        $this->assertSame($review->created_at, $result[4]['reviews']['createdAt']);
    }

    public function testGetBreadcrumb()
    {
        $this->_createDataForTestGetBreadcrumb();

        // 無効な店舗ID
        $result = $this->storeService->getBreadcrumb(0, ['isStore' => true, 'isSearch' => false]);
        $this->assertCount(0, $result['elements']); // 何も取得できない

        // 店舗のパンクズ取得
        // 仕様 メイン親ジャンル > メイン親ジャンル/大エリア > メインジャンル/中エリア > メインジャンル/店名
        // 例) 和食/寿司・魚/寿司/東京 寿司/恵比寿・代官山・中目黒 寿司/恵比寿 寿司/店名
        $result = $this->storeService->getBreadcrumb($this->store->id, ['isStore' => true, 'isSearch' => false]);
        $this->assertCount(4, $result['elements']);
        // メイン親ジャンルが取得できる
        $this->assertArrayHasKey('text', $result['elements'][0]);
        $this->assertSame('メキシカン', $result['elements'][0]['text']);
        $this->assertArrayHasKey('url', $result['elements'][0]);
        $this->assertSame('genre/b-cooking/m-mexican', $result['elements'][0]['url']);
        // メイン親ジャンル/大エリアが取得できる
        $this->assertArrayHasKey('text', $result['elements'][1]);
        $this->assertSame('東京/メキシカン', $result['elements'][1]['text']);
        $this->assertArrayHasKey('url', $result['elements'][1]);
        $this->assertSame('genre/s-chimichanga/am-testtokyo', $result['elements'][1]['url']);

        // メインジャンル/中エリアが取得できる
        $this->assertArrayHasKey('text', $result['elements'][2]);
        $this->assertSame('恵比寿・代官山・中目黒/チミチャンガ', $result['elements'][2]['text']);
        $this->assertArrayHasKey('url', $result['elements'][2]);
        $this->assertSame('genre/s-chimichanga/am-ebisu-daikanyama-nakameguro', $result['elements'][2]['url']);

        // メインジャンル/店名が取得できる
        $this->assertArrayHasKey('text', $result['elements'][3]);
        $this->assertSame('チミチャンガ/ロバティトス', $result['elements'][3]['text']);
        $this->assertArrayHasKey('url', $result['elements'][3]);
        $this->assertSame('sh-lobatitos', $result['elements'][3]['url']);

        // 検索のパンクズ取得(エリア)
        $result = $this->storeService->getBreadcrumb($this->store->id, ['isStore' => false, 'isSearch' => true, 'suggestCd' => 'AREA', 'suggenstText' => '東京']);
        $this->assertCount(1, $result['elements']);

        // エリアが取得できる
        $this->assertArrayHasKey('text', $result['elements'][0]);
        $this->assertSame('東京', $result['elements'][0]['text']);
        $this->assertArrayHasKey('url', $result['elements'][0]);
        $this->assertSame('area/ab-cooking/testtokyo', $result['elements'][0]['url']);

        // 検索のパンクズ取得(エリア+料理ジャンル)
        $result = $this->storeService->getBreadcrumb($this->store->id, [
            'isStore' => false,
            'isSearch' => true,
            'suggestCd' => 'AREA',
            'suggenstText' => '東京',
            'cookingGenreCd' => 'm-mexican',
        ]);
        $this->assertCount(2, $result['elements']);

        // エリアが取得できる
        $this->assertArrayHasKey('text', $result['elements'][0]);
        $this->assertSame('東京', $result['elements'][0]['text']);
        $this->assertArrayHasKey('url', $result['elements'][0]);
        $this->assertSame('area/ab-cooking/testtokyo', $result['elements'][0]['url']);
        // エリア/料理ジャンルが取得できる
        $this->assertArrayHasKey('text', $result['elements'][1]);
        $this->assertSame('東京 メキシカン', $result['elements'][1]['text']);
        $this->assertArrayHasKey('url', $result['elements'][1]);
        $this->assertSame('genre/m-mexican/am-testtokyo', $result['elements'][1]['url']);

        // 検索のパンクズ取得(エリア+メニュージャンル)
        $result = $this->storeService->getBreadcrumb(1, [
            'isStore' => false,
            'isSearch' => true,
            'suggestCd' => 'AREA',
            'suggenstText' => '東京',
            'cookingGenreCd' => 'm-mexican',
            'menuGenreCd' => 's-chimichanga',
        ]);
        $this->assertCount(4, $result['elements']);
        // エリアが取得できる
        $this->assertArrayHasKey('text', $result['elements'][0]);
        $this->assertSame('チミチャンガ', $result['elements'][0]['text']);
        $this->assertArrayHasKey('url', $result['elements'][0]);
        $this->assertSame('genre/s-chimichanga', $result['elements'][0]['url']);
        // メニュージャンルが取得できる
        $this->assertArrayHasKey('text', $result['elements'][1]);
        $this->assertSame('メキシカン', $result['elements'][1]['text']);
        $this->assertArrayHasKey('url', $result['elements'][1]);
        $this->assertSame('genre/b-cooking/m-mexican', $result['elements'][1]['url']);
        // メイン親ジャンルが取得できる
        $this->assertArrayHasKey('text', $result['elements'][2]);
        $this->assertSame('チミチャンガ', $result['elements'][2]['text']);
        $this->assertArrayHasKey('url', $result['elements'][2]);
        $this->assertSame('genre/b-cooking/m-mexican/s-chimichanga', $result['elements'][2]['url']);
        // エリア 親ジャンルが取得できる
        $this->assertArrayHasKey('text', $result['elements'][3]);
        $this->assertSame('東京 チミチャンガ', $result['elements'][3]['text']);
        $this->assertArrayHasKey('url', $result['elements'][3]);
        $this->assertSame('genre/s-chimichanga/am-testtokyo', $result['elements'][3]['url']);

        // 検索のパンクズ取得（エリア以外のコード+メニュージャンル）
        $result = $this->storeService->getBreadcrumb(1, [
            'isStore' => false,
            'isSearch' => true,
            'suggestCd' => 'TEST',
            'suggenstText' => '東京',
            'cookingGenreCd' => 'm-mexican',
            'menuGenreCd' => 's-chimichanga',
        ]);
        $this->assertCount(0, $result['elements']); // 何も取得できない
    }

    public function testStoreSearch()
    {
        list($store, $menu, $menuPrice) = $this->_createDataForTestStoreSearch();

        $result = $this->storeService->storeSearch([
            'appCd' => 'RS',
            'suggestCd' => 'CURRENT_LOC',
            'latitude' => '100.00001',
            'longitude' => '200',
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('stores', $result);
        // 店舗情報が取得できる
        $this->assertCount(1, $result['stores']);
        $this->assertArrayHasKey('id', $result['stores'][0]);
        $this->assertArrayHasKey('name', $result['stores'][0]);
        $this->assertArrayHasKey('access', $result['stores'][0]);
        $this->assertArrayHasKey('daytimeBudgetLowerLimit', $result['stores'][0]);
        $this->assertArrayHasKey('nightBudgetLowerLimit', $result['stores'][0]);
        $this->assertArrayHasKey('latitude', $result['stores'][0]);
        $this->assertArrayHasKey('longitude', $result['stores'][0]);
        $this->assertArrayHasKey('appCd', $result['stores'][0]);
        $this->assertArrayHasKey('lowerOrdersTime', $result['stores'][0]);
        $this->assertArrayHasKey('priceLevel', $result['stores'][0]);
        $this->assertSame($store->id, $result['stores'][0]['id']);
        $this->assertSame('テストtest店舗', $result['stores'][0]['name']);
        $this->assertSame('最寄り駅から徒歩５分の場所です。', $result['stores'][0]['access']);
        $this->assertSame(1000, $result['stores'][0]['daytimeBudgetLowerLimit']);
        $this->assertSame(1500, $result['stores'][0]['nightBudgetLowerLimit']);
        $this->assertSame(1, $result['stores'][0]['distance']);
        $this->assertSame(100.0, $result['stores'][0]['latitude']);
        $this->assertSame(200.0, $result['stores'][0]['longitude']);
        $this->assertSame('TORS', $result['stores'][0]['appCd']);
        $this->assertSame(180, $result['stores'][0]['lowerOrdersTime']);
        $this->assertSame(2, $result['stores'][0]['priceLevel']);
        // 店舗ジャンルは取得できない（未設定のため、０件）
        $this->assertArrayHasKey('storeGenres', $result['stores'][0]);
        $this->assertCount(0, $result['stores'][0]['storeGenres']);
        // 店舗画像は取得できない（未設定のため、０件）
        $this->assertArrayHasKey('storeImage', $result['stores'][0]);
        $this->assertCount(0, $result['stores'][0]['storeImage']);
        // おすすめメニューが取得できる
        $this->assertArrayHasKey('recommendMenu', $result['stores'][0]);
        $this->assertArrayHasKey('id', $result['stores'][0]['recommendMenu']);
        $this->assertArrayHasKey('name', $result['stores'][0]['recommendMenu']);
        $this->assertArrayHasKey('price', $result['stores'][0]['recommendMenu']);
        $this->assertArrayHasKey('id', $result['stores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('priceCd', $result['stores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('price', $result['stores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('distance', $result['stores'][0]);
        $this->assertArrayHasKey('openingHours', $result['stores'][0]);
        $this->assertSame($menu->id, $result['stores'][0]['recommendMenu']['id']);
        $this->assertSame('テストレストランメニュー', $result['stores'][0]['recommendMenu']['name']);
        $this->assertSame($menuPrice->id, $result['stores'][0]['recommendMenu']['price']['id']);
        $this->assertSame('NORMAL', $result['stores'][0]['recommendMenu']['price']['priceCd']);
        $this->assertSame('2500', $result['stores'][0]['recommendMenu']['price']['price']);
        // 店舗営業時間が取得できない（未設定のため、０件）
        $this->assertCount(0, $result['stores'][0]['openingHours']);
        // 検索結果件数などが取得できる
        $this->assertArrayHasKey('sumCount', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1, $result['sumCount']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(1.0, $result['pageMax']);

        // ジャンル、店舗画像、営業時間の登録
        list($storeImage, $openingHour, $genre, $genre2) = $this->_createStoreDateForTestStoreSearch($store);

        $result = $this->storeService->storeSearch([
            'appCd' => 'RS',
            'suggestCd' => 'CURRENT_LOC',
            'latitude' => '100.00001',
            'longitude' => '200',
        ]);
        $this->assertIsArray($result);
        // 上記の差分だけ確認
        // 料理ジャンルが取得できる
        $this->assertCount(2, $result['stores'][0]['storeGenres']);      // ジャンルあり（2件）
        $this->assertArrayHasKey('id', $result['stores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('name', $result['stores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('genreCd', $result['stores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('appCd', $result['stores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('path', $result['stores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('isDelegate', $result['stores'][0]['storeGenres'][0]);
        $this->assertSame($genre->id, $result['stores'][0]['storeGenres'][0]['id']);
        $this->assertSame('テスト料理ジャンル', $result['stores'][0]['storeGenres'][0]['name']);
        $this->assertSame('searchtest', $result['stores'][0]['storeGenres'][0]['genreCd']);
        $this->assertSame('TORS', $result['stores'][0]['storeGenres'][0]['appCd']);
        $this->assertSame('/b-cooking/searchtest', $result['stores'][0]['storeGenres'][0]['path']);
        $this->assertSame(1, $result['stores'][0]['storeGenres'][0]['isDelegate']);
        // メニュージャンルが取得できる
        $this->assertArrayHasKey('id', $result['stores'][0]['storeGenres'][1]);
        $this->assertArrayHasKey('name', $result['stores'][0]['storeGenres'][1]);
        $this->assertArrayHasKey('genreCd', $result['stores'][0]['storeGenres'][1]);
        $this->assertArrayHasKey('appCd', $result['stores'][0]['storeGenres'][1]);
        $this->assertArrayHasKey('path', $result['stores'][0]['storeGenres'][1]);
        $this->assertArrayHasKey('isDelegate', $result['stores'][0]['storeGenres'][1]);
        $this->assertSame($genre2->id, $result['stores'][0]['storeGenres'][1]['id']);
        $this->assertSame('テストメニュージャンル', $result['stores'][0]['storeGenres'][1]['name']);
        $this->assertSame('searchtestmenu', $result['stores'][0]['storeGenres'][1]['genreCd']);
        $this->assertSame('RS', $result['stores'][0]['storeGenres'][1]['appCd']);
        $this->assertSame('/b-cooking/searchtestmenu', $result['stores'][0]['storeGenres'][1]['path']);
        $this->assertSame(1, $result['stores'][0]['storeGenres'][1]['isDelegate']);
        // 店舗画像が取得できる
        $this->assertIsArray($result['stores'][0]['storeImage']);       // 店舗画像あり
        $this->assertArrayHasKey('id', $result['stores'][0]['storeImage']);
        $this->assertArrayHasKey('imageCd', $result['stores'][0]['storeImage']);
        $this->assertArrayHasKey('imageUrl', $result['stores'][0]['storeImage']);
        $this->assertSame($storeImage->id, $result['stores'][0]['storeImage']['id']);
        $this->assertSame('RESTAURANT_LOGO', $result['stores'][0]['storeImage']['imageCd']);
        $this->assertSame('https://test.jp/test11.jpg', $result['stores'][0]['storeImage']['imageUrl']);
        // 営業時間が取得できる
        $this->assertCount(1, $result['stores'][0]['openinghours']);
        $this->assertArrayHasKey('id', $result['stores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('openingTime', $result['stores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('closeTime', $result['stores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('code', $result['stores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('lastOrderTime', $result['stores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('week', $result['stores'][0]['openinghours'][0]);
        $this->assertSame($openingHour->id, $result['stores'][0]['openinghours'][0]['id']);
        $this->assertSame('09:00:00', $result['stores'][0]['openinghours'][0]['openingTime']);
        $this->assertSame('22:00:00', $result['stores'][0]['openinghours'][0]['closeTime']);
        $this->assertSame('ALL_DAY', $result['stores'][0]['openinghours'][0]['code']);
        $this->assertSame('21:30:00', $result['stores'][0]['openinghours'][0]['lastOrderTime']);
        $this->assertSame('011111110', $result['stores'][0]['openinghours'][0]['week']);
    }

    public function testGetCancelPolicy()
    {
        $store = $this->_createDateForTestGetCancelPolicy();

        $result = $this->storeService->getCancelPolicy($store->id, 'RS');
        $this->assertCount(2, $result);
        // キャンセルポリシー（12時間前）
        $this->assertArrayHasKey('beforeTime', $result[0]);
        $this->assertArrayHasKey('isAfter', $result[0]);
        $this->assertArrayHasKey('cancelFee', $result[0]);
        $this->assertArrayHasKey('cancelFeeUnit', $result[0]);
        $this->assertSame(12, $result[0]['beforeTime']);
        $this->assertSame(0, $result[0]['isAfter']);
        $this->assertSame(50, $result[0]['cancelFee']);
        $this->assertSame('FIXED_RATE', $result[0]['cancelFeeUnit']);
        // キャンセルポリシー（2日前）
        $this->assertArrayHasKey('beforeDay', $result[1]);
        $this->assertArrayHasKey('isAfter', $result[1]);
        $this->assertArrayHasKey('cancelFee', $result[1]);
        $this->assertArrayHasKey('cancelFeeUnit', $result[1]);
        $this->assertSame(2, $result[1]['beforeDay']);
        $this->assertSame(0, $result[1]['isAfter']);
        $this->assertSame(30, $result[1]['cancelFee']);
        $this->assertSame('FIXED_RATE', $result[1]['cancelFeeUnit']);
    }

    public function testFilterMenu()
    {
        $store = $this->_createDataForTestFunctions();

        // 空席なし
        $res = [
            'restaurantMenu' => [
                [
                    'name' => 'テストメニュー1',
                    'providedTime' => 0,
                ],
            ],
            'stocks' => [
                [
                    'vacancyTime' => '10:00:00',
                    'people' => 1,
                    'sets' => 0,
                ],
                [
                    'vacancyTime' => '11:00:00',
                    'people' => 1,
                    'sets' => 0,
                ]
            ],
        ];
        $msg = null;
        $result = $this->storeService->filterMenu($res, '2999-10-01', '10:00:00', 1, $store->id, $msg);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertCount(0, $result['stocks']);
        $this->assertSame('ご指定の日時で販売可能なメニューがありません。', $msg);

        // 空席あり
        $res = [
            'restaurantMenu' => [
                [
                    'name' => 'テストメニュー１',
                    'providedTime' => 120,
                ],
            ],
            'stocks' => [
                [
                    'vacancyTime' => '10:00:00',
                    'people' => 1,
                    'sets' => 2,
                ],
                [
                    'vacancyTime' => '10:00:00',
                    'people' => 2,
                    'sets' => 1,
                ],
                // 残席数が0の在庫
                [
                    'vacancyTime' => '10:30:00',
                    'people' => 2,
                    'sets' => 0,
                ],
                // ラストオーダー後の在庫
                [
                    'vacancyTime' => '22:00:00',
                    'people' => 1,
                    'sets' => 2,
                ]
            ],
        ];
        $msg = null;
        $result = $this->storeService->filterMenu($res, '2999-10-01', '10:00:00', 1, $store->id, $msg);
        // 対象メニューがある
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(1, $result['restaurantMenu']);
        $this->assertSame('テストメニュー１', $result['restaurantMenu'][0]['name']);
        $this->assertSame(120, $result['restaurantMenu'][0]['providedTime']);
        // 利用可能な在庫のみが取得できる
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertSame('10:00:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(1, $result['stocks'][0]['people']);
        $this->assertSame(2, $result['stocks'][0]['sets']);
        // メッセージはNULL
        $this->assertNull($msg);
    }

    public function testReturnAllEmpty()
    {
        $msg = null;
        $result = $this->storeService->returnAllEmpty(0, '10:00:00', 2, $msg);
        $this->assertArrayHasKey('restaurantMenu', $result);
        $this->assertCount(0, $result['restaurantMenu']);
        $this->assertArrayHasKey('stocks', $result);
        $this->assertCount(1, $result['stocks']);
        $this->assertArrayHasKey('vacancyTime', $result['stocks'][0]);
        $this->assertArrayHasKey('people', $result['stocks'][0]);
        $this->assertArrayHasKey('sets', $result['stocks'][0]);
        $this->assertSame('10:00:00', $result['stocks'][0]['vacancyTime']);
        $this->assertSame(2, $result['stocks'][0]['people']);
        $this->assertSame(0, $result['stocks'][0]['sets']);
        $this->assertSame('営業時間外のため注文できません。', $msg);
    }

    public function testRestaurantCheck()
    {
        $holiday = null;
        $store = $this->_createDataForTestFunctions();

        // 予約日に過去日を指定
        $msg = null;
        $this->assertFalse($this->storeService->restaurantCheck($holiday, new Carbon('2022-10-01'), new Carbon('2022-09-30'), $store, $msg));
        $this->assertSame('過ぎた日付を来店日にすることはできません。', $msg);

        // 予約日に定休日を指定
        $msg = null;
        $this->assertFalse($this->storeService->restaurantCheck($holiday, new Carbon('2022-10-01'), new Carbon('2022-10-03'), $store, $msg));
        $this->assertSame('営業時間外のため注文できません。', $msg);

        // 予約日に営業日を指定
        $msg = null;
        $this->assertTrue($this->storeService->restaurantCheck($holiday, new Carbon('2022-10-01'), new Carbon('2022-10-04'), $store, $msg));
        $this->assertNull($msg);
    }

    public function testMenuPreCheck()
    {
        list($store, $menu, $menu2) = $this->_createDateForTestMenuPreCheck();
        $menus = Menu::where('store_id', $store->id)->get();
        $holiday = null;

        // 提供可能メニューあり
        $msg = null;
        $result = $this->storeService->menuPreCheck('false', new Carbon('2022-10-01'), new Carbon('2022-10-04'), new Carbon('2022-10-04'), 2, $menus, $holiday, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);
        $this->assertNotSame($menu2->id, $result[0]['id']);
        $this->assertNull($msg);

        // 提供可能メニューなし（定休日）
        $msg = null;
        $result = $this->storeService->menuPreCheck('false', new Carbon('2022-10-01'), new Carbon('2022-10-03'), new Carbon('2022-10-03'), 2, $menus, $holiday, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('月曜日はこのメニューを注文できません。', $msg);
    }

    public function testAllCheckWithTime()
    {
        list($store, $menu) = $this->_createDateForTestAllCheckWithTime();
        $storeId = $store->id;
        $openingHours = OpeningHour::where('store_id', $storeId)->get();
        $menus = Menu::where('store_id', $storeId)->get();

        $this->_createVacancy($storeId, '2999-10-01', '09:00:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '10:00:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '10:00:00', 2, 2, 0);
        $this->_createVacancy($storeId, '2999-10-01', '10:30:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '11:00:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '11:30:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '12:00:00', 5, 1, 1);
        $this->_createVacancy($storeId, '2999-10-01', '12:30:00', 5, 1, 1);
        $this->_createVacancy($storeId, '2999-10-01', '13:00:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '13:30:00', 5, 1, 0);
        $this->_createVacancy($storeId, '2999-10-01', '14:00:00', 0, 1, 0);

        $stocks = Vacancy::where('store_id', $storeId)->get();
        $holiday = null;
        $now = Carbon::now();
        $dt = new Carbon('2999-10-01 10:00:00');
        $extClosingTime = Carbon::now();
        $endAt = new Carbon();
        $vTime = new Carbon();
        $salesLunchEndTime = new Carbon('2999-10-01 13:00:00');
        $salesDinnerEndTime = new Carbon('2999-10-01 21:00:00');

        // 在庫あり（販売中。10時+1時間（提供時間）の10-11時の間で販売停止フラグOFF＆在庫数１以上のレコードのみ）
        $msg = null;
        $stock = $stocks->where('time', '10:00:00')->first();
        $result = $this->storeService->allCheckWithTime('false', '2999-10-01', '10:00:00', 1, null, $store, $openingHours, null, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);

        // 在庫あり（販売停止。11時+1時間（提供時間）の11-12時の間で販売停止フラグOFFのレコードあり）
        $msg = null;
        $stock = $stocks->where('time', '11:00:00')->first();
        $result = $this->storeService->allCheckWithTime('false', '2999-10-01', '11:00:00', 1, null, $store, $openingHours, null, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);

        // 在庫なし（対象となる13時+1時間（提供時間）の13-14時の間で在庫数0のレコードあり）
        $msg = null;
        $stock = $stocks->where('time', '13:00:00')->first();
        $result = $this->storeService->allCheckWithTime('false', '2999-10-01', '13:00:00', 1, null, $store, $openingHours, null, $menus, $stocks, $stock, $holiday, $now, $dt, $extClosingTime, $endAt, $vTime, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
    }

    public function testCheckTodayOrFuture()
    {
        $today = Carbon::now();

        // 指定日が本日より未来=true
        $vDate = $today->copy()->addDay();
        $msg = null;
        $this->assertTrue($this->storeService->checkTodayOrFuture($today, $vDate, $msg));
        $this->assertNull($msg);

        // 指定日と本日が同じ=true
        $vDate = $today->copy();
        $msg = null;
        $this->assertTrue($this->storeService->checkTodayOrFuture($today, $vDate, $msg));
        $this->assertNull($msg);

        // 指定日が本日より過去
        $today = Carbon::now();
        $vDate = $today->copy()->subDay();
        $msg = null;
        $this->assertFalse($this->storeService->checkTodayOrFuture($today, $vDate, $msg));
        $this->assertSame('過ぎた日付を来店日にすることはできません。', $msg);
    }

    public function testCheckFromWeek()
    {
        $holiday = new Holiday();
        $holiday->date = '2999-10-01';

        // 提供可能
        $weeks = '01000000';                // 火曜だけ提供可能（祝日不可）
        $now = new Carbon('2999-10-01');    // 火曜日
        $msg = null;
        $this->assertTrue($this->storeService->checkFromWeek(null, $weeks, $now, 2, $msg));
        $this->assertNull($msg);

        // 提供不可能
        $weeks = '01000000';                // 火曜だけ提供可能（祝日不可）
        $now = new Carbon('2999-10-02');    // 水曜日
        $msg = null;
        $this->assertFalse($this->storeService->checkFromWeek(null, $weeks, $now, 2, $msg));
        $this->assertSame('水曜日はこのメニューを注文できません。', $msg);

        // 提供不可能(祝日)
        $weeks = '01000000';                // 火曜だけ提供可能（祝日不可）
        $now = new Carbon('2999-10-01');    // 火曜日
        $msg = null;
        $this->assertFalse($this->storeService->checkFromWeek($holiday, $weeks, $now, 2, $msg));
        $this->assertSame('2999-10-01はこのメニューを注文できません。', $msg);

        // 提供可能(祝日)
        $weeks = '01000001';                // 火曜＆祝日だけ提供可能
        $now = new Carbon('2999-10-01');    // 火曜日
        $msg = null;
        $this->assertTrue($this->storeService->checkFromWeek($holiday, $weeks, $now, 2, $msg));
        $this->assertNull($msg);
    }

    public function testCheckOpeningHours()
    {
        $store = $this->_createDateForTestCheckOpeningHours();
        $storeId = $store->id;
        $openingHours = OpeningHour::where('store_id', $storeId)->get();    // 火曜日のみ営業（祝日は休み）

        $holiday = new Holiday();
        $holiday->date = '2999-10-01';

        // 営業日(営業時間内）=true
        $now = new Carbon('2999-10-01 10:00:00');    // 火曜日
        $msg = null;
        $this->assertTrue($this->storeService->checkOpeningHours(null, $openingHours, $now, $msg));
        $this->assertNull($msg);

        // 営業日(営業時間外）=false
        $now = new Carbon('2999-10-01 23:00:00');    // 火曜日
        $msg = null;
        $this->assertFalse($this->storeService->checkOpeningHours(null, $openingHours, $now, $msg));
        $this->assertSame('営業時間外のため注文できません。', $msg);

        // 営業日（祝日）=false
        $now = new Carbon('2999-10-01 10:00:00');    // 火曜日
        $msg = null;
        $this->assertFalse($this->storeService->checkOpeningHours($holiday, $openingHours, $now, $msg));
        $this->assertSame('営業時間外のため注文できません。', $msg);

        // 営業日（祝日)=true
        {
            OpeningHour::where('store_id', $storeId)
                ->update(['week' => '01000001']);       // 祝日も営業日とする

            $openingHours = OpeningHour::where('store_id', $storeId)->get();    // 火曜日のみ営業（祝日は休み）
            $now = new Carbon('2999-10-01 10:00:00');    // 火曜日
            $msg = null;
            $this->assertTrue($this->storeService->checkOpeningHours($holiday, $openingHours, $now, $msg));
            $this->assertNull($msg);
        }

        // 定休日=false
        $now = new Carbon('2999-10-02 10:00:00');    // 水曜日
        $msg = null;
        $this->assertFalse($this->storeService->checkOpeningHours(null, $openingHours, $now, $msg));
        $this->assertSame('営業時間外のため注文できません。', $msg);
    }

    public function testCheckSalesTime()
    {
        // memo::外部API連携の処理は、連携先でのテストデータが作れないため、テスト対象外としておく
        // memo::checkSalesTime関数内で使用されていない引数はnullにしておく。

        list($store, $menu) = $this->_createDateForTestCheckSalesTime();
        $storeId = $store->id;
        $openingHours = OpeningHour::where('store_id', $storeId)->get();        // 火曜日のみ営業（祝日は休み）
        $menus = Menu::where('store_id', $storeId)->get();

        $holiday = null;
        $now = Carbon::now();
        $extClosingTime = Carbon::now();
        $endAt = new Carbon();
        $salesLunchEndTime = new Carbon('2999-10-02 13:00:00');
        $salesDinnerEndTime = new Carbon('2999-10-02 21:00:00');
        $extClosingTime = null;                                     // 外部サービス連携部分はテストできないため、nullを渡しておく

        // 提供可能メニューなし（営業時間外）
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 23:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertNull($msg);

        // 提供可能メニューなし（定休日）
        $visitPeople = 2;
        $dt = new Carbon('2999-10-02 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertNull($msg);

        // 提供可能メニューなし（提供人数範囲外（下限））
        $visitPeople = 1;
        $dt = new Carbon('2999-10-01 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertNull($msg);

        // 提供可能メニューなし（提供人数範囲外（上限））
        $visitPeople = 11;
        $dt = new Carbon('2999-10-01 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertNull($msg);

        // 提供可能メニューなし（提供時間とラストオーダーの間）
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 21:45:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('プラン提供時間外です。', $msg);

        // 提供可能メニューあり(ランチ・ディナー提供あり/予約時間帯：ランチ)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);

        // 提供可能メニューあり(ランチ・ディナー提供あり/予約時間帯：ディナー)
        $dt = new Carbon('2999-10-01 18:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);

        // 提供可能メニューあり(ランチ・ディナー提供あり/予約時間帯：ランチでもディナーでも無い)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 15:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('プラン提供時間外です。', $msg);

        // メニュー提供時間変更（ランチ提供のみ）
        Menu::find($menu->id)
            ->update([
                'sales_lunch_start_time' => '10:00:00',
                'sales_lunch_end_time' => '14:00:00',
                'sales_dinner_start_time' => null,
                'sales_dinner_end_time' => null
            ]);
        $menus = Menu::where('store_id', $storeId)->get();

        // 提供可能メニューあり(ランチ提供あり/予約時間帯：ランチ)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);

        // 提供可能メニューなし(ランチ提供あり/予約時間帯：ランチ以外)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 09:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('ランチ提供時間外です。', $msg);

        // メニュー提供時間変更（ディナー提供のみ）
        Menu::find($menu->id)
            ->update([
                'sales_lunch_start_time' => null,
                'sales_lunch_end_time' => null,
                'sales_dinner_start_time' => '17:00:00',
                'sales_dinner_end_time' => '23:00:00'
            ]);
        $menus = Menu::where('store_id', $storeId)->get();

        // 提供可能メニューあり(ディナー提供あり/予約時間帯：ディナー)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 18:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(1, $result);
        $this->assertSame($menu->id, $result[0]['id']);

        // 提供可能メニューなし(ディナー提供あり/予約時間帯：ディナー以外)
        $visitPeople = 2;
        $dt = new Carbon('2999-10-01 10:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('ディナー提供時間外です。', $msg);

        // 営業曜日とメニュー提供曜日を全日に変更
        OpeningHour::where('store_id', $storeId)
            ->update(['week' => '11111111']);
        $openingHours = OpeningHour::where('store_id', $storeId)->get();
        Menu::find($menu->id)
            ->update(['provided_day_of_week' => '11111111']);
        $menus = Menu::where('store_id', $storeId)->get();

        // 提供可能メニューなし(予約が最低注文時間を超えた)
        $visitPeople = 2;
        $dt = new Carbon();
        $dt->addDay();
        $dt->hour = 18;     // 予約日は明日の１８時
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('予約が間に合いません。', $msg);

        // 最低注文時間をクリア
        Menu::find($menu->id)
            ->update(['lower_orders_time' => null]);
        $menus = Menu::where('store_id', $storeId)->get();

        // 提供可能メニューなし(予約が過去日)
        $visitPeople = 2;
        $dt = new Carbon('2022-10-01 18:00:00');
        $msg = null;
        $result = $this->storeService->checkSalesTime('false', null, null, $visitPeople, $store, $openingHours, null, null, $menus, $holiday, $now, $dt, $extClosingTime, $endAt, $salesLunchEndTime, $salesDinnerEndTime, $msg);
        $this->assertCount(0, $result);
        $this->assertSame('過去の時間は指定できません。', $msg);
    }

    public function testGetClosingTime()
    {
        // stock情報なし
        $this->assertNull($this->storeService->getClosingTime(null));

        // stock情報あり
        $param = \Mockery::mock('testApistock');
        $param->stocks = [
            [
                'headcount' => 1,
                'stock' => [
                    ['reservation_time' => '10:00:00', 'sets' => 0, 'duration' => 90],
                    ['reservation_time' => '10:30:00', 'sets' => 0, 'duration' => 90],
                    ['reservation_time' => '11:00:00', 'sets' => 0, 'duration' => 90],
                ],
            ],
            [
                'headcount' => 2,
                'stock' => [
                    ['reservation_time' => '10:00:00', 'sets' => 0, 'duration' => 90],
                    ['reservation_time' => '10:30:00', 'sets' => 0, 'duration' => 90],
                    ['reservation_time' => '11:00:00', 'sets' => 0, 'duration' => 90],
                    ['reservation_time' => '11:30:00', 'sets' => 0, 'duration' => 90],
                ],
            ],
        ];
        $result = $this->storeService->getClosingTime($param);
        $this->assertSame('11:30:00', $result);
    }

    public function testGetStoreBuffet()
    {
        list($store, $bigArea, $middelArea, $storeImage, $storeImage2, $menu, $menu2, $menuImage, $menuPrice, $genre) = $this->_createDateForTestGetStoreBuffet();
        $storeId = $store->id;

        // 存在しない店舗
        $this->assertNull($this->storeService->getStoreBuffet(0, $genre->id));

        // 未公開店舗
        $this->assertNull($this->storeService->getStoreBuffet($storeId, $genre->id));

        // テスト店舗を公開に変更
        Store::find($store->id)->update(['published' => 1]);

        // 公開店舗(メニュー提供はランチ＆ディナー共に設定あり、祝日提供可)
        $result = $this->storeService->getStoreBuffet($storeId, $genre->id);
        $this->assertArrayHasKey('store', $result);
        $this->assertArrayHasKey('menus', $result);
        $this->assertArrayHasKey('recommend', $result);
        // 店舗情報が取得できる
        $this->assertArrayHasKey('id', $result['store']);
        $this->assertArrayHasKey('name', $result['store']);
        $this->assertArrayHasKey('images', $result['store']);
        $this->assertSame($storeId, $result['store']['id']);
        $this->assertSame('テスト店舗', $result['store']['name']);
        // 店舗画像が取得できる
        $this->assertCount(1, $result['store']['images']);
        $this->assertTrue(in_array($storeImage->id, array_column($result['store']['images'], 'id')));   // $storeImageは返ってくる
        $this->assertFalse(in_array($storeImage2->id, array_column($result['store']['images'], 'id'))); // $storeImage2は返ってこない
        $this->assertArrayHasKey('id', $result['store']['images'][0]);
        $this->assertArrayHasKey('imageCd', $result['store']['images'][0]);
        $this->assertArrayHasKey('imageUrl', $result['store']['images'][0]);
        $this->assertSame($storeImage->id, $result['store']['images'][0]['id']);
        $this->assertSame('RESTAURANT_LOGO', $result['store']['images'][0]['imageCd']);
        $this->assertSame('https://test.jp/test11.jpg', $result['store']['images'][0]['imageUrl']);
        // 店舗エリアが取得できる
        $this->assertArrayHasKey('area', $result['store']);
        $this->assertArrayHasKey('bigAreaId', $result['store']['area']);
        $this->assertArrayHasKey('bigAreaName', $result['store']['area']);
        $this->assertArrayHasKey('bigAreaAreaCd', $result['store']['area']);
        $this->assertArrayHasKey('middleAreaId', $result['store']['area']);
        $this->assertArrayHasKey('middleAreaName', $result['store']['area']);
        $this->assertArrayHasKey('middleAreaAreaCd', $result['store']['area']);
        $this->assertSame($bigArea->id, $result['store']['area']['bigAreaId']);
        $this->assertSame('テストエリア', $result['store']['area']['bigAreaName']);
        $this->assertSame('testarea', $result['store']['area']['bigAreaAreaCd']);
        $this->assertSame($middelArea->id, $result['store']['area']['middleAreaId']);
        $this->assertSame('テストエリア1', $result['store']['area']['middleAreaName']);
        $this->assertSame('testarea1', $result['store']['area']['middleAreaAreaCd']);
        // 指定ジャンルに紐づくメニューだけ取得できる
        $this->assertCount(1, $result['menus']);
        $this->assertTrue(in_array($menu->id, array_column($result['menus'], 'id')));   // $menuは返ってくる
        $this->assertFalse(in_array($menu2->id, array_column($result['menus'], 'id'))); // $menu2は返ってこない
        $this->assertArrayHasKey('id', $result['menus'][0]);
        $this->assertArrayHasKey('appCd', $result['menus'][0]);
        $this->assertArrayHasKey('name', $result['menus'][0]);
        $this->assertArrayHasKey('description', $result['menus'][0]);
        $this->assertArrayHasKey('plan', $result['menus'][0]);
        $this->assertArrayHasKey('salesLunchStartTime', $result['menus'][0]);
        $this->assertArrayHasKey('salesDinnerStartTime', $result['menus'][0]);
        $this->assertArrayHasKey('providedDayOfWeek', $result['menus'][0]);
        $this->assertSame($menu->id, $result['menus'][0]['id']);
        $this->assertSame('RS', $result['menus'][0]['appCd']);
        $this->assertSame('テストメニュー1', $result['menus'][0]['name']);
        $this->assertSame('テスト説明', $result['menus'][0]['description']);
        $this->assertSame('テストプラン', $result['menus'][0]['plan']);
        $this->assertSame('10:00:00', $result['menus'][0]['salesLunchStartTime']);
        $this->assertSame('18:00:00', $result['menus'][0]['salesDinnerStartTime']);
        $this->assertSame('11111111', $result['menus'][0]['providedDayOfWeek']);
        // 上記メニューの画像情報が取得できる
        $this->assertArrayHasKey('image', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['image']);
        $this->assertArrayHasKey('id', $result['menus'][0]['image']);
        $this->assertArrayHasKey('imageCd', $result['menus'][0]['image']);
        $this->assertArrayHasKey('imageUrl', $result['menus'][0]['image']);
        $this->assertSame($menuImage->id, $result['menus'][0]['image']['id']);
        $this->assertSame('MENU_MAIN', $result['menus'][0]['image']['imageCd']);
        $this->assertSame('https://test.jp/test2.jpg', $result['menus'][0]['image']['imageUrl']);
        // 上記メニューの価格情報が取得できる
        $this->assertArrayHasKey('price', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['price']);
        $this->assertArrayHasKey('id', $result['menus'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['menus'][0]['price']);
        $this->assertArrayHasKey('price', $result['menus'][0]['price']);
        $this->assertArrayHasKey('startDate', $result['menus'][0]['price']);
        $this->assertArrayHasKey('endDate', $result['menus'][0]['price']);
        $this->assertSame($menuPrice->id, $result['menus'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['menus'][0]['price']['priceCd']);
        $this->assertSame('1000', $result['menus'][0]['price']['price']);
        $this->assertSame('2022-10-01', $result['menus'][0]['price']['startDate']);
        $this->assertSame('2999-12-31', $result['menus'][0]['price']['endDate']);
        // 上記メニューの最短利情報が取得できる
        $this->assertArrayHasKey('shortestAvailableDate', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('date', $result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('time', $result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('headcount', $result['menus'][0]['shortestAvailableDate']);
        $this->assertSame('2999-10-01', $result['menus'][0]['shortestAvailableDate']['date']);
        $this->assertSame('10:00:00', $result['menus'][0]['shortestAvailableDate']['time']);
        $this->assertSame(1, $result['menus'][0]['shortestAvailableDate']['headcount']);
        // 指定店舗のおすすめ店舗情報は取得できない（プログラム上、未設定のため）
        $this->assertCount(0, $result['recommend']);

        // メニュー提供情報変更
        Menu::find($menu->id)
            ->update([
                'sales_lunch_start_time' => '12:00:00',
                'sales_lunch_end_time' => '15:00:00',
                'sales_dinner_start_time' => null,
                'sales_dinner_end_time' => null,
                'provided_day_of_week' => '11111110',  // 祝日のみ休業
            ]);
        // 祝日情報追加
        $holiday = new Holiday();
        $holiday->date = '2999-10-01';
        $holiday->save();

        // 公開店舗(メニュー提供はランチ＆ディナー共に設定あり、祝日提供可)
        $result = $this->storeService->getStoreBuffet($storeId, $genre->id);
        $this->assertArrayHasKey('store', $result);
        $this->assertArrayHasKey('menus', $result);
        $this->assertArrayHasKey('recommend', $result);
        // メニューの最短利情報が取得できる
        // 2999-10−０１が祝日になるため、2999-10-02分が取得できる
        // ランチ営業時間が13：00からになるため、13：00分が取得できる
        $this->assertArrayHasKey('shortestAvailableDate', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('date', $result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('time', $result['menus'][0]['shortestAvailableDate']);
        $this->assertArrayHasKey('headcount', $result['menus'][0]['shortestAvailableDate']);
        $this->assertSame('2999-10-02', $result['menus'][0]['shortestAvailableDate']['date']);
        $this->assertSame('13:00:00', $result['menus'][0]['shortestAvailableDate']['time']);
        $this->assertSame(1, $result['menus'][0]['shortestAvailableDate']['headcount']);

        // 本日以降有効なメニューの料金情報をなくし、最短利用情報が取得できないことを確認する
        Price::where('menu_id', $menu->id)->update(['end_date' => '2022-12-31']);
        $result = $this->storeService->getStoreBuffet($storeId, $genre->id);
        $this->assertArrayHasKey('store', $result);
        $this->assertArrayHasKey('menus', $result);
        $this->assertArrayHasKey('recommend', $result);
        $this->assertArrayHasKey('shortestAvailableDate', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['shortestAvailableDate']);
        $this->assertCount(0, $result['menus'][0]['shortestAvailableDate']);
    }

    private function _createStoreForTestGet()
    {
        $station = new Station();
        $station->name = 'テスト駅';
        $station->station_cd = 'teststation';
        $station->latitude = '100';
        $station->longitude = '200';
        $station->save();

        $store = new Store();
        $store->name = 'テスト店舗';
        $store->code = 'teststore';
        $store->alias_name = 'testaliasname';
        $store->postal_code = '1234567';
        $store->address_1 = 'テスト住所1';
        $store->address_2 = 'テスト住所2';
        $store->address_3 = 'テスト住所3';
        $store->tel = '0311112222';
        $store->tel_order = '0311113333';
        $store->latitude = '300';
        $store->longitude = '400';
        $store->email_1 = 'gourmet-test1@adventure-inc.co.jp';
        $store->daytime_budget_lower_limit = 1000;
        $store->daytime_budget_limit = 1500;
        $store->access = '駅から徒歩5分です。';
        $store->account = 'アカウント';
        $store->remarks = '備考です。';
        $store->description = '店舗情報説明です。';
        $store->fax = '0311114444';
        $store->use_fax = 1;
        $store->regular_holiday = '10111110';
        $store->night_budget_lower_limit = 2000;
        $store->night_budget_limit = 3000;
        $store->can_card = 1;
        $store->card_types = 'MASTER,OTHER';
        $store->can_digital_money = 1;
        $store->digital_money_types = 'NANACO,WAON,EDY,PAYPAY,MERPAY';
        $store->has_private_room = 1;
        $store->private_room_types = '10-20_PEOPLE,20-30_PEOPLE,30_OVER_PEOPLE';
        $store->has_parking = 1;
        $store->has_coin_parking = 1;
        $store->number_of_seats = 100;
        $store->can_charter = 1;
        $store->charter_types = '20-50_PEOPLE_C';
        $store->smoking = 1;
        $store->smoking_types = 'SEPARATE';
        $store->station_id = $station->id;
        $store->save();
        $this->store = $store;

        $genreLevel3 = new Genre();
        $genreLevel3->name = 'test3';
        $genreLevel3->genre_cd = 'test3';
        $genreLevel3->app_cd = 'TORS';
        $genreLevel3->level = 3;
        $genreLevel3->published = 1;
        $genreLevel3->path = '/b-cooking/test2';
        $genreLevel3->save();

        $genreLevel4 = new Genre();
        $genreLevel4->name = 'test4';
        $genreLevel4->genre_cd = 'test4';
        $genreLevel4->app_cd = 'TORS';
        $genreLevel4->level = 4;
        $genreLevel4->published = 1;
        $genreLevel4->path = '/b-cooking/test2/test3';
        $genreLevel4->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $store->id;
        $genreGroup->genre_id = $genreLevel4->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $image = new Image();
        $image->store_id = $store->id;
        $image->image_cd = 'RESTAURANT_LOGO';
        $image->url = 'https://test.jp/test.jpg';
        $image->weight = 1;
        $image->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '10111110';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->start_at = '07:00:00';
        $openingHour->end_at = '21:00:00';
        $openingHour->last_order_time = '20:30:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'TO';
        $menu->name = 'テストメニュー名';
        $menu->save();

        $review = new Review();
        $review->store_id = $store->id;
        $review->menu_id = $menu->id;
        $review->user_id = 1;
        $review->user_name = 'グルメ太郎';
        $review->body = 'テストクチコミ';
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->published = 1;
        $review->save();

        return [$store, $genreLevel3, $image, $openingHour, $station];
    }

    private function _createDataForTestGetStoreTakeoutMenu()
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'TO';
        $menu->name = 'テストメニュー名';
        $menu->description = 'テストメニュー説明';
        $menu->published = 1;
        $menu->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = 1500;
        $menuPrice->start_date = '2022-10-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        $image = new Image();
        $image->menu_id = $menu->id;
        $image->image_cd = 'MENU_MAIN';
        $image->url = 'https://test.jp/test.jpg';
        $image->weight = 1;
        $image->save();

        $stock = new Stock();
        $stock->menu_id = $menu->id;
        $stock->stock_number = 5;
        $stock->date = '2022-10-01';
        $stock->save();

        $stock = new Stock();
        $stock->menu_id = $menu->id;
        $stock->stock_number = 3;
        $stock->date = '2022-10-02';
        $stock->save();

        $genreLevel4 = new Genre();
        $genreLevel4->name = 'test4';
        $genreLevel4->genre_cd = 'test4';
        $genreLevel4->app_cd = 'TORS';
        $genreLevel4->level = 4;
        $genreLevel4->published = 1;
        $genreLevel4->path = '/b-cooking/test2/test3';
        $genreLevel4->save();

        $genreGroup = new GenreGroup();
        $genreGroup->menu_id = $menu->id;
        $genreGroup->genre_id = $genreLevel4->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        return [$store, $menu, $menuPrice, $image, $genreLevel4];
    }

    private function _createStoreDataForTestGetStoreRestaurantMenu($storeId)
    {
        $openingHour = new OpeningHour();
        $openingHour->store_id = $storeId;
        $openingHour->week = '10011111';            // 火水提供不可
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->start_at = '10:00:00';
        $openingHour->end_at = '21:00:00';
        $openingHour->last_order_time = '20:30:00';
        $openingHour->save();
    }

    private function _createMenuDataForTestGetStoreRestaurantMenu($menuId)
    {
        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = 1500;
        $menuPrice->start_date = '2022-10-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menuId;
        $menuPrice->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menuId;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test.jpg';
        $menuImage->weight = 1;
        $menuImage->save();

        return [$menuPrice, $menuImage];
    }

    private function _createDataFotTestGetStoreRestaurantMenu($published = 0, $api = false)
    {
        $store = new Store();
        $store->published = $published;
        $store->regular_holiday = '11011111';       // 火曜日定休日
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'RS';
        $menu->name = 'テストメニュー名';
        $menu->description = 'テストメニュー説明';
        $menu->provided_time = '90';
        $menu->number_of_course = 5;
        $menu->free_drinks = 1;
        $menu->provided_day_of_week = '10001111';   // 火水木提供不可
        $menu->published = $published;
        $menu->save();

        $menu2 = new Menu();
        $menu2->store_id = $store->id;
        $menu2->app_cd = 'RS';
        $menu2->published = $published;
        $menu2->save();

        if ($api) {
            $externalApi = new ExternalApi();
            $externalApi->store_id = $store->id;
            $externalApi->save();
        }

        return [$store, $menu, $menu2];
    }

    private function _createDataForTestGetStoreReview()
    {
        $store = new Store();
        $store->save();

        $reviewImage = new Image();
        $reviewImage->image_cd = 'STORE_USER_POST';
        $reviewImage->url = 'https://test.jp/test2.jpg';
        $reviewImage->save();

        $review = new Review();
        $review->store_id = $store->id;
        $review->user_id = 1;
        $review->user_name = 'グルメ太郎';
        $review->body = 'テストクチコミ';
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->image_id = $reviewImage->id;
        $review->published = 1;
        $review->save();

        return [$store, $review, $reviewImage];
    }

    private function _createDateForTestGetStoreImage()
    {
        $store = new Store();
        $store->save();

        $storeImage = new Image();
        $storeImage->store_id = $store->id;
        $storeImage->image_cd = 'STORE_INSIDE';
        $storeImage->url = 'https://test.jp/test11.jpg';
        $storeImage->save();

        $storeImage2 = new Image();
        $storeImage2->store_id = $store->id;
        $storeImage2->image_cd = 'STORE_OUTSIDE';
        $storeImage2->url = 'https://test.jp/test12.jpg';
        $storeImage2->save();

        $storeImage3 = new Image();
        $storeImage3->store_id = $store->id;
        $storeImage3->image_cd = 'OTHER';
        $storeImage3->url = 'https://test.jp/test13.jpg';
        $storeImage3->save();
        $storeImage2->save();

        $storeImage4 = new Image();
        $storeImage4->store_id = $store->id;
        $storeImage4->image_cd = 'FOOD_LOGO';
        $storeImage4->url = 'https://test.jp/test14.jpg';
        $storeImage4->save();

        $storeImage5 = new Image();
        $storeImage5->store_id = $store->id;
        $storeImage5->image_cd = 'RESTAURANT_LOGO';
        $storeImage5->url = 'https://test.jp/test15.jpg';
        $storeImage5->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'RS';
        $menu->name = 'テストメニュー';
        $menu->published = 1;
        $menu->save();

        $menuPrice = new Price();
        $menuPrice->menu_id = $menu->id;
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = 1000;
        $menuPrice->start_date = '2022-10-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test2.jpg';
        $menuImage->save();

        $reviewImage = new Image();
        $reviewImage->menu_id = $menu->id;
        $reviewImage->image_cd = 'STORE_USER_POST';
        $reviewImage->url = 'https://test.jp/test3.jpg';
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

        return [$store, $storeImage, $storeImage2, $storeImage3, $storeImage4, $storeImage5, $menu, $menuPrice, $menuImage, $review, $reviewImage];
    }

    private function _createDataForTestGetBreadcrumb()
    {
        $parea = new Area();
        $parea->name = '東京';
        $parea->area_cd = 'testtokyo';
        $parea->level = 1;
        $parea->path = '/';
        $parea->published = 1;
        $parea->save();

        $carea = new Area();
        $carea->name = '恵比寿・代官山・中目黒';
        $carea->area_cd = 'ebisu-daikanyama-nakameguro';
        $carea->level = 2;
        $carea->path = '/testtokyo';
        $carea->published = 1;
        $carea->save();

        $store = new Store();
        $store->name = 'ロバティトス';
        $store->code = 'lobatitos';
        $store->area_id = $carea->id;
        $store->save();
        $this->store = $store;

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();

        $pgenre = new Genre();
        $pgenre->name = 'メキシカン';
        $pgenre->app_cd = 'TORS';
        $pgenre->genre_cd = 'm-mexican';
        $pgenre->level = 2;
        $pgenre->path = '/b-cooking';
        $pgenre->published = 1;
        $pgenre->save();

        $cgenre = new Genre();
        $cgenre->name = 'チミチャンガ';
        $cgenre->app_cd = 'TORS';
        $cgenre->genre_cd = 's-chimichanga';
        $cgenre->level = 3;
        $cgenre->path = '/b-cooking/m-mexican';
        $cgenre->published = 1;
        $cgenre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $cgenre->id;
        $genreGroup->menu_id = $menu->id;
        $genreGroup->store_id = $store->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();
    }

    private function _createDataForTestStoreSearch()
    {
        $area = $this->_createArea('テストエリア', 'test', 2, '/testarea');

        $store = new Store();
        $store->name = 'テストtest店舗';
        $store->code = 'testtest02';
        $store->app_cd = 'TORS';
        $store->published = 1;
        $store->area_id = $area->id;
        $store->latitude = '100';
        $store->longitude = '200';
        $store->daytime_budget_lower_limit = '1000';
        $store->daytime_budget_limit = '2000';
        $store->night_budget_lower_limit = '1500';
        $store->night_budget_limit = '5000';
        $store->regular_holiday = '011111110';      // 月祝休業
        $store->price_level = 2;
        $store->lower_orders_time = 180;
        $store->access = '最寄り駅から徒歩５分の場所です。';
        $store->save();

        // レストランメニュー
        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->name = 'テストレストランメニュー';
        $menu->store_id = $store->id;
        $menu->published = 1;
        $menu->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = '2500';
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        return [$store, $menu, $menuPrice];
    }

    private function _createStoreDateForTestStoreSearch($store)
    {
        $storeImage = new Image();
        $storeImage->store_id = $store->id;
        $storeImage->image_cd = 'RESTAURANT_LOGO';
        $storeImage->url = 'https://test.jp/test11.jpg';
        $storeImage->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '011111110';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        // 料理ジャンル
        $genre = new Genre();
        $genre->genre_cd = 'searchtest';
        $genre->name = 'テスト料理ジャンル';
        $genre->path = '/b-cooking/searchtest';
        $genre->app_cd = 'TORS';
        $genre->level = 3;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->store_id = $store->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // メニュージャンル
        $genre2 = new Genre();
        $genre2->genre_cd = 'searchtestmenu';
        $genre2->name = 'テストメニュージャンル';
        $genre2->path = '/b-cooking/searchtestmenu';
        $genre2->app_cd = 'RS';
        $genre2->save();

        $genreGroup2 = new GenreGroup();
        $genreGroup2->genre_id = $genre2->id;
        $genreGroup2->store_id = $store->id;
        $genreGroup2->is_delegate = 1;
        $genreGroup2->save();

        return [$storeImage, $openingHour, $genre, $genre2];
    }

    private function _createDateForTestGetCancelPolicy()
    {
        $store = new Store();
        $store->save();

        $cancelFee = new CancelFee();
        $cancelFee->store_id = $store->id;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = '2022-09-01';
        $cancelFee->apply_term_to = '2999-09-30';
        $cancelFee->cancel_limit_unit = 'TIME';
        $cancelFee->cancel_limit = '12';
        $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        $cancelFee->cancel_fee = '50';
        $cancelFee->fraction_round = 'ROUND_UP';
        $cancelFee->cancel_fee_max = '1500';
        $cancelFee->cancel_fee_min = null;
        $cancelFee->visit = 'BEFORE';
        $cancelFee->published = 1;
        $cancelFee->save();

        $cancelFee = new CancelFee();
        $cancelFee->store_id = $store->id;
        $cancelFee->app_cd = 'RS';
        $cancelFee->apply_term_from = '2022-09-01';
        $cancelFee->apply_term_to = '2999-09-30';
        $cancelFee->cancel_limit_unit = 'DAY';
        $cancelFee->cancel_limit = '2';
        $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        $cancelFee->cancel_fee = '30';
        $cancelFee->fraction_round = 'ROUND_UP';
        $cancelFee->cancel_fee_max = '1500';
        $cancelFee->cancel_fee_min = null;
        $cancelFee->visit = 'BEFORE';
        $cancelFee->published = 1;
        $cancelFee->save();

        return $store;
    }

    private function _createDataForTestFunctions()
    {
        $store = new Store();
        $store->regular_holiday = '011111110';
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '011111110';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        return $store;
    }

    private function _createDateForTestMenuPreCheck()
    {
        $store = new Store();
        $store->regular_holiday = '011111110';
        $store->save();

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->name = 'テストレストランメニュー1';
        $menu->provided_day_of_week = '01111111';
        $menu->store_id = $store->id;
        $menu->published = 1;
        $menu->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = '2500';
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        $menu2 = new Menu();
        $menu2->app_cd = 'RS';
        $menu2->name = 'テストレストランメニュー1';
        $menu2->provided_day_of_week = '01111111';
        $menu2->store_id = $store->id;
        $menu2->published = 1;
        $menu2->save();

        return [$store, $menu, $menu2];
    }

    private function _createDateForTestAllCheckWithTime()
    {
        $store = new Store();
        $store->regular_holiday = '011111110';
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '011111110';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'RS';
        $menu->name = 'テストレストランメニュー1';
        $menu->provided_day_of_week = '01111111';
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '22:00:00';
        $menu->provided_time = 60;
        $menu->published = 1;
        $menu->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = '2500';
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        return [$store, $menu];
    }

    private function _createDateForTestCheckOpeningHours()
    {
        $store = new Store();
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '01000000';        // 火曜日のみ営業（祝日は休み）
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        return $store;
    }


    private function _createDateForTestCheckSalesTime()
    {
        $store = new Store();
        $store->regular_holiday = '01000000';        // 火曜日のみ営業（祝日は休み）
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '01000000';            // 火曜日のみ営業（祝日は休み）
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'RS';
        $menu->name = 'テストレストランメニュー1';
        $menu->provided_day_of_week = '01111111';
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '22:00:00';
        $menu->available_number_of_lower_limit = 2;
        $menu->available_number_of_upper_limit = 10;
        $menu->provided_time = 60;
        $menu->lower_orders_time = 2880;                // 最低注文時間：二日前
        $menu->published = 1;
        $menu->save();

        return [$store, $menu];
    }

    private function _createDateForTestGetStoreBuffet()
    {
        $bigArea = $this->_createArea('テストエリア', 'testarea', 1, '/');
        $middelArea = $this->_createArea('テストエリア1', 'testarea1', 2, '/testarea');

        $store = new Store();
        $store->area_id = $middelArea->id;
        $store->name = 'テスト店舗';
        $store->published = 0;
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111111';        // 火曜日のみ営業（祝日は休み）
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        $storeImage = new Image();
        $storeImage->store_id = $store->id;
        $storeImage->image_cd = 'RESTAURANT_LOGO';
        $storeImage->url = 'https://test.jp/test11.jpg';
        $storeImage->weight = 1;
        $storeImage->save();

        $storeImage2 = new Image();
        $storeImage2->store_id = $store->id;
        $storeImage2->image_cd = 'STORE_INSIDE';
        $storeImage2->url = 'https://test.jp/test2.jpg';
        $storeImage2->weight = 0;
        $storeImage2->save();

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->name = 'テストメニュー1';
        $menu->description = 'テスト説明';
        $menu->plan = 'テストプラン';
        $menu->sales_lunch_start_time = '10:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '18:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->provided_day_of_week = '11111111';
        $menu->store_id = $store->id;
        $menu->available_number_of_lower_limit = 1;
        $menu->available_number_of_upper_limit = 10;
        $menu->published = 1;
        $menu->buffet_lp_published = 1;
        $menu->save();

        $menu2 = new Menu();
        $menu2->app_cd = 'RS';
        $menu2->name = 'テストメニュー2';
        $menu2->description = 'テスト説明';
        $menu2->plan = 'テストプラン';
        $menu2->sales_lunch_start_time = '10:00:00';
        $menu2->sales_lunch_end_time = '14:00:00';
        $menu2->sales_dinner_start_time = '18:00:00';
        $menu2->sales_dinner_end_time = '21:00:00';
        $menu2->provided_day_of_week = '11111111';
        $menu2->store_id = $store->id;
        $menu2->available_number_of_lower_limit = 1;
        $menu2->available_number_of_upper_limit = 10;
        $menu2->published = 1;
        $menu2->buffet_lp_published = 1;
        $menu2->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test2.jpg';
        $menuImage->save();

        $menuPrice = new Price();
        $menuPrice->menu_id = $menu->id;
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = 1000;
        $menuPrice->start_date = '2022-10-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->save();

        $genre = new Genre();
        $genre->genre_cd = 'searchtest';
        $genre->name = 'テストメニュージャンル';
        $genre->path = '/b-cooking';
        $genre->app_cd = 'TORS';
        $genre->level = 2;
        $genre->save();

        $genre2 = new Genre();
        $genre2->genre_cd = 'searchtest2';
        $genre2->name = 'テストメニュージャンル2';
        $genre2->path = '/b-cooking/searchtest';
        $genre2->app_cd = 'TORS';
        $genre2->level = 3;
        $genre2->save();

        $genreGroup2 = new GenreGroup();
        $genreGroup2->genre_id = $genre2->id;
        $genreGroup2->menu_id = $menu->id;
        $genreGroup2->is_delegate = 1;
        $genreGroup2->save();

        $this->_createVacancy($store->id, '2999-10-01', '10:00:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-01', '10:30:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-01', '11:00:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-01', '11:30:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-01', '12:00:00', 0, 1, 0);   // 在庫0
        $this->_createVacancy($store->id, '2999-10-01', '12:30:00', 0, 1, 0);   // 在庫0
        $this->_createVacancy($store->id, '2999-10-01', '13:00:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-02', '10:00:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-02', '10:30:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-02', '11:00:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-02', '11:30:00', 5, 1, 0);   // 在庫5
        $this->_createVacancy($store->id, '2999-10-02', '12:00:00', 0, 1, 0);   // 在庫0
        $this->_createVacancy($store->id, '2999-10-02', '12:30:00', 0, 1, 0);   // 在庫0
        $this->_createVacancy($store->id, '2999-10-02', '13:00:00', 5, 1, 0);   // 在庫5

        return [$store, $bigArea, $middelArea, $storeImage, $storeImage2, $menu, $menu2, $menuImage, $menuPrice, $genre];
    }

    private function _createArea($name, $cd, $level, $path)
    {
        $area = new Area();
        $area->name = $name;
        $area->area_cd = $cd;
        $area->level = $level;
        $area->path = $path;
        $area->published = 1;
        $area->save();
        return $area;
    }

    private function _createCmTmUser()
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = 'gourmet-test1@adventure-inc.co.jp';
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->gender_id = 1;
        $cmTmUser->save();
        return $cmTmUser;
    }

    private function _createFavorite($userId, $storeId)
    {
        $favorite = new Favorite();
        $favorite->list = json_encode([['id' => $storeId]]);
        $favorite->user_id = $userId;
        $favorite->app_cd = 'RS';
        $favorite->save();
    }

    private function _createVacancy($storeId, $date, $time, $stock, $headcount, $isStopSale)
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
}
