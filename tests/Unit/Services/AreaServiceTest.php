<?php

namespace Tests\Unit\Services;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AreaServiceTest extends TestCase
{
    private $area;
    private $areaService;

    public function setUp(): void
    {
        parent::setUp();
        $this->areaService = $this->app->make('App\Services\AreaService');
        DB::beginTransaction();

        $area = new Area();
        $area->name = '大阪';
        $area->area_cd = 'testosaka';
        $area->level = 1;
        $area->path = '/';
        $area->published = 1;
        $area->save();
        $this->area = $area;
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetArea()
    {
        $area = $this->_createArea('梅田', 'testumeda', $this->area);
        $area2 = $this->_createArea('茶屋町', 'testchayamachi', $area);
        $area3 = $this->_createArea('茶屋町2', 'testchayamachi2', $area2, false);

        // area_cd＝JAPANからLEVEL＝1エリアの取得
        $result = $this->areaService->getArea('JAPAN');
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($this->area->id, array_column($result['areas'], 'id')));

        // area_cd＝JAPANからLEVEL＝2エリアの取得
        $result = $this->areaService->getArea('JAPAN', 2);
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($area->id, array_column($result['areas'], 'id')));

        // 指定したエリア(LEVEL＝1)の直下のエリア
        $result = $this->areaService->getArea('testosaka');
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($area->id, array_column($result['areas'], 'id')));

        // 指定したエリア(LEVEL＝2)の直下のエリア
        $result = $this->areaService->getArea('testumeda');
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($area2->id, array_column($result['areas'], 'id')));

        // 指定したエリア(LEVEL＝1)の直下のエリア
        $result = $this->areaService->getArea('testosaka', 2);
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($area->id, array_column($result['areas'], 'id')));

        // 指定したエリア(LEVEL＝2)の直下のエリア
        $result = $this->areaService->getArea('testumeda', 2);
        $this->assertTrue(count($result) > 0);
        $this->assertArrayHasKey('areas', $result);
        $this->assertTrue(in_array($area2->id, array_column($result['areas'], 'id')));

        // 指定したエリア(LEVEL＝3)の直下のエリア($area3取れない）
        $result = $this->areaService->getArea('testchayamachi', 3);
        $this->assertCount(0, $result);

        // 存在しないエリア名を指定
        $result = $this->areaService->getArea('testtesttest');
        $this->assertCount(0, $result);
    }

    public function testGeaAreaAdmin()
    {
        $area = $this->_createArea('梅田', 'testumeda', $this->area);
        $param = [
            'areaCd' => 'test',
        ];
        $result = $this->areaService->getAreaAdmin($param);
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($area->id, $result[0]['id']);
    }

    private function _createArea($areaName, $areaCd, $parendArea, $addStore = true)
    {
        $area = new Area();
        $area->name = $areaName;
        $area->area_cd = $areaCd;
        $area->level = $parendArea->level + 1;
        if ($parendArea->path == '/') {
            $area->path = '/' . $parendArea->area_cd;
        } else {
            $area->path = $parendArea->path . '/' . $parendArea->area_cd;
        }
        $area->published = 1;
        $area->save();

        if ($addStore) {
            $store = new Store();
            $store->name = 'テスト店舗';
            $store->code = 'teststore';
            $store->area_id = $area->id;
            $store->published = 1;
            $store->save();
        }

        return $area;
    }
}
