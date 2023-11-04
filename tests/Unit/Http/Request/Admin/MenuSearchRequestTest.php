<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuSearchRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuSearchRequestTest extends TestCase
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
        $request  = new MenuSearchRequest();
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
        $request  = new MenuSearchRequest();
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
            'success-empty' => [
                // テスト条件
                [
                    'id' => '',
                    'app_cd' => '',
                    'name' => '',
                    'store_name' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => 123,
                    'app_cd' => 'TO',
                    'name' => 'テスト',
                    'store_name' => 'テスト店舗',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notEmpty' => [
                // テスト条件
                [
                    'id' => 123,
                    'app_cd' => 123,
                    'name' => 123,
                    'store_name' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用コードは文字列を指定してください。'],
                    'name' => ['nameは文字列を指定してください。'],
                    'store_name' => ['store nameは文字列を指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'id' => '１２３４',         // 全角数字
                    'app_cd' => 'TO',
                    'name' => 'テスト',
                    'store_name' => 'テスト店舗',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'id' => 'aaaaa',        // 半角文字
                    'app_cd' => 'TO',
                    'name' => 'テスト',
                    'store_name' => 'テスト店舗',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'id' => 'あああ',        // 全角文字
                    'app_cd' => 'TO',
                    'name' => 'テスト',
                    'store_name' => 'テスト店舗',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
        ];
    }
}
