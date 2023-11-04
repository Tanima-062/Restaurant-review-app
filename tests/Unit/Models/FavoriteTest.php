<?php

namespace Tests\Unit\Models;

use App\Models\Favorite;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    private $favorite;
    private $testFavoriteId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->favorite = new Favorite();

        $this->_createFavorite();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testRegisterFavorite()
    {
        // お気に入り追加成功（既存レコード）
        $msg = '';
        $this->assertTrue($this->favorite->registerFavorite(1, 3, 'RS', $msg));
        $this->assertSame('', $msg);

        // お気に入り追加成功(新規レコード)
        $msg = '';
        $this->assertTrue($this->favorite->registerFavorite(1, 4, 'TO', $msg));
        $this->assertSame('', $msg);

        // お気に入り追加エラー(上限変更)
        Config::set('restaurant.favorite.limit', 2);
        $this->assertFalse($this->favorite->registerFavorite(1, 5, 'RS', $msg));
        $this->assertSame(\Lang::get('message.favoriteLimit'), $msg);

        // お気に入り追加失敗（例外エラー）
        {
            try {
                $favorite = $this->favorite::find($this->testFavoriteId);
                $favorite->list = 'aaaaaaaaaa';
                $favorite->save();
                $result = $this->favorite->registerFavorite(1, 3, 'RS', $msg);
            } catch (Exception $e) {
                $this->assertSame('', $msg);
            }
        }
    }

    public function testDeleteFavorite()
    {
        // お気に入り削除成功（対象外のidは残る）
        $this->assertTrue($this->favorite->deleteFavorite(1, 2, 'RS'));
        $result = Favorite::find($this->testFavoriteId);
        $this->assertSame('[{"id":1}]', $result->list);

        // お気に入り削除成功
        $this->assertTrue($this->favorite->deleteFavorite(1, 1, 'RS'));
        $result = Favorite::find($this->testFavoriteId);
        $this->assertSame('[]', $result->list);

        // お気に入り削除失敗（例外エラー）
        {
            try {
                $favorite = $this->favorite::find($this->testFavoriteId);
                $favorite->list = 'aaaaaaaaaa';
                $favorite->save();
                $result = $this->favorite->deleteFavorite(1, 1, 'RS');
                $this->assertTrue(false);   // 上記処理で例外エラー発生するため、ここは通過しない
            } catch (Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function testGetFavoriteIds()
    {
        $result = $this->favorite->getFavoriteIds(1, 'RS');
        $this->assertIsArray($result);
        $this->assertContains(1, $result);
        $this->assertSame(1, $result[0]);
    }

    public function testIsLimit()
    {
        $favorite = $this->favorite::find($this->testFavoriteId);

        // 上限チェックOK
        $limit = 0;
        Config::set('restaurant.favorite.limit', 100);
        $this->assertFalse($this->favorite->isLimit($favorite, $limit));
        $this->assertSame(100, $limit);

        // 上限チェックNG
        $limit = 0;
        Config::set('restaurant.favorite.limit', 1);
        $this->assertTrue($this->favorite->isLimit($favorite, $limit));
        $this->assertSame(1, $limit);
    }

    private function _createFavorite()
    {
        $favorite = new Favorite();
        $favorite->user_id = 1;
        $favorite->list = '[{"id":1}]';
        $favorite->app_cd = 'RS';
        $favorite->save();
        $this->testFavoriteId = $favorite->id;
    }
}
