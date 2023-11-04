<?php

namespace Tests\Feature\Controller\Admin;

use App\Libs\ImageUpload;
use App\Models\Image;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoreImageControllerTest extends TestCase
{
    private $dirPath = 'images/test-code-test/store/';

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
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEditForm($store, $image);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.Image.edit');  // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'storeImageExists',
                'storeImages',
                'storeImageCodes',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('storeImageExists', true);
            $response->assertViewHas('storeImages', [$image->toArray()]);
            $response->assertViewHas('storeImageCodes', [
                'STORE_INSIDE' => '店舗内観',
                'STORE_OUTSIDE' => '店舗外観',
                'OTHER' => 'その他',
                'FOOD_LOGO' => 'フードロゴ',
                'RESTAURANT_LOGO' => 'レストランロゴ',
            ]);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEditForm($store2, $image);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.edit');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
        $response->assertStatus(302);                                              // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/image/edit");         // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の画像設定を更新しました。');

        $uploadFileName = $this->_getFileName($uploadFile);

        // 画像情報が変更されていることを確認する
        $result = Image::find($image->id);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result->url);
        $this->assertSame(1.0, $result->weight);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
        $response->assertStatus(302);                                              // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/image/edit");         // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の画像設定を更新しました。');

        $uploadFileName = $this->_getFileName($uploadFile);

        // 画像情報が変更されていることを確認する
        $result = Image::find($image->id);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result->url);
        $this->assertSame(1.0, $result->weight);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
            $response->assertStatus(302);                                              // リダイレクト
            $response->assertRedirect("/admin/store/{$store->id}/image/edit");         // リダイレクト先
            $response->assertSessionHas('message', '「テスト店舗」の画像設定を更新しました。');

            $uploadFileName = $this->_getFileName($uploadFile);

            // 画像情報が変更されていることを確認する
            $result = Image::find($image->id);
            $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result->url);
            $this->assertSame(1.0, $result->weight);

            // テスト用にアップロードしたファイルを削除しておく
            $this->_deleteImage($this->dirPath, $uploadFileName);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callEdit($store2, $image2, $uploadFile2, $oldFileName);
            $response->assertStatus(302);                                              // リダイレクト
            $response->assertRedirect("/admin/store/{$store2->id}/image/edit");        // リダイレクト先
            $response->assertSessionHas('custom_error', '「テスト店舗」の画像設定を更新できませんでした。');
            $this->_deleteImage($this->dirPath, $oldFileName);  // テスト用にアップした画像を削除しておく
        }

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
        $response->assertStatus(302);                                              // リダイレクト
        $response->assertRedirect("/admin/store/{$store->id}/image/edit");         // リダイレクト先
        $response->assertSessionHas('message', '「テスト店舗」の画像設定を更新しました。');

        $uploadFileName = $this->_getFileName($uploadFile);

        // 画像情報が変更されていることを確認する
        $result = Image::find($image->id);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result->url);
        $this->assertSame(1.0, $result->weight);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeAppCodes',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeAppCodes', [
            'TO' => 'テイクアウト',
            'RS' => 'レストラン',
        ]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAddForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeAppCodes',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeAppCodes', [
            'TO' => 'テイクアウト',
            'RS' => 'レストラン',
        ]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAddForm($store, $image);
            $response->assertStatus(200);
            $response->assertViewIs('admin.Store.Image.add');  // 指定bladeを確認
            $response->assertViewHasAll([
                'store',
                'storeImageExists',
                'storeImages',
                'storeAppCodes',
                'storeImageCodes',
            ]);                         // bladeに渡している変数を確認
            $response->assertViewHas('store', $store);
            $response->assertViewHas('storeImageExists', true);
            $response->assertViewHas('storeImages', [$image->toArray()]);
            $response->assertViewHas('storeAppCodes', [
                'TO' => 'テイクアウト',
                'RS' => 'レストラン',
            ]);
            $response->assertViewHas('storeImageCodes', [
                'STORE_INSIDE' => '店舗内観',
                'STORE_OUTSIDE' => '店舗外観',
                'OTHER' => 'その他',
                'FOOD_LOGO' => 'フードロゴ',
                'RESTAURANT_LOGO' => 'レストランロゴ',
            ]);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAddForm($store2, $image);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($store, $image);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Store.Image.add');  // 指定bladeを確認
        $response->assertViewHasAll([
            'store',
            'storeImageExists',
            'storeImages',
            'storeAppCodes',
            'storeImageCodes',
        ]);                         // bladeに渡している変数を確認
        $response->assertViewHas('store', $store);
        $response->assertViewHas('storeImageExists', true);
        $response->assertViewHas('storeImages', [$image->toArray()]);
        $response->assertViewHas('storeAppCodes', [
            'TO' => 'テイクアウト',
            'RS' => 'レストラン',
        ]);
        $response->assertViewHas('storeImageCodes', [
            'STORE_INSIDE' => '店舗内観',
            'STORE_OUTSIDE' => '店舗外観',
            'OTHER' => 'その他',
            'FOOD_LOGO' => 'フードロゴ',
            'RESTAURANT_LOGO' => 'レストランロゴ',
        ]);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd($store, $uploadFileName);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」店舗画像を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/image/edit",
        ]);

        // 画像情報が追加されていることを確認する
        $result = Image::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('RESTAURANT_LOGO', $result[0]['image_cd']);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result[0]['url']);
        $this->assertSame(0.0, $result[0]['weight']);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callAdd($store, $uploadFileName);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」店舗画像を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/image/edit",
        ]);

        // 画像情報が追加されていることを確認する
        $result = Image::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('RESTAURANT_LOGO', $result[0]['image_cd']);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result[0]['url']);
        $this->assertSame(0.0, $result[0]['weight']);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callAdd($store, $uploadFileName);
            $response->assertStatus(200)->assertJson([
                'success' => '「テスト店舗」店舗画像を追加しました。',
                'url' =>  env('ADMIN_URL') . "/store/{$store->id}/image/edit",
            ]);

            // 画像情報が追加されていることを確認する
            $result = Image::where('store_id', $store->id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('RESTAURANT_LOGO', $result[0]['image_cd']);
            $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result[0]['url']);
            $this->assertSame(0.0, $result[0]['weight']);

            // テスト用にアップロードしたファイルを削除しておく
            $this->_deleteImage($this->dirPath, $uploadFileName);
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callAdd($store2, $uploadFileName);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($store, $uploadFileName);
        $response->assertStatus(200)->assertJson([
            'success' => '「テスト店舗」店舗画像を追加しました。',
            'url' =>  env('ADMIN_URL') . "/store/{$store->id}/image/edit",
        ]);

        // 画像情報が追加されていることを確認する
        $result = Image::where('store_id', $store->id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('RESTAURANT_LOGO', $result[0]['image_cd']);
        $this->assertSame(ImageUpload::environment() . $this->dirPath . $uploadFileName, $result[0]['url']);
        $this->assertSame(0.0, $result[0]['weight']);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($this->dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddValidationError()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddValidationError($store);
        $response->assertStatus(200)->assertJson([
            'error' => ['店舗画像は必ず指定してください。'],
        ]);

        $this->logout();
    }

    public function testAddNotAjax()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddNotAjax($store);
        $response->assertStatus(200);

        // データが登録されていないことを確認する
        $this->assertFalse(Image::where('store_id', $store->id)->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callDelete($store, $uploadFileName);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 画像情報が削除されていることを確認する
        $this->assertFalse(Image::where('store_id', $store->id)->exists());
        // Storageからファイルが削除されていることを確認する
        $this->assertFalse($this->_checkExistsImage($this->dirPath, $uploadFileName));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callDelete($store, $uploadFileName);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 画像情報が削除されていることを確認する
        $this->assertFalse(Image::where('store_id', $store->id)->exists());
        // Storageからファイルが削除されていることを確認する
        $this->assertFalse($this->_checkExistsImage($this->dirPath, $uploadFileName));

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // 担当店舗の場合、正常にアクセスできること
        {
            $response = $this->_callDelete($store, $uploadFileName);
            $response->assertStatus(200)->assertJson(['result' => 'ok']);

            // 画像情報が削除されていることを確認する
            $this->assertFalse(Image::where('store_id', $store->id)->exists());
            // Storageからファイルが削除されていることを確認する
            $this->assertFalse($this->_checkExistsImage($this->dirPath, $uploadFileName));
        }

        // 担当外店舗の場合、アクセスできないこと
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $store2 = $this->_createStore($settlementCompany2->id);
            $response = $this->_callDelete($store2, $uploadFileName);
            $response->assertStatus(500);
            // テスト用にアップロードしたファイルを削除しておく
            $this->_deleteImage($this->dirPath, $uploadFileName);
        }

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($store, $uploadFileName);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // 画像情報が削除されていることを確認する
        $this->assertFalse(Image::where('store_id', $store->id)->exists());
        // Storageからファイルが削除されていることを確認する
        $this->assertFalse($this->_checkExistsImage($this->dirPath, $uploadFileName));

        $this->logout();
    }

    public function testStoreImageControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method editForm
        $response = $this->_callEditForm($store, $image);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->dirPath, $oldFileName);  // テスト用にアップした画像を削除しておく

        // target method addForm
        $response = $this->_callAddForm($store, $image);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store, $uploadFileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->dirPath, $uploadFileName);   // テスト用にアップロードしたファイルを削除しておく

        $this->logout();
    }

    public function testStoreImageControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // target method editForm
        $response = $this->_callEditForm($store, $image);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($store, $image, $uploadFile, $oldFileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->dirPath, $oldFileName);  // テスト用にアップした画像を削除しておく

        // target method addForm
        $response = $this->_callAddForm($store, $image);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($store, $uploadFileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->dirPath, $uploadFileName);   // テスト用にアップロードしたファイルを削除しておく

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

    private function _createImage($storeId, $url, $imageCd = 'RESTAURANT_LOGO')
    {
        $image = new Image();
        $image->store_id = $storeId;
        $image->menu_id = null;
        $image->image_cd = $imageCd;
        $image->url = $url;
        $image->weight = 100;
        $image->save();
        return $image;
    }

    private function _uploadFile($store, &$uploadFileName)
    {
        // ファイルのアップロード
        $image = UploadedFile::fake()->create('test-image.jpg'); // fakeファイルを用意
        ImageUpload::store($image, $this->dirPath);

        // アップロードしたファイル情報をDBに格納する
        $uploadFileName = $this->_getFileName($image);
        $url = ImageUpload::environment() . $this->dirPath . $uploadFileName;
        $imageModel = $this->_createImage($store->id, $url);

        return $imageModel;
    }

    private function _getFileName($image)
    {
        return basename($image) . '.' . $image->extension();
    }

    private function _deleteImage($dirPath, $fileName)
    {
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認
    }

    private function _checkExistsImage($dirPath, $fileName)
    {
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkFile = \Storage::disk('gcs')->allFiles($dirPath . $fileName);   // 指定フォルダ内のファイルを全て取得
        if (is_array($checkFile) && count($checkFile) > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function _callEditForm($store, &$image)
    {
        $image = $this->_createImage($store->id, null);
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/store?page=1'),
        ])->get("/admin/store/{$store->id}/image/edit");
    }

    private function _callEdit($store, &$image, &$uploadFile, &$oldFileName)
    {
        $image = $this->_uploadFile($store, $oldFileName);
        $uploadFile = UploadedFile::fake()->create("test-image2.png");
        return $this->withHeaders([
            'HTTP_REFERER' =>  url("/admin/store/{$store->id}/image/edit"),
        ])->post("/admin/store/{$store->id}/image/edit", [
            'storeImage' => [[
                'image_cd' => 'RESTAURANT_LOGO',
                'image_path' => $uploadFile,
                'weight' => 1,
                'id' => $image->id,
                'store_id' => $store->id,
            ]],
            'store_code' => $store->code,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm($store, &$image)
    {
        $image = $this->_createImage($store->id, null);
        return $this->get("/admin/store/{$store->id}/image/add");
    }

    private function _callAdd($store, &$uploadFileName)
    {
        $uploadFile = UploadedFile::fake()->create("test-image.png");
        $uploadFileName = $this->_getFileName($uploadFile);
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/image/add", [
            'storeImage' => [[
                'image_cd' => 'RESTAURANT_LOGO',
                'image_path' => $uploadFile,
            ]],
            'store_id' => $store->id,
            'store_code' => $store->code,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddValidationError($store)
    {
        return $this->withHeaders([
            'X-Requested-With' =>  'XMLHttpRequest',
        ])->post("/admin/store/image/add", [
            'storeImage' => [[
                'image_cd' => 'RESTAURANT_LOGO',
                'image_path' => null,
            ]],
            'store_id' => $store->id,
            'store_code' => $store->code,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddNotAjax($store)
    {
        $uploadFile = UploadedFile::fake()->create("test-image.png");
        return $this->post("/admin/store/image/add", [
            'storeImage' => [[
                'image_cd' => 'RESTAURANT_LOGO',
                'image_path' => $uploadFile,
            ]],
            'store_id' => $store->id,
            'store_code' => $store->code,
            'store_name' => $store->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete($store, &$fileName)
    {
        $image = $this->_uploadFile($store, $fileName);
        return $this->post("/admin/store/{$store->id}/image/delete/{$image->id}", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
