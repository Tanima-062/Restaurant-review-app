<?php

namespace Tests\Unit\Libs;

use App\Libs\ImageUpload;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testEnvironment()
    {
        if (\App::environment('local') || \App::environment('develop')) {
            $this->assertSame('https://jp.skyticket.jp/gourmet/', ImageUpload::environment());
        } elseif (\App::environment('staging') || \App::environment('production')) {
            $this->assertSame('https://skyticket.jp/gourmet/', ImageUpload::environment());
        } else {
            $this->assertTrue(false);   // ここは通過しないはず
        }
    }

    public function testStore()
    {
        $image = UploadedFile::fake()->create("test.png");
        $fileName = basename($image) . '.' . $image->extension();
        $dirPath = env('GOOGLE_CLOUD_STORAGE_IMAGE_PATH_PREFIX','').'story/test/';

        // 画像をアップロードできるか確認
        ImageUpload::store($image, $dirPath);                           // ファイルをアップロード
        $checkUploadFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkUploadFile);                         // 戻り値は配列である
        $this->assertCount(1, $checkUploadFile);                        // ファイル数が1である
        $this->assertSame($dirPath . $fileName, $checkUploadFile[0]);   // ファイル名がアップロードしたファイルである

        // テスト用にアップロードした画像を削除しておく
        \Storage::disk('gcs')->delete($dirPath . $fileName);            // アップロードしたファイルを削除
        $checkDeleteFile = \Storage::disk('gcs')->allFiles($dirPath);   // 指定フォルダ内のファイルを全て取得
        $this->assertIsArray($checkDeleteFile);                         // 戻り値は配列である
        $this->assertCount(0, $checkDeleteFile);                        // 残っていないことを確認
    }
}
