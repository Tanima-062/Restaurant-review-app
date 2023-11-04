<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\StoreSearchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreSearchRequestTest extends TestCase
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
        $request  = new StoreSearchRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $paramRequest = new Request();
        $paramRequest->merge($params);

        // テスト実施
        $request  = new StoreSearchRequest();
        $rules = $request->rules($paramRequest);
        $errorMessages = $request->messages();
        $validator = Validator::make($params, $rules, $errorMessages);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testMessages()
    {
        $request  = new StoreSearchRequest();
        $result = $request->messages();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('latitude.required_with', $result);
        $this->assertSame('suggestCdをCURRENT_LOCとした場合は、:attributeを必ず指定してください。', $result['latitude.required_with']);
        $this->assertArrayHasKey('longitude.required_with', $result);
        $this->assertSame('suggestCdをCURRENT_LOCとした場合は、:attributeを必ず指定してください。', $result['longitude.required_with']);
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
                    'suggestCd' => '',
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => '',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'cookingGenreCd' => 'test-cooking-genre',
                    'menuGenreCd' => 'test-menu-genre',
                    'suggestCd' => 'CURRENT_LOC',
                    'suggestText' => '現在地検索',
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
                    'page' => 1,
                    'latitude' => '100',
                    'longitude' => '200',
                    'appCd' => 'TO',
                    'lowerPrice' => '1000',
                    'upperPrice' => '1500',
                    'zone' => 10,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'cookingGenreCd' => 123,                // 半角数値
                    'menuGenreCd' => 123,                   // 半角数値
                    'suggestCd' => 123,                     // 半角数値
                    'suggestText' => 123,                   // 半角数値
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'visitPeople' => 2,
                    'page' => 1,
                    'latitude' => '100',
                    'longitude' => '200',
                    'appCd' => 123,                         // 半角数値
                    'lowerPrice' => '1000',
                    'upperPrice' => '1500',
                    'zone' => 10,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cookingGenreCd' => ['cooking genre cdは文字列を指定してください。'],
                    'menuGenreCd' => ['menu genre cdは文字列を指定してください。'],
                    'suggestCd' => ['suggest cdは文字列を指定してください。'],
                    'suggestText' => ['suggest textは文字列を指定してください。'],
                    'appCd' => ['app cdは文字列を指定してください。'],
                ],
            ],
            'error-requiredWith-suggestText' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'STATION',
                    'suggestText' => '',                  // suggestCd項目がSTATIONかAREAの場合のみ入力必須
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'suggestText' => ['suggest cdを指定する場合は、suggest textも指定してください。'],
                ],
            ],
            'error-requiredWith-suggestText2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'AREA',
                    'suggestText' => '',                  // suggestCd項目がSTATIONかAREAの場合のみ入力必須
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'suggestText' => ['suggest cdを指定する場合は、suggest textも指定してください。'],
                ],
            ],
            'success-requiredWith-suggestText' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'STATION',
                    'suggestText' => '現在地検索',                  // suggestCd項目がSTATIONかAREAの場合のみ入力必須
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-requiredWith-suggestText2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'AREA',
                    'suggestText' => 'エリア検索',                  // suggestCd項目がSTATIONかAREAの場合のみ入力必須
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-requiredWith-latitude-longitude' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'CURRENT_LOC',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',                  // suggestCd項目がCURRENT_LOCの場合のみ入力必須
                    'longitude' => '',                 // suggestCd項目がCURRENT_LOCの場合のみ入力必須
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'latitude' => ['suggestCdをCURRENT_LOCとした場合は、latitudeを必ず指定してください。'],
                    'longitude' => ['suggestCdをCURRENT_LOCとした場合は、longitudeを必ず指定してください。'],
                ],
            ],
            'success-requiredWith-latitude-longitude' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => 'CURRENT_LOC',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '100',                  // suggestCd項目がCURRENT_LOCの場合のみ入力必須
                    'longitude' => '200',                 // suggestCd項目がCURRENT_LOCの場合のみ入力必須
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-requiredWith-zone' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '1000',
                    'upperPrice' => '1500',
                    'zone' => '',               // lowerPrice,upperPriceがある場合入力必須
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'zone' => ['lower price / upper priceを指定する場合は、zoneも指定してください。'],
                ],
            ],
            'success-requiredWith-zone' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '1000',
                    'upperPrice' => '1500',
                    'zone' => 100,               // lowerPrice,upperPriceがある場合入力必須
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '2099/10/01',  // Y/m/d形式
                    'visitTime' => '09-00',       // H-i形式
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '20991001',  // Ymd形式
                    'visitTime' => '0900',       // Hi形式
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '２０９９／１０／０１',   // 全角
                    'visitTime' => '０９：００',           // 全角
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '',
                    'upperPrice' => '',
                    'zone' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '１２３',      // 全角数字
                    'page' => '１２３',             // 全角数字
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => '１２３',       // 全角数字
                    'upperPrice' => '１２３',       // 全角数字
                    'zone' => '１２３',             // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                    'page' => ['pageは整数で指定してください。'],
                    'lowerPrice' => [
                        'lower priceは整数で指定してください。',
                        'lower priceには、3より小さな値を指定してください。',  // upperPriceの桁数が3桁なので3以下となる
                    ],
                    'upperPrice' => [
                        'upper priceは整数で指定してください。',
                        'upper priceには、3より大きな値を指定してください。',  // lowerPriceの桁数が3桁なので3以上となる
                    ],
                    'zone' => ['zoneは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => 'visitPeople',     // 半角文字
                    'page' => 'page',                   // 半角文字
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => 'lowerPrice',       // 半角文字
                    'upperPrice' => 'upperPrice',       // 半角文字
                    'zone' => 'zone',                   // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                    'page' => ['pageは整数で指定してください。'],
                    'lowerPrice' => [
                        'lower priceは整数で指定してください。',
                        'lower priceには、10より小さな値を指定してください。',  // upperPriceの桁数が10桁なので10以下となる
                    ],
                    'upperPrice' => [
                        'upper priceは整数で指定してください。',
                        'upper priceには、10より大きな値を指定してください。',  // lowerPriceの桁数が10桁なので10以上となる
                    ],
                    'zone' => ['zoneは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => 'あああああ',      // 全角文字
                    'page' => 'いいいいい',             // 全角文字
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => 'ううううう',       // 全角文字
                    'upperPrice' => 'えええええ',       // 全角文字
                    'zone' => 'おおおおお',             // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitPeople' => ['visit peopleは整数で指定してください。'],
                    'page' => ['pageは整数で指定してください。'],
                    'lowerPrice' => [
                        'lower priceは整数で指定してください。',
                        'lower priceには、5より小さな値を指定してください。',  // upperPriceの桁数が5桁なので5以下となる
                    ],
                    'upperPrice' => [
                        'upper priceは整数で指定してください。',
                        'upper priceには、5より大きな値を指定してください。',  // lowerPriceの桁数が5桁なので5以上となる
                    ],
                    'zone' => ['zoneは整数で指定してください。'],
                ],
            ],
            'error-notLtGt' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => 1001,   // upperPrice未満でないといけない
                    'upperPrice' => 1000,   // lowerPriceより大きくないといけない
                    'zone' => 0,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'lowerPrice' => [
                        'lower priceには、1000より小さな値を指定してください。',
                    ],
                    'upperPrice' => [
                        'upper priceには、1001より大きな値を指定してください。',
                    ],
                ],
            ],
            'error-notLtGt2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => 1000,   // upperPrice未満でないといけない
                    'upperPrice' => 1000,   // lowerPriceより大きくないといけない
                    'zone' => 0,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'lowerPrice' => [
                        'lower priceには、1000より小さな値を指定してください。',
                    ],
                    'upperPrice' => [
                        'upper priceには、1000より大きな値を指定してください。',
                    ],
                ],
            ],
            'success-ltGt' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'visitDate' => '',
                    'visitTime' => '',
                    'visitPeople' => '',
                    'page' => '',
                    'latitude' => '',
                    'longitude' => '',
                    'appCd' => 'TO',
                    'lowerPrice' => 999,    // upperPrice未満でないといけない
                    'upperPrice' => 1000,   // lowerPriceより大きくないといけない
                    'zone' => 0,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
