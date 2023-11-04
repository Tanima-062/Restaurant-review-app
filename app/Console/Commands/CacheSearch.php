<?php

namespace App\Console\Commands;

use App\Modules\BaseApi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CacheSearch extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create cache for search api';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //if (!App::environment('local')) {
        $this->_create();
        //}

        return;
    }

    private function _create()
    {
        // 午前09:00〜23:45までの受け取り時間でキャッシュ生成
        $startTime = 9;
        $endTime = 24;

        $now = new Carbon();
        $today = $now->copy()->format('Y-m-d');

        $appUrl = env('APP_URL', null);
        if (\App::environment('local')) {
            // localは固定
            $appUrl = 'https://dev-gourmet-api.skyticket.jp';
        }

        $minutesPattern = [0, 15, 30, 45];
        $pagesPattern = [1, 2, 3, 4, 5];
        for ($x = $startTime; $x < $endTime; ++$x) {
            try {
                for ($i = 0; $i < count($minutesPattern); ++$i) {
                    $pickUpTime = new Carbon($today);

                    $pickUpTime = $pickUpTime->addHours($x);
                    $tmpPickUpTime = $pickUpTime->addMinutes($minutesPattern[$i]);
                    // 受け取り時間が過去のキャッシュは作らない
                    if ($tmpPickUpTime->isPast()) {
                        continue;
                    }
                    // 受け取り時間検索
                    for ($ii = 0; $ii < count($pagesPattern); ++$ii) {
                        $url = sprintf($appUrl.'/v1/ja/gourmet/takeout/search?pickUpDate=%s&page=%s&pickUpTime=%s', $today, $pagesPattern[$ii], $tmpPickUpTime->format('H:i'));
                        \Log::info($url);
                        $curlClass = new BaseApi($url);
                        $res = $curlClass->get();

                        if (isset($res['searchResult']['genres']) && count($res['searchResult']['genres']) > 0) {
                            // cookingGenreCd検索
                            foreach ($res['searchResult']['genres'] as $val) {
                                $url = sprintf($appUrl.'/v1/ja/gourmet/takeout/search?pickUpDate=%s&page=%s&pickUpTime=%s&cookingGenreCd=%s&menuGenreCd=', $today, $pagesPattern[$ii], $tmpPickUpTime->format('H:i'), $val['genreCd']);
                                \Log::info($url);
                                $curlClass = new BaseApi($url);
                                $res = $curlClass->get();
                                if (isset($res['searchResult']['genres']) && count($res['searchResult']['genres']) > 0) {
                                    // menuGenreCd検索
                                    foreach ($res['searchResult']['genres'] as $v) {
                                        $url = sprintf($appUrl.'/v1/ja/gourmet/takeout/search?pickUpDate=%s&page=%s&pickUpTime=%s&cookingGenreCd=%s&menuGenreCd=%s', $today, $pagesPattern[$ii], $tmpPickUpTime->format('H:i'), $val['genreCd'], $v['genreCd']);
                                        \Log::info($url);
                                        $curlClass = new BaseApi($url);
                                        $res = $curlClass->get();
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // 途中でエラーになっても続ける
                \Log::error($e);
            }
        }
    }
}
