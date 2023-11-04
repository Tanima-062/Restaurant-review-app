<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\OpeningHour;
use App\Models\Reservation;
use App\Models\Vacancy;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RegisterAllVacancy extends Command
{
    use BaseCommandTrait;

    const MAX_END_MONTH = 3;
    const TIME_UNIT = 60;
    const IS_STOP_SALE = false;

    private $className;
    private $menu;
    private $isPro;
    private $stock = 10;
    private $createdAt;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register_all:vacancy {stock} {start?} {end?} {startStoreId?} {endStoreId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is command insert data into vacancies table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Menu $menu)
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->menu = $menu;
        $this->createdAt = Carbon::now();
        $this->isPro = \App::environment('production');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->start();

            $this->stock = $this->argument('stock');
            // 開始日と終了日が指定されている場合はその期間、指定されていない場合は翌々月1ヶ月間
            if (!empty($this->argument('start')) && !empty($this->argument('end'))) {
                $startDate = new Carbon($this->argument('start'));
                $endDate = new Carbon($this->argument('end'));
            } else {
                $startDate = Carbon::now()->addMonthsNoOverflow(2)->startOfMonth();
                $endDate = Carbon::now()->addMonthsNoOverflow(2)->endOfMonth();
            }
            if ($startDate->copy()->addMonths(self::MAX_END_MONTH)->lt($endDate)) {
                $title = '[空席一括登録バッチ パラメータエラー]';
                $msg = '3ヶ月より長い期間を指定しないでください。';
                if ($this->isPro) {
                    CommonLog::notifyToChat(
                        $title,
                        $msg
                    );
                } else {
                    \Log::error($title.$msg);
                }

                return false;
            }

            $startStoreId = (int)$this->argument('startStoreId');
            $endStoreId = (int)$this->argument('endStoreId');
            // 対象の店舗を取得
            $query = \DB::table('stores')
                ->where(function ($query) {
                    $query->orWhere('app_cd', key(config('code.appCd.rs')))
                          ->orWhere('app_cd', key(config('code.appCd.tors')));
                })
                ->where('regular_holiday', '!=', '1111111110') //不定休の店舗は対象外
                ->where('batch_register_all_vacancy_flg', 1) // 本処理対象のみ
                ->whereExists(function ($query) {
                    $query->select(\DB::raw('id'))
                          ->from('opening_hours')
                          ->whereRaw('opening_hours.store_id = stores.id');
                })
                ->whereNotExists(function ($query) {
                    $query->select(\DB::raw('id'))
                          ->from('external_apis')
                          ->whereRaw('external_apis.store_id = stores.id');
                })
                ->orderBy('id', 'asc');

            if ($startStoreId > 0 && $endStoreId > 0) { // 登録対象店舗を範囲指定出来るようにする(オプション)
                $query->whereBetween('id', [$startStoreId, $endStoreId]);
            }

            $stores = $query->get();

            if (!($stores->count() > 0)) {
                $title = '[空席一括登録バッチ 実行エラー]';
                $msg = '対象店舗が設定されていないため、実行できませんでした';
                if ($this->isPro) {
                    CommonLog::notifyToChat(
                        $title,
                        $msg
                    );
                } else {
                    \Log::error($title.$msg);
                }
                return false;
            }

            $deletedDate = [];

            // 店舗ごとに処理
            foreach ($stores as $key => $store) {

                try {
                    echo '店舗ID: '.$store->id.' 開始[メモリ使用量]：'.memory_get_usage() / (1024 * 1024)."MB\n";
                    $ops = OpeningHour::where('store_id', $store->id)->get();

                    // 日跨ぎ営業している店舗かどうかを判定
                    $isOvernight = $this->_checkOvernight($ops);

                    foreach ($ops as $op) {
                        // 店舗の営業時間全てを網羅
                        $inputs = $this->_makeIntervals($op, $isOvernight);
                        if (empty($inputs)) {
                            continue;
                        }

                        // 指定期間分登録
                        $currentDate = $startDate->copy();
                        $numberOfSeats = $store->number_of_seats;
                        while ($currentDate->lte($endDate)) {
                            $this->_exec($currentDate, $op->week, $inputs, $store->id, $numberOfSeats, $deletedDate);
                        }
                    }
                } catch (\throwable $e) {
                    //ログに吐いてエラーを握りつぶす
                    $title = '空席データ全一括登録バッチで例外発生しましたが握り潰しました';
                    if ($this->isPro) {
                        CommonLog::notifyToChat(
                            $title,
                            $e->getMessage()
                        );
                    } else {
                        \Log::error($e);
                    }
                }
                unset($deletedDate[$store->id]);
                $stores[$key] = null;
            }

            $this->end();
        } catch (\throwable $e) {
            $title = '空席データ全一括登録バッチで例外発生しました';
            if ($this->isPro) {
                CommonLog::notifyToChat(
                    $title,
                    $e->getMessage()
                );
            } else {
                \Log::error($e);
            }

            return false;
        }

        return true;
    }

    private function _exec($currentDate, $paramWeek, $params, $id, $numberOfSeats, &$deletedDate)
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
            //\Log::debug('予約が入ってるため登録しません');
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

        [$week, $weekName] = $this->menu->getWeek($currentDate);
        //echo '実行終了1[メモリ使用量]：'.memory_get_usage() / (1024 * 1024)."MB\n";
        if ($paramWeek[$week] === '1') {
            if ($this->stock > 0) {
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
                if (!isset($deletedDate[$id][$currentDate->format('Y-m-d')])) {
                    Vacancy::where('store_id', $id)
                        ->whereDate('date', $currentDate)
                        ->delete();
                    $deletedDate[$id][$currentDate->format('Y-m-d')] = 1;
                }

                \DB::table('vacancies')->insert($records);
            } else {
                \DB::table('vacancies')
                    ->where('store_id', $id)
                    ->where('date', $currentDate->format('Y-m-d'))->delete();
            }
            \DB::commit();
            // これがないと変数初期化や処理メソッド化など何をしてもインサート処理でメモリーリークします
            \DB::connection()->unsetEventDispatcher();
        } else {
            \DB::rollback();
        }

        //echo '実行終了3[メモリ使用量]：'.memory_get_usage() / (1024 * 1024)."MB\n";

        $currentDate->addDay();
    }

    private function _makeIntervals($op, $isOvernight)
    {
        // 在庫詳細設定の開始時間と終了時間を取得
        $start = $end = null;
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

        // interval生成
        $intervals = [];
        $now = new Carbon($start);
        $end = new Carbon($end);
        $count = 0;
        while ($now->lte($end)) {
            if ($isOvernight && $now->between(new Carbon('00:00:00'), new Carbon('10:00:00'))) {
                // 日跨ぎ営業している店舗の場合、0:00〜10:00の在庫は登録しない
                $now->addMinutes(self::TIME_UNIT);
                continue;
            }
            $tmp['reserved'] = '';
            $tmp['base_stock'] = $this->stock;
            $tmp['is_stop_sale'] = self::IS_STOP_SALE;
            $tmp['time'] = $now->copy()->format('H:i:s');
            $intervals[$count] = $tmp;
            $now->addMinutes(self::TIME_UNIT);
            ++$count;
        }

        return $intervals;
    }

    /**
     * 店舗が日跨ぎ営業しているか判定する
     * 00:00:00で始まる営業時間と23:59:00で終わる営業時間が両方存在する場合、日跨ぎ営業しているとみなす
     * @return bool 日跨ぎ営業している場合true
     */
    private function _checkOvernight($ops)
    {
        $start = false;
        $end = false;
        foreach ($ops as $op) {
            if ($op->start_at == '00:00:00') {
                $start = true;
            }
            if ($op->end_at == '23:59:00') {
                $end = true;
            }
        }
        return $start && $end;
    }
}
