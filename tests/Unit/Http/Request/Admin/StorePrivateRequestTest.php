<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StorePrivateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePrivateRequestTest extends TestCase
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
        $request  = new StorePrivateRequest();
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
        $request  = new StorePrivateRequest();
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
                    'published' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => ['publishedは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'published' => '0',     // 0が正
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'published' => '０',       // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                        'publishedは、0から0の間で指定してください。',
                    ],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'published' => 'aaa',       // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                        'publishedは、0から0の間で指定してください。',
                    ],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'published' => 'あ',       // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                        'publishedは、0から0の間で指定してください。',
                    ],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'published' => -1,      // 0が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => ['publishedは、0から0の間で指定してください。'],
                ],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'published' => 1,       // 0が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => ['publishedは、0から0の間で指定してください。'],
                ],
            ],
        ];
    }
}
