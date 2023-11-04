<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuOptionToppingEditRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuOptionToppingEditRequestTest extends TestCase
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
        $request  = new MenuOptionToppingEditRequest();
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
        $request  = new MenuOptionToppingEditRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new MenuOptionToppingEditRequest();
        $result = $request->attributes();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('menuOptionTopping.*.contents', $result);
        $this->assertSame('トッピング内容', $result['menuOptionTopping.*.contents']);
        $this->assertArrayHasKey('menuOptionTopping.*.price', $result);
        $this->assertSame('トッピング金額（税込）', $result['menuOptionTopping.*.price']);
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
                    'menuOptionTopping' => [[
                        'contents' => '',
                        'price' => '',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOptionTopping.0.contents' =>  ['トッピング内容は必ず指定してください。'],
                    'menuOptionTopping.0.price' =>  ['トッピング金額（税込）は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'menuOptionTopping' => [[
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
                    'menuOptionTopping' => [[
                        'contents' => 123,
                        'price' => '1',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOptionTopping.0.contents' => ['トッピング内容は文字列を指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'menuOptionTopping' => [[
                        'contents' => 'トッピング内容テスト',
                        'price' => '12345678',              // max:8桁
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
                    'menuOptionTopping' => [[
                        'contents' => 'トッピング内容テストい',
                        'price' => '123456789',              // 9桁、max:8桁
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuOptionTopping.0.contents' =>  ['トッピング内容は、10文字以下で入力して下さい。'],
                    'menuOptionTopping.0.price' =>  ['トッピング金額（税込）は1桁から8桁の間で指定してください。'],
                ],
            ],
        ];
    }
}
