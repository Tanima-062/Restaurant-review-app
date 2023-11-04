<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreVacancyEditAllCommitRequest;
use App\Http\Requests\Admin\StoreVacancyEditAllRequest;
use App\Http\Requests\Admin\StoreVacancyEditCommitRequest;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\Vacancy;
use App\Models\VacancyQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StoreVacancyController extends AdminController
{
    private $menu;

    public function __construct(Menu $menu)
    {
        $this->menu = $menu;
    }

    /**
     * レストラン空席管理　カレンダーページ.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function index(Request $request, int $id)
    {
        // indexから来た場合のみ、redirectのためにURLをセッションで保持
        if (isset(parse_url(url()->previous())['query']) && preg_match('/^page=[0-9]*/', parse_url(url()->previous())['query'])) {
            session(['storeVacancyRedirectTo' => url()->previous()]);
        }

        session()->forget('paramWeek');
        session()->forget('paramStart');
        session()->forget('paramEnd');
        $today = Carbon::today()->format('Y-m-d');

        $store = Store::find($id);

        if ($request->ajax()) {
            $start = $request->start;
            $end = $request->end;
            \Cookie::queue('dateStart', $request->start, 60);
            // カレンダー表示のための空席情報取得
            $getVacancy = Vacancy::where('store_id', $id)
                ->whereNotNull('stock')
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<', $end)
                ->whereDate('date', '>=', $today)
                ->where('headcount', 1)
                ->get(); //->toArray();
            // カレンダー情報のための予約情報取得
            $time = ' 00:00:00';
            $getReservationQuery = Reservation::whereDate('pick_up_datetime', '>=', $start.$time)
            ->whereDate('pick_up_datetime', '<=', $end.$time)
            ->where('app_cd', config('code.gmServiceCd.rs'))
            ->where('reservation_status', '!=', config('code.reservationStatus.cancel.key'));

            $getReservationQuery->whereHas('reservationStore', function ($query) use ($id) {
                $query->where('store_id', $id);
            });
            $getReservation = $getReservationQuery->get()->toArray();
            //vacancy
            $saleDates = [];
            $eventsArr = [];

            foreach ($getVacancy as $vacancy) {
                $title = $vacancy['is_stop_sale'] === 0 ? '販売中' : '売止';
                $stockTitle = [
                        'title' => $title,
                        'start' => $vacancy['date'],
                        'color' => '#e3f4fc',
                    ];
                // その日の設定がまだない場合は必ず設定
                if (!isset($saleDates[$vacancy['date']])) {
                    $saleDates[$vacancy['date']] = $stockTitle;
                    continue;
                }
                if (
                        // その日の設定を廃止→販売中にはできるが販売中→廃止にはしない。一つでも販売中があれば販売中と表示するため
                        isset($saleDates[$vacancy['date']]) && $saleDates[$vacancy['date']]['title'] === '売止' && $title === '販売中'
                    ) {
                    $saleDates[$vacancy['date']] = $stockTitle;
                }
            }

            $reservationDate = [];
            $vacancyDate = [];
            $colorForReservation = '#FFCCFF';
            foreach ($getReservation as $reservation) {
                $pick_up_date = date('Y-m-d', strtotime($reservation['pick_up_datetime']));
                $reservationDate[$pick_up_date]['title'] = '予約あり';
                $reservationDate[$pick_up_date]['start'] = $pick_up_date;
                $reservationDate[$pick_up_date]['color'] = $colorForReservation;
            }

            $year = date('Y', strtotime($start));
            $month = date('m', strtotime($start));
            $countDates = date('t', strtotime($start));
            for ($i = 1; $i <= $countDates; ++$i) {
                $date = $year.'-'.$month.'-'.sprintf('%02d', $i);

                if (!isset($reservationDate[$date])) {
                    $reservationDate[$date]['title'] = '予約なし';
                    $reservationDate[$date]['start'] = $date;
                    $reservationDate[$date]['color'] = $colorForReservation;
                }

                if (!isset($saleDates[$date])) {
                    if ($today <= $date) {
                        $vacancyDate[$date]['title'] = '在庫データ未登録';
                        $vacancyDate[$date]['start'] = $date;
                        $vacancyDate[$date]['color'] = '#e3f4fc';
                    }
                } else {
                    $vacancyDate[$date] = $saleDates[$date];
                }
            }

            // 祝日をカレンダーに表示
            $holidayDate = [];
            $holidays = Holiday::whereDate('date', '>=', $start)->whereDate('date', '<=', $end)->get();
            foreach ($holidays as $holiday) {
                $holidayDate[$holiday->date]['title'] = '☆ '.$holiday->name;
                $holidayDate[$holiday->date]['start'] = $holiday->date;
                $holidayDate[$holiday->date]['color'] = '#FF0000';
                $holidayDate[$holiday->date]['textColor'] = '#FFFFFF';
                $holidayDate[$holiday->date]['classNames'] = ['testClass'];
            }

            $eventsArr = array_merge(array_values($reservationDate), array_values($holidayDate), array_values($vacancyDate), $eventsArr);
            $data = collect($eventsArr);

            return response()->json($data);
        }

        $storeOpeningHourConst = config('const.storeOpeningHour');
        $weeks = $storeOpeningHourConst['week'];

        return view('admin.Store.Vacancy.index', compact('store', 'weeks'));
    }

    /**
     * レストラン空席管理　編集ページ表示.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function editForm(Request $request, $id)
    {
        $store = Store::find($id);
        $date = session('date', $request->date);
        // カレンダー表示のための空席情報取得
        $vacancies = Vacancy::where('store_id', $id)
        ->whereDate('date', '=', $date)
        ->where('headcount', 1)
        ->get();

        // デフォルトの間隔
        $intervalTime = (int) $request->intervalTime;
        if (empty($intervalTime)) {
            if (count($vacancies) > 1) {
                $time1 = new Carbon($vacancies[0]->time);
                $time2 = new Carbon($vacancies[1]->time);
                $intervalTime = $time1->diffInMinutes($time2);
            } else {
                $config = config('const.storeVacancy.interval.thirty');
                $intervalTime = $config['value'];
            }
        }

        $now = new Carbon($date);
        // 1=営業中 0=休み
        $regexp = ['[1]', '[1]', '[1]', '[1]', '[1]', '[1]', '[1]'];

        // -------営業時間を取得-------start
        // 指定日が祝日の場合は曜日は何でも良く祝日だけ見る
        $holiday = Holiday::where('date', $now->copy()->format('Y-m-d'))->first();
        if (!is_null($holiday)) {
            for ($i = 0; $i <= 6; ++$i) {
                $regexp[$i] = '[0|1]';
            }
        } else {
            [$week, $weekName] = $this->menu->getWeek($now->copy());
            for ($i = 0; $i <= 6; ++$i) {
                if ($week !== $i) {
                    $regexp[$i] = '[0|1]';
                }
            }
            $regexp[7] = '[0|1]';
        }

        $ops = OpeningHour::where('store_id', $id)
        ->where('week', 'regexp', implode('', $regexp))
        ->get();

        // -------営業時間を取得-------end

        // 在庫詳細設定の開始時間と終了時間を取得
        $start = $end = null;
        foreach ($ops as $op) {
            if (is_null($start)) {
                $start = $op->start_at;
            }
            if (is_null($end)) {
                $end = $op->end_at;
            }

            $dtStart = new Carbon($start);
            $opStart = new Carbon($op->start_at);
            if ($opStart->lt($dtStart)) {
                $start = $op->start_at;
            }
            $dtEnd = new Carbon($end);
            $opEnd = new Carbon($op->end_at);
            if ($opEnd->gt($dtEnd)) {
                $end = $op->end_at;
            }
        }

        // interval生成
        $intervals = [];
        $now = new Carbon($start);
        $end = new Carbon($end);
        while ($now->lt($end)) {
            $tmp['reserved'] = '';
            $tmp['base_stock'] = '';
            $tmp['is_stop_sale'] = '';
            $tmp['time'] = $now->copy()->format('H:i:s');
            $intervals[$tmp['time']] = $tmp;
            $now->addMinutes($intervalTime);
        }
        // defaultの設定
        if (count($vacancies) > 0) {
            $time1 = new Carbon($vacancies[0]->time);
            $time2 = new Carbon($vacancies[1]->time);
            if ($time1->diffInMinutes($time2) === $intervalTime) {
                foreach ($vacancies as $vacancy) {
                    if (isset($intervals[$vacancy->time])) {
                        $intervals[$vacancy->time]['base_stock'] = $vacancy->base_stock;
                        $intervals[$vacancy->time]['is_stop_sale'] = $vacancy->is_stop_sale;
                    }
                }
            }
        }

        // 営業時間外は編集不可にするため、営業時間かどうかフラグを設定する。
        // 予約済み数も設定する
        $prefixPickUpDateTime = date('Y-m-d ', strtotime($date));
        foreach ($intervals as $key => $val) {
            // 営業時間かどうかの設定
            $isOpen = 0;
            $dt = new Carbon($key);
            foreach ($ops as $op) {
                $start = new Carbon($op->start_at);
                $end = new Carbon($op->end_at);
                if ($dt->gte($start) && $dt->lt($end)) {
                    $isOpen = 1;
                }
            }
            $intervals[$key]['isOpen'] = $isOpen;

            // 予約済み数(来店人数)の設定
            $query = Vacancy::where('store_id', $id);
            $query->where('date', $prefixPickUpDateTime);
            $query->where('time', $key);
            $query->where('headcount', 1);
            $vacancy = $query->first();
            $intervals[$key]['countReservation'] = ($vacancy) ? ($vacancy->base_stock - $vacancy->stock) : 0;
        }

        return view(
            'admin.Store.Vacancy.edit',
            [
                'title' => date('Y年m月d日', strtotime($date)),
                'date' => $date,
                'store' => $store,
                'intervals' => $intervals,
                'intervalTime' => (int) $intervalTime,
                'regexp' => implode('', $regexp),
            ]
        );
    }

    /**
     * レストラン空席管理　一括登録ページ表示.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function editAllForm(StoreVacancyEditAllRequest $request, $id)
    {
        $store = Store::find($id);

        $paramWeek = session('paramWeek', $request->week);
        $paramStart = session('paramStart', $request->start);
        $paramEnd = session('paramEnd', $request->end);
        $params = session('params', []);

        $config = config('const.storeVacancy.interval.thirty'); // デフォルトの間隔
        $intervalTime = empty($request->intervalTime) ? $config['value'] : (int) $request->intervalTime;

        // 1=営業中 0=休み
        $regexp = ['[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]', '[0|1]'];
        $regWeek = '';

        foreach ($paramWeek as $key => $w) {
            if ($w === '1') {
                $regexp[$key] = '['.$w.']';
            }
            $regWeek .= $w;
        }

        $ops = OpeningHour::where('store_id', $id)
        ->where('week', 'regexp', implode('', $regexp))
        ->get();

        // 在庫詳細設定の開始時間と終了時間を取得
        $start = $end = null;
        foreach ($ops as $op) {
            if (is_null($start)) {
                $start = $op->start_at;
            }
            if (is_null($end)) {
                $end = $op->end_at;
            }

            $dtStart = new Carbon($start);
            $opStart = new Carbon($op->start_at);
            if ($opStart->lt($dtStart)) {
                $start = $op->start_at;
            }
            $dtEnd = new Carbon($end);
            $opEnd = new Carbon($op->end_at);
            if ($opEnd->gt($dtEnd)) {
                $end = $op->end_at;
            }
        }

        // interval生成
        $intervals = [];
        $now = new Carbon($start);
        $end = new Carbon($end);
        while ($now->lt($end)) {
            $tmp['time'] = $now->copy()->format('H:i:s');
            // 登録後の画面遷移の場合のみデフォルト値設定
            if (isset($params[$tmp['time']])) {
                $tmp['base_stock'] = $params[$tmp['time']]['base_stock'];
                $tmp['is_stop_sale'] = $params[$tmp['time']]['is_stop_sale'];
            } else {
                $tmp['base_stock'] = '';
                $tmp['is_stop_sale'] = '';
            }

            $intervals[$tmp['time']] = $tmp;

            $now->addMinutes($intervalTime);
        }

        // 営業時間外は編集不可にするため、営業時間かどうかフラグを設定する。
        // 予約済み数も設定する
        foreach ($intervals as $key => $val) {
            // 営業時間かどうかの設定
            $isOpen = 0;
            $dt = new Carbon($key);
            foreach ($ops as $op) {
                $start = new Carbon($op->start_at);
                $end = new Carbon($op->end_at);
                if ($dt->gte($start) && $dt->lt($end)) {
                    $isOpen = 1;
                }
            }
            $intervals[$key]['isOpen'] = $isOpen;
        }

        session(['paramStart' => $paramStart]);
        session(['paramEnd' => $paramEnd]);
        session(['paramWeek' => $paramWeek]);

        $storeOpeningHourConst = config('const.storeOpeningHour');
        $weeks = $storeOpeningHourConst['week'];

        return view(
            'admin.Store.Vacancy.editAll',
            [
                'term' => date('Y年m月d日', strtotime($paramStart)).' ~ '.date('Y年m月d日', strtotime($paramEnd)),
                'start' => $paramStart,
                'end' => $paramEnd,
                'store' => $store,
                'intervals' => $intervals,
                'intervalTime' => (int) $intervalTime,
                'weeks' => $weeks,
                'regexp' => implode('', $regexp),
                'paramWeek' => $paramWeek,
            ]
        );
    }

    /**
     * レストラン空席管理　編集ページデータ登録.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function editAll(StoreVacancyEditAllCommitRequest $request, $id)
    {
        try {
            $paramWeek = session('paramWeek', $request->week);
            $paramStart = session('paramStart', $request->start);
            $paramEnd = session('paramEnd', $request->end);
            $inputs = $request->all();
            $params = [];

            $ops = OpeningHour::where('store_id', $id)
            ->where('week', 'regexp', $inputs['regexp'])
            ->get();

            // 自動で終了時間〜（終了時間＋インターバル）の時間帯の在庫を自動で登録
            // 終了時間+インターバル=0時を超える設定は不可能です
            foreach ($ops as $op) {
                $tmp = [];
                $time = new Carbon($op->end_at);
                $lastIntervalTime = $time->copy()->subMinutes($inputs['intervalTime'])->format('H:i:s');

                if (isset($inputs['interval'][$lastIntervalTime])) {
                    $tmp['base_stock'] = $inputs['interval'][$lastIntervalTime]['base_stock'];
                    $tmp['is_stop_sale'] = $inputs['interval'][$lastIntervalTime]['is_stop_sale'];
                    $inputs['interval'][$time->format('H:i:s')] = $tmp;
                }
            }
            foreach ($inputs['interval'] as $key => $values) {
                $params[$key]['time'] = $key;
                foreach ($values as $k => $v) {
                    $params[$key][$k] = $v;
                }
            }

            // queueへ登録
            $vacancyQueue = new VacancyQueue();
            $vacancyQueue->store_id = $id;
            $vacancyQueue->request = json_encode($inputs);
            $vacancyQueue->save();

            $request->message = '更新しました。';
        } catch (\Throwable $e) {
            \Log::error($e);
            session(['paramStart' => $paramStart]);
            session(['paramEnd' => $paramEnd]);
            session(['paramWeek' => $paramWeek]);
            session(['params' => $params]);

            return redirect(route('admin.store.vacancy.editAllForm', [
                'id' => $id,
                'start' => $paramStart,
                'end' => $paramEnd,
                'intervalTime' => $request->intervalTime,
                ]))
            ->with('custom_error', '更新できませんでした。');
        }

        session(['paramStart' => $paramStart]);
        session(['paramEnd' => $paramEnd]);
        session(['paramWeek' => $paramWeek]);
        session(['params' => $params]);

        return redirect(route('admin.store.vacancy.editAllForm', [
            'id' => $id,
            'start' => $paramStart,
            'end' => $paramEnd,
            'intervalTime' => $request->intervalTime,
            ]))
        ->with('message', $request->message);
    }

    /**
     * レストラン空席管理　編集ページデータ登録.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function edit(StoreVacancyEditCommitRequest $request, $id)
    {
        try {
            \DB::beginTransaction();

            $inputs = $request->all();
            $params = [];

            $ops = OpeningHour::where('store_id', $id)
            ->where('week', 'regexp', $inputs['regexp'])
            ->get();

            // 自動で終了時間〜（終了時間＋インターバル）の時間帯の在庫を自動で登録
            // 終了時間+インターバル=0時を超える設定は不可能です
            foreach ($ops as $op) {
                $tmp = [];
                $time = new Carbon($op->end_at);
                $lastIntervalTime = $time->copy()->subMinutes($inputs['intervalTime'])->format('H:i:s');

                if (isset($inputs['interval'][$lastIntervalTime])) {
                    $tmp['base_stock'] = $inputs['interval'][$lastIntervalTime]['base_stock'];
                    $tmp['is_stop_sale'] = $inputs['interval'][$lastIntervalTime]['is_stop_sale'];
                    $inputs['interval'][$time->format('H:i:s')] = $tmp;
                }
            }
            foreach ($inputs['interval'] as $key => $values) {
                $params[$key]['time'] = $key;
                foreach ($values as $k => $v) {
                    $params[$key][$k] = $v;
                }
            }

            $store = Store::find($id);
            $records = [];
            foreach ($params as $param) {
                $tmpRecord = [];
                $tmpRecord['date'] = $request->date;
                $tmpRecord['time'] = $param['time'];
                $tmpRecord['base_stock'] = $param['base_stock'];
                $tmpRecord['stock'] = null;
                $tmpRecord['is_stop_sale'] = $param['is_stop_sale'];
                $tmpRecord['store_id'] = $id;
                // stockの計算
                for ($i = 1; $i <= $store->number_of_seats; ++$i) {
                    $tmpRecord['headcount'] = $i;
                    $stock = $tmpRecord['base_stock'] / $tmpRecord['headcount'];
                    if ($stock < 0) {
                        continue;
                    }
                    $tmpRecord['stock'] = floor($stock);
                    $records[] = $tmpRecord;
                }
            }

            // delete
            $deleteQuery = Vacancy::where('store_id', $id)
            ->whereDate('date', '=', $request->date);
            if (count($deleteQuery->get()) > 0) {
                $deleteQuery->delete();
            }

            // insert
            \DB::table('vacancies')->insert($records);
            \DB::commit();
            $request->message = '更新しました。';
        } catch (\Throwable $e) {
            \Log::error($e);
            \DB::rollback();

            return redirect(route('admin.store.vacancy.editForm', ['id' => $id]))
            ->with('custom_error', '更新できませんでした。')
            ->with('date', $request->date);
        }

        return redirect(route('admin.store.vacancy.editForm', ['id' => $id]))
        ->with('message', $request->message)
        ->with('date', $request->date);
    }

    /**
     * レストラン空席管理　カレンダーページ　１週目をコピーする機能. 使ってない.
     *
     * @params Request $request リクエスト
     * @params int $id 店舗ID
     *
     * @return View
     */
    public function copy(Request $request, $id)
    {
        try {
            $dateStart = \Cookie::get('dateStart');
            $insertData = $daleteDates = [];
            \DB::beginTransaction();
            $start = new Carbon($dateStart);
            $currentDate = $start->copy();
            // 最初の一週間分のデータをコピー
            for ($i = 0; $i < 7; ++$i) {
                $vacancies = Vacancy::where('store_id', $id)->where('date', $currentDate->format('Y-m-d'))->get();

                // １週目の各曜日を起点として１週間単位で早送りした日付が同じ月である限りコピーし続ける
                $today = $currentDate->copy();
                $nextWeekMonth = $today->addWeek()->format('m');
                while ($nextWeekMonth === $start->format('m')) {
                    // 祝日の場合はスキップする
                    if (is_null(Holiday::where('date', $today->format('Y-m-d'))->first())) {
                        // コピーする内容は元の日付のvancancyすべて
                        foreach ($vacancies as $vacancy) {
                            $rec = [];
                            $rec['date'] = $today->format('Y-m-d');
                            $rec['time'] = $vacancy->time;
                            $rec['headcount'] = $vacancy->headcount;
                            $rec['base_stock'] = $vacancy->base_stock;
                            $rec['stock'] = $vacancy->stock;
                            $rec['is_stop_sale'] = $vacancy->is_stop_sale;
                            $rec['store_id'] = $id;
                            $insertData[] = $rec;
                        }
                        // 古いデータは削除する
                        $deleteDates[] = $today->format('Y-m-d');
                    }
                    $nextWeekMonth = $today->addWeek()->format('m');
                }
                // 日付インクリメント
                $currentDate->addDay();
            }

            if (!empty($insertData)) {
                // 一括削除とインサートを実行
                Vacancy::where('store_id', $id)->whereIn('date', $deleteDates)->delete();
                \DB::table('vacancies')->insert($insertData);

                \DB::commit();
                $request->message = 'コピーしました。';
            } else {
                $request->message = 'コピーするデータがありませんでした。';
            }
        } catch (\Throwable $e) {
            \Log::error($e);
            \DB::rollback();
        }

        return redirect(route('admin.store.vacancy', ['id' => $id]))
        ->with('message', $request->message)
        ->with('date', $request->date);
    }
}
