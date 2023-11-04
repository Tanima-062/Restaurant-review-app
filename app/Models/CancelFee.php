<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Reservation;
use Kyslik\ColumnSortable\Sortable;

class CancelFee extends Model
{
    use Sortable;

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'apply_term_from' => 'datetime',
        'apply_term_to' => 'datetime',
    ];

    public function getCancelPolicy($storeId, $appCd)
    {
        $query = CancelFee::query();
        $query->where('store_id', $storeId);
        $query->where('app_cd', $appCd);
        $now = new Carbon();
        $query->where('apply_term_from', '<=', $now);
        $query->where('apply_term_to', '>=', $now);
        $query->where('published', 1);
        $result = $query->get();

        return $result;
    }

    public function calcCancelFee(int $reservationId)
    {
        $reservation = Reservation::find($reservationId);
        $reservationMenu = $reservation->reservationMenus[0];
        $visitDate = new Carbon($reservation->pick_up_datetime);
        $now = Carbon::now();
        $total = $reservation->total;
        $cancelFeeDay = null;     //cancelLimitUnitがdayになってる日付が一番近いデータ保存用
        $cancelFeeTime = null;    //cancelLimitUnitがtimeになってる日付が一番近いデータ保存用
        $cancelFeeId = null;

        //来店前or後
        if (strtotime($now->format('Y-m-d H:i:s')) < strtotime($visitDate->format('Y-m-d H:i:s'))) {

            //該当するキャンセルポリシーを取得
            $cancelFees = CancelFee::where('store_id', $reservation->reservationStore->store_id)
            ->where('app_cd', $reservation->app_cd)
            ->where('apply_term_from', '<=', $now)
            ->where('apply_term_to', '>=', $now)
            ->where('visit', config('code.cancelPolicy.visit.before'))
            ->where('published', 1)
            ->get();

            //キャンセル料の登録がない場合はnull返す
            if ($cancelFees->count() === 0) {
                $refundPrice = null;
                $cancelPrice = null;
                $cancelFeeId = null;
                return [$refundPrice, $cancelPrice, $cancelFeeId];
            }

            //キャンセル料の登録がある場合
            foreach ($cancelFees as $cancelFee) {
                if ($cancelFee->cancel_limit_unit === config('code.cancelPolicy.cancel_limit_unit.day')) {
                    //unitがdayだった場合
                    $cancelLimit = $visitDate->copy()->subDays($cancelFee->cancel_limit);
                    if (strtotime($now->format('Y-m-d')) <= strtotime($cancelLimit->format('Y-m-d'))) {
                        if (empty($cancelFeeDay)) {
                            $cancelFeeDay = $cancelFee;
                            continue;
                        }
                        if ($cancelFeeDay->cancel_limit < $cancelFee->cancel_limit) {
                            $cancelFeeDay = $cancelFee;
                        }
                    }
                } else {
                    //unitがtimeだった場合
                    $cancelLimit = $visitDate->copy()->subHours($cancelFee->cancel_limit);
                    if (strtotime($now->format('Y-m-d H:i:s')) <= strtotime($cancelLimit->format('Y-m-d H:i:s'))) {
                        if (empty($cancelFeeTime)) {
                            $cancelFeeTime = $cancelFee;
                            continue;
                        }
                        if ($cancelFeeTime->cancel_limit <= $cancelFee->cancel_limit) {
                            $cancelFeeTime = $cancelFee;
                        }
                    }
                }
            }

            //該当するデータがどちらか判定する処理
            if (!empty($cancelFeeDay) && empty($cancelFeeTime)) {   //cancelFeeDayしか入ってなかった場合
                $todayCancelFee = $cancelFeeDay;
            } elseif (empty($cancelFeeDay) && !empty($cancelFeeTime)) { //cancelFeeTimeしか入ってなかった場合
                $todayCancelFee = $cancelFeeTime;
            } elseif (!empty($cancelFeeDay) && !empty($cancelFeeTime)) {        //両方にデータが入っていた場合
                $cancelLimitDay = $visitDate->copy()->hour(23)->minute(59)->subDays($cancelFeeDay->cancel_limit);
                $cancelLimitTime = $visitDate->copy()->subHours($cancelFeeTime->cancel_limit);
                $todayCancelFee = strtotime($cancelLimitDay->format('Y-m-d H:i:s')) > strtotime($cancelLimitTime->format('Y-m-d H:i:s')) ? $cancelFeeTime : $cancelFeeDay;
            } else {    //どちらにもデータが入っていなかった場合はnullを返す
                $refundPrice = null;
                $cancelPrice = null;
                $cancelFeeId = null;
                return [$refundPrice, $cancelPrice, $cancelFeeId];
            }
        } else {
            //該当するキャンセルポリシーを取得
            $todayCancelFee = CancelFee::where('store_id', $reservation->reservationStore->store_id)
            ->where('app_cd', $reservation->app_cd)
            ->where('apply_term_from', '<=', $now)
            ->where('apply_term_to', '>=', $now)
            ->where('visit', config('code.cancelPolicy.visit.after'))
            ->where('published', 1)
            ->first();

            //キャンセル料の登録がない場合はnullで返す
            if (empty($todayCancelFee)) {
                $refundPrice = null;
                $cancelPrice = null;
                $cancelFeeId = null;
                return [$refundPrice, $cancelPrice, $cancelFeeId];
            }
        }

        $cancelPrice = [];
        $cancelPrice['total'] = 0;

        //定率or定額
        if ($todayCancelFee->cancel_fee_unit === config('code.cancelPolicy.cancel_fee_unit.fixedRate')) {

            $cancelPrice['menu']['price'] = $reservationMenu->unit_price * ($todayCancelFee->cancel_fee / 100);
            $cancelPrice['menu']['count'] = $reservation->persons;

            if ($reservationMenu->reservationOptions->count() !== 0) {
                foreach ($reservationMenu->reservationOptions as $reservationOption) {
                    $tmp['price'] = $reservationOption->unit_price * ($todayCancelFee->cancel_fee / 100);
                    $tmp['count'] = $reservationOption->count;
                    $cancelPrice['options'][$reservationOption->option_id] = $tmp;
                }
            }

        } else {    //定額の場合はcount1で固定
            $cancelFee = $todayCancelFee->cancel_fee;
            if ($cancelFee < $total) { // 合計金額未満
                if ($cancelFee <= $reservationMenu->price) {   //メニュー金額以下
                    $cancelPrice['menu']['price'] = $cancelFee;
                    $cancelPrice['menu']['count'] = 1;
                } else {    //メニュー金額以上
                    if ($reservationMenu->reservationOptions->count() !== 0) {
                        foreach ($reservationMenu->reservationOptions as $reservationOption) {
                            if (0 > $reservationOption->price - $cancelFee) {
                                $tmp['price'] = $reservationOption->price;
                                $tmp['count'] = 1;
                                $cancelPrice['options'][$reservationOption->option_id] = $tmp;
                                $cancelFee -= $reservationOption->price;
                            } else {
                                $tmp['price'] = $cancelFee;
                                $tmp['count'] = 1;
                                $cancelPrice['options'][$reservationOption->option_id] = $tmp;
                                $cancelFee -= $cancelFee;
                                break;
                            }
                        }
                        if ($cancelFee > 0) {
                            $cancelPrice['menu']['price'] = $cancelFee;
                            $cancelPrice['menu']['count'] = 1;
                        }
                    } else {
                        $cancelPrice['menu']['price'] = $reservationMenu->price;
                        $cancelPrice['menu']['count'] = 1;
                    }
                }
            } else {   //合計金額以上
                $cancelPrice['menu']['price'] = $reservationMenu->price;
                $cancelPrice['menu']['count'] = 1;

                if ($reservationMenu->reservationOptions->count() !== 0) {
                    foreach ($reservationMenu->reservationOptions as $reservationOption) {
                        $tmp['price'] = $reservationOption->price;
                        $tmp['count'] = 1;
                        $cancelPrice['options'][$reservationOption->option_id] = $tmp;
                    }
                }
            }
        }

        //繰り上げor切り捨てして合計金額を計算
        if ($todayCancelFee->fraction_round === config('code.cancelPolicy.fraction_round.roundUp')
            && $todayCancelFee->cancel_fee_unit === config('code.cancelPolicy.cancel_fee_unit.fixedRate')
            && $todayCancelFee->cancel_fee !== 100) {   //100%の時は繰り上げor切り捨てしない
            //1しか来ないはずなのでもし10,100も来るようであれば修正必要
            $ceilMenu = ceil($cancelPrice['menu']['price']);
            $cancelPrice['menu']['price'] = $ceilMenu;
            $cancelPrice['menu']['total'] = $ceilMenu * $cancelPrice['menu']['count'];
            $cancelPrice['total'] += $cancelPrice['menu']['total'];
            if (isset($cancelPrice['options'])) {
                foreach ($cancelPrice['options'] as $optionId => $option) {
                    $ceilOption = ceil($option['price']);
                    $cancelPrice['options'][$optionId]['price'] = $ceilOption;
                    $cancelPrice['options'][$optionId]['total'] = $ceilOption * $cancelPrice['options'][$optionId]['count'];
                    $cancelPrice['total'] += $cancelPrice['options'][$optionId]['total'];
                }
            }
        } elseif($todayCancelFee->fraction_round === config('code.cancelPolicy.fraction_round.roundDown')
            && $todayCancelFee->cancel_fee_unit === config('code.cancelPolicy.cancel_fee_unit.fixedRate')
            && $todayCancelFee->cancel_fee !== 100) {   //100%の時は繰り上げor切り捨てしない
            //1しか来ないはずなのでもし10,100も来るようであれば修正必要
            $floorMenu = floor($cancelPrice['menu']['price']);
            $cancelPrice['menu']['price'] = $floorMenu;
            $cancelPrice['menu']['total'] = $floorMenu * $cancelPrice['menu']['count'];
            $cancelPrice['total'] += $cancelPrice['menu']['total'];
            if (isset($cancelPrice['options'])) {
                foreach ($cancelPrice['options'] as $optionId => $option) {
                    $floorOption = floor($option['price']);
                    $cancelPrice['options'][$optionId]['price'] = $floorOption;
                    $cancelPrice['options'][$optionId]['total'] = $floorOption * $option['count'];
                    $cancelPrice['total'] += $cancelPrice['options'][$optionId]['total'];
                }
            }
        } else {
            if (isset($cancelPrice['menu'])) {
                $cancelPrice['menu']['total'] = $cancelPrice['menu']['price'] * $cancelPrice['menu']['count'];
                $cancelPrice['total'] += $cancelPrice['menu']['total'];
            }
            if (isset($cancelPrice['options'])) {
                foreach ($cancelPrice['options'] as $optionId => $option) {
                    $cancelPrice['options'][$optionId]['total'] = $option['price'] * $option['count'];
                    $cancelPrice['total'] += $cancelPrice['options'][$optionId]['total'];
                }
            }
        }

        //最低、最高額の設定があった場合のチェック
        if (!empty($todayCancelFee->cancel_fee_max)
            && $todayCancelFee->cancel_fee !== 100
            && $todayCancelFee->cancel_fee_unit === config('code.cancelPolicy.cancel_fee_unit.fixedRate')) {
            //キャンセル料合計が最高額を超えていたら差額をレコード登録
            if ($todayCancelFee->cancel_fee_max < $cancelPrice['total']) {
                $cancelFeeDiff = $todayCancelFee->cancel_fee_max - $cancelPrice['total'];
                $cancelPrice['total'] = $todayCancelFee->cancel_fee_max;
                $cancelPrice['diff'] = $cancelFeeDiff;
            }
        }

        if (!empty($todayCancelFee->cancel_fee_min)
            && $todayCancelFee->cancel_fee !== 100
            && $todayCancelFee->cancel_fee_unit === config('code.cancelPolicy.cancel_fee_unit.fixedRate')) {
            //キャンセル料合計が最低額を下回っていたら差額をレコード登録
            if ($todayCancelFee->cancel_fee_min > $cancelPrice['total']
                && $reservation->total > $todayCancelFee->cancel_fee_min) {
                $cancelFeeDiff = $todayCancelFee->cancel_fee_min - $cancelPrice['total'];
                $cancelPrice['total'] = $todayCancelFee->cancel_fee_min;
                $cancelPrice['diff'] = $cancelFeeDiff;
            } elseif ($todayCancelFee->cancel_fee_min > $cancelPrice['total']
                && $total < $todayCancelFee->cancel_fee_min) { //合計金額が最低額を下回っていた場合(全額取る)
                $cancelFeeDiff = $total - $cancelPrice['total'];
                $cancelPrice['total'] = $total;
                $cancelPrice['diff'] = $cancelFeeDiff;
            }
        }

        $cancelFeeId = $todayCancelFee->id;
        $refundPrice = $total - $cancelPrice['total'];

        return [$refundPrice, $cancelPrice, $cancelFeeId];
    }
}

