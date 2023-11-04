<?php

namespace Tests\Unit\Models;

use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\Stock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockTest extends TestCase
{
    private $stock;
    private $testDate;
    private $testDateTomorrow;
    private $testMenuId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->stock = new Stock();

        $dt = new Carbon('2222-02-22');
        $this->testDate = $dt->copy()->format('Y-m-d');
        $this->testDateTomorrow = $dt->copy()->tomorrow();
        $this->testMenuId = 99999;
        $this->_createStock(100, $this->testDate);
        $this->_createStock(100, $this->testDateTomorrow);
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testHasStock()
    {
        // 2222-02-22に在庫100中99予約する
        $this->_createReservation($this->testDate, $this->testMenuId, 99);
        // 2222-02-23に在庫100中97予約する
        $this->_createReservation($this->testDateTomorrow, $this->testMenuId, 98);

        // 2222-02-22の現在の在庫はまだある
        $this->assertTrue($this->stock->hasStock($this->testDate, $this->testMenuId, 0));
        // 2222-02-22の1コは在庫ある
        $this->assertTrue($this->stock->hasStock($this->testDate, $this->testMenuId, 1));
        // 2222-02-22の2コは在庫ない
        $this->assertFalse($this->stock->hasStock($this->testDate, $this->testMenuId, 2));

        // 2222-02-23の現在の在庫はまだある
        $this->assertTrue($this->stock->hasStock($this->testDateTomorrow, $this->testMenuId, 0));
        // 2222-02-23の1コは在庫ある
        $this->assertTrue($this->stock->hasStock($this->testDateTomorrow, $this->testMenuId, 1));
        // 2222-02-23の2コは在庫ある
        $this->assertTrue($this->stock->hasStock($this->testDateTomorrow, $this->testMenuId, 2));
        // 2222-02-23の3コは在庫ない
        $this->assertFalse($this->stock->hasStock($this->testDateTomorrow, $this->testMenuId, 3));
    }

    public function testScopeMenuId()
    {
        $result = $this->stock::MenuId($this->testMenuId)->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());
    }

    private function _createReservation($date, $menuId, $count)
    {
        $reservation = new Reservation();
        $reservation->pick_up_datetime = $date;
        $reservation->save();
        $reservationMenu = new ReservationMenu();
        $reservationMenu->count = $count;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menuId;
        $reservationMenu->save();
    }

    private function _createStock($stock_number, $date)
    {
        $stock = new Stock();
        $stock->stock_number = $stock_number;
        $stock->date = $date;
        $stock->menu_id = $this->testMenuId;
        $stock->save();
    }
}
