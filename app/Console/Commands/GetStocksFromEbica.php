<?php

namespace App\Console\Commands;

use App\Models\ExternalApi;
use Illuminate\Console\Command;
use App\Models\Vacancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Modules\Ebica\EbicaStockSave;
use Batch;

class GetStocksFromEbica extends Command
{
    use BaseCommandTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebica:getStocks {month}';

    private $className;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store vacancy info from Ebica to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        $this->process();

        $this->end();

        return;
    }

    public function process()
    {
        // 差分確認パターン
        DB::beginTransaction();
        try {
            $ebica = new EbicaStockSave();

            // コマンドの引数である月を取得
            $month = (int) $this->argument('month');
            
            $threeMonthLater = Carbon::today();
            $threeMonthLater = $threeMonthLater->addMonths($month);
            $now = Carbon::now(); //created_ad用の現在時刻

            // データを保持する順番通りにしないといけない。Batchモジュールの変な仕様
            $columns = [
                'api_store_id',
                'date',
                'time',
                'headcount',
                'stock',
                'store_id',
                'created_at'
            ];

            // $apiShopId = 4610;
            // $shopId = 3;

            $external_apis = ExternalApi::all();
            \Log::debug($external_apis);
            foreach ($external_apis as $external_api) {
                \Log::debug($external_api);
                $apiShopId = $external_api->api_store_id;
                $shopId = $external_api->store_id;
                if ($month === 1) {
                    $today = Carbon::today();
                }

                if ($month === 2) {
                    $today = Carbon::today();
                    $today->addMonth();
                }

                if ($month === 3) {
                    $today = Carbon::today();
                    $today->addMonths($month - 1);
                }

                // コマンドで受け取った月の空席情報を取得
                while ($today->lt($threeMonthLater)) {
                    $ebicaDates = [];  // エビカデータ保存用配列
                    $tmpInserts = [];  // バルクインサート用配列
                    $tmpUpdates = [];  // バルクアップデート用配列
                    $tmpDeletes = [];  // 削除用配列
                    // ebica空席情報取得
                    $stockData = $ebica->getStock($apiShopId, $today->format('Y-m-d'));

                    // ebica空席情報の配列の作成
                    foreach ($stockData->stocks as $stocks) {
                        foreach ($stocks->stock as $stock) {
                            $data['api_store_id'] = $apiShopId;
                            $data['date'] = $today->format('Y-m-d');
                            $data['time'] = $stock->reservation_time . ':00';
                            $data['headcount'] = $stocks->headcount;
                            $data['stock'] = $stock->sets;
                            $data['store_id'] = $shopId;
                            $data['created_at'] = $now;
                            $ebicaDates[] = $data;
                        }
                    }

                    $vacancies = Vacancy::where('store_id', $shopId)->where('date', $today->format('Y-m-d'))->get();
                    $vacancies = $vacancies->all();

                    \Log::debug(1);
                    // ebicaから取得したレコード数とVacanciesから取得したレコード数が同じ場合
                    if (count($ebicaDates) === count($vacancies)) {
                        for ($i = 0; $i < count($ebicaDates); $i++) {
                            // ebica取得の日付とVacancies取得の日付の比較
                            if ($ebicaDates[$i]['date'] !== $vacancies[$i]['date']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の時間とVacancies取得の時間の比較
                            if ($ebicaDates[$i]['time'] !== $vacancies[$i]['time']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の予約人数とVacancies取得の予約人数の比較
                            if ($ebicaDates[$i]['headcount'] !== $vacancies[$i]['headcount']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の空席状況とVacancies取得の空席状況の比較
                            if ($ebicaDates[$i]['stock'] !== $vacancies[$i]['stock']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }
                        }
                    }

                    \Log::debug(2);
                    // ebicaから取得したレコード数がVacanciesから取得したレコード数より多かった場合
                    if (count($ebicaDates) > count($vacancies)) {
                        for ($i = 0; $i < count($ebicaDates); $i++) {
                            // Vacanciesから取得したレコードがない場合
                            if (empty($vacancies[$i])) {
                                $tmpInserts[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の日付とVacancies取得の日付の比較
                            if ($ebicaDates[$i]['date'] !== $vacancies[$i]['date']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の時間とVacancies取得の時間の比較
                            if ($ebicaDates[$i]['time'] !== $vacancies[$i]['time']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の予約人数とVacancies取得の予約人数の比較
                            if ($ebicaDates[$i]['headcount'] !== $vacancies[$i]['headcount']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の空席状況とVacancies取得の空席状況の比較
                            if ($ebicaDates[$i]['stock'] !== $vacancies[$i]['stock']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }
                        }
                    }

                    \Log::debug(3);
                    // Vacanciesから取得したレコード数がebicaから取得したレコード数より多かった場合
                    if (count($ebicaDates) < count($vacancies)) {
                        for ($i = 0; $i < count($vacancies); $i++) {
                            // Ebicaから取得したレコードがない場合
                            if (empty($ebicaDates[$i])) {
                                $tmpDeletes[] = $vacancies[$i]['id'];
                                continue;
                            }

                            // ebica取得の日付とVacancies取得の日付の比較
                            if ($ebicaDates[$i]['date'] !== $vacancies[$i]['date']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の時間とVacancies取得の時間の比較
                            if ($ebicaDates[$i]['time'] !== $vacancies[$i]['time']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の予約人数とVacancies取得の予約人数の比較
                            if ($ebicaDates[$i]['headcount'] !== $vacancies[$i]['headcount']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }

                            // ebica取得の空席状況とVacancies取得の空席状況の比較
                            if ($ebicaDates[$i]['stock'] !== $vacancies[$i]['stock']) {
                                $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
                                $tmpUpdates[] = $ebicaDates[$i];
                                continue;
                            }
                        }
                    }
                    
                    \Log::debug(4);
                    // バルクインサート
                    if (!empty($tmpInserts)) {
                        $resultInsert = Batch::insert(new Vacancy(), $columns, $tmpInserts, 500);
                        \Log::debug('insert end : ' . print_r($resultInsert, true));
                    }

                    \Log::debug(5);
                    // バルクアップデート
                    if (!empty($tmpUpdates)) {
                        // 編集データに関連したデータの一括編集
                        $resultUpdate = Batch::update(new Vacancy, $tmpUpdates, 'id');
                        \Log::debug('update end : ' . print_r($resultUpdate, true));
                    }

                    \Log::debug(6);
                    // 削除
                    if (!empty($tmpDeletes)) {
                        $resultDelete = Vacancy::whereIn('id', $tmpDeletes)->delete();
                        \Log::debug('delete end : ' . print_r($resultDelete, true));
                    }

                    \Log::debug(7);
                    $today = $today->addDay();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            \Log::error($e);
            DB::rollback();
        }


        // // 処理スピードを見て選択するためにコメントアウト中
        // // 全てUpdateしてからInsertかDeleteパターン
        // DB::beginTransaction();
        // try {
        //     $ebica = new EbicaStockSave();
        //     $threeMonthLater = Carbon::today();
        //     $threeMonthLater = $threeMonthLater->addMonths($month);
        //     $now = Carbon::now(); //created_ad用の現在時刻

        //     // データを保持する順番通りにしないといけない。Batchモジュールの変な仕様
        //     $columns = [
        //         'api_store_id',
        //         'date',
        //         'time',
        //         'headcount',
        //         'stock',
        //         'store_id',
        //         'created_at'
        //     ];

        //     // $apiShopId = 4610;
        //     // $shopId = 3;

        //     $external_apis = ExternalApi::all();
        //     foreach ($external_apis as $external_api) {
        //         $apiShopId = $external_api->api_store_id;
        //         $shopId = $external_api->store_id;
        //         $today = Carbon::today();
                
        //         // 3ヶ月先までの空席情報を取得
        //         while ($today->lt($threeMonthLater)) {
        //             $tmpInserts = [];  // バルクインサート用配列
        //             $tmpUpdates = [];  // バルクアップデート用配列
        //             $tmpDeletes = [];  // 削除用配列
        //             $ebicaDates = [];  // エビカデータ保存用配列
        //             // ebica空席情報取得
        //             $stockData = $ebica->getStock($apiShopId, $today->format('Y-m-d'));

        //             //データ配列の作成
        //             foreach ($stockData->stocks as $stocks) {
        //                 foreach ($stocks->stock as $stock) {
        //                     $data['api_store_id'] = $apiShopId;
        //                     $data['date'] = $today->format('Y-m-d');
        //                     $data['time'] = $stock->reservation_time . ':00';
        //                     $data['headcount'] = $stocks->headcount;
        //                     $data['stock'] = $stock->sets;
        //                     $data['store_id'] = $shopId;
        //                     $data['created_at'] = $now;
        //                     $ebicaDates[] = $data;
        //                 }
        //             }

        //             $vacancies = Vacancy::where('store_id', $shopId)->where('date', $today->format('Y-m-d'))->get();
        //             $vacancies = $vacancies->toArray();

        //             // ebicaから取得したレコード数とVacanciesから取得したレコード数が同じ場合
        //             if (count($ebicaDates) === count($vacancies)) {
        //                 // dd('ebicaとvacancyが同じ', $ebicaDates, $vacancies);
        //                 for ($i = 0; $i < count($ebicaDates); $i++) {
        //                         $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
        //                         $tmpUpdates[] = $ebicaDates[$i];
        //                 }
        //             }

        //             // ebicaから取得したレコード数がVacanciesから取得したレコード数より多かった場合
        //             if (count($ebicaDates) > count($vacancies)) {
        //                 // dd('ebicaが多いパターン', $ebicaDates, $vacancies);
        //                 for ($i = 0; $i < count($ebicaDates); $i++) {
        //                     // Vacanciesから取得したレコードがない場合
        //                     if (empty($vacancies[$i])) {
        //                         $tmpInserts[] = $ebicaDates[$i];
        //                         continue;
        //                     }

        //                     $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
        //                     $tmpUpdates[] = $ebicaDates[$i];
        //                 }
        //             }

        //             // Vacanciesから取得したレコード数がebicaから取得したレコード数より多かった場合
        //             if (count($ebicaDates) < count($vacancies)) {
        //                 // dd('vacanciesが多いパターン', $ebicaDates, $vacancies);
        //                 for ($i = 0; $i < count($vacancies); $i++) {
        //                     // Ebicaから取得したレコードがない場合
        //                     if (empty($ebicaDates[$i])) {
        //                         $tmpDeletes[] = $vacancies[$i]['id'];
        //                         continue;
        //                     }
        //                     $ebicaDates[$i]['id'] = $vacancies[$i]['id'];
        //                     $tmpUpdates[] = $ebicaDates[$i];
        //                 }
        //             }

        //             if (!empty($tmpInserts)) {
        //                 $resultInsert = Batch::insert(new Vacancy(), $columns, $tmpInserts, 500);
        //                 // $resultInsert = Batch::insert(new Vacancy, $columns, $tmpUpdates, 500);
        //                 // \Log::debug('insert end : ' . print_r($resultInsert, true));
        //                 // dd($resultInsert);
        //             }

        //             if (!empty($tmpUpdates)) {
        //                 // 編集データに関連したデータの一括編集
        //                 $resultUpdate = Batch::update(new Vacancy, $tmpUpdates, 'id');
        //                 // \Log::debug('update end : ' . print_r($resultUpdate, true));
        //                 // dd($resultUpdate);
        //             }

        //             if (!empty($tmpDeletes)) {
        //                 $resultDelete = Vacancy::whereIn('id', $tmpDeletes)->delete();
        //                 // \Log::debug('delete end : ' . print_r($resultDelete, true));
        //             }

        //             $today = $today->addDay();
        //             \Log::debug($today);
        //         }

        //         \Log::debug('succeeded');
        //     }

        //     DB::commit();
        // } catch (\Exception $e) {
        //     \Log::debug('failed');
        //     DB::rollback();
        // }


        // // 処理スピードを見て選択するためにコメントアウト中
        // // 全てDeleteしてからInsert
        // DB::beginTransaction();
        // try {
        //     $ebica = new EbicaStockSave();
        //     $threeMonthLater = Carbon::today();
        //     $threeMonthLater = $threeMonthLater->addMonths($month);
        //     $now = Carbon::now(); //created_ad用の現在時刻

        //     // データを保持する順番通りにしないといけない。Batchモジュールの変な仕様
        //     $columns = [
        //         'api_store_id',
        //         'date',
        //         'time',
        //         'headcount',
        //         'stock',
        //         'store_id',
        //         'created_at'
        //     ];

        //     // $apiShopId = 4610;
        //     // $shopId = 3;

        //     $external_apis = ExternalApi::all();
        //     foreach ($external_apis as $external_api) {
        //         $apiShopId = $external_api->api_store_id;
        //         $shopId = $external_api->store_id;
        //         $today = Carbon::today();
                
        //         // 3ヶ月先までの空席情報を取得
        //         while ($today->lt($threeMonthLater)) {
        //             $ebicaDates = [];  // エビカデータ保存用配列
        //             // ebica空席情報取得
        //             $stockData = $ebica->getStock($apiShopId, $today->format('Y-m-d'));

        //             //データ配列の作成
        //             foreach ($stockData->stocks as $stocks) {
        //                 foreach ($stocks->stock as $stock) {
        //                     $data['api_store_id'] = $apiShopId;
        //                     $data['date'] = $today->format('Y-m-d');
        //                     $data['time'] = $stock->reservation_time . ':00';
        //                     $data['headcount'] = $stocks->headcount;
        //                     $data['stock'] = $stock->sets;
        //                     $data['store_id'] = $shopId;
        //                     $data['created_at'] = $now;
        //                     $ebicaDates[] = $data;
        //                 }
        //             }

        //             $vacancies = Vacancy::where('store_id', $shopId)->where('date', $today->format('Y-m-d'))->get();
        //             $vacancies = $vacancies->toArray();
        //             $deleteVacancies = array_column($vacancies, 'id');
        //             if (!empty($deleteVacancies)) {
        //                 $resultDelete = Vacancy::whereIn('id', $deleteVacancies)->delete();
        //             }
        //             if (!empty($ebicaDates)) {
        //                 $resultInsert = Batch::insert(new Vacancy(), $columns, $ebicaDates, 500);
        //             }

        //             $today = $today->addDay();
        //             \Log::debag($today);
        //         }

        //         \Log::debag('succeeded');
        //     }

        //     DB::commit();
        // } catch (\Exception $e) {
        //     \Log::debug('failed');
        //     DB::rollback();
        // }
    }
}
