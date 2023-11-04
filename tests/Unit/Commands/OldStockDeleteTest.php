<?php

namespace Tests\Unit\Commands;

use App\Models\Reservation;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OldStockDeleteTest extends TestCase
{
    private $testReservationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testOldStockDelete()
    {
        $dt = new Carbon();
        $lastMonth = $dt->copy()->subMonth()->format('Y-m-d');
        $stock1 = $this->_createStock($lastMonth);  // 先月分データ
        $stock2 = $this->_createStock($dt);         // 今月分データ

        // テスト1（該当データあり）
        // バッチ実行
        $this->artisan('stock:delete')
        ->expectsOutput('[OldStockDelete] ##### START #####')
        ->expectsOutput(0);

        $this->assertFalse(Stock::where('id', $stock1->id)->exists()); // 削除されている
        $this->assertTrue(Stock::where('id', $stock2->id)->exists()); // 削除されていない

        // テスト２（該当データなし）
        // バッチ実行
        $this->artisan('stock:delete')
        ->expectsOutput('[OldStockDelete] ##### START #####')
        ->expectsOutput(0);

        $this->assertTrue(Stock::where('id', $stock2->id)->exists()); // 削除されていない
    }

    private function _createStock($date)
    {
        $stock = new Stock();
        $stock->date = $date;
        $stock->save();
        return $stock;
    }
}
