<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\UpdateReservationInfoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateReservationInfoRequestTest extends TestCase
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
        $request  = new UpdateReservationInfoRequest();
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
        $request  = new UpdateReservationInfoRequest();
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
                    'reservation_id' => '',
                    'reservation_status' => '',
                    'pick_up_datetime' => '',
                    'persons' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは必ず指定してください。'],
                    'pick_up_datetime' => ['pick up datetimeは必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'reservation_id' => 123,
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 2,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'reservation_id' => '１２３４',             // 全角数字
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => '１２３４',                    // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'persons' => ['personsは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'reservation_id' => 'reservation_id',      // 半角文字
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 'persons',                    // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'persons' => ['personsは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'reservation_id' => 'あああああ',               // 全角文字
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 'いいいいい',                     // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idは整数で指定してください。'],
                    'persons' => ['personsは整数で指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'reservation_id' =>  0,                      // min:1
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 2,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_id' => ['reservation idには、1以上の数字を指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'reservation_id' =>  1,                      // min:1
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 2,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'reservation_id' =>  123,
                    'reservation_status' => 123,
                    'pick_up_datetime' => '2999-01-01 10:00:00',
                    'persons' => 2,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservation_status' => ['reservation statusは文字列を指定してください。'],
                ],
            ],
            'error-notDate' => [
                // テスト条件
                [
                    'reservation_id' =>  123,
                    'reservation_status' => 'ENSURE',
                    'pick_up_datetime' => 'yyyymmdd',
                    'persons' => 2,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'pick_up_datetime' => ['pick up datetimeには有効な日付を指定してください。'],
                ],
            ],
        ];
    }
}
