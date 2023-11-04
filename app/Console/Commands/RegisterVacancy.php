<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\Vacancy;
use App\Models\VacancyQueue;
use App\Models\Holiday;
use App\Services\StoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RegisterVacancy extends Command
{
    use BaseCommandTrait;

    private $className;
    private $menu;
    private $storeService;
    private $createdAt;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register:vacancy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'insert data into vacancies table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Menu $menu, StoreService $storeService)
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->menu = $menu;
        $this->createdAt = Carbon::now();
        $this->storeService = $storeService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        $vacancyQueue = VacancyQueue::query();
        $queues = $vacancyQueue->get();

        foreach ($queues as $queue) {
            try {
                // メモリ上限を上げない実装にするため数千件ずつインサートと削除を行うため時間かかります 3ヶ月の30分単位で約7分
                $this->_register($queue->request, $queue->store_id);
                $queue->delete();
            } catch (\Throwable $e) {
                \DB::rollback();
                \Log::error($e->getMessage());

                $isPro = \App::environment('production');
                if ($isPro) {
                    $title = '空席管理一括登録バッチで例外発生';
                    if (isset($queue->store_id)) {
                        $title = '店舗ID:'.$queue->store_id.' '.$title;
                    }
                    CommonLog::notifyToChat(
                        $title,
                        $e->getMessage()
                    );
                } else {
                    \Log::error($e);
                }
            }
        }

        $this->end();

        return true;
    }

    private function _register($request, $id)
    {
        $inputs = json_decode($request, true);

        $paramWeek = $inputs['week'];
        $paramStart = $inputs['start'];
        $paramEnd = $inputs['end'];

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
        $count = 0;
        foreach ($inputs['interval'] as $key => $values) {
            $params[$count]['time'] = $key;
            foreach ($values as $k => $v) {
                $params[$count][$k] = $v;
            }
            ++$count;
        }

        // 指定期間分登録
        $startDate = new Carbon($paramStart);
        $currentDate = $startDate->copy();
        $endDate = new Carbon($paramEnd);

        $store = Store::find($id);
        $regularHoliday = $store->regular_holiday;
        $numberOfSeats = $store->number_of_seats;

        // メモリ節約
        $ops = null;
        $op = null;
        $time = null;
        $lastIntervalTime = null;
        $inputs = null;
        $tmp = null;
        $store = null;
        $count = null;
        $key = null;
        $values = null;
        $paramStart = null;
        $paramEnd = null;
        $msg = null;

        while ($currentDate->lte($endDate)) {
            // 指定した日付の祝日情報を取得
            $holiday = Holiday::where('date', $currentDate->format('Y-m-d'))->first();
            $isRegularHoliday = $this->storeService->checkFromWeek($holiday, $regularHoliday, $currentDate, 1, $msg); // 店舗定休日かどうか

            // 店舗定休日の場合は、在庫を登録しない
            if (!$isRegularHoliday) {
                $currentDate->addDay();
                continue;
            }

            // メモリ解放を目的としてサブルーチン化します
            $this->_exec($currentDate, $paramWeek, $params, $id, $numberOfSeats);
        }
    }

    private function _exec($currentDate, $paramWeek, $params, $id, $numberOfSeats)
    {
        $records = [];

        \DB::beginTransaction();

        // 予約が入っている日付を取得
        $pickUpDatetimes = Reservation::where('pick_up_datetime', '>=', $currentDate->format('Y-m-d 00:00:00'))
                        ->where('pick_up_datetime', '<=', $currentDate->format('Y-m-d 23:59:59'))
                        ->where('app_cd', config('code.gmServiceCd.rs'))
                        ->where('reservation_status', '!=', config('code.reservationStatus.cancel.key'))
                        ->whereHas('reservationStore', function ($q) use ($id) {
                            $q->where('store_id', $id);
                        })->pluck('pick_up_datetime');
        $formatedPickUpDatetimes = $pickUpDatetimes->map(function ($pickUpDatetime) {
            $cbn = new Carbon($pickUpDatetime);

            return $cbn->format('Y-m-d');
        })->toArray();

        // 予約が既にある日付は在庫の設定はしない仕様
        if (in_array($currentDate->format('Y-m-d'), $formatedPickUpDatetimes, true)) {
            \DB::rollback();
            $currentDate->addDay();

            return;
        }

        $pickUpDatetimes = null;
        $formatedPickUpDatetimes = null;

        // currentDateが祝日で祝日営業してない場合は、営業時間外なので在庫の設定はしない仕様
        if (Holiday::where('date', $currentDate->copy()->format('Y-m-d'))->exists() && $paramWeek[7] === '0') {
            //\Log::debug('祝日の営業はないので祝日の空席は登録しません');
            \DB::rollback();
            $currentDate->addDay();

            return;
        }

        // 指定曜日の場合
        list($week, $weekName) = $this->menu->getWeek($currentDate);
        //echo '実行終了1[メモリ使用量]：'.memory_get_usage() / (1024 * 1024)."MB\n";
        if ($paramWeek[$week] === '1') {
            $cnt = count($params);
            // メモリ要件のためforeachは使いません
            for ($ii = 0; $ii < $cnt; ++$ii) {
                $tmpRecord = [];
                $tmpRecord['date'] = $currentDate->format('Y-m-d');
                $tmpRecord['time'] = $params[$ii]['time'];
                $tmpRecord['base_stock'] = $params[$ii]['base_stock'];
                $tmpRecord['stock'] = null;
                $tmpRecord['is_stop_sale'] = $params[$ii]['is_stop_sale'];
                $tmpRecord['store_id'] = $id;
                $tmpRecord['created_at'] = $this->createdAt;

                // stockの計算
                for ($i = 1; $i <= $numberOfSeats; ++$i) {
                    $tmpRecord['headcount'] = $i;
                    $stock = $tmpRecord['base_stock'] / $tmpRecord['headcount'];
                    if ($stock < 0) {
                        continue;
                    }
                    $tmpRecord['stock'] = floor($stock);
                    $records[] = $tmpRecord;
                }
            }
        }

        // 登録するデータがなければdeleteしないように
        if (empty($records)) {
            \DB::rollback();
            $currentDate->addDay();

            return;
        }

        Vacancy::where('store_id', $id)
            ->whereDate('date', $currentDate)
            ->delete();

        $collection = collect($records);
        $records = $collection->chunk(1000);
        foreach ($records as $record){
            \DB::table('vacancies')->insert($record->toArray());
        }

        \DB::commit();
        // これがないと変数初期化や処理メソッド化など何をしてもインサート処理でメモリーリークします
        \DB::connection()->unsetEventDispatcher();

        //echo '実行終了3[メモリ使用量]：'.memory_get_usage() / (1024 * 1024)."MB\n";

        $currentDate->addDay();
    }
}
