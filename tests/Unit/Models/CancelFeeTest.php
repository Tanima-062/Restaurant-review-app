<?php

namespace Tests\Unit\Models;

use App\Models\CancelFee;
use App\Models\Menu;
use App\Models\Option;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CancelFeeTest extends TestCase
{
    private $cancelFee;
    private $testStoreId;
    private $reservationIdForNotCancelFee;      // 予約ID（キャンセルポリシー未登録）
    private $reservationIdForDayCancelFee;      // 予約ID（キャンセルポリシー日付設定）
    private $reservationIdForTimeCancelFee;     // 予約ID（キャンセルポリシー時間設定）
    private $reservationIdForDAYTimeCancelFee;  // 予約ID（キャンセルポリシー日付＆時間設定）
    private $reservationIdForOther;             // 予約ID（その他）
    private $reservationIdForOther2;            // 予約ID（その他）
    private $cancelFeeIdForDayCancelFee;        // キャンセルポリシーID（キャンセルポリシー日付指定）
    private $cancelFeeIdForTimeCancelFee;       // キャンセルポリシーID（キャンセルポリシー時間指定）
    private $cancelFeeIdForDAYTimeCancelFee;    // キャンセルポリシーID（キャンセルポリシー日付＆時間指定）
    private $cancelFeeIdForOther;               // キャンセルポリシーID（その他）
    private $cancelFeeIdForOther2;              // キャンセルポリシーID（その他）

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cancelFee = new CancelFee();

        $this->_createCancelFee();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetCancelPolicy()
    {
        $result = $this->cancelFee->getCancelPolicy($this->testStoreId, 'RS');
        $this->assertIsObject($result);
    }

    public function testCalcCancelFee()
    {
        // キャンセルポリシー未登録店舗（来店前）
        $result = $this->cancelFee->calcCancelFee($this->reservationIdForNotCancelFee);
        $this->assertCount(3, $result);
        $this->assertNull($result['0']);    // refundPrice
        $this->assertNull($result['1']);    // cancelPrice
        $this->assertNull($result['2']);    // cancelFeeId

        // キャンセルポリシー未登録店舗（来店後）
        {
            $reservation = Reservation::find($this->reservationIdForNotCancelFee);
            $reservation->pick_up_datetime = '2022-10-01';
            $reservation->save();

            $result = $this->cancelFee->calcCancelFee($this->reservationIdForNotCancelFee);
            $this->assertCount(3, $result);
            $this->assertNull($result['0']);    // refundPrice
            $this->assertNull($result['1']);    // cancelPrice
            $this->assertNull($result['2']);    // cancelFeeId
        }

        // キャンセルポリシー日付（DAY)設定（有効データ：あり）のみ店舗（来店前）
        // キャンセルポリシー最高額設定あり
        {
            $result = $this->cancelFee->calcCancelFee($this->reservationIdForDayCancelFee);
            $this->assertCount(3, $result);
            $this->assertSame(3800, $result['0']);                                // refundPrice  //5300円-1500円（最高額）
            $this->assertCount(4, $result[1]);                                      // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                        // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(1500, $result['1']['total']);                       // cancelPrice（値：キャンセル料金）...最高金額設定値
            $this->assertArrayHasKey('menu', $result['1']);                         // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                              // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(750.0, $result['1']['menu']['price']);                // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(2, $result['1']['menu']['count']);                  // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(1500.0, $result['1']['menu']['total']);               // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertArrayHasKey('options', $result['1']);                      // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                           // cancelPrice（予約オプション詳細 オプション数）
            $this->assertArrayHasKey('diff', $result['1']);                         // cancelPrice（キー名：差額）
            $this->assertSame($this->cancelFeeIdForDayCancelFee , $result['2']);  // cancelFeeId
        }

        // キャンセルポリシー日付（DAY)設定（有効データ：なし))のみ店舗（来店前）
        // キャンセルポリシー最高額設定あり
        {
            $cancelFee = Reservation::find($this->reservationIdForDayCancelFee);
            $cancelFee->pick_up_datetime = Carbon::now()->addHour(2)->format('Y-m-d 18:00:00');  // 予約日を2時間後に変更
            $cancelFee->save();

            $result = $this->cancelFee->calcCancelFee($this->reservationIdForDayCancelFee);
            $this->assertCount(3, $result);
            $this->assertNull($result['0']);    // refundPrice
            $this->assertNull($result['1']);    // cancelPrice
            $this->assertNull($result['2']);    // cancelFeeId
        }

        // キャンセルポリシー時間（TIME)設定のみ店舗（来店前）
        // キャンセルポリシー最低額設定あり
        // 予約料金よりもキャンセル料金が低い場合
        {
            $result = $this->cancelFee->calcCancelFee($this->reservationIdForTimeCancelFee);
            $this->assertCount(3, $result);
            $this->assertSame(2300, $result['0']);                                // refundPrice  // 5300円-3000円
            $this->assertCount(4, $result[1]);                                      // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                        // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(3000, $result['1']['total']);                       // cancelPrice（値：キャンセル料金）...最低キャンセル料金が計算されたキャンセル料金よりも高いため、最低キャンセル料金が入る
            $this->assertArrayHasKey('menu', $result['1']);                         // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                              // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(1250.0, $result['1']['menu']['price']);               // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(2, $result['1']['menu']['count']);                  // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(2500.0, $result['1']['menu']['total']);               // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertArrayHasKey('options', $result['1']);                      // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                           // cancelPrice（予約オプション詳細 オプション数）
            $this->assertArrayHasKey('diff', $result['1']);                         // cancelPrice（キー名：差額）
            $this->assertSame($this->cancelFeeIdForTimeCancelFee , $result['2']); // cancelFeeId
        }

        // キャンセルポリシー時間（TIME)設定のみ店舗（来店前）
        // キャンセルポリシー最低額設定あり
        // 予約料金よりもキャンセル料金が高い場合
        {
            // キャンセル最低料金を変更
            $cancelFee = CancelFee::find($this->cancelFeeIdForTimeCancelFee);
            $cancelFee->cancel_fee_min = '7000';
            $cancelFee->save();

            $result = $this->cancelFee->calcCancelFee($this->reservationIdForTimeCancelFee);
            $this->assertCount(3, $result);
            $this->assertSame(0, $result['0']);                                     // refundPrice...5300円-7000円=マイナスになるのでキャンセル料金は0
            $this->assertCount(4, $result[1]);                                      // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                        // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(5300, $result['1']['total']);                         // cancelPrice（値：キャンセル料金）...最低キャンセル料金が計算されたキャンセル料金よりも高いが、予約料金を超えるため、予約料金が入る
            $this->assertArrayHasKey('menu', $result['1']);                         // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                              // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(1250.0, $result['1']['menu']['price']);               // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(2, $result['1']['menu']['count']);                    // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(2500.0, $result['1']['menu']['total']);               // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertArrayHasKey('options', $result['1']);                      // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                           // cancelPrice（予約オプション詳細 オプション数）
            $this->assertArrayHasKey('diff', $result['1']);                         // cancelPrice（キー名：差額）
            $this->assertSame($this->cancelFeeIdForTimeCancelFee , $result['2']);   // cancelFeeId
        }

        // キャンセルポリシー時間（DAY/TIME)設定のみ店舗（来店前）
        // 予約料金よりもキャンセル料金が高い場合
        {
            $result = $this->cancelFee->calcCancelFee($this->reservationIdForDAYTimeCancelFee);
            $this->assertCount(3, $result);
            $this->assertSame(0, $result['0']);                                         // refundPrice  // 5300円-5500円＝マイナスになるのでキャンセル料金は0
            $this->assertCount(3, $result[1]);                                          // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                            // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(5300, $result['1']['total']);                             // cancelPrice（値：キャンセル料金）...キャンセル定額値
            $this->assertArrayHasKey('menu', $result['1']);                             // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                                  // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(5000, $result['1']['menu']['price']);                     // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(1, $result['1']['menu']['count']);                        // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(5000, $result['1']['menu']['total']);                     // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertArrayHasKey('options', $result['1']);                          // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                               // cancelPrice（予約オプション詳細 オプション数）
            $this->assertSame($this->cancelFeeIdForDAYTimeCancelFee , $result['2']);    // cancelFeeId
        }

        // キャンセルポリシー時間（DAY/TIME)設定のみ店舗（来店前）
        // 予約料金よりもキャンセル値料金が低い
        // ＆メニュー料金よりもキャンセル料金が低い
        {
            // キャンセル料金設定を変更
            $cancelFee = CancelFee::find($this->cancelFeeIdForDAYTimeCancelFee);
            $cancelFee->cancel_fee = '1000';
            $cancelFee->save();

            $result = $this->cancelFee->calcCancelFee($this->reservationIdForDAYTimeCancelFee);
            $this->assertCount(3, $result);
            $this->assertSame(4300, $result['0']);                                      // refundPrice  // 5300円-1000円
            $this->assertCount(2, $result[1]);                                          // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                            // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(1000, $result['1']['total']);                             // cancelPrice（値：キャンセル料金）...キャンセル料金（定額）
            $this->assertArrayHasKey('menu', $result['1']);                             // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                                  // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(1000, $result['1']['menu']['price']);                     // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(1, $result['1']['menu']['count']);                        // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(1000, $result['1']['menu']['total']);                     // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertSame($this->cancelFeeIdForDAYTimeCancelFee , $result['2']);    // cancelFeeId
        }

        // その他
        // 予約料金よりもキャンセル値料金が低い
        // ＆メニュー料金よりもキャンセル料金が高い（オプションあり）
        // ＆オプション料金よりもキャンセル料金が低い
        {
            $result = $this->cancelFee->calcCancelFee($this->reservationIdForOther);
            $this->assertCount(3, $result);
            $this->assertSame(1000, $result['0']);                                      // refundPrice  // 5000円-4000円
            $this->assertCount(3, $result[1]);                                          // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                            // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(4000, $result['1']['total']);                             // cancelPrice（値：キャンセル料金）...キャンセル料金（定額）
            $this->assertArrayHasKey('menu', $result['1']);                             // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                                  // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(1000, $result['1']['menu']['price']);                     // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(1, $result['1']['menu']['count']);                        // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(1000, $result['1']['menu']['total']);                     // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertArrayHasKey('options', $result['1']);                          // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                               // cancelPrice（予約オプション詳細 オプション数）
            $this->assertSame($this->cancelFeeIdForOther , $result['2']);               // cancelFeeId
        }

        // その他
        // 予約料金よりもキャンセル値料金が低い
        // ＆メニュー料金よりもキャンセル料金が高い（オプションあり）
        // ＆オプション料金よりもキャンセル料金が高い
        {
            // キャンセル料金設定を変更
            $cancelFee = CancelFee::find($this->cancelFeeIdForOther);
            $cancelFee->cancel_fee = '2500';
            $cancelFee->save();

            $result = $this->cancelFee->calcCancelFee($this->reservationIdForOther);
            $this->assertCount(3, $result);
            $this->assertSame(2500, $result['0']);                                      // refundPrice...5000円-2500円
            $this->assertCount(2, $result[1]);                                          // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                            // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(2500, $result['1']['total']);                             // cancelPrice（値：キャンセル料金）...キャンセル料金（定額）
            $this->assertArrayHasKey('options', $result['1']);                          // cancelPrice（キー名：予約オプション詳細）
            $this->assertCount(1, $result[1]['options']);                               // cancelPrice（予約オプション詳細 オプション数）
            $this->assertSame($this->cancelFeeIdForOther , $result['2']);               // cancelFeeId
        }

        // その他
        // 予約料金よりもキャンセル値料金が低い
        // ＆メニュー料金よりもキャンセル料金が高い（オプションなし）
        // ※予約オプションなしで、キャンセル料金もメニュー料金を超える場合（通常あり得るのか？
        {
            $result = $this->cancelFee->calcCancelFee($this->reservationIdForOther2);
            $this->assertCount(3, $result);
            $this->assertSame(3000, $result['0']);                                      // refundPrice...5000円-2000円
            $this->assertCount(2, $result[1]);                                          // cancelPrice
            $this->assertArrayHasKey('total', $result['1']);                            // cancelPrice（キー名：キャンセル料金）
            $this->assertSame(2000, $result['1']['total']);                             // cancelPrice（値：キャンセル料金）...オプションがなく、キャンセル料金（定額）がメニュー料金を超えるため、メニュー料金が入る
            $this->assertArrayHasKey('menu', $result['1']);                             // cancelPrice（キー名：予約メニュー詳細）
            $this->assertCount(3, $result[1]['menu']);                                  // cancelPrice（予約メニュー詳細）
            $this->assertArrayHasKey('price', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：単価）
            $this->assertSame(2000, $result['1']['menu']['price']);                     // cancelPrice（予約メニュー詳細 値：単価）
            $this->assertArrayHasKey('count', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：人数）
            $this->assertSame(1, $result['1']['menu']['count']);                        // cancelPrice（予約メニュー詳細 値：人数）
            $this->assertArrayHasKey('total', $result['1']['menu']);                    // cancelPrice（予約メニュー詳細 キー名：合計）
            $this->assertSame(2000, $result['1']['menu']['total']);                     // cancelPrice（予約メニュー詳細 値：合計）
            $this->assertSame($this->cancelFeeIdForOther2 , $result['2']);              // cancelFeeId
        }

    }

    private function _createCancelFee()
    {
        // キャンセルポリシー未登録店舗への予約（来店前）
        {
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $this->testStoreId;
            $menu->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = '2999-10-01 12:00:00';
            $reservation->save();
            $this->reservationIdForNotCancelFee = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForNotCancelFee;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->reservation_id = $this->reservationIdForNotCancelFee;
            $reservationMenu->save();
        }

        // キャンセルポリシー日付（DAY)設定店舗への予約（来店前）
        // 定率、切り上げ、最高額設定あり
        // 予約オプションあり
        {
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->save();

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = '150';
            $option->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = '2999-10-01 12:00:00';
            $reservation->total = '5300';
            $reservation->persons = 2;
            $reservation->save();
            $this->reservationIdForDayCancelFee = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForDayCancelFee;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = '2';
            $reservationMenu->unit_price = '2500';
            $reservationMenu->price = $reservationMenu->count * $reservationMenu->unit_price;
            $reservationMenu->reservation_id = $this->reservationIdForDayCancelFee;
            $reservationMenu->save();

            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $reservationMenu->id;
            $reservationOption->option_id = $option->id;
            $reservationOption->count = $reservationMenu->count;
            $reservationOption->unit_price = $option->price;
            $reservationOption->price = $reservationOption->count * $reservationOption->unit_price;
            $reservationOption->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_limit = '2';
            $cancelFee->cancel_fee_unit = 'FIXED_RATE';
            $cancelFee->cancel_fee = '30';
            $cancelFee->fraction_round = 'ROUND_UP';
            $cancelFee->cancel_fee_max = '1500';
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
            $this->cancelFeeIdForDayCancelFee = $cancelFee->id;
        }

        // キャンセルポリシー時間（TIME)設定店舗への予約（来店前）
        // 定率、切り捨て、最低額設定あり
        // 予約オプションあり
        {
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->save();

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = '150';
            $option->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = '2999-10-01 12:00:00';
            $reservation->total = '5300';
            $reservation->persons = 2;
            $reservation->save();
            $this->reservationIdForTimeCancelFee = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForTimeCancelFee;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = '2';
            $reservationMenu->unit_price = '2500';
            $reservationMenu->price = $reservationMenu->count * $reservationMenu->unit_price;
            $reservationMenu->reservation_id = $this->reservationIdForTimeCancelFee;
            $reservationMenu->save();

            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $reservationMenu->id;
            $reservationOption->option_id = $option->id;
            $reservationOption->count = $reservationMenu->count;
            $reservationOption->unit_price = $option->price;
            $reservationOption->price = $reservationOption->count * $reservationOption->unit_price;
            $reservationOption->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'TIME';
            $cancelFee->cancel_limit = '1';
            $cancelFee->cancel_fee_unit = 'FIXED_RATE';
            $cancelFee->cancel_fee = '50';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = '3000';
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
            $this->cancelFeeIdForTimeCancelFee = $cancelFee->id;
        }

        // キャンセルポリシー両方（DAY/TIME)設定店舗への予約（来店前）
        // 定率、切り捨て、最低額設定あり
        // 予約オプションあり
        // 来店2日前：キャンセル料1000円
        // 来店1日前：キャンセル料1500円
        // 来店1時間：キャンセル料3000円
        // 来店12時間：キャンセル料2000円
        {
            $reservationDay = Carbon::now()->addDay(2)->format('Y-m-d H:i:s'); // 本日の2日後
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->save();

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = '150';
            $option->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = $reservationDay;
            $reservation->total = '5300';
            $reservation->persons = 2;
            $reservation->save();
            $this->reservationIdForDAYTimeCancelFee = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForDAYTimeCancelFee;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = '2';
            $reservationMenu->unit_price = '2500';
            $reservationMenu->price = $reservationMenu->count * $reservationMenu->unit_price;
            $reservationMenu->reservation_id = $this->reservationIdForDAYTimeCancelFee;
            $reservationMenu->save();

            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $reservationMenu->id;
            $reservationOption->option_id = $option->id;
            $reservationOption->count = $reservationMenu->count;
            $reservationOption->unit_price = $option->price;
            $reservationOption->price = $reservationOption->count * $reservationOption->unit_price;
            $reservationOption->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_limit = '2';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '5500';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
            $this->cancelFeeIdForDAYTimeCancelFee = $cancelFee->id;

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_limit = '1';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '1500';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'TIME';
            $cancelFee->cancel_limit = '1';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '3000';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'TIME';
            $cancelFee->cancel_limit = '12';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '2000';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
        }

        // キャンセルポリシー設定店舗への予約（来店前）
        // 定率、切り捨て、最低額設定あり
        // 予約オプションあり
        // 来店2日前：キャンセル料4000円
        {
            $reservationDay = Carbon::now()->addDay(2)->format('Y-m-d'); // 本日の2日後
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->save();

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = '1500';
            $option->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = $reservationDay;
            $reservation->total = '5000';
            $reservation->persons = 2;
            $reservation->save();
            $this->reservationIdForOther = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForOther;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = '2';
            $reservationMenu->unit_price = '1000';
            $reservationMenu->price = $reservationMenu->count * $reservationMenu->unit_price;
            $reservationMenu->reservation_id = $this->reservationIdForOther;
            $reservationMenu->save();

            $reservationOption = new ReservationOption();
            $reservationOption->reservation_menu_id = $reservationMenu->id;
            $reservationOption->option_id = $option->id;
            $reservationOption->count = $reservationMenu->count;
            $reservationOption->unit_price = $option->price;
            $reservationOption->price = $reservationOption->count * $reservationOption->unit_price;
            $reservationOption->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_limit = '2';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '4000';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
            $this->cancelFeeIdForOther = $cancelFee->id;
        }

        // キャンセルポリシー設定店舗への予約（来店前）
        // 定率、切り捨て、最低額設定あり
        // 予約オプションなし
        // 来店2日前：キャンセル料4000円
        {
            $reservationDay = Carbon::now()->addDay(2)->format('Y-m-d'); // 本日の2日後
            $store = new Store();
            $store->save();

            $menu = new Menu();
            $menu->store_id = $store->id;
            $menu->save();

            $option = new Option();
            $option->menu_id = $menu->id;
            $option->price = '1500';
            $option->save();

            $reservation = new Reservation();
            $reservation->app_cd = 'RS';
            $reservation->pick_up_datetime = $reservationDay;
            $reservation->total = '5000';
            $reservation->persons = 2;
            $reservation->save();
            $this->reservationIdForOther2 = $reservation->id;

            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->reservationIdForOther2;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = '2';
            $reservationMenu->unit_price = '1000';
            $reservationMenu->price = $reservationMenu->count * $reservationMenu->unit_price;
            $reservationMenu->reservation_id = $this->reservationIdForOther2;
            $reservationMenu->save();

            $cancelFee = new CancelFee();
            $cancelFee->store_id = $store->id;
            $cancelFee->app_cd = 'RS';
            $cancelFee->apply_term_from = '2022-09-01';
            $cancelFee->apply_term_to = '2999-09-30';
            $cancelFee->cancel_limit_unit = 'DAY';
            $cancelFee->cancel_limit = '2';
            $cancelFee->cancel_fee_unit = 'FLAT_RATE';
            $cancelFee->cancel_fee = '4000';
            $cancelFee->fraction_round = 'ROUND_DOWN';
            $cancelFee->cancel_fee_max = null;
            $cancelFee->cancel_fee_min = null;
            $cancelFee->visit = 'BEFORE';
            $cancelFee->published = 1;
            $cancelFee->save();
            $this->cancelFeeIdForOther2 = $cancelFee->id;
        }
    }
}
