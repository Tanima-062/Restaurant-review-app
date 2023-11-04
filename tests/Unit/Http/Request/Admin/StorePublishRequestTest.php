<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StorePublishRequest;
use App\Models\CancelFee;
use App\Models\CommissionRate;
use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Image;
use App\Models\OpeningHour;
use App\Models\SettlementCompany;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePublishRequestTest extends TestCase
{
    private $testStoreId;

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
        $request  = new StorePublishRequest();
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
        $params['id'] = $this->testStoreId;

        // データ追加が必要な場合は、対象関数を呼び出す
        if (!empty($addMethod)) {
            $this->{$addMethod}();
        }

        // テスト実施
        $request  = new StorePublishRequest($params);
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
                '_setStoreInfometion',
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'published' => '１',   // 全角数字
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
                '_setStoreInfometion',
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
                '_setStoreInfometion',
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'published' => 'ああ',   // 全角文字
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
                '_setStoreInfometion',
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
                '_setStoreInfometion',
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
                '_setStoreInfometion',
            ],
            'error-withValidator-takaout-priceLevelNotExist' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗のテイクアウト価格帯を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStorePriceLevel',
            ],
            'error-withValidator-takaout-pickUpTimeIntervalNotExist' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗のテイクアウト受取時間間隔を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStorePickUpTimeInterval',
            ],
            'error-withValidator-takaout-lowerOrdersTimeNotExist' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗の最低注文時間(分)を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreLowerOrdersTime',
            ],
            'error-withValidator-takaout-areaNotExist' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗の検索エリアの設定をしてください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreArea',
            ],
            'error-withValidator-takaout-openingHourNotExist' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '営業時間を1つ以上登録してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreOpeningHour',
            ],
            'error-withValidator-takaout-image' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '利用サービスにてテイクアウトを設定している場合は、フードロゴを設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreToImage',
            ],
            'error-withValidator-takaout-imageCount' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '優先度1.00以上の画像は最低３つ設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreImageCount',
            ],
            'error-withValidator-takaout-genre' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗のメインジャンルを設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreGenre',
            ],
            'error-withValidator-takaout-settlementCompany' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗の精算会社を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkSettlementCompany',
            ],
            'error-withValidator-takaout-commissionRate' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '店舗の精算会社の販売手数料を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkCommissionRate',
            ],
            'error-withValidator-takaout-commissionRate2' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        'テイクアウトの有効な販売手数料の設定がありません。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkCommissionRateTo',
            ],
            'success-withValidator-takaout' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_checkStoreToPublished',
            ],
            'error-withValidator-restaurant-image' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '利用サービスにてレストランを設定している場合は、レストランロゴを設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreRsImage',
            ],
            'error-withValidator-restaurant-cancelFee' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        '利用サービスにレストランを設定している場合は、レストランサービスのキャンセル料を設定してください。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkStoreCancelFee',
            ],
            'error-withValidator-restaurant-commissionRate' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published' => [
                        '店舗「テスト店舗」を公開するには下記の設定をしてください。',
                        'レストランの有効な販売手数料の設定がありません。',
                    ],
                ],
                // 追加呼び出し関数
                '_checkCommissionRateRs',
            ],
            'success-withValidator-restaurant' => [
                // テスト条件
                [
                    'published' => 1,   // 1が正
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_checkStoreRsPublished',
            ],
        ];
    }

    private function _createData()
    {
        $store = new Store();
        $store->name = 'テスト店舗';
        $store->app_cd = 'RS';
        $store->published = 1;
        $store->save();
        $this->testStoreId = $store->id;
    }

    // テスト結果確認データ作成関数（店舗公開に必要な情報のうち、一部（$except）を作成しない）
    private function _setStoreInfometion($except = null, $appCdTo = true)
    {
        $storeId = $this->testStoreId;

        $updateParams = [];

        if ($appCdTo) {
            $updateParams['app_cd'] = 'TO'; // テイクアウト店舗に変更
        }

        if ($except != '_checkStorePriceLevel') $updateParams['price_level'] = 1;
        if ($except != '_checkStorePickUpTimeInterval') $updateParams['pick_up_time_interval'] = '30';
        if ($except != '_checkStoreLowerOrdersTime') $updateParams['lower_orders_time'] = '180';
        if ($except != '_checkStoreArea') $updateParams['area_id'] = 1;

        if ($except != '_checkStoreOpeningHour') {
            $openigHour = new OpeningHour();
            $openigHour->store_id = $storeId;
            $openigHour->save();
        }

        if ($except != '_checkStoreToImage') {
            $menuImage = new Image();
            $menuImage->store_id = $storeId;
            $menuImage->image_cd = 'FOOD_LOGO';
            $menuImage->weight = 100;
            $menuImage->save();
        }

        if ($except != '_checkStoreRsImage') {
            $menuImage2 = new Image();
            $menuImage2->store_id = $storeId;
            $menuImage2->image_cd = 'RESTAURANT_LOGO';
            $menuImage2->weight = 100;
            $menuImage2->save();
        }

        if ($except != '_checkStoreImageCount') {
            $menuImage = new Image();
            $menuImage->store_id = $storeId;
            $menuImage->image_cd = 'OTHER';
            $menuImage->weight = 1;
            $menuImage->save();

            $menuImage = new Image();
            $menuImage->store_id = $storeId;
            $menuImage->image_cd = 'OTHER';
            $menuImage->weight = 1;
            $menuImage->save();
        }

        $menuImage = new Image();
        $menuImage->store_id = $storeId;
        $menuImage->image_cd = 'OTHER';
        $menuImage->weight = 0;             // 優先度0の分を登録しておき、_checkStoreImageCount実行時に優先度の高い画像としてカウントされないことを確認しておく
        $menuImage->save();


        if ($except != '_checkStoreCancelFee') {
            $cancelFee = new CancelFee();
            $cancelFee->app_cd = 'RS';
            $cancelFee->store_id = $storeId;
            $cancelFee->published = 1;
            $cancelFee->save();
        }

        if ($except != '_checkStoreGenre') {
            $genre = new Genre();
            $genre->level = 2;
            $genre->genre_cd = 'test2';
            $genre->published = 1;
            $genre->path = '/test';
            $genre->save();

            $genreGroup = new GenreGroup();
            $genreGroup->store_id = $storeId;
            $genreGroup->genre_id = $genre->id;
            $genreGroup->is_delegate = 1;
            $genreGroup->save();
        }

        if ($except != '_checkSettlementCompany') {

            $settlementCompany = new SettlementCompany();
            $settlementCompany->save();
            $updateParams['settlement_company_id'] = $settlementCompany->id;

            if ($except != '_checkCommissionRate') {

                if ($except != '_checkCommissionRateTo') {
                    $commissionRateTo = new CommissionRate();
                    $commissionRateTo->app_cd = 'TO';
                    $commissionRateTo->settlement_company_id = $settlementCompany->id;
                    $commissionRateTo->apply_term_from = new Carbon('2022-10-01');
                    $commissionRateTo->apply_term_to = new Carbon('2999-12-31');
                    $commissionRateTo->published = 1;
                    $commissionRateTo->save();
                }

                if ($except != '_checkCommissionRateRs') {
                    $commissionRateRs = new CommissionRate();
                    $commissionRateRs->app_cd = 'RS';
                    $commissionRateRs->settlement_company_id = $settlementCompany->id;
                    $commissionRateRs->apply_term_from = new Carbon('2022-10-01');
                    $commissionRateRs->apply_term_to = new Carbon('2999-12-31');
                    $commissionRateRs->published = 1;
                    $commissionRateRs->save();
                }
            }
        }

        Store::find($storeId)->update($updateParams);
    }

    private function _checkStorePriceLevel()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStorePickUpTimeInterval()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreLowerOrdersTime()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreArea()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreOpeningHour()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreToImage()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreRsImage()
    {
        $this->_setStoreInfometion(__FUNCTION__, false);
    }

    private function _checkStoreImageCount()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreCancelFee()
    {
        $this->_setStoreInfometion(__FUNCTION__, false);
    }

    private function _checkStoreGenre()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkSettlementCompany()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkCommissionRate()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkCommissionRateTo()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkCommissionRateRs()
    {
        $this->_setStoreInfometion(__FUNCTION__, false);
    }

    private function _checkStoreToPublished()
    {
        $this->_setStoreInfometion(__FUNCTION__);
    }

    private function _checkStoreRsPublished()
    {
        $this->_setStoreInfometion(__FUNCTION__, false);
    }

}
