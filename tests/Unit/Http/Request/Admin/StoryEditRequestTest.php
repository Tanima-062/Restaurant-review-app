<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoryEditRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoryEditRequestTest extends TestCase
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
        $request  = new StoryEditRequest();
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
        $request  = new StoryEditRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoryEditRequest();
        $result = $request->attributes();
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('image', $result);
        $this->assertSame('画像', $result['image']);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('記事タイトル', $result['title']);
        $this->assertArrayHasKey('url', $result);
        $this->assertSame('URL', $result['url']);
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
                    'image' => '',
                    'title' => '',
                    'url' => '',
                    'app_cd' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'title' => ['記事タイトルは必ず指定してください。'],
                    'url' => ['URLは必ず指定してください。'],
                    'app_cd' => ['利用コードは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png"),
                    'title' => 'テストタイトル',
                    'url' => 'https://teststory',
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.jpg"),
                    'title' => 'テストタイトル',
                    'url' => 'https://teststory',
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes2' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.jpeg"),
                    'title' => 'テストタイトル',
                    'url' => 'https://teststory',
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-mimes3' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.gif"),
                    'title' => 'テストタイトル',
                    'url' => 'https://teststory',
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notMimes' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.pdf"),
                    'title' => 'テストタイトル',
                    'url' => 'https://teststory',
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'image' => ['画像にはjpeg, jpg, png, gifタイプのファイルを指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png")->size(8192),    // max:8192
                    'title' => Str::random(100),                                        // max:100
                    'url' => 'https://' . Str::random(247),                              // max:255
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png")->size(8199),    // max:8192
                    'title' => Str::random(101),                                        // max:100
                    'url' => 'https://' . Str::random(248),                              // max:255
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'image' => ['画像には、8192 kB以下のファイルを指定してください。'],
                    'title' => ['記事タイトルは、100文字以下で指定してください。'],
                    'url' => ['URLは、255文字以下で指定してください。'],
                ],
            ],
            'error-notUrl' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png"),
                    'title' => 'テストタイトル',
                    'url' => 'https:/teststory',                           // URL形式が正
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'url' => ['URLに正しい形式を指定してください。'],
                ],
            ],
            'error-notUrl2' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png"),
                    'title' => 'テストタイトル',
                    'url' => 'ｈｔｔｐ：／／ｔｅｓｔ',                           // 全角文字、URL形式が正
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'url' => ['URLに正しい形式を指定してください。'],
                ],
            ],
            'error-notUrl3' => [
                // テスト条件
                [
                    'image' => UploadedFile::fake()->create("test.png"),
                    'title' => 'テストタイトル',
                    'url' => 'ああああああああああ',                           // 全角文字、URL形式が正
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'url' => ['URLに正しい形式を指定してください。'],
                ],
            ],
        ];
    }
}
