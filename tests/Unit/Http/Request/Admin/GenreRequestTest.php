<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\GenreRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GenreRequestTest extends TestCase
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
        $request  = new GenreRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $request  = new GenreRequest([], $params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new GenreRequest();
        $result = $request->attributes();
        $this->assertCount(6, $result);
        $this->assertArrayHasKey('big_genre', $result);
        $this->assertSame('ジャンル(大)', $result['big_genre']);
        $this->assertArrayHasKey('middle_genre', $result);
        $this->assertSame('ジャンル(中)', $result['middle_genre']);
        $this->assertArrayHasKey('small_genre', $result);
        $this->assertSame('ジャンル(小)', $result['small_genre']);
        $this->assertArrayHasKey('app_cd', $result);
        $this->assertSame('利用サービス', $result['app_cd']);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('カテゴリ名', $result['name']);
        $this->assertArrayHasKey('genre_cd', $result);
        $this->assertSame('カテゴリコード', $result['genre_cd']);
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
                    'big_genre' => '',
                    'middle_genre' => '',
                    'small_genre' => '',
                    'app_cd' => '',
                    'name' => '',
                    'genre_cd' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'big_genre' => ['ジャンル(大)は必ず指定してください。'],
                    'app_cd' => ['利用サービスは必ず指定してください。'],
                    'name' => ['カテゴリ名は必ず指定してください。'],
                    'genre_cd' => ['カテゴリコードは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',         // b-cooking or b-detailed
                    'middle_genre' => 'test-middel',
                    'small_genre' => 'test-small',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'i-test',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-inRule' => [
                // テスト条件
                [
                    'big_genre' => 'b-detailed',        // b-cooking or b-detailed
                    'middle_genre' => 'test-middel',
                    'small_genre' => 'test-small',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'i-test',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInRule' => [
                // テスト条件
                [
                    'big_genre' => 'test-big',        // b-cooking or b-detailed
                    'middle_genre' => 'test-middel',
                    'small_genre' => 'test-small',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'i-test',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'big_genre' => ['選択されたジャンル(大)は正しくありません。'],
                ],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',         // b-cooking or b-detailed
                    'middle_genre' => 123,
                    'small_genre' => 123,
                    'app_cd' => 123,
                    'name' => 123,
                    'genre_cd' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'middle_genre' => ['ジャンル(中)は文字列を指定してください。'],
                    'small_genre' => ['ジャンル(小)は文字列を指定してください。'],
                    'app_cd' => ['利用サービスは文字列を指定してください。'],
                    'name' => ['カテゴリ名は文字列を指定してください。'],
                    'genre_cd' => [
                        'カテゴリコードは文字列を指定してください。',
                        '先頭にプレフィックスとして「i-」をつけてください',
                    ],
                ],
            ],
            'error-notAlphaDash' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => 'test-middel',
                    'small_genre' => 'test-small',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'i-abcd123-_efg*',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'genre_cd' => ['カテゴリコードはアルファベットとダッシュ(-)及び下線(_)がご利用できます。'],
                ],
            ],
            'success-genrePrefix1' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => '',
                    'small_genre' => '',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'm-abcd123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-genrePrefix2' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => 'm-test',
                    'small_genre' => '',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 's-abcd123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-genrePrefix3' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => 'm-test',
                    'small_genre' => 's-test',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'i-abcd123',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notGenrePrefix1' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => '',
                    'small_genre' => '',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'abcd123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'genre_cd' => ['先頭にプレフィックスとして「m-」をつけてください'],
                ],
            ],
            'error-notGenrePrefix2' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => 'm-test',
                    'small_genre' => '',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'abcd123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'genre_cd' => ['先頭にプレフィックスとして「s-」をつけてください'],
                ],
            ],
            'error-notGenrePrefix3' => [
                // テスト条件
                [
                    'big_genre' => 'b-cooking',        // b-cooking or b-detailed
                    'middle_genre' => 'm-test',
                    'small_genre' => 's-test',
                    'app_cd' => 'TO',
                    'name' => 'テストジャンル',
                    'genre_cd' => 'abcd123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'genre_cd' => ['先頭にプレフィックスとして「i-」をつけてください'],
                ],
            ],
        ];
    }
}
