<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\GenreSearchRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GenreSearchRequestTest extends TestCase
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
        $request  = new GenreSearchRequest();
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
        $request  = new GenreSearchRequest();
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
                    'name' => '',
                    'genre_cd' => '',
                    'app_cd' => '',
                    'path' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'name' => 'テストジャンル1',
                    'genre_cd' => 'test',
                    'app_cd' => 'TO',
                    'path' => '/b-cooking',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'name' => 123,
                    'genre_cd' => 123,
                    'app_cd' => 123,
                    'path' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['nameは文字列を指定してください。'],
                    'genre_cd' => ['genre cdは文字列を指定してください。'],
                    'app_cd' => ['利用コードは文字列を指定してください。'],
                    'path' => ['pathは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
