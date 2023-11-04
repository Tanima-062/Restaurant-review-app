<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\AuthReviewRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuthReviewRequestTest extends TestCase
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
        $request  = new AuthReviewRequest();
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
        $request  = new AuthReviewRequest();
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
                    'reservationNo' => '',
                    'menuId' => '',
                    'evaluationCd' => '',
                    'body' => '',
                    'isRealName' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationNo' => ['reservation noは必ず指定してください。'],
                    'menuId' => ['menu idは必ず指定してください。'],
                    'evaluationCd' => ['evaluation cdは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => true,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'reservationNo' => 123,
                    'menuId' => 123,
                    'evaluationCd' => 123,
                    'body' => 123,
                    'isRealName' => true,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationNo' => ['reservation noは文字列を指定してください。'],
                    'evaluationCd' => ['evaluation cdは文字列を指定してください。'],
                    'body' => ['bodyは文字列を指定してください。'],
                ],
            ],
            'error-notInt' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => '１２３４',         // 全角数字
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => true,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => ['menu idは整数で指定してください。'],
                ],
            ],
            'error-notInt2' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 'menu_id',          // 半角文字
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => true,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => ['menu idは整数で指定してください。'],
                ],
            ],
            'error-notInt3' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 'あああああ',           // 全角文字
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => true,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => ['menu idは整数で指定してください。'],
                ],
            ],
            'error-notBoolean' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => '123',          // 文字列
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRealName' => ['is real nameは、trueかfalseを指定してください。'],
                ],
            ],
            'error-notBoolean2' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => 2,              // 0・1以外の数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRealName' => ['is real nameは、trueかfalseを指定してください。'],
                ],
            ],
            'error-notBoolean3' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => 'あああああ',   // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'isRealName' => ['is real nameは、trueかfalseを指定してください。'],
                ],
            ],
            'success-boolean' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => false,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-boolean2' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-boolean3' => [
                // テスト条件
                [
                    'reservationNo' => '123456',
                    'menuId' => 123,
                    'evaluationCd' => 'COOKING',
                    'body' => 'テストレビュー内容',
                    'isRealName' => 0,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
