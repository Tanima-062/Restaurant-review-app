<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UpdateStockQueue;
use App\Modules\Ebica\EbicaStockSave;
use Illuminate\Support\Carbon;

class Vacancy extends Model
{
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * API用のstore_id(api_store_id)を取得
     * @param int storeId ストアID
     */
    public static function scopeGetApiShopId ($query, Int $storeId) {
        return $query->select('api_store_id')
            ->where('store_id', $storeId);
    }

    /**
     * 予約情報をAPIから取得
     * @param int storeId ストアID
     * @param string visitDate 予約日
     */
    public static function scopeGetVacancies ($query, Int $storeId, String $visitDate) {
        return $query->where('store_id', $storeId)
            // ->where('is_stop_sale', 0)
            ->where('date', $visitDate);
    }

    /**
     * レストラン｜メニュー単位の在庫数
     *
     * @param $params
     */
    public function menuVacancy($params)
    {
    //     $res = [];

    //     // 来店日
    //     $visitDate = $params['visitDate'];

    //     // menuIdの場合
    //     if (!empty($params['menuId'])) {
    //         $menu = Menu::find($params['menuId']);
    //         $menuId = $menu->id;
    //         $storeId = isset($menu->store_id) ? $menu->store_id : NULL;
    //     }

    //     // reservationIdの場合
    //     if (!empty($params['reservationId'])) {
    //         $reservation = ReservationStore::where('reservation_id', $params['reservationId'])->first();
    //         $menuId = ReservationMenu::where('reservation_id', $params['reservationId'])->first();
    //         $menuId = $menuId->menu_id;
    //         $storeId = isset($reservation->store_id) ? $reservation->store_id : NULL;
    //     }

    //     // 店舗情報取得
    //     $store = Store::find($storeId);
    //     // 店舗IDと紐付いたEbica用店舗情報を取得
    //     $externalApis = ExternalApi::where('store_id', $store->id)->first();

    //     // Ebica連携あり店舗の空席情報取得
    //     if (!is_null($externalApis)) {
    //         $addDay = 3;
    //         // 予約する日から$addDay日後
    //         $fmtDate = Carbon::now()->addDay($addDay)->format('Y/m/d');

    //         $menu = isset($menu) ? $menu : Menu::find($menuId);

    //         $fmtVisitDate = new Carbon($visitDate);
    //         // $visitDateを$fmtDateと比較するためにフォーマット
    //         $fmtVisitDate = $fmtVisitDate->format('Y/m/d');

    //         // true:予約予定日($visitDate)が予約する日から$addDay日以内ならEbicaAPIを呼び出す。
    //         // false:予約予定日($visitDate)が予約する日から$addDay + 1日以上ならDatabaseのVacanciesを参照する。
    //         if ($fmtDate > $fmtVisitDate) {
    //             // Ebica API用のstore_id(api_store_id)をVacaciesより取得
    //             $apiShopId = Vacancy::getApiShopId($storeId)->first();
    //             $apiShopId = $apiShopId['api_store_id'];
    //             // Ebica APIから空席情報を取得

    //             $stock = new EbicaStockSave();
    //             // Ebica API用の店舗IDがない場合はNULL指定
    //             $stockDatas = !is_null($apiShopId) ? $stock->getStock($apiShopId, $visitDate) : null;

    //             $arrange = [];
    //             if ($stockDatas) {
    //                 foreach ($stockDatas->stocks as $stocks) {
    //                     foreach ($stocks->stock as $stock) {
    //                         $insert['vacancyTime'] = $stock->reservation_time.':00';
    //                         $insert['people'] = $stocks->headcount;
    //                         $insert['sets'] = $stock->sets;

    //                         $arrange[] = $insert;
    //                     }
    //                     $res['stocks'] = $arrange;
    //                 }
    //             }
    //         } else {
    //             // Vacanciesテーブルから空席情報を取得
    //             $vacancies = Vacancy::getVacancies($storeId, $visitDate)->get();
    //             if (!is_null($vacancies)) {
    //                 foreach ($vacancies as $num => $vacancy) {
    //                     $res['stocks'][$num]['vacancyTime'] = $vacancy->time;
    //                     $res['stocks'][$num]['people'] = $vacancy->headcount;
    //                     $res['stocks'][$num]['sets'] = $vacancy->stock;
    //                 }
    //             }
    //         }
    //         // Ebica連携なしの店舗の空席情報取得
    //     } else {

    //         // 店舗総座席数
    //         $numberOfSeats = $store->number_of_seats;

    //         // 店舗に紐付いたメニュー
    //         $storeMenus = $store->restaurantMenus;

    //         // 指定日合計予約数予約数
    //         $rsvMenus = 0;

    //         // 指定日合計予約数予約数
    //         $rsvSeats = 0;

    //         $menu = isset($menu) ? $menu : Menu::find($menuId);

    //         // メニュー予約数 $rsvMenus
    //         $reservations = Reservation::where('pick_up_datetime', 'LIKE', $visitDate.'%')
    //             ->whereHas('reservationStore', function ($q) use ($menu) {
    //                 $q->where('store_id', $menu->store_id);
    //             })
    //             ->whereHas('reservationMenus', function ($q) use ($menu) {
    //                 $q->where('menu_id', $menu->id);
    //                 // $q->where('menu_id', $menuId);
    //             })
    //             ->whereNull('cancel_datetime')
    //             ->get();

    //         if ($reservations->count() > 0) {
    //             foreach ($reservations as $reservation) {
    //                 $rsvMenus += $reservation->persons;
    //             }
    //         }

    //         // 予約席数 $rsvSeats
    //         foreach ($storeMenus as $storeMenu) {
    //             $reservations = Reservation::where('pick_up_datetime', 'LIKE', $visitDate.'%')
    //                 ->whereHas('reservationStore', function ($q) use ($storeMenu) {
    //                     $q->where('store_id', $storeMenu->store_id);
    //                 })
    //                 ->whereHas('reservationMenus', function ($q) use ($storeMenu) {
    //                     $q->where('menu_id', $storeMenu->id);
    //                 })
    //                 ->whereNull('cancel_datetime')
    //                 ->get();

    //             if ($reservations->count() > 0) {
    //                 foreach ($reservations as $reservation) {
    //                     $rsvSeats += $reservation->persons;
    //                 }
    //             }
    //         }

    //         // メニュー在庫取得
    //         $dbStock = Stock::where('menu_id', $menuId)
    //             ->where('date', $visitDate)
    //             ->first();
    //         $dbStockNumber = is_null($dbStock) ? NULL : $dbStock->stock_number;

    //         // メニュー在庫（メニュー在庫 - メニュー予約数）
    //         $stocksMinusReservedSeats = $dbStockNumber - $rsvMenus;

    //         // 残り座席数（店舗座席数 - 店舗予約数）
    //         $seatsMinusReservedSeats = $numberOfSeats - $rsvSeats;

    //         // 予約可能席数計算(店舗総座席数 - 当日予約数)
    //         $canReservation = $stocksMinusReservedSeats > $seatsMinusReservedSeats ? $seatsMinusReservedSeats : $stocksMinusReservedSeats;

    //         // 予約可能席数上限設定（現状：99人 or メニューの利用可能上限人数）
    //         $upperLimit = $menu->available_number_of_upper_limit > config('const.store.rsvLimit.upper') ? config('const.store.rsvLimit.upper') : $menu->available_number_of_upper_limit;
    //         $loopLimit = $canReservation > $upperLimit ? $upperLimit : $canReservation;

    //         $openingHours = OpeningHour::where('store_id', $storeId)->get();
    //         $holiday = Holiday::where('date', $visitDate)->first();

    //         // 月=>0 火=>1 水=>2 木=>3 金=>4 土=>5 日=>6 祝=>7
    //         // 祝日ではないの場合
    //         if (is_null($holiday)) {
    //             $date = (new Carbon($visitDate))->dayOfWeek - 1;
    //             $date = $date === -1 ? $date = 6 : $date;
    //         // 祝日の場合
    //         } else {
    //             $date = 7;
    //         }

    //         // 店舗の基本情報を配列化
    //         foreach ($openingHours as $key => $openingHour) {
    //             $weeks[$key]['week'] = str_split($openingHour->week);    // 営業日
    //             $weeks[$key]['open'] = $openingHour->start_at;           // 開店時間
    //             $weeks[$key]['end'] = $openingHour->end_at;              // 閉店時間
    //             $weeks[$key]['last'] = $openingHour->last_order_time;    // ラストオーダー時間

    //             // ラストオーダー時間の設定がない場合は、閉店時間を取得
    //             $weeks[$key]['last'] = is_null($weeks[$key]['last']) ? $openingHour->end_at : $weeks[$key]['last'];
    //         }

    //         // 休日か確認
    //         $resultWeeks = [];
    //         foreach ($weeks as $key => $week) {
    //             if ($week['week'][$date] === '1') {
    //                 $resultWeeks[$key] = $week;
    //             }
    //         }

    //         // // メニュー最低注文時間と店舗最低注文時間の2つを比べて、長い方を最低注文時間とするパターン（予約導線と実装方法が違うためコメントアウト）
    //         // $storeLowerOrdersTime = is_null($store->lower_orders_time) ? 0: $store->lower_orders_time;;  // 店舗最低注文時間
    //         // $menuLowerOrdersTime = is_null($menu->lower_orders_time) ? 0: $menu->lower_orders_time;  // メニュー最低注文時間
    //         // $lowerOrdersTime = $storeLowerOrdersTime >= $menuLowerOrdersTime ? $storeLowerOrdersTime : $menuLowerOrdersTime;

    //         // メニュー最低注文時間のみで判断するパターン（予約動線でこちらを使用している）
    //         $lowerOrdersTime = is_null($menu->lower_orders_time) ? 0: $menu->lower_orders_time;  // メニュー最低注文時間

    //         $now = Carbon::now();                          // 現在時刻取得
    //         $now = $now->addMinutes($lowerOrdersTime);     // 現在時刻＋最低注文時間
    //         $vDate = new Carbon($visitDate.'00:00:00');    // 来店日
    //         $today = Carbon::today();                      // 今日の日付

    //         $msg = NULL;
    //         // メニュー提供可能日か確認
    //         $result = $menu->checkFromWeek($menu->provided_day_of_week, $vDate, 2, $msg);
    //         // 在庫がなかった時に$res['stocks']に空の配列を返す
    //         if ($result === FALSE) {
    //             $res['stocks'] = [];
    //             return $res;
    //         }

    //         if ($vDate->gte($today)) {
    //             $startAt = new Carbon();        // 営業開始時間
    //             $endAt = new Carbon();          // 営業終了時間
    //             $lastAt = new Carbon();         // ラストオーダー時間

    //             $arrayVisitDate = explode('-', $visitDate);        // 来店日をsetDateTimeするために配列化

    //             // データを配列で持たせるための整数(下記while文下で使用)
    //             $num = 0;

    //             // 予約可能席数をloop：sets計算のため&people・setsセット
    //             for ($i = $menu->available_number_of_lower_limit - 1; $i < $loopLimit; ++$i) {
    //                 // 店舗に結びついたopening_hours(休みの場合は除く)の回数loop：vacancyTime計算のため
    //                 foreach ($resultWeeks as $resultWeek) {
    //                     $arrayOpne = explode(':', $resultWeek['open']);    // 開店時間をsetDateTimeするために配列化
    //                     $arrayEnd = explode(':', $resultWeek['end']);      // 閉店時間をsetDateTimeするために配列化
    //                     $arrayLast = explode(':', $resultWeek['last']);    // ラストオーダー時間をsetDateTimeするために配列化

    //                     // 開店時間と閉店時間、ラストオーダー時間を日付+時間(setDateTime)でCarbonでセット
    //                     $startAt = $startAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayOpne[0], $arrayOpne[1], $arrayOpne[2]);
    //                     $endAt = $endAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayEnd[0], $arrayEnd[1], $arrayEnd[2]);
    //                     $endAt->subMinutes($menu->provided_time);
    //                     $lastAt = $lastAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLast[0], $arrayLast[1], $arrayLast[2]);

    //                     // ランチ販売開始時間が設定されている場合
    //                     if (!is_null($menu->sales_lunch_start_time)) {
    //                         $lunchStartAt = new Carbon();                                       // ランチ販売開始時間のCarbonインスタンス化
    //                         $arrayLunchStart = explode(':', $menu->sales_lunch_start_time);     // 開店時間をsetDateTimeするために配列化
    //                         $lunchStartAt = $lunchStartAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLunchStart[0], $arrayLunchStart[1], $arrayLunchStart[2]); // ランチ販売開始時間
    //                     }

    //                     // ランチ販売終了時間が設定されている場合
    //                     if (!is_null($menu->sales_lunch_end_time)) {
    //                         $lunchEndAt = new Carbon();                                         // ランチ販売終了時間のCarbonインスタンス化
    //                         $arrayLunchEnd = explode(':', $menu->sales_lunch_end_time);         // ラストオーダー時間をsetDateTimeするために配列化
    //                         $lunchEndAt = $lunchEndAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayLunchEnd[0], $arrayLunchEnd[1], $arrayLunchEnd[2]); // ランチ販売終了時間
    //                         $lunchEndAt->subMinutes($menu->provided_time); // ランチ販売終了時間からメニュー提供時間を引く（この処理をしないと、販売終了時間ぎりぎりまで在庫が有効になる）
    //                     }

    //                     // ディナー販売開始時間が設定されている場合
    //                     if (!is_null($menu->sales_dinner_start_time)) {
    //                         $dinnerStartAt = new Carbon();                                      // ディナー販売開始時間のCarbonインスタンス化
    //                         $arrayDinnerStart = explode(':', $menu->sales_dinner_start_time);   // 開店時間をsetDateTimeするために配列化
    //                         $dinnerStartAt = $dinnerStartAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayDinnerStart[0], $arrayDinnerStart[1], $arrayDinnerStart[2]);  // ディナー販売開始時間
    //                     }

    //                     // ディナー販売終了時間が設定されている場合
    //                     if (!is_null($menu->sales_dinner_end_time)) {
    //                         $dinnerEndAt = new Carbon();                                        // ディナー販売終了時間のCarbonインスタンス化
    //                         $arrayDinnerEnd = explode(':', $menu->sales_dinner_end_time);       // ラストオーダー時間をsetDateTimeするために配列化
    //                         $dinnerEndAt = $dinnerEndAt->setDateTime($arrayVisitDate[0], $arrayVisitDate[1], $arrayVisitDate[2], $arrayDinnerEnd[0], $arrayDinnerEnd[1], $arrayDinnerEnd[2]); // ディナー販売終了時間
    //                         $dinnerEndAt->subMinutes($menu->provided_time); // ディナー販売終了時間からメニュー提供時間を引く（この処理をしないと、販売終了時間ぎりぎりまで在庫が有効になる）
    //                     }

    //                     // 開店時間がラストオーダーの時間を超えるまでloop：vacancyTimeセットのため
    //                     while (!$startAt->gt($lastAt)) {
    //                         // 過去の時間(含む最低注文時間)だった場合(setsを0にする)
    //                         if (!$startAt->gt($now)) {
    //                             $res['stocks'][$num]['vacancyTime'] = $startAt->format('H:i');
    //                             $res['stocks'][$num]['people'] = $i + 1;
    //                             $res['stocks'][$num]['sets'] = 0;
    //                             $startAt = $startAt->addMinutes(config('const.store.interval.vacancyTime'));
    //                             ++$num;
    //                         // 未来の時間だった場合
    //                         } else {
    //                             $res['stocks'][$num]['vacancyTime'] = $startAt->format('H:i');
    //                             $res['stocks'][$num]['people'] = $i + 1;

    //                             // ランチ販売開始時間とランチ販売終了時間、ディナー販売開始時間、ディナー販売終了時間の全てが設定されている場合
    //                             if (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time) && !is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
    //                                 $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
    //                                 $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
    //                                 if (($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) || ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt))) {
    //                                     $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
    //                                 } else {
    //                                     $res['stocks'][$num]['sets'] = 0;
    //                                 }
    //                             // ランチ販売開始時間とランチ販売終了時間が設定されている場合
    //                             } elseif (!is_null($menu->sales_lunch_start_time) && !is_null($menu->sales_lunch_end_time)) {
    //                                 $lunchEndAt = $endAt->lte($lunchEndAt) ? $endAt : $lunchEndAt;
    //                                 // ランチ販売開始時間 < 販売時間　かつ　販売時間 < ランチ販売終了時間 - メニュー提供時間
    //                                 if ($lunchStartAt->lte($startAt) && $startAt->lte($lunchEndAt)) {
    //                                     $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
    //                                 } else {
    //                                     $res['stocks'][$num]['sets'] = 0;
    //                                 }
    //                             // ディナー販売開始時間とディナー販売終了時間が設定されている場合
    //                             } elseif (!is_null($menu->sales_dinner_start_time) && !is_null($menu->sales_dinner_end_time)) {
    //                                 $dinnerEndAt = $endAt->lte($dinnerEndAt) ? $endAt : $dinnerEndAt;
    //                                 // ディナー販売開始時間 < 販売時間　かつ　販売時間 < ディナー販売終了時間 - メニュー提供時間
    //                                 if ($dinnerStartAt->lte($startAt) && $startAt->lte($dinnerEndAt)) {
    //                                     $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
    //                                 } else {
    //                                     $res['stocks'][$num]['sets'] = 0;
    //                                 }
    //                             // 販売時間が入っていない場合
    //                             } else {
    //                                 $res['stocks'][$num]['sets'] = (int) floor($canReservation / ($i + 1));
    //                             }

    //                             $startAt = $startAt->addMinutes(config('const.store.interval.vacancyTime'));
    //                             ++$num;
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     // 在庫がなかった時に$res['stocks']に空の配列を返す
    //     if (!isset($res['stocks']) && !empty($result)) {
    //         $res['stocks'] = [];
    //     }

    //     return $res;
    }

    /**
     * 在庫更新(complete,save,cancel)
     *
     * @param integer $persons
     * @param Menu $menu
     * @param Carbon $dt
     *
     * @return void
     */
    public function updateStock(int $persons, Menu $menu, Carbon $dt)
    {
        $now = Carbon::now();
        $store = Store::find($menu->store_id);
        $storeId = $store->id;
        $startTime = $dt->format('H:i:s');
        $endTime = $dt->copy()->addMinutes($menu->provided_time)->format('H:i:s');

        // 在庫更新処理
        if (empty($store->external_api)) {  //外部接獄なしの場合
            $vacancies = Vacancy::where('store_id', $storeId)
                ->whereDate('date', $dt->format('Y-m-d'))
                ->whereTime('time', '>=', $startTime)
                ->whereTime('time', '<=', $endTime)
                ->get();

            $baseVacancies = $vacancies->where('headcount', 1)->all();
            foreach ($baseVacancies as $baseVacancy) {
                $baseVacancy->stock = $baseVacancy->stock - $persons < 0 ? 0 : $baseVacancy->stock - $persons;
                $baseVacancy->save();
                $timeMatchVacancies = $vacancies->where('time', $baseVacancy->time)->where('headcount', '!=', 1)->all();
                foreach ($timeMatchVacancies as $timeMatchVacancy) {
                    $timeMatchVacancy->stock = floor($baseVacancy->stock / $timeMatchVacancy->headcount) < 0 ? 0 : floor($baseVacancy->stock / $timeMatchVacancy->headcount);
                    $timeMatchVacancy->save();
                }
            }
        } else {    //外部接続ありの場合
            if (Vacancy::where('store_id', $storeId)->whereDate('date', $dt->format('Y-m-d'))->exists()) {
                $data = [];
                $data['store_id'] = $storeId;
                $data['date'] = $dt->format('Y-m-d');
                $data['exec_at'] = $now->copy()->addMinutes(10)->format('Y-m-d H:i:s');

                UpdateStockQueue::insert($data);
            }
        }
    }
}
