<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\MenuOptionOkonomiAddRequest;
use App\Http\Requests\Admin\MenuOptionOkonomiEditRequest;
use App\Models\Menu;
use App\Models\Option;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuOptionControllerTest extends TestCase
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
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callIndex($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptionOkonomiExists',
            'menuOptionToppingExists',
            'menuOptionOkonomis',
            'contentsOptionOkonomis',
            'menuOptionToppings',
            'menuOptionRequired',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptionOkonomiExists', 0);
        $response->assertViewHas('menuOptionToppingExists', 0);
        $response->assertViewHas('menuOptionOkonomis', null);
        $response->assertViewHas('contentsOptionOkonomis', null);
        $response->assertViewHas('menuOptionToppings', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callIndex($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptionOkonomiExists',
            'menuOptionToppingExists',
            'menuOptionOkonomis',
            'contentsOptionOkonomis',
            'menuOptionToppings',
            'menuOptionRequired',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptionOkonomiExists', 0);
        $response->assertViewHas('menuOptionToppingExists', 0);
        $response->assertViewHas('menuOptionOkonomis', null);
        $response->assertViewHas('contentsOptionOkonomis', null);
        $response->assertViewHas('menuOptionToppings', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗のメニューオプション
        {
            $response = $this->_callIndex($menu);
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Menu.Option.index'); // 指定bladeを確認
            $response->assertViewHasAll([
                'menu',
                'menuOptionExists',
                'menuOptionOkonomiExists',
                'menuOptionToppingExists',
                'menuOptionOkonomis',
                'contentsOptionOkonomis',
                'menuOptionToppings',
                'menuOptionRequired',
            ]);                                                 // bladeに渡している変数を確認
            $response->assertViewHas('menu', $menu);
            $response->assertViewHas('menuOptionExists', 0);
            $response->assertViewHas('menuOptionOkonomiExists', 0);
            $response->assertViewHas('menuOptionToppingExists', 0);
            $response->assertViewHas('menuOptionOkonomis', null);
            $response->assertViewHas('contentsOptionOkonomis', null);
            $response->assertViewHas('menuOptionToppings', null);
            $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);
        }

        // 担当外店舗のメニューオプション
        {
            list($store2, $menu2) = $this->_createStoreMenu();
            $response = $this->_callIndex($menu2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callIndex($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.index'); // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptionOkonomiExists',
            'menuOptionToppingExists',
            'menuOptionOkonomis',
            'contentsOptionOkonomis',
            'menuOptionToppings',
            'menuOptionRequired',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptionOkonomiExists', 0);
        $response->assertViewHas('menuOptionToppingExists', 0);
        $response->assertViewHas('menuOptionOkonomis', null);
        $response->assertViewHas('contentsOptionOkonomis', null);
        $response->assertViewHas('menuOptionToppings', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiKeywordAddFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.addKeyword');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiKeywordAddFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.addKeyword');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);
        $this->logout();
    }

    public function testOkonomiKeywordAddFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.addKeyword');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiKeywordAddFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(200);                                       // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.addKeyword');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiKeywordAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');      // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」オプションの項目を追加しました。');

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiKeywordAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');      // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」オプションの項目を追加しました。');

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiKeywordAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」オプションの項目を追加しました。');

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiKeywordAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');      // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」オプションの項目を追加しました。');

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiKeywordAddException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuOptionOkonomiAddRequestのinput('image_path')呼び出しで例外発生させるようにする
        $menuOptionOkonomiAddRequest = \Mockery::mock(MenuOptionOkonomiAddRequest::class)->makePartial();
        $menuOptionOkonomiAddRequest->shouldReceive('input')->once()->with('option_cd')->andThrow(new \Exception());
        $menuOptionOkonomiAddRequest->shouldReceive('input')->once()->with('menu_name')->andReturn($menu->name);
        $menuOptionOkonomiAddRequest->shouldReceive('input')->andReturn($menu->id);     // 1回目以降のinput関数呼び出しにmenu->idを渡しておく
        $this->app->instance(MenuOptionOkonomiAddRequest::class, $menuOptionOkonomiAddRequest);

        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');      // リダイレクト先
        $response->assertSessionHas('custom_error', '「' . $menu->name . '」オプションの項目を追加できませんでした。');  // モックでメニュー名を渡せていないため空になっている

        // 登録されていないことを確認
        $this->assertFalse(Option::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testOkonomiContentsAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テスト項目」オプションの内容を保存しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容1', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);
        $this->assertSame('OKONOMI', $result[1]['option_cd']);
        $this->assertSame(1, $result[1]['required']);
        $this->assertSame(1, $result[1]['keyword_id']);
        $this->assertSame('テスト項目', $result[1]['keyword']);
        $this->assertSame('テスト内容2', $result[1]['contents']);
        $this->assertSame(150, $result[1]['price']);

        $this->logout();
    }

    public function testOkonomiContentsAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テスト項目」オプションの内容を保存しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容1', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);
        $this->assertSame('OKONOMI', $result[1]['option_cd']);
        $this->assertSame(1, $result[1]['required']);
        $this->assertSame(1, $result[1]['keyword_id']);
        $this->assertSame('テスト項目', $result[1]['keyword']);
        $this->assertSame('テスト内容2', $result[1]['contents']);
        $this->assertSame(150, $result[1]['price']);

        $this->logout();
    }

    public function testOkonomiContentsAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テスト項目」オプションの内容を保存しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容1', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);
        $this->assertSame('OKONOMI', $result[1]['option_cd']);
        $this->assertSame(1, $result[1]['required']);
        $this->assertSame(1, $result[1]['keyword_id']);
        $this->assertSame('テスト項目', $result[1]['keyword']);
        $this->assertSame('テスト内容2', $result[1]['contents']);
        $this->assertSame(150, $result[1]['price']);

        $this->logout();
    }

    public function testOkonomiContentsAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テスト項目」オプションの内容を保存しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(2, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容1', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);
        $this->assertSame('OKONOMI', $result[1]['option_cd']);
        $this->assertSame(1, $result[1]['required']);
        $this->assertSame(1, $result[1]['keyword_id']);
        $this->assertSame('テスト項目', $result[1]['keyword']);
        $this->assertSame('テスト内容2', $result[1]['contents']);
        $this->assertSame(150, $result[1]['price']);

        $this->logout();
    }

    public function testOkonomiContentsAddValidationError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiContentsAddValidationError($menu);
        $response->assertStatus(200)
            ->assertJson([
                'error' => [
                    '内容は必ず指定してください。',
                    '金額（税込）は必ず指定してください。',
                ]
            ]);

        // 追加されていないことを確認
        $result = Option::where('menu_id', $menu->id)->get();
        $this->assertCount(1, $result);

        $this->logout();
    }

    public function testOkonomiContentsAddUpdate()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiContentsAddUpdate($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テスト項目」オプションの内容を保存しました。']);

        // 更新されていることを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(100000, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容2', $result[0]['contents']);
        $this->assertSame(150, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiContentsAddNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiContentsAddNotAjax($menu);
        $response->assertStatus(200);

        // 更新されていないことを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(100000, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);
        $this->assertNull($result[0]['contents_id']);
        $this->assertNull($result[0]['contents']);
        $this->assertNull($result[0]['price']);

        $this->logout();
    }

    public function testOkonomiEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired',
        ]);                                                         // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 1);
        $response->assertViewHas('menuOptions', [$option->toArray()]);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired',
        ]);                                                         // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 1);
        $response->assertViewHas('menuOptions', [$option->toArray()]);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired',
        ]);                                                         // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 1);
        $response->assertViewHas('menuOptions', [$option->toArray()]);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiEditFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(200);                               // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Okonomi.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired',
        ]);                                                         // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 1);
        $response->assertViewHas('menuOptions', [$option->toArray()]);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testOkonomiEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のお好みを更新しました。');

        // 更新されていることを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目更新', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容更新', $result[0]['contents']);
        $this->assertSame(150, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のお好みを更新しました。');

        // 更新されていることを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目更新', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容更新', $result[0]['contents']);
        $this->assertSame(150, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のお好みを更新しました。');

        // 更新されていることを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目更新', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容更新', $result[0]['contents']);
        $this->assertSame(150, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のお好みを更新しました。');

        // 更新されていることを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目更新', $result[0]['keyword']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容更新', $result[0]['contents']);
        $this->assertSame(150, $result[0]['price']);

        $this->logout();
    }

    public function testOkonomiEditException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuOptionOkonomiEditRequestのinput()呼び出しで例外発生させるようにする
        $menuOptionOkonomiEditRequest = \Mockery::mock(MenuOptionOkonomiEditRequest::class)->makePartial();
        $menuOptionOkonomiEditRequest->shouldReceive('input')->once()->with('menuOption')->andThrow(new \Exception());
        $menuOptionOkonomiEditRequest->shouldReceive('input')->once()->with('menu_name')->andReturn($menu->name);
        $menuOptionOkonomiEditRequest->shouldReceive('input')->andReturn($menu->id); // input関数呼び出しの時はmenu->idを渡しておく
        $this->app->instance(MenuOptionOkonomiEditRequest::class, $menuOptionOkonomiEditRequest);

        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('custom_error', '「' . $menu->name . '」のお好みを更新できませんでした。');  // モックでメニュー名を渡せていないため空になっている

        // 更新されていないことを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);
        $this->assertSame('OKONOMI', $result[0]['option_cd']);
        $this->assertSame(1, $result[0]['required']);
        $this->assertSame(1, $result[0]['keyword_id']);
        $this->assertSame('テスト項目', $result[0]['keyword']);             // 変わっていないこと
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容1', $result[0]['contents']);           // 変わっていないこと
        $this->assertSame(100, $result[0]['price']);                      // 変わっていないこと
    }

    public function testToppingAddFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(200);                                // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Topping.add');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testToppingAddFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(200);                                // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Topping.add');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testToppingAddFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(200);                                // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Topping.add');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testToppingAddFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(200);                                // アクセス確認
        $response->assertViewIs('admin.Menu.Option.Topping.add');    // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuOptionExists',
            'menuOptions',
            'menuOptionRequired'
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuOptionExists', 0);
        $response->assertViewHas('menuOptions', null);
        $response->assertViewHas('menuOptionRequired', [1 => '必須', 0 => '任意']);

        $this->logout();
    }

    public function testToppingAddFormRestaurant()
    {
        list($store, $menu) = $this->_createStoreMenu(0, 'RS');         // レストランメニューとしてテストデータを用意
        $this->loginWithInHouseAdministrator();                           // 社内管理者としてログイン

        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(302);                                       // リダイレクト（レストランメニューはトッピング対象外）
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先

        $this->logout();
    }

    public function testToppingAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テストメニュー」オプションのトッピングを追加しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('TOPPING', $result[0]['option_cd']);
        $this->assertNull($result[0]['keyword_id']);
        $this->assertNull($result[0]['keyword']);
        $this->assertNull($result[0]['required']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testToppingAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テストメニュー」オプションのトッピングを追加しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('TOPPING', $result[0]['option_cd']);
        $this->assertNull($result[0]['keyword_id']);
        $this->assertNull($result[0]['keyword']);
        $this->assertNull($result[0]['required']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testToppingAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テストメニュー」オプションのトッピングを追加しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('TOPPING', $result[0]['option_cd']);
        $this->assertNull($result[0]['keyword_id']);
        $this->assertNull($result[0]['keyword']);
        $this->assertNull($result[0]['required']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testToppingAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(200)->assertJson(['success' => '「テストメニュー」オプションのトッピングを追加しました。']);

        // 登録されているかを確認
        $result = Option::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result); // 1件登録があること
        $this->assertSame('TOPPING', $result[0]['option_cd']);
        $this->assertNull($result[0]['keyword_id']);
        $this->assertNull($result[0]['keyword']);
        $this->assertNull($result[0]['required']);
        $this->assertSame(1, $result[0]['contents_id']);
        $this->assertSame('テスト内容', $result[0]['contents']);
        $this->assertSame(100, $result[0]['price']);

        $this->logout();
    }

    public function testToppingAddValidationError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callToppingAddValidationError($menu);
        $response->assertStatus(200)
            ->assertJson([
                'error' => [
                    '内容は必ず指定してください。',
                    '金額（税込）は必ず指定してください。'
                ]
            ]);

        // 追加されていないことを確認
        $this->assertFalse(Option::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testToppingAddNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callToppingAddNotAjax($menu);
        $response->assertStatus(200);

        // 追加されていないことを確認
        $this->assertFalse(Option::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testToppingEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のトッピングを更新しました。');


        // 更新されていることを確認
        $result = Option::find($option->id);
        $this->assertSame('TOPPING', $result->option_cd);
        $this->assertNull($result->keyword_id);
        $this->assertNull($result->keyword);
        $this->assertSame(1, $result->required);
        $this->assertSame(1, $result->contents_id);
        $this->assertSame('テスト内容更新', $result->contents);     // 更新されている
        $this->assertSame(150, $result->price);                  // 更新されている

        $this->logout();
    }

    public function testToppingEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のトッピングを更新しました。');

        // 更新されていることを確認
        $result = Option::find($option->id);
        $this->assertSame('TOPPING', $result->option_cd);
        $this->assertNull($result->keyword_id);
        $this->assertNull($result->keyword);
        $this->assertSame(1, $result->required);
        $this->assertSame(1, $result->contents_id);
        $this->assertSame('テスト内容更新', $result->contents);     // 更新されている
        $this->assertSame(150, $result->price);                  // 更新されている

        $this->logout();
    }

    public function testToppingEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のトッピングを更新しました。');

        // 更新されていることを確認
        $result = Option::find($option->id);
        $this->assertSame('TOPPING', $result->option_cd);
        $this->assertNull($result->keyword_id);
        $this->assertNull($result->keyword);
        $this->assertSame(1, $result->required);
        $this->assertSame(1, $result->contents_id);
        $this->assertSame('テスト内容更新', $result->contents);     // 更新されている
        $this->assertSame(150, $result->price);                  // 更新されている

        $this->logout();
    }

    public function testToppingEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/option');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」のトッピングを更新しました。');

        // 更新されていることを確認
        $result = Option::find($option->id);
        $this->assertSame('TOPPING', $result->option_cd);
        $this->assertNull($result->keyword_id);
        $this->assertNull($result->keyword);
        $this->assertSame(1, $result->required);
        $this->assertSame(1, $result->contents_id);
        $this->assertSame('テスト内容更新', $result->contents);     // 更新されている
        $this->assertSame(150, $result->price);                  // 更新されている

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);

        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // データが存在すること
        $this->assertNotNull(Option::find($option->id));

        $response = $this->_callDelete($menu, $option);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Option::find($option->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);

        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // データが存在すること
        $this->assertNotNull(Option::find($option->id));

        $response = $this->_callDelete($menu, $option);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Option::find($option->id));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);

        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // データが存在すること
        $this->assertNotNull(Option::find($option->id));

        $response = $this->_callDelete($menu, $option);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Option::find($option->id));

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);

        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // データが存在すること
        $this->assertNotNull(Option::find($option->id));

        $response = $this->_callDelete($menu, $option);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Option::find($option->id));

        $this->logout();
    }

    public function testDeleteExcection()
    {
        list($store, $menu) = $this->_createStoreMenu();

        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callDeleteExcption($menu);
        $response->assertStatus(500);

        $this->logout();
    }

    public function testMenuOptionControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex($menu);
        $response->assertStatus(404);

        // target method okonomiKeywordAddForm
        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(404);

        // target method okonomiKeywordAdd
        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(404);

        // target method okonomiContentsAdd
        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(404);

        // target method okonomiEditForm
        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(404);

        // target method okonomiEdit
        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(404);

        // target method ToppingAddForm
        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(404);

        // target method ToppingAdd
        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(404);

        // target method ToppingEdit
        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(404);

        // target method delete
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);
        $response = $this->_callDelete($menu, $option);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testMenuOptionControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex($menu);
        $response->assertStatus(404);

        // target method okonomiKeywordAddForm
        $response = $this->_callOkonomiKeywordAddForm($menu);
        $response->assertStatus(404);

        // target method okonomiKeywordAdd
        $response = $this->_callOkonomiKeywordAdd($menu);
        $response->assertStatus(404);

        // target method okonomiContentsAdd
        $response = $this->_callOkonomiContentsAdd($menu);
        $response->assertStatus(404);

        // target method okonomiEditForm
        $response = $this->_callOkonomiEditForm($menu, $option);
        $response->assertStatus(404);

        // target method okonomiEdit
        $response = $this->_callOkonomiEdit($menu);
        $response->assertStatus(404);

        // target method ToppingAddForm
        $response = $this->_callToppingAddForm($menu);
        $response->assertStatus(404);

        // target method ToppingAdd
        $response = $this->_callToppingAdd($menu);
        $response->assertStatus(404);

        // target method ToppingEdit
        $response = $this->_callToppingEdit($menu, $option);
        $response->assertStatus(404);

        // target method delete
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);
        $response = $this->_callDelete($menu, $option);
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

    private function _createMenu($storeId, $published = 1, $appCd = 'TO')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11111111';
        $menu->free_drinks = 0;
        $menu->published = $published;
        $menu->save();
        return $menu;
    }

    private function _createOption($menuId, $option_cd, $keywordId, $keyword, $contentsId, $contents, $price)
    {
        $option = new Option();
        $option->menu_id = $menuId;
        $option->option_cd = $option_cd;
        $option->keyword_id = $keywordId;
        $option->keyword = $keyword;
        $option->contents_id = $contentsId;
        $option->contents = $contents;
        $option->price = $price;
        $option->required = 1;
        $option->save();
        return $option;
    }

    private function _createStoreMenu($published = 0, $appCd = 'TO')
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published, $appCd);
        return [$store, $menu];
    }

    private function _callIndex($menu)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu?page=10'),
        ])->get('/admin/menu/' . $menu->id . '/option');
    }

    private function _callOkonomiKeywordAddForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/option/okonomi/add');
    }

    private function _callOkonomiKeywordAdd($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/option/okonomi/add', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'option_cd' => 'OKONOMI',
            'required' => '1',
            'keyword' => 'テスト項目',
            'contents_id' => '1',
            'contents' => 'テスト内容',
            'price' => '100',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callOkonomiContentsAdd($menu)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 1, 'テスト項目', 1, 'テスト内容1', 100);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $option->id . '/option/okonomi/contents/add', [
            'menuOption' => [
                'menu_id' => $menu->id,
                'contents' => 'テスト内容2',
                'price' => '150',
            ],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callOkonomiContentsAddValidationError($menu)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 1, 'テスト項目', 1, 'テスト内容1', 100);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $option->id . '/option/okonomi/contents/add', [
            'menuOption' => [
                'menu_id' => $menu->id,
                'contents' => '',
                'price' => '',
            ],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callOkonomiContentsAddUpdate($menu)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 100000, 'テスト項目', null, null, null);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $option->id . '/option/okonomi/contents/add', [
            'menuOption' => [
                'menu_id' => $menu->id,
                'contents' => 'テスト内容2',
                'price' => '150',
            ],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callOkonomiContentsAddNotAjax($menu)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 100000, 'テスト項目', null, null, null);
        return $this->post('/admin/menu/' . $option->id . '/option/okonomi/contents/add', [
            'menuOption' => [
                'menu_id' => $menu->id,
                'contents' => 'テスト内容2',
                'price' => '150',
            ],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callOkonomiEditForm($menu, &$option)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 1, 'テスト項目', 1, 'テスト内容1', 100);
        return $this->get('/admin/menu/' . $menu->id . '/option/okonomi/edit?keyword_id=1');
    }

    private function _callOkonomiEdit($menu)
    {
        $option = $this->_createOption($menu->id, 'OKONOMI', 1, 'テスト項目', 1, 'テスト内容1', 100);
        return $this->post('/admin/menu/option/okonomi/edit', [
            'menuOkonomi' => [[
                'required' => '1',
                'keyword' => 'テスト項目更新',
            ]],
            'menuOption' => [[
                'id' => $option->id,
                'contents' => 'テスト内容更新',
                'price' => '150',
            ]],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'keyword_id' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callToppingAddForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/option/topping/add');
    }

    private function _callToppingAdd($menu)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/option/topping/add', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'menuOption' => [[
                'contents' => 'テスト内容',
                'price' => 100,
                'option_cd' => 'TOPPING',
            ]],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callToppingAddValidationError($menu)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/option/topping/add', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'menuOption' => [[
                'contents' => '',
                'price' => '',
                'option_cd' => 'TOPPING',
            ]],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callToppingAddNotAjax($menu)
    {
        return $this->post('/admin/menu/option/topping/add', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'menuOption' => [[
                'contents' => 'テスト内容',
                'price' => 100,
                'option_cd' => 'TOPPING',
            ]],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callToppingEdit($menu, &$option)
    {
        $option = $this->_createOption($menu->id, 'TOPPING', null, null, 1, 'テスト内容', 100);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu/' . $menu->id . '/option'),
        ])->post('/admin/menu/' . $menu->id . '/option/topping/edit', [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'menuOptionTopping' => [[
                'option_id' => $option->id,
                'contents' => 'テスト内容更新',
                'price' => 150,
            ]],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($menu, $option)
    {
        return $this->post('/admin/menu/' . $menu->id . '/option/delete/' . $option->id, [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDeleteExcption($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/option/delete/1234567890123', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
