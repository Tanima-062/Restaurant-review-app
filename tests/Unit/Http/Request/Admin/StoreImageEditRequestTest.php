<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreImageEditRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreImageEditRequestTest extends TestCase
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
        $request  = new StoreImageEditRequest();
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
        $request  = new StoreImageEditRequest();
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
                    'storeImage' => [[
                        'image_cd' => '',
                        'image_path' => '',
                        'weight' => '',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.image_cd' => ['店舗画像コードは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png"),
                        'weight' => 1,
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.jpg"),
                        'weight' => 1,
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes2' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.jpeg"),
                        'weight' => 1,
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notMimes' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.pdf"),
                        'weight' => 1,
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.image_path' => ['店舗画像にはpng, jpg, jpegタイプのファイルを指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png")->size(8192),   // max:8192
                        'weight' => 10,
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png")->size(8193),   // max:8192
                        'weight' => 10.1,                                                       // 0〜10が正
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.image_path' => ['店舗画像には、8192 kB以下のファイルを指定してください。'],
                    'storeImage.0.weight' => ['storeImage.0.weightは、0から10の間で指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png"),
                        'weight' => -1,                                                // 0〜10が正
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.weight' => ['storeImage.0.weightは、0から10の間で指定してください。'],
                ],
            ],
            'error-notNumeric' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png"),
                        'weight' => '１２３４５',   // 全角数字
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.weight' => ['storeImage.0.weightには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric2' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png"),
                        'weight' => 'あああああ',   // 全角文字
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.weight' => ['storeImage.0.weightには、数字を指定してください。'],
                ],
            ],
            'error-notNumeric3' => [
                // テスト条件
                [
                    'storeImage' => [[
                        'image_cd' => 'RESTAURANT_LOGO',
                        'image_path' => UploadedFile::fake()->create("test.png"),
                        'weight' => 'aaa',      // 半角文字
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'storeImage.0.weight' => ['storeImage.0.weightには、数字を指定してください。'],
                ],
            ],
        ];
    }
}
