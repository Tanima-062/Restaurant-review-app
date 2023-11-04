<?php

namespace Tests\Unit\Modules\Settlement;

use App\Models\CancelDetail;
use App\Models\Menu;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Modules\Settlement\AllAggregate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AllAggregateTest extends TestCase
{
    private $creatCount = 1;
    private $testStoreId;
    private $testMenuId;

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

    public function testStr()
    {
        $result = new AllAggregate(0, '202210', 3);

        // checkmethod :: termStr
        $this->assertSame('1日～15日', $result->termStr(1));
        $this->assertSame('16日～末日', $result->termStr(2));
        $this->assertSame('合計', $result->termStr(100));
        $this->assertSame('', $result->termStr(4));

        // checkmethod :: appCdStr
        $this->assertSame('レストラン', $result->appCdStr('RS'));
        $this->assertSame('テイクアウト', $result->appCdStr('TO'));
        $this->assertSame('合計', $result->appCdStr(''));
    }

    public function testAllAggregate()
    {
        // テストデータ１を作成(テスト店舗２つ、予約１件ずつ)
        $testSettlementCompanyId = $this->_createSettlementCompany('TAX_EXCLUDED');
        list($testStoreId, $testMenuId) = $this->_createStoreMenu($testSettlementCompanyId);
        $this->_createReservation($testStoreId, $testMenuId, 'TO', '2022-10-01', 'FIXED_RATE', '10.0', 1000, 100);
        list($testStoreId2, $testMenuId2) = $this->_createStoreMenu($testSettlementCompanyId);
        $this->_createReservation($testStoreId2, $testMenuId2, 'RS', '2022-10-01', 'FIXED_RATE', '10.0', 2000, 180);

        // テストデータ２を作成(テスト店舗1つ、予約2件)
        $testSettlementCompanyId2 = $this->_createSettlementCompany('TAX_INCLUDED');
        list($testStoreId3, $testMenuId3) = $this->_createStoreMenu($testSettlementCompanyId2);
        $this->_createReservation($testStoreId3, $testMenuId3, 'TO', '2022-10-01', 'FLAT_RATE', 1000, 1000, 100);
        $this->_createReservation($testStoreId3, $testMenuId3, 'TO', '2022-10-02', 'FLAT_RATE', 1000, 1000, 100);
        $this->_createReservation($testStoreId3, $testMenuId3, 'TO', '2022-10-21', 'FLAT_RATE', 1000, 1000, 100);

        // テストデータ３を作成
        // 店舗IDが0なので、対応する精算会社IDが取れず、集計対象外となる
        $this->_createReservation(0, 0, 'RS', '2022-10-21', 'FLAT_RATE', 1000, 500, 50);

        /**
         * 集計モジュールを呼び出し（テストデータ１を使用）
         * レストラン+テイクアウト合計の確認
         */
        $result = new AllAggregate($testSettlementCompanyId, '202210', 3);

        // 実行結果(レコード存在確認)
        $this->assertFalse(empty($result->allAggregate[1]));    // 1-15日までの集計レコードがある
        $this->assertFalse(empty($result->allAggregate[2]));    // 16-末日までの集計レコードがある
        // 集計結果(testStoreID分)
        {
            // テイクアウトの1-15日分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.TO.1";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId, $result->aggregate[$arrayKey]['settlement_company_id']);    // 精算会社ID
            $this->assertSame($testStoreId, $result->aggregate[$arrayKey]['shop_id']);                              // 店舗ID
            $this->assertSame('TO', $result->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(1, $result->aggregate[$arrayKey]['term']);                                            // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(2000.0, $result->aggregate[$arrayKey]['total']);                                      // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1000.0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                    // キャンセル明細合計
            $this->assertSame(909.09090909091, $result->aggregate[$arrayKey]['cancel_detail_price_main']);          // キャンセルメイン合計
            $this->assertSame(90.909090909091, $result->aggregate[$arrayKey]['cancel_detail_price_option']);        // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(200.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);                 // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // テイクアウトの16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.TO.2";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // テイクアウト分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.TO.S";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testStoreId, $result->aggregate[$arrayKey]['shop_id']);                              // 店舗ID
            $this->assertSame('TO', $result->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(100, $result->aggregate[$arrayKey]['term']);                                          // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(2000.0, $result->aggregate[$arrayKey]['total']);                                      // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1000.0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                    // キャンセル明細合計
            $this->assertSame(909.09090909091, $result->aggregate[$arrayKey]['cancel_detail_price_main']);          // キャンセルメイン合計
            $this->assertSame(90.909090909091, $result->aggregate[$arrayKey]['cancel_detail_price_option']);        // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(200.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);               // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // レストランの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.RS.1";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // レストラン分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.RS.2";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // レストラン分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.RS.S";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // 店舗分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId}.S.S";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testStoreId, $result->aggregate[$arrayKey]['shop_id']);                              // 店舗ID
            $this->assertSame('合計', $result->aggregate[$arrayKey]['app_cd']);                                      // app_cd
            $this->assertSame(1000, $result->aggregate[$arrayKey]['term']);                                         // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(2000.0, $result->aggregate[$arrayKey]['total']);                                      // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1000.0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                    // キャンセル明細合計
            $this->assertSame(909.09090909091, $result->aggregate[$arrayKey]['cancel_detail_price_main']);          // キャンセルメイン合計
            $this->assertSame(90.909090909091, $result->aggregate[$arrayKey]['cancel_detail_price_option']);        // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(200.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);               // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
        }
        // 集計結果(testStoreID2分)
        {
            // テイクアウトの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.TO.1";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // テイクアウト分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.TO.2";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // テイクアウト(なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.TO.S";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // レストランの1-15日分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.RS.1";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId, $result->aggregate[$arrayKey]['settlement_company_id']);    // 精算会社ID
            $this->assertSame($testStoreId2, $result->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('RS', $result->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(1, $result->aggregate[$arrayKey]['term']);                                            // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(3963.6363636364, $result->aggregate[$arrayKey]['total']);                             // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                         // キャンセル明細合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_main']);                        // キャンセルメイン合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_option']);                      // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(396.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);               // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // レストランの16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.RS.2";
            $this->assertArrayNotHasKey($arrayKey, $result->aggregate->toArray());
            // レストラン分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.RS.S";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId, $result->aggregate[$arrayKey]['settlement_company_id']);    // 精算会社ID
            $this->assertSame($testStoreId2, $result->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('RS', $result->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(100, $result->aggregate[$arrayKey]['term']);                                          // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(3963.6363636364, $result->aggregate[$arrayKey]['total']);                             // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                         // キャンセル明細合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_main']);                        // キャンセルメイン合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_option']);                      // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(396.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);               // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // 店舗分（あり）
            $arrayKey = "{$testSettlementCompanyId}.{$testStoreId2}.S.S";
            $this->assertArrayHasKey($arrayKey, $result->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId, $result->aggregate[$arrayKey]['settlement_company_id']);    // 精算会社ID
            $this->assertSame($testStoreId2, $result->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('合計', $result->aggregate[$arrayKey]['app_cd']);                                     // app_cd
            $this->assertSame(1000, $result->aggregate[$arrayKey]['term']);                                         // 集計期間（１は1-15日）
            $this->assertSame(1, $result->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(3963.6363636364, $result->aggregate[$arrayKey]['total']);                             // 金額
            $this->assertSame(10, $result->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_sum']);                         // キャンセル明細合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_main']);                        // キャンセルメイン合計
            $this->assertSame(0, $result->aggregate[$arrayKey]['cancel_detail_price_option']);                      // キャンセルオプション合計
            $this->assertSame(10.0, $result->aggregate[$arrayKey]['commission_rate_fixed']);                        // キャンセル手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat']);                            // キャンセル手数料（変動）
            $this->assertSame(396.0, $result->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);               // 販売手数料（固定）
            $this->assertSame(0, $result->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
        }
        // テスト用精算会社IDが全て、実行結果に含まれているか
        $this->assertTrue(in_array($testSettlementCompanyId, $result->allSettlementCompanyIds));
        $this->assertTrue(in_array($testSettlementCompanyId2, $result->allSettlementCompanyIds));
        // Module呼び出し時に指定した精算会社IDが取得できているか(指定していない精算会社は含まれていないか)
        $this->assertTrue(in_array($testSettlementCompanyId, $result->partSettlementCompanyIds));
        $this->assertFalse(in_array($testSettlementCompanyId2, $result->partSettlementCompanyIds));

        /**
         * 集計モジュール（1−15日分）を呼び出し（テストデータ2を使用）
         * 1-15日分の確認
         */
        $result2 = new AllAggregate($testSettlementCompanyId2, '202210', 1);

        // 実行結果(レコード存在確認)
        $this->assertFalse(empty($result2->allAggregate[1]));    // 1-15日までの集計レコードがある
        $this->assertTrue(empty($result2->allAggregate[2]));    // 16-末日までの集計レコードがない
        // 集計結果(testStoreID3分)
        {
            // テイクアウトの1-15日分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.1";
            $this->assertArrayHasKey($arrayKey, $result2->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result2->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result2->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('TO', $result2->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(1, $result2->aggregate[$arrayKey]['term']);                                            // 集計期間（１は1-15日）
            $this->assertSame(2, $result2->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(4400, $result2->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result2->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(2200, $result2->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(2000, $result2->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(200, $result2->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed']);                      // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(22000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);             // 販売手数料（固定）
            $this->assertSame(0, $result2->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // テイクアウト分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.2";
            $this->assertArrayNotHasKey($arrayKey, $result2->aggregate->toArray());
            // テイクアウト分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.S";
            $this->assertArrayHasKey($arrayKey, $result2->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result2->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result2->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('TO', $result2->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(100, $result2->aggregate[$arrayKey]['term']);                                          // 集計期間（１は1-15日）
            $this->assertSame(2, $result2->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(4400, $result2->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result2->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(2200, $result2->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(2000, $result2->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(200, $result2->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed']);                      // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(22000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);             // 販売手数料（固定）
            $this->assertSame(0, $result2->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // レストランの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.1";
            $this->assertArrayNotHasKey($arrayKey, $result2->aggregate->toArray());
            // レストラン分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.2";
            $this->assertArrayNotHasKey($arrayKey, $result2->aggregate->toArray());
            // レストラン分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.S";
            $this->assertArrayNotHasKey($arrayKey, $result2->aggregate->toArray());
            $this->assertArrayNotHasKey($arrayKey, $result2->aggregate->toArray());
            // 店舗分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.S.S";
            $this->assertArrayHasKey($arrayKey, $result2->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result2->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result2->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('合計', $result2->aggregate[$arrayKey]['app_cd']);                                      // app_cd
            $this->assertSame(1000, $result2->aggregate[$arrayKey]['term']);                                         // 集計期間（１は1-15日）
            $this->assertSame(2, $result2->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(4400, $result2->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result2->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(2200, $result2->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(2000, $result2->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(200, $result2->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed']);                      // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result2->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(22000.0, $result2->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);             // 販売手数料（固定）
            $this->assertSame(0, $result2->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
        }

        /**
         * 集計モジュール（16−末日分）を呼び出し（テストデータ2を使用）
         * 16-末日分の確認
         */
        $result3 = new AllAggregate($testSettlementCompanyId2, '202210', 2);
        // 実行結果(レコード存在確認)
        $this->assertTrue(empty($result3->allAggregate[1]));    // 1-15日までの集計レコードがない
        $this->assertFalse(empty($result3->allAggregate[2]));    // 16-末日までの集計レコードがある
        // 集計結果(testStoreID3分)
        {
            // テイクアウトの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.1";
            $this->assertArrayNotHasKey($arrayKey, $result3->aggregate->toArray());
            // テイクアウト分の16-末日分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.2";
            $this->assertArrayHasKey($arrayKey, $result3->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result3->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result3->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('TO', $result3->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(2, $result3->aggregate[$arrayKey]['term']);                                            // 集計期間（2は16−末日)
            $this->assertSame(1, $result3->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(2200, $result3->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result3->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1100, $result3->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(1000, $result3->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(100, $result3->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed']);                           // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result3->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);                   // 販売手数料（固定）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // テイクアウト分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.S";
            $this->assertArrayHasKey($arrayKey, $result3->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result3->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result3->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('TO', $result3->aggregate[$arrayKey]['app_cd']);                                       // app_cd
            $this->assertSame(100, $result3->aggregate[$arrayKey]['term']);                                          // 集計期間（2は16−末日)
            $this->assertSame(1, $result3->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(2200, $result3->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result3->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1100, $result3->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(1000, $result3->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(100, $result3->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed']);                           // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result3->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);                   // 販売手数料（固定）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
            // レストランの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.1";
            $this->assertArrayNotHasKey($arrayKey, $result3->aggregate->toArray());
            // レストラン分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.2";
            $this->assertArrayNotHasKey($arrayKey, $result3->aggregate->toArray());
            // レストラン分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.S";
            $this->assertArrayNotHasKey($arrayKey, $result3->aggregate->toArray());
            // 店舗分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.S.S";
            $this->assertArrayHasKey($arrayKey, $result3->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result3->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result3->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('合計', $result3->aggregate[$arrayKey]['app_cd']);                                      // app_cd
            $this->assertSame(1000, $result3->aggregate[$arrayKey]['term']);                                            // 集計期間（2は16−末日)
            $this->assertSame(1, $result3->aggregate[$arrayKey]['close_num']);                                    // 予約数
            $this->assertSame(2200, $result3->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result3->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(1100, $result3->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(1000, $result3->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(100, $result3->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed']);                           // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result3->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);                   // 販売手数料（固定）
            $this->assertSame(0, $result3->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
        }

        /**
         * 集計モジュール（全期間分）を呼び出し（テストデータ2を使用）
         * 1-15日と16-末日の合計を確認
         */
        $result4 = new AllAggregate($testSettlementCompanyId2, '202210', 3);
        // 実行結果(レコード存在確認)
        $this->assertFalse(empty($result4->allAggregate[1]));    // 1-15日までの集計レコードがある
        $this->assertFalse(empty($result4->allAggregate[2]));    // 16-末日までの集計レコードがある
        // 集計結果(testStoreID3分)
        {
            // テイクアウトの1-15日分（あり）
            // 詳細は上のテストで確認しているので割愛
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.1";
            $this->assertArrayHasKey($arrayKey, $result4->aggregate->toArray());
            // テイクアウト分の16-末日分（あり）
            // 詳細は上のテストで確認しているので割愛
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.2";
            $this->assertArrayHasKey($arrayKey, $result4->aggregate->toArray());
            // テイクアウト分（あり）
            // 詳細は上のテストで確認しているので割愛
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.TO.S";
            $this->assertArrayHasKey($arrayKey, $result4->aggregate->toArray());
            // レストランの1-15日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.1";
            $this->assertArrayNotHasKey($arrayKey, $result4->aggregate->toArray());
            // レストラン分の16-末日分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.2";
            $this->assertArrayNotHasKey($arrayKey, $result4->aggregate->toArray());
            // レストラン分（なし）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.RS.S";
            $this->assertArrayNotHasKey($arrayKey, $result4->aggregate->toArray());
            // 店舗分（あり）
            $arrayKey = "{$testSettlementCompanyId2}.{$testStoreId3}.S.S";
            $this->assertArrayHasKey($arrayKey, $result4->aggregate->toArray());
            $this->assertSame($testSettlementCompanyId2, $result4->aggregate[$arrayKey]['settlement_company_id']);   // 精算会社ID
            $this->assertSame($testStoreId3, $result4->aggregate[$arrayKey]['shop_id']);                             // 店舗ID
            $this->assertSame('合計', $result4->aggregate[$arrayKey]['app_cd']);                                      // app_cd
            $this->assertSame(1000, $result4->aggregate[$arrayKey]['term']);                                         // 集計期間（１は1-15日）
            $this->assertSame(3, $result4->aggregate[$arrayKey]['close_num']);                                       // 予約数
            $this->assertSame(6600, $result4->aggregate[$arrayKey]['total']);                                        // 金額
            $this->assertSame(10, $result4->aggregate[$arrayKey]['tax']);                                            // 消費税率？
            $this->assertSame(3300, $result4->aggregate[$arrayKey]['cancel_detail_price_sum']);                      // キャンセル明細合計
            $this->assertSame(3000, $result4->aggregate[$arrayKey]['cancel_detail_price_main']);                     // キャンセルメイン合計
            $this->assertSame(300, $result4->aggregate[$arrayKey]['cancel_detail_price_option']);                    // キャンセルオプション合計
            $this->assertSame(1000.0, $result4->aggregate[$arrayKey]['commission_rate_fixed']);                      // キャンセル手数料（固定）
            $this->assertSame(1000.0, $result4->aggregate[$arrayKey]['commission_rate_flat']);                       // キャンセル手数料（変動）
            $this->assertSame(44000.0, $result4->aggregate[$arrayKey]['commission_rate_fixed_fee_tax']);             // 販売手数料（固定）
            $this->assertSame(0, $result4->aggregate[$arrayKey]['commission_rate_flat_fee_tax']);                    // 販売手数料（変動）
        }

        /**
         * 集計モジュールを呼び出し
         * 期間指定の値が無効
         */
        $result5 = new AllAggregate(0, '202210', 4);
        $this->assertTrue(empty($result5->allAggregate[1]));
        $this->assertTrue(empty($result5->allAggregate[2]));
        $this->assertTrue(empty($result5->aggregate[1]));
        $this->assertTrue(empty($result5->aggregate[2]));
        $this->assertCount(0, $result5->allSettlementCompanyIds);
        $this->assertCount(0, $result5->partSettlementCompanyIds);
    }

    private function _createSettlementCompany($resultBaseAmount)
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社' . $this->creatCount;
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->result_base_amount = $resultBaseAmount;
        $settlementCompany->save();
        $this->creatCount++;
        return $settlementCompany->id;
    }

    private function _createStoreMenu($settlementCompanyId2)
    {
        $store = new Store();
        $store->settlement_company_id = $settlementCompanyId2;
        $store->save();

        $menu = new Menu;
        $menu->store_id = $this->testStoreId;
        $menu->save();
        return [$store->id, $this->testMenuId];
    }

    private function _createReservation($storeId, $menuId, $appCd, $pickUpDatetime, $accountingCondition, $commissionRate, $menuUnitPrice, $optionUnitPrice)
    {
        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->total = 2200;
        $reservation->tax = 10;
        $reservation->persons = 2;
        $reservation->accounting_condition = $accountingCondition;  // FIXED_RATE or FLAT_RATE
        $reservation->commission_rate = $commissionRate;
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->is_close = 1;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $storeId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->menu_id =  $menuId;
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->count = 2;
        $reservationMenu->unit_price = $menuUnitPrice;
        $reservationMenu->price = $menuUnitPrice * 2;
        $reservationMenu->save();

        $reservationOption = new ReservationOption();
        $reservationOption->reservation_menu_id = $reservationMenu->id;
        $reservationOption->count = 2;
        $reservationOption->unit_price = $optionUnitPrice;
        $reservationOption->price = $optionUnitPrice * 2;
        $reservationOption->save();

        // テイクアウトのテストデータにキャンセル明細をつける
        if ($appCd == 'TO') {
            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'MENU';
            $cancelDetail->price = '1000';
            $cancelDetail->count = '1';
            $cancelDetail->save();

            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->account_code = 'OKONOMI';
            $cancelDetail->price = '100';
            $cancelDetail->count = '1';
            $cancelDetail->save();
        }
    }
}
