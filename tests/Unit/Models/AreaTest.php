<?php

namespace Tests\Unit\Models;

use App\Models\Area;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AreaTest extends TestCase
{
    private $area;
    private $testParentAreaId;
    private $testAreaId;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->area = new Area();

        $this->_createArea();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStores()
    {
        $testStoreId = $this->testStoreId;
        $result = $this->area::whereHas('stores', function ($query) use ($testStoreId) {
            $query->where('id', $testStoreId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testAreaId, $result[0]['id']);
    }

    public function testScopeGetListByPath()
    {
        $result = $this->area::GetListByPath('oosaka', 2)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testAreaId, $result[0]['id']);
    }

    public function testScopeAdminSearchFilter()
    {
        $valid = [
            'name' => 'ななんば',
            'area_cd' => 'nananba',
            'path' => 'oosaka',
        ];
        $result = $this->area::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testAreaId, $result[0]['id']);
    }

    public function testScopeGetStartWithPath()
    {
        $result = $this->area::GetStartWithPath('oosaka', 1)->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testAreaId, $result[0]['id']);
    }

    public function testScopeGetAreaIdWithAreaCd()
    {
        $result = $this->area::GetAreaIdWithAreaCd('nananba')->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testAreaId, $result[0]['id']);
    }

    public function testScopeOrderBySort()
    {
        $result = $this->area::OrderBySort()
            ->whereIn('id', [$this->testAreaId, $this->testParentAreaId])->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testParentAreaId, $result[0]['id']); // 並び順が正しいか
        $this->assertSame($this->testAreaId, $result[1]['id']);
    }

    public function testGetParentAreas()
    {
        // 該当データあり
        $result = $this->area->getParentAreas(['oosaka']);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertSame($this->testParentAreaId, $result[0]['id']);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertSame('お大阪', $result[0]['name']);
        $this->assertArrayHasKey('area_cd', $result[0]);
        $this->assertSame('oosaka', $result[0]['area_cd']);

        // 該当データなし
        $result = $this->area->getParentAreas(['testosaka']);
        $this->assertCount(0, $result);
    }

    private function _createArea()
    {
        $parentArea = new Area();
        $parentArea->name = 'お大阪';
        $parentArea->area_cd = 'oosaka';
        $parentArea->path = '/';
        $parentArea->level = 1;
        $parentArea->published = 1;
        $parentArea->sort = 1;
        $parentArea->save();
        $this->testParentAreaId = $parentArea->id;

        $area = new Area();
        $area->name = 'ななんば';
        $area->area_cd = 'nananba';
        $area->path = '/' . $parentArea->area_cd;
        $area->published = 1;
        $area->level = 2;
        $area->sort = 10;
        $area->save();
        $this->testAreaId = $area->id;

        $store = new Store();
        $store->area_id = $this->testAreaId;
        $store->save();
        $this->testStoreId = $store->id;
    }
}
