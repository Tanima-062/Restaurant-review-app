<?php

namespace Tests\Unit\Modules\Reservation;

use App\Models\Menu;
use App\Models\Store;
use App\Modules\Reservation\OrderIntervalOperation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderIntervalOperationTest extends TestCase
{
    private $OrderIntervalOperation;
    private $pickUpDate;
    private $menu;

    public function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();
        $this->_createMenu();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetter()
    {
        $this->_createOrderIntervalOperation('2022-10-01 10:00:00');

        // target method::getNow()
        $result = $this->OrderIntervalOperation->getNow();
        $this->assertTrue($this->pickUpDate->eq($result));

        // target method::getMenu()
        $result = $this->OrderIntervalOperation->getMenu();
        $this->assertSame($this->menu->id, $result->id);

        // target method::getLunchStart()
        $checkDate = new Carbon('2022-10-01 11:00:00');
        $result = $this->OrderIntervalOperation->getLunchStart();
        $this->assertTrue($checkDate->eq($result));

        // target method::getLunchEnd()
        // 受け取り時間間隔を考慮した時間になる
        $checkDate = new Carbon('2022-10-01 13:30:00');
        $result = $this->OrderIntervalOperation->getLunchEnd();
        $this->assertTrue($checkDate->eq($result));

        // target method::getDinnerStart()
        $checkDate = new Carbon('2022-10-01 17:00:00');
        $result = $this->OrderIntervalOperation->getDinnerStart();
        $this->assertTrue($checkDate->eq($result));

        // target method::getDinnerEnd()
        // 受け取り時間間隔を考慮した時間になる
        $checkDate = new Carbon('2022-10-01 21:30:00');
        $result = $this->OrderIntervalOperation->getDinnerEnd();
        $this->assertTrue($checkDate->eq($result));

        // target method::getPickUpDateTime()
        $result = $this->OrderIntervalOperation->getPickUpDateTime();
        $this->assertTrue($this->pickUpDate->eq($result));
    }

    public function testIsLunchTime()
    {
        // ランチ時間内
        $this->_createOrderIntervalOperation('2022-10-01 12:00:00');
        $this->assertTrue($this->OrderIntervalOperation->isLunchTime());

        // ランチ時間外
        $this->_createOrderIntervalOperation('2022-10-01 10:00:00');
        $this->assertFalse($this->OrderIntervalOperation->isLunchTime());
    }

    public function testIsDinnerTime()
    {
        // ディナー時間内
        $this->_createOrderIntervalOperation('2022-10-01 18:00:00');
        $this->assertTrue($this->OrderIntervalOperation->isDinnerTime());

        // ディナー時間外
        $this->_createOrderIntervalOperation('2022-10-01 23:00:00');
        $this->assertFalse($this->OrderIntervalOperation->isDinnerTime());
    }

    public function testIsOrderTakable()
    {
        $this->_createOrderIntervalOperation('2022-10-01 10:00:00');
        // 注文可
        $this->assertTrue($this->OrderIntervalOperation->isOrderTakable(1));
        // 注文不可（注文数オーバー）
        $this->assertFalse($this->OrderIntervalOperation->isOrderTakable(10));
    }

    public function testIsLastOrderEnded()
    {
        // 過去の受け取り時間を指定
        $this->_createOrderIntervalOperation('2022-10-01 10:00:00', '2022-10-02 10:00:00');
        $msg = null;
        $this->assertTrue($this->OrderIntervalOperation->isLastOrderEnded($msg));
        $this->assertSame('過去の受け取り時間は指定できません。', $msg);

        // 準備時間に間に合わない受け取り時間の場合
        {
            // 2022-10-01 10:00:00に10:00:00受け取り予定で注文できない。メッセージ="次の注文可能時間は11:15からです。"
            $this->_createOrderIntervalOperation('2022-10-01 10:00:00');
            $msg = null;
            $this->assertTrue($this->OrderIntervalOperation->isLastOrderEnded($msg));
            $this->assertSame('次の注文可能時間は11:15からです。', $msg);

            // 2022-10-01 14:00:00に14:00:00受け取り予定で注文できない。メッセージ="次の注文可能時間は次の注文可能時間は17:00からです。"
            $this->_createOrderIntervalOperation('2022-10-01 14:00:00');
            $msg = null;
            $this->assertTrue($this->OrderIntervalOperation->isLastOrderEnded($msg));
            $this->assertSame('次の注文可能時間は17:00からです。', $msg);

            // 2022-10-01 22:00:00に21:30:00受け取り予定で注文できない。メッセージ="次の注文可能時間は11:00からです。"
            $this->_createOrderIntervalOperation('2022-10-01 22:00:00');
            $msg = null;
            $this->assertTrue($this->OrderIntervalOperation->isLastOrderEnded($msg));
            $this->assertSame('次の注文可能時間は11:00からです。', $msg);
        }

        // 受け取り時間のインターバルがすでに開始している場合
        {
            // 条件に合うテストコードかけず...
        }

        // 注文時間内
        {
            // 受け取り時間=ランチ開始の場合
            // 2022-10-01 11:00:00に09:00:00受け取り予定で注文できる。メッセージ=""
            $this->_createOrderIntervalOperation('2022-10-01 11:00:00', '2022-10-01 09:00:00');
            $msg = null;
            $this->assertFalse($this->OrderIntervalOperation->isLastOrderEnded($msg));
            $this->assertNull($msg);

            // 2022-10-01 10:00:00に12:30受け取り予定で注文できる。メッセージ=""
            $this->_createOrderIntervalOperation('2022-10-01 12:30:00', '2022-10-01 10:00:00');
            $msg = null;
            $this->assertFalse($this->OrderIntervalOperation->isLastOrderEnded($msg));
            $this->assertNull($msg);
        }
    }

    private function _createMenu()
    {
        $store = new Store();
        $store->app_cd = 'TO';
        $store->pick_up_time_interval = '30';
        $store->lower_orders_time = '60';
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'TO';
        $menu->sales_lunch_start_time = '11:00:00';
        $menu->sales_lunch_end_time = '14:00:00';
        $menu->sales_dinner_start_time = '17:00:00';
        $menu->sales_dinner_end_time = '22:00:00';
        $menu->number_of_orders_same_time = 5;
        $menu->save();
        $this->menu = $menu;
    }

    private function _createOrderIntervalOperation($pickUpDate, $nowDate = null)
    {
        $this->pickUpDate = new Carbon($pickUpDate);
        if (is_null($nowDate)) {
            $nowDateTime = $pickUpDate;
        } else {
            $nowDateTime = new Carbon($nowDate);
        }
        $this->OrderIntervalOperation = new OrderIntervalOperation($this->menu->id,  $this->pickUpDate->toDateString(), $this->pickUpDate->toTimeString('microsecond'), $nowDateTime);
    }
}
