<?php

namespace Tests\Feature\Controller\Admin;

use App\Libs\ImageUpload;
use App\Http\Controllers\Admin\MenuImageController;
use App\Http\Requests\Admin\MenuImageAddRequest;
use App\Http\Requests\Admin\MenuImageEditRequest;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class MenuImageControllerTest extends TestCase
{
    private $menuImageController;
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->menuImageController = new MenuImageController();
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
        $response->assertViewIs('admin.Menu.Image.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuImageExists',
            'menuImages',
            'menuAppCodes',
            'menuImageCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEditForm($menu);
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Menu.Image.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuImageExists',
            'menuImages',
            'menuAppCodes',
            'menuImageCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

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
            $response->assertViewIs('admin.Menu.Image.edit');   // 指定bladeを確認
            $response->assertViewHasAll([
                'menu',
                'menuImageExists',
                'menuImages',
                'menuAppCodes',
                'menuImageCodes',
            ]);                                                 // bladeに渡している変数を確認
            $response->assertViewHas('menu', $menu);
            $response->assertViewHas('menuImageExists', 0);
            $response->assertViewHas('menuImages', null);
            $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
            $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);
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
        $response->assertViewIs('admin.Menu.Image.edit');   // 指定bladeを確認
        $response->assertViewHasAll([
            'menu',
            'menuImageExists',
            'menuImages',
            'menuAppCodes',
            'menuImageCodes',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像設定を更新しました。');

        list($beforeImage, $afterImage) = $images;

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $beforeFileName = $this->_getFileName($beforeImage);
        $afterFileName = $this->_getFileName($afterImage);

        // 更新されているかを確認
        $result = Image::find($menuImage->id);
        $this->assertNotNull($result);
        $this->assertSame('MENU_MAIN', $result->image_cd);
        $this->assertNotSame($this->_getUrl($beforeFileName), $result->url);
        $this->assertSame($this->_getUrl($afterFileName), $result->url);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);        // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                              // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                             // ファイル数が1である
        $this->assertSame($dirPath . $afterFileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $afterFileName);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                     // 社内一般としてログイン

        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像設定を更新しました。');

        list($beforeImage, $afterImage) = $images;

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $beforeFileName = $this->_getFileName($beforeImage);
        $afterFileName = $this->_getFileName($afterImage);

        // 更新されているかを確認
        $result = Image::find($menuImage->id);
        $this->assertNotNull($result);
        $this->assertSame('MENU_MAIN', $result->image_cd);
        $this->assertNotSame($this->_getUrl($beforeFileName), $result->url);
        $this->assertSame($this->_getUrl($afterFileName), $result->url);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);        // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                              // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                             // ファイル数が1である
        $this->assertSame($dirPath . $afterFileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $afterFileName);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像設定を更新しました。');

        list($beforeImage, $afterImage) = $images;

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $beforeFileName = $this->_getFileName($beforeImage);
        $afterFileName = $this->_getFileName($afterImage);

        // 更新されているかを確認
        $result = Image::find($menuImage->id);
        $this->assertNotNull($result);
        $this->assertSame('MENU_MAIN', $result->image_cd);
        $this->assertNotSame($this->_getUrl($beforeFileName), $result->url);
        $this->assertSame($this->_getUrl($afterFileName), $result->url);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);        // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                              // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                             // ファイル数が1である
        $this->assertSame($dirPath . $afterFileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $afterFileName);

        $this->logout();
    }

    public function testEditWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                    // 社外一般権限としてログイン

        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');   // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像設定を更新しました。');

        list($beforeImage, $afterImage) = $images;

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $beforeFileName = $this->_getFileName($beforeImage);
        $afterFileName = $this->_getFileName($afterImage);

        // 更新されているかを確認
        $result = Image::find($menuImage->id);
        $this->assertNotNull($result);
        $this->assertSame('MENU_MAIN', $result->image_cd);
        $this->assertNotSame($this->_getUrl($beforeFileName), $result->url);
        $this->assertSame($this->_getUrl($afterFileName), $result->url);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);        // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                              // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                             // ファイル数が1である
        $this->assertSame($dirPath . $afterFileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $afterFileName);

        $this->logout();
    }

    public function testEditThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuImageEditRequestのfile('menu')呼び出しで例外発生させるようにする
        $menuImageEditRequest = \Mockery::mock(MenuImageEditRequest::class)->makePartial();
        $menuImageEditRequest->shouldReceive('file')->once()->with('menu')->andThrow(new \Exception());
        $menuImageEditRequest->shouldReceive('input')->andReturn($menu->name); // input関数呼び出しの時はmenu->nameを渡しておく
        $this->app->instance(MenuImageEditRequest::class, $menuImageEditRequest);

        $response = $this->_callEditThrowable($menu, $images, $menuImage);
        $response->assertStatus(302);                                            // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');   // リダイレクト先
        $response->assertSessionHas('custom_error', '「' . $menu->name . '」の画像設定を更新できませんでした。');

        list($beforeImage, $afterImage) = $images;

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $beforeFileName = $this->_getFileName($beforeImage);
        $afterFileName = $this->_getFileName($afterImage);

        // 更新されていないかを確認
        $result = Image::find($menuImage->id);
        $this->assertNotNull($result);
        $this->assertSame('MENU_MAIN', $result->image_cd);
        $this->assertSame($this->_getUrl($beforeFileName), $result->url);
        $this->assertNotSame($this->_getUrl($afterFileName), $result->url);

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $beforeFileName);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Image.add');      // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'menuImageExists', 'menuImages', 'menuAppCodes', 'menuImageCodes']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Image.add');      // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'menuImageExists', 'menuImages', 'menuAppCodes', 'menuImageCodes']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Image.add');      // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'menuImageExists', 'menuImages', 'menuAppCodes', 'menuImageCodes']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testAddFormWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAddForm($menu);
        $response->assertStatus(200);                         // アクセス確認
        $response->assertViewIs('admin.Menu.Image.add');      // 指定bladeを確認
        $response->assertViewHasAll(['menu', 'menuImageExists', 'menuImages', 'menuAppCodes', 'menuImageCodes']);  // bladeに渡している変数を確認
        $response->assertViewHas('menu', $menu);
        $response->assertViewHas('menuImageExists', 0);
        $response->assertViewHas('menuImages', null);
        $response->assertViewHas('menuAppCodes', ['TO' => 'テイクアウト', 'RS' => 'レストラン']);
        $response->assertViewHas('menuImageCodes', ['MENU_MAIN' => 'メイン画像']);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callAdd($menu, $image);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像を追加しました。');


        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);

        // 登録されているかを確認
        $result = Image::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);     // 1件登録があること
        $this->assertSame('MENU_MAIN', $result[0]['image_cd']);
        $this->assertSame($this->_getUrl($fileName), $result[0]['url']);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                         // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                        // ファイル数が1である
        $this->assertSame($dirPath . $fileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $fileName);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callAdd($menu, $image);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像を追加しました。');

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);

        // 登録されているかを確認
        $result = Image::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);     // 1件登録があること
        $this->assertSame('MENU_MAIN', $result[0]['image_cd']);
        $this->assertSame($this->_getUrl($fileName), $result[0]['url']);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                         // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                        // ファイル数が1である
        $this->assertSame($dirPath . $fileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $fileName);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callAdd($menu, $image);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像を追加しました。');

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);

        // 登録されているかを確認
        $result = Image::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);     // 1件登録があること
        $this->assertSame('MENU_MAIN', $result[0]['image_cd']);
        $this->assertSame($this->_getUrl($fileName), $result[0]['url']);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                         // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                        // ファイル数が1である
        $this->assertSame($dirPath . $fileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $fileName);

        $this->logout();
    }

    public function testAddWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callAdd($menu, $image);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');  // リダイレクト先
        $response->assertSessionHas('message', '「テストメニュー」の画像を追加しました。');

        // アップロードファイル情報
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);

        // 登録されているかを確認
        $result = Image::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(1, $result);     // 1件登録があること
        $this->assertSame('MENU_MAIN', $result[0]['image_cd']);
        $this->assertSame($this->_getUrl($fileName), $result[0]['url']);

        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                         // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                        // ファイル数が1である
        $this->assertSame($dirPath . $fileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        $this->_deleteImage($dirPath, $fileName);

        $this->logout();
    }

    public function testAddThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        // MenuImageAddRequestのinput('image_path')呼び出しで例外発生させるようにする
        $menuImageAddRequest = \Mockery::mock(MenuImageAddRequest::class)->makePartial();
        $menuImageAddRequest->shouldReceive('file')->once()->with('image_path')->andThrow(new \Exception());
        $menuImageAddRequest->shouldReceive('input')->once()->with('menu_name')->andReturn($menu->name);
        $menuImageAddRequest->shouldReceive('input')->andReturn($menu->id); // input関数呼び出しの時はmenu->idを渡しておく
        $this->app->instance(MenuImageAddRequest::class, $menuImageAddRequest);

        $response = $this->_callAddThrowable($menu);
        $response->assertStatus(302);                                           // リダイレクト
        $response->assertRedirect('/admin/menu/' . $menu->id . '/image/edit');  // リダイレクト先
        $response->assertSessionHas('custom_error', '「' . $menu->name . '」の画像を追加できませんでした。');

        // 登録されているかを確認
        $result = Image::where('menu_id', $menu->id)->get()->toArray();
        $this->assertCount(0, $result);     // 登録されていないこと

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();           // 社内管理者としてログイン

        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Image::find($menuImage->id));

        // Storage側も削除されていること
        $dirPath = $this->_getDirPath();
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Image::find($menuImage->id));

        // Storage側も削除されていること
        $dirPath = $this->_getDirPath();
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認

        $this->logout();
    }

    public function testDeleteWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Image::find($menuImage->id));

        // Storage側も削除されていること
        $dirPath = $this->_getDirPath();
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認

        $this->logout();
    }

    public function testDeleteWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていること
        $this->assertNull(Image::find($menuImage->id));

        // Storage側も削除されていること
        $dirPath = $this->_getDirPath();
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認

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

    public function testMenuImageControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        $dirPath = $this->_getDirPath();

        // Controller内の関数にアクセスできないことを確認する

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(404);
        $fileName = $this->_getFileName($images[0]);
        $this->_deleteImage($dirPath, $fileName);               // テスト用にアップロードした画像を削除しておく

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $image);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(404);
        $fileName = $this->_getFileName($image);
        $this->_deleteImage($dirPath, $fileName);               // テスト用にアップロードした画像を削除しておく

        $this->logout();
    }

    public function testMenuImageControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        $dirPath = $this->_getDirPath();

        // target method editForm
        $response = $this->_callEditForm($menu);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($menu, $images, $menuImage);
        $response->assertStatus(404);
        $fileName = $this->_getFileName($images[0]);
        $this->_deleteImage($dirPath, $fileName);               // テスト用にアップロードした画像を削除しておく

        // target method addForm
        $response = $this->_callAddForm($menu);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store, $image);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($menu, $image, $menuImage);
        $response->assertStatus(404);
        $fileName = $this->_getFileName($image);
        $this->_deleteImage($dirPath, $fileName);               // テスト用にアップロードした画像を削除しておく

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

    private function _createImage($menu, $fileName = null)
    {
        $menuImage = new Image();
        $menuImage->menu_id = $menu->id;
        $menuImage->image_cd = 'MENU_MAIN';
        if (!is_null($fileName)) {
            $menuImage->url = $this->_getUrl($fileName);
        }
        $menuImage->save();
        return $menuImage;
    }

    private function _callEditForm($menu)
    {
        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu?page=10'),
        ])->get('/admin/menu/' . $menu->id . '/image/edit');
    }

    private function _callEdit($menu, &$images, &$menuImage)
    {
        $image = UploadedFile::fake()->create("test.png");
        $image2 = UploadedFile::fake()->create("test2.png");
        $images = [$image, $image2];

        // Imageデータ登録し、変更前ファイルをアップロード
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);
        $menuImage = $this->_createImage($menu, $fileName);
        ImageUpload::store($image, $dirPath);

        return $this->withHeaders([
            'HTTP_REFERER' =>  url('/admin/menu/' . $menu->id . '/image/edit'),
        ])->post('/admin/menu/' . $menu->id . '/image/edit', [
            'menu' => [[
                'id' => $menuImage->id,
                'image_cd' => 'MENU_MAIN',
                'image_path' => $image2,
                'menu_id' => $menu->id,
                'weight' => 1,
            ]],
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditThrowable($menu, &$images, &$menuImage)
    {
        return $this->_callEdit($menu, $images, $menuImage);
    }

    private function _callAddForm($menu)
    {
        return $this->get('/admin/menu/' . $menu->id . '/image/add');
    }

    private function _callAdd($menu, &$image)
    {
        $image = UploadedFile::fake()->create("test.png");
        return $this->post('/admin/menu/image/add', [
            'image_cd' => 'MENU_MAIN',
            'image_path' => $image,
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddThrowable($menu)
    {
        return $this->_callAdd($menu, $image);
    }

    private function _callDelete($menu, &$image, &$menuImage)
    {
        $image = UploadedFile::fake()->create("test.png");

        // Imageデータ登録し、変更前ファイルをアップロード
        $dirPath = $this->_getDirPath();
        $fileName = $this->_getFileName($image);
        $menuImage = $this->_createImage($menu, $fileName);
        ImageUpload::store($image, $dirPath);

        return $this->post('/admin/menu/' . $menu->id . '/image/delete/' . $menuImage->id, [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDeleteException($menu)
    {
        return $this->post('/admin/menu/' . $menu->id . '/image/delete/123456789012345', [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _deleteImage($dirPath, $fileName)
    {
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認
    }

    private function _getDirPath()
    {
        return env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX', '') . 'test-menu-image-controller/' . $this->menuImageController::MENU_IMAGE_PATH;
    }

    private function _getFileName($image)
    {
        return basename($image) . '.' . $image->extension();
    }

    private function _getUrl($fileName)
    {
        $dirPath = $this->_getDirPath();
        return ImageUpload::environment() . $dirPath . $fileName;
    }
}
