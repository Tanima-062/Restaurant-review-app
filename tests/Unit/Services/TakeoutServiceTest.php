<?php

namespace Tests\Unit\Services;

use App\Models\Area;
use App\Models\CmTmUser;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\Review;
use App\Models\Station;
use App\Models\Stock;
use App\Models\Store;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class TakeoutServiceTest extends TestCase
{
    private $takeoutService;

    public function setUp(): void
    {
        parent::setUp();
        $this->takeoutService = $this->app->make('App\Services\TakeoutService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetRecommendation()
    {
        list($store, $menu, $menuImage, $menuPrice, $genre, $menu2, $menu3) = $this->_createDataForTestGetRecommendation();

        // 指定エリアに紐づくメニューがない
        $result = $this->takeoutService->getRecommendation(['areaCd' => 'testarea']);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);

        // 指定エリアに紐づくメニューがある
        $result = $this->takeoutService->getRecommendation(['areaCd' => 'testarea1']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertCount(1, $result['result']);
        $this->assertTrue(in_array($menu->id, array_column($result['result'], 'id')));   // $menuは返ってくる
        $this->assertFalse(in_array($menu2->id, array_column($result['result'], 'id'))); // $menu2は返ってこない
        $this->assertFalse(in_array($menu3->id, array_column($result['result'], 'id'))); // $menu3は返ってこない
        // メニュー情報が取得できる
        $this->assertArrayHasKey('id', $result['result'][0]);
        $this->assertArrayHasKey('name', $result['result'][0]);
        $this->assertArrayHasKey('description', $result['result'][0]);
        $this->assertSame($menu->id, $result['result'][0]['id']);
        $this->assertSame('テストメニュー1', $result['result'][0]['name']);
        $this->assertSame('テスト説明1', $result['result'][0]['description']);
        // メニューの画像情報が取得できる
        $this->assertArrayHasKey('thumbImage', $result['result'][0]);
        $this->assertArrayHasKey('id', $result['result'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['result'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['result'][0]['thumbImage']);
        $this->assertSame($menuImage->id, $result['result'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['result'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['result'][0]['thumbImage']['imageUrl']);
        // メニューの価格情報が取得できる
        $this->assertArrayHasKey('price', $result['result'][0]);
        $this->assertArrayHasKey('id', $result['result'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['result'][0]['price']);
        $this->assertArrayHasKey('price', $result['result'][0]['price']);
        $this->assertSame($menuPrice->id, $result['result'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['result'][0]['price']['priceCd']);
        $this->assertSame('1000', $result['result'][0]['price']['price']);
        // メニューに紐づく店舗情報が取得できる
        $this->assertArrayHasKey('store', $result['result'][0]);
        $this->assertArrayHasKey('id', $result['result'][0]['store']);
        $this->assertArrayHasKey('name', $result['result'][0]['store']);
        $this->assertArrayHasKey('access', $result['result'][0]['store']);
        $this->assertSame($store->id, $result['result'][0]['store']['id']);
        $this->assertSame('テスト店舗', $result['result'][0]['store']['name']);
        $this->assertSame('駅から徒歩５分です。', $result['result'][0]['store']['access']);
        // メニューに紐づく店舗のジャンル情報が取得できる
        $this->assertArrayHasKey('genres', $result['result'][0]['store']);
        $this->assertCount(1, $result['result'][0]['store']['genres']);
        $this->assertArrayHasKey('id', $result['result'][0]['store']['genres'][0]);
        $this->assertArrayHasKey('name', $result['result'][0]['store']['genres'][0]);
        $this->assertArrayHasKey('genre_cd', $result['result'][0]['store']['genres'][0]);
        $this->assertArrayHasKey('app_cd', $result['result'][0]['store']['genres'][0]);
        $this->assertArrayHasKey('path', $result['result'][0]['store']['genres'][0]);
        $this->assertArrayHasKey('level', $result['result'][0]['store']['genres'][0]);
        $this->assertSame($genre->id, $result['result'][0]['store']['genres'][0]['id']);
        $this->assertSame('テストジャンル', $result['result'][0]['store']['genres'][0]['name']);
        $this->assertSame('searchtest', $result['result'][0]['store']['genres'][0]['genre_cd']);
        $this->assertSame('TORS', $result['result'][0]['store']['genres'][0]['app_cd']);
        $this->assertSame('/b-cooking/searchtest', $result['result'][0]['store']['genres'][0]['path']);
        $this->assertSame(3, $result['result'][0]['store']['genres'][0]['level']);
    }

    public function testSearch()
    {
        list($store, $menu, $menuImage, $menuPrice) = $this->_createDataForTestSearch();

        $result = $this->takeoutService->search([
            'suggestCd' => 'CURRENT_LOC',
            'latitude' => '100.00001',
            'longitude' => '200',
        ]);
        $this->assertIsArray($result);
        // ページャー情報が取得できる
        $this->assertArrayHasKey('sumCount', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pageMax', $result);
        $this->assertSame(1, $result['sumCount']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(1, $result['page']);
        // ジャンル情報g取得できる
        $this->assertArrayHasKey('genres', $result);
        $this->assertCount(0, $result['genres']);       // App\Models\Menuのsearch関数内でgenre取得部分がコメントアウトになっているため、いつでも0件になる
        // 該当メニューが取得できる
        $this->assertArrayHasKey('menus', $result);
        $this->assertCount(1, $result['menus']);
        $this->assertArrayHasKey('id', $result['menus'][0]);
        $this->assertArrayHasKey('name', $result['menus'][0]);
        $this->assertArrayHasKey('description', $result['menus'][0]);
        $this->assertSame($menu->id, $result['menus'][0]['id']);
        $this->assertSame('テストテイクアウトメニュー', $result['menus'][0]['name']);
        $this->assertSame('テスト説明', $result['menus'][0]['description']);
        // メニュー画像情報が取得できる
        $this->assertArrayHasKey('thumbImage', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['thumbImage']);
        $this->assertArrayHasKey('id', $result['menus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['menus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['menus'][0]['thumbImage']);
        $this->assertSame($menuImage->id, $result['menus'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['menus'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['menus'][0]['thumbImage']['imageUrl']);
        // メニュー価格情報が取得できる
        $this->assertArrayHasKey('price', $result['menus'][0]);
        $this->assertIsArray($result['menus'][0]['price']);
        $this->assertArrayHasKey('id', $result['menus'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['menus'][0]['price']);
        $this->assertArrayHasKey('price', $result['menus'][0]['price']);
        $this->assertSame($menuPrice->id, $result['menus'][0]['price']['id']);
        $this->assertSame('NORMAL', $result['menus'][0]['price']['priceCd']);
        $this->assertSame('1000', $result['menus'][0]['price']['price']);
        // メニューに紐づく店舗情報が取得できる
        $this->assertArrayHasKey('store', $result['menus'][0]);
        $this->assertArrayHasKey('id', $result['menus'][0]['store']);
        $this->assertArrayHasKey('name', $result['menus'][0]['store']);
        $this->assertArrayHasKey('latitude', $result['menus'][0]['store']);
        $this->assertArrayHasKey('longitude', $result['menus'][0]['store']);
        $this->assertArrayHasKey('distance', $result['menus'][0]['store']);
        $this->assertSame($store->id, $result['menus'][0]['store']['id']);
        $this->assertSame('テスト店舗', $result['menus'][0]['store']['name']);
        $this->assertSame(100.0, $result['menus'][0]['store']['latitude']);
        $this->assertSame(200.0, $result['menus'][0]['store']['longitude']);
        $this->assertSame(1.1164625780862, $result['menus'][0]['store']['distance']);
    }

    public function testDetailMenu()
    {
        list($store, $station, $genre, $menu, $menuImage, $menuPrice, $menuOption) = $this->_createDataForTestDetailMenu();
        $menuId = $menu->id;

        // 提供外曜日
        $param = ['pickUpDate' => '2999-09-30', 'pickUpTime' => '10:00:00'];
        $msg = null;
        $result = $this->takeoutService->detailMenu($menuId, $param, $msg);
        $this->assertIsArray($result);
        // 結果情報が取得できる
        $this->assertArrayHasKey('status', $result);
        $this->assertIsArray($result['status']);
        $this->assertArrayHasKey('canSale', $result['status']);
        $this->assertArrayHasKey('message', $result['status']);
        $this->assertFalse($result['status']['canSale']);
        $this->assertSame('月曜日はこのメニューを注文できません。', $result['status']['message']);
        // お気に入り情報が取得できる
        $this->assertArrayHasKey('isFavorite', $result);
        $this->assertFalse($result['isFavorite']);
        // レビュー情報が取得できる
        $this->assertArrayHasKey('evaluations', $result);
        $this->assertCount(1, $result['evaluations']);
        $this->assertArrayHasKey('evaluationCd', $result['evaluations'][0]);
        $this->assertArrayHasKey('percentage', $result['evaluations'][0]);
        $this->assertSame('GOOD_DEAL', $result['evaluations'][0]['evaluationCd']);
        $this->assertSame(100.0, $result['evaluations'][0]['percentage']);
        // メニュー情報が取得できる
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('appCd', $result);
        $this->assertArrayHasKey('salesLunchStartTime', $result);
        $this->assertArrayHasKey('salesLunchEndTime', $result);
        $this->assertArrayHasKey('salesDinnerStartTime', $result);
        $this->assertArrayHasKey('salesDinnerEndTime', $result);
        $this->assertArrayHasKey('stockNumber', $result);
        $this->assertSame($menuId, $result['id']);
        $this->assertSame('テストテイクアウトメニュー', $result['name']);
        $this->assertSame('テスト説明', $result['description']);
        $this->assertSame('TO', $result['appCd']);
        $this->assertSame('09:00:00', $result['salesLunchStartTime']);
        $this->assertSame('14:00:00', $result['salesLunchEndTime']);
        $this->assertSame('17:00:00', $result['salesDinnerStartTime']);
        $this->assertSame('22:00:00', $result['salesDinnerEndTime']);
        $this->assertSame(0, $result['stockNumber']);
        // メニューの画像情報が取得できる
        $this->assertArrayHasKey('menuImage', $result);
        $this->assertIsArray($result['menuImage']);
        $this->assertArrayHasKey('id', $result['menuImage']);
        $this->assertArrayHasKey('imageCd', $result['menuImage']);
        $this->assertArrayHasKey('imageUrl', $result['menuImage']);
        $this->assertSame($menuImage->id, $result['menuImage']['id']);
        $this->assertSame('MENU_MAIN', $result['menuImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['menuImage']['imageUrl']);
        // メニューに紐づく店舗情報が取得できる
        $this->assertArrayHasKey('store', $result);
        $this->assertIsArray($result['store']);
        $this->assertArrayHasKey('id', $result['store']);
        $this->assertArrayHasKey('name', $result['store']);
        $this->assertArrayHasKey('latitude', $result['store']);
        $this->assertArrayHasKey('longitude', $result['store']);
        $this->assertArrayHasKey('address', $result['store']);
        $this->assertSame($store->id, $result['store']['id']);
        $this->assertSame('テスト店舗', $result['store']['name']);
        $this->assertSame(100.0, $result['store']['latitude']);
        $this->assertSame(200.0, $result['store']['longitude']);
        $this->assertSame('住所1 住所2 住所3', $result['store']['address']);
        // メニューに紐づく店舗の駅情報が取得できる
        $this->assertArrayHasKey('station', $result['store']);
        $this->assertIsArray($result['store']['station']);
        $this->assertArrayHasKey('id', $result['store']['station']);
        $this->assertArrayHasKey('name', $result['store']['station']);
        $this->assertArrayHasKey('latitude', $result['store']['station']);
        $this->assertArrayHasKey('longitude', $result['store']['station']);
        $this->assertArrayHasKey('distance', $result['store']['station']);
        $this->assertSame($station->id, $result['store']['station']['id']);
        $this->assertSame('テスト駅', $result['store']['station']['name']);
        $this->assertSame(100.0, $result['store']['station']['latitude']);
        $this->assertSame(200.0, $result['store']['station']['longitude']);
        $this->assertSame(0, $result['store']['station']['distance']);
        // メニューに紐づく店舗のジャンル情報が取得できる
        $this->assertArrayHasKey('storeGenres', $result['store']);
        $this->assertCount(1, $result['store']['storeGenres']);
        $this->assertArrayHasKey('id', $result['store']['storeGenres'][0]);
        $this->assertArrayHasKey('name', $result['store']['storeGenres'][0]);
        $this->assertArrayHasKey('genreCd', $result['store']['storeGenres'][0]);
        $this->assertArrayHasKey('appCd', $result['store']['storeGenres'][0]);
        $this->assertArrayHasKey('path', $result['store']['storeGenres'][0]);
        $this->assertSame($genre->id, $result['store']['storeGenres'][0]['id']);
        $this->assertSame('テストジャンル', $result['store']['storeGenres'][0]['name']);
        $this->assertSame('testgenre', $result['store']['storeGenres'][0]['genreCd']);
        $this->assertSame('TORS', $result['store']['storeGenres'][0]['appCd']);
        $this->assertSame('/b-cooking/test', $result['store']['storeGenres'][0]['path']);
        // メニューの価格情報が取得できる
        $this->assertArrayHasKey('menuPrice', $result);
        $this->assertIsArray($result['menuPrice']);
        $this->assertArrayHasKey('id', $result['menuPrice']);
        $this->assertArrayHasKey('priceCd', $result['menuPrice']);
        $this->assertArrayHasKey('price', $result['menuPrice']);
        $this->assertSame($menuPrice->id, $result['menuPrice']['id']);
        $this->assertSame('NORMAL', $result['menuPrice']['priceCd']);
        $this->assertSame('1000', $result['menuPrice']['price']);
        // メニューオプション情報が取得できる
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
        $this->assertSame($menuOption->id, $result['options'][0]['id']);
        $this->assertSame('TOPPING', $result['options'][0]['optionCd']);
        $this->assertSame(1, $result['options'][0]['required']);
        $this->assertSame(1, $result['options'][0]['keywordId']);
        $this->assertSame('項目名', $result['options'][0]['keyword']);
        $this->assertSame(1, $result['options'][0]['contentsId']);
        $this->assertSame('内容', $result['options'][0]['contents']);
        $this->assertSame(500, $result['options'][0]['price']);

        // 在庫なし
        $param = ['pickUpDate' => '2999-10-08', 'pickUpTime' => '10:00:00'];
        $msg = null;
        $result = $this->takeoutService->detailMenu($menuId, $param, $msg);
        $this->assertIsArray($result);
        // 結果情報が取得できる
        $this->assertArrayHasKey('status', $result);
        $this->assertIsArray($result['status']);
        $this->assertArrayHasKey('canSale', $result['status']);
        $this->assertArrayHasKey('message', $result['status']);
        $this->assertFalse($result['status']['canSale']);
        $this->assertSame('在庫がありません。', $result['status']['message']);
        // メニュー情報が取得できる（他の項目チェックは１番目と同じなので割愛）
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('stockNumber', $result);
        $this->assertSame($menuId, $result['id']);
        $this->assertSame(0, $result['stockNumber']);

        // 同時間帯注文組数オーバー
        $param = ['pickUpDate' => '2999-10-01', 'pickUpTime' => '10:00:00'];
        $msg = null;
        $result = $this->takeoutService->detailMenu($menuId, $param, $msg);
        $this->assertIsArray($result);
        // 結果情報が取得できる
        $this->assertArrayHasKey('status', $result);
        $this->assertIsArray($result['status']);
        $this->assertArrayHasKey('canSale', $result['status']);
        $this->assertArrayHasKey('message', $result['status']);
        $this->assertFalse($result['status']['canSale']);
        $this->assertSame('注文が殺到しているため受けられません。', $result['status']['message']);
        // メニュー情報が取得できる（在庫以外項目チェックは１番目と同じなので割愛）
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('stockNumber', $result);
        $this->assertSame($menuId, $result['id']);
        $this->assertSame(2, $result['stockNumber']);   // 在庫（5)-既存予約数（3)

        // メニューの同時時間帯注文数を変更
        Menu::find($menu->id)->update(['number_of_orders_same_time' => 2]);

        // 予約可能
        $param = ['pickUpDate' => '2999-10-01', 'pickUpTime' => '10:00:00'];
        $msg = null;
        $result = $this->takeoutService->detailMenu($menuId, $param, $msg);
        $this->assertIsArray($result);
        // 結果情報が取得できる
        $this->assertArrayHasKey('status', $result);
        $this->assertIsArray($result['status']);
        $this->assertArrayHasKey('canSale', $result['status']);
        $this->assertArrayHasKey('message', $result['status']);
        $this->assertTrue($result['status']['canSale']);
        $this->assertNull($result['status']['message']);
        // メニュー情報が取得できる（在庫以外項目チェックは１番目と同じなので割愛）
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('stockNumber', $result);
        $this->assertSame($menuId, $result['id']);
        $this->assertSame(2, $result['stockNumber']);   // 在庫（5)-既存予約数（3)

        // ログインする
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $login = $this->app->make('App\Modules\UserLogin')->login($request);

        // 予約可能＆ログインユーザーのお気に入り店舗として情報取得できる
        $param = ['pickUpDate' => '2999-10-01', 'pickUpTime' => '10:00:00'];
        $msg = null;
        $result = $this->takeoutService->detailMenu($menuId, $param, $msg);
        $this->assertIsArray($result);
        // 結果情報が取得できる
        $this->assertArrayHasKey('status', $result);
        $this->assertIsArray($result['status']);
        $this->assertArrayHasKey('canSale', $result['status']);
        $this->assertArrayHasKey('message', $result['status']);
        $this->assertTrue($result['status']['canSale']);
        $this->assertNull($result['status']['message']);
        // メニュー情報が取得できる（在庫以外項目チェックは１番目と同じなので割愛）
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('stockNumber', $result);
        $this->assertSame($menuId, $result['id']);
        $this->assertSame(2, $result['stockNumber']);   // 在庫（5)-既存予約数（3)
        // お気に入り情報が取得できる
        $this->assertArrayHasKey('isFavorite', $result);
        $this->assertTrue($result['isFavorite']);
    }

    public function testGetStory()
    {
        list($story, $storyImage) = $this->_createDateForTestGetStory();
        $storyId = $story->id;

        $request = new Request();
        $request->merge([
            'page' => 1,
        ]);
        $result = $this->takeoutService->getStory($request);
        // テスト用に登録したストーリー情報が取得できること
        $this->assertTrue(in_array($storyId, array_column($result, 'id')));
        $target = Arr::first($result, function ($value, $key) use ($storyId) {
            return ($value['id'] == $storyId);
        });
        $this->assertIsArray($target);
        $this->assertSame($storyId, $target['id']);
        $this->assertSame('テストタイトル', $target['title']);
        $this->assertSame('TO', $target['appCd']);
        $this->assertSame('https://test.jp/story/111', $target['guideUrl']);
        $this->assertSame($storyImage->id, $target['image']['id']);
        $this->assertSame('TEST', $target['image']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $target['image']['imageUrl']);
    }

    public function testGetTakeoutGenre()
    {
        list($genre2, $genre3, $genre4) = $this->_createDataForTestGetTakeoutGenre();

        // 定数定義されているgenre_cdを指定＆レベル未指定で、
        // テスト用に登録したジャンル情報が取得できること
        $result = $this->takeoutService->getTakeoutGenre('B-COOKING');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('genres', $result);
        // テスト用に登録したジャンル情報が取得できること
        $genreId = $genre2->id;
        $this->assertTrue(in_array($genreId, array_column($result['genres'], 'id')));
        $target = Arr::first($result['genres'], function ($value, $key) use ($genreId) {
            return ($value['id'] == $genreId);
        });
        $this->assertIsArray($target);
        $this->assertSame($genreId, $target['id']);
        $this->assertSame('テストジャンル', $target['name']);
        $this->assertSame('testgenre', $target['genreCd']);
        $this->assertSame('TORS', $target['appCd']);
        $this->assertSame('/b-cooking', $target['path']);
        $this->assertSame(2, $target['level']);
        // テスト用に登録したジャンル情報が取得できないこと($genre3)
        $genreId = $genre3->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));
        // テスト用に登録したジャンル情報が取得できないこと($genre4)
        $genreId = $genre4->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));

        // 定数定義されているgenre_cdを指定＆レベル2指定で、
        // テスト用に登録したジャンル情報が取得できること
        $result = $this->takeoutService->getTakeoutGenre('B-COOKING', 2);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('genres', $result);
        // テスト用に登録したジャンル情報が取得できること($genre2)
        $genreId = $genre2->id;
        $this->assertTrue(in_array($genreId, array_column($result['genres'], 'id')));
        $target = Arr::first($result['genres'], function ($value, $key) use ($genreId) {
            return ($value['id'] == $genreId);
        });
        $this->assertIsArray($target);
        $this->assertSame($genreId, $target['id']);
        $this->assertSame('テストジャンル', $target['name']);
        $this->assertSame('testgenre', $target['genreCd']);
        $this->assertSame('TORS', $target['appCd']);
        $this->assertSame('/b-cooking', $target['path']);
        $this->assertSame(2, $target['level']);
        // テスト用に登録したジャンル情報が取得できること($genre3)
        $genreId = $genre3->id;
        $this->assertTrue(in_array($genreId, array_column($result['genres'], 'id')));
        $target = Arr::first($result['genres'], function ($value, $key) use ($genreId) {
            return ($value['id'] == $genreId);
        });
        $this->assertIsArray($target);
        $this->assertSame($genreId, $target['id']);
        $this->assertSame('テストジャンル2', $target['name']);
        $this->assertSame('testgenre2', $target['genreCd']);
        $this->assertSame('TORS', $target['appCd']);
        $this->assertSame('/b-cooking/testgenre', $target['path']);
        $this->assertSame(3, $target['level']);
        // テスト用に登録したジャンル情報が取得できないこと($genre4)
        $genreId = $genre4->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));

        // 定数定義されていないgenre_cdを指定＆レベル未指定で、
        // テスト用に登録したジャンル情報が取得できること
        $result = $this->takeoutService->getTakeoutGenre('testgenre');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('genres', $result);
        // テスト用に登録したジャンル情報が取得できないこと($genre2)
        $genreId = $genre2->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));
        // テスト用に登録したジャンル情報が取得できること
        $genreId = $genre3->id;
        $this->assertTrue(in_array($genreId, array_column($result['genres'], 'id')));
        $target = Arr::first($result['genres'], function ($value, $key) use ($genreId) {
            return ($value['id'] == $genreId);
        });
        $this->assertIsArray($target);
        $this->assertSame($genreId, $target['id']);
        $this->assertSame('テストジャンル2', $target['name']);
        $this->assertSame('testgenre2', $target['genreCd']);
        $this->assertSame('TORS', $target['appCd']);
        $this->assertSame('/b-cooking/testgenre', $target['path']);
        $this->assertSame(3, $target['level']);
        // テスト用に登録したジャンル情報が取得できないこと($genre4)
        $genreId = $genre4->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));

        // 定数定義されていないgenre_cdを指定＆レベル2指定で、
        // テスト用に登録したジャンル情報が取得できること
        $result = $this->takeoutService->getTakeoutGenre('testgenre', 2);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('genres', $result);
        // テスト用に登録したジャンル情報が取得できないこと($genre2)
        $genreId = $genre2->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));
        // テスト用に登録したジャンル情報が取得できないこと($genre3)
        $genreId = $genre3->id;
        $this->assertFalse(in_array($genreId, array_column($result['genres'], 'id')));
        // テスト用に登録したジャンル情報が取得できること
        $genreId = $genre4->id;
        $this->assertTrue(in_array($genreId, array_column($result['genres'], 'id')));
        $target = Arr::first($result['genres'], function ($value, $key) use ($genreId) {
            return ($value['id'] == $genreId);
        });
        $this->assertIsArray($target);
        $this->assertSame($genreId, $target['id']);
        $this->assertSame('テストジャンル3', $target['name']);
        $this->assertSame('testgenre3', $target['genreCd']);
        $this->assertSame('TORS', $target['appCd']);
        $this->assertSame('/b-cooking/testgenre/testgenre2', $target['path']);
        $this->assertSame(4, $target['level']);

        // 未登録のgenre_cdを指定
        // 何も取得できないこと
        $result = $this->takeoutService->getTakeoutGenre('test');
        $this->assertCount(0, $result);
    }

    public function testClose()
    {
        $reservation = $this->_createDataForTestClose();
        $reservationId = $reservation->id;
        $this->assertNull($reservation->pick_up_receive_datetime);

        $request = new Request();
        $request->merge([
            'reservationNo' => 'TO' . $reservationId,
            'tel' => '0311112222',
        ]);

        // 未受け取りの場合に、受け取り処理呼び出し
        $this->takeoutService->close($request);
        // 受け取り済に変わっていることを確認
        $checkReservation = Reservation::find($reservationId);
        $this->assertNotNull($checkReservation->pick_up_receive_datetime);

        // 受け取り済の場合、受け取り処理呼び出し
        // 例外エラーが返ってくる
        try {
            $this->takeoutService->close($request);
            $this->assertTrue(false);   // 例外にならずここにきたらエラーとする
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testSearchBox()
    {
        list($bigArea, $middelArea) = $this->_createDateForTestSearchBox();

        $result = $this->takeoutService->searchBox();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('areas', $result);
        // テスト用に登録したエリア（$bigArea)が取得できること
        $areaId = $bigArea->id;
        $this->assertTrue(in_array($areaId, array_column($result['areas'], 'id')));
        $target = Arr::first($result['areas'], function ($value, $key) use ($areaId) {
            return ($value['id'] == $areaId);
        });
        $this->assertIsArray($target);
        $this->assertSame($areaId, $target['id']);
        // テスト用に登録したエリア（$middelArea)が取得できること
        $areaId = $middelArea->id;
        $this->assertTrue(in_array($areaId, array_column($result['areas'], 'id')));
        $target = Arr::first($result['areas'], function ($value, $key) use ($areaId) {
            return ($value['id'] == $areaId);
        });
        $this->assertIsArray($target);
        $this->assertSame($areaId, $target['id']);
    }

    private function _createDataForTestGetRecommendation()
    {
        $bigArea = $this->_createArea('テストエリア', 'testarea', 1, '/');
        $middelArea = $this->_createArea('テストエリア1', 'testarea1', 2, '/testarea');

        $store = new Store();
        $store->area_id = $middelArea->id;
        $store->name = 'テスト店舗';
        $store->access = '駅から徒歩５分です。';
        $store->published = 1;
        $store->save();

        $menu = new Menu();
        $menu->app_cd = 'TO';
        $menu->store_id = $store->id;
        $menu->name = 'テストメニュー1';
        $menu->description = 'テスト説明1';
        $menu->published = 1;
        $menu->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test.jpg';
        $menuImage->save();

        $menuPrice = new Price();
        $menuPrice->menu_id = $menu->id;
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = 1000;
        $menuPrice->start_date = '2022-10-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->save();

        // 料理ジャンル
        $genre = new Genre();
        $genre->genre_cd = 'searchtest';
        $genre->name = 'テストジャンル';
        $genre->path = '/b-cooking/searchtest';
        $genre->app_cd = 'TORS';
        $genre->level = 3;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->store_id = $store->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $store2 = new Store();
        $store2->area_id = $middelArea->id;
        $store2->name = 'テスト店舗2';
        $store2->published = 1;
        $store2->save();

        $menu2 = new Menu();
        $menu2->app_cd = 'TO';
        $menu2->store_id = $store2->id;
        $menu2->name = 'テストメニュー2';
        $menu2->published = 0;
        $menu2->save();

        $store3 = new Store();
        $store3->area_id = $middelArea->id;
        $store3->name = 'テスト店舗3';
        $store3->published = 0;
        $store3->save();

        $menu3 = new Menu();
        $menu3->app_cd = 'TO';
        $menu3->store_id = $store3->id;
        $menu3->name = 'テストメニュー3';
        $menu3->published = 0;
        $menu3->save();

        return [$store, $menu, $menuImage, $menuPrice, $genre, $menu2, $menu3];
    }

    private function _createDataForTestSearch()
    {
        $area = $this->_createArea('テストエリア', 'test', 2, '/testarea');

        $store = new Store();
        $store->name = 'テスト店舗';
        $store->code = 'testtest';
        $store->app_cd = 'TORS';
        $store->published = 1;
        $store->area_id = $area->id;
        $store->latitude = '100';
        $store->longitude = '200';
        $store->regular_holiday = '111111111';
        $store->save();

        // テイクアウトメニュー
        $menu = new Menu();
        $menu->app_cd = 'TO';
        $menu->name = 'テストテイクアウトメニュー';
        $menu->description = 'テスト説明';
        $menu->store_id = $store->id;
        $menu->published = 1;
        $menu->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test.jpg';
        $menuImage->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = '1000';
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        // 在庫
        $now = Carbon::now();
        $this->_createStock($menu->id, $now, 5);

        return [$store, $menu, $menuImage, $menuPrice];
    }

    private function _createDataForTestDetailMenu()
    {
        $station = new Station();
        $station->name = 'テスト駅';
        $station->station_cd = 'teststation';
        $station->latitude = '100';
        $station->longitude = '200';
        $station->save();

        $store = new Store();
        $store->name = 'テスト店舗';
        $store->code = 'testtest';
        $store->app_cd = 'TORS';
        $store->published = 1;
        $store->station_id = $station->id;
        $store->latitude = '100';
        $store->longitude = '200';
        $store->regular_holiday = '111111111';
        $store->address_1 = '住所1';
        $store->address_2 = '住所2';
        $store->address_3 = '住所3';
        $store->save();

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
        $genre->genre_cd = 'testgenre';
        $genre->name = 'テストジャンル';
        $genre->path = '/b-cooking/test';
        $genre->app_cd = 'TORS';
        $genre->level = 3;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->genre_id = $genre->id;
        $genreGroup->store_id = $store->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        // テイクアウトメニュー
        $menu = new Menu();
        $menu->app_cd = 'TO';
        $menu->name = 'テストテイクアウトメニュー';
        $menu->description = 'テスト説明';
        $menu->provided_day_of_week = '01000000';
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '22:00:00';
        $menu->store_id = $store->id;
        $menu->published = 1;
        $menu->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->url = 'https://test.jp/test.jpg';
        $menuImage->save();

        $menuPrice = new Price();
        $menuPrice->price_cd = 'NORMAL';
        $menuPrice->price = '1000';
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2999-12-31';
        $menuPrice->menu_id = $menu->id;
        $menuPrice->save();

        $menuOption = new Option();
        $menuOption->menu_id = $menu->id;
        $menuOption->option_cd = 'TOPPING';
        $menuOption->required = 1;
        $menuOption->keyword_id = 1;
        $menuOption->keyword = '項目名';
        $menuOption->contents_id = 1;
        $menuOption->contents = '内容';
        $menuOption->price = 500;
        $menuOption->save();

        $review = new Review();
        $review->store_id = $store->id;
        $review->menu_id = $menu->id;
        $review->user_id = 1;
        $review->user_name = 'グルメ太郎';
        $review->body = 'テストクチコミ';
        $review->evaluation_cd = 'GOOD_DEAL';
        $review->published = 1;
        $review->save();

        // 在庫
        $this->_createStock($menu->id, '2999-10-01', 5);

        // 予約
        $reservation = new Reservation();
        $reservation->pick_up_datetime = '2999-10-01 10:00:00';
        $reservation->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id = $menu->id;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->count = 3;
        $reservationMenu->save();

        $user = $this->_createCmTmUser();

        $favorite = new Favorite();
        $favorite->list = json_encode([['id' => $menu->id]]);
        $favorite->app_cd = 'TO';
        $favorite->user_id = $user->user_id;
        $favorite->save();

        return [$store, $station, $genre, $menu, $menuImage, $menuPrice, $menuOption, $user];
    }

    private function _createDateForTestGetStory()
    {
        $storyImage = new Image();
        $storyImage->image_cd = 'TEST';
        $storyImage->url = 'https://test.jp/test.jpg';
        $storyImage->save();

        $story = new Story();
        $story->app_cd = 'TO';
        $story->title = 'テストタイトル';
        $story->guide_url = 'https://test.jp/story/111';
        $story->published = 1;
        $story->image_id = $storyImage->id;
        $story->save();

        return [$story, $storyImage];
    }

    private function _createDataForTestGetTakeoutGenre()
    {
        $genre2 = new Genre();
        $genre2->genre_cd = 'testgenre';
        $genre2->name = 'テストジャンル';
        $genre2->path = '/b-cooking';
        $genre2->app_cd = 'TORS';
        $genre2->level = 2;
        $genre2->published = 1;
        $genre2->save();

        $genre3 = new Genre();
        $genre3->genre_cd = 'testgenre2';
        $genre3->name = 'テストジャンル2';
        $genre3->path = '/b-cooking/testgenre';
        $genre3->app_cd = 'TORS';
        $genre3->level = 3;
        $genre3->published = 1;
        $genre3->save();

        $genre4 = new Genre();
        $genre4->genre_cd = 'testgenre3';
        $genre4->name = 'テストジャンル3';
        $genre4->path = '/b-cooking/testgenre/testgenre2';
        $genre4->app_cd = 'TORS';
        $genre4->level = 4;
        $genre4->published = 1;
        $genre4->save();

        return [$genre2, $genre3, $genre4];
    }

    private function _createDataForTestClose()
    {
        $reservation = new Reservation();
        $reservation->tel = '0311112222';
        $reservation->pick_up_receive_datetime = null;
        $reservation->save();

        return $reservation;
    }

    private function _createDateForTestSearchBox()
    {
        $bigArea = $this->_createArea('テストエリア', 'testarea', 1, '/');
        $middelArea = $this->_createArea('テストエリア1', 'testarea1', 2, '/testarea');

        $store = new Store();
        $store->app_cd = 'TO';
        $store->area_id = $middelArea->id;
        $store->name = 'テスト店舗';
        $store->published = 1;
        $store->save();

        return [$bigArea, $middelArea];
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

    private function _createStock($menuId, $date, $stockNumber)
    {
        $stock = new Stock();
        $stock->menu_id = $menuId;
        $stock->date = $date;
        $stock->stock_number = $stockNumber;
        $stock->save();
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
}
