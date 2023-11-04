<?php

namespace App\Modules\Settlement;

use App\Libs\ConsumptionTax;
use App\Models\CancelDetail;
use App\Models\Reservation;
use App\Models\SettlementCompany;
use App\Models\SettlementDownload;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Log;

class ClientInvoice
{
    const ONLY_SEAT_FEE = 180;
    const DEFERRED_PRICE = 3000;

    private $cancelFeeCodes = ['MENU', 'OKONOMI', 'TOPPING'];
    private $year;
    private $month;
    private $startTerm;              // 期間（開始）
    private $endTerm;
    private $accountingConditionRS; // レストラン計上単位
    private $accountingConditionTO; // テイクアウト計上単位
    private $commissionRS;
    private $commissionTO;
    private $statusEnsure; // 受注確定(予約ステータス)
    private $statusCancel; // キャンセル(予約ステータス)
    private $reservations; // 精算範囲の予約データ

    public $tax;          // 税率(%)
    public $settlementCompany = null;
    public $settlementDownload = null;
    public $onlySeatAmount = 0;              // 席のみ手数料合計
    public $onlySeatNumber = 0;              // 席のみ人数合計
    public $telAmount = 0;                   // 電話予約合計
    public $invoiceAmount = 0;               // 請求合計
    public $payAmount = 0;                   // 支払合計
    public $aggregateEnsureAmount = 0;       // 成約金額合計
    public $aggregateCancelFeeAmount = 0;    // キャンセル料金額合計
    public $commissionAmount = 0;            // 手数料合計
    public $commissionTax = 0;               // 手数料にかかる税額
    public $otherCommissionTax = 0;          // 席のみと電話の手数料合計にかかる税額
    public $deferredPrice = 0;               // 繰延金額
    public $settlementAmount = 0;            // 精算額

    public $ensureDetails = [];              // 成約明細リスト
    public $cancelDetails = [];              // キャンセル料明細リスト
    public $onlySeatDetails = [];            // 席のみ料明細リスト
    public $callDetails = [];                // 電話予約明細リスト

    public function __construct(int $settlementCompanyId, int $yyyymm, array $settlementDownload = [])
    {
        $this->statusEnsure = config('code.reservationStatus.ensure.key');
        $this->statusCancel = config('code.reservationStatus.cancel.key');

        $this->settlementCompany = SettlementCompany::find($settlementCompanyId);
        $this->year = substr($yyyymm, 0, 4);
        $this->month = substr($yyyymm, 4, 2);
        $this->settlementDownload = SettlementDownload::where('settlement_company_id', $settlementCompanyId)
            ->where('month', $yyyymm)->first();
        if ($this->settlementDownload) {
            $this->startTerm = $this->settlementDownload->start_term;
            $this->endTerm = $this->settlementDownload->end_term;
            $this->accountingConditionRS = $this->settlementDownload->accounting_condition_rs;
            if ($this->settlementDownload->accounting_condition_rs == 'FIXED_RATE') { // 定率(RS)
                $this->commissionRS = $this->settlementDownload->commission_rate_rs * 0.01;
            } else { // 定額(RS)
                $this->commissionRS = $this->settlementDownload->commission_rate_rs;
            }

            $this->accountingConditionTO = $this->settlementDownload->accounting_condition_to;
            if ($this->settlementDownload->accounting_condition_to == 'FIXED_RATE') { // 定率(TO)
                $this->commissionTO = $this->settlementDownload->commission_rate_to * 0.01;
            } else { // 定額(TO)
                $this->commissionTO = $this->settlementDownload->commission_rate_to;
            }
        } else {
            $this->startTerm = isset($settlementDownload['startTerm']) ? $settlementDownload['startTerm'] : '';
            $this->endTerm = isset($settlementDownload['endTerm']) ? $settlementDownload['endTerm'] : '';
            if (isset($settlementDownload['commissionRateRS']) && isset($settlementDownload['accountingConditionRS'])) {
                $this->accountingConditionRS = $settlementDownload['accountingConditionRS'];
                if ($settlementDownload['accountingConditionRS'] == 'FIXED_RATE') { // 定率(RS)
                    $this->commissionRS = $settlementDownload['commissionRateRS'] * 0.01;
                } else { // 定額(RS)
                    $this->commissionRS = $settlementDownload['commissionRateRS'];
                }
            }

            if (isset($settlementDownload['commissionRateTO']) && isset($settlementDownload['accountingConditionTO'])) {
                $this->accountingConditionTO = $settlementDownload['accountingConditionTO'];
                if ($settlementDownload['accountingConditionTO'] == 'FIXED_RATE') { // 定率(TO)
                    $this->commissionTO = $settlementDownload['commissionRateTO'] * 0.01;
                } else { // 定額(TO)
                    $this->commissionTO = $settlementDownload['commissionRateTO'];
                }
            }
        }
    }

    public function agg() {
        $this->tax = ConsumptionTax::calc(date('Ym'));
        $this->reservations = $this->aggregateReservation();

        $this->aggregateEnsureAmount = $this->aggregateEnsureAmount();

        $this->aggregateCancelFeeAmount = $this->aggregateCancelFeeAmount();
        $this->commissionAmount = $this->commissionAmount();

        $this->commissionTax = floor($this->commissionAmount * ($this->tax / 100.0));   // 切り捨て
        $this->payAmount = $this->payAmount();
        $this->deferredPrice = $this->calcDeferredPrice();

        [$this->onlySeatAmount, $this->onlySeatNumber, $this->onlySeatDetails] = $this->aggregateOnlySeat();
        $this->callDetails = $this->callDetails();
        $this->otherCommissionTax = floor(($this->onlySeatAmount + $this->telAmount)  * ($this->tax / 100.0));   // 切り捨て
        $this->invoiceAmount = $this->onlySeatAmount + $this->telAmount + $this->otherCommissionTax;

        $this->settlementAmount = $this->payAmount - $this->invoiceAmount + $this->deferredPrice;

        $this->ensureDetails = $this->ensureDetails();
        $this->cancelDetails = $this->cancelDetails();
    }

    /**
     * 繰り越しメッセージの表示
     *
     * 条件：（全て当てはまる場合に表示）
     * 1：今回の出力対象は、前半締（1−15日）である
     * 2：現在時刻が後半締め前である
     * 3：金額が3000円未満である
     *
     * @return string
     */
    public function deferredDesc()
    {
        if ($this->startTerm == 1 && $this->endTerm == 15) {
            // 現在時刻が後半締前か確認するため、対象年月の末日を取得し比較する
            $now = new Carbon();
            $targetEndDay = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth(); // 対象年月の末日を取得
            if ($now <= $targetEndDay) {
                if (abs($this->settlementAmount) < self::DEFERRED_PRICE) {
                    return '※3000円未満の場合は次期精算に繰り越しいたします。';
                }
            }
        }
        return '';
    }

    /**
     * 表示上の品目の年月の表示を整える
     * @return string
     */
    public function formatYm()
    {
        return $this->year . '年 ' .$this->month. ' 月 ' . $this->startTerm . '日 〜 ' . $this->endTerm . '日';
    }

    /**
     * 口座種別(当座 or 普通)を返却する
     * @return mixed
     */
    public function accountTypeStr()
    {
        $accountType = $this->settlementCompany->account_type;
        $result = array_search($accountType, array_column(config('const.settlement.account_type'), 'value'));
        return config('const.settlement.account_type')[$result]['label'];
    }

    /**
     * 税金分を引く(メニューに税込みで登録されているため)
     * @param $reservations
     * @return false|float|int
     */
    private function decreaseTax($reservations)
    {
        $total = 0;
        foreach ($reservations as $reservation) {
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
        }

        return $total;
    }

    /**
     * 予約ステータスが成約の予約の合計金額を返却する
     * @return mixed
     */
    private function aggregateEnsureAmount()
    {
        $reservations = $this->filterReservationStatus($this->reservations, $this->statusEnsure);

        return $this->decreaseTax($reservations);
    }

    /**
     * キャンセル料の合計金額を返却する
     * @return mixed
     */
    private function aggregateCancelFeeAmount()
    {
        $reservations = $this->filterReservationStatus($this->reservations, $this->statusCancel)->values();

        $cancelDetails = CancelDetail::whereIn('reservation_id', $reservations->pluck('id')->all())->get();

        return $this->cancelDetailSum($cancelDetails, $reservations);
    }

    /**
     * キャンセル料をキャンセル料collectionから集計する
     * @param $cancelDetails
     * @param $reservations
     * @return mixed
     */
    private function cancelDetailSum($cancelDetails, $reservations)
    {
        return $cancelDetails->sum(function ($cancelDetail) use ($reservations) {
            // キャンセル料に含むのだけ集計する
            if (in_array($cancelDetail['account_code'], $this->cancelFeeCodes)) {
                $reservation = $reservations->where('id', $cancelDetail['reservation_id'])->first();
                $cancelDetailSum = $cancelDetail['price'] * $cancelDetail['count'];
                if ($reservation->reservationStore->store->settlementCompany->result_base_amount == 'TAX_EXCLUDED') {
                    return $cancelDetailSum - ($cancelDetailSum * $reservation->tax / (100 + $reservation->tax));
                } else {
                    return $cancelDetailSum;
                }
            } else {
                return 0;
            }
        });
    }

    /**
     * 特定の期間の成約の予約データを抽出する
     * @return mixed
     */
    public function aggregateReservation()
    {
        $first = $this->startTerm;
        $last = $this->endTerm;
        $startDatetime = Carbon::create($this->year, $this->month, $first, 0, 0, 0);
        $endDatetime = Carbon::create($this->year, $this->month, $last, 23, 59, 59);

        // 精算対象の店舗idリスト取得
        $stores = Store::where('settlement_company_id', $this->settlementCompany->id)->get();
        $storeIds = $stores->pluck('id')->all();

        // 精算月から対象になる予約を抽出する
        $query = Reservation::with(['reservationMenus.reservationOptions', 'reservationStore.store.settlementCompany'])
            ->where('is_close', 1)
            ->where('payment_status', '!=', config('code.paymentStatus.wait_payment.key'))
            ->whereBetween('pick_up_datetime', [$startDatetime, $endDatetime]);

        return $query->whereHas('reservationStore', function ($query) use ($storeIds) {
            $query->whereIn('store_id', $storeIds);
        })->get();
    }

    /**
     * 席のみ予約のときの手数料合計金額、予約人数を集計する
     * @return array
     */
    private function aggregateOnlySeat()
    {
        $reservations = $this->reservations->filter(function ($value, $key) {
            $isOnlySeat = false;

            if ($value->payment_status != config('code.paymentStatus.unpaid.key') ||
                (!is_null($value->cancel_datetime) && $value->cancel_datetime < $value->pick_up_datetime)) {
                return false;
            }

            foreach ($value->reservationMenus as $reservationMenu) {
                // 0だったら席のみ
                if ($reservationMenu->unit_price == 0) {
                    $isOnlySeat = true;
                }
            }

            return $isOnlySeat;
        });

        $sum = 0;
        $number = 0;
        $onlySeatDetails = [];
        foreach ($reservations as $reservation) {
            $tmpSum = 0;
            $tmpNumber = 0;
            foreach ($reservation->reservationMenus as $reservationMenu) {
                $tmpSum += $reservationMenu->count * self::ONLY_SEAT_FEE;
                $tmpNumber += $reservationMenu->count;
            }

            if (isset($onlySeatDetails[$reservation->reservationStore->store_id])) {
                $tmp = $onlySeatDetails[$reservation->reservationStore->store_id];
                $tmp['amount'] += $tmpSum;
                $tmp['number'] += $tmpNumber;
                $onlySeatDetails[$reservation->reservationStore->store_id] = $tmp;
            } else {
                $onlySeatDetails[$reservation->reservationStore->store_id] = [
                    'storeId' => $reservation->reservationStore->store_id,
                    'storeName' => $reservation->reservationStore->name,
                    'amount' => $tmpSum,
                    'number' => $tmpNumber,
                ];
            }

            $sum += $tmpSum;
            $number += $tmpNumber;
        }

        return [$sum, $number, $onlySeatDetails];
    }

    private function filterReservationStatus(Collection $reservations, string $status)
    {
        return $reservations->where('reservation_status', $status);
    }

    private function commissionAmount()
    {
        $reservationRS = $this->reservations->where('app_cd', key(config('code.appCd.rs')));
        $reservationRSEnsure = $this->filterReservationStatus($reservationRS, $this->statusEnsure);
        $reservationRSCancel = $this->filterReservationStatus($reservationRS, $this->statusCancel);
        $cancelDetailsRS = CancelDetail::whereIn('reservation_id', $reservationRSCancel->pluck('id')->all())->get();
        $cancelDetailSumRS = $this->cancelDetailSum($cancelDetailsRS, $reservationRS);

        $reservationTO = $this->reservations->where('app_cd', key(config('code.appCd.to')));
        $reservationTOEnsure = $this->filterReservationStatus($reservationTO, $this->statusEnsure);
        $reservationTOCancel = $this->filterReservationStatus($reservationTO, $this->statusCancel);
        $cancelDetailsTO = CancelDetail::whereIn('reservation_id', $reservationTOCancel->pluck('id')->all())->get();
        $cancelDetailSumTO = $this->cancelDetailSum($cancelDetailsTO, $reservationTO);

        $rsTotal = $this->decreaseTax($reservationRSEnsure);
        $toTotal = $this->decreaseTax($reservationTOEnsure);
        Log::debug('reservationRSEnsure:'.$rsTotal);
        Log::debug('cancelDetailSumRS:'.$cancelDetailSumRS);
        Log::debug('commissionRS:'.$this->commissionRS);
        Log::debug('reservationTOEnsure:'.$toTotal);
        Log::debug('cancelDetailSumTO:'.$cancelDetailSumTO);
        Log::debug('commissionTO:'.$this->commissionTO);
        $RS = 0;
        $TO = 0;
        if ($this->accountingConditionRS == 'FIXED_RATE') {
            $RS = ($rsTotal + $cancelDetailSumRS) * $this->commissionRS;
        } elseif ($this->accountingConditionRS == 'FLAT_RATE') {
            Log::error('[RS]FLAT_RATE:This route is wrong! settlement_company_id =>'.$this->settlementCompany->id);
        }

        if ($this->accountingConditionTO == 'FIXED_RATE') {
            $TO = ($toTotal + $cancelDetailSumTO) * $this->commissionTO;
        } elseif ($this->accountingConditionTO == 'FLAT_RATE') {
            Log::error('[TO]FLAT_RATE:This route is wrong! settlement_company_id =>'.$this->settlementCompany->id);
        }

        Log::debug('RS + TO:'.($RS + $TO));
        return (int)($RS + $TO);
    }

    private function payAmount()
    {
        return ($this->aggregateEnsureAmount + $this->aggregateCancelFeeAmount) -
            ($this->commissionAmount + $this->commissionTax);
    }

    private function calcDeferredPrice()
    {
        if ($this->startTerm == 16) {
            $settlementDownload = SettlementDownload::where('settlement_company_id', $this->settlementCompany->id)
                ->where('month', $this->year . $this->month)
                ->where('start_term', 1)
                ->where('end_term', 15)
                ->first();
            if (!$settlementDownload) {
                return 0;
            }
            return $settlementDownload->deferred_price ?: 0;
        }

        return 0;
    }

    private function ensureDetails()
    {
        $reservationEnsure = $this->filterReservationStatus($this->reservations, $this->statusEnsure);
        $grouped = $reservationEnsure->groupBy('reservationStore.store_id');

        $details = [];
        foreach ($grouped as $key => $g) {
            foreach ($g as $r) {
                if (isset($details[$key])) {
                    $tmp = $details[$key];
                    $tmp['amount'] += $this->decreaseTax([$r]);
                    $details[$key] = $tmp;
                } else {
                    if ($r->app_cd == key(config('code.appCd.to'))) {
                        $appName = config('code.appCd.to.TO');
                    } else {
                        $appName = config('code.appCd.rs.RS');
                    }
                    $details[$key] = [
                        'storeId' => $r->reservationStore->store_id,
                        'storeName' => $r->reservationStore->name,
                        'appCd' => $appName,
                        'amount' => $this->decreaseTax([$r]),
                    ];
                }
            }
        }

        return $details;
    }


    private function cancelDetails()
    {
        $reservationCancel = $this->filterReservationStatus($this->reservations, $this->statusCancel);
        $cancelDetails = CancelDetail::whereIn('reservation_id', $reservationCancel->pluck('id')->all())->get();
        $grouped = $reservationCancel->groupBy('reservationStore.store_id');

        $details = [];
        foreach ($grouped as $key => $g) {
            foreach ($g as $r) {
                $cds = $cancelDetails->where('reservation_id', $r->id);
                $sum = $this->cancelDetailSum($cds, $reservationCancel);
                if (isset($details[$key])) {
                    $tmp = $details[$key];
                    $tmp['amount'] += $sum;
                    $details[$key] = $tmp;
                } else {
                    if ($r->app_cd == key(config('code.appCd.to'))) {
                        $appName = config('code.appCd.to.TO');
                    } else {
                        $appName = config('code.appCd.rs.RS');
                    }
                    $details[$key] = [
                        'storeId' => $r->reservationStore->store_id,
                        'storeName' => $r->reservationStore->name,
                        'appCd' => $appName,
                        'amount' => $sum,
                    ];
                }
            }
        }

        return $details;
    }

    private function callDetails()
    {
        $start = Carbon::create($this->year, $this->month, 1)->firstOfMonth();
        $end = Carbon::create($this->year, $this->month, 1)->lastOfMonth();;
        $callTracker = new CallTracker();
        $this->callDetails = $callTracker->getCommissionByMonth($start, $end, $this->settlementCompany->id);
        $this->telAmount = array_sum(array_column($this->callDetails, 'amount'));

        return $this->callDetails;
    }
}
