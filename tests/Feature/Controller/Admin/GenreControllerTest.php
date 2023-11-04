<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\GenreRequest;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class GenreControllerTest extends TestCase
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

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Genre.index');   // 指定bladeを確認
        $response->assertViewHasAll(['genres']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();     // クライアント管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Genre.index');   // 指定bladeを確認
        $response->assertViewHasAll(['genres']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testAddForm()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Genre.add');     // 指定bladeを確認

        $this->logout();
    }

    public function testAdd()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // データが登録されていないことを確認
        $this->assertFalse(Genre::where('genre_cd', 'm-abcd123')->exists());

        $response = $this->_callAdd();
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('message', 'ジャンル「テストジャンル」を作成しました');

        // データが登録されていることを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd123')->exists());

        $this->logout();
    }

    public function testAddDeplication()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // 事前に重複用データを用意する
        $genre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);

        // データが一件であることを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd123')->exists());
        $this->assertSame(1, Genre::where('genre_cd', 'm-abcd123')->get()->count());

        $response = $this->_callAdd();
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンル「テストジャンル」を作成出来ませんでした。重複しています。');

        // データが増えていないことを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd123')->exists());
        $this->assertSame(1, Genre::where('genre_cd', 'm-abcd123')->get()->count());

        $this->logout();
    }

    public function testAddThrowable()
    {
        // GenreRequestのexcept()呼び出しで例外発生させるようにする
        $genreRequest = \Mockery::mock(GenreRequest::class)->makePartial();
        $genreRequest->shouldReceive('except')->andThrow(new \Exception());
        $genreRequest->shouldReceive('input')->andReturn('');
        $genreRequest->shouldReceive('all')->andReturn('');
        $this->app->instance(GenreRequest::class, $genreRequest);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAdd();
        $response->assertStatus(302);
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンル「」を作成できませんでした');  // モックでジャンル名を渡せていないため空になっている

        $this->logout();
    }

    public function testListWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testListWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testListWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();     // クライアント管理者としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testListWithClientGeneral()
    {
        $this->loginWithClientGeneral();     // クライアント一般としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testListWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();     // 社外一般権限としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testListWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();     // 精算管理会社としてログイン

        $smallGenre = null;
        $response = $this->_callList($smallGenre);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallGenre->toArray()]]);

        $this->logout();
    }

    public function testEditForm()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEditForm($genre);
        $response->assertStatus(200);                                                                                   // アクセス確認
        $response->assertViewIs('admin.Genre.edit');                                                                     // 指定bladeを確認
        $response->assertViewHasAll(['genre', 'bigGenre', 'middleGenre', 'middleGenres', 'smallGenre', 'smallGenres']);    // bladeに渡している変数を確認
        $response->assertViewHas('genre', $genre);

        $this->logout();
    }

    public function testEdit()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $middleGenre = $this->_createGenre('/b-cooking/m-abcd123', 's-abcd123', 3);
        $smallGenre = $this->_createGenre('/b-cooking/m-abcd123/s-abcd123', 'i-abcd123', 4);

        // 変更前データを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd123')->exists());
        $this->assertFalse(Genre::where('genre_cd', 'm-abcd456')->exists());

        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('message', 'ジャンル「テストジャンル」を更新しました');

        // データが変更されていることを確認
        $this->assertFalse(Genre::where('genre_cd', 'm-abcd123')->exists());
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd456')->exists());

        $this->logout();
    }

    public function testEdit2()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $middleGenre = $this->_createGenre('/b-cooking/m-abcd123', 's-abcd123', 3);
        $smallGenre = $this->_createGenre('/b-cooking/m-abcd123/s-abcd123', 'i-abcd123', 4);

        // 変更前データを確認
        $this->assertTrue(Genre::where('genre_cd', 'i-abcd123')->exists());
        $this->assertFalse(Genre::where('genre_cd', 'i-abcd456')->exists());

        // 小ジャンルも変更できるか確認する
        // private function makePath用
        $response = $this->_callEdit2($smallGenre);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('message', 'ジャンル「テストジャンル」を更新しました');

        // データが変更されていることを確認
        $this->assertFalse(Genre::where('genre_cd', 'i-abcd123')->exists());
        $this->assertTrue(Genre::where('genre_cd', 'i-abcd456')->exists());

        $this->logout();
    }

    public function testEditDeplication()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $bigGenre2 = $this->_createGenre('/b-cooking', 'm-abcd456', 2);

        // 変更前データを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd456')->exists());
        $this->assertSame(1, Genre::where('genre_cd', 'm-abcd456')->get()->count());

        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/genre');    // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンル「テストジャンル」を更新出来ませんでした。重複しています。');

        // データが変更されていないことを確認
        $this->assertTrue(Genre::where('genre_cd', 'm-abcd456')->exists());
        $this->assertSame(1, Genre::where('genre_cd', 'm-abcd456')->get()->count());    // 2つになっていたら更新されてしまっている

        $this->logout();
    }

    public function testGenreControllerWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($genre);
        $response->assertStatus(404);

        // target method editForm
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testGenreControllerWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();     // クライアント管理者としてログイン

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(403);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(403);

        // target method editForm
        $response = $this->_callEditForm($genre);
        $response->assertStatus(403);

        // target method editForm
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(403);

        $this->logout();
    }

    public function testGenreControllerWithClientGeneral()
    {
        $this->loginWithClientGeneral();     // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($genre);
        $response->assertStatus(404);

        // target method editForm
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testGenreControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();     // 社外一般権限としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($genre);
        $response->assertStatus(404);

        // target method editForm
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testGenreControllerWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();     // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($genre);
        $response->assertStatus(404);

        // target method editForm
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $response = $this->_callEdit($bigGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createGenre($path, $genreCd, $level)
    {
        $genre = new Genre();
        $genre->app_cd = 'TORS';
        $genre->path = $path;
        $genre->genre_cd = $genreCd;
        $genre->level = $level;
        $genre->save();
        return $genre;
    }

    private function _callIndex()
    {
        return $this->get('/admin/genre');
    }

    private function _callAddForm()
    {
        return $this->get('/admin/genre/add');
    }

    private function _callAdd()
    {
        return $this->post('/admin/genre/add', [
            'big_genre' => 'b-cooking',        // b-cooking or b-detailed
            'middle_genre' => '',
            'small_genre' => '',
            'app_cd' => 'TO',
            'name' => 'テストジャンル',
            'genre_cd' => 'm-abcd123',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callList(&$smallGenre)
    {
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $middleGenre = $this->_createGenre('/b-cooking/m-abcd123', 's-abcd123', 3);
        $smallGenre = $this->_createGenre('/b-cooking/m-abcd123/s-abcd123', 'i-abcd123', 4);

        return $this->get('/admin/common/genre/list?genre_cd=' . $middleGenre->genre_cd . '&parent_value=' . $bigGenre->genre_cd . '&app_cd=TORS&&level=4');
    }

    private function _callEditForm(&$smallGenre)
    {
        $bigGenre = $this->_createGenre('/b-cooking', 'm-abcd123', 2);
        $middleGenre = $this->_createGenre('/b-cooking/m-abcd123', 's-abcd123', 3);
        $smallGenre = $this->_createGenre('/b-cooking/m-abcd123/s-abcd123', 'i-abcd123', 4);

        return $this->get('/admin/genre/' . $smallGenre->id . '/edit');
    }

    private function _callEdit($genre)
    {
        return $this->post('/admin/genre/' . $genre->id . '/edit', [
            'big_genre' => 'b-cooking',        // b-cooking or b-detailed
            'middle_genre' => '',
            'small_genre' => '',
            'app_cd' => 'TORS',
            'name' => 'テストジャンル',
            'genre_cd' => 'm-abcd456',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEdit2($genre)
    {
        return $this->post('/admin/genre/' . $genre->id . '/edit', [
            'big_genre' => 'b-cooking',        // b-cooking or b-detailed
            'middle_genre' => 'm-abcd123',
            'small_genre' => 's-abcd123',
            'app_cd' => 'TORS',
            'name' => 'テストジャンル',
            'genre_cd' => 'i-abcd456',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
