<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Price;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuControllerTest extends TestCase
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
        $response->assertStatus(200);                  // アクセス確認
        $response->assertViewIs('admin.Menu.index');   // 指定bladeを確認
        $response->assertViewHasAll(['menus']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                  // アクセス確認
        $response->assertViewIs('admin.Menu.index');   // 指定bladeを確認
        $response->assertViewHasAll(['menus']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                  // アクセス確認
        $response->assertViewIs('admin.Menu.index');   // 指定bladeを確認
        $response->assertViewHasAll(['menus']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();     // 社外一般権限としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                  // アクセス確認
        $response->assertViewIs('admin.Menu.index');   // 指定bladeを確認
        $response->assertViewHasAll(['menus']);        // bladeに渡している変数を確認

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                                                                   // アクセス確認
        $response->assertViewIs('admin.Menu.edit');                                                     // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);   // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                                                                   // アクセス確認
        $response->assertViewIs('admin.Menu.edit');                                                     // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);   // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗のメニュー変更
        {
            $response = $this->_callEditForm($menu);
            $response->assertStatus(200);                                                                   // アクセス確認
            $response->assertViewIs('admin.Menu.edit');                                                     // 指定bladeを確認
            $response->assertViewHasAll(['menu', 'stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);   // bladeに渡している変数を確認
            $response->assertViewHas('menu', $menu);
            $response->assertViewHas('appCd', [
                'to' => ['TO' => 'テイクアウト'],
                'rs' => ['RS' => 'レストラン'],
                'tors' => ['TORS' => 'テイクアウト/レストラン'],
            ]);
            $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
            $response->assertViewHas('providedDayOfWeeks', [
                '月' => '1',
                '火' => '1',
                '水' => '1',
                '木' => '1',
                '金' => '1',
                '土' => '1',
                '日' => '1',
                '祝' => '1',
            ]);
        }

        // 担当外店舗のメニュー変更
        {
            list($store2, $menu2) = $this->_createStoreMenu();
            $response = $this->_callEditForm($menu2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                                                                   // アクセス確認
        $response->assertViewIs('admin.Menu.edit');                                                     // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);   // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEdit($store, $menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');    // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を更新しました');

        $result = Menu::find($menu->id);
        $this->assertSame('テストメニュー123', $result->name);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callEdit($store, $menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');    // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を更新しました');

        $result = Menu::find($menu->id);
        $this->assertSame('テストメニュー123', $result->name);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗のメニュー変更
        {
            $response = $this->_callEdit($store, $menu);
            $response->assertStatus(302);                   // リダイレクト
            $response->assertRedirect('/admin/menu');       // リダイレクト先
            $response->assertSessionHas('message', 'メニュー「テストメニュー123」を更新しました');

            $result = Menu::find($menu->id);
            $this->assertSame('テストメニュー123', $result->name);
        }

        // 担当外店舗のメニュー変更
        {
            list($store2, $menu2) = $this->_createStoreMenu();
            $response = $this->_callEdit($store2, $menu2);
            $response->assertStatus(302);                       // リダイレクト（try-catch中に403エラーになり、throwable処理されている）
            $response->assertRedirect('/admin/menu');           // リダイレクト先
            $response->assertSessionHas('custom_error', 'メニュー「テストメニュー123」を更新できませんでした');

            $result = Menu::find($menu2->id);
            $this->assertSame('テストメニュー', $result->name);   // 更新されていないこと
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEdit($store, $menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を更新しました');

        $result = Menu::find($menu->id);
        $this->assertSame('テストメニュー123', $result->name);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Menu.add');      // 指定bladeを確認
        $response->assertViewHasAll(['stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);  // bladeに渡している変数を確認
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Menu.add');      // 指定bladeを確認
        $response->assertViewHasAll(['stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);  // bladeに渡している変数を確認
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Menu.add');      // 指定bladeを確認
        $response->assertViewHasAll(['stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);  // bladeに渡している変数を確認
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Menu.add');      // 指定bladeを確認
        $response->assertViewHasAll(['stores', 'appCd', 'freeDrinks', 'providedDayOfWeeks']);  // bladeに渡している変数を確認
        $response->assertViewHas('appCd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);
        $response->assertViewHas('freeDrinks', [1 => 'あり', 0 => 'なし']);
        $response->assertViewHas('providedDayOfWeeks', [
            '月' => '1',
            '火' => '1',
            '水' => '1',
            '木' => '1',
            '金' => '1',
            '土' => '1',
            '日' => '1',
            '祝' => '1',
        ]);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // 登録メニューがないことを確認
        $result = Menu::where('store_id', $store->id)->count();
        $this->assertSame(0, $result);

        $response = $this->_callAdd($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect('/admin/menu?page=' . $this->_getPageNumber());   // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を作成しました');

        // 登録されているかを確認
        $result = Menu::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('テストメニュー123', $result[0]['name']);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $store = $this->_createStore();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // 登録メニューがないことを確認
        $result = Menu::where('store_id', $store->id)->count();
        $this->assertSame(0, $result);

        $response = $this->_callAdd($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect('/admin/menu?page=' . $this->_getPageNumber());   // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を作成しました');

        // 登録されているかを確認
        $result = Menu::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('テストメニュー123', $result[0]['name']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 登録メニューがないことを確認
        $result = Menu::where('store_id', $store->id)->count();
        $this->assertSame(0, $result);

        $response = $this->_callAdd($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect('/admin/menu?page=' . $this->_getPageNumber());   // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を作成しました');

        // 登録されているかを確認
        $result = Menu::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('テストメニュー123', $result[0]['name']);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $store = $this->_createStore();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // 登録メニューがないことを確認
        $result = Menu::where('store_id', $store->id)->count();
        $this->assertSame(0, $result);

        $response = $this->_callAdd($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect('/admin/menu?page=' . $this->_getPageNumber());   // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー123」を作成しました');

        // 登録されているかを確認
        $result = Menu::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('テストメニュー123', $result[0]['name']);

        $this->logout();
    }

    public function testAddThrowable()
    {
        $store = $this->_createStore();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // StoreのecalcHourToMin()呼び出しで例外発生させるようにする
        $storeModel = \Mockery::mock(Store::class)->makePartial();
        $storeModel->shouldReceive('calcHourToMin')->andThrow(new \Exception());
        $this->app->instance(Store::class, $storeModel);

        $response = $this->_callAdd($store);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('custom_error', 'メニュー「テストメニュー123」を作成できませんでした');

        // 登録メニューが登録されていないことを確認
        $result = Menu::where('store_id', $store->id)->count();
        $this->assertSame(0, $result);

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // データが存在すること
        $this->assertNotNull(Menu::find($menu->id));

        $response = $this->_callDelete($menu);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Menu::find($menu->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // データが存在すること
        $this->assertNotNull(Menu::find($menu->id));

        $response = $this->_callDelete($menu);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Menu::find($menu->id));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗のメニュー
        {
            // データが存在すること
            $this->assertNotNull(Menu::find($menu->id));

            $response = $this->_callDelete($menu);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // データが削除されていること
            $this->assertNull(Menu::find($menu->id));
        }

        // 担当店舗外のメニュー
        {
            // データが存在すること
            list($store2, $menu2) = $this->_createStoreMenu();
            $this->assertNotNull(Menu::find($menu2->id));

            $response = $this->_callDelete($menu2);
            $response->assertStatus(500);                       // Execption return false

            // データが削除されていないること
            $this->assertNotNull(Menu::find($menu2->id));
        }


        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // データが存在すること
        $this->assertNotNull(Menu::find($menu->id));

        $response = $this->_callDelete($menu);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Menu::find($menu->id));

        $this->logout();
    }

    public function testSetPublishWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu(0);
        $this->_createMenuPublished($menu);
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 未公開であること
        $this->assertSame(0, Menu::find($menu->id)->published);

        $response = $this->_callSetPublish($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先ト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を公開しました。');

        // 公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPublishWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu(0);
        $this->_createMenuPublished($menu);
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 未公開であること
        $this->assertSame(0, Menu::find($menu->id)->published);

        $response = $this->_callSetPublish($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を公開しました。');

        // 公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPublishWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu(0);
        $this->_createMenuPublished($menu);
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 未公開であること
        $this->assertSame(0, Menu::find($menu->id)->published);

        $response = $this->_callSetPublish($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を公開しました。');

        // 公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPublishWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu(0);
        $this->_createMenuPublished($menu);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 未公開であること
        $this->assertSame(0, Menu::find($menu->id)->published);

        $response = $this->_callSetPublish($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を公開しました。');

        // 公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPrivateWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 公開中であること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertNull($menu->staff_id);

        $response = $this->_callSetPrivate($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を非公開にしました。');

        // 非公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(0, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPrivateWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 公開中であること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertNull($menu->staff_id);

        $response = $this->_callSetPrivate($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を非公開にしました。');

        // 非公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(0, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPrivateWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 公開中であること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertNull($menu->staff_id);

        $response = $this->_callSetPrivate($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を非公開にしました。');

        // 非公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(0, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testSetPrivateWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        // 公開中であること
        $menu = Menu::find($menu->id);
        $this->assertSame(1, $menu->published);
        $this->assertNull($menu->staff_id);

        $response = $this->_callSetPrivate($menu);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/menu');     // リダイレクト先
        $response->assertSessionHas('message', 'メニュー「テストメニュー」を非公開にしました。');

        // 非公開に変わっていること
        $menu = Menu::find($menu->id);
        $this->assertSame(0, $menu->published);
        $this->assertSame($staff->id, $menu->staff_id);

        $this->logout();
    }

    public function testMenuControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $menu);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu);
        $response->assertStatus(404);

        // target method setPublish
        $this->_createMenuPublished($menu);
        $response = $this->_callSetPublish($menu);
        $response->assertStatus(404);

        // target method setPrivate
        $response = $this->_callSetPrivate($menu);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testMenuControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $menu);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu);
        $response->assertStatus(404);

        // target method setPublish
        $this->_createMenuPublished($menu);
        $response = $this->_callSetPublish($menu);
        $response->assertStatus(404);

        // target method setPrivate
        $response = $this->_callSetPrivate($menu);
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

    private function _createStoreMenu($published = 1)
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published);
        return [$store, $menu];
    }

    private function _createMenuPublished($menu)
    {
        $menuId = $menu->id;
        $genreLevel2 = new Genre();
        $genreLevel2->level = 2;
        $genreLevel2->genre_cd = 'test2';
        $genreLevel2->published = 1;
        $genreLevel2->path = '/test';
        $genreLevel2->save();

        $genreGroup = new GenreGroup();
        $genreGroup->menu_id = $menuId;
        $genreGroup->genre_id = $genreLevel2->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();

        $menuImage = new Image();
        $menuImage->menu_id = $menuId;
        $menuImage->image_cd = 'MENU_MAIN';
        $menuImage->weight = 100;
        $menuImage->save();

        $price = new Price();
        $price->menu_id = $menuId;
        $price->price = 1000;
        $price->save();
    }
    private function _getPageNumber()
    {
        $pageNumber = 1;
        $menuLists = Menu::query()->adminSearchFilter([])->count();
        if ($menuLists > 0) {
            $pageNumber = ceil($menuLists / 30);
        }
        return $pageNumber;
    }

    private function _callIndex()
    {
        return $this->get('/admin/menu');
    }

    private function _callEditForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/edit');
    }

    private function _callEdit($store, $menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/edit', [
            'store_name' => $store->id,
            'menu_name' => 'テストメニュー123',
            'menu_description' => 'テスト説明123',
            'sales_lunch_start_time' => '09:00:00',
            'sales_lunch_end_time' => '14:00:00',
            'sales_dinner_start_time' => '17:00:00',
            'sales_dinner_end_time' => '21:00:00',
            'app_cd' => 'RS',
            'number_of_orders_same_time' => '60',
            'number_of_course' => '3',
            'provided_time' => '120',
            'lower_orders_time_hour' => '1',
            'lower_orders_time_minute' => '30',
            'free_drinks' => '1',
            'published' => '0',
            'plan' => '1000',
            'menu_notes' => 'テスト',
            'buffet_lp_published' => '0',
            'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm()
    {
        return $this->get('/admin/menu/add');
    }

    private function _callAdd($store)
    {
        return $this->post('/admin/menu/add', [
            'store_name' => $store->id,
            'menu_name' => 'テストメニュー123',
            'menu_description' => 'テスト説明123',
            'sales_lunch_start_time' => '09:00:00',
            'sales_lunch_end_time' => '14:00:00',
            'sales_dinner_start_time' => '17:00:00',
            'sales_dinner_end_time' => '21:00:00',
            'app_cd' => 'RS',
            'number_of_orders_same_time' => '60',
            'number_of_course' => '3',
            'provided_time' => '120',
            'lower_orders_time_hour' => '1',
            'lower_orders_time_minute' => '30',
            'free_drinks' => '1',
            'published' => '0',
            'plan' => '1000',
            'menu_notes' => 'テスト',
            'buffet_lp_published' => '0',
            'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/delete', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSetPublish($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/publish', [
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSetPrivate($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/private', [
            'published' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
