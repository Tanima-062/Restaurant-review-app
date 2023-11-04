<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreCookingGenreAddRequest;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCookingGenreAddRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testAuthorize()
    {
        $request  = new StoreCookingGenreAddRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        $store = $this->_createData();
        $params['id'] = $store->id;

        // 追加呼出関数の指定がある場合は、呼び出す
        if (!empty($addMethod)) {
            $this->{$addMethod}($store);
        }

        // テスト実施
        $request  = new StoreCookingGenreAddRequest($params);
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
        $request->withValidator($validator);                                // withValidator関数呼び出し
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
            'success-notEmpty' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 0,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-numeric' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 0.0,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            // 'error-notString' => [],     // 該当しないgenre情報を引こうとしてエラーとなるため、このテストはできなかった
            'error-notNumeric' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => '１',      // 全角数字
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'is_delegate' => ['is delegateには、数字を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notNumeric2' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 'あ',      // 全角文字
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'is_delegate' => ['is delegateには、数字を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notNumeric3' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 'aaa',     // 半角文字
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'is_delegate' => ['is delegateには、数字を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-duplicate' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test2',
                    'small2_genre' => null,
                    'is_delegate' => 1,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'multiple' => ['料理ジャンルは重複して登録することはできません。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegistMainGenre' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => '',
                    'is_delegate' => 1,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'is_delegate' => ['ジャンル(小)2が設定されている場合はメインジャンルに設定できません。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-delegateFlagOn' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 1,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegistsMainGenre' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test3',
                    'small2_genre' => null,
                    'is_delegate' => 1,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'is_delegate' => ['メインジャンルは2つ以上設定できません。'],
                ],
                // 追加呼び出し関数
                '_createGenre',
            ],
            'success-small2Genre' => [
                // テスト条件
                [
                    'middle_genre' => 'test',
                    'small_genre' => 'test2',
                    'small2_genre' => 'test20',
                    'is_delegate' => 0,
                    'app_cd' => 'TO',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],

        ];
    }

    private function _createData()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->name = 'テスト店舗1234';
        $store->published = 1;
        $store->save();

        $genre = new Genre();                   // 店舗に紐づくジャンルとして登録
        $genre->name = 'テストジャンル';
        $genre->genre_cd = 'test2';
        $genre->app_cd = 'TO';
        $genre->path = '/B-COOKING/test';
        $genre->level = 3;
        $genre->published = 1;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $store->id;
        $genreGroup->genre_id = $genre->id;
        $genreGroup->is_delegate = 0;
        $genreGroup->save();

        $genre2 = new Genre();                  // 店舗に紐づけたいジャンルとして用意
        $genre2->name = 'テストジャンル';
        $genre2->genre_cd = 'test3';
        $genre2->app_cd = 'TO';
        $genre2->path = '/B-COOKING/test';
        $genre2->level = 3;
        $genre2->published = 1;
        $genre2->save();

        $genre3 = new Genre();                  // 店舗に紐づけたいジャンルとして用意
        $genre3->name = 'テストジャンル';
        $genre3->genre_cd = 'test20';
        $genre3->app_cd = 'TO';
        $genre3->path = '/B-COOKING/test/test2';
        $genre3->level = 4;
        $genre3->published = 1;
        $genre3->save();
        return $store;
    }

    private function _createGenre($store)
    {
        $genre = new Genre();
        $genre->name = 'テストジャンル';
        $genre->genre_cd = 'test10';
        $genre->app_cd = 'TO';
        $genre->path = '/B-COOKING/test';
        $genre->level = 3;
        $genre->published = 1;
        $genre->save();

        $genreGroup = new GenreGroup();
        $genreGroup->store_id = $store->id;
        $genreGroup->genre_id = $genre->id;
        $genreGroup->is_delegate = 1;
        $genreGroup->save();
    }
}
