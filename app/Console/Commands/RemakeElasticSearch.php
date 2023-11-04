<?php

namespace App\Console\Commands;

use App\Models\Station;
use Illuminate\Console\Command;
use Log;
use Exception;

class RemakeElasticSearch extends Command
{
    use BaseCommandTrait;

    const INDEX_STATION = 'station';
    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remake:elastic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This remakes index and mapping and all data on elasticsearch';

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

    private function process() {

        $isOutput = true;
        $stationCsv = '/tmp/station.csv';

        // 駅データ抽出
        $fp = fopen($stationCsv, 'w');
        try {
            $header = ['id', 'station_cd', 'name', 'latitude', 'longitude'];
            fputcsv($fp, $header);

            Station::whereNull('deleted_at')
                ->where('prefecture_id', 13)
                ->chunk(300, function ($stations) use ($fp) {
                    foreach ($stations as $station) {
                        $line = [
                            $station->id,
                            $station->station_cd,
                            $station->name,
                            $station->latitude,
                            $station->longitude
                        ];
                        fputcsv($fp, $line);
                    }
                });
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $isOutput = false;
        } finally {
            if (!fclose($fp)) {
                $this->error('fail write csv. file close error.');
                $isOutput = false;
            }
        }

        if (!$isOutput) {
            return false;
        }

        $elasticHost = config('services.elastic.host');
        $elasticPort = config('services.elastic.port');
        $user = config('services.elastic.user');
        $password = config('services.elastic.password');

        if (config('app.env') != 'local') {
            //$elasticHost = 'https://' . $elasticHost;
        }

        $url = sprintf("%s:%d/%s", $elasticHost, $elasticPort, self::INDEX_STATION);

        $authCurl = ($user && $password) ? sprintf("-u %s:%s", $user, $password) : '';
        // indexの確認
        exec('curl '. $authCurl .' -XGET '.$elasticHost.':'.$elasticPort.'/_cat/indices', $result);

        $inStation = false;
        foreach ((array)$result as $r) {
            if (strpos($r, 'station')) {
                $inStation = true;
            }
        }
        if ($inStation) { // station indexがあったら削除。
            // elasticsearchのstationインデックス削除
            exec('curl '. $authCurl .' -XDELETE '.$url, $result);
            $this->line("DELETE:".print_r($result, true));
        }

        // elasticsearchのstationのmapping+インデックス作成
        $mapping = json_encode(config('mapping.station'));
        $cmd = sprintf("curl ". $authCurl ." -XPUT %s -H 'Content-Type: application/json' -d '%s' ", $url, $mapping);
        if (!$this->done($cmd)) {
            return false;
        }

        $authLoader = ($user && $password) ? sprintf("--http-auth %s:%s", $user, $password) : '';
        // elasticsearchへインポート
        $cmd = sprintf("elasticsearch_loader ". $authLoader ." --es-host %s:%d --index %s --type _doc csv %s",
            $elasticHost, $elasticPort, self::INDEX_STATION, $stationCsv);

        if (!$this->done($cmd)) {
            return false;
        }

        return true;
    }

    private function done(string $cmd, int $i=0) : bool
    {
        exec($cmd, $result);
        $this->line($cmd.",".print_r($result, true));

        if (count($result) < ($i+1)) {
            return false;
        }

        $decoded = json_decode($result[$i], true);
        if (!isset($decoded['acknowledged']) || !$decoded['acknowledged']) {
            return false;
        }

        return true;
    }
}
