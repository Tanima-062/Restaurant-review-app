<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuOptionOkonomiEditRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuOptionOkonomiEditRequestTest extends TestCase
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
        $request  = new MenuOptionOkonomiEditRequest();
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
        $request  = new MenuOptionOkonomiEditRequest();
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
                    'menuOkonomi' => [[
                        'required' => '',
                        'keyword' => '',
                    ]],
                    'menuOption' => [[
                        'contents' => '',
                        'price' => '',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOkonomi.0.required' => ['必須/任意は必ず指定してください。'],
                    'menuOkonomi.0.keyword' => ['項目は必ず指定してください。'],
                    'menuOption.0.contents' =>  ['内容は必ず指定してください。'],
                    'menuOption.0.price' =>  ['金額（税込）は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'menuOkonomi' => [[
                        'required' => '1',
                        'keyword' => '1',
                    ]],
                    'menuOption' => [[
                        'contents' => '内容',
                        'price' => '1',
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'menuOkonomi' => [[
                        'required' => '1',
                        'keyword' => 123,
                    ]],
                    'menuOption' => [[
                        'contents' => 123,
                        'price' => '1',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOkonomi.0.keyword' => ['項目は文字列を指定してください。'],
                    'menuOption.0.contents' => ['内容は文字列を指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'menuOkonomi' => [[
                        'required' => '1',
                        'keyword' => '項目テスト項目テスト',    // max:10
                    ]],
                    'menuOption' => [[
                        'contents' => '内容テスト内容テスト',   // max:10
                        'price' => '12345678',              // 1~8桁が正
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
                    'menuOkonomi' => [[
                        'required' => '1',
                        'keyword' => '項目テスト項目テストあ',  // 11桁、max:10
                    ]],
                    'menuOption' => [[
                        'contents' => '内容テスト内容テストい', // 11桁、max:10
                        'price' => '123456789',             // 9桁、1~8桁が正
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOkonomi.0.keyword' => ['項目は、10文字以下で入力して下さい。'],
                    'menuOption.0.contents' =>  ['内容は、10文字以下で入力して下さい。'],
                    'menuOption.0.price' =>  ['金額（税込）は1桁から8桁の間で指定してください。'],
                ],
            ],
        ];
    }
}
