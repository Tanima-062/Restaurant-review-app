<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenreGroupTest extends TestCase
{
    private $genreGroup;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->genreGroup = new GenreGroup();

        $this->_createGenreGroup();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testGetCookingGenreByStoreId()
    {
        // 店舗ジャンル呼び出し
        $result = $this->genreGroup->getCookingGenreByStoreId($this->testStoreId);
        $this->assertCount(3, $result); // 3件取れる

        // 店舗ジャンル呼び出し(level3以降のデータ取得：該当データあり)
        $result = $this->genreGroup->getCookingGenreByStoreId($this->testStoreId, '/test/test2');
        $this->assertCount(3, $result); // ３件取れる

        // 店舗ジャンル呼び出し(level3以降のデータ取得：該当データなし)
        $result = $this->genreGroup->getCookingGenreByStoreId($this->testStoreId, '/test/test2aaa');
        $this->assertCount(0, $result); // 0件
    }

    private function _createGenreGroup()
    {
        $store = new Store();
        $store->save();
        $this->testStoreId = $store->id;

        $genreLevel2 = new Genre();
        $genreLevel2->level = 2;
        $genreLevel2->genre_cd = 'test2';
        $genreLevel2->published = 1;
        $genreLevel2->path = '/test';                           // path::/test
        $genreLevel2->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $this->testStoreId;
        $genreGroup->genre_id = $genreLevel2->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();

        $genreLevel3 = new Genre();
        $genreLevel3->level = 3;
        $genreLevel3->genre_cd = 'test3';
        $genreLevel3->published = 1;
        $genreLevel3->path = $genreLevel2->path. '/test2';  // path::/test/test2
        $genreLevel3->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $this->testStoreId;
        $genreGroup->genre_id = $genreLevel3->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();

        $genreLevel4 = new Genre();
        $genreLevel4->level = 4;
        $genreLevel4->genre_cd = 'test4a';
        $genreLevel4->published = 1;
        $genreLevel4->path = $genreLevel3->path. '/test3';  // path::/test/test2/test3
        $genreLevel4->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $this->testStoreId;
        $genreGroup->genre_id = $genreLevel4->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();

        $genreLevel4 = new Genre();
        $genreLevel4->level = 4;
        $genreLevel4->genre_cd = 'test4b';
        $genreLevel4->published = 1;
        $genreLevel4->path = $genreLevel3->path. '/test3';  // path::/test/test2/test3
        $genreLevel4->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $this->testStoreId;
        $genreGroup->genre_id = $genreLevel4->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();
    }
}
