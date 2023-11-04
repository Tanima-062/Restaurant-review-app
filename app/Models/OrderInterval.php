<?php

namespace App\Models;

use App\Modules\Reservation\OrderIntervalOperation;
use Illuminate\Database\Eloquent\Model;

class OrderInterval extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'order_taken' => 'int',
        'orderable_item' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 同時間帯注文組数チェック.
     *
     * @param  string date
     * @param  string time
     * @param  int menuId
     * @param  bool createFlg 時間帯データが作られてない場合このフラグをtrueでチェックついでに作成する
     * @param  string msg
     *
     * @return bool true:注文可能 false:注文不可
     */
    public function isOrderable(string $date, string $time, int $menuId, $addition = 0, &$msg, $nowDateTime = null): bool
    {
        $oip = new OrderIntervalOperation($menuId, $date, $time, $nowDateTime);

        // 注文時間が営業時間外なら注文不可
        if (!$oip->isLunchTime() && !$oip->isDinnerTime()) {
            if (strtotime($oip->getPickUpDateTime()->format('H:i')) < strtotime($oip->getLunchStart()->format('H:i'))) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($oip->getMenu()->sales_lunch_start_time)));
            } elseif (strtotime($oip->getPickUpDateTime()->format('H:i')) > strtotime($oip->getLunchEnd()->format('H:i')) && strtotime($oip->getPickUpDateTime()->format('H:i')) < strtotime($oip->getDinnerStart()->format('H:i'))) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($oip->getMenu()->sales_dinner_start_time)));
            } elseif (strtotime($oip->getPickUpDateTime()->format('H:i')) > strtotime($oip->getDinnerEnd()->format('H:i'))) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($oip->getMenu()->sales_lunch_start_time)));
            }

            return false;
        }

        // インターバル毎のラストオーダーチェック
        if ($oip->isLastOrderEnded($msg)) {
            return false;
        }

        // インターバル毎の注文可能数チェック
        if (!$oip->isOrderTakable($addition)) {
            $msg = \Lang::get('message.intervalOrderCheckFailure');

            return false;
        }

        return true;
    }
}
