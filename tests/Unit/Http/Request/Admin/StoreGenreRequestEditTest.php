<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreGenreRequestEdit;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreGenreRequestEditTest extends TestCase
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
        $request  = new StoreGenreRequestEdit();
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
        $request  = new StoreGenreRequestEdit();
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
                    'middle_genre' => [''],
                    'small_genre' => [''],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'middle_genre.0' => ['middle_genre.0は必ず指定してください。'],
                    'small_genre.0' => ['small_genre.0は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'middle_genre' => ['テスト中ジャンル'],
                    'small_genre' => ['テスト小ジャンル'],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'middle_genre' => [1],
                    'small_genre' => [2],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'middle_genre.0' => ['middle_genre.0は文字列を指定してください。'],
                    'small_genre.0' => ['small_genre.0は文字列を指定してください。'],
                ],
            ],
        ];
    }
}
