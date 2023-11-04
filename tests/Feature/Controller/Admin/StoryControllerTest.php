<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\StoryAddRequest;
use App\Http\Requests\Admin\StoryEditRequest;
use App\Libs\ImageUpload;
use App\Models\Image;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\Story;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StoryControllerTest extends TestCase
{
    private $dirPath = 'images/story';

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
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Story.index');       // 指定bladeを確認
        $response->assertViewHasAll(['stories', 'app_cd']); // bladeに渡している変数を確認
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                       // アクセス確認
        $response->assertViewIs('admin.Story.index');       // 指定bladeを確認
        $response->assertViewHasAll(['stories', 'app_cd']); // bladeに渡している変数を確認
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEditForm($story);
        $response->assertStatus(200);                        // アクセス確認
        $response->assertViewIs('admin.Story.edit');         // 指定bladeを確認
        $response->assertViewHasAll(['story', 'app_cd']);    // bladeに渡している変数を確認
        $story = Story::with('image')->find($story->id);
        $response->assertViewHas('story', $story);
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callEditForm($story);
        $response->assertStatus(200);                        // アクセス確認
        $response->assertViewIs('admin.Story.edit');         // 指定bladeを確認
        $response->assertViewHasAll(['story', 'app_cd']);    // bladeに渡している変数を確認
        $story = Story::with('image')->find($story->id);
        $response->assertViewHas('story', $story);
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('message', 'ストーリー「テストタイトル」を更新しました');

        $dirPath = $this->_getDirPath($story->id);

        // データが変更されていることを確認
        $resultStory = Story::find($story->id);
        $this->assertSame('テストタイトル', $resultStory->title);
        $this->assertSame('TO', $resultStory->app_cd);
        $this->assertSame('http://test2.jp', $resultStory->guide_url);
        $resultImage = IMage::find($story->image_id);
        $this->assertSame(ImageUpload::environment() . $dirPath . $uploadFileName, $resultImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($dirPath, $uploadFileName);

        $this->logout();
    }

    public function testEditWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('message', 'ストーリー「テストタイトル」を更新しました');

        $dirPath = $this->_getDirPath($story->id);

        // データが変更されていることを確認
        $resultStory = Story::find($story->id);
        $this->assertSame('テストタイトル', $resultStory->title);
        $this->assertSame('TO', $resultStory->app_cd);
        $this->assertSame('http://test2.jp', $resultStory->guide_url);
        $resultImage = IMage::find($story->image_id);
        $this->assertSame(ImageUpload::environment() . $dirPath . $uploadFileName, $resultImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($dirPath, $uploadFileName);

        $this->logout();
    }

    public function testEditThrowable()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // StoryEditRequestのinput('title')呼び出しで例外発生させるようにする
        $storyEditRequest = \Mockery::mock(StoryEditRequest::class)->makePartial();
        $storyEditRequest->shouldReceive('file')->andReturn('');
        $storyEditRequest->shouldReceive('input')->once()->with('title')->andThrow(new \Exception());
        $storyEditRequest->shouldReceive('input')->andReturn('タイトル');
        $storyEditRequest->shouldReceive('get')->andReturn('');
        $this->app->instance(StoryEditRequest::class, $storyEditRequest);

        $response = $this->_callEditThrowable($story);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('custom_error', 'ストーリー「タイトル」を更新できませんでした');

        // データが変更されていないことを確認
        $resultStory = Story::find($story->id);
        $this->assertSame('タイトル', $resultStory->title);
        $this->assertSame('RS', $resultStory->app_cd);
        $this->assertSame('http://test.jp', $resultStory->guide_url);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                        // アクセス確認
        $response->assertViewIs('admin.Story.add');         // 指定bladeを確認
        $response->assertViewHasAll(['app_cd']);    // bladeに渡している変数を確認
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testAddFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);                        // アクセス確認
        $response->assertViewIs('admin.Story.add');         // 指定bladeを確認
        $response->assertViewHasAll(['app_cd']);    // bladeに渡している変数を確認
        $response->assertViewHas('app_cd', [
            'to' => ['TO' => 'テイクアウト'],
            'rs' => ['RS' => 'レストラン'],
            'tors' => ['TORS' => 'テイクアウト/レストラン'],
        ]);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('message', 'ストーリー「teststory-タイトル」を追加しました');

        // データが変更されていることを確認
        $resultStory = Story::where('title', 'teststory-タイトル')->get();
        $this->assertSame('TO', $resultStory[0]['app_cd']);
        $this->assertSame('http://test.jp', $resultStory[0]['guide_url']);
        $this->assertSame(1, $resultStory[0]['published']);
        $this->assertNotNull($resultStory[0]['image_id']);

        $dirPath = $this->_getDirPath($resultStory[0]['id']);
        $resultImage = IMage::find($resultStory[0]['image_id']);
        $this->assertSame(ImageUpload::environment() . $dirPath . $uploadFileName, $resultImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('message', 'ストーリー「teststory-タイトル」を追加しました');

        // データが変更されていることを確認
        $resultStory = Story::where('title', 'teststory-タイトル')->get();
        $this->assertSame('TO', $resultStory[0]['app_cd']);
        $this->assertSame('http://test.jp', $resultStory[0]['guide_url']);
        $this->assertSame(1, $resultStory[0]['published']);
        $this->assertNotNull($resultStory[0]['image_id']);

        $dirPath = $this->_getDirPath($resultStory[0]['id']);
        $resultImage = IMage::find($resultStory[0]['image_id']);
        $this->assertSame(ImageUpload::environment() . $dirPath . $uploadFileName, $resultImage->url);

        // テスト用にアップロードしたファイルを削除しておく
        $this->_deleteImage($dirPath, $uploadFileName);

        $this->logout();
    }

    public function testAddThrowable()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        // StoryEditRequestのinput('title')呼び出しで例外発生させるようにする
        $storyAddRequest = \Mockery::mock(StoryAddRequest::class)->makePartial();
        $storyAddRequest->shouldReceive('file')->andThrow(new \Exception());
        $storyAddRequest->shouldReceive('input')->andReturn('teststory-タイトル');
        $storyAddRequest->shouldReceive('get')->andReturn('');
        $this->app->instance(StoryAddRequest::class, $storyAddRequest);

        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin/story');    // リダイレクト先
        $response->assertSessionHas('custom_error', 'ストーリー「teststory-タイトル」を追加できませんでした');

        // データが追加されていないことを確認
        $this->assertFalse(Story::where('title', 'teststory-タイトル')->exists());

        $this->logout();
    }

    public function testDeleteWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていることを確認
        $this->assertNull(Story::find($story->id));
        $this->assertNull(Image::find($image->id));

        $this->logout();
    }

    public function testDeleteWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(200)->assertJson(['result' => 'ok']);

        // データが削除されていることを確認
        $this->assertNull(Story::find($story->id));
        $this->assertNull(Image::find($image->id));

        $this->logout();
    }

    public function testDeleteThrowable()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $response = $this->_callDeleteThrowable();
        $response->assertStatus(500);

        $this->logout();
    }

    public function testStoryControllerWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($story);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        $this->logout();
    }

    public function testStoryControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($story);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        $this->logout();
    }

    public function testStoryControllerWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();     // 社外一般権限としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($story);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        $this->logout();
    }

    public function testStoryControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithSettlementAdministrator($settlementCompany->id);    // 精算管理会社としてログイン

        // Controller内の関数にアクセスできないことを確認する

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($story);
        $response->assertStatus(404);

        // target method edit
        $response = $this->_callEdit($story, $uploadFileName, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($uploadFileName);
        $response->assertStatus(404);

        // target method delete
        $response = $this->_callDelete($story, $image, $oldfileName);
        $response->assertStatus(404);
        $this->_deleteImage($this->_getDirPath($story->id), $oldfileName); // テスト用にアップしたファイルを削除する

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

    private function _createImage($url)
    {
        $image = new Image();
        $image->store_id = null;
        $image->menu_id = null;
        $image->image_cd = null;
        $image->url = $url;
        $image->weight = 1;
        $image->save();
        return $image;
    }

    private function _createStory($storyId, $imageId)
    {
        $story = new Story();
        $story->title = 'タイトル';
        $story->app_cd = 'RS';
        $story->guide_url = 'http://test.jp';
        $story->published = 0;
        $story->image_id = $imageId;
        $story->id = $storyId;
        $story->save();
        return $story;
    }

    private function _getDirPath($storyId)
    {
        return $this->dirPath . '/' . $storyId . '/';
    }

    private function _getFileName($image)
    {
        return basename($image) . '.' . $image->extension();
    }

    private function _uploadFile($storyId, &$imageUrl, &$uploadFileName)
    {
        $dirPath = $this->_getDirPath($storyId);

        // Storageへファイルをアップロード
        $image = UploadedFile::fake()->create('test-image.jpg'); // fakeファイルを用意
        ImageUpload::store($image, $dirPath);

        // アップロードしたファイル情報をDBに格納する
        $uploadFileName = $this->_getFileName($image);
        $imageUrl = ImageUpload::environment() . $dirPath . $uploadFileName;
        $imageModel = $this->_createImage($imageUrl);

        return $imageModel;
    }

    private function _deleteImage($dirPath, $fileName)
    {
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認
    }

    private function _callIndex()
    {
        return $this->get('/admin/story');
    }

    private function _callEditForm(&$story)
    {
        $latestStory = Story::latest()->first();
        $storyId = $latestStory ? ($latestStory->id) + 1 : 1;
        $image = $this->_createImage('test.jpg');
        $story = $this->_createStory($storyId, $image->id);
        return $this->get('/admin/story/' . $story->id . '/edit');
    }

    private function _callEdit(&$story, &$uploadFileName, &$oldfileName)
    {
        $latestStory = Story::latest()->first();
        $storyId = $latestStory ? ($latestStory->id) + 1 : 1;
        $image = $this->_uploadFile($storyId, $imageUrl, $oldfileName); // テストファイルをStorageへアップロードする
        $story = $this->_createStory($storyId, $image->id);

        $uploadFile = UploadedFile::fake()->create("test-image2.png");
        $uploadFileName = $this->_getFileName($uploadFile);
        return $this->post('/admin/story/' . $story->id . '/edit', [
            'image' => $uploadFile,
            'title' => 'テストタイトル',
            'url' => 'http://test2.jp',
            'app_cd' => 'TO',
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditThrowable(&$story)
    {
        $latestStory = Story::latest()->first();
        $storyId = $latestStory ? ($latestStory->id) + 1 : 1;
        $image = $this->_createImage('test.jpg');
        $story = $this->_createStory($storyId, $image->id);
        return $this->post('/admin/story/' . $story->id . '/edit', [
            'image' => null,
            'title' => 'テストタイトル',
            'url' => 'http://test2.jp',
            'app_cd' => 'TO',
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm()
    {
        return $this->get('/admin/story/add');
    }

    private function _callAdd(&$uploadFileName)
    {
        $uploadFile = UploadedFile::fake()->create("test-image2.png");
        $uploadFileName = $this->_getFileName($uploadFile);
        return $this->post('/admin/story/add', [
            'image' => $uploadFile,
            'title' => 'teststory-タイトル',
            'url' => 'http://test.jp',
            'app_cd' => 'TO',
            'published' => 1,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDelete(&$story = null, &$image = null, &$oldfileName)
    {
        if (is_null($story)) {
            $latestStory = Story::latest()->first();
            $storyId = $latestStory ? ($latestStory->id) + 1 : 1;
            $image = $this->_uploadFile($storyId, $imageUrl, $oldfileName); // テストファイルをStorageへアップロードする
            $story = $this->_createStory($storyId, $image->id);
        }
        return $this->post("/admin/story/{$story->id}/delete", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callDeleteThrowable()
    {
        return $this->post("/admin/story/1234567890/delete", [
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
