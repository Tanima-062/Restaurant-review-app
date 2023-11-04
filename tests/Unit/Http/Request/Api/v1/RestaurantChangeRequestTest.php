<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\RestaurantChangeRequest;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RestaurantChangeRequestTest extends TestCase
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
        $request  = new RestaurantChangeRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        // データ追加が必要な場合は、対象関数を呼び出す
        if (!empty($addMethod)) {
            $result = $this->{$addMethod}();
            $params['reservationId'] = $result->id;
        }

        // テスト実施
        $request  = new RestaurantChangeRequest($params);
        $rules = $request->rules();
        $validator = Validator::make($params, $rules);
        // withValidator関数のテストの時だけ呼び出す（そうしておかないと必須入力やInteger型などのチェックがうまく確認できない）
        if ($addMethod == '_createData2' || $addMethod == '_createData3') {
            $request->withValidator($validator);                                // withValidator関数呼び出し
        }
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
                    'visitDate' => '',
                    'visitTime' => '',
                    'persons' => '',
                    'reservationId' => '',
                    'request' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['reservation idは必ず指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notFormat' => [
                // テスト条件
                [
                    'visitDate' => '2099/10/01',  // Y/m/d形式
                    'visitTime' => '09-00',       // H-i形式
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'visitDate' => '20991001',  // Ymd形式
                    'visitTime' => '0900',       // Hi形式
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'visitDate' => '２０９９／１０／０１',   // 全角
                    'visitTime' => '０９：００',           // 全角
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                    'visitTime' => ['visit timeはH:i形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => '２',              // 全角数字
                    'reservationId' => '１２３',     // 全角数字
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 'persons',                 // 半角文字
                    'reservationId' => 'reservationId',     // 半角文字
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 'あああああ',          // 全角文字
                    'reservationId' => 'いいいいい',    // 全角文字
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'persons' => ['personsは整数で指定してください。'],
                    'reservationId' => ['reservation idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notString' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => 123,               // 半角数値
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'request' => ['requestは文字列を指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-withValidator' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['変更期限が過ぎているため変更できません。'],
                ],
                // 追加呼び出し関数
                '_createData2',
            ],
            'success-withValidator' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'visitTime' => '09:00',
                    'persons' => 2,
                    'reservationId' => null,        // テスト直前に追加関数で発行された分をセットする
                    'request' => '卵アレルギーです',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData3',
            ],
        ];
    }

    private function _createData($pickUpDatetime = '2099/10/01 09:00:00')
    {
        $reservation = new Reservation();
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->save();
        return $reservation;
    }

    private function _createData2()
    {
        // 現時刻が予約変更可能日時を過ぎている
        $now = Carbon::now()->hour(0)->minute(0);
        return $this->_createData($now->format('Y-m-d H:i'));
    }

    private function _createData3()
    {
        // 現時刻が予約変更可能日時を過ぎていない
        $now = Carbon::now()->addDay()->hour(0)->minute(0);
        return $this->_createData($now->format('Y-m-d H:i'));
    }
}
