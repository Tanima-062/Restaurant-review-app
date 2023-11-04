<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\StoreGenreRequestAdd;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreGenreControllerTest extends TestCase
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
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.detailed_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-DETAILED');

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.detailed_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-DETAILED');

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAddForm($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.Genre.detailed_add');  // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'middleGenres',
                'bigGenre',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('bigGenre', 'B-DETAILED');
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAddForm($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.detailed_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-DETAILED');

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを追加しました');

        // 登録されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($smallGenre->id, $result[0]['genre_id']);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを追加しました');

        // 登録されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($smallGenre->id, $result[0]['genre_id']);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを追加しました');

        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($smallGenre->id, $result[0]['genre_id']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'ジャンルを追加しました');

        // 登録されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame($smallGenre->id, $result[0]['genre_id']);

        $this->logout();
    }

    public function testAddNotExistsGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddNotExistsGenre($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/detailed/add");  // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルが存在しませんでした');

        // 登録されていないことを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testAddDuplication()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddDuplication($store, $smallGenre);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/detailed/add");  // リダイレクト先
        $response->assertSessionHas('custom_error', '既に登録済みです');

        // 登録データが増えていないこと（一件のまま）
        $this->assertCount(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenre->id)->get());

        $this->logout();
    }

    public function testAddThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // CallTracerRequestのinput関数呼び出しで例外発生させるようにする
        $storeGenreRequestAdd = \Mockery::mock(StoreGenreRequestAdd::class)->makePartial();
        $storeGenreRequestAdd->shouldReceive('input')->andThrow(new \Exception());
        $this->app->instance(StoreGenreRequestAdd::class, $storeGenreRequestAdd);

        $response = $this->_callAddNotExistsGenre($store);
        $response->assertStatus(302);                                               // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");  // リダイレクト先
        $response->assertSessionHas('custom_error', 'ジャンルを追加できませんでした');

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'storeName',
            'bigGenre',
            'cookingGenre',
            'detailedMiddleGenres',
            'cookingMiddleGenres',
            'detailedGenreGroups',
            'cookingGenreGroups',
            'id',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('storeName', $store->name);
        $response->assertViewHas('bigGenre', 'B-DETAILED');
        $response->assertViewHas('cookingGenre', 'B-COOKING');
        $response->assertViewHas('id', $store->id);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'storeName',
            'bigGenre',
            'cookingGenre',
            'detailedMiddleGenres',
            'cookingMiddleGenres',
            'detailedGenreGroups',
            'cookingGenreGroups',
            'id',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('storeName', $store->name);
        $response->assertViewHas('bigGenre', 'B-DETAILED');
        $response->assertViewHas('cookingGenre', 'B-COOKING');
        $response->assertViewHas('id', $store->id);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEditForm($store);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.Genre.edit');  // 指定bladeを確認
            $response->assertViewHasAll([
                'storeName',
                'bigGenre',
                'cookingGenre',
                'detailedMiddleGenres',
                'cookingMiddleGenres',
                'detailedGenreGroups',
                'cookingGenreGroups',
                'id',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('storeName', $store->name);
            $response->assertViewHas('bigGenre', 'B-DETAILED');
            $response->assertViewHas('cookingGenre', 'B-COOKING');
            $response->assertViewHas('id', $store->id);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEditForm($store2);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'storeName',
            'bigGenre',
            'cookingGenre',
            'detailedMiddleGenres',
            'cookingMiddleGenres',
            'detailedGenreGroups',
            'cookingGenreGroups',
            'id',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('storeName', $store->name);
        $response->assertViewHas('bigGenre', 'B-DETAILED');
        $response->assertViewHas('cookingGenre', 'B-COOKING');
        $response->assertViewHas('id', $store->id);

        $this->logout();
    }

    public function testEditFormNotExistsGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditFormNotExistsGenre($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'storeName',
            'bigGenre',
            'cookingGenre',
            'detailedMiddleGenres',
            'cookingMiddleGenres',
            'detailedGenreGroups',
            'cookingGenreGroups',
            'id',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('storeName', $store->name);
        $response->assertViewHas('bigGenre', 'B-DETAILED');
        $response->assertViewHas('cookingGenre', 'B-COOKING');
        $response->assertViewHas('detailedGenreGroups', collect()); // 店舗に紐づくジャンル情報がないため、初期値である
        $response->assertViewHas('cookingGenreGroups', collect());  // 店舗に紐づくジャンル情報がないため、初期値である
        $response->assertViewHas('id', $store->id);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'こだわりジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'こだわりジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEdit($store, $smallGenres);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', 'こだわりジャンルを保存しました');

            // ジャンルグループ情報が保存されていることを確認する
            $result = GenreGroup::where('store_id', $store->id)->get();
            $this->assertCount(2, $result);
            $this->assertSame($smallGenres[0], $result[0]['genre_id']);
            $this->assertSame($smallGenres[1], $result[1]['genre_id']);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEdit($store2, $smallGenres);
            $response->assertStatus(302);
            $response->assertSessionHas('custom_error', 'こだわりジャンルを保存できませんでした');
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', 'こだわりジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testEditDuplicationSelectGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditDuplicationSelectGenre($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', 'こだわりジャンルは重複して登録することはできません');

        // ジャンルグループ情報が保存されていないことを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditNotExistsGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditNotExistsGenre($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', 'こだわりジャンルが存在しませんでした');

        // ジャンルグループ情報が保存されていないことを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testEditDupulicationGenreGroup()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditDupulicationGenreGroup($store, $targetGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', 'こだわりジャンルを保存できませんでした');

        // 1ジャンルに対してジャンルグループは一件登録されていないことを確認する
        $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store, $smallGenres);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 指定したジャンルグループだけが削除されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[0])->exists());
        $this->assertTrue(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[1])->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store, $smallGenres);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 指定したジャンルグループだけが削除されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[0])->exists());
        $this->assertTrue(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[1])->exists());

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、削除できること
        {
            $response = $this->_callDelete($store, $smallGenres);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 指定したジャンルグループだけが削除されていることを確認する
            $this->assertFalse(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[0])->exists());
            $this->assertTrue(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[1])->exists());
        }

        // 担当外店舗の場合、削除できないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2, $smallGenres2);
            $response->assertStatus(500);

            // 指定したジャンルグループも削除されていないことを確認する
            $this->assertTrue(GenreGroup::where('store_id', $store2->id)->where('genre_id', $smallGenres2[0])->exists());
            $this->assertTrue(GenreGroup::where('store_id', $store2->id)->where('genre_id', $smallGenres2[1])->exists());
        }

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($store, $smallGenres);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 指定したジャンルグループだけが削除されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[0])->exists());
        $this->assertTrue(GenreGroup::where('store_id', $store->id)->where('genre_id', $smallGenres[1])->exists());

        $this->logout();
    }

    public function testCookingEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', '料理ジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testCookingEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callCookingEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', '料理ジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testCookingEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callCookingEdit($store, $smallGenres);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを保存しました');

            // ジャンルグループ情報が保存されていることを確認する
            $result = GenreGroup::where('store_id', $store->id)->get();
            $this->assertCount(2, $result);
            $this->assertSame($smallGenres[0], $result[0]['genre_id']);
            $this->assertSame($smallGenres[1], $result[1]['genre_id']);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callCookingEdit($store2, $smallGenres);
            $response->assertStatus(302);
            $response->assertSessionHas('custom_error', '料理ジャンルを保存できませんでした');
        }

        $this->logout();
    }

    public function testCookingEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callCookingEdit($store, $smallGenres);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('message', '料理ジャンルを保存しました');

        // ジャンルグループ情報が保存されていることを確認する
        $result = GenreGroup::where('store_id', $store->id)->get();
        $this->assertCount(2, $result);
        $this->assertSame($smallGenres[0], $result[0]['genre_id']);
        $this->assertSame($smallGenres[1], $result[1]['genre_id']);

        $this->logout();
    }

    public function testCookingEditNoSelectMainGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditNoSelectMainGenre($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', 'メインジャンルを選択してください');

        // ジャンルグループ情報が保存されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCookingEditSelectedGenreError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditSelectedGenreError($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', '小ジャンル2が設定されている場合はメインジャンルに設定できません');

        // ジャンルグループ情報が保存されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCookingEditSelectMoreMainGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditSelectMoreMainGenre($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', 'メインジャンルは2つ以上設定できません');

        // ジャンルグループ情報が保存されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCookingEditNotExistsGenre()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditNotExistsGenre($store);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', '料理ジャンルが存在しませんでした');

        // ジャンルグループ情報が保存されていることを確認する
        $this->assertFalse(GenreGroup::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testCookingEditDuplication()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditDuplication($store, $targetGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', '料理ジャンルは重複して登録することはできません。');

        // 1ジャンルに対してジャンルグループは一件登録されていないことを確認する
        $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());

        $this->logout();
    }

    public function testCookingEditDuplication2()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingEditDuplication2($store, $targetGenre);
        $response->assertStatus(302);                                             // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
        $response->assertSessionHas('custom_error', '料理ジャンルを保存できませんでした');

        // 1ジャンルに対してジャンルグループは一件登録されていないことを確認する
        $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());

        $this->logout();
    }

    public function testCookingAddFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.cooking_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-COOKING');

        $this->logout();
    }

    public function testCookingAddFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.cooking_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-COOKING');

        $this->logout();
    }

    public function testCookingAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.cooking_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-COOKING');

        $this->logout();
    }

    public function testCookingAddFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Genre.cooking_add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'middleGenres',
            'bigGenre',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('bigGenre', 'B-COOKING');

        $this->logout();
    }

    public function testCookingAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // ジャンル小を登録する場合
        {
            $response = $this->_callCookingAdd($store, false, $targetGenre);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを追加しました');

            // ジャンルグループ情報が保存されていることを確認する
            $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());
        }

        // ジャンル小2を登録する場合
        {
            $response = $this->_callCookingAdd($store, true, $targetGenre);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを追加しました');

            // ジャンルグループ情報が保存されていることを確認する
            $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());
        }

        $this->logout();
    }

    public function testCookingAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // ジャンル小を登録する場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callCookingAdd($store, false, $targetGenre);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを追加しました');

            // ジャンルグループ情報が保存されていることを確認する
            $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());
        }

        $this->logout();
    }

    public function testCookingAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // ジャンル小を登録する場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callCookingAdd($store, false, $targetGenre);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを追加しました');

            // ジャンルグループ情報が保存されていることを確認する
            $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());
        }

        $this->logout();
    }

    public function testCookingAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // ジャンル小を登録する場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callCookingAdd($store, false, $targetGenre);
            $response->assertStatus(302);                                             // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/genre/edit");        // リダイレクト先
            $response->assertSessionHas('message', '料理ジャンルを追加しました');

            // ジャンルグループ情報が保存されていることを確認する
            $this->assertSame(1, GenreGroup::where('store_id', $store->id)->where('genre_id', $targetGenre->id)->get()->count());
        }

        $this->logout();
    }

    public function testStoreGenreControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $smallGenres);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store, $smallGenres);
        $response->assertStatus(404);

        // target method cookingEdit
        $response = $this->_callCookingEdit($store, $smallGenres);
        $response->assertStatus(404);

        // target method cookingAddForm
        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(404);

        // target method cookingAdd
        $response = $this->_callCookingAdd($store, false, $targetGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStoreGenreControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method addForm
        $response = $this->_callAddForm($store);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $smallGenre);
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $smallGenres);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store, $smallGenres);
        $response->assertStatus(404);

        // target method cookingEdit
        $response = $this->_callCookingEdit($store, $smallGenres);
        $response->assertStatus(404);

        // target method cookingAddForm
        $response = $this->_callCookingAddForm($store);
        $response->assertStatus(404);

        // target method cookingAdd
        $response = $this->_callCookingAdd($store, false, $targetGenre);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStore($settlementCompanyId, $appCd = 'RS')
    {
        $store = new Store();
        $store->app_cd = $appCd;
        $store->code = 'test-code-test';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->area_id = 1;
        $store->published = 0;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _createGenre($level, $name, $genreCd, $path)
    {
        $genre = new Genre();
        $genre->level = $level;
        $genre->name = $name;
        $genre->genre_cd = $genreCd;
        $genre->published = 1;
        $genre->path = $path;
        $genre->save();
        return $genre;
    }

    private function _createGenreGroup($storeId, $genreId, $isDelegate = 0)
    {
        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $storeId;
        $genreGroup->genre_id = $genreId;
        $genreGroup->is_delegate = $isDelegate;
        $genreGroup->save();
        return $genreGroup;
    }

    private function _callAddForm($store)
    {
        return $this->get("/admin/store/{$store->id}/genre/detailed/add");
    }

    private function _callAdd($store, &$smallGenre)
    {
        $middelGenre = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-genre', '/b-detailed');
        $smallGenre = $this->_createGenre(3, 'テスト小ジャンル', 's-test-genre', '/b-detailed/m-test-genre');
        return $this->post("/admin/store/{$store->id}/genre/detailed/add", [
            'middle_genre' => 'm-test-genre',
            'small_genre' => 's-test-genre',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotExistsGenre($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/detailed/add", [
            'middle_genre' => 'm-test-genre',
            'small_genre' => 's-test-genre',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddDuplication($store, &$smallGenre)
    {
        $middelGenre = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-genre', '/b-detailed');
        $smallGenre = $this->_createGenre(3, 'テスト小ジャンル', 's-test-genre', '/b-detailed/m-test-genre');
        $this->_createGenreGroup($store->id, $smallGenre->id);
        return $this->post("/admin/store/{$store->id}/genre/detailed/add", [
            'middle_genre' => 'm-test-genre',
            'small_genre' => 's-test-genre',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditForm($store)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-d-genre', '/b-detailed');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre', '/b-detailed/m-test-d-genre');
        $middelGenre2 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre', '/b-cooking');
        $smallGenre2 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $small2Genre2 = $this->_createGenre(4, 'テスト2小ジャンル', 'i-test-c-genre', '/b-cooking/m-test-c-genre/s-test-c-genre');
        $this->_createGenreGroup($store->id, $smallGenre1->id);
        $this->_createGenreGroup($store->id, $small2Genre2->id);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/{$store->id}/genre/edit");
    }

    private function _callEditFormNotExistsGenre($store)
    {
        $this->_createGenreGroup($store->id, 1234567890);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/{$store->id}/genre/edit");
    }

    private function _callEdit($store, &$smallGenres)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-d-genre', '/b-detailed');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre', '/b-detailed/m-test-d-genre');
        $smallGenre2 = $this->_createGenre(3, 'テスト小ジャンル2', 's-test-d-genre2', '/b-detailed/m-test-d-genre');
        $smallGenres = [$smallGenre1->id, $smallGenre2->id];
        $genreGroup = $this->_createGenreGroup($store->id, $smallGenre1->id);
        return $this->post("/admin/store/{$store->id}/genre/edit", [
            'genre_group_id' => [$genreGroup->id, null],
            'middle_genre' => [$middelGenre1->genre_cd, $middelGenre1->genre_cd],
            'small_genre' => [$smallGenre1->genre_cd, $smallGenre2->genre_cd],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditDuplicationSelectGenre($store)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-d-genre', '/b-detailed');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre', '/b-detailed/m-test-d-genre');
        return $this->post("/admin/store/{$store->id}/genre/edit", [
            'genre_group_id' => [null, null],
            'middle_genre' => [$middelGenre1->genre_cd, $middelGenre1->genre_cd],
            'small_genre' => [$smallGenre1->genre_cd, $smallGenre1->genre_cd],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditNotExistsGenre($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/edit", [
            'genre_group_id' => [null, null],
            'middle_genre' => ['m-test-d-genre'],
            'small_genre' => ['s-test-d-genre'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditDupulicationGenreGroup($store, &$smallGenre1)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-d-genre', '/b-detailed');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre', '/b-detailed/m-test-d-genre');
        $genreGroup = $this->_createGenreGroup($store->id, $smallGenre1->id);
        return $this->post("/admin/store/{$store->id}/genre/edit", [
            'genre_group_id' => [null],
            'middle_genre' => [$middelGenre1->genre_cd],
            'small_genre' => [$smallGenre1->genre_cd],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store, &$smallGenres)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-d-genre', '/b-detailed');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre', '/b-detailed/m-test-d-genre');
        $smallGenre2 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-d-genre2', '/b-detailed/m-test-d-genre');
        $genreGroup1 = $this->_createGenreGroup($store->id, $smallGenre1->id);
        $genreGroup2 = $this->_createGenreGroup($store->id, $smallGenre2->id);
        $smallGenres = [$smallGenre1->id, $smallGenre2->id];
        return $this->post("/admin/store/genre/delete/{$genreGroup1->id}", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEdit($store, &$smallGenres)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre', '/b-cooking');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $middelGenre2 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre2', '/b-cooking');
        $smallGenre2 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre2', '/b-cooking/m-test-c-genre2');
        $small2Genre2 = $this->_createGenre(3, 'テスト小ジャンル2', 'i-test-c-genre2', '/b-cooking/m-test-c-genre2/s-test-c-genre2');
        $smallGenres = [$smallGenre1->id, $small2Genre2->id];
        $genreGroup = $this->_createGenreGroup($store->id, $smallGenre1->id);
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [$genreGroup->id, null],
            'cooking_middle_genre' => [$middelGenre1->genre_cd, $middelGenre2->genre_cd],
            'cooking_small_genre' => [$smallGenre1->genre_cd, $smallGenre2->genre_cd],
            'cooking_small2_genre' => [null, $small2Genre2->genre_cd],
            'cooking_delegate' => ['main', 'normal'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditNoSelectMainGenre($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [null],
            'cooking_middle_genre' => ['m-test-c-genre'],
            'cooking_small_genre' => ['s-test-c-genre'],
            'cooking_small2_genre' => [null],
            'cooking_delegate' => ['normal'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditSelectMoreMainGenre($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [null, null],
            'cooking_middle_genre' => ['m-test-c-genre', 'm-test-c-genre'],
            'cooking_small_genre' => ['s-test-c-genre', 's-test-c-genre2'],
            'cooking_small2_genre' => [null, null],
            'cooking_delegate' => ['main', 'main'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditSelectedGenreError($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [null],
            'cooking_middle_genre' => ['m-test-c-genre'],
            'cooking_small_genre' => ['s-test-c-genre'],
            'cooking_small2_genre' => ['s-test-c-genre2'],
            'cooking_delegate' => ['main'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditNotExistsGenre($store)
    {
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [null],
            'cooking_middle_genre' => ['m-test-c-genre'],
            'cooking_small_genre' => ['s-test-c-genre'],
            'cooking_small2_genre' => [null],
            'cooking_delegate' => ['main'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditDuplication($store, &$smallGenre1)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre', '/b-cooking');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $smallGenre2 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $genreGroup = $this->_createGenreGroup($store->id, $smallGenre1->id, 1);
        $genreGroup2 = $this->_createGenreGroup($store->id, $smallGenre2->id, 1);
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [$genreGroup->id, $smallGenre2->id],
            'cooking_middle_genre' => [$middelGenre1->genre_cd, $middelGenre1->genre_cd],
            'cooking_small_genre' => [$smallGenre1->genre_cd, $smallGenre1->genre_cd],      // 2つとも同じgenreに変更しようとする
            'cooking_small2_genre' => [null], null,
            'cooking_delegate' => ['main', 'normal'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingEditDuplication2($store, &$smallGenre1)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre', '/b-cooking');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $genreGroup = $this->_createGenreGroup($store->id, $smallGenre1->id, 1);
        return $this->post("/admin/store/{$store->id}/genre/cooking/edit", [
            'cooking_genre_group_id' => [null],
            'cooking_middle_genre' => [$middelGenre1->genre_cd],
            'cooking_small_genre' => [$smallGenre1->genre_cd],
            'cooking_small2_genre' => [null],
            'cooking_delegate' => ['main'],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCookingAddForm($store)
    {
        return $this->get("/admin/store/{$store->id}/genre/cooking/add");
    }

    private function _callCookingAdd($store, $addGenre2, &$targetGenre)
    {
        $middelGenre1 = $this->_createGenre(2, 'テスト中ジャンル', 'm-test-c-genre', '/b-cooking');
        $smallGenre1 = $this->_createGenre(3, 'テスト小ジャンル', 's-test-c-genre', '/b-cooking/m-test-c-genre');
        $small2Genre1Cd = null;
        $targetGenre = $smallGenre1;
        if ($addGenre2) {
            $small2Genre1 = $this->_createGenre(3, 'テスト小ジャンル', 'i-test-c-genre', '/b-cooking/m-test-c-genre/s-test-c-genre');
            $small2Genre1Cd = $small2Genre1->genre_cd;
            $targetGenre = $small2Genre1;
        }
        return $this->post("/admin/store/{$store->id}/genre/cooking/add", [
            'middle_genre' => $middelGenre1->genre_cd,
            'small_genre' => $smallGenre1->genre_cd,
            'small2_genre' => $small2Genre1Cd,
            'is_delegate' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
