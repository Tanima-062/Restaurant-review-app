<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\MenuRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MenuRequestTest extends TestCase
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
        $request  = new MenuRequest();
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
        $request  = new MenuRequest();
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
                    'pickUpDate' => '',
                    'pickUpTime' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'pickUpDate' => '2099-10-01',
                    'pickUpTime' => '09:00',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'pickUpDate' => '2099/10/01',  // Y/m/d形式
                    'pickUpTime' => '09-00',       // H-i形式
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
                    'pickUpDate' => '20991001',  // Ymd形式
                    'pickUpTime' => '0900',       // Hi形式
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
                    'pickUpDate' => '２０９９／１０／０１',   // 全角
                    'pickUpTime' => '０９：００',           // 全角
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pickUpDate' => ['pick up dateはY-m-d形式で指定してください。'],
                    'pickUpTime' => ['pick up timeはH:i形式で指定してください。'],
                ],
            ],
        ];
    }
}
