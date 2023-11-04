<?php

namespace Tests\Unit\Commands;

use App\Models\Menu;
use App\Models\Store;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterAllStockTest extends TestCase
{
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

    public function testRegisterAllStock()
    {
        // テスト対象店舗を一旦クリアする
        $this->_clearMenu();

        // テスト１（対象メニューなしで実行エラー）
        {
            // バッチ実行
            $this->artisan('register_all:stock 3')
                ->expectsOutput(0);
        }

        // テストデータ作成
        $menu = $this->_createMenu('11111010');   // 土祝は提供不可
        $stock = $this->_createStock($menu, '2022-11-02');

        // テスト2（対象メニューあり、期間指定：あり、指定期間に登録在庫あり）
        {
            // 実行前確認(在庫は１件)
            $this->assertSame(1, Stock::where('menu_id', $menu->id)->whereBetWeen('date', ['2022-11-02', '2022-11-05'])->count());

            // バッチ実行
            $this->artisan('register_all:stock 3 2022/11/2 2022/11/5')
                ->expectsOutput(0);

            // 実行結果
            // 11/2は営業日のため、在庫ありだが、既存データがあるため、上書きされていない
            $result = Stock::find($stock->id);
            $this->assertSame($menu->id, $result->menu_id);
            $this->assertSame(10, $result->stock_number);
            // 11/4は営業日のため、在庫あり
            $result = Stock::where('menu_id', $menu->id)->where('date', '2022-11-04');
            $this->assertTrue($result->exists());
            $this->assertSame(3, $result->first()->stock_number);
            // 11/3は祝日（定休日）のため、在庫なし
            $this->assertFalse(Stock::where('menu_id', $menu->id)->where('date', '2022-11-03')->exists());
            // 11/5は定休日のため、在庫なし
            $this->assertFalse(Stock::where('menu_id', $menu->id)->where('date', '2022-11-05')->exists());
        }

        // テスト3（対象メニューあり、期間指定：あり、指定期間に登録在庫なし）
        {
            // 実行前確認(在庫は0件)
            $this->assertFalse(Stock::where('menu_id', $menu->id)->where('date', '2022-11-05')->exists());

            // バッチ実行
            $this->artisan('register_all:stock 3 2022/11/5 2022/11/5')
                ->expectsOutput(0);

            // 実行結果
            // 11/5は定休日のため、在庫なし
            $this->assertFalse(Stock::where('menu_id', $menu->id)->where('date', '2022-11-05')->exists());
        }

        // テストデータ作成
        $this->_clearMenu();
        $menu2 = $this->_createMenu('11111111');   // 全日提供可

        // テスト4（対象メニューあり、期間指定：なし(翌々月)）
        {
            $startDate = Carbon::now()->addMonthsNoOverflow(2)->startOfMonth();
            $endDate = Carbon::now()->addMonthsNoOverflow(2)->endOfMonth();
            $testDate = Carbon::now()->addMonthsNoOverflow(2);
            $testDate->day = 10;

            // 実行前確認(在庫がない事)
            $this->assertFalse(Stock::where('menu_id', $menu2->id)->whereBetWeen('date', [$startDate, $endDate])->exists());

            // バッチ実行
            $this->artisan('register_all:stock 7')
                ->expectsOutput(0);

            // 実行結果（在庫が登録され、在庫数は指定数(7)である）
            $result = Stock::where('menu_id', $menu2->id)->whereBetWeen('date', [$startDate, $endDate]);
            $this->assertTrue($result->exists());
            $this->assertSame(7, $result->first()->stock_number);
        }
    }

    private function _clearMenu()
    {
        // 全て対象外にする（トランザクション内のことなので問題なし）
        Menu::where('batch_register_all_stock_flg', 1)
            ->update(['batch_register_all_stock_flg' => 0]);
    }

    private function _createMenu($providedDayOfWeek)
    {
        $store = new Store();
        $store->app_cd = 'TO';
        $store->save();
        $this->storeFirst = $store;

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'TO';
        $menu->provided_day_of_week = $providedDayOfWeek;
        $menu->batch_register_all_stock_flg = 1;
        $menu->save();
        return $menu;
    }

    private function _createStock($menu, $date)
    {
        $stock = new Stock();
        $stock->menu_id = $menu->id;
        $stock->date = $date;
        $stock->stock_number = 10;
        $stock->save();
        return $stock;
    }
}
