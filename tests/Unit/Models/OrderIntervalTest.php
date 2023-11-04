<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\OrderInterval;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderIntervalTest extends TestCase
{
    private $orderInterval;
    private $testDate;
    private $testDateTomorrow;
    private $testMenuId;
    private $reservationInfo;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->orderInterval = new OrderInterval();

        $dt = new Carbon('2021-04-02');
        $this->testDate = $dt->copy()->format('Y-m-d');
        $this->testDateTomorrow = $dt->copy()->tomorrow();
        $this->reservationInfo = '{
            "customer":{
               "firstName":"吾郎",
               "lastName":"山田",
               "email":"yamada@org",
               "tel":"012012340000",
               "request":"アレルギーあります"
            },
            "application":{
               "menus":[
                  {
                     "menu":{
                        "id":1,
                        "count":1
                     },
                     "options":[
                        {
                           "id":1,
                           "keywordId":0,
                           "contentsId":0
                        }
                     ]
                  },
                  {
                    "menu":{
                       "id":2,
                       "count":1
                    },
                    "options":[
                       {
                          "id":2,
                          "keywordId":0,
                          "contentsId":0
                       }
                    ]
                 }
               ],
               "pickUpDate":"2222-02-22",
               "pickUpTime":"12:00:00"
            },
            "payment":{
               "returnUrl":"string"
            }
         }';

        $this->_createStoreMenu();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testIsOrderable()
    {
        // 各種設定
        // ランチタイム  12:00~15:00
        // ディナータイム 17:00~20:00
        // 同時注文可能数 10コ
        // 最低注文時間 15分
        // 受け取り間隔 15分

        /*
         * ランチ開始前
         */
        // 2021-04-02 11:00:00に11:15受け取り予定で注文できない。メッセージ="次の注文可能時間は12:00からです。"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '11:15', $this->testMenuId, 1, $msg, '2021-04-02 11:00:00');
        $this->assertSame('次の注文可能時間は12:00からです。', $msg);

        // 2021-04-02 11:44:59に12:00受け取り予定で注文できる。メッセージ=""
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '12:00', $this->testMenuId, 1, $msg, '2021-04-02 11:44:59');
        $this->assertNull($msg);

        // 2021-04-02 11:45:00に12:00受け取り予定で注文できない。メッセージ="次の注文可能時間は12:15からです"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '12:00', $this->testMenuId, 1, $msg, '2021-04-02 11:45:00');
        $this->assertSame('次の注文可能時間は12:15からです。', $msg);

        /*
         * ランチ時間中
         */
        // 2021-04-02 14:44:59に15:00受け取り予定で注文できる。メッセージ=""
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '15:00', $this->testMenuId, 1, $msg, '2021-04-02 14:44:59');
        $this->assertNull($msg);

        // 2021-04-02 14:45:00に15:00受け取り予定で注文できない。メッセージ="次の注文可能時間は17:00からです"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '15:00', $this->testMenuId, 1, $msg, '2021-04-02 14:45:00');
        $this->assertSame('次の注文可能時間は17:00からです。', $msg);

        /*
         * ランチ終了とディナー開始の間
         */
        // 2021-04-02 15:00:00に15:30受け取り予定で注文できない。メッセージ="次の注文可能時間は17:00からです"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '15:30', $this->testMenuId, 1, $msg, '2021-04-02 15:00:00');
        $this->assertSame('次の注文可能時間は17:00からです。', $msg);

        /*
         * ディナー時間中
         */
        // 2021-04-02 19:44:59に20:00受け取り予定で注文できる。メッセージ=""
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '20:00', $this->testMenuId, 1, $msg, '2021-04-02 19:44:59');
        $this->assertNull($msg);

        // 2021-04-02 19:45:00に20:00受け取り予定で注文できない。メッセージ="次の注文可能時間は12:00からです"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '20:00', $this->testMenuId, 1, $msg, '2021-04-02 19:45:00');
        $this->assertSame('次の注文可能時間は12:00からです。', $msg);

        /*
         * ランチ終了とディナー終了
         */
        // 2021-04-02 20:00:00に20:30受け取り予定で注文できない。メッセージ="次の注文可能時間は12:00からです"
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '20:30', $this->testMenuId, 1, $msg, '2021-04-02 20:00:00');
        $this->assertSame('次の注文可能時間は12:00からです。', $msg);

        /*
         * ランチ時間中 15分毎に10コを超えて注文はできない
         */
        // 2021-04-02 14:44:59に15:00受け取り予定で注文できる。メッセージ=""
        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '15:00', $this->testMenuId, 10, $msg, '2021-04-02 14:44:59');
        $this->assertNull($msg);

        $msg = null;
        $this->orderInterval->isOrderable('2021-04-02', '15:00', $this->testMenuId, 11, $msg, '2021-04-02 14:44:59');
        $this->assertSame('注文が殺到しているため受けられません。', $msg);
    }

    /*
        public function testTakeOrder()
        {
            $errMsg = null;

            // 注文時間帯データを作成しておく
            $this->orderInterval->isOrderable($this->testDate, '11:59', 1, 0, $msg);
            $this->orderInterval->isOrderable($this->testDate, '11:59', 2, 0, $msg);

            // 10コまでは注文できる
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
            $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);

            // 2222-02-22 12:00　11コ目は上限
            try {
                $this->orderInterval->takeOrder(json_decode($this->reservationInfo, true), $errMsg);
                $this->assertTrue(false);
            } catch (\Throwable $e) {
                $this->assertTrue(true);
            }
        }
    */
    private function _createStoreMenu()
    {
        $store = new Store();
        $store->pick_up_time_interval = 15;
        $store->lower_orders_time = 15;
        $store->save();

        $menu = new Menu();
        $menu->sales_lunch_start_time = '12:00';
        $menu->sales_lunch_end_time = '15:00';
        $menu->sales_dinner_start_time = '17:00';
        $menu->sales_dinner_end_time = '20:00';
        $menu->number_of_orders_same_time = 10;
        $menu->store_id = $store->id;
        $menu->save();
        $this->testMenuId = $menu->id;
    }
}
