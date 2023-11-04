<?php

namespace Tests\Unit\Models;

use App\Models\Area;
use App\Models\ExternalApi;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\Menu;
use App\Models\SettlementCompany;
use App\Models\Station;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Review;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreTest extends TestCase
{
    private $store;
    private $testAreaId;
    private $testGenreId;
    private $testImageId;
    private $testStaffIdForAnother;
    private $testSettlementCompanyId;
    private $testStoreId;
    private $testStoreId2;
    private $testGenreIdForSearch;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->store = new Store();

        $this->_createStore();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testImage()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('image', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testImages()
    {
        $result = $this->store::find($this->testStoreId)->images('test');
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testImageId, $result[0]['id']);
    }

    public function testReview()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('reviews', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testTakeoutMenus()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('takeoutMenus', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testRestaurantMenus()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('restaurantMenus', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testRecommendMenus()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('recommendMenus', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testOpeningHours()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('openingHours', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testSettlementCompany()
    {
        $testSettlementCompanyId = $this->testSettlementCompanyId;
        $result = $this->store::whereHas('settlementCompany', function ($query) use ($testSettlementCompanyId) {
            $query->where('id', $testSettlementCompanyId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testStations()
    {
        $testStationId = $this->testStationId;
        $result = $this->store::whereHas('stations', function ($query) use ($testStationId) {
            $query->where('id', $testStationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testAreas()
    {
        $testAreaId = $this->testAreaId;
        $result = $this->store::whereHas('areas', function ($query) use ($testAreaId) {
            $query->where('id', $testAreaId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testStaff()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('staff', function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testAnotherStaff()
    {
        $testStaffIdForAnother = $this->testStaffIdForAnother;
        $result = $this->store::whereHas('anotherStaff', function ($query) use ($testStaffIdForAnother) {
            $query->where('id', $testStaffIdForAnother);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testGenres()
    {
        $testGenreId = $this->testGenreId;
        $result = $this->store::whereHas('genres' , function ($query) use ($testGenreId) {
            $query->where('genre_id', $testGenreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testGenreDelegate()
    {
        $testGenreId = $this->testGenreId;
        $result = $this->store::whereHas('genre_delegate' , function ($query) use ($testGenreId) {
            $query->where('genre_id', $testGenreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testExternalApi()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->store::whereHas('external_api' , function ($query) use ($testStoreId) {
            $query->where('store_id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testScopeAdminSearchFilter()
    {
        // ID検索
        $valid = [
            'id' => $this->testStoreId,
        ];
        $result = $this->store::adminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);

        // name検索
        $valid = [
            'name' => 'テストtest店舗',
        ];
        $result = $this->store::adminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);

        // settlement_company_name検索
        $valid = [
            'settlement_company_name' => 'テスト請求会社',
        ];
        $result = $this->store::adminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);

        // code検索
        $valid = [
            'code' => 'testtest01',
        ];
        $result = $this->store::adminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testScopePublished()
    {
        // 該当なし
        $result = $this->store::published()->where('id', $this->testStoreId)->get();
        $this->assertIsObject($result);
        $this->assertSame(0, $result->count());

        // 該当あり
        {
            $store = Store::find($this->testStoreId);
            $store->published = 1;
            $store->save();

            $result = $this->store::published()->where('id', $this->testStoreId)->get();
            $this->assertIsObject($result);
            $this->assertSame(1, $result->count());
            $this->assertSame($this->testStoreId, $result[0]['id']);

            $store = Store::find($this->testStoreId);
            $store->published = 0;
            $store->save();
        }
    }

    public function testSearchByRadius()
    {
        $originLat = '26.216727649223404';
        $originLon = '127.71649009100204';
        $result = $this->store->searchByRadius($originLat, $originLon);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        foreach ($result as $key => $val) {
            $this->assertSame($this->testStoreId, $key);
            break;
        }
    }

    public function testGetRegularHoliday()
    {
        //定休日なし
        {
            $store = Store::find($this->testStoreId);
            $store->regular_holiday = '111111110';
            $store->save();

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(1);
            $this->assertSame(1, $result);

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(8);
            $this->assertSame(0, $result);
        }

        //定休日あり
        {
            $store = Store::find($this->testStoreId);
            $store->regular_holiday = '011111100';      // 火水木金土が営業、月日祝は休業
            $store->save();

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(0); // 指定日：月曜日
            $this->assertSame('0', $result);

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(1); // 指定日：火曜
            $this->assertSame('1', $result);

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(8); // 指定日：祝日
            $this->assertSame(1, $result);

            $result = $this->store::find($this->testStoreId)->getRegularHoliday(9); // 8（祝日）に読み替えされ、1が返却される
            $this->assertSame(1, $result);
        }
    }

    public function testScopeList()
    {
        // ユーザ認証して、該当データが取得できるか
        Auth::attempt([
            'username' => 'test01',
            'password' => 'Test0101',
        ]);
        $result = $this->store::List()->where('id', $this->testStoreId)->get();
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testStoreId, $result[0]['id']);

        // ユーザ認証して、該当データが取得できるか
        Auth::attempt([
            'username' => 'test02',
            'password' => 'Test0202',
        ]);
        $result = $this->store::List()->where('id', $this->testStoreId)->get();
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    public function testMoveImage()
    {
        $store = Store::with('image')->find(829); //開発DBに本当に存在する店舗の画像を移動させる
        $orgCode = $store->code;
        $tmpCode = 'tmp'.$orgCode;
        foreach ($store->image as $getStoreImage) {
            //1つ目だけ移動する
            $oldUrl = $getStoreImage->url;
            $category = '/store';
            $expectationUrl = str_replace($orgCode, $tmpCode, $oldUrl);
            $newUrl = $store->moveImage($oldUrl, $tmpCode, $category);  // 移動する
            $this->assertSame($expectationUrl, $newUrl);              // 予測した値と同じか確認
            $orgUrl = $store->moveImage($newUrl, $orgCode, $category);  // 元に戻す
            $this->assertSame($oldUrl, $orgUrl);                      // 変更前の値と同じか確認
            break;
        }
    }

    public function testGetFavoriteStores()
    {
        $store = Store::find($this->testStoreId);
        $store->published = 1;
        $store->save();

        $searchParams = [
            'appCd' => 'RS',
        ];
        $result = $this->store->getFavoriteStores($searchParams, 'false', [$this->testStoreId]);
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);

        $result = $this->store->getFavoriteStores($searchParams, 'true', [$this->testStoreId]);
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);

        $store = Store::find($this->testStoreId);
        $store->published = 0;
        $store->save();
    }

    public function testStoreSearch()
    {
        // 本テスト用データの追加と更新
        {
            $this->_createStoreSearch();

            $store = Store::find($this->testStoreId);
            $store->published = 1;
            $store->regular_holiday = '011111100';      // 火水木金土が営業、月日祝は休業
            $store->save();
        }

        // 現在地検索
        // 対象：RS:レストラン
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '26.216727649223404',
                'longitude' => '127.71649009100204',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：TO:テイクアウト（該当なし）
        {
            $params = [
                'appCd' => 'TO',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '26.216727649223404',
                'longitude' => '127.71649009100204',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // 駅検索
        // 対象：RS:レストラン（該当なし）
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'STATION',
                'suggestText' => 'テストtestステーション',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // 駅検索
        // 対象：RS:レストラン（該当あり）
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'STATION',
                'suggestText' => 'テストステーション',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
        }

        // エリア検索
        // 対象：RS:レストラン
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'AREA',
                'suggestText' => 'test',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン（該当なし）
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'test',  // 予期せぬ値
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // その他検索
        // 対象：RS:レストラン
        // 昼予算あり
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'AREA',
                'suggestText' => 'test',
                'zone' => '1',
                'lowerPrice' => '1000',
                'upperPrice' => '2000',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン
        // 夜予算あり
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'AREA',
                'suggestText' => 'test',
                'zone' => '0',
                'lowerPrice' => '1000',
                'upperPrice' => '5000',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：TO:テイクアウト
        // メニュージャンル：searchtestmenu
        {
            $params = [
                'appCd' => 'TO',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
                'visitDate' => '2022-10-01',        // 営業日
                'visitTime' => '09:00:00',
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
                'visitDate' => '2022-10-01',        // 営業日＆空席あり
                'visitTime' => '09:00:00',
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
                'visitDate' => '2022-10-02',        // 営業日＆空席なし
                'visitTime' => '09:00:00',
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
                'visitDate' => '2022-10-03',        // 休業日
                'visitTime' => '09:00:00',
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'visitDate' => '2022-10-01',
                'visitTime' => '23:00:00',          // 営業時間外
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(0, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        // メニュージャンル指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'menuGenreCd' => 'searchtestmenu',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        // その他検索
        // 対象：RS:レストラン
        // 来店情報指定
        // 料理ジャンル指定
        {
            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'CURRENT_LOC',
                'latitude' => '35.656181433705434',
                'longitude' => '139.7020649511106',
                'cookingGenreCd' => 'searchtest',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        //API連携店舗テスト
        {
            $externalApi = ExternalApi::where('store_id', $this->testStoreId)->first();
            $externalApi->api_store_id = 12345;     // 無効な値だが今回用に入れておく
            $externalApi->save();

            $params = [
                'appCd' => 'RS',
                'suggestCd' => 'STATION',
                'suggestText' => 'テストステーション',
                'visitDate' => '2022-10-01',        // 営業日＆空席あり
                'visitTime' => '09:00:00',
                'visitPeople' => '2',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);

            $externalApi = ExternalApi::where('store_id', $this->testStoreId)->first();
            $externalApi->api_store_id = null;
            $externalApi->save();
        }

        // その他検索
        // 対象：指定なし
        // 料理ジャンル指定
        {
            $params = [
                'appCd' => '',
                'cookingGenreCd' => 'searchtest',
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId2, $result['storeList'][0]['id']);
        }

        // その他検索
        // こだわり条件
        {
            // 該当あり(1店舗分)
            $params = [
                'appCd' => '',
                'details' => "{$this->testGenreId}",
            ];
            $result = $this->store->storeSearch($params);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
            $this->assertSame(1, $result['count']);
            $this->assertArrayHasKey('pageMax', $result);
            $this->assertSame(1.0, $result['pageMax']);
            $this->assertArrayHasKey('storeList', $result);
            $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);

            // 複数店舗分取得できるか確認するため、テスト店舗にジャンルを追加
            {
                $genreGroup = new GenreGroup();
                $genreGroup->genre_id = $this->testGenreIdForSearch;
                $genreGroup->store_id = $this->testStoreId;
                $genreGroup->is_delegate = 1;
                $genreGroup->save();

                $params = [
                    'appCd' => '',
                    'details' => "{$this->testGenreId},{$this->testGenreIdForSearch}",
                ];
                $result = $this->store->storeSearch($params);
                $this->assertIsArray($result);
                $this->assertArrayHasKey('count', $result);
                $this->assertSame(2, $result['count']);
                $this->assertArrayHasKey('pageMax', $result);
                $this->assertSame(1.0, $result['pageMax']);
                $this->assertArrayHasKey('storeList', $result);
                $this->assertSame($this->testStoreId, $result['storeList'][0]['id']);
                $this->assertSame($this->testStoreId2, $result['storeList'][1]['id']);
            }
        }
    }

    public function testCalcHourToMin()
    {
        // 2時間10分を渡して、130分が返ってくるか
        $result = $this->store->calcHourToMin(2, 10);
        $this->assertSame($result, 130);
    }

    public function testCalcMinToHour()
    {
        // 130分を渡して、2時間10分が返ってくるか
        $result = $this->store->calcMinToHour(130);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame($result[0], 2);
        $this->assertSame($result[1], 10);

        // nullを渡して、nullが入った配列が返ってくるか
        $result = $this->store->calcMinToHour(null);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }

    public function testGetRecommendation()
    {
        // 本テスト用データ更新
        {
            $store = Store::find($this->testStoreId);
            $store->published = 1;
            $store->save();
        }

        $params = [
            'areaCd' => 'test',
            'stationCd' => 'test',
        ];
        $result = $this->store->getRecommendation($params);
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStoreId, $result[0]['id']);
    }

    private function _createStore()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'テスト請求会社';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        $station = new Station();
        $station->station_cd = 'test';
        $station->name = 'テストステーション';
        $station->name_roma = 'teststation';
        $station->latitude = '26.21907697725042';    // 場所は首里城前駅にする（適当
        $station->longitude = '127.72559520823751';
        $station->prefecture_id = 13;               // 都道府県は東京にする（StoreSearch関数の検索範囲は東京固定なので）
        $station->save();
        $this->testStationId = $station->id;

        $area = new Area();
        $area->name = 'テストエリア';
        $area->area_cd = 'test';
        $area->level = 2;
        $area->save();
        $this->testAreaId = $area->id;

        $staff = new Staff();
        $staff->store_id = 0;
        $staff->username = 'test01';
        $staff->password = bcrypt('Test0101');
        $staff->save();
        $this->testStaffIdForAnother = $staff->id;

        $store = new Store();
        $store->name = 'テストtest店舗';
        $store->code = 'testtest01';
        $store->app_cd = 'RS';
        $store->settlement_company_id = $this->testSettlementCompanyId;
        $store->station_id = $this->testStationId;
        $store->area_id = $this->testAreaId;
        $store->staff_id = $this->testStaffIdForAnother;
        $store->published = 0;
        $store->latitude = '26.218213504996722';    // 場所は首里城公園にする（適当
        $store->longitude = '127.7193948880703';
        $store->daytime_budget_lower_limit = '1000';
        $store->daytime_budget_limit = '2000';
        $store->night_budget_lower_limit = '1500';
        $store->night_budget_limit = '5000';
        $store->save();
        $this->testStoreId = $store->id;

        $image = new Image();
        $image->store_id = $this->testStoreId;
        $image->image_cd = 'test';
        $image->save();
        $this->testImageId = $image->id;

        $image = new Image();
        $image->store_id = $this->testStoreId;
        $image->image_cd = 'RESTAURANT_LOGO';
        $image->save();

        $review = new Review();
        $review->store_id = $this->testStoreId;
        $review->save();

        $menu = new Menu();
        $menu->app_cd = 'RS';
        $menu->store_id = $this->testStoreId;
        $menu->published = 1;
        $menu->save();

        $price = new Price();
        $price->price_cd = 'NORMAL';
        $price->price = '1000';
        $price->start_date = '2022-01-01';
        $price->end_date = '2999-12-31';
        $price->menu_id = $menu->id;
        $price->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId;
        $openingHour->week = '111111110';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        $staff = new Staff();
        $staff->store_id = $this->testStoreId;
        $staff->username = 'test02';
        $staff->password = bcrypt('Test0202');
        $staff->staff_authority_id = 3;
        $staff->save();

        $genre = new Genre();
        $genre->save();
        $genre->genre_cd = 'searchtest001';
        $this->testGenreId = $genre->id;

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $this->testGenreId;
        $genreGroup->store_id = $this->testStoreId;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $externalApi = new ExternalApi();
        $externalApi->store_id = $this->testStoreId;
        $externalApi->save();

        $vacancy = new Vacancy();
        $vacancy->date = '2022-10-01';
        $vacancy->time = '09:00:00';
        $vacancy->headcount = '2';
        $vacancy->base_stock = '10';
        $vacancy->stock = '5';
        $vacancy->is_stop_sale = 0;
        $vacancy->store_id = $this->testStoreId;
        $vacancy->save();
    }

    private function _createStoreSearch()
    {
        $store = new Store();
        $store->name = 'テストtest2店舗';
        $store->code = 'testtest02';
        $store->app_cd = 'TORS';
        $store->settlement_company_id = $this->testSettlementCompanyId;
        $store->station_id = 1288;  // 渋谷駅
        $store->area_id = 226;      // 渋谷
        $store->published = 1;
        $store->latitude = '35.656181433705434';    // 場所は渋谷駅周辺（適当
        $store->longitude = '139.7020649511106';
        $store->daytime_budget_lower_limit = '1000';
        $store->daytime_budget_limit = '2000';
        $store->night_budget_lower_limit = '1500';
        $store->night_budget_limit = '5000';
        $store->regular_holiday = '011111110';      // 月祝休業
        $store->save();
        $this->testStoreId2 = $store->id;

        $image = new Image();
        $image->store_id = $this->testStoreId2;
        $image->image_cd = 'test';
        $image->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId2;
        $openingHour->week = '011111110';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '22:00:00';
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->last_order_time = '21:30:00';
        $openingHour->save();

        $externalApi = new ExternalApi();
        $externalApi->store_id = $this->testStoreId2;
        $externalApi->save();

        // 料理ジャンル
        $genre = new Genre();
        $genre->genre_cd = 'searchtest';
        $genre->path = '/b-cooking/searchtest';
        $genre->level = 3;
        $genre->save();
        $this->testGenreIdForSearch = $genre->id;

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->store_id = $this->testStoreId2;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // メニュージャンル
        $genre = new Genre();
        $genre->genre_cd = 'searchtestmenu';
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->store_id = $this->testStoreId2;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // テイクアウトメニュー
        {
            $menu = new Menu();
            $menu->app_cd = 'TO';
            $menu->store_id = $this->testStoreId2;
            $menu->published = 1;
            $menu->save();

            $image = new Image();
            $image->menu_id = $menu->id;
            $image->image_cd = 'FOOD_LOGO';
            $image->save();

            $price = new Price();
            $price->price_cd = 'NORMAL';
            $price->price = '1000';
            $price->start_date = '2022-01-01';
            $price->end_date = '2999-12-31';
            $price->menu_id = $menu->id;
            $price->save();
        }

        // レストランメニュー
        {
            $menu = new Menu();
            $menu->app_cd = 'RS';
            $menu->store_id = $this->testStoreId2;
            $menu->published = 1;
            $menu->save();

            $image = new Image();
            $image->menu_id = $menu->id;
            $image->image_cd = 'FOOD_LOGO';
            $image->save();

            $price = new Price();
            $price->price_cd = 'NORMAL';
            $price->price = '2500';
            $price->start_date = '2022-01-01';
            $price->end_date = '2999-12-31';
            $price->menu_id = $menu->id;
            $price->save();

            $vacancy = new Vacancy();
            $vacancy->date = '2022-10-01';
            $vacancy->time = '09:00:00';
            $vacancy->headcount = '2';
            $vacancy->base_stock = '10';
            $vacancy->stock = '5';
            $vacancy->is_stop_sale = 0;
            $vacancy->store_id = $this->testStoreId2;
            $vacancy->save();
        }
    }
}
