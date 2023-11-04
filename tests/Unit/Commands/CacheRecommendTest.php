<?php

namespace Tests\Unit\Commands;

use App\Models\SearchHistory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacheRecommendTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCacheRecommend()
    {
        // 対象データあり
        // バッチ実行
        $this->artisan('cache:recommend')
            ->expectsOutput('[CacheRecommend] ##### START #####')
            ->expectsOutput(0);

        // 対象データなし skipパターン
        {
            // 対象データをなくす
            $searchHistories = SearchHistory::whereNotNull(['cooking_genre_cd'])
                ->orWhereNotNull(['menu_genre_cd'])
                ->delete();

            // バッチ実行
            $this->artisan('cache:recommend')
                ->expectsOutput('[CacheRecommend] ##### START #####')
                ->expectsOutput(0);
        }
    }
}
