<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Controllers\Admin\AreaController;
use App\Models\Area;
use Illuminate\Support\Facades\DB;
// use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\Feature\Controller\Admin\TestCase;

class AreaControllerTest extends TestCase
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

    public function testIndex()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Area.index');    // 指定bladeを確認
        $response->assertViewHasAll(['areas']);         // bladeに渡している変数を確認
        $this->logout();
    }

    public function testAddForm()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // oldヘルパーの値が未入力
        $response = $this->get('/admin/area/add');                  // テストページの取得
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Area.add');                  // 指定bladeを確認
        $response->assertViewHasAll(['bigAreas', 'middleAreas']);   // bladeに渡している変数を確認

        // oldヘルパーの値が入力あり
        $response = $this->withSession([
            '_old_input' => [
                'big_area' => 'test-big-area',
            ],
        ])->get('/admin/area/add');                                 // oldヘルパーに値を設定し、テストページを取得
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Area.add');                  // 指定bladeを確認
        $response->assertViewHas(['bigAreas', 'middleAreas']);      // bladeに渡している変数を確認

        $this->logout();
    }

    public function testAdd()
    {
        // 実行前にデータがないことを確認
        $result = Area::where('area_cd', 'testa')->get();
        $this->assertCount(0, $result);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // CSRF用ミドルウェアに引っかからないようにtoken発行して設定する方法（Featureテストはこの方法で実装しておく）
        {
            $response = $this->_callAdd();
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアa」を作成しました');

            // データが登録されたことを確認
            $result = Area::where('area_cd', 'testa')->get();
            $this->assertCount(1, $result);
        }

        // CSRFチェックを無効化して、呼び出す方法（テストとしてはこの方法もありかと思うので、念のため残しておく）
        // {
        //     // データが登録されたことを確認
        //     $result = Area::where('area_cd', 'testb')->get();
        //     $this->assertCount(0, $result);
        //     $response = $this->withoutMiddleware(VerifyCsrfToken::class)
        //         ->post('/admin/area/add', [
        //             'big_area' => '',
        //             'middle_area' => '',
        //             'name' => 'テストエリアb',
        //             'area_cd' => 'testb',
        //             'weight' => '',
        //             'sort' => '',
        //             '_token' => session()->get('_token'),   // 発行したtokenを設定
        //         ]);                                         // テストページの取得
        //     $response->assertStatus(302);                           // リダイレクト
        //     // データが登録されたことを確認
        //     $result = Area::where('area_cd', 'testb')->get();
        //     $this->assertCount(1, $result);
        // }

        $this->logout();
    }

    public function testAddThrowable()
    {
        $areaController = \Mockery::mock(AreaController::class)->makePartial();     // ControllerのmakePath()呼び出しで例外発生させるようにする
        $areaController->shouldReceive('makePath')->andThrow(new \Exception());
        $this->app->instance(AreaController::class, $areaController);

        // 実行前にデータがないことを確認
        $result = Area::where('area_cd', 'testa')->get();
        $this->assertCount(0, $result);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        session()->regenerateToken();                       // CSRF用ミドルウェアに引っかからないようにtoken発行
        $response = $this->post('/admin/area/add', [
            'big_area' => '',
            'middle_area' => '',
            'name' => 'テストエリアa',
            'area_cd' => 'testa',
            'weight' => '',
            'sort' => '',
            '_token' => session()->get('_token'),           // 発行したtokenを設定
        ]);                                                 // テストページの取得
        $response->assertStatus(302);                       // リダイレクト
        $response->assertRedirect('/admin/area');
        $response->assertSessionHas('custom_error', 'エリア「テストエリアa」を作成できませんでした');

        // データが登録されていないことを確認
        $result = Area::where('area_cd', 'testa')->get();
        $this->assertCount(0, $result);

        $this->logout();
    }

    public function testEditForm()
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        $middleArea = $this->_createArea('test-middle-area', '/test-big-area', 2);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // oldヘルパーの値が未入力
        $response = $this->get('/admin/area/' . $middleArea->id . '/edit');                                     // テストページの取得
        $response->assertStatus(200);                                                                           // アクセス確認
        $response->assertViewIs('admin.Area.edit');                                                             // 指定bladeを確認
        $response->assertViewHasAll(['area', 'bigArea', 'bigAreas', 'middleArea', 'middleAreas', 'smallArea']); // bladeに渡している変数を確認

        // oldヘルパーの値が入力あり
        $response = $this->withSession([
            '_old_input' => [
                'big_area' => 'test-big-area',
            ],
        ])->get('/admin/area/' . $middleArea->id . '/edit');                                                    // oldヘルパーに値を設定し、テストページを取得
        $response->assertStatus(200);                                                                           // アクセス確認
        $response->assertViewIs('admin.Area.edit');                                                             // 指定bladeを確認
        $response->assertViewHasAll(['area', 'bigArea', 'bigAreas', 'middleArea', 'middleAreas', 'smallArea']); // bladeに渡している変数を確認

        $this->logout();
    }

    public function testEdit()
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        $bigArea2 = $this->_createArea('test-big-area2', '/', 1);
        $middleArea = $this->_createArea('test-middle-area', '/test-big-area', 2);
        $middleArea2 = $this->_createArea('test-middle-area2', '/test-big-area', 2);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // 正常（同じレベルで更新）
        {
            $this->assertTrue(Area::where('area_cd', 'test-middle-area')->exists());
            $this->assertFalse(Area::where('area_cd', 'testa')->exists());

            $response = $this->post('/admin/area/' . $middleArea->id . '/edit', [
                'big_area' => 'test-big-area',
                'middle_area' => '',
                'name' => 'テストエリアa',
                'area_cd' => 'testa',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $middleArea->area_cd,
                'old_area_path' => $middleArea->path,
                'old_area_level' => $middleArea->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアa」を更新しました');

            // データが更新されたことを確認
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());
            $this->assertTrue(Area::where('area_cd', 'testa')->exists());
            $result = Area::where('area_cd', 'testa')->get();
            $this->assertCount(1, $result);
            $this->assertSame($middleArea->id, $result[0]['id']);
            $this->assertSame('テストエリアa', $result[0]['name']);
        }

        // 正常（違うレベル(1->2)で更新）
        {
            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area3')->exists());

            $response = $this->post('/admin/area/' . $bigArea2->id . '/edit', [
                'big_area' => 'test-big-area',
                'middle_area' => '',
                'name' => 'テストエリアb',
                'area_cd' => 'test-middle-area3',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $bigArea2->area_cd,
                'old_area_path' => $bigArea2->path,
                'old_area_level' => $bigArea2->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアb」を更新しました');

            // データが更新されたことを確認
            $this->assertFalse(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertTrue(Area::where('area_cd', 'test-middle-area3')->exists());
            $result = Area::where('area_cd', 'test-middle-area3')->get();
            $this->assertCount(1, $result);
            $this->assertSame($bigArea2->id, $result[0]['id']);
            $this->assertSame('テストエリアb', $result[0]['name']);
            $this->assertSame('/test-big-area', $result[0]['path']);
        }

        // 正常（違うレベル(2->3)で更新）
        {
            $this->assertTrue(Area::where('area_cd', 'test-middle-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-small-area')->exists());

            $response = $this->post('/admin/area/' . $middleArea2->id . '/edit', [
                'big_area' => 'test-big-area',
                'middle_area' => 'testa',
                'name' => 'テストエリアc',
                'area_cd' => 'test-small-area',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $middleArea->area_cd,
                'old_area_path' => $middleArea->path,
                'old_area_level' => $middleArea->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアc」を更新しました');

            // データが更新されたことを確認
            $this->assertFalse(Area::where('area_cd', 'test-middle-area2')->exists());
            $this->assertTrue(Area::where('area_cd', 'test-small-area')->exists());
            $result = Area::where('area_cd', 'test-small-area')->get();
            $this->assertCount(1, $result);
            $this->assertSame($middleArea2->id, $result[0]['id']);
            $this->assertSame('テストエリアc', $result[0]['name']);
            $this->assertSame('/test-big-area/testa', $result[0]['path']);
        }

        $this->logout();
    }

    public function testEditCustomError()
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        $bigArea2 = $this->_createArea('test-big-area2', '/', 1);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // 存在しないpath(階層が)
        {
            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());

            $response = $this->post('/admin/area/' . $bigArea2->id . '/edit', [
                'big_area' => 'test-big-area/error',                                // DBに存在しない値
                'middle_area' => 'test-middle-area',                                // DBに存在しない値
                'name' => 'テストエリアa',
                'area_cd' => 'test-middle-area',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $bigArea2->area_cd,
                'old_area_path' => $bigArea2->path,
                'old_area_level' => $bigArea2->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('custom_error', 'エリア「テストエリアa」を更新出来ませんでした。更新するためのPATH「/test-big-area/error/test-middle-area」が存在しません。');

            // データが更新されていないとを確認
            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());
        }

        // 存在しないpath2
        {
            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());

            $response = $this->post('/admin/area/' . $bigArea2->id . '/edit', [
                'big_area' => 'test-big-area-error',                                // DBに存在しない値
                'middle_area' => '',
                'name' => 'テストエリアa',
                'area_cd' => 'test-middle-area',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $bigArea2->area_cd,
                'old_area_path' => $bigArea2->path,
                'old_area_level' => $bigArea2->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('custom_error', 'エリア「テストエリアa」を更新出来ませんでした。更新するためのPATH「/test-big-area-error」が存在しません。');

            // データが更新されていないとを確認
            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());
        }

        $this->logout();
    }

    public function testEditTogetherChildArea()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // 中エリア変更(非公開）
        {
            $bigArea = $this->_createArea('test-big-area', '/', 1);
            $middleArea = $this->_createArea('test-middle-area', '/test-big-area', 2);
            $smallArea = $this->_createArea('test-small-area', '/test-big-area/test-middle-area', 3);

            $this->assertTrue(Area::where('area_cd', 'test-middle-area')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-middle-area-1')->exists());

            $response = $this->post('/admin/area/' . $middleArea->id . '/edit', [
                'big_area' => 'test-big-area',
                'middle_area' => '',
                'name' => 'テストエリアa',
                'area_cd' => 'test-middle-area-1',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $middleArea->area_cd,
                'old_area_path' => $middleArea->path,
                'old_area_level' => $middleArea->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアa」を更新しました');

            // データが更新されたことを確認
            $this->assertFalse(Area::where('area_cd', 'test-middle-area')->exists());
            $this->assertTrue(Area::where('area_cd', 'test-middle-area-1')->exists());
            $result = Area::where('area_cd', 'test-middle-area-1')->first();
            $this->assertSame($middleArea->id, $result->id);                            // 対象データであること
            $this->assertSame('テストエリアa', $result->name);                            // エリア名が変わっていること
            $result2 = Area::find($smallArea->id);
            $this->assertSame('/test-big-area/test-middle-area-1', $result2->path);     // 親エリアのコード変更に伴いpathが変更されていること
        }

        // 大エリア変更(公開）
        {
            $bigArea2 = $this->_createArea('test-big-area2', '/', 1, 1);
            $middleArea2 = $this->_createArea('test-middle-area2', '/test-big-area2', 2, 1);

            $this->assertTrue(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-big-area2-1')->exists());

            $response = $this->post('/admin/area/' . $bigArea2->id . '/edit', [
                'big_area' => '',
                'middle_area' => '',
                'name' => 'テストエリアb',
                'area_cd' => 'test-big-area2-1',
                'weight' => '',
                'sort' => '',
                'published' => 1,
                'old_area_cd' => $bigArea2->area_cd,
                'old_area_path' => $bigArea2->path,
                'old_area_level' => $bigArea2->level,
                '_token' => $this->makeSessionToken(),          // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('message', 'エリア「テストエリアb」を更新しました');

            // データが更新されたことを確認
            $this->assertFalse(Area::where('area_cd', 'test-big-area2')->exists());
            $this->assertTrue(Area::where('area_cd', 'test-big-area2-1')->exists());
            $result = Area::where('area_cd', 'test-big-area2-1')->first();
            $this->assertSame($bigArea2->id, $result->id);                          // 対象データであること
            $this->assertSame('テストエリアb', $result->name);                        // エリア名が変わっていること
            $result2 = Area::find($middleArea2->id);
            $this->assertSame('/test-big-area2-1', $result2->path);                 // 親エリアのコード変更に伴いpathが変更されていること
        }

        $this->logout();
    }

    public function testEditExecption()
    {
        $areaController = \Mockery::mock(AreaController::class)->makePartial();     // ControllerのmakePath()呼び出しで例外発生させるようにする
        $areaController->shouldReceive('setLevel')->andThrow(new \Exception());
        $this->app->instance(AreaController::class, $areaController);

        $bigArea = $this->_createArea('test-big-area', '/', 1);

        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // 例外発生し、データ更新されていないこと
        {
            $this->assertTrue(Area::where('area_cd', 'test-big-area')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-big-area-1')->exists());

            $response = $this->post('/admin/area/' . $bigArea->id . '/edit', [
                'big_area' => '',
                'middle_area' => '',
                'name' => 'テストエリアa',
                'area_cd' => 'test-big-area-1',
                'weight' => '',
                'sort' => '',
                'old_area_cd' => $bigArea->area_cd,
                'old_area_path' => $bigArea->path,
                'old_area_level' => $bigArea->level,
                '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
            ]);                                                 // テストページの取得
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/area');
            $response->assertSessionHas('custom_error', 'エリア「テストエリアa」の更新に失敗しました()');

            // データが更新されていないとを確認
            $this->assertTrue(Area::where('area_cd', 'test-big-area')->exists());
            $this->assertFalse(Area::where('area_cd', 'test-big-area-1')->exists());
        }

        $this->logout();
    }

    public function testListWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testListWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testListWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();        // クライアント管理者としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testListWithClientGeneral()
    {
        $this->loginWithClientGeneral();        // クライアント一般としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testListWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testListWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();        // 精算管理会社としてログイン

        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(200);
        $response->assertJson(['ret' => [$smallArea->toArray()]]);

        $this->logout();
    }

    public function testAreaControllerWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();        // 社内一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(404);                   // アクセス不可

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(404);                   // アクセス確認

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);                   // アクセス確認

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);                   // アクセス確認

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);                   // アクセス確認

        $this->logout();
    }

    public function testAreaControllerWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();        // クライアント管理者としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(404);                   // アクセス不可

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(404);                   // アクセス確認

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);                   // アクセス確認

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);                   // アクセス確認

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);                   // アクセス確認

        $this->logout();
    }

    public function testAreaControllerWithClientGeneral()
    {
        $this->loginWithClientGeneral();        // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(404);                   // アクセス不可

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(404);                   // アクセス確認

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);                   // アクセス確認

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);                   // アクセス確認

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);                   // アクセス確認

        $this->logout();
    }

    public function testAreaControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();        // 社外一般権限としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(404);                   // アクセス不可

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(404);                   // アクセス確認

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);                   // アクセス確認

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);                   // アクセス確認

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);                   // アクセス確認

        $this->logout();
    }

    public function testAreaControllerWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();        // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(404);                   // アクセス不可

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(404);                   // アクセス確認

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(404);                   // アクセス確認

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(404);                   // アクセス確認

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(404);                   // アクセス確認

        $this->logout();
    }

    public function testAreaControllerAsNoLogin()
    {
        // Controller内の関数にアクセスできないこと（リダイレクトでログイン画面に遷移）を確認する

        // target method index
        $response = $this->get('/admin/area/');         // テストページの取得
        $response->assertStatus(302);                   // アクセス不可
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method addForm
        $response = $this->get('/admin/area/add');      // テストページの取得
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method add
        $response = $this->_callAdd();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method editForm
        $response = $this->_callEditForm();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method edit
        $response = $this->_callEdit();
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ

        // target method list
        $smallArea = null;
        $response = $this->_callList($smallArea);
        $response->assertStatus(302);                   // アクセス確認
        $response->assertRedirect('/admin');            // 管理サイトトップへ
    }

    private function _createArea($areaCd, $path, $level, $published = 0)
    {
        $area = new Area();
        $area->area_cd = $areaCd;
        $area->path = $path;
        $area->level = $level;
        $area->published = $published;
        $area->save();
        return $area;
    }

    private function _callAdd()
    {
        return $this->post('/admin/area/add', [
            'big_area' => '',
            'middle_area' => '',
            'name' => 'テストエリアa',
            'area_cd' => 'testa',
            'weight' => '',
            'sort' => '',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditForm()
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        return $this->get('/admin/area/' . $bigArea->id . '/edit');
    }

    private function _callEdit()
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        return $this->post('/admin/area/' . $bigArea->id . '/edit', [
            'big_area' => '',
            'middle_area' => '',
            'name' => 'テストエリアa',
            'area_cd' => 'test-big-area-1',
            'weight' => '',
            'sort' => '',
            'old_area_cd' => $bigArea->area_cd,
            'old_area_path' => $bigArea->path,
            'old_area_level' => $bigArea->level,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callList(&$smallArea)
    {
        $bigArea = $this->_createArea('test-big-area', '/', 1);
        $middleArea = $this->_createArea('test-middle-area', '/test-big-area', 2);
        $smallArea = $this->_createArea('test-small-area', '/test-big-area/test-middle-area', 3);

        return $this->get('/admin/common/area/list?area_cd=' . $middleArea->area_cd . '&parent_value=' . $bigArea->area_cd . '&level=3');
    }
}
