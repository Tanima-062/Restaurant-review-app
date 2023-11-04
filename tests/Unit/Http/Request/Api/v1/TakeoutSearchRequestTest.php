<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\TakeoutSearchRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TakeoutSearchRequestTest extends TestCase
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
        $request  = new TakeoutSearchRequest();
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
        $request  = new TakeoutSearchRequest();
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
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'page' => '',
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
                    'menuGenreCd' => 'test-manu-genre',
                    'suggestCd' => 'CURRENT_LOC',
                    'suggestText' => '現在地検索',
                    'pickUpDate' => '2099-10-10',
                    'pickUpTime' => '09:00',
                    'page' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'cookingGenreCd' => 123,        // 半角数値
                    'menuGenreCd' => 123,           // 半角数値
                    'suggestCd' => 123,             // 半角数値
                    'suggestText' => 123,           // 半角数値
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'page' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'cookingGenreCd' => ['cooking genre cdは文字列を指定してください。'],
                    'menuGenreCd' => ['menu genre cdは文字列を指定してください。'],
                    'suggestCd' => ['suggest cdは文字列を指定してください。'],
                    'suggestText' => ['suggest textは文字列を指定してください。'],
                ],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '2099/10/01',  // Y/m/d形式
                    'pickUpTime' => '09-00',       // H-i形式
                    'page' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '20991001',  // Ymd形式
                    'pickUpTime' => '0900',       // Hi形式
                    'page' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '２０９９／１０／０１',   // 全角
                    'pickUpTime' => '０９：００',           // 全角
                    'page' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'page' => '１２３',      // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'page' => ['pageは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'page' => 'page',      // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'page' => ['pageは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'cookingGenreCd' => '',
                    'menuGenreCd' => '',
                    'suggestCd' => '',
                    'suggestText' => '',
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                    'page' => 'あああああ',      // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'page' => ['pageは整数で指定してください。'],
                ],
            ],
        ];
    }
}
