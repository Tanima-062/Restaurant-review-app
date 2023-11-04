<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreCookingGenreEditRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCookingGenreEditRequestTest extends TestCase
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
        $request  = new StoreCookingGenreEditRequest();
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
        $request  = new StoreCookingGenreEditRequest($params);
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
                    'cooking_middle_genre' => [],
                    'cooking_small_genre' => [],
                    'cooking_small2_genre' => [],
                    'cooking_genre_group_id' => [],
                    'cooking_delegate' => [],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-empty' => [
                // テスト条件
                [
                    'cooking_middle_genre' => [''],
                    'cooking_small_genre' => [''],
                    'cooking_small2_genre' => [''],
                    'cooking_genre_group_id' => [''],
                    'cooking_delegate' => [''],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cooking_middle_genre.0' => ['cooking_middle_genre.0は必ず指定してください。'],
                    'cooking_small_genre.0' => ['cooking_small_genre.0は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'cooking_middle_genre' => ['test'],
                    'cooking_small_genre' => ['test2'],
                    'cooking_small2_genre' => ['test3'],
                    'cooking_genre_group_id' => [1],
                    'cooking_delegate' => ['0'],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'cooking_middle_genre' => [1],
                    'cooking_small_genre' => [2],
                    'cooking_small2_genre' => [3],
                    'cooking_genre_group_id' => [4],
                    'cooking_delegate' => [5],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cooking_middle_genre.0' => ['cooking_middle_genre.0は文字列を指定してください。'],
                    'cooking_small_genre.0' => ['cooking_small_genre.0は文字列を指定してください。'],
                    'cooking_small2_genre.0' => ['cooking_small2_genre.0は文字列を指定してください。'],
                    'cooking_delegate.0' => ['cooking_delegate.0は文字列を指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'cooking_middle_genre' => ['test'],
                    'cooking_small_genre' => ['test2'],
                    'cooking_small2_genre' => ['test3'],
                    'cooking_genre_group_id' => ['１'],     // 全角数字
                    'cooking_delegate' => ['0'],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cooking_genre_group_id.0' => ['cooking_genre_group_id.0は整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'cooking_middle_genre' => ['test'],
                    'cooking_small_genre' => ['test2'],
                    'cooking_small2_genre' => ['test3'],
                    'cooking_genre_group_id' => ['aaaa'],     // 半角文字
                    'cooking_delegate' => ['0'],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cooking_genre_group_id.0' => ['cooking_genre_group_id.0は整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'cooking_middle_genre' => ['test'],
                    'cooking_small_genre' => ['test2'],
                    'cooking_small2_genre' => ['test3'],
                    'cooking_genre_group_id' => ['ああああ'],     // 全角文字
                    'cooking_delegate' => ['0'],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cooking_genre_group_id.0' => ['cooking_genre_group_id.0は整数で指定してください。'],
                ],
            ],
        ];
    }
}
