<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuImageAddRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuImageAddRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testAuthorize()
    {
        $request  = new MenuImageAddRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        // テスト実施
        $request  = new MenuImageAddRequest();
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    /**
     * データプロバイダ
     *
     * @return データプロバイダ
     *
     * @dataProvider dataprovider
     */
    public function dataprovider(): array
    {
        // testRules関数で順番にテストされる
        return [
            'error-empty' => [
                // テスト条件
                [
                    'image_cd' => '',
                    'image_path' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'image_cd' => ['メニュー画像コードは必ず指定してください。'],
                    'image_path' => ['メニュー画像は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.png"),
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.jpg"),
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes2' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.jpeg"),
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notMimes' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.pdf"),
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'image_path' => ['メニュー画像にはpng, jpg, jpegタイプのファイルを指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.png")->size(8192),   // max:8192
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'image_cd' => 'MENU_MAIN',
                    'image_path' => UploadedFile::fake()->create("test.png")->size(8193),   // max:8192
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'image_path' => ['メニュー画像には、8192 kB以下のファイルを指定してください。'],
                ],
            ],
        ];
    }
}
