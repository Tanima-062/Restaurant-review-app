<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Price;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PriceTest extends TestCase
{
    private $price;
    private $testMenuId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->price = new Price();

        $this->_createPrice();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeMenuId()
    {
        $result = $this->price::MenuId($this->testMenuId)->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());
    }

    public function testScopeAvailable()
    {
        $result = $this->price::Available($this->testMenuId, '2022-10-01')->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
    }

    private function _createPrice()
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();
        $this->testMenuId = $menu->id;

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->start_date = '2022-01-01';
        $price->end_date = '2022-12-01';
        $price->save();

        $price = new Price();
        $price->menu_id = $menu->id;
        $price->start_date = '2023-01-01';
        $price->end_date = '2023-12-01';
        $price->save();
    }
}
