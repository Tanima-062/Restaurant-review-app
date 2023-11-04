<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GenreTest extends TestCase
{
    private $genre;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->genre = new Genre();

        $this->_createGenre();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testScopeGetListByPath()
    {
        $result = $this->genre::GetListByPath('TO', 'test', 3)->get();
        $this->assertIsObject($result);
    }

    public function testScopeGetStartWithPath()
    {
        $result = $this->genre::GetStartWithPath('TO', 'test', 3)->get();
        $this->assertIsObject($result);
    }

    public function testScopeAdminSearchFilter()
    {
        $valid = [
            'name' => 'test',
            'genre_cd' => 'test',
            'app_cd' => 'TO',
            'path' => 'test',
        ];
        $result = $this->genre::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
    }

    public function testScopeGetGenreMenu()
    {
        $result = $this->genre::GetGenreMenu('/test', 'TO', 'test3')->get();
        $this->assertIsObject($result);
    }

    private function _createGenre()
    {
        $genreLevel2 = new Genre();
        $genreLevel2->name = 'test';
        $genreLevel2->genre_cd = 'test2';
        $genreLevel2->app_cd = 'TORS';
        $genreLevel2->level = 2;
        $genreLevel2->published = 1;
        $genreLevel2->path = '/test';                           // path::/test
        $genreLevel2->save();

        $genreLevel3 = new Genre();
        $genreLevel3->name = 'test3';
        $genreLevel3->genre_cd = 'test3';
        $genreLevel3->app_cd = 'TORS';
        $genreLevel3->level = 3;
        $genreLevel3->published = 1;
        $genreLevel3->path = $genreLevel2->path. '/test2';  // path::/test/test2
        $genreLevel3->save();

        $genreLevel4 = new Genre();
        $genreLevel4->name = 'test4a';
        $genreLevel4->genre_cd = 'test4a';
        $genreLevel4->app_cd = 'TORS';
        $genreLevel4->level = 4;
        $genreLevel4->published = 1;
        $genreLevel4->path = $genreLevel3->path. '/test3';  // path::/test/test2/test3
        $genreLevel4->save();

        $genreLevel4 = new Genre();
        $genreLevel4->name = 'testab';
        $genreLevel4->genre_cd = 'test4b';
        $genreLevel4->app_cd = 'TORS';
        $genreLevel4->level = 4;
        $genreLevel4->published = 1;
        $genreLevel4->path = $genreLevel3->path. '/test3';  // path::/test/test2/test3
        $genreLevel4->save();

    }
}
