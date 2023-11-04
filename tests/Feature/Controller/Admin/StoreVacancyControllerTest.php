<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Holiday;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\Vacancy;
use App\Models\VacancyQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreVacancyControllerTest extends TestCase
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

    public function testIndexWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.index');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.index');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.index');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callIndex($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.index');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'weeks',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('weeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testIndexAjax()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndexAjax($store);
        $response->assertStatus(200)->assertJson([
            ['title' => '予約あり','start' => '2099-10-01','color' => '#FFCCFF',],
            ['title' => '予約あり','start' => '2099-10-02','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-03','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-04','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-05','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-06','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-07','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-08','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-09','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-10','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-11','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-12','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-13','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-14','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-15','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-16','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-17','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-18','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-19','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-20','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-21','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-22','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-23','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-24','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-25','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-26','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-27','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-28','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-29','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-30','color' => '#FFCCFF',],
            ['title' => '予約なし','start' => '2099-10-31','color' => '#FFCCFF',],
            ['title' => '☆ 祝日','start' => '2099-10-03','color' => '#FF0000', 'textColor' => '#FFFFFF', 'classNames' => ['testClass']],
            ['title' => '販売中','start' => '2099-10-01','color' => '#e3f4fc',],
            ['title' => '販売中','start' => '2099-10-02','color' => '#e3f4fc',],
            ['title' => '売止','start' => '2099-10-03','color' => '#e3f4fc',],
            ['title' => '販売中','start' => '2099-10-04','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-05','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-06','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-07','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-08','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-09','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-10','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-11','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-12','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-13','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-14','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-15','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-16','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-17','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-18','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-19','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-20','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-21','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-22','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-23','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-24','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-25','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-26','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-27','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-28','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-29','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-30','color' => '#e3f4fc',],
            ['title' => '在庫データ未登録','start' => '2099-10-31','color' => '#e3f4fc',],
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '10:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '11:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '13:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '13:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '14:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '14:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '15:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '15:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '16:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '16:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '17:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '17:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '18:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '18:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '08:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '08:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '10:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '11:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '13:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '13:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '14:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '14:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '15:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '15:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '16:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '16:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '17:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '17:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '18:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '18:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '08:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '08:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '10:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '11:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '13:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '13:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '14:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '14:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '15:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '15:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '16:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '16:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '17:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '17:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '18:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '18:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '08:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '08:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '10:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '11:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '13:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '13:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '14:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '14:00:00', 'isOpen' => 0, 'countReservation' => 0],
            '15:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '15:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '16:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '16:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '17:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '17:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '18:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '18:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '08:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '08:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormNotIntervalTime()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditNotIntervalTime($store, true);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '10:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '11:00:00' => ['reserved' => '', 'base_stock' => 5, 'is_stop_sale' => 0, 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 4],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormNotIntervalTimeNotVacancy()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditNotIntervalTime($store, false);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '10:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '11:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '09:30:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '09:30:00', 'isOpen' => 1, 'countReservation' => 0],
            '10:30:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '10:30:00', 'isOpen' => 1, 'countReservation' => 0],
            '11:30:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '11:30:00', 'isOpen' => 1, 'countReservation' => 0],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '12:30:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:30:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 30);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditFormHoliday()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditHoliday($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'title',
            'date',
            'store',
            'intervals',
            'intervalTime',
            'regexp',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('title', '2099年10月01日');
        $response->assertViewHas('date', '2099-10-01');
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '09:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '09:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '10:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '10:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '11:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '11:00:00', 'isOpen' => 1, 'countReservation' => 0],
            '12:00:00' => ['reserved' => '', 'base_stock' => '', 'is_stop_sale' => '', 'time' => '12:00:00', 'isOpen' => 1, 'countReservation' => 0],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[0|1][0|1][0|1][0|1][0|1][0|1][0|1]');

        $this->logout();
    }

    public function testEditAllFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.editAll');  // 指定bladeを確認
        $response->assertViewHasAll([
            'term',
            'start',
            'end',
            'store',
            'intervals',
            'intervalTime',
            'weeks',
            'regexp',
            'paramWeek',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('term', $start->format('Y年m月d日') . ' ~ ' . $end->format('Y年m月d日'));
        $response->assertViewHas('start', $start->format('Y-m-d'));
        $response->assertViewHas('end', $end->format('Y-m-d'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '08:00:00' => ['time' => '08:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '09:00:00' => ['time' => '09:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '10:00:00' => ['time' => '10:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '11:00:00' => ['time' => '11:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '12:00:00' => ['time' => '12:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '13:00:00' => ['time' => '13:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '14:00:00' => ['time' => '14:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '15:00:00' => ['time' => '15:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '16:00:00' => ['time' => '16:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '17:00:00' => ['time' => '17:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '18:00:00' => ['time' => '18:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[1][0|1][1][1][1][1][1][0|1]');
        $response->assertViewHas('paramWeek', ['1', '0', '1', '1', '1', '1', '1', '0']);

        $this->logout();
    }

    public function testEditAllFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.editAll');  // 指定bladeを確認
        $response->assertViewHasAll([
            'term',
            'start',
            'end',
            'store',
            'intervals',
            'intervalTime',
            'weeks',
            'regexp',
            'paramWeek',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('term', $start->format('Y年m月d日') . ' ~ ' . $end->format('Y年m月d日'));
        $response->assertViewHas('start', $start->format('Y-m-d'));
        $response->assertViewHas('end', $end->format('Y-m-d'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '08:00:00' => ['time' => '08:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '09:00:00' => ['time' => '09:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '10:00:00' => ['time' => '10:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '11:00:00' => ['time' => '11:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '12:00:00' => ['time' => '12:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '13:00:00' => ['time' => '13:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '14:00:00' => ['time' => '14:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '15:00:00' => ['time' => '15:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '16:00:00' => ['time' => '16:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '17:00:00' => ['time' => '17:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '18:00:00' => ['time' => '18:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[1][0|1][1][1][1][1][1][0|1]');
        $response->assertViewHas('paramWeek', ['1', '0', '1', '1', '1', '1', '1', '0']);

        $this->logout();
    }

    public function testEditAllFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.editAll');  // 指定bladeを確認
        $response->assertViewHasAll([
            'term',
            'start',
            'end',
            'store',
            'intervals',
            'intervalTime',
            'weeks',
            'regexp',
            'paramWeek',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('term', $start->format('Y年m月d日') . ' ~ ' . $end->format('Y年m月d日'));
        $response->assertViewHas('start', $start->format('Y-m-d'));
        $response->assertViewHas('end', $end->format('Y-m-d'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '08:00:00' => ['time' => '08:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '09:00:00' => ['time' => '09:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '10:00:00' => ['time' => '10:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '11:00:00' => ['time' => '11:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '12:00:00' => ['time' => '12:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '13:00:00' => ['time' => '13:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '14:00:00' => ['time' => '14:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '15:00:00' => ['time' => '15:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '16:00:00' => ['time' => '16:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '17:00:00' => ['time' => '17:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '18:00:00' => ['time' => '18:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[1][0|1][1][1][1][1][1][0|1]');
        $response->assertViewHas('paramWeek', ['1', '0', '1', '1', '1', '1', '1', '0']);

        $this->logout();
    }

    public function testEditAllFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.editAll');  // 指定bladeを確認
        $response->assertViewHasAll([
            'term',
            'start',
            'end',
            'store',
            'intervals',
            'intervalTime',
            'weeks',
            'regexp',
            'paramWeek',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('term', $start->format('Y年m月d日') . ' ~ ' . $end->format('Y年m月d日'));
        $response->assertViewHas('start', $start->format('Y-m-d'));
        $response->assertViewHas('end', $end->format('Y-m-d'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '08:00:00' => ['time' => '08:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '09:00:00' => ['time' => '09:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '10:00:00' => ['time' => '10:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '11:00:00' => ['time' => '11:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '12:00:00' => ['time' => '12:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '13:00:00' => ['time' => '13:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '14:00:00' => ['time' => '14:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '15:00:00' => ['time' => '15:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '16:00:00' => ['time' => '16:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '17:00:00' => ['time' => '17:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
            '18:00:00' => ['time' => '18:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 1],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[1][0|1][1][1][1][1][1][0|1]');
        $response->assertViewHas('paramWeek', ['1', '0', '1', '1', '1', '1', '1', '0']);

        $this->logout();
    }

    public function testEditAllFormParamsSession()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // セッションに入力内容を格納しておく（登録後の画面遷移時の処理）
        $params = [
            '08:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '09:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '10:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '11:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '12:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '15:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '16:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '17:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            '18:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
        ];
        session(['params' => $params]);

        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Vacancy.editAll');  // 指定bladeを確認
        $response->assertViewHasAll([
            'term',
            'start',
            'end',
            'store',
            'intervals',
            'intervalTime',
            'weeks',
            'regexp',
            'paramWeek',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('term', $start->format('Y年m月d日') . ' ~ ' . $end->format('Y年m月d日'));
        $response->assertViewHas('start', $start->format('Y-m-d'));
        $response->assertViewHas('end', $end->format('Y-m-d'));
        $response->assertViewHas('store', $store);
        $response->assertViewHas('intervals', [
            '08:00:00' => ['time' => '08:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '09:00:00' => ['time' => '09:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '10:00:00' => ['time' => '10:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '11:00:00' => ['time' => '11:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '12:00:00' => ['time' => '12:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '13:00:00' => ['time' => '13:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '14:00:00' => ['time' => '14:00:00', 'base_stock' => '', 'is_stop_sale' => '', 'isOpen' => 0],
            '15:00:00' => ['time' => '15:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '16:00:00' => ['time' => '16:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '17:00:00' => ['time' => '17:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
            '18:00:00' => ['time' => '18:00:00', 'base_stock' => 5, 'is_stop_sale' => 0, 'isOpen' => 1],
        ]);
        $response->assertViewHas('intervalTime', 60);
        $response->assertViewHas('regexp', '[1][0|1][1][1][1][1][1][0|1]');
        $response->assertViewHas('paramWeek', ['1', '0', '1', '1', '1', '1', '1', '0']);

        $this->logout();
    }

    public function testEditAllWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(302);   // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&intervalTime=60");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // Queueに登録されていることを確認する
        $this->assertTrue(VacancyQueue::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditAllWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(302);   // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&intervalTime=60");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // Queueに登録されていることを確認する
        $this->assertTrue(VacancyQueue::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditAllWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(302);   // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&intervalTime=60");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // Queueに登録されていることを確認する
        $this->assertTrue(VacancyQueue::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditAllWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(302);   // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&intervalTime=60");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');

        // Queueに登録されていることを確認する
        $this->assertTrue(VacancyQueue::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditAllThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditAll($store, $startDay, $endDay, ['60']);  // 例外発生されるため、あえて文字列ではなく配列を渡す
        $response->assertStatus(302);   // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&intervalTime%5B0%5D=60");   // リダイレクト先
        $response->assertSessionHas('custom_error', '更新できませんでした。');

        // Queueに登録されていないことを確認する
        $this->assertFalse(VacancyQueue::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(302);                                          // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/edit");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');
        $response->assertSessionHas('date', '2099-10-01');

        // 古い在庫情報は削除されていることを確認する
        $this->assertNull(Vacancy::find($oldVacancy->id));
        // 新しく在庫情報が登録されていることを確認する
        // headcount1つにつき、11レコードできる。よって11レコード×headcount10パターン=110レコードできることになる
        $this->assertSame(110, Vacancy::where('store_id', $store->id)->whereDate('date', '2099-10-01')->get()->count());

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(302);                                          // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/edit");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');
        $response->assertSessionHas('date', '2099-10-01');

        // 古い在庫情報は削除されていることを確認する
        $this->assertNull(Vacancy::find($oldVacancy->id));
        // 新しく在庫情報が登録されていることを確認する
        // headcount1つにつき、11レコードできる。よって11レコード×headcount10パターン=110レコードできることになる
        $this->assertSame(110, Vacancy::where('store_id', $store->id)->whereDate('date', '2099-10-01')->get()->count());

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(302);                                          // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/edit");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');
        $response->assertSessionHas('date', '2099-10-01');

        // 古い在庫情報は削除されていることを確認する
        $this->assertNull(Vacancy::find($oldVacancy->id));
        // 新しく在庫情報が登録されていることを確認する
        // headcount1つにつき、11レコードできる。よって11レコード×headcount10パターン=110レコードできることになる
        $this->assertSame(110, Vacancy::where('store_id', $store->id)->whereDate('date', '2099-10-01')->get()->count());

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(302);                                          // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/edit");   // リダイレクト先
        $response->assertSessionHas('message', '更新しました。');
        $response->assertSessionHas('date', '2099-10-01');

        // 古い在庫情報は削除されていることを確認する
        $this->assertNull(Vacancy::find($oldVacancy->id));
        // 新しく在庫情報が登録されていることを確認する
        // headcount1つにつき、11レコードできる。よって11レコード×headcount10パターン=110レコードできることになる
        $this->assertSame(110, Vacancy::where('store_id', $store->id)->whereDate('date', '2099-10-01')->get()->count());

        $this->logout();
    }

    public function testEditThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store, $oldVacancy, ['60']);
        $response->assertStatus(302);                                          // リダイレクト
        $response->assertRedirect("/admin/store/vacancy/{$store->id}/edit");   // リダイレクト先
        $response->assertSessionHas('custom_error', '更新できませんでした。');
        $response->assertSessionHas('date', '2099-10-01');

        // 古い在庫情報は削除されていないことを確認する
        $this->assertNotNull(Vacancy::find($oldVacancy->id));
        // 新しく在庫情報が登録されていないことを確認する
        $this->assertFalse(Vacancy::where('store_id', $store->id)->whereDate('date', '2099-10-01')->where('id', '<>', $oldVacancy->id)->exists());

        $this->logout();
    }

    public function testStoreVacancyControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method editAllForm
        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(404);

        // target method editAll
        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreVacancyControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex($store);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method editAllForm
        $response = $this->_callEditAllForm($store, $start, $end);
        $response->assertStatus(404);

        // target method editAll
        $response = $this->_callEditAll($store, $startDay, $endDay);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $oldVacancy);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStore($settlementCompanyId, $appCd = 'RS')
    {
        $store = new Store();
        $store->app_cd = $appCd;
        $store->code = 'test-code-test';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->area_id = 1;
        $store->number_of_seats = 10;
        $store->published = 0;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _createVacancy($storeId, $date = '2099-10-01', $time = '09:00:00', $isStopSale = 0, $baseStock = 1)
    {
        $vacancy = new Vacancy();
        $vacancy->store_id = $storeId;
        $vacancy->date = $date;
        $vacancy->time = $time;
        $vacancy->headcount = 1;
        $vacancy->base_stock = $baseStock;
        $vacancy->stock = 1;
        $vacancy->is_stop_sale = $isStopSale;
        $vacancy->save();
        return $vacancy;
    }

    private function _createHoliday($date)
    {
        $holiday = new Holiday();
        $holiday->date = $date;
        $holiday->name = '祝日';
        $holiday->save();
    }

    private function _createReservation($storeId, $pickUpDatetime)
    {
        $reservation = new Reservation();
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'RESERVE';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $storeId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();
    }

    private function _createOpeningHour($storeId, $startAt = '09:00', $endAt = '13:00', $lastOrderTime = '12:30')
    {
        $openingHour = new OpeningHour();
        $openingHour->store_id = $storeId;
        $openingHour->opening_hour_cd = 'ALL_DAY';
        $openingHour->week = '10111110';
        $openingHour->start_at = $startAt;
        $openingHour->end_at = $endAt;
        $openingHour->last_order_time = $lastOrderTime;
        $openingHour->save();
        return $openingHour;
    }

    private function _callIndex($store)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/vacancy/{$store->id}");
    }

    private function _callIndexAjax($store)
    {
        $this->_createVacancy($store->id, '2099-10-01', '09:00:00');
        $this->_createVacancy($store->id, '2099-10-01', '10:00:00');
        $this->_createVacancy($store->id, '2099-10-02', '09:00:00');
        $this->_createVacancy($store->id, '2099-10-02', '10:00:00');
        $this->_createVacancy($store->id, '2099-10-03', '09:00:00', 1);
        $this->_createVacancy($store->id, '2099-10-04', '09:00:00', 1);
        $this->_createVacancy($store->id, '2099-10-04', '10:00:00');
        $this->_createHoliday('2099-10-03');
        $this->_createReservation($store->id, '2099-10-01 09:00:00');
        $this->_createReservation($store->id, '2099-10-02 09:00:00');
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->get("/admin/store/vacancy/{$store->id}?start=2099-10-01&end=2099-10-30");
    }

    private function _callEditForm($store)
    {
        $this->_createOpeningHour($store->id);
        $this->_createOpeningHour($store->id, '15:00', '19:00', '18:30');
        $this->_createOpeningHour($store->id, '08:00', '09:00', '09:00');   // 営業時間が登録済みデータより早い時間分を登録
        $this->_createVacancy($store->id, '2099-10-01', '09:00:00', 0, 5);
        $this->_createVacancy($store->id, '2099-10-01', '10:00:00', 0, 5);
        $this->_createVacancy($store->id, '2099-10-01', '11:00:00', 0, 5);
        return $this->get("/admin/store/vacancy/{$store->id}/edit?date=2099-10-01&intervalTime=60");
    }

    private function _callEditNotIntervalTime($store, $addVacancy)
    {
        if ($addVacancy) {
            $this->_createVacancy($store->id, '2099-10-01', '09:00:00', 0, 5);
            $this->_createVacancy($store->id, '2099-10-01', '10:00:00', 0, 5);
            $this->_createVacancy($store->id, '2099-10-01', '11:00:00', 0, 5);
        }
        $this->_createOpeningHour($store->id);
        return $this->get("/admin/store/vacancy/{$store->id}/edit?date=2099-10-01&intervalTime=");
    }

    private function _callEditHoliday($store)
    {
        $this->_createHoliday('2099-10-01');
        $this->_createOpeningHour($store->id);
        return $this->get("/admin/store/vacancy/{$store->id}/edit?date=2099-10-01&intervalTime=60");
    }

    private function _callEditAllForm($store, &$start, &$end)
    {
        $this->_createOpeningHour($store->id);
        $this->_createOpeningHour($store->id, '15:00', '19:00', '18:30');
        $this->_createOpeningHour($store->id, '08:00', '09:00', '09:00');   // 営業時間が登録済みデータより早い時間分を登録

        // 3ヶ月以内の期間を指定（２ヶ月後の1−5日）
        $now = new Carbon();
        $start = $now->copy()->addMonth(2)->day(1);
        $startDay = $start->format('Y-m-d');
        $end = $now->copy()->addMonth(2)->day(5);
        $endDay = $end->format('Y-m-d');

        return $this->get("/admin/store/vacancy/{$store->id}/editAllForm?start={$startDay}&end={$endDay}&week[]=1&week[]=0&week[]=1&week[]=1&week[]=1&week[]=1&week[]=1&week[]=0&intervalTime=60");
    }

    private function _callEditAll($store, &$startDay, &$endDay, $intervalTime = '60')
    {
        $this->_createOpeningHour($store->id, '08:00', '13:00', '12:30');
        $this->_createOpeningHour($store->id, '15:00', '19:00', '18:30');

        // 3ヶ月以内の期間を指定（２ヶ月後の1−5日）
        $now = new Carbon();
        $start = $now->copy()->addMonth(2)->day(1);
        $startDay = $start->format('Y-m-d');
        $end = $now->copy()->addMonth(2)->day(5);
        $endDay = $end->format('Y-m-d');

        return $this->post("/admin/store/vacancy/{$store->id}/editAll", [
            'start' => $startDay,
            'end' => $endDay,
            'regexp' => '[1][0|1][1][1][1][1][1][0|1]',
            'intervalTime' => $intervalTime,
            'week' => [1, 0, 1, 1, 1, 1, 1, 1, 0],
            'interval' => [
                '08:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '09:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '10:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '11:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '12:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '15:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '16:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '17:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '18:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEdit($store, &$vacancy, $intervalTime = '60')
    {
        $this->_createOpeningHour($store->id, '08:00', '13:00', '12:30');
        $this->_createOpeningHour($store->id, '15:00', '19:00', '18:30');
        $vacancy = $this->_createVacancy($store->id, '2099-10-01', '09:00:00', 0, 5);
        return $this->post("/admin/store/vacancy/{$store->id}/edit", [
            'date' => '2099-10-01',
            'regexp' => '[1][0|1][1][1][1][1][1][0|1]',
            'intervalTime' => $intervalTime,
            'week' => [1, 0, 1, 1, 1, 1, 1, 1, 0],
            'interval' => [
                '08:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '09:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '10:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '11:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '12:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '15:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '16:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '17:00:00' => ['base_stock' => 5, 'is_stop_sale' => 0],
                '18:00:00' => ['base_stock' => 0, 'is_stop_sale' => 0],
            ],
            'store_id' => $store->id,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

}
