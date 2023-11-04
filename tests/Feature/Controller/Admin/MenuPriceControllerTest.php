<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Menu;
use App\Models\Price;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuPriceControllerTest extends TestCase
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

    public function testAddFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Price.add');      // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Price.add');      // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Price.add');      // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Price.add');      // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAdd($menu, $priceIds);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の料金を追加しました。',
            'url' => env('APP_URL') . '/admin/menu/' . $menu->id . '/price/edit',
        ]);

        // 登録されているかを確認
        $result = Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->get();
        $this->assertCount(2, $result);     // 2件登録があること
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('2022-01-01', $result[0]['start_date']);
        $this->assertSame('2022-12-31', $result[0]['end_date']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);
        $this->assertSame('1600', $result[1]['price']);
        $this->assertSame('2023-01-01', $result[1]['start_date']);
        $this->assertSame('2023-12-31', $result[1]['end_date']);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAdd($menu, $priceIds);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の料金を追加しました。',
            'url' => env('APP_URL') . '/admin/menu/' . $menu->id . '/price/edit',
        ]);

        // 登録されているかを確認
        $result = Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->get();
        $this->assertCount(2, $result);     // 2件登録があること
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('2022-01-01', $result[0]['start_date']);
        $this->assertSame('2022-12-31', $result[0]['end_date']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);
        $this->assertSame('1600', $result[1]['price']);
        $this->assertSame('2023-01-01', $result[1]['start_date']);
        $this->assertSame('2023-12-31', $result[1]['end_date']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAdd($menu, $priceIds);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の料金を追加しました。',
            'url' => env('APP_URL') . '/admin/menu/' . $menu->id . '/price/edit',
        ]);

        // 登録されているかを確認
        $result = Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->get();
        $this->assertCount(2, $result);     // 2件登録があること
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('2022-01-01', $result[0]['start_date']);
        $this->assertSame('2022-12-31', $result[0]['end_date']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);
        $this->assertSame('1600', $result[1]['price']);
        $this->assertSame('2023-01-01', $result[1]['start_date']);
        $this->assertSame('2023-12-31', $result[1]['end_date']);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($menu, $priceIds);
        $response->assertStatus(200)->assertJson([
            'success' => '「テストメニュー」の料金を追加しました。',
            'url' => env('APP_URL') . '/admin/menu/' . $menu->id . '/price/edit',
        ]);

        // 登録されているかを確認
        $result = Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->get();
        $this->assertCount(2, $result);     // 2件登録があること
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('2022-01-01', $result[0]['start_date']);
        $this->assertSame('2022-12-31', $result[0]['end_date']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);
        $this->assertSame('1600', $result[1]['price']);
        $this->assertSame('2023-01-01', $result[1]['start_date']);
        $this->assertSame('2023-12-31', $result[1]['end_date']);

        $this->logout();
    }

    public function testAddDuplication()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // 入力内で重複
        {
            $response = $this->_callAddDepulication($menu, $priceIds);
            $response->assertStatus(200)->assertJson([
                'error' => ['メニュー料金の日付期間が重複しています。'],
            ]);

            // 登録されていないことを確認
            $this->assertFalse(Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->exists());
        }

        // DB登録分と重複
        {
            $response = $this->_callAddDepulication2($menu, $priceIds);
            $response->assertStatus(200)->assertJson([
                'error' => ['メニュー料金の日付期間が重複しています。'],
            ]);

            // 登録されていないことを確認
            $this->assertFalse(Price::where('menu_id', $menu->id)->whereNotIn('id', $priceIds)->exists());
        }

        $this->logout();
    }

    public function testAddNotAjax()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAddNotAjax($menu);
        $response->assertStatus(200);

        // 登録されていないことを確認
        $this->assertFalse(Price::where('menu_id', $menu->id)->exists());

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();               // 社内管理者としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Price.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Price.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗の画像変更
        {
            $response = $this->_callEditForm($menu);
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Menu.Price.edit');   // 指定bladeを確認
            $response->assertViewHasAll([
                'menu',
                'menuPriceExists',
                'menuPrices',
                'menuPriceCodes',
            ]);                                                 // bladeに渡している変数を確認
            $response->assertViewHas('menu', $menu);
            $response->assertViewHas('menuPriceExists', 0);
            $response->assertViewHas('menuPrices', null);
            $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);
        }

        // 担当外店舗の画像変更
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
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Price.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuPriceExists',
            'menuPrices',
            'menuPriceCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuPriceExists', 0);
        $response->assertViewHas('menuPrices', null);
        $response->assertViewHas('menuPriceCodes', ['NORMAL' => '通常', 'SALE' => '割引']);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/price/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の料金を保存しました。');

        // 更新されているかを確認
        $result = Price::where('menu_id', $menu->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($price->id, $result[0]['id']);                // 更新分
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('2023-10-01', $result[0]['start_date']);
        $this->assertSame('2023-12-01', $result[0]['end_date']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);            // 追加分
        $this->assertSame('2024-01-01', $result[1]['start_date']);
        $this->assertSame('2024-10-01', $result[1]['end_date']);
        $this->assertSame('2000', $result[1]['price']);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/price/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の料金を保存しました。');

        // 更新されているかを確認
        $result = Price::where('menu_id', $menu->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($price->id, $result[0]['id']);                // 更新分
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('2023-10-01', $result[0]['start_date']);
        $this->assertSame('2023-12-01', $result[0]['end_date']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);            // 追加分
        $this->assertSame('2024-01-01', $result[1]['start_date']);
        $this->assertSame('2024-10-01', $result[1]['end_date']);
        $this->assertSame('2000', $result[1]['price']);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/price/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の料金を保存しました。');

        // 更新されているかを確認
        $result = Price::where('menu_id', $menu->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($price->id, $result[0]['id']);                // 更新分
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('2023-10-01', $result[0]['start_date']);
        $this->assertSame('2023-12-01', $result[0]['end_date']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);            // 追加分
        $this->assertSame('2024-01-01', $result[1]['start_date']);
        $this->assertSame('2024-10-01', $result[1]['end_date']);
        $this->assertSame('2000', $result[1]['price']);

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(302);                                       // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/price/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の料金を保存しました。');

        // 更新されているかを確認
        $result = Price::where('menu_id', $menu->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($price->id, $result[0]['id']);                // 更新分
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('2023-10-01', $result[0]['start_date']);
        $this->assertSame('2023-12-01', $result[0]['end_date']);
        $this->assertSame('1500', $result[0]['price']);
        $this->assertSame('NORMAL', $result[1]['price_cd']);            // 追加分
        $this->assertSame('2024-01-01', $result[1]['start_date']);
        $this->assertSame('2024-10-01', $result[1]['end_date']);
        $this->assertSame('2000', $result[1]['price']);

        $this->logout();
    }

    public function testEditDuplication()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEditDipulication($menu, $price);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/price/edit');  // リダイレクト先

        // 追加・更新されていないことを確認
        $result = Price::where('menu_id', $menu->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($price->id, $result[0]['id']);
        $this->assertSame('NORMAL', $result[0]['price_cd']);
        $this->assertSame('2020-01-01', $result[0]['start_date']);
        $this->assertSame('2020-06-30', $result[0]['end_date']);
        $this->assertSame('1200', $result[0]['price']);

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Price::find($price->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Price::find($price->id));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Price::find($price->id));

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Price::find($price->id));

        $this->logout();
    }

    public function testDeleteException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callDeleteException($menu);
        $response->assertStatus(500);

        $this->logout();
    }

    public function testMenuPriceControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $priceIds);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testMenuPriceControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $priceIds);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $price);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $price);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->code = 'test-menu-image-controller';
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

    private function _createStoreMenu($published = 0)
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published);
        return [$store, $menu];
    }

    private function _createPrice($menu, $startDate, $endDate, $menuPrice)
    {
        $price = new Price();
        $price->menu_id = $menu->id;
        $price->price_cd = 'NORMAL';
        $price->start_date = $startDate;
        $price->end_date = $endDate;
        $price->price = $menuPrice;
        $price->save();
        return $price;
    }

    private function _callAddForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/price/add');
    }

    private function _callAdd($menu, &$priceIds)
    {
        $price = $this->_createPrice($menu, '2021-01-01', '2021-06-30', '1200');
        $price2 = $this->_createPrice($menu, '2021-07-01', '2021-12-31', '1500');
        $priceIds = [$price->id, $price2->id];

        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $menu->id . '/price/add', [
            'menu' => [
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2022-01-01',
                    'price_end_date' => '2022-12-31',
                    'price' => 1500,
                ],
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-01-01',
                    'price_end_date' => '2023-12-31',
                    'price' => 1600,
                ],
            ],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddDepulication($menu, &$priceIds)
    {
        $priceIds = [];
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $menu->id . '/price/add', [
            'menu' => [
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-01-01',
                    'price_end_date' => '2023-12-31',
                    'price' => 1600,
                ],                                          // 入力での重複
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-02-01',
                    'price_end_date' => '2024-12-31',
                    'price' => 1600,
                ],                                          // 入力での重複
            ],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddDepulication2($menu, &$priceIds)
    {
        $price = $this->_createPrice($menu, '2020-01-01', '2020-06-30', '1200');
        $price2 = $this->_createPrice($menu, '2021-01-01', '2021-06-30', '1200');
        $price3 = $this->_createPrice($menu, '2022-01-01', '2022-06-30', '1200');
        $priceIds = [$price->id, $price2->id, $price3->id];

        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post('/admin/menu/' . $menu->id . '/price/add', [
            'menu' => [
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2022-01-01',
                    'price_end_date' => '2022-12-31',
                    'price' => 1500,
                ],                                          // DBとの重複
            ],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotAjax($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/price/add', [
            'menu' => [
                [
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2021-01-01',
                    'price_end_date' => '2021-12-31',
                    'price' => 1500,
                ],
            ],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditForm($menu)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu?page=10'),
        ])->get('/admin/menu/' . $menu->id . '/price/edit');
    }

    private function _callEdit($menu, &$price)
    {
        $price = $this->_createPrice($menu, '2020-01-01', '2020-06-30', '1200');
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu/' . $menu->id . '/price/edit'),
        ])->post('/admin/menu/' . $menu->id . '/price/edit', [
            'menu' => [
                [
                    'price_id' => $price->id,
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-10-01',
                    'price_end_date' => '2023-12-01',
                    'price' => '1500',
                ],
                [
                    'id' => null,
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2024-01-01',
                    'price_end_date' => '2024-10-01',
                    'price' => '2000',
                ],
            ],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditDipulication($menu, &$price)
    {
        $price = $this->_createPrice($menu, '2020-01-01', '2020-06-30', '1200');
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu/' . $menu->id . '/price/edit'),
        ])->post('/admin/menu/' . $menu->id . '/price/edit', [
            'menu' => [
                [
                    'price_id' => $price->id,
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-10-01',
                    'price_end_date' => '2023-12-01',
                    'price' => '1500',
                ],
                [
                    'id' => null,
                    'price_cd' => 'NORMAL',
                    'price_start_date' => '2023-10-01',
                    'price_end_date' => '2024-10-01',
                    'price' => '2000',
                ],
            ],
            'menu_id' => $menu->id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($menu, &$price)
    {
        $price = $this->_createPrice($menu, '2020-01-01', '2020-06-30', '1200');
        return $this->post('/admin/menu/' . $menu->id . '/price/delete/' . $price->id, [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDeleteException($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/price/delete/12345678912345', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
