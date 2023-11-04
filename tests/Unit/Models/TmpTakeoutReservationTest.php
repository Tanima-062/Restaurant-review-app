<?php

namespace Tests\Unit\Models;

use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Stock;
use App\Models\Store;
use App\Models\TmpTakeoutReservation;
use Exception;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TmpTakeoutReservationTest extends TestCase
{
    private $testMenuId;
    private $testTmpTakeoutReservationId;
    private $tmpTakeoutReservation;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->tmpTakeoutReservation = new TmpTakeoutReservation();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveSession()
    {
        $this->_createMenu();
        $userInfo = [
            'firstName' => '太朗',
            'lastName' => 'グルメ',
            'email' => 'gourmet-test@adventure-inc.co.jp',
            'tel' => '0698765432',
            'request' => '卵アレルギーです',
        ];
        $menu = array_merge(Menu::where('id', $this->testMenuId)->first()->toArray(), ['count' => 2]);
        $menus = [[
            'menu' => $menu,
        ]];
        $info = [
            'customer' => $userInfo,
            'application' => [
                'pickUpDate' => '2022-10-01',
                'pickUpTime' => '09:00:00',
                'menus' => [$menus[0], $menus[0]],
            ],
        ];
        $menuInfo = [];
        $menuInfo[$this->testMenuId] = $menu;
        $menuInfo[$this->testMenuId]['menuPrice']['price'] = 1500;

        // 営業日チェック（エラー)
        {
            $this->_createStock(10, '2022-10-01', $this->testMenuId);
            $errMsg = null;
            $this->assertFalse($this->tmpTakeoutReservation->saveSession('testsession', $info, $errMsg));
            $this->assertSame('土曜日は定休日のため注文できません。', $errMsg);
        }

        // 在庫なしエラー
        {
            $info['application']['pickUpDate'] = '2022-10-02';
            $info['application']['pickUpTime'] = '09:00:00';
            $errMsg = null;
            $this->assertFalse($this->tmpTakeoutReservation->saveSession('testsession', $info, $errMsg));
            $this->assertSame('在庫がありません。', $errMsg);
        }

        //  同時間帯注文組数チェック（エラー)
        {
            $this->_createStock(10, '2999-10-02', $this->testMenuId);
            $info['application']['pickUpDate'] = '2999-10-02';
            $info['application']['pickUpTime'] = '10:00:00';
            $errMsg = null;
            $this->assertFalse($this->tmpTakeoutReservation->saveSession('testsession', $info, $errMsg));
            $this->assertSame('注文が殺到しているため受けられません。', $errMsg);
        }

        //  正常
        {
            $menu = Menu::find($this->testMenuId);
            $menu->number_of_orders_same_time = 5;
            $menu->save();
            $this->_createStock(10, '2999-10-03', $this->testMenuId);
            $info['application']['pickUpDate'] = '2999-10-03';
            $info['application']['pickUpTime'] = '10:00:00';
            $errMsg = null;
            $this->assertTrue($this->tmpTakeoutReservation->saveSession('testsession', $info, $errMsg));
            $this->assertNull($errMsg);
        }

        //  例外エラー
        {
            $menu = Menu::find($this->testMenuId);
            $menu->number_of_orders_same_time = 5;
            $menu->save();
            $this->_createStock(10, '2999-10-04', $this->testMenuId);
            $info['application']['pickUpDate'] = '2999-10-04';
            $info['application']['pickUpTime'] = '10:00:00';
            $info['customer'] = null;
            $errMsg = null;
            $this->assertFalse($this->tmpTakeoutReservation->saveSession('testsession', $info, $errMsg));
            $this->assertSame('save failed', $errMsg);
        }
    }

    public function testDeleteSession()
    {
        // 正常
        $this->_createTmpTakeoutReservation();
        $this->assertTrue($this->tmpTakeoutReservation->deleteSession('testsession'));
    }

    public function testSaveRes()
    {
        // 正常
        $this->_createTmpTakeoutReservation();
        $this->assertTrue($this->tmpTakeoutReservation->saveRes('testsession', [], 'COMPLETE'));
        $tmpTakeoutReservation = TmpTakeoutReservation::find($this->testTmpTakeoutReservationId);
        $this->assertSame('COMPLETE', $tmpTakeoutReservation->status);
    }

    public function testGetInfo()
    {
        // 該当データなし
        try {
            $result = $this->tmpTakeoutReservation->getInfo('testsession');
        } catch (Exception $e) {
            $this->assertSame('No query results for model [App\Models\TmpTakeoutReservation].', $e->getMessage());
        }

        // 正常
        $this->_createTmpTakeoutReservation();
        $result = $this->tmpTakeoutReservation->getInfo('testsession');
        $this->assertSame(['test'],$result);

        // 例外エラー
        try {
            // テストデータ更新（info列をnull)
            $tmpTakeoutReservation = $this->tmpTakeoutReservation::find($this->testTmpTakeoutReservationId);
            $tmpTakeoutReservation->info = null;
            $tmpTakeoutReservation->save();

            $result = $this->tmpTakeoutReservation->getInfo('testsession');
        } catch (Exception $e) {
            $this->assertSame('Undefined variable: info', $e->getMessage());
        }
    }

    private function _createMenu()
    {
        $store = new Store();
        $store->regular_holiday = '11111011';
        $store->save();

        $openingHour = new OpeningHour();
        $openingHour->store_id = $store->id;
        $openingHour->week = '11111011';
        $openingHour->start_at = '09:00:00';
        $openingHour->end_at = '13:00:00';
        $openingHour->save();
        $this->testOpeningHourId = $openingHour->id;

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->app_cd = 'TO';
        $menu->sales_lunch_start_time = '09:00:00';
        $menu->sales_lunch_end_time = '13:00:00';
        $menu->lower_orders_time = '1';
        $menu->provided_day_of_week = '11111011';
        $menu->save();
        $this->testMenuId = $menu->id;
    }

    private function _createStock($stock_number, $date, $menuId)
    {
        $stock = new Stock();
        $stock->stock_number = $stock_number;
        $stock->date = $date;
        $stock->menu_id = $menuId;
        $stock->save();
    }

    private function _createTmpTakeoutReservation()
    {
        $tmpTakeoutReservation = new TmpTakeoutReservation();
        $tmpTakeoutReservation->session_id = 'testsession';
        $tmpTakeoutReservation->info = json_encode(['test']);
        $tmpTakeoutReservation->save();
        $this->testTmpTakeoutReservationId = $tmpTakeoutReservation->id;
    }
}
