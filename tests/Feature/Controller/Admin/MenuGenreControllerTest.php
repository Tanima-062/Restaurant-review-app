<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\MenuGenreAddRequest;
use App\Http\Requests\Admin\MenuGenreEditRequest;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuGenreControllerTest extends TestCase
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

    public function testEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menuName',
            'menuPublished',
            'appCd',
            'bigGenre',
            'middleGenres',
            'genreGroups',
            'id'
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menuName', 'テストメニュー');
        $response->assertViewHas('menuPublished', 0);
        $response->assertViewHas('appCd', 'RS');
        $response->assertViewHas('id', $menu->id);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menuName',
            'menuPublished',
            'appCd',
            'bigGenre',
            'middleGenres',
            'genreGroups',
            'id'
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menuName', 'テストメニュー');
        $response->assertViewHas('menuPublished', 0);
        $response->assertViewHas('appCd', 'RS');
        $response->assertViewHas('id', $menu->id);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menuName',
            'menuPublished',
            'appCd',
            'bigGenre',
            'middleGenres',
            'genreGroups',
            'id'
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menuName', 'テストメニュー');
        $response->assertViewHas('menuPublished', 0);
        $response->assertViewHas('appCd', 'RS');
        $response->assertViewHas('id', $menu->id);

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menuName',
            'menuPublished',
            'appCd',
            'bigGenre',
            'middleGenres',
            'genreGroups',
            'id'
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menuName', 'テストメニュー');
        $response->assertViewHas('menuPublished', 0);
        $response->assertViewHas('appCd', 'RS');
        $response->assertViewHas('id', $menu->id);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを保存しました');

        list($smallGenre11, $smallGenre12, $smallGenre13, $small2Genre21, $small2Genre22) = $genres;
        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(3, $result);
        $this->assertFalse(in_array($smallGenre11->id, array_column($result, 'genre_id')));     // 更新前のジャンルIDのデータはないこと
        $this->assertFalse(in_array($small2Genre21->id, array_column($result, 'genre_id')));    // 更新前のジャンルIDのデータはないこと
        $this->assertTrue(in_array($smallGenre12->id, array_column($result, 'genre_id')));      // 更新されていること
        $this->assertTrue(in_array($small2Genre22->id, array_column($result, 'genre_id')));     // 更新されていること
        $this->assertTrue(in_array($smallGenre13->id, array_column($result, 'genre_id')));      // 追加されていること

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを保存しました');

        list($smallGenre11, $smallGenre12, $smallGenre13, $small2Genre21, $small2Genre22) = $genres;
        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(3, $result);
        $this->assertFalse(in_array($smallGenre11->id, array_column($result, 'genre_id')));     // 更新前のジャンルIDのデータはないこと
        $this->assertFalse(in_array($small2Genre21->id, array_column($result, 'genre_id')));    // 更新前のジャンルIDのデータはないこと
        $this->assertTrue(in_array($smallGenre12->id, array_column($result, 'genre_id')));      // 更新されていること
        $this->assertTrue(in_array($small2Genre22->id, array_column($result, 'genre_id')));     // 更新されていること
        $this->assertTrue(in_array($smallGenre13->id, array_column($result, 'genre_id')));      // 追加されていること

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを保存しました');

        list($smallGenre11, $smallGenre12, $smallGenre13, $small2Genre21, $small2Genre22) = $genres;
        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(3, $result);
        $this->assertFalse(in_array($smallGenre11->id, array_column($result, 'genre_id')));     // 更新前のジャンルIDのデータはないこと
        $this->assertFalse(in_array($small2Genre21->id, array_column($result, 'genre_id')));    // 更新前のジャンルIDのデータはないこと
        $this->assertTrue(in_array($smallGenre12->id, array_column($result, 'genre_id')));      // 更新されていること
        $this->assertTrue(in_array($small2Genre22->id, array_column($result, 'genre_id')));     // 更新されていること
        $this->assertTrue(in_array($smallGenre13->id, array_column($result, 'genre_id')));      // 追加されていること

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを保存しました');

        list($smallGenre11, $smallGenre12, $smallGenre13, $small2Genre21, $small2Genre22) = $genres;
        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(3, $result);
        $this->assertFalse(in_array($smallGenre11->id, array_column($result, 'genre_id')));     // 更新前のジャンルIDのデータはないこと
        $this->assertFalse(in_array($small2Genre21->id, array_column($result, 'genre_id')));    // 更新前のジャンルIDのデータはないこと
        $this->assertTrue(in_array($smallGenre12->id, array_column($result, 'genre_id')));      // 更新されていること
        $this->assertTrue(in_array($small2Genre22->id, array_column($result, 'genre_id')));     // 更新されていること
        $this->assertTrue(in_array($smallGenre13->id, array_column($result, 'genre_id')));      // 追加されていること

        $this->logout();
    }

    public function testEditNotExists()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEditNotExists($menu, $genre);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルが存在しませんでした');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertTrue(in_array($genre->id, array_column($result, 'genre_id')));     // 更新されていないこと

        $this->logout();
    }

    public function testEditDuplication()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEditDuplication($menu, $genre);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', '既に登録済みです');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);                                                  // 追加されていないこと
        $this->assertTrue(in_array($genre->id, array_column($result, 'genre_id')));

        $this->logout();
    }

    public function testEditException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuGenreEditRequestのinput()呼び出しで例外発生させるようにする
        $menuGenreEditRequest = \Mockery::mock(MenuGenreEditRequest::class)->makePartial();
        $menuGenreEditRequest->shouldReceive('input')->andThrow(new \Exception());
        $this->app->instance(MenuGenreEditRequest::class, $menuGenreEditRequest);

        $response = $this->_callEditException($menu, $genre);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルを保存できませんでした');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(0, $result);                                         // 登録されていないこと

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.add');                    // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'middleGenres', 'bigGenre']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.add');                    // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'middleGenres', 'bigGenre']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.add');                    // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'middleGenres', 'bigGenre']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Genre.add');                    // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'middleGenres', 'bigGenre']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $smallGenre = null;
        $small2Genre = null;

        // 小ジャンル登録
        {
            $response = $this->_callAdd($menu, $smallGenre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(1, $result);                                                     // 1件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));  // 期待したgenreIdが登録されていること
        }

        // 小2ジャンル登録
        {
            $response = $this->_callAdd2($menu, $small2Genre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(2, $result);                                                     // 2件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));    // 期待したgenreIdが登録されていること
            $this->assertTrue(in_array($small2Genre->id, array_column($result, 'genre_id')));   // 期待したgenreIdが登録されていること
        }

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $smallGenre = null;
        $small2Genre = null;

        // 小ジャンル登録
        {
            $response = $this->_callAdd($menu, $smallGenre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(1, $result);                                                     // 1件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));  // 期待したgenreIdが登録されていること
        }

        // 小2ジャンル登録
        {
            $response = $this->_callAdd2($menu, $small2Genre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(2, $result);                                                     // 2件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));    // 期待したgenreIdが登録されていること
            $this->assertTrue(in_array($small2Genre->id, array_column($result, 'genre_id')));   // 期待したgenreIdが登録されていること
        }

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $smallGenre = null;
        $small2Genre = null;

        // 小ジャンル登録
        {
            $response = $this->_callAdd($menu, $smallGenre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(1, $result);                                                     // 1件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));  // 期待したgenreIdが登録されていること
        }

        // 小2ジャンル登録
        {
            $response = $this->_callAdd2($menu, $small2Genre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(2, $result);                                                     // 2件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));    // 期待したgenreIdが登録されていること
            $this->assertTrue(in_array($small2Genre->id, array_column($result, 'genre_id')));   // 期待したgenreIdが登録されていること
        }

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $smallGenre = null;
        $small2Genre = null;

        // 小ジャンル登録
        {
            $response = $this->_callAdd($menu, $smallGenre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(1, $result);                                                     // 1件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));  // 期待したgenreIdが登録されていること
        }

        // 小2ジャンル登録
        {
            $response = $this->_callAdd2($menu, $small2Genre);
            $response->assertStatus(302);                                           // リダイレクト
            $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');  // リダイレクト先
            $response->assertSessionHas('message', 'ジャンルを追加しました');

            // 登録されているかを確認
            $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
            $this->assertCount(2, $result);                                                     // 2件登録があること
            $this->assertTrue(in_array($smallGenre->id, array_column($result, 'genre_id')));    // 期待したgenreIdが登録されていること
            $this->assertTrue(in_array($small2Genre->id, array_column($result, 'genre_id')));   // 期待したgenreIdが登録されていること
        }

        $this->logout();
    }

    public function testAddNotExists()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddNotExists($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/add');   // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルが存在しませんでした');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(0, $result);

        $this->logout();
    }

    public function testAddDuplication()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddDuplication($menu, $genre);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/add');   // リダイレクト先
        $response->assertSessionHas('custom_error', '既に登録済みです');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);                                         // 1件登録されていること（2件あれば間違って登録されている事になる）
        $this->assertSame($genre->id, $result[0]['genre_id']);

        $this->logout();
    }

    public function testAddException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuGenreAddRequestのinput()呼び出しで例外発生させるようにする
        $menuGenreAddRequest = \Mockery::mock(MenuGenreAddRequest::class)->makePartial();
        $menuGenreAddRequest->shouldReceive('input')->andThrow(new \Exception());
        $this->app->instance(MenuGenreAddRequest::class, $menuGenreAddRequest);

        $response = $this->_callAddException($menu);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/genre/edit');   // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルを追加できませんでした');

        $result = GenreGroup::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(0, $result);                                         // 登録されていないこと

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);

        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // データが存在すること
        $this->assertNotNull(GenreGroup::find($genreGroup->id));

        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(GenreGroup::find($genreGroup->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);

        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // データが存在すること
        $this->assertNotNull(GenreGroup::find($genreGroup->id));

        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(GenreGroup::find($genreGroup->id));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);

        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // データが存在すること
        $this->assertNotNull(GenreGroup::find($genreGroup->id));

        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(GenreGroup::find($genreGroup->id));

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);

        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // データが存在すること
        $this->assertNotNull(GenreGroup::find($genreGroup->id));

        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(GenreGroup::find($genreGroup->id));

        $this->logout();
    }

    public function testMenuGenreControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $genre);
        $response->assertStatus(404);

        // target method delete
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);
        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testMenuGenreControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $genres);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $genre);
        $response->assertStatus(404);

        // target method delete
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre->id);
        $response = $this->_callDelete($genreGroup);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->published = $published;
        $store->save();
        return $store;
    }

    private function _createMenu($storeId, $published = 1)
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = 'RS';
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11111111';
        $menu->free_drinks = 0;
        $menu->published = $published;
        $menu->save();
        return $menu;
    }

    private function _createGenre($genreCd, $path, $level)
    {
        $genre = new Genre();
        $genre->app_cd = 'RS';
        $genre->genre_cd = $genreCd;
        $genre->path = $path;
        $genre->level = $level;
        $genre->published = 1;
        $genre->save();
        return $genre;
    }

    private function _createGenreGroup($menuId, $genreId)
    {
        $genreGroup = new GenreGroup();
        $genreGroup->menu_id = $menuId;
        $genreGroup->genre_id = $genreId;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();
        return $genreGroup;
    }

    private function _createStoreMenu($published = 0)
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published);
        return [$store, $menu];
    }

    private function _callEditForm($menu)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre1 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $middleGenre2 = $this->_createGenre('test2', '/b-cooking', 2);
        $smallGenre2 = $this->_createGenre('test2-1', '/b-cooking/test2', 3);
        $small2Genre2 = $this->_createGenre('test2-1-1', '/b-cooking/test2/test2-1', 4);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre1->id);                // 小ジャンル２がいないジャンル
        $genreGroup2 = $this->_createGenreGroup($menu->id, $small2Genre2->id);              // 小ジャンル２がいるジャンル
        $genreGroup2 = $this->_createGenreGroup($menu->id, null);                   // if (!$genre) の通過確認用

        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu?page=10'),
        ])->get('/admin/menu/' . $menu->id . '/genre/edit');
    }

    private function _callEdit($menu, &$genres)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $smallGenre12 = $this->_createGenre('test1-2', '/b-cooking/test1', 3);
        $smallGenre13 = $this->_createGenre('test1-3', '/b-cooking/test1', 3);
        $middleGenre2 = $this->_createGenre('test2', '/b-cooking', 2);
        $smallGenre21 = $this->_createGenre('test2-1', '/b-cooking/test2', 3);
        $small2Genre21 = $this->_createGenre('test2-1-1', '/b-cooking/test2/test2-1', 4);
        $small2Genre22 = $this->_createGenre('test2-1-2', '/b-cooking/test2/test2-1', 4);        // 更新用ジャンル
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre11->id);                   // 小ジャンル２がいないジャンル
        $genreGroup2 = $this->_createGenreGroup($menu->id, $small2Genre21->id);                  // 小ジャンル２がいるジャンル

        $genres = [$smallGenre11, $smallGenre12, $smallGenre13, $small2Genre21, $small2Genre22];

        return $this->post('/admin/menu/' . $menu->id . '/genre/edit', [
            'middle_genre' => ['test1', 'test2', 'test1'],
            'small_genre' => ['test1-2', 'test2-1', 'test1-3'],
            'small2_genre' => ['', 'test2-1-2', ''],
            'app_cd' => 'RS',
            'genre_group_id' => [$genreGroup->id, $genreGroup2->id, ''],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditNotExists($menu, &$genre)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre11->id);

        $genre = $smallGenre11;

        return $this->post('/admin/menu/' . $menu->id . '/genre/edit', [
            'middle_genre' => ['test1'],
            'small_genre' => ['test1-3'],
            'small2_genre' => [''],
            'app_cd' => 'RS',
            'genre_group_id' => [$genreGroup->id],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditDuplication($menu, &$genre)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre11->id);

        $genre = $smallGenre11;

        return $this->post('/admin/menu/' . $menu->id . '/genre/edit', [
            'middle_genre' => ['test1', 'test1'],
            'small_genre' => ['test1-1', 'test1-1'],
            'small2_genre' => ['', ''],
            'app_cd' => 'RS',
            'genre_group_id' => [$genreGroup->id, ''],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditException($menu, &$genre)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);

        $genre = $smallGenre11;

        return $this->post('/admin/menu/' . $menu->id . '/genre/edit', [
            'middle_genre' => ['test1'],
            'small_genre' => ['test1-1'],
            'small2_genre' => [''],
            'app_cd' => 'RS',
            'genre_group_id' => [''],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/genre/add');
    }

    private function _callAdd($menu, &$genre)
    {
        $middleGenre = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test1-1', '/b-cooking/test1', 3);

        $genre = $smallGenre;

        return $this->post('/admin/menu/' . $menu->id . '/genre/add', [
            'middle_genre' => 'test1',
            'small_genre' => 'test1-1',
            'small2_genre' => '',
            'app_cd' => 'RS',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAdd2($menu, &$genre)
    {
        $middleGenre = $this->_createGenre('test2', '/b-cooking', 2);
        $smallGenre = $this->_createGenre('test2-1', '/b-cooking/test2', 3);
        $small2Genre = $this->_createGenre('test2-1-1', '/b-cooking/test2/test2-1', 4);

        $genre = $small2Genre;

        return $this->post('/admin/menu/' . $menu->id . '/genre/add', [
            'middle_genre' => 'test2',
            'small_genre' => 'test2-1',
            'small2_genre' => 'test2-1-1',
            'app_cd' => 'RS',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotExists($menu)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);

        return $this->post('/admin/menu/' . $menu->id . '/genre/add', [
            'middle_genre' => 'test1',
            'small_genre' => 'test1-3',
            'small2_genre' => '',
            'app_cd' => 'RS',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddDuplication($menu, &$genre)
    {
        $middleGenre1 = $this->_createGenre('test1', '/b-cooking', 2);
        $smallGenre11 = $this->_createGenre('test1-1', '/b-cooking/test1', 3);
        $genreGroup = $this->_createGenreGroup($menu->id, $smallGenre11->id);   // 事前に登録しておく

        $genre = $smallGenre11;

        return $this->post('/admin/menu/' . $menu->id . '/genre/add', [
            'middle_genre' => 'test1',
            'small_genre' => 'test1-1',
            'small2_genre' => '',
            'app_cd' => 'RS',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddException($menu)
    {
        return $this->_callAdd($menu, $genre);
    }

    private function _callDelete($genreGroup)
    {
        return $this->post('/admin/menu/genre/delete/' . $genreGroup->id, [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
