<?php

namespace Tests\Unit\Services;

use App\Models\Favorite;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Price;
use App\Models\Station;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoriteServiceTest extends TestCase
{
    private $favoriteService;

    public function setUp(): void
    {
        parent::setUp();
        $this->favoriteService = $this->app->make('App\Services\FavoriteService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGet()
    {
        // テスト用データの登録
        list($station, $store, $menu, $oldPrice, $price, $image, $openingHour) = $this->_createFavorite('TO');

        // ユーザーIDを指定して、関数呼び出し
        $result = $this->favoriteService->get(1);
        $this->assertArrayHasKey('takeoutMenus', $result);
        $this->assertCount(1, $result['takeoutMenus']);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('description', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('thumbImage', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('price', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('store', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('latitude', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('longitude', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('station', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('latitude', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('longitude', $result['takeoutMenus'][0]['store']['station']);
        $this->assertSame($menu->id, $result['takeoutMenus'][0]['id']);
        $this->assertSame('テストメニュー', $result['takeoutMenus'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['takeoutMenus'][0]['description']);
        $this->assertSame($image->id, $result['takeoutMenus'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['takeoutMenus'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['takeoutMenus'][0]['thumbImage']['imageUrl']);
        $this->assertSame($price->id, $result['takeoutMenus'][0]['price']['id']);
        $this->assertSame('testprice2', $result['takeoutMenus'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['takeoutMenus'][0]['price']['price']);
        $this->assertSame($store->id, $result['takeoutMenus'][0]['store']['id']);
        $this->assertSame('テスト店舗', $result['takeoutMenus'][0]['store']['name']);
        $this->assertSame(300.0, $result['takeoutMenus'][0]['store']['latitude']);
        $this->assertSame(400.0, $result['takeoutMenus'][0]['store']['longitude']);
        $this->assertSame($station->id, $result['takeoutMenus'][0]['store']['station']['id']);
        $this->assertSame('テスト駅', $result['takeoutMenus'][0]['store']['station']['name']);
        $this->assertSame(100.0, $result['takeoutMenus'][0]['store']['station']['latitude']);
        $this->assertSame(200.0, $result['takeoutMenus'][0]['store']['station']['longitude']);

        // メニューIDを指定して、関数呼び出し(上記と同じ結果になること)
        $menuIds = "{$menu->id}";
        $result = $this->favoriteService->get(0, null, null, $menuIds);
        $this->assertArrayHasKey('takeoutMenus', $result);
        $this->assertCount(1, $result['takeoutMenus']);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('description', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('thumbImage', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageCd', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('imageUrl', $result['takeoutMenus'][0]['thumbImage']);
        $this->assertArrayHasKey('price', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('priceCd', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('price', $result['takeoutMenus'][0]['price']);
        $this->assertArrayHasKey('store', $result['takeoutMenus'][0]);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('latitude', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('longitude', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('station', $result['takeoutMenus'][0]['store']);
        $this->assertArrayHasKey('id', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('name', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('latitude', $result['takeoutMenus'][0]['store']['station']);
        $this->assertArrayHasKey('longitude', $result['takeoutMenus'][0]['store']['station']);
        $this->assertSame($menu->id, $result['takeoutMenus'][0]['id']);
        $this->assertSame('テストメニュー', $result['takeoutMenus'][0]['name']);
        $this->assertSame('テストメニュー説明', $result['takeoutMenus'][0]['description']);
        $this->assertSame($image->id, $result['takeoutMenus'][0]['thumbImage']['id']);
        $this->assertSame('MENU_MAIN', $result['takeoutMenus'][0]['thumbImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['takeoutMenus'][0]['thumbImage']['imageUrl']);
        $this->assertSame($price->id, $result['takeoutMenus'][0]['price']['id']);
        $this->assertSame('testprice2', $result['takeoutMenus'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['takeoutMenus'][0]['price']['price']);
        $this->assertSame($store->id, $result['takeoutMenus'][0]['store']['id']);
        $this->assertSame('テスト店舗', $result['takeoutMenus'][0]['store']['name']);
        $this->assertSame(300.0, $result['takeoutMenus'][0]['store']['latitude']);
        $this->assertSame(400.0, $result['takeoutMenus'][0]['store']['longitude']);
        $this->assertSame($station->id, $result['takeoutMenus'][0]['store']['station']['id']);
        $this->assertSame('テスト駅', $result['takeoutMenus'][0]['store']['station']['name']);
        $this->assertSame(100.0, $result['takeoutMenus'][0]['store']['station']['latitude']);
        $this->assertSame(200.0, $result['takeoutMenus'][0]['store']['station']['longitude']);

        // メニューIDと期間を指定して、関数呼び出し(メニューの値段が正しいこと)
        $result = $this->favoriteService->get(0, '2022-10-02', '11:00:00', $menuIds);
        $this->assertSame($oldPrice->id, $result['takeoutMenus'][0]['price']['id']);
        $this->assertSame('testprice1', $result['takeoutMenus'][0]['price']['priceCd']);
        $this->assertSame('1000', $result['takeoutMenus'][0]['price']['price']);

        // メニューIDと期間を指定して、関数呼び出し(メニューの値段が正しいこと)
        $result = $this->favoriteService->get(0, '2022-12-02', '11:00:00', $menuIds);
        $this->assertSame($price->id, $result['takeoutMenus'][0]['price']['id']);
        $this->assertSame('testprice2', $result['takeoutMenus'][0]['price']['priceCd']);
        $this->assertSame('1500', $result['takeoutMenus'][0]['price']['price']);
    }

    public function testGetFavoriteStores()
    {
        // テスト用データの登録
        list($station, $store, $menu, $oldPrice, $price, $image, $openingHour) = $this->_createFavorite('RS');

        // ユーザーIDを指定して、関数呼び出し
        $param = [
            'pickUpDate' => '2022-10-02',
            'pickUpTime' => '11:00:00',
            'visitPeople' => 2,
            'dateUndecided' => true,
        ];
        $result = $this->favoriteService->getFavoriteStores(1, $param);
        $this->assertArrayHasKey('restorantStores', $result);
        $this->assertCount(1, $result['restorantStores']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]);
        $this->assertArrayHasKey('access', $result['restorantStores'][0]);
        $this->assertArrayHasKey('daytimeBudgetLowerLimit', $result['restorantStores'][0]);
        $this->assertArrayHasKey('nightBudgetLowerLimit', $result['restorantStores'][0]);
        $this->assertArrayHasKey('storeGenres', $result['restorantStores'][0]);
        $this->assertCount(1, $result['restorantStores'][0]['storeGenres']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('genreCd', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('appCd', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('path', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('isDelegate', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('storeImage', $result['restorantStores'][0]);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('imageCd', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('imageUrl', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('recommendMenu', $result['restorantStores'][0]);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('price', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('priceCd', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('price', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('openinghours', $result['restorantStores'][0]);
        $this->assertCount(1, $result['restorantStores'][0]['openinghours']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('openTime', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('closeTime', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('code', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('lastOrderTIme', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('week', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('latitude', $result['restorantStores'][0]);
        $this->assertArrayHasKey('longitude', $result['restorantStores'][0]);
        $this->assertArrayHasKey('appCd', $result['restorantStores'][0]);
        $this->assertArrayHasKey('lowerOrdersTime', $result['restorantStores'][0]);
        $this->assertArrayHasKey('priceLevel', $result['restorantStores'][0]);
        $this->assertSame($store->id, $result['restorantStores'][0]['id']);
        $this->assertSame('テスト店舗', $result['restorantStores'][0]['name']);
        $this->assertSame('テスト駅から徒歩5分です。', $result['restorantStores'][0]['access']);
        $this->assertSame(1000, $result['restorantStores'][0]['daytimeBudgetLowerLimit']);
        $this->assertSame(1500, $result['restorantStores'][0]['nightBudgetLowerLimit']);
        $this->assertSame($store->genres()->first()->id, $result['restorantStores'][0]['storeGenres'][0]['id']);
        $this->assertSame('test4', $result['restorantStores'][0]['storeGenres'][0]['name']);
        $this->assertSame('test4', $result['restorantStores'][0]['storeGenres'][0]['genreCd']);
        $this->assertSame('TORS', $result['restorantStores'][0]['storeGenres'][0]['appCd']);
        $this->assertSame('/b-cooking/test2/test3', $result['restorantStores'][0]['storeGenres'][0]['path']);
        $this->assertNull($result['restorantStores'][0]['storeGenres'][0]['isDelegate']);
        $this->assertSame($image->id, $result['restorantStores'][0]['storeImage']['id']);
        $this->assertSame('RESTAURANT_LOGO', $result['restorantStores'][0]['storeImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restorantStores'][0]['storeImage']['imageUrl']);
        $this->assertSame($menu->id, $result['restorantStores'][0]['recommendMenu']['id']);
        $this->assertSame('テストメニュー', $result['restorantStores'][0]['recommendMenu']['name']);
        $this->assertSame($price->id, $result['restorantStores'][0]['recommendMenu']['price']['id']);
        $this->assertSame('testprice2', $result['restorantStores'][0]['recommendMenu']['price']['priceCd']);
        $this->assertSame('1500', $result['restorantStores'][0]['recommendMenu']['price']['price']);
        $this->assertCount(1, $result['restorantStores'][0]['openinghours']);
        $this->assertSame($openingHour->id, $result['restorantStores'][0]['openinghours'][0]['id']);
        $this->assertSame('11:00:00', $result['restorantStores'][0]['openinghours'][0]['openTime']);
        $this->assertSame('21:00:00', $result['restorantStores'][0]['openinghours'][0]['closeTime']);
        $this->assertSame('ALL_DAY', $result['restorantStores'][0]['openinghours'][0]['code']);
        $this->assertSame('20:30:00', $result['restorantStores'][0]['openinghours'][0]['lastOrderTIme']);
        $this->assertSame('10111110', $result['restorantStores'][0]['openinghours'][0]['week']);
        $this->assertSame(300.0, $result['restorantStores'][0]['latitude']);
        $this->assertSame(400.0, $result['restorantStores'][0]['longitude']);
        $this->assertSame('RS', $result['restorantStores'][0]['appCd']);
        $this->assertSame(60, $result['restorantStores'][0]['lowerOrdersTime']);
        $this->assertSame(2, $result['restorantStores'][0]['priceLevel']);

        // メニューIDを指定して、関数呼び出し(上記と同じ結果になること)
        $param = [
            'pickUpDate' => '2022-10-02',
            'pickUpTime' => '11:00:00',
            'visitPeople' => 2,
            'storeIds' => "{$store->id}",
            'dateUndecided' => true,
        ];
        $result = $this->favoriteService->getFavoriteStores(0, $param);
        $this->assertArrayHasKey('restorantStores', $result);
        $this->assertCount(1, $result['restorantStores']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]);
        $this->assertArrayHasKey('access', $result['restorantStores'][0]);
        $this->assertArrayHasKey('daytimeBudgetLowerLimit', $result['restorantStores'][0]);
        $this->assertArrayHasKey('nightBudgetLowerLimit', $result['restorantStores'][0]);
        $this->assertArrayHasKey('storeGenres', $result['restorantStores'][0]);
        $this->assertCount(1, $result['restorantStores'][0]['storeGenres']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('genreCd', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('appCd', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('path', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('isDelegate', $result['restorantStores'][0]['storeGenres'][0]);
        $this->assertArrayHasKey('storeImage', $result['restorantStores'][0]);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('imageCd', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('imageUrl', $result['restorantStores'][0]['storeImage']);
        $this->assertArrayHasKey('recommendMenu', $result['restorantStores'][0]);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('name', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('price', $result['restorantStores'][0]['recommendMenu']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('priceCd', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('price', $result['restorantStores'][0]['recommendMenu']['price']);
        $this->assertArrayHasKey('openinghours', $result['restorantStores'][0]);
        $this->assertCount(1, $result['restorantStores'][0]['openinghours']);
        $this->assertArrayHasKey('id', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('openTime', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('closeTime', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('code', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('lastOrderTIme', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('week', $result['restorantStores'][0]['openinghours'][0]);
        $this->assertArrayHasKey('latitude', $result['restorantStores'][0]);
        $this->assertArrayHasKey('longitude', $result['restorantStores'][0]);
        $this->assertArrayHasKey('appCd', $result['restorantStores'][0]);
        $this->assertArrayHasKey('lowerOrdersTime', $result['restorantStores'][0]);
        $this->assertArrayHasKey('priceLevel', $result['restorantStores'][0]);
        $this->assertSame($store->id, $result['restorantStores'][0]['id']);
        $this->assertSame('テスト店舗', $result['restorantStores'][0]['name']);
        $this->assertSame('テスト駅から徒歩5分です。', $result['restorantStores'][0]['access']);
        $this->assertSame(1000, $result['restorantStores'][0]['daytimeBudgetLowerLimit']);
        $this->assertSame(1500, $result['restorantStores'][0]['nightBudgetLowerLimit']);
        $this->assertSame($store->genres()->first()->id, $result['restorantStores'][0]['storeGenres'][0]['id']);
        $this->assertSame('test4', $result['restorantStores'][0]['storeGenres'][0]['name']);
        $this->assertSame('test4', $result['restorantStores'][0]['storeGenres'][0]['genreCd']);
        $this->assertSame('TORS', $result['restorantStores'][0]['storeGenres'][0]['appCd']);
        $this->assertSame('/b-cooking/test2/test3', $result['restorantStores'][0]['storeGenres'][0]['path']);
        $this->assertNull($result['restorantStores'][0]['storeGenres'][0]['isDelegate']);
        $this->assertSame($image->id, $result['restorantStores'][0]['storeImage']['id']);
        $this->assertSame('RESTAURANT_LOGO', $result['restorantStores'][0]['storeImage']['imageCd']);
        $this->assertSame('https://test.jp/test.jpg', $result['restorantStores'][0]['storeImage']['imageUrl']);
        $this->assertSame($menu->id, $result['restorantStores'][0]['recommendMenu']['id']);
        $this->assertSame('テストメニュー', $result['restorantStores'][0]['recommendMenu']['name']);
        $this->assertSame($price->id, $result['restorantStores'][0]['recommendMenu']['price']['id']);
        $this->assertSame('testprice2', $result['restorantStores'][0]['recommendMenu']['price']['priceCd']);
        $this->assertSame('1500', $result['restorantStores'][0]['recommendMenu']['price']['price']);
        $this->assertCount(1, $result['restorantStores'][0]['openinghours']);
        $this->assertSame($openingHour->id, $result['restorantStores'][0]['openinghours'][0]['id']);
        $this->assertSame('11:00:00', $result['restorantStores'][0]['openinghours'][0]['openTime']);
        $this->assertSame('21:00:00', $result['restorantStores'][0]['openinghours'][0]['closeTime']);
        $this->assertSame('ALL_DAY', $result['restorantStores'][0]['openinghours'][0]['code']);
        $this->assertSame('20:30:00', $result['restorantStores'][0]['openinghours'][0]['lastOrderTIme']);
        $this->assertSame('10111110', $result['restorantStores'][0]['openinghours'][0]['week']);
        $this->assertSame(300.0, $result['restorantStores'][0]['latitude']);
        $this->assertSame(400.0, $result['restorantStores'][0]['longitude']);
        $this->assertSame('RS', $result['restorantStores'][0]['appCd']);
        $this->assertSame(60, $result['restorantStores'][0]['lowerOrdersTime']);
        $this->assertSame(2, $result['restorantStores'][0]['priceLevel']);
    }

    private function _createFavorite($appCd)
    {
        $station = new Station();
        $station->name = 'テスト駅';
        $station->latitude = 100;
        $station->longitude = 200;
        $station->save();

        $store = new Store();
        $store->app_cd = $appCd;
        $store->name = 'テスト店舗';
        $store->latitude = 300;
        $store->longitude = 400;
        $store->access = 'テスト駅から徒歩5分です。';
        $store->daytime_budget_lower_limit = '1000';
        $store->night_budget_lower_limit = '1500';
        $store->price_level = 2;
        $store->lower_orders_time = 60;
        $store->station_id = $station->id;
        $store->published = 1;
        $store->save();

        $openingHour = null;
        if ($appCd == 'RS') {
            $image = new Image();
            $image->store_id = $store->id;
            $image->image_cd = 'RESTAURANT_LOGO';
            $image->weight = 1.00;
            $image->url = 'https://test.jp/test.jpg';
            $image->save();

            $openingHour = new OpeningHour();
            $openingHour->store_id = $store->id;
            $openingHour->week = '10111110';
            $openingHour->opening_hour_cd = 'ALL_DAY';
            $openingHour->start_at = '11:00:00';
            $openingHour->end_at = '21:00:00';
            $openingHour->last_order_time = '20:30:00';
            $openingHour->save();

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
        }

        $menu = new Menu();
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->store_id = $store->id;
        $menu->description = 'テストメニュー説明';
        $menu->sales_lunch_start_time = '11:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '21:00:00';
        $menu->published = 1;
        $menu->save();

        $oldPrice = new Price();
        $oldPrice->menu_id = $menu->id;
        $oldPrice->price_cd = 'testprice1';
        $oldPrice->start_date = '2022-01-01';
        $oldPrice->end_date = '2022-11-30';
        $oldPrice->price = 1000;
        $oldPrice->save();

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->price_cd = 'testprice2';
        $price->start_date = '2022-12-01';
        $price->end_date = '2999-12-31';
        $price->price = 1500;
        $price->save();

        if ($appCd == 'TO') {
            $image = new Image();
            $image->menu_id = $menu->id;
            $image->image_cd = 'MENU_MAIN';
            $image->weight = 1.00;
            $image->url = 'https://test.jp/test.jpg';
            $image->save();
        }

        $favoriteList = [];
        if ($appCd == 'TO') {
            $favoriteList[] = ['id' => $menu->id];
        } else {
            $favoriteList[] = ['id' => $store->id];
        }
        $favorite = new Favorite();
        $favorite->list = json_encode($favoriteList);
        $favorite->app_cd = $appCd;
        $favorite->user_id = 1;
        $favorite->save();

        return [$station, $store, $menu, $oldPrice, $price, $image, $openingHour];
    }

}
