<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\Store;
use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptionTest extends TestCase
{
    private $option;
    private $testMenuId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->option = new Option();

        $this->_createOption();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeMenuId()
    {
        $result = $this->option::MenuId($this->testMenuId)->get();
        $this->assertIsObject($result);
    }

    private function _createOption()
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();
        $this->testMenuId = $menu->id;

        $option = new Option();
        $option->menu_id = $menu->id;
        $option->save();
    }
}
