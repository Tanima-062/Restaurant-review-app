<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StationSearchRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StationSearchRequestTest extends TestCase
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
        $request  = new StationSearchRequest();
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
        $request  = new StationSearchRequest($params);
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
                    'name' => '',
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
                    'name' => 'テスト駅',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'id' => '１２３４５',   // 全角数字
                    'name' => 'テスト駅',
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
                    'id' => 'aaaaa',   // 半角文字
                    'name' => 'テスト駅',
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
                    'id' => 'あああああ',   // 全角文字
                    'name' => 'テスト駅',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは整数で指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'id' => 0,                          // min:1
                    'name' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idには、1以上の数字を指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'id' => 1,                          // min:1
                    'name' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => Str::random(128),              // max:128
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => Str::random(129),              // max:128
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['nameは、128文字以下で指定してください。'],
                ],
            ],
        ];
    }
}
