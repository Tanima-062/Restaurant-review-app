<?php

namespace App\Console\Commands;

use App\Models\SearchHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportTakeoutSearchHistory extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:takeoutSearchHistory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'insert histories into search_histories table';

    private $parameterNameToSave = [
        'cooking_genre_cd', 'menu_genre_cd', 'suggest_cd', 'suggest_text',
    ];

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

    public function handle()
    {
        $this->start();

        $this->process();

        $this->end();

        return;
    }

    /**
     * Execute the console command.
     *
     * @return int 0:正常終了 1:異常終了 2:対象データなし
     */
    public function process()
    {
        try {
            // 前日のログファイルを取得
            $dt = Carbon::yesterday()->format('Y-m-d');
            $logFileName = 'searchTakeout-'.$dt.'.log';
            $pathName = storage_path('logs');
            $fillPathFileName = $pathName.DIRECTORY_SEPARATOR.$logFileName;
            if (!file_exists($fillPathFileName)) {
                return 2;
            }
            $file = fopen($fillPathFileName, 'r');
            $insert = [];
            if ($file) {
                try {
                    $createdAt = Carbon::now()->toDateTimeString();
                    while ($line = fgets($file)) {
                        $tmpArray = [
                            'cooking_genre_cd' => null,
                            'menu_genre_cd' => null,
                            'suggest_cd' => null,
                            'suggest_text' => null,
                            'pick_up_datetime' => null,
                        ];

                        $lineArr = json_decode($line, true);

                        foreach (json_decode($lineArr['params']) as $key => $val) {
                            if ($key === 'cookingGenreCd') {
                                $tmpArray['cooking_genre_cd'] = $val;
                            }
                            if ($key === 'menuGenreCd') {
                                $tmpArray['menu_genre_cd'] = $val;
                            }
                            if ($key === 'suggestCd') {
                                $tmpArray['suggest_cd'] = $val;
                            }
                            if ($key === 'suggestText') {
                                $tmpArray['suggest_text'] = $val;
                            }
                            if ($key === 'pickUpDatetime') {
                                $tmpArray['pick_up_datetime'] = $val;
                            }
                            // カラム数が異なるレコードを一気に登録不可なのでカラム名は指定する↑
                            //$snakeKey = ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $key)), '_');
                            //if (in_array($snakeKey, $this->parameterNameToSave)) {
                            //    $tmpArray[$snakeKey] = $val;
                            //}
                        }
                        $tmpArray['created_at'] = $createdAt;
                        $tmpArray['session_id'] = $lineArr['16_session'];

                        $insert[] = $tmpArray;
                    }
                } catch (\Throwable $e) {
                    throw $e;
                } finally {
                    fclose($file);
                }
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return 1;
        }
        try {
            $dt = new Carbon();
            $dtLastMonthStart = $dt->copy()->subMonth()->format('Y-m-d 00:00:00');
            $dtLastMonthEnd = $dt->copy()->subMonth()->format('Y-m-d 23:59:59');
            // 1ヶ月前のデータを削除し、昨日分の履歴を登録
            DB::beginTransaction();
            SearchHistory::where('created_at', '>=', $dtLastMonthStart)->where('created_at', '<=', $dtLastMonthEnd)->delete();
            SearchHistory::insert($insert);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            $this->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
