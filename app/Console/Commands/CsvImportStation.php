<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Models\Station;
use Batch;
use Log;
use Cache;

class CsvImportStation extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {filename : ファイル名を指定}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'station data import to Database';

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

        $fileName = $this->argument('filename');

        try {
            $filePath = storage_path('app/upload/' . $fileName);

            set_time_limit(0);

            // 元データはUTF-8のはず
            $file = new \SplFileObject($filePath);
            $file->setFlags(
                \SplFileObject::READ_CSV | // CSVとして行を読み込み
                \SplFileObject::READ_AHEAD |    // 先読み／巻き戻しで読み込み
                \SplFileObject::SKIP_EMPTY |    // 空行を読み飛ばす
                \SplFileObject::DROP_NEW_LINE   // 行末の改行を読み飛ばす
            );

            $now = Carbon::now()->format('Y-m-d H:i:s');
            $insert = [];
            $update = [];
            // データを保持する順番通りにしないといけない。Batchモジュールの変な仕様
            $columns = ['station_cd', 'created_at', 'name', 'name_roma', 'longitude', 'latitude', 'prefecture_id', 'deleted_at', 'updated_at'];
            $stations = Station::all();
            // 一行ずつ処理
            foreach ($file as $line) {
                // 1行目が項目名だった場合はpassする
                if ($file->key() == 0 && $line[0] == 'station_cd') {
                    continue;
                }

                // 終端の場合はpassする
                if ($file->eof()) {
                    continue;
                }

                if (count($line) != 15) {
                    throw new \Exception('csv format error');
                }

                /*$station = null;
                foreach ($stations as $s) {
                    if ($s['station_cd'] == $line[0]) {
                        $station = $s;
                    }

                    usleep(1);
                }*/
                $station = $stations->firstWhere('station_cd', $line[0]);

                if (empty($station)) {
                    $station = new Station;
                    $station['station_cd'] = (int)$line[0];
                    $station['created_at'] = $now;
                }

                $station['name'] = $line[2];
                $station['name_roma'] = (!empty($line[4])) ? str_replace('-', '_', strtolower($line[4])) : '';
                $station['longitude'] = $line[9];
                $station['latitude'] = $line[10];
                $station['prefecture_id'] = $line[6];

                if ((int)$line[13] === 2 && empty($station['deleted_at'])) {
                    $station['deleted_at'] = $now;
                }

                if ((int)$line[13] === 0 && !empty($station['deleted_at'])) {
                    $station['deleted_at'] = null;
                }

                if ($station->isDirty()) {
                    $station['updated_at'] = $now;
                } else {
                    continue;
                }

                if (empty($station->id)) {
                    $insert[] = $station->toArray();
                } else {
                    $update[] = $station->toArray();
                }

                usleep(2);
            }

            $this->line('-- insert target count:'.count($insert));
            $this->line('-- update target count:'.count($update));

            if (count($insert) > 0) {
                $result = Batch::insert(new Station, $columns, $insert, 500);
                $this->line('insert end : '. print_r($result, true));
            }

            if (count($update) > 0) {
                $result = Batch::update(new Station, $update, 'id');
                $this->line('update end : '. print_r($result, true));
            }

        } catch (\Exception | \Throwable $e) {
            $this->error('csv import error : '.$e->getMessage());
        }

        // 二重起動防止のpidのキャッシュは消しておく
        $pid = Cache::get($fileName, 0);
        Cache::forget($fileName);
        $this->line('forget pid : '.$pid);

        $this->end();

        return;
    }
}
