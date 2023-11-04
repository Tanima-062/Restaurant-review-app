<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use App\Libs\CommonLog;
use App\Models\Holiday;
use App\Models\OpeningHour;
use App\Models\CallReachJob;
use App\Models\Store;
use Exception;

class CallReach extends Command
{
    use BaseCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callReach:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send HTTP request to callReach';

    /**
     * @var App\Models\CallReachJob
     */
    protected $callReachJob;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        CallReachJob $callReachJob
    )
    {
        parent::__construct();
        $this->callReachJob = $callReachJob;
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

    /**
     * Execute the console command.
     *
     * @return int 0:正常終了 1:異常終了 2:対象データなし
     */
    private function process()
    {
        $baseUrl = config('const.callReach.url');
        $now = new Carbon();
        $env = \App::environment();
        $env_config = \Config::get($env);

        //営業時間外フロー
        $callReachClosedJobs = $this->callReachJob->getClosedJobsCount();
        if($callReachClosedJobs) {
            $flowId = $env_config['callReachFlowId']['CLOSED'];
            $url = $baseUrl. $flowId;
            foreach($callReachClosedJobs as $callReachClosedJob) {
                $store = Store::find($callReachClosedJob->store_id);
                if(!$store) {
                    CommonLog::notifyToChat(
                        '【CallReach：営業時間外フロー】店舗情報取得エラー　店舗ID：'. $callReachClosedJob->store_id,
                        $callReachClosedJob->store_id
                    );
                    continue;
                }
                $checkOpeningHoursResult = $this->checkOpeningHours($callReachClosedJob->store_id, $now);
                if(!$checkOpeningHoursResult) {
                    continue;
                }
                $data = [
                    "phone" => str_replace('-', '', $store->tel),       // ハイフンなしにする
                    "args" => [
                        "update_nums" => $callReachClosedJob->count,
                    ],
                ];
                try {
                    $result = $this->doRequest($url, $data);
                }catch (Exception $e) {
                    if($e->getCode() === 500) {
                        $this->callReachJob->updateServerErrorResponseByStoreId($callReachClosedJob->store_id, $data, $e);
                    }
                    if($e->getCode() !== 500) {
                        CommonLog::notifyToChat(
                            '【CallReach：営業時間外フロー】リクエストエラー　店舗ID：'. $callReachClosedJob->store_id,
                            $e->getMessage()
                        );
                        $this->callReachJob->updateErrorResponseByStoreId($callReachClosedJob->store_id, $data, $e);
                    }
                    sleep(1);
                    continue;
                }
                $res_data = json_decode($result->getBody(), true);
                $this->callReachJob->updateResponseByStoreId($callReachClosedJob->store_id, $data, $res_data);
                sleep(1);
            }
        }

        //営業時間内フロー
        $callReachJobs = $this->callReachJob->getJobs();
        if(!$callReachJobs) {
            return 2;
        }
        $callReachJobs->map(function ($callReachJob) use($baseUrl, $env_config, $now){
            // $flowId = $env_config['callReachFlowId'][$callReachJob->job_cd];
            $flowId = $env_config['callReachFlowId'][$callReachJob->job_cd];
            $url = $baseUrl. $flowId;
            $store = Store::find($callReachJob->store_id);
            if(!$store) {
                CommonLog::notifyToChat(
                    '【CallReach】店舗情報取得エラー　JobID：'. $callReachJob->id,
                    $callReachJob->store_id
                );
                return;
            }
            $openingHourCheckResult = $this->checkOpeningHours($callReachJob->store_id, $now);
            if(!$openingHourCheckResult) {
                $callReachJob->job_status = 'CLOSED';
                $callReachJob->save();
                return;
            }
            $data = [
                "phone" => str_replace('-', '', $store->tel),       // ハイフンなしにする
                "args" => [
                    "reservation_date" => $callReachJob->pick_up_datetime->format('m月d日H時i分'),
                    "name" => $callReachJob->name,
                    "reservation_nums" => $callReachJob->persons,
                ],
            ];
            try {
                $result = $this->doRequest($url, $data);
            }catch (Exception $e) {
                $res_data = json_decode($e->getResponse()->getBody(), true);
                if($e->getCode() !== 500) {
                    $this->callReachJob->updateServerErrorResponse($callReachJob, $res_data, $data, $e);
                }
                if($e->getCode() === 500) {
                    CommonLog::notifyToChat(
                        '【CallReach】リクエストエラー　JobID：'. $callReachJob->id,
                        $e->getMessage()
                    );
                    $this->callReachJob->updateErrorResponse($callReachJob, $res_data, $data, $e);
                }
                sleep(1);
                return;
            }
            $res_data = json_decode($result->getBody(), true);
            $this->callReachJob->updateResponse($callReachJob, $res_data, $data);
            sleep(1);
        });

        $this->callReachJob->updateStatusWithMaxCount();
        return 0;
    }

    /**
     * CallReachリクエスト実行
     *
     * @param string $url
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function doRequest(string $url, array $data): \Psr\Http\Message\ResponseInterface
    {
        $client = new Client();
        $options = [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer '. 'jLWr!zk7Wd80HGfiew$mi%0I',
                'Content-Type' => 'application/json',
            ]
        ];
        $response = $client->request('POST', $url, $options);

        return $response;
    }

    /**
     * 現在時刻が営業時間内か確認
     *
     * @param integer $id
     * @param Carbon $now
     * @return boolean
     */
    private function checkOpeningHours(int $id, Carbon $now): bool
    {
        $result = false;
        $openingHours = OpeningHour::where('store_id', $id)->get();
        [$week, $weekName] = $this->getWeek($now);

        foreach ($openingHours as $openingHour) {
            if ($openingHour->week[$week] !== '1') {
                continue;
            }
            if (
                !(strtotime($now->copy()->format('H:i:s')) >= strtotime($openingHour->start_at) && strtotime($now->copy()->format('H:i:s')) < strtotime($openingHour->end_at))
            ) {
                continue;
            }
            // 祝日休みの場合は今日が祝日かどうかチェック
            if ($week === '7' && $openingHour->week[7] !== '1') {
                $holiday = Holiday::where('date', $now->format('Y-m-d'))->first();
                if (!is_null($holiday)) {
                    // 祝日のため休み
                    continue;
                }
            }
            $result = true;
            break;
        }

        return $result;
    }

    public function getWeek($now)
    {
        $week = null;
        $weekName = '';
        switch (date('w', $now->timestamp)) {
                case 0:
                    $week = 6;
                    $weekName = '日曜日';
                    break;

                case 1:
                    $week = 0;
                    $weekName = '月曜日';
                    break;

                case 2:
                    $week = 1;
                    $weekName = '火曜日';
                    break;

                case 3:
                    $week = 2;
                    $weekName = '水曜日';
                    break;

                case 4:
                    $week = 3;
                    $weekName = '木曜日';
                    break;

                case 5:
                    $week = 4;
                    $weekName = '金曜日';
                    break;

                case 6:
                    $week = 5;
                    $weekName = '土曜日';
                    break;
            }

        return [$week, $weekName];
    }

    public function clientRequest() {

    }
}
