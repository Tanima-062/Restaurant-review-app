<?php

namespace Tests\Unit\Models;

use App\Models\OpeningHour;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpeningHourTest extends TestCase
{
    private $openingHour;
    private $testStoreId;
    private $testOpeningHourId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->openingHour = new OpeningHour();

        $this->_createOpeningHour();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeStoreId()
    {
        $result = $this->openingHour::StoreId($this->testStoreId)->get();
        $this->assertIsObject($result);
    }

    public function testGetMypageOpeningHour()
    {
        // 通常営業日（月曜日）
        $result = $this->openingHour->getMypageOpeningHour($this->testStoreId, 0, false);
        $this->assertCount(1, $result);
        $this->assertSame($this->testOpeningHourId, $result[0]['id']);

        // 通常休業日（火曜日：平日）
        $result = $this->openingHour->getMypageOpeningHour($this->testStoreId, 1, false);
        $this->assertCount(0, $result);

        // 通常休業日（火曜日：祝日）
        $result = $this->openingHour->getMypageOpeningHour($this->testStoreId, 1, true);
        $this->assertCount(0, $result);

        // 祝日営業日確認
        {
            // 祝日を営業日に変更
            $openingHour = OpeningHour::find($this->testOpeningHourId);
            $openingHour->week = '10111111';
            $openingHour->save();

            // 祝日営業日
            $result = $this->openingHour->getMypageOpeningHour($this->testStoreId, 1, true);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOpeningHourId, $result[0]['id']);
        }
    }

    private function _createOpeningHour()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;

        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId;
        $openingHour->week = '10111110';                // 火曜だけ休業（祝日は休み）
        $openingHour->save();
        $this->testOpeningHourId = $openingHour->id;
    }
}
