<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuPublishRequest;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Price;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuPublishRequestTest extends TestCase
{
    private $testMenuId;

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
        $request  = new MenuPublishRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        $this->_createData();
        $params['id'] = $this->testMenuId;

        // データ追加が必要な場合は、対象関数を呼び出す
        if (!empty($addMethod)) {
            $this->{$addMethod}();
        }

        // テスト実施
        $request  = new MenuPublishRequest($params);
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
            'error-empty' => [
                // テスト条件
                [
                    'published' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは必ず指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'published' => '１',    // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'published' => 'aaa',   // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                        'publishedは、1から1の間で指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'published' => 'あ',   // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは整数で指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'published' => 0,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは、1から1の間で指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'published' => 2,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'publishedは、1から1の間で指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
            'error-menuGenre' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'メニュー「テストメニュー」を公開するには下記の設定をしてください。',
                        'メニューのジャンルを設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkMenuGenre',
            ],
            'error-menuImage' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'メニュー「テストメニュー」を公開するには下記の設定をしてください。',
                        'メニューの画像を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkMenuImage',
            ],
            'error-menuPrice' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        'メニュー「テストメニュー」を公開するには下記の設定をしてください。',
                        'メニューの料金を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkMenuPrice',
            ],
            'success-menuPublished' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_setMenuInfometion',
            ],
        ];
    }

    private function _createData()
    {
        $menu = new Menu();
        $menu->name = 'テストメニュー';
        $menu->app_cd = 'RS';
        $menu->published = 1;
        $menu->save();
        $this->testMenuId = $menu->id;
    }


    // テスト結果確認データ作成関数（店舗公開に必要な情報のうち、一部（$except）を作成しない）
    private function _setMenuInfometion($except = null, $appCdTo = true)
    {
        if ($except != '_checkMenuGenre') {
            $genreLevel2 = new Genre();
            $genreLevel2->level = 2;
            $genreLevel2->genre_cd = 'test2';
            $genreLevel2->published = 1;
            $genreLevel2->path = '/test';
            $genreLevel2->save();

            $genreGroup = new GenreGroup();
            $genreGroup->menu_id = $this->testMenuId;
            $genreGroup->genre_id = $genreLevel2->id;
            $genreGroup->is_delegate = 0;
            $genreGroup->save();
        }

        if ($except != '_checkMenuImage') {
            $menuImage = new Image();
            $menuImage->menu_id = $this->testMenuId;
            $menuImage->image_cd = 'MENU_MAIN';
            $menuImage->weight = 100;
            $menuImage->save();
        }

        if ($except != '_checkMenuPrice') {
            $price = new Price();
            $price->menu_id = $this->testMenuId;
            $price->price = 1000;
            $price->save();
        }
    }

    private function _checkMenuGenre()
    {
        $this->_setMenuInfometion(__FUNCTION__);
    }

    private function _checkMenuImage()
    {
        $this->_setMenuInfometion(__FUNCTION__);
    }

    private function _checkMenuPrice()
    {
        $this->_setMenuInfometion(__FUNCTION__);
    }
}
