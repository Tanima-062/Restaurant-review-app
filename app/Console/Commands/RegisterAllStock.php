<?php

namespace App\Console\Commands;

use App;
use DB;
use Log;
use App\Libs\CommonLog;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RegisterAllStock extends Command
{
    use BaseCommandTrait;

    // 祝日の曜日コード
    private const DAY_OF_WEEK_HOLIDAY = 99;

    // 提供可能日ビット（0桁目が月曜、1桁目が火曜・・・7桁目が祝日）と曜日コードのマッピング
    private static $holidayMapping = [
       Carbon::MONDAY,
       Carbon::TUESDAY,
       Carbon::WEDNESDAY,
       Carbon::THURSDAY,
       Carbon::FRIDAY,
       Carbon::SATURDAY,
       Carbon::SUNDAY,
       self::DAY_OF_WEEK_HOLIDAY,
   ];

   // 現在日時
   private $currentDatetime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'register_all:stock {stock} {start?} {end?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command inserts stock data of takeout into stocks table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->currentDatetime = Carbon::now();
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

            $targetMenus = Menu::has('store') //店舗が削除済みでない
                ->where('app_cd', '=', 'TO') // テイクアウトのみ
                ->where('batch_register_all_stock_flg', 1) // 本処理対象のみ
                ->select('id', 'provided_day_of_week');

            $targetMenuCount = $targetMenus->get()->count();
            if ($targetMenuCount > 0) {
                // 登録する在庫数
                $stockNumber = $this->argument('stock');

                // 開始日と終了日が指定されている場合はその期間、指定されていない場合は翌々月1ヶ月間
                if (!empty($this->argument('start')) && !empty($this->argument('end'))) {
                    $startDate = new Carbon($this->argument('start'));
                    $endDate = new Carbon($this->argument('end'));
                } else {
                    $startDate = Carbon::now()->addMonthsNoOverflow(2)->startOfMonth();
                    $endDate = Carbon::now()->addMonthsNoOverflow(2)->endOfMonth();
                }
                // 登録対象の日付配列を生成
                $calendar = $this->_makeCalendar($startDate, $endDate);

                // テイクアウトのメニューを1000件ずつ取得して在庫を登録する
                $targetMenus->chunk(1000, function ($menus) use ($stockNumber, $calendar, $startDate, $endDate) {
                    foreach ($menus as $menu) {
                        echo '対象メニューID: ' . $menu->id . "\n";

                        // メニューの在庫を登録する
                        $this->_registStock($menu, $stockNumber, $calendar, $startDate, $endDate);
                    }
                });
            } else {
                $title = '[在庫データ一括登録バッチ 実行エラー]';
                $msg = '対象メニューが設定されていないため、実行できませんでした';
                // 本番環境の場合、チャットワークに通知
                if (App::environment('production')) {
                    CommonLog::notifyToChat($title, $msg);
                }
                \Log::error($title.$msg);
            }

            $this->end();
        } catch (\throwable $e) {
            $title = '在庫データ一括登録バッチで例外が発生しました';
            // 本番環境の場合、チャットワークに通知
            if (App::environment('production')) {
                CommonLog::notifyToChat($title, $e->getMessage());
            }
            // それ以外の場合、エラーログのみ
            Log::error($title.'::'.$e->getMessage());
        }
    }

    /**
     * 指定された期間の全日付の配列を生成する
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function _makeCalendar($startDate, $endDate)
    {
        // 指定された期間内の祝日を取得する
        $holidays = Holiday::where('date', '>=', $startDate->format('Y-m-d'))
        ->where('date', '<=', $endDate->format('Y-m-d'))
        ->pluck('name', 'date')
        ->toArray();

        // 期間内の日付と曜日を配列にする
        $calendar = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $date = $currentDate->format('Y-m-d');
            if (isset($holidays[$date])) {
                // 祝日の場合、祝日コードを設定
                $calendar[$date] = self::DAY_OF_WEEK_HOLIDAY;
            } else {
                // 祝日以外の場合、曜日コードを設定
                $calendar[$date] = $currentDate->dayOfWeek;
            }
            $currentDate->addDay();
        }
        return $calendar;
    }

    /**
     * メニューの在庫を登録する
     * @param Menu $menu
     * @param int $stockNumber
     * @param array $calendar
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function _registStock($menu, $stockNumber, $calendar, $startDate, $endDate)
    {
        // このメニューの、既に登録済みの在庫を取得する
        $currentStocks = Stock::where('menu_id', '=', $menu->id)
        ->where('date', '>=', $startDate->format('Y-m-d'))
        ->where('date', '<=', $endDate->format('Y-m-d'))
        ->pluck('stock_number', 'date')
        ->toArray();

        // このメニューの提供可能曜日を配列に整形
        $menuDayOfWeek = [];
        foreach (str_split($menu->provided_day_of_week) as $key => $value) {
            if ($value == 1) {
                // 曜日ビットがONの場合、該当する曜日コードを配列に格納
                $menuDayOfWeek[] = self::$holidayMapping[$key];
            }
        }

        // 登録データを生成
        $insertData = [];
        foreach ($calendar as $date => $dayOfWeek) {
            // 在庫が既に登録済みの日は（在庫0件の場合も）登録しない
            if (isset($currentStocks[$date])) {
                continue;
            }
            // 提供可能曜日でない場合は登録しない
            if (!in_array($dayOfWeek, $menuDayOfWeek)) {
                continue;
            }
            // 在庫データ生成
            $tmpData = [
                'stock_number' => $stockNumber,
                'date' => $date,
                'menu_id' => $menu->id,
                'created_at' => $this->currentDatetime,
                'updated_at' => $this->currentDatetime,
            ];
            $insertData[] = $tmpData;
        }

        // 登録する在庫が無い場合は処理終了
        if (empty($insertData)) {
            return;
        }

        // 在庫を登録
        try {
            DB::beginTransaction();
            Stock::insert($insertData);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
