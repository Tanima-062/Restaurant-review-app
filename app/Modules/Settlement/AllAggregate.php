<?php

namespace App\Modules\Settlement;

use App\Models\Reservation;
use Carbon\Carbon;

class AllAggregate
{
    const ALL_TERM = 3;        // 全期間
    const FIRST_TERM = 1;      // 1-15日
    const SECOND_TERM = 2;     // 16-末日
    const SUM_TERM = 100;      // 期間合計を出力したいとき用
    const SUM_APP = 1000;      // サービス合計を出力したいとき用
    const ALL_SETTLEMENT = 0;  // 全精算管理会社
    const PART_SETTLEMENT = 1; // 選択された精算管理会社

    public $allAggregate = null;
    public $aggregate = null;
    public $allSettlementCompanyIds = [];
    public $partSettlementCompanyIds = [];
    private $partSettlementCompanyId;
    private $year;
    private $month;
    private $termFlag;

    function __construct($partSettlementCompanyId = 0, $yyyymm = '', $termFlag = 0)
    {
        $this->partSettlementCompanyId = $partSettlementCompanyId;

        $this->year = (int)substr($yyyymm, 0, 4);
        $this->month = (int)substr($yyyymm, 4, 2);
        $this->termFlag = $termFlag;

        $this->agg();
    }

    private function agg()
    {
        $endTerm = Carbon::create($this->year, $this->month, 1)->lastOfMonth()->day;
        if ($this->termFlag == self::ALL_TERM) {
            $termReservations = [
                self::FIRST_TERM => $this->aggregateReservation(1, 15),
                self::SECOND_TERM => $this->aggregateReservation(16, $endTerm)
            ];
        } elseif ($this->termFlag == self::FIRST_TERM) {
            $termReservations = [
                self::FIRST_TERM => $this->aggregateReservation(1, 15)
            ];
        } elseif ($this->termFlag == self::SECOND_TERM) {
            $termReservations = [
                self::SECOND_TERM => $this->aggregateReservation(16, $endTerm)
            ];
        } else {
            $termReservations = [];
        }

        $this->allAggregate = $this->groupByRow($termReservations, self::ALL_SETTLEMENT);

        $partTermReservations = [];
        foreach ($termReservations as $term => $reservations) {
            if ($this->partSettlementCompanyId > 0) {
                $partTermReservations[$term] =
                    $reservations->where('reservationStore.store.settlement_company_id', $this->partSettlementCompanyId);
            } else {
                $partTermReservations[$term] = $reservations;
            }
        }

        $this->aggregate = $this->groupByRow($partTermReservations, self::PART_SETTLEMENT);
    }

    /**
     * 特定の期間の成約の予約データを抽出する
     * @param int $first
     * @param int $last
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function aggregateReservation(int $first, int $last)
    {
        $startDatetime = Carbon::create($this->year, $this->month, $first, 0, 0, 0)->format('Y-m-d H:i:s');
        $endDatetime = Carbon::create($this->year, $this->month, $last, 23, 59, 59)->format('Y-m-d H:i:s');

        // 精算月から対象になる予約を抽出する
        return $query = Reservation::with(['reservationMenus.reservationOptions', 'reservationStore.store.settlementCompany', 'cancelDetails'])
            ->whereBetween('pick_up_datetime', [$startDatetime, $endDatetime])
            ->where('is_close', 1)->get();
    }

    public function termStr(int $term)
    {
        switch ($term) {
            case self::FIRST_TERM:
                return '1日～15日';
            case self::SECOND_TERM:
                return '16日～末日';
            case self::SUM_TERM:
                return '合計';
            default :
                return '';
        }
    }

    public function appCdStr(string $appCd)
    {
        if ($appCd == 'RS') {
            return 'レストラン';
        } elseif ($appCd == 'TO') {
            return 'テイクアウト';
        } else {
            return '合計';
        }
    }

    private function groupByRow($termReservations, $status)
    {
        $result = collect();
        $result2 = collect();
        $result3 = collect();
        $result4 = collect();
        // 予約データを店舗単位にする(1行に精算会社、店舗、サービス、期間となるようにする)
        foreach ($termReservations as $term => $reservations) {
            foreach ($reservations as $reservation) {
                $shopId = $reservation->reservationStore->store_id;
                $appCd = $reservation->app_cd;
                if (empty($reservation->reservationStore->store->settlementCompany->id)) {
                    continue;
                }
                $settlementCompanyId = $reservation->reservationStore->store->settlementCompany->id;
                // 画面表示用(検索条件の精算管理会社一覧)
                $this->allSettlementCompanyIds[] = $settlementCompanyId;

                if ($status == self::PART_SETTLEMENT) {
                    // 期間単位のレコード
                    $key = sprintf("%d.%d.%s.%d", $settlementCompanyId, $shopId, $appCd, $term);
                    $this->collectRecord($result, $key, $shopId, $appCd, $term, $reservation);

                    // サービス合計のレコード
                    $appKey = sprintf("%d.%d.%s.S", $settlementCompanyId, $shopId, $appCd);
                    $this->collectRecord($result2, $appKey, $shopId, $appCd, self::SUM_TERM, $reservation);

                    // 店舗合計のレコード
                    $shopKey = sprintf("%d.%d.S.S", $settlementCompanyId, $shopId);
                    $this->collectRecord($result3, $shopKey, $shopId, '合計', self::SUM_APP, $reservation);

                    // 画面表示用(ページング用)
                    $this->partSettlementCompanyIds[] = $settlementCompanyId;
                    $this->partSettlementCompanyIds = array_values(array_unique($this->partSettlementCompanyIds));
                    asort($this->partSettlementCompanyIds);
                } else {
                    // 全精算管理会社の期間別レコード
                    $this->collectRecord($result4, $term, $shopId, $appCd, $term, $reservation);
                }
            }
        }

        if ($status == self::PART_SETTLEMENT) {
            $merged = $result->merge($result2)->merge($result3);
        } else {
            $merged = $result4;
        }

        return $merged->sort(function ($a, $b) {
            if ($a['settlement_company_id'] == $b['settlement_company_id']) {
                if ($a['shop_id'] == $b['shop_id']) {
                    if ($a['app_cd'] == $b['app_cd']) {
                        if ($a['term'] == $b['term']) {
                            return 0;
                        }

                        return ($a['term'] < $b['term']) ? -1 : 1;
                    }

                    return ($a['app_cd'] < $b['app_cd']) ? -1 : 1;
                }

                return ($a['shop_id'] < $b['shop_id']) ? -1 : 1;
            }

            return ($a['settlement_company_id'] < $b['settlement_company_id']) ? -1 : 1;
        });
    }

    /**
     * 税金分を引く(メニューに税込みで登録されているため)
     * @param $reservation
     * @return float
     */
    private function decreaseTax($reservation)
    {
        $total = 0;
        if ($reservation->reservationStore->store->settlementCompany->result_base_amount == 'TAX_EXCLUDED') {
            foreach ($reservation->reservationMenus as $reservationMenu) {
                $total = $total + $reservationMenu->price - ($reservationMenu->price * $reservation->tax / (100 + $reservation->tax));
                foreach ($reservationMenu->reservationOptions as $reservationOption) {
                    $total = $total + $reservationOption->price - ($reservationOption->price * $reservation->tax / (100 + $reservation->tax));
                }
            }
        } else {
            $total += $reservation->total;
        }

        return $total;
    }

    private function decreaseTaxCancelDetail($cancelDetail, $reservation)
    {
        $cancelDetailSum = $cancelDetail['price'] * $cancelDetail['count'];
        if ($reservation->reservationStore->store->settlementCompany->result_base_amount == 'TAX_EXCLUDED') {
            return $cancelDetailSum - ($cancelDetailSum * $reservation->tax / (100 + $reservation->tax));
        } else {
            return $cancelDetailSum;
        }
    }

    private function collectRecord(&$result, $key, $shopId, $appCd, $term, $reservation)
    {
        $total = $this->decreaseTax($reservation);
        if (!$result->has($key)) { // create
            $data = [
                'settlement_company_id' => $reservation->reservationStore->store->settlementCompany->id,        // 精算管理会社id
                'settlement_company_name' => $reservation->reservationStore->store->settlementCompany->name,    // 精算管理会社名
                'shop_id' => $shopId,                                                                           // 店舗id
                'shop_name' => $reservation->reservationStore->name,                                            // 店舗名
                'app_cd' => $appCd,                                                                             // 利用サービス
                'app_name' => '',
                'term' => $term,                                                                                // 対象期間
                'close_num' => (is_null($reservation->cancel_datetime) && $total > 0) ? 1 : 0,     // 成約件数(事前決済)
                'total' => $total,                                                                 // 成約金額(事前決済)
                'local_num' => (is_null($reservation->cancel_datetime) && $total == 0) ? 1 : 0,    // 成約件数(現地決済)
                'local_people' => (is_null($reservation->cancel_datetime) && $total == 0) ? $reservation->persons : 0, // 成約人数(現地決済)
                'cancel_num' => (!is_null($reservation->cancel_datetime)) ? 1 : 0,                              // ｷｬﾝｾﾙ件数
                'cancel_detail_price_main' => $reservation->cancelDetails->sum(function ($cancelDetail) use ($reservation) {       // ｷｬﾝｾﾙ料(ﾒｲﾝ)
                    if ($cancelDetail['account_code'] == 'MENU') {
                        return $this->decreaseTaxCancelDetail($cancelDetail, $reservation);
                    }

                    return 0;
                }),
                'cancel_detail_price_option' => $reservation->cancelDetails->sum(function ($cancelDetail) use ($reservation) {     // ｷｬﾝｾﾙ料(ｵﾌﾟｼｮﾝ)
                    if ($cancelDetail['account_code'] == 'OKONOMI' || $cancelDetail['account_code'] == 'TOPPING') {
                        return $this->decreaseTaxCancelDetail($cancelDetail, $reservation);
                    }

                    return 0;
                }),
                'commission_rate_fixed' => ($reservation->accounting_condition == 'FIXED_RATE') ? $reservation->commission_rate : 0, // 販売手数料(率)
                'commission_rate_flat' => ($reservation->accounting_condition == 'FLAT_RATE') ? $reservation->commission_rate : 0, // 販売手数料(定額)
                'tax' => $reservation->tax, // 税金
                'v_settle' => true,
                'v_shop' => true,
                'v_app_cd' => true,
                'v_term' => true,
            ];

            $data['cancel_detail_price_sum'] = $data['cancel_detail_price_main'] + $data['cancel_detail_price_option']; // ｷｬﾝｾﾙ料合計(全体)

            // 初期化
            $data['commission_rate_fixed_fee_tax'] = 0;
            $data['commission_rate_flat_fee_tax'] = 0;

            // 販売手数料 税込
            if ($reservation->accounting_condition == 'FIXED_RATE') { // 定率
                $data['commission_rate_fixed_fee_tax'] = floor($data['total'] * ((float)$data['commission_rate_fixed'] / 100.0));
            } else {
                $data['commission_rate_flat_fee_tax'] = (int)$data['commission_rate_flat'] * $data['local_people']; // 定額
            }

            $result->put($key, $data);
        } else { // update
            $tmpData = $result->pull($key);
            if (is_null($reservation->cancel_datetime) && $reservation->total > 0) { // 事前決済 - 成約
                $tmpData['close_num']++;
                if (empty($tmpData['commission_rate_fixed'])) {
                    $tmpData['commission_rate_fixed'] = $reservation->commission_rate;
                }

                $tmpData['commission_rate_fixed_fee_tax'] = floor($tmpData['commission_rate_fixed_fee_tax'] + $reservation->total * ((float)$tmpData['commission_rate_fixed'] / 100.0)); // 販売手数料 - 定率のはず
            } elseif (is_null($reservation->cancel_datetime) && $reservation->total == 0) { // 現地決済 - 成約
                $tmpData['local_num']++;
                $tmpData['local_people'] = $tmpData['local_people'] + $reservation->persons;
                if (empty($tmpData['commission_rate_flat'])) {
                    $tmpData['commission_rate_flat'] = $reservation->commission_rate;
                }

                $tmpData['commission_rate_flat_fee_tax'] = $tmpData['commission_rate_flat_fee_tax'] + (int)$tmpData['commission_rate_flat'] * $tmpData['local_people']; // 販売手数料 - 定額のはず
            } elseif (!is_null($reservation->cancel_datetime)) { // 事前決済 - キャンセル
                $tmpData['cancel_num']++;
            }

            $tmpData['total'] = $total + $tmpData['total'];

            $tmpData['cancel_detail_price_main'] = $tmpData['cancel_detail_price_main'] + $reservation->cancelDetails->sum(function ($cancelDetail) use ($reservation) {
                if ($cancelDetail['account_code'] == 'MENU') {
                    return $this->decreaseTaxCancelDetail($cancelDetail, $reservation);
                }

                return 0;
            });

            $tmpData['cancel_detail_price_option'] = $tmpData['cancel_detail_price_option'] + $reservation->cancelDetails->sum(function ($cancelDetail) use ($reservation) {
                if ($cancelDetail['account_code'] == 'OKONOMI' || $cancelDetail['account_code'] == 'TOPPING') {
                    return $this->decreaseTaxCancelDetail($cancelDetail, $reservation);
                }

                return 0;
            });

            $tmpData['cancel_detail_price_sum'] = $tmpData['cancel_detail_price_main'] + $tmpData['cancel_detail_price_option'];

            $result->put($key, $tmpData);
        }
    }
}
