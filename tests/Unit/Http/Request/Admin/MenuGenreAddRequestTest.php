<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuGenreAddRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuGenreAddRequestTest extends TestCase
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
        $request  = new MenuGenreAddRequest();
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
        $request  = new MenuGenreAddRequest();
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
                    'middle_genre' => '',
                    'small_genre' => '',
                    'small2_genre' => '',
                    'app_cd' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'middle_genre' => ['middle genreは必ず指定してください。'],
                    'small_genre' => ['small genreは必ず指定してください。'],
                    'app_cd' =>  ['利用コードは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'middle_genre' => 'm-test_middle',
                    'small_genre' => 'm-test_small',
                    'small2_genre' => 'm-test_small2',
                    'app_cd' => 'RS',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'middle_genre' => 123,
                    'small_genre' => 123,
                    'small2_genre' => 123,
                    'app_cd' => 123,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'middle_genre' => ['middle genreは文字列を指定してください。'],
                    'small_genre' => ['small genreは文字列を指定してください。'],
                    'small2_genre' => ['small2 genreは文字列を指定してください。'],
                    'app_cd' => ['利用コードは文字列を指定してください。'],
                ],
            ],
        ];
    }
}
