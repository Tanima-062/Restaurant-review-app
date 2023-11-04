<?php

namespace App\Console\Commands;

use App\Models\Genre;
use App\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CacheRecommend extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:recommend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'make recommend cache from search_histories table';

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
    private function process()
    {
        try {
            $list = DB::table('search_histories')
            ->select('cooking_genre_cd', 'menu_genre_cd', DB::raw('(  (count(search_histories.id)  ) )  as `count` '))
            ->whereNotNull(['cooking_genre_cd'])
            ->orWhereNotNull(['menu_genre_cd'])
            ->groupBy('cooking_genre_cd', 'menu_genre_cd')
            ->orderBy('count', 'DESC')
            ->limit(config('takeout.batch.cacheRecommend.numberOfGenres'))
            ->get();

            // 対象データなし
            if (count($list) === 0) {
                return 2;
            }

            $menuGenreCd = $list->sortBy('count')->toArray();

            $recommendationForSearchApi = [];
            $recommendationForRecommendApi = [];
            foreach ($menuGenreCd as $genreCd) {
                $genre = null;
                $query = Menu::Query();
                $query->whereHas('genres', function ($query) use ($genreCd, &$genre) {
                    if (!empty($genreCd->cooking_genre_cd)) {
                        $query->where('genres.genre_cd', $genreCd->cooking_genre_cd);
                        $genre = $genreCd->cooking_genre_cd;
                    } elseif (!empty($genreCd->menu_genre_cd)) {
                        $rec = Genre::where('genre_cd', $genreCd->menu_genre_cd)->first();
                        $genre = $genreCd->menu_genre_cd;
                        if (!is_null($rec)) {
                            $query->Where('genres.path', '=', $rec->path.'/'.$genreCd->menu_genre_cd);
                        }
                    }
                });
                if (!empty($genre)) {
                    $res = $query->take(config('takeout.batch.cacheRecommend.numberOfMenusPerGenre'))->get()->toArray();
                    if (count($res) > 0) {
                        $recommendationForSearchApi[$genre] = $res;
                    }
                    $res = $query->orderBy('updated_at', 'desc')->take(config('takeout.batch.cacheRecommend.numberOfMenusPerGenre'))->get()->toArray();
                    if (count($res) > 0) {
                        $recommendationForRecommendApi[$genre] = $res;
                    }
                }
            }
            // redis上書き保存
            $recommendationForSearchApiEnc = json_encode($recommendationForSearchApi);

            Redis::set(config('takeout.batch.cacheRecommend.cache.nameSearchApi'), $recommendationForSearchApiEnc);

            // redisの中身を取得
            $recommendationForSearchApiResult = Redis::get(config('takeout.batch.cacheRecommend.cache.nameSearchApi'));
            // 上書きされてない場合は失敗
            if ($recommendationForSearchApiEnc !== $recommendationForSearchApiResult) {
                throw new \Exception('list cannot be saved.');
            }

            // redis上書き保存
            $recommendationForRecommendApiEnc = json_encode($recommendationForRecommendApi);
            Redis::set(config('takeout.batch.cacheRecommend.cache.nameRecommendApi'), $recommendationForRecommendApiEnc);

            // redisの中身を取得
            $recommendationForRecommendApiResult = Redis::get(config('takeout.batch.cacheRecommend.cache.nameRecommendApi'));
            // 上書きされてない場合は失敗
            if ($recommendationForRecommendApiEnc !== $recommendationForRecommendApiResult) {
                throw new \Exception('list cannot be saved.');
            }
            // 成功
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }

        return 1;
    }
}
