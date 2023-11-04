<?php

namespace Tests\Unit\Modules\Settlement;

use App\Models\CallTrackers;
use App\Models\CallTrackerLogs;
use App\Models\CancelDetail;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use App\Models\Store;
use App\Modules\Settlement\ClientInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientInvoiceTest extends TestCase
{
    private $testSettlementCompanyId;
    private $testSettlementDownloadId;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        // テストデータ作成（成果基準額=税抜,手数料は定率）
        $this->_createClientInvoice();
        $settlementDownload = $this->_createSettlementDownload($this->testSettlementCompanyId, 1, 30, 'FIXED_RATE',  '10.0');
        $this->testSettlementDownloadId = $settlementDownload->id;
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testDeferredDesc()
    {
        // 月１締(1-末日)の場合は、メッセージは表示されない
        {
            $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202210');
            // 3000円未満の場合
            $clientInvoice->settlementAmount = 2999;
            $this->assertSame('', $clientInvoice->deferredDesc());
            // 3000円以上の場合
            $clientInvoice->settlementAmount = 3000;
            $this->assertSame('', $clientInvoice->deferredDesc());
        }

        // 前半締（1-15日)データの場合は、現在月で後半締データが発生する可能性がある場合のみメッセージを表示する
        {
            // 過去
            {
                $this->_createSettlementDownload($this->testSettlementCompanyId, 1, 15, 'FIXED_RATE',  '10.0', 3000, '202211');
                $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202211');
                // 3000円未満の場合
                $clientInvoice->settlementAmount = 2999;
                $this->assertSame('', $clientInvoice->deferredDesc());
                // 3000円以上の場合
                $clientInvoice->settlementAmount = 3000;
                $this->assertSame('', $clientInvoice->deferredDesc());
            }

            // 現在月
            {
                $now = new Carbon();
                $ym = $now->year.$now->month;

                $this->_createSettlementDownload($this->testSettlementCompanyId, 1, 15, 'FIXED_RATE',  '10.0', 3000, $ym);
                $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, $ym);
                // 3000円未満の場合
                $clientInvoice->settlementAmount = 2999;
                $this->assertSame('※3000円未満の場合は次期精算に繰り越しいたします。', $clientInvoice->deferredDesc());
                // 3000円以上の場合
                $clientInvoice->settlementAmount = 3000;
                $this->assertSame('', $clientInvoice->deferredDesc());
            }
        }

        // 後半締(16-末日)データの場合は、メッセージは表示されない
        {
            $this->_createSettlementDownload($this->testSettlementCompanyId, 16, 30, 'FIXED_RATE',  '10.0', 3000, '202212');
            $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202212');
            // 3000円未満の場合
            $clientInvoice->settlementAmount = 2999;
            $this->assertSame('', $clientInvoice->deferredDesc());
            // 3000円以上の場合
            $clientInvoice->settlementAmount = 3000;
            $this->assertSame('', $clientInvoice->deferredDesc());
        }
    }

    public function testFormatYm()
    {
        $settlementDownload = SettlementDownload::find($this->testSettlementDownloadId);
        $settlementDownload->start_term = '1';
        $settlementDownload->end_term = '15';
        $settlementDownload->save();

        $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202210');
        $this->assertSame('2022年 10 月 1日 〜 15日', $clientInvoice->formatYm());

        $settlementDownload = SettlementDownload::find($this->testSettlementDownloadId);
        $settlementDownload->start_term = '1';
        $settlementDownload->end_term = '30';
        $settlementDownload->save();

        $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202210');
        $this->assertSame('2022年 10 月 1日 〜 30日', $clientInvoice->formatYm());
    }

    public function testAccountTypeStr()
    {
        // 普通
        {
            $settlementCompany = SettlementCompany::find($this->testSettlementCompanyId);
            $settlementCompany->account_type = 'SAVINGS';
            $settlementCompany->save();

            $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202201');
            $this->assertSame('普通', $clientInvoice->accountTypeStr());
        }
        // 当座
        {
            $settlementCompany = SettlementCompany::find($this->testSettlementCompanyId);
            $settlementCompany->account_type = 'CURRENT';
            $settlementCompany->save();

            $clientInvoice = new ClientInvoice($this->testSettlementCompanyId, '202201');
            $this->assertSame('当座', $clientInvoice->accountTypeStr());
        }
    }

    public function testClientInvoice()
    {
        // テストデータ作成（成果基準額=税抜,手数料は定率）
        // 1~末日まで分
        $testSettlementCompanyId = $this->testSettlementCompanyId;
        $testSettlementDownloadId = $this->testSettlementDownloadId;
        $clientInvoice = new ClientInvoice($testSettlementCompanyId, '202210');
        $clientInvoice->agg();

        $this->assertSame(10, $clientInvoice->tax);
        $this->assertSame($testSettlementCompanyId, $clientInvoice->settlementCompany->id);
        $this->assertSame($testSettlementDownloadId, $clientInvoice->settlementDownload->id);
        $this->assertSame(720, $clientInvoice->onlySeatAmount);                     // 席のみ予約の手数料合計(4名×180円）
        $this->assertSame(4, $clientInvoice->onlySeatNumber);                       // 席のみ予約の人数
        $this->assertSame(700, $clientInvoice->telAmount);                          // 電話予約合計
        $this->assertSame(1562.0, $clientInvoice->invoiceAmount);                   // 請求合計（席のみ予約+電話予約の税込）
        $this->assertSame(9078.0, $clientInvoice->payAmount);                       // 支払合計(予約済み合計+キャンセル合計-手数料合計-手数料税額)
        $this->assertSame(3200, $clientInvoice->aggregateEnsureAmount);             // 予約済み合計(RS予約+TO予約)
        $this->assertSame(7000, $clientInvoice->aggregateCancelFeeAmount);          // キャンセル合計(RSキャンセル+TOキャンセル)
        $this->assertSame(1020, $clientInvoice->commissionAmount);                  // 手数料合計
        $this->assertSame(102.0, $clientInvoice->commissionTax);                    // 手数料にかかる税額
        $this->assertSame(142.0, $clientInvoice->otherCommissionTax);               // 席のみと電話の手数料合計にかかる税額
        $this->assertSame(0, $clientInvoice->deferredPrice);                        // 繰延金額
        $this->assertSame(7516.0, $clientInvoice->settlementAmount);                // 精算額（支払合計-請求額+繰延金額）

        // テストデータ作成（成果基準額=税抜,手数料は定額）
        $this->_createClientInvoice();
        $testSettlementCompanyId = $this->testSettlementCompanyId;
        $settlementDownload = $this->_createSettlementDownload($testSettlementCompanyId, 16, 30, 'FLAT_RATE', '1000');
        $testSettlementDownloadId = $settlementDownload->id;
        $settlementDownload2 = $this->_createSettlementDownload($testSettlementCompanyId, 1, 15, 'FLAT_RATE', '1000', 2000); //1-15日分繰越確認用テストデータ
        $clientInvoice2 = new ClientInvoice($testSettlementCompanyId, '202210');
        $clientInvoice2->agg();

        $this->assertSame(10, $clientInvoice->tax);
        $this->assertSame($testSettlementCompanyId, $clientInvoice2->settlementCompany->id);
        $this->assertSame($testSettlementDownloadId, $clientInvoice2->settlementDownload->id);
        $this->assertSame(0, $clientInvoice2->onlySeatAmount);                      // 席のみ予約の手数料合計
        $this->assertSame(0, $clientInvoice2->onlySeatNumber);                      // 席のみ予約の人数
        $this->assertSame(700, $clientInvoice2->telAmount);                         // 電話予約合計
        $this->assertSame(770.0, $clientInvoice2->invoiceAmount);                   // 請求合計
        $this->assertSame(9000.0, $clientInvoice2->payAmount);                      // 支払合計(予約済み合計+キャンセル合計-手数料合計-手数料税額)
        $this->assertSame(2000, $clientInvoice2->aggregateEnsureAmount);            // 予約済み合計(RS予約+TO予約)
        $this->assertSame(7000, $clientInvoice2->aggregateCancelFeeAmount);         // キャンセル合計(RSキャンセル+TOキャンセル)
        $this->assertSame(0, $clientInvoice2->commissionAmount);                    // 手数料合計
        $this->assertSame(0.0, $clientInvoice2->commissionTax);                     // 手数料にかかる税額
        $this->assertSame(70.0, $clientInvoice2->otherCommissionTax);               // 席のみと電話の手数料合計にかかる税額
        $this->assertSame(2000, $clientInvoice2->deferredPrice);                    // 繰延金額(1-15日分)
        $this->assertSame(10230.0, $clientInvoice2->settlementAmount);              // 精算額（支払合計-請求額+繰延金額）

        // テストデータ作成（成果基準額=税込,手数料は固定）
        // 1~15日まで分
        $this->_createClientInvoice('TAX_INCLUDED');
        $testSettlementCompanyId = $this->testSettlementCompanyId;
        $settlementDownloadArray = [
            'startTerm' => 1,
            'endTerm' => 15,
            'accountingConditionRS' => 'FLAT_RATE',
            'commissionRateRS' => 1000,
            'accountingConditionTO' => 'FLAT_RATE',
            'commissionRateTO' => 180,
        ];
        $clientInvoice3 = new ClientInvoice($testSettlementCompanyId, '202210', $settlementDownloadArray);
        $clientInvoice3->agg();
        $this->assertSame(10, $clientInvoice3->tax);
        $this->assertSame($testSettlementCompanyId, $clientInvoice3->settlementCompany->id);
        $this->assertNull($clientInvoice3->settlementDownload);
        $this->assertSame(720, $clientInvoice3->onlySeatAmount);                 // 席のみ予約の手数料合計(4名×180円）
        $this->assertSame(4, $clientInvoice3->onlySeatNumber);                   // 席のみ予約の人数
        $this->assertSame(700, $clientInvoice3->telAmount);                      // 電話予約合計
        $this->assertSame(1562.0, $clientInvoice3->invoiceAmount);               // 請求合計（席のみ予約+電話予約の税込）
        $this->assertSame(1200.0, $clientInvoice3->payAmount);                   // 支払合計(予約済み合計+キャンセル合計-手数料合計-手数料税額)
        $this->assertSame(1200, $clientInvoice3->aggregateEnsureAmount);         // 予約済み合計(RS予約+TO予約)
        $this->assertSame(0, $clientInvoice3->aggregateCancelFeeAmount);         // キャンセル合計(RSキャンセル+TOキャンセル)
        $this->assertSame(0, $clientInvoice3->commissionAmount);                 // 手数料合計
        $this->assertSame(0.0, $clientInvoice3->commissionTax);                  // 手数料にかかる税額
        $this->assertSame(142.0, $clientInvoice3->otherCommissionTax);           // 席のみと電話の手数料合計にかかる税額
        $this->assertSame(0, $clientInvoice3->deferredPrice);                    // 繰延金額
        $this->assertSame(-362.0, $clientInvoice3->settlementAmount);            // 精算額（支払合計-請求額+繰延金額）

        // 16~末日まで分(手数料は定額)
        $settlementDownloadArray = [
            'startTerm' => 16,
            'endTerm' => 30,
            'accountingConditionRS' => 'FIXED_RATE',
            'commissionRateRS' => 10.0,
            'accountingConditionTO' => 'FIXED_RATE',
            'commissionRateTO' => 10.0,
        ];
        $clientInvoice4 = new ClientInvoice($testSettlementCompanyId, '202210', $settlementDownloadArray);
        $clientInvoice4->agg();
        $this->assertSame(0, $clientInvoice4->onlySeatAmount);                   // 席のみ予約の手数料合計
        $this->assertSame(0, $clientInvoice4->onlySeatNumber);                   // 席のみ予約の人数
        $this->assertSame(700, $clientInvoice4->telAmount);                      // 電話予約合計
        $this->assertSame(770.0, $clientInvoice4->invoiceAmount);                // 請求合計
        $this->assertSame(8188.0, $clientInvoice4->payAmount);                   // 支払合計(予約済み合計+キャンセル合計-手数料合計-手数料税額)
        $this->assertSame(2000, $clientInvoice4->aggregateEnsureAmount);         // 予約済み合計(RS予約+TO予約)
        $this->assertSame(7200, $clientInvoice4->aggregateCancelFeeAmount);      // キャンセル合計(RSキャンセル+TOキャンセル)
        $this->assertSame(920, $clientInvoice4->commissionAmount);               // 手数料合計
        $this->assertSame(92.0, $clientInvoice4->commissionTax);                 // 手数料にかかる税額
        $this->assertSame(70.0, $clientInvoice4->otherCommissionTax);            // 席のみと電話の手数料合計にかかる税額
        $this->assertSame(0, $clientInvoice4->deferredPrice);                    // 繰延金額
        $this->assertSame(7418.0, $clientInvoice4->settlementAmount);            // 精算額（支払合計-請求額+繰延金額）
    }

    private function _createClientInvoice($resultBaseAmount = 'TAX_EXCLUDED')
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->result_base_amount = $resultBaseAmount;
        $settlementCompany->account_type = 'SAVINGS';
        $settlementCompany->save();
        $this->testSettlementCompanyId = $settlementCompany->id;

        $store = new Store();
        $store->name = 'グルメtestテスト店舗';
        $store->settlement_company_id = $this->testSettlementCompanyId;
        $store->save();
        $this->testStoreId = $store->id;

        $menu = new Menu();
        $menu->store_id = $this->testStoreId;
        $menu->app_cd = 'TO';
        $menu->save();
        $takeoutMenuId = $menu->id;

        $menu = new Menu();
        $menu->store_id = $this->testStoreId;
        $menu->app_cd = 'RS';
        $menu->save();
        $RestaurantMenuId = $menu->id;

        // メニューが税込の場合
        if ($resultBaseAmount == 'TAX_EXCLUDED') {
            // 予約成立データ
            {
                // テイクアウト
                $this->_createTakeoutReservation($takeoutMenuId, '01', 'ENSURE', 'AUTH', 550, 110);  // 税抜600円、合計660×2＝1320
                // レストラン
                $this->_createRestaurantReservation($RestaurantMenuId, '20', 'ENSURE', 'AUTH', 1100); // 税抜1000円、合計1100×2＝2200
                $this->_createRestaurantReservation($RestaurantMenuId, '01', 'ENSURE', 'UNPAID', 0);    // 合計0（席のみ）
                $this->_createRestaurantReservation($RestaurantMenuId, '15', 'ENSURE', 'UNPAID', 0);    // 合計0（席のみ）
            }
            // 予約キャンセルデータ
            {
                // テイクアウト
                $this->_createTakeoutReservation($takeoutMenuId, '17', 'CANCEL', 'CANCEL', 990, 110);  // 税抜1000円、合計1100×2=2200
                // レストラン
                $this->_createRestaurantReservation($RestaurantMenuId, '20', 'CANCEL', 'CANCEL', 2750); // 合計2750×2＝5500
                $this->_createRestaurantReservation($RestaurantMenuId, '01', 'CANCEL', 'CANCEL', 0);    // 合計0（席のみ）
            }
        } else {
            // 予約成立データ
            {
                // テイクアウト
                $this->_createTakeoutReservation($takeoutMenuId, '01', 'ENSURE', 'AUTH', 500, 100);  // 合計1200
                // レストラン
                $this->_createRestaurantReservation($RestaurantMenuId, '20', 'ENSURE', 'AUTH', 1000); // 合計2000
                $this->_createRestaurantReservation($RestaurantMenuId, '01', 'ENSURE', 'UNPAID', 0);    // 合計0（席のみ）
                $this->_createRestaurantReservation($RestaurantMenuId, '15', 'ENSURE', 'UNPAID', 0);    // 合計0（席のみ）
            }
            // 予約キャンセルデータ
            {
                // テイクアウト
                $this->_createTakeoutReservation($takeoutMenuId, '17', 'CANCEL', 'CANCEL', 1000, 100);  // 合計2200
                // レストラン
                $this->_createRestaurantReservation($RestaurantMenuId, '20', 'CANCEL', 'CANCEL', 2500); // 合計5000
                $this->_createRestaurantReservation($RestaurantMenuId, '01', 'CANCEL', 'CANCEL', 0);    // 合計0（席のみ）
            }
        }

        // 電話予約
        $callTrackers = new CallTrackers();
        $callTrackers->store_id = $this->testStoreId;
        $callTrackers->advertiser_id = "clientInvoiceTest{$this->testStoreId}";
        $callTrackers->save();

        $callTrackerLogs = new CallTrackerLogs();
        $callTrackerLogs->valid_status = 1;
        $callTrackerLogs->call_secs = 60;       // 通話時間60秒
        $callTrackerLogs->client_id = $callTrackers->advertiser_id;
        $callTrackerLogs->created_at = '2022-10-01 12:00:00';
        $callTrackerLogs->save();
    }

    private function _createSettlementDownload($settlementCompanyId, $startTerm, $endTerm, $accountingCondition, $commissionRate, $deferredPrice = 0, $month='202210')
    {
        $settlementDownload = new SettlementDownload();
        $settlementDownload->settlement_company_id = $settlementCompanyId;
        $settlementDownload->month = $month;
        $settlementDownload->start_term = $startTerm;
        $settlementDownload->end_term = $endTerm;
        $settlementDownload->commission_rate_rs = $commissionRate;
        $settlementDownload->accounting_condition_rs = $accountingCondition;
        $settlementDownload->commission_rate_to = $commissionRate;
        $settlementDownload->accounting_condition_to = $accountingCondition;
        $settlementDownload->deferred_price = $deferredPrice;
        $settlementDownload->save();
        return $settlementDownload;
    }

    private function _createTakeoutReservation($takeoutMenuId, $reservationDay, $reservationStatus, $paymentStatus, $unitPrice, $optionUnitPrice)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->reservation_status = $reservationStatus;
        $reservation->payment_status = $paymentStatus;
        $reservation->payment_method = 'CREDIT';
        $reservation->tax = 10;
        $reservation->total = ($unitPrice * 2) + ($optionUnitPrice * 2);
        $reservation->commission_rate = 10.0;
        $reservation->tel = '0356785678';
        $reservation->created_at = '2022-10-01 12:00:00';
        $reservation->pick_up_datetime = '2022-10-' . $reservationDay . ' 15:00:00';
        $reservation->is_close = 1;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $this->testStoreId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id =  $takeoutMenuId;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->count = 2;
        $reservationMenu->unit_price = $unitPrice;
        $reservationMenu->price = $unitPrice * 2;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->count = 2;
        $reservationOption->unit_price = $optionUnitPrice;
        $reservationOption->price = $optionUnitPrice * 2;
        $reservationOption->save();

        if ($reservationStatus == 'CANCEL') {
            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'MENU';
            $cancelDetail->price = $unitPrice;
            $cancelDetail->count = 2;
            $cancelDetail->save();

            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'TOPPING';
            $cancelDetail->price = $optionUnitPrice;
            $cancelDetail->count = 2;
            $cancelDetail->save();

            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'TEST';    // ['MENU', 'OKONOMI', 'TOPPING']以外は0円として処理される
            $cancelDetail->price = 0;
            $cancelDetail->count = 2;
            $cancelDetail->save();
        }
    }

    private function _createRestaurantReservation($RestaurantMenuId, $reservationDay, $reservationStatus, $paymentStatus, $unitPrice)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->email = 'gourmet-test@adventure-inc.co.jp';
        $reservation->reservation_status = $reservationStatus;
        $reservation->payment_status = $paymentStatus;
        $reservation->payment_method = 'CREDIT';
        $reservation->tax = 10;
        $reservation->total = $unitPrice * 2;
        $reservation->commission_rate = 10.0;
        $reservation->tel = '0356785678';
        $reservation->created_at = '2022-10-01 12:00:00';
        $reservation->pick_up_datetime = '2022-10-' . $reservationDay . ' 15:00:00';
        $reservation->cancel_datetime = null;
        $reservation->is_close = 1;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $this->testStoreId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id =  $RestaurantMenuId;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->count = 2;
        $reservationMenu->unit_price = $unitPrice;
        $reservationMenu->price = $unitPrice * 2;
        $reservationMenu->save();

        if ($reservationStatus == 'CANCEL' && ($unitPrice * 2) > 0) {
            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'MENU';
            $cancelDetail->price = $unitPrice;
            $cancelDetail->count = 2;
            $cancelDetail->save();
        }
    }
}
