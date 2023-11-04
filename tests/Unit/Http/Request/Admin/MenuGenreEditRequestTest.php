<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuGenreEditRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuGenreEditRequestTest extends TestCase
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
        $request  = new MenuGenreEditRequest();
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
        $request  = new MenuGenreEditRequest();
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
                    'middle_genre' => [''],
                    'small_genre' => [''],
                    'small2_genre' => [''],
                    'app_cd' => '',
                    'genre_group_id' => [''],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' =>  ['利用コードは必ず指定してください。'],
                    'middle_genre.0' => ['middle_genre.0は必ず指定してください。'],
                    'small_genre.0' => ['small_genre.0は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'middle_genre' => ['m-test_middle'],
                    'small_genre' => ['m-test_small'],
                    'small2_genre' => ['m-test_small2'],
                    'app_cd' => 'RS',
                    'genre_group_id' => [1],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'middle_genre' => [123],
                    'small_genre' => [123],
                    'small2_genre' => [123],
                    'app_cd' => 123,
                    'genre_group_id' => [1],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'app_cd' => ['利用コードは文字列を指定してください。'],
                    'middle_genre.0' => ['middle_genre.0は文字列を指定してください。'],
                    'small_genre.0' => ['small_genre.0は文字列を指定してください。'],
                    'small2_genre.0' => ['small2_genre.0は文字列を指定してください。'],
                ],
            ],
        ];
    }
}
