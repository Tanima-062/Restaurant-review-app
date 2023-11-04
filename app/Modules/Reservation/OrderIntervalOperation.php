<?php

namespace App\Modules\Reservation;

use App\Models\Menu;
use App\Models\OrderInterval;
use Illuminate\Support\Carbon;

class OrderIntervalOperation
{
    private $menu;
    private $now;
    private $startDateTime;
    private $pickUpDateTime;
    private $currentInterval;
    private $currentStart;
    private $intervalStart;
    private $intervalEnd;
    private $nextIntervalStart;
    private $nextIntervalEnd;
    private $nowDateTime;
    private $lunchStart;
    private $lunchEnd;
    private $dinnerStart;
    private $dinnerEnd;

    public function __construct($menuId, $pickUpDate, $pickUpTime, $nowDateTime = null)
    {
        $this->setNowDateTime($nowDateTime);
        $this->menu = Menu::find($menuId);
        $this->setStartTime();
        $this->setPickUpDateTime($pickUpDate, $pickUpTime);
        $this->setInterval();
        $this->getNextInterval();
    }

    public function getNow()
    {
        return $this->now;
    }

    public function getMenu()
    {
        return $this->menu;
    }

    public function getLunchStart()
    {
        return $this->lunchStart;
    }

    public function getLunchEnd()
    {
        return $this->lunchEnd;
    }

    public function getDinnerStart()
    {
        return $this->dinnerStart;
    }

    public function getDinnerEnd()
    {
        return $this->dinnerEnd;
    }

    public function getPickUpDateTime()
    {
        return $this->pickUpDateTime;
    }

    protected function setNowDateTime($datetime)
    {
        $this->nowDateTime = $datetime;
    }

    protected function setStartTime()
    {
        // 現在時刻+最低調理時間
        $now = empty($this->nowDateTime) ? new Carbon() : new Carbon($this->nowDateTime);

        $this->now = $now->copy();
        $menu = Menu::find($this->menu->id);
        $this->startDateTime = $now->copy()->addMinutes($menu->store->lower_orders_time);
        \Log::debug('startDateTime:'.$this->startDateTime);
    }

    protected function setPickUpDateTime($pickUpDate, $pickUpTime)
    {
        $this->pickUpDateTime = new Carbon($pickUpDate.' '.$pickUpTime);
    }

    protected function setInterval()
    {
        \Log::debug('pickUpDateTime:'.$this->pickUpDateTime);
        $start = new Carbon($this->pickUpDateTime->copy()->format('Y-m-d H:00:00'));

        // // 旧実装（もしものための残し。新実装で問題なければ消してOK　チケット：feature/RTN-1721）
        // while ($this->pickUpDateTime->copy()->minute > $start->minute) {
        //     $start->addMinutes($this->menu->store->pick_up_time_interval);
        // }

        // 新実装
        while ($this->pickUpDateTime->copy()->format('Y-m-d H:i:s') > $start->format('Y-m-d H:i:s')) {
            $start->addMinutes($this->menu->store->pick_up_time_interval);
        }
        $this->intervalStart = $start->copy();
        $this->intervalEnd = $start->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        \Log::debug('intervalStart:'.$this->intervalStart);
        \Log::debug('intervalEnd:'.$this->intervalEnd);
    }

    protected function getNextInterval()
    {
        $this->nextIntervalStart = $this->intervalStart->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        $this->nextIntervalEnd = $this->intervalEnd->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        // 販売時間チェック
        $lunchStart = new Carbon($this->pickUpDateTime->format('Y-m-d').' '.$this->menu->sales_lunch_start_time);
        $this->lunchStart = $lunchStart;
        $tmpLunchEnd = new Carbon($this->pickUpDateTime->format('Y-m-d').' '.$this->menu->sales_lunch_end_time);
        $lunchEnd = $tmpLunchEnd->copy()->subMinutes($this->menu->store->pick_up_time_interval);
        $this->lunchEnd = $lunchEnd;
        $dinnerStart = new Carbon($this->pickUpDateTime->format('Y-m-d').' '.$this->menu->sales_dinner_start_time);
        $this->dinnerStart = $dinnerStart;
        $tmpDinnerEnd = new Carbon($this->pickUpDateTime->format('Y-m-d').' '.$this->menu->sales_dinner_end_time);
        $dinnerEnd = $tmpDinnerEnd->copy()->subMinutes($this->menu->store->pick_up_time_interval);
        $this->dinnerEnd = $dinnerEnd;

        // ランチタイム前かつ、(ランチ開始-注文間隔時間) ~ ランチ開始時間の間の場合
        if ($this->nextIntervalStart->between($lunchStart, $lunchStart->copy()->addMinutes($this->menu->store->pick_up_time_interval))) {
            return;
        }

        // ランチタイム前なら次のインターバル開始時間はランチタイム開始時間
        if ($this->nextIntervalStart->lte($lunchStart)) {
            $this->nextIntervalStart = new Carbon($lunchStart);
            $this->nextIntervalEnd = $this->nextIntervalStart->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        }

        // ランチタイム終了かつディナータイム前なら次のインターバル開始時間はディナータイム開始時間
        if (!$this->nextIntervalStart->between($lunchStart, $lunchEnd) && $this->nextIntervalStart->lte($dinnerStart)) {
            $this->nextIntervalStart = new Carbon($dinnerStart);
            $this->nextIntervalEnd = $this->nextIntervalStart->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        }
        // ランチタイム終了かつディナータイム終了なら次のインターバル開始時間はランチタイム開始時間
        if (!$this->nextIntervalStart->between($lunchStart, $lunchEnd) && !$this->nextIntervalStart->between($dinnerStart, $dinnerEnd)) {
            $this->nextIntervalStart = new Carbon($lunchStart);
            $this->nextIntervalEnd = $this->nextIntervalStart->copy()->addMinutes($this->menu->store->pick_up_time_interval);
        }

        \Log::debug('nextIntervalStart:'.$this->nextIntervalStart);
        \Log::debug('nextIntervalEnd:'.$this->nextIntervalEnd);
    }

    /*public function getOrderInterval($pickUpDate, $pickUpTime)
    {
        $this->pickUpDateTime = $pickUpDate.' '.$pickUpTime;
        $this->orderInterval = $this->_getOrderInterval($this->menu->id, $pickUpDate, $pickUpTime);
        //\Log::debug($this->orderInterval->start);
        //\Log::debug($this->orderInterval->end);

        return $this->orderInterval;
    }*/

    public function isLunchTime()
    {
        if ($this->menu->sales_lunch_start_time <= $this->pickUpDateTime->format('H:i:s') && $this->menu->sales_lunch_end_time >= $this->pickUpDateTime->format('H:i:s')) {
            return true;
        }

        return false;
    }

    public function isDinnerTime()
    {
        if ($this->menu->sales_dinner_start_time <= $this->pickUpDateTime->format('H:i:s') && $this->menu->sales_dinner_end_time >= $this->pickUpDateTime->format('H:i:s')) {
            return true;
        }

        return false;
    }

    public function isOrderTakable($addition)
    {
        $query = '
        select count(rm.count) AS count from (
            SELECT * FROM reservation_menus WHERE menu_id = :menu_id
        ) AS rm
        inner join
        (
            SELECT * FROM reservations WHERE pick_up_datetime >= :interval_start
            AND pick_up_datetime < :interval_end
        ) AS r
        on rm.reservation_id = r.id
        ';
        $queryParams = [
            'menu_id' => $this->menu->id,
            'interval_start' => $this->intervalStart->format('Y-m-d H:i:s'),
            'interval_end' => $this->intervalEnd->format('Y-m-d H:i:s'),
        ];
        $result = \DB::select($query, $queryParams);
        $count = $result[0]->count;

        \Log::debug('order_taken'.$count);
        $tmpp = $count + $addition;
        \Log::debug('order_taken + order'.$tmpp);
        $count = ($addition > 0) ? $count + $addition : $count;

        if ($count > (int) $this->menu->number_of_orders_same_time) {
            return false;
        }

        return true;
    }

    public function isLastOrderEnded(&$msg)
    {
        \Log::debug('interval start'.$this->intervalStart);
        \Log::debug('interval end'.$this->intervalEnd);

        // 調理時間なしに過去を指定された場合
        if (strtotime($this->pickUpDateTime->format('Y-m-d H:i:s')) < strtotime($this->now->format('Y-m-d H:i:s'))) {
            $msg = sprintf(\Lang::get('message.pastOrderInterval'));

            return true;
        }

        // 準備時間に間に合わない受け取り時間の場合
        //\Log::debug($this->pickUpDateTime.'<'.$this->startDateTime);

        if (strtotime($this->pickUpDateTime->format('Y-m-d H:i:s')) <= strtotime($this->startDateTime->format('Y-m-d H:i:s'))) {
            // 次の注文可能時間を返す
            // 15分の切り上げ
            $this->startDateTime = $this->startDateTime->addMinutes(config('const.store.roundUpTime.1') - $this->startDateTime->minute % config('const.store.roundUpTime.1'));
            $msg = sprintf(\Lang::get('message.nextOrderInterval'), $this->startDateTime->format('H:i'));

            // 受け取り時間=ランチ終了時間の場合は、ディナー開始時間が次の受け取り可能時間
            if ($this->pickUpDateTime->format('H:i:s') === $this->menu->sales_lunch_end_time) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($this->menu->sales_dinner_start_time)));
            }

            // 受け取り時間=ディナー終了時間の場合は、ランチ開始時間が次の受け取り可能時間
            if ($this->pickUpDateTime->format('H:i:s') === $this->menu->sales_dinner_end_time) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($this->menu->sales_lunch_start_time)));
            }

            //$intervalStart->addMinutes($this->menu->store->pick_up_time_interval);
            //$msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($intervalStart->format(''))));

            return true;
        }

        // 受け取り時間のインターバルがすでに開始している場合
        if (strtotime($this->intervalStart->format('Y-m-d H:i:s')) <= strtotime($this->now->format('Y-m-d H:i:s'))) {
            // 次の注文可能時間を返す
            $msg = sprintf(\Lang::get('message.nextOrderInterval'), $this->nextIntervalStart->format('H:i'));

            // 受け取り時間=ランチ終了時間の場合は、ディナー開始時間が次の受け取り可能時間
            if ($this->pickUpDateTime->format('H:i:s') === $this->menu->sales_lunch_end_time) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($this->menu->sales_dinner_start_time)));
            }

            // 受け取り時間=ディナー終了時間の場合は、ランチ開始時間が次の受け取り可能時間
            if ($this->pickUpDateTime->format('H:i:s') === $this->menu->sales_dinner_end_time) {
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), date('H:i', strtotime($this->menu->sales_lunch_start_time)));
            }

            return true;
        }

        // 受け取り時間=ランチ開始の場合は、受け取り時間のインターバルが実際には開始してないが開始前のインターバルを擬似的に見る必要がある
        if ($this->pickUpDateTime->format('H:i:s') === $this->menu->sales_lunch_start_time) {
            $tmpIntervalStart = $this->intervalStart->copy()->subMinutes($this->menu->store->pick_up_time_interval);
            if (strtotime($tmpIntervalStart->format('Y-m-d H:i:s')) <= strtotime($this->now->format('Y-m-d H:i:s'))) {
                // 次の注文可能時間を返す
                $msg = sprintf(\Lang::get('message.nextOrderInterval'), $this->intervalStart->copy()->addMinutes($this->menu->store->pick_up_time_interval)->format('H:i'));

                return true;
            }
        }

        return false;
    }
}
