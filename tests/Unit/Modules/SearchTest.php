<?php

namespace Tests\Unit\Modules;

use App\Models\Menu;
use App\Models\Station;
use App\Models\Store;
use App\Modules\Search;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchTest extends TestCase
{
    private $testMenu;
    private $search;
    private $testStationLatitude = 35.71053109581002;   // 押上駅（スカイツリーすぐそば）
    private $testStationLongitude = 139.8133720521922;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->search = new Search();
        $this->testMenu = $this->_createStoreMenu();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetDistance()
    {
        $stationLatitude = $this->testStationLatitude;
        $stationLongitude = $this->testStationLongitude;

        // 1:現在地とメニューに紐づく店の距離を求める
        $result = $this->search->getDistance($stationLatitude, $stationLongitude, $this->testMenu, 1);
        $this->assertSame(247.41891086748, $result);

        // 2:現在地とメニューに紐づく駅の距離を求める
        $result = $this->search->getDistance($stationLatitude, $stationLongitude, $this->testMenu, 2);
        $this->assertSame(247.41891086748, $result);

        // 紐付け店舗のないメニュー
        $result = $this->search->getDistance($stationLatitude, $stationLongitude, new Menu(), 1);
        $this->assertNull($result);
    }

    public function testGetStoreDistance()
    {
        $stationLatitude = $this->testStationLatitude;
        $stationLongitude = $this->testStationLongitude;
        $store_id = $this->testMenu->store_id;

        $result = $this->search->getStoreDistance($stationLatitude, $stationLongitude, $store_id);
        $this->assertSame(247.41891086748, $result);
    }

    private function _createStoreMenu()
    {
        $station = new Station();
        $station->latitude = 35.71023773445729;   // スカイツリーの場所を指定
        $station->longitude = 139.81066166723554;
        $station->save();

        $store = new Store();
        $store->name = 'グルメtestテスト店舗';
        $store->latitude = 35.71023773445729;   // スカイツリーの場所を指定
        $store->longitude = 139.81066166723554;
        $store->station_id = $station->id;
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();

        return $menu;
    }
}
