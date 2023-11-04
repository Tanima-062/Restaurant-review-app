<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuStockControllerTest extends TestCase
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
        list($store, $menu) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Stock.index');    // 指定bladeを確認
        $response->assertViewHasAll(['getStock', 'menu']);    // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Stock.index');    // 指定bladeを確認
        $response->assertViewHasAll(['getStock', 'menu']);    // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Stock.index');    // 指定bladeを確認
        $response->assertViewHasAll(['getStock', 'menu']);    // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testIndexWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Stock.index');    // 指定bladeを確認
        $response->assertViewHasAll(['getStock', 'menu']);    // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);

        $this->logout();
    }

    public function testIndexRestaurant()
    {
        list($store, $menu) = $this->_createStoreMenu(0, 'RS');
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(302);                       // リダイレクト（レストランメニューは対象外）
        $response->assertRedirect('/admin/menu?page=10');   // リダイレクト先

        $this->logout();
    }

    public function testIndexAjaxNotReservation()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexAjaxNotReservation($menu, $stocks);
        list($stock1, $stock2) = $stocks;
        $response->assertStatus(200)->assertJson([
            [
                'id' => $stock1->id,
                'title' => '在庫：5',
                'start' => '2099-10-01',
                'menu_id' => $menu->id,
                'color' => '#e3f4fc',
            ],
            [
                'id' => $stock2->id,
                'title' => '在庫：5',
                'start' => '2099-10-02',
                'menu_id' => $menu->id,
                'color' => '#e3f4fc',
            ],
        ]);

        $this->logout();
    }

    public function testIndexAjaxReservation()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexAjaxReservation($menu, $stocks);
        list($stock1, $stock2) = $stocks;
        $response->assertStatus(200)->assertJson([
            [
                'id' => $stock1->id,
                'title' => '在庫：5',
                'start' => '2099-10-01',
                'menu_id' => $menu->id,
                'color' => '#e3f4fc',
            ],
            [
                'id' => $stock2->id,
                'title' => '在庫：5',
                'start' => '2099-10-02',
                'menu_id' => $menu->id,
                'color' => '#e3f4fc',
            ],
            [
                'title' => '予約：2',
                'start' => '2099-10-01',
                'color' => '#fae9e8',
                'menu_id' => $menu->id,
            ],
        ]);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAdd($menu);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を追加しました。',
        ]);

        // 登録されているかを確認
        $result = Stock::where('menu_id', $menu->id)->where('date', '2099-10-03')->get();
        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['stock_number']);

        $this->logout();
    }

    public function tesztAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAdd($menu);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を追加しました。',
        ]);

        // 登録されているかを確認
        $result = Stock::where('menu_id', $menu->id)->where('date', '2099-10-03')->get();
        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['stock_number']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAdd($menu);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を追加しました。',
        ]);

        // 登録されているかを確認
        $result = Stock::where('menu_id', $menu->id)->where('date', '2099-10-03')->get();
        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['stock_number']);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($menu);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を追加しました。',
        ]);

        // 登録されているかを確認
        $result = Stock::where('menu_id', $menu->id)->where('date', '2099-10-03')->get();
        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['stock_number']);

        $this->logout();
    }

    public function testAddValidationError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddValidationError($menu);
        $response->assertStatus(200)->assertJson([
            'error' => ['在庫数は必ず指定してください。'],
        ]);

        // 登録されていないことを確認
        $this->assertFalse(Stock::where('menu_id', $menu->id)->where('date', '2099-10-03')->exists());

        $this->logout();
    }

    public function testAddThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $request = \Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('ajax')->andReturn(true);
        $request->shouldReceive('all')->andThrow(new \Exception());
        $request->shouldReceive('input')->andReturn($menu->name); // input関数呼び出しの時はmenu-nameを渡しておく
        $this->app->instance(Request::class, $request);

        $response = $this->_callAdd($menu);
        $response->assertStatus(500);

        // 登録されていないことを確認
        $this->assertFalse(Stock::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testAddNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddNotAjax($menu);
        $response->assertStatus(200);

        // 登録されていないことを確認
        $this->assertFalse(Stock::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(10, $result->stock_number);  // 5->10に変更されている

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(10, $result->stock_number);  // 5->10に変更されている

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(10, $result->stock_number);  // 5->10に変更されている

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(10, $result->stock_number);  // 5->10に変更されている

        $this->logout();
    }

    public function testEditValidationError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEditValidationError($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'error' => ['在庫数は必ず指定してください。'],
        ]);

        // 更新されていないかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(5, $result->stock_number);  // 5->10に変更されていない

        $this->logout();
    }

    public function testEditThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $request = \Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('ajax')->andReturn(true);
        $request->shouldReceive('all')->andThrow(new \Exception());
        $request->shouldReceive('input')->andReturn($menu->name); // input関数呼び出しの時はmenu-nameを渡しておく
        $this->app->instance(Request::class, $request);

        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(500);

        // 更新されていないかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(5, $result->stock_number);  // 5->10に変更されていない

        $this->logout();
    }

    public function testEditNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEditNotAjax($menu, $stock);
        $response->assertStatus(200);

        // 更新されていないかを確認
        $result = Stock::find($stock->id);
        $this->assertNotNull($result);
        $this->assertSame(5, $result->stock_number);  // 5->10に変更されていない

        $this->logout();
    }

    public function testBulkUpdateWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(31, $result);                        // 登録数は2099/10の日数の数分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(10, $result[0]['stock_number']);      // 5->10に変更されている
        $this->assertSame('2099-10-02', $result[1]['date']);    // 新規追加されている
        $this->assertSame(10, $result[1]['stock_number']);      // 新規追加されている
        $this->assertSame('2099-10-31', $result[30]['date']);   // 新規追加されている
        $this->assertSame(10, $result[30]['stock_number']);     // 新規追加されている

        $this->logout();
    }

    public function testBulkUpdateWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(31, $result);                        // 登録数は2099/10の日数の数分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(10, $result[0]['stock_number']);      // 5->10に変更されている
        $this->assertSame('2099-10-02', $result[1]['date']);    // 新規追加されている
        $this->assertSame(10, $result[1]['stock_number']);      // 新規追加されている
        $this->assertSame('2099-10-31', $result[30]['date']);   // 新規追加されている
        $this->assertSame(10, $result[30]['stock_number']);     // 新規追加されている

        $this->logout();
    }

    public function testBulkUpdateWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(31, $result);                        // 登録数は2099/10の日数の数分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(10, $result[0]['stock_number']);      // 5->10に変更されている
        $this->assertSame('2099-10-02', $result[1]['date']);    // 新規追加されている
        $this->assertSame(10, $result[1]['stock_number']);      // 新規追加されている
        $this->assertSame('2099-10-31', $result[30]['date']);   // 新規追加されている
        $this->assertSame(10, $result[30]['stock_number']);     // 新規追加されている

        $this->logout();
    }

    public function testBulkUpdateWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の在庫を更新しました。',
        ]);

        // 更新されているかを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(31, $result);                        // 登録数は2099/10の日数の数分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(10, $result[0]['stock_number']);      // 5->10に変更されている
        $this->assertSame('2099-10-02', $result[1]['date']);    // 新規追加されている
        $this->assertSame(10, $result[1]['stock_number']);      // 新規追加されている
        $this->assertSame('2099-10-31', $result[30]['date']);   // 新規追加されている
        $this->assertSame(10, $result[30]['stock_number']);     // 新規追加されている

        $this->logout();
    }

    public function testBulkUpdateValidationError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callBulkUpdateValidationError($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'error' => ['在庫数は必ず指定してください。'],
        ]);

        // 追加・更新されていないことを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(1, $result);                        // 登録数はテストデータの一件分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(5, $result[0]['stock_number']);      // 5->10に変更されていない

        $this->logout();
    }

    public function testBulkUpdateThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callBulkUpdateThrowable($menu, $stock);
        $response->assertStatus(200)->assertJson([
            'error' => ['「テストメニュー」の在庫を更新できませんでした。'],
        ]);

        // 追加・更新されていないことを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(1, $result);                        // 登録数はテストデータの一件分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(5, $result[0]['stock_number']);      // 5->10に変更されていない

        $this->logout();
    }

    public function testBulkUpdateNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callBulkUpdateNotAjax($menu, $stock);
        $response->assertStatus(200);

        // 追加・更新されていないことを確認
        $result = Stock::where('menu_id', $menu->id)->get();
        $this->assertCount(1, $result);                        // 登録数はテストデータの一件分
        $this->assertSame($stock->id, $result[0]['id']);
        $this->assertSame(5, $result[0]['stock_number']);      // 5->10に変更されていない

        $this->logout();
    }


    public function testGetDataWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(200)->assertJson($stock->toArray());    // テストデータと同じ内容が返ってくること

        $this->logout();
    }

    public function testGetDataWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(200)->assertJson($stock->toArray());    // テストデータと同じ内容が返ってくること

        $this->logout();
    }

    public function testGetDataWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(200)->assertJson($stock->toArray());    // テストデータと同じ内容が返ってくること

        $this->logout();
    }

    public function testGetDataWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(200)->assertJson($stock->toArray());    // テストデータと同じ内容が返ってくること

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callDelete($menu, $stock);
        $response->assertStatus(200);

        // データが削除されていること
        $this->assertNull(Stock::find($stock->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callDelete($menu, $stock);
        $response->assertStatus(200);

        // データが削除されていること
        $this->assertNull(Stock::find($stock->id));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callDelete($menu, $stock);
        $response->assertStatus(200);

        // データが削除されていること
        $this->assertNull(Stock::find($stock->id));

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($menu, $stock);
        $response->assertStatus(200);

        // データが削除されていること
        $this->assertNull(Stock::find($stock->id));

        $this->logout();
    }

    public function testMenuStockControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method add
        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(404);

        // target method bulkUpdate
        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(404);

        // target method getData
        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $stock);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testMenuStockControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method add
        $response = $this->_callIndexNotAjax($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $stock);
        $response->assertStatus(404);

        // target method bulkUpdate
        $response = $this->_callBulkUpdate($menu, $stock);
        $response->assertStatus(404);

        // target method getData
        $response = $this->_callGetData($menu, $stock);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $stock);
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

    private function _createStoreMenu($published = 0, $appCd = 'TO')
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published, $appCd);
        return [$store, $menu];
    }

    private function _createStock($menuId, $date, $stockNumber)
    {
        $stock = new Stock();
        $stock->menu_id = $menuId;
        $stock->date = $date;
        $stock->stock_number = $stockNumber;
        $stock->save();
        return $stock;
    }

    private function _createReservation($menuId, $pickUpDatetime)
    {
        $reservation = new Reservation();
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menuId;
        $reservationMenu->count = 1;
        $reservationMenu->unit_price = 1000;
        $reservationMenu->price = 1000;
        $reservationMenu->save();
    }

    private function _callIndexNotAjax($menu)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu?page=10'),
        ])->get('/admin/menu/stock/' . $menu->id);
    }

    private function _callIndexAjaxNotReservation($menu, &$stocks)
    {
        $stock1 = $this->_createStock($menu->id, '2099-10-01', 5);
        $stock2 = $this->_createStock($menu->id, '2099-10-02', 5);
        $stocks = [$stock1, $stock2];
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest'
        ])->get('/admin/menu/stock/' . $menu->id);
    }

    private function _callIndexAjaxReservation($menu, &$stocks)
    {
        $stock1 = $this->_createStock($menu->id, '2099-10-01', 5);
        $stock2 = $this->_createStock($menu->id, '2099-10-02', 5);
        $this->_createReservation($menu->id, '2099-10-01 10:00:00');
        $this->_createReservation($menu->id, '2099-10-01 10:00:00');
        $stocks = [$stock1, $stock2];
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest'
        ])->get('/admin/menu/stock/' . $menu->id);
    }

    private function _callAdd($menu)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/add', [
            'add_stock_number' => '5',
            'date' => '2099-10-03',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddValidationError($menu)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/add', [
            'add_stock_number' => '',
            'date' => '2099-10-03',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotAjax($menu)
    {
        return $this->post('/admin/menu/stock/add', [
            'add_stock_number' => '5',
            'date' => '2099-10-03',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEdit($menu, &$stock)
    {
        $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/edit', [
            'stock_id' => $stock->id,
            'stock_number' => '10',
            'stock_date' => '2099-10-01',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditValidationError($menu, &$stock)
    {
        $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/edit', [
            'stock_id' => $stock->id,
            'stock_number' => '',
            'stock_date' => '2099-10-01',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditNotAjax($menu, &$stock)
    {
        $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        return $this->post('/admin/menu/stock/edit', [
            'stock_id' => $stock->id,
            'stock_number' => '10',
            'stock_date' => '2099-10-01',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callBulkUpdate($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/bulk_update', [
            'year' => 2099,
            'month' => 10,
            'stock_number_all' => '10',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callBulkUpdateValidationError($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/bulk_update', [
            'year' => 2099,
            'month' => 10,
            'stock_number_all' => '',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callBulkUpdateThrowable($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/stock/bulk_update', [
            'year' => 'aaaa',
            'month' => 10,
            'stock_number_all' => '10',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callBulkUpdateNotAjax($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->post('/admin/menu/stock/bulk_update', [
            'year' => 2099,
            'month' => 10,
            'stock_number_all' => '10',
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callGetData($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->get('/admin/menu/stock/get_data/' . $stock->id);
    }

    private function _callDelete($menu, &$stock = null)
    {
        if (is_null($stock)) {
            $stock = $this->_createStock($menu->id, '2099-10-01', 5);
        }
        return $this->post('/admin/menu/stock/delete', [
            'id' => $stock->id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
