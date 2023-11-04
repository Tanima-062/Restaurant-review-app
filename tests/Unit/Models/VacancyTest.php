<?php

namespace Tests\Unit\Models;

use App\Models\ExternalApi;
use App\Models\Menu;
use App\Models\UpdateStockQueue;
use App\Models\Store;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VacancyTest extends TestCase
{
    private $testStoreId;
    private $testVacancyId;
    private $vacancy;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->vacancy = new Vacancy();

        $this->_createStore();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeGetApiShopId()
    {
        $this->_createVacancy('2022-10-01');

        $result = $this->vacancy::GetApiShopId($this->testStoreId)->get();
        $this->assertIsObject($result);
        $this->assertSame(100000, $result[0]['api_store_id']);
    }

    public function testScopeGetVacancies()
    {
        $this->_createVacancy('2022-10-01');

        $result = $this->vacancy::GetVacancies($this->testStoreId, '2022-10-01')->get();
        $this->assertIsObject($result);
        $this->assertSame($this->testVacancyId, $result[0]['id']);
    }

    public function testUpdateStock()
    {
        $menu = new Menu();
        $menu->store_id = $this->testStoreId;
        $menu->provided_time = '30';
        $menu->save();

        $dt = new Carbon('2022-10-01 09:00:00');

        // 空席データを用意する
        $this->_createVacancy('2022-10-01', '09:00:00', 1, 10);
        $vacancyId = $this->testVacancyId;
        $this->_createVacancy('2022-10-01', '09:00:00', 2, 5);
        $vacancyId2 = $this->testVacancyId;

        // 外部接続なし店舗の場合
        {
            $this->vacancy->updateStock(3, $menu, $dt);

            // 更新結果：$vacancyIdは7、$vacancyId2は3
            $vacancy = $this->vacancy::find($vacancyId);
            $this->assertSame(7, $vacancy['stock']);
            $vacancy = $this->vacancy::find($vacancyId2);
            $this->assertSame(3, $vacancy['stock']);
        }

        // 外部接続あり店舗の場合
        {
            $externalApi = new ExternalApi();
            $externalApi->store_id = $this->testStoreId;
            $externalApi->save();

            $this->vacancy->updateStock(3, $menu, $dt);

            // Queue用データが登録されているか確認
            $UpdateStockQueue = UpdateStockQueue::where('store_id', $this->testStoreId)->get();
            $this->assertSame(1, $UpdateStockQueue->count());
            $this->assertSame('2022-10-01', $UpdateStockQueue[0]['date']);
        }
    }

    private function _createStore()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;
    }

    private function _createVacancy($date, $time='09:00:00', $headCount=1, $stock=1)
    {
        $vacancy = new Vacancy();
        $vacancy->api_store_id = 100000;
        $vacancy->store_id = $this->testStoreId;
        $vacancy->date = $date;
        $vacancy->time = $time;
        $vacancy->headcount = $headCount;
        $vacancy->stock = $stock;
        $vacancy->save();
        $this->testVacancyId = $vacancy->id;
    }
}
