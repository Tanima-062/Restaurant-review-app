<?php

namespace Tests\Unit\Http\Request\Api\v1;

use App\Http\Requests\Api\v1\menuVacancyRequest;
use App\Models\Menu;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class menuVacancyRequestTest extends TestCase
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
        $request  = new menuVacancyRequest();
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
            [$menu, $reservation] = $this->{$addMethod}();

            if ($addMethod == '_createData' || $addMethod == '_createData2') {
                $params['reservationId'] = $reservation->id;
            }
            if ($addMethod == '_createData' || $addMethod == '_createData3') {
                $params['menuId'] = $menu->id;
            }
        }

        // テスト実施
        $request  = new menuVacancyRequest($params);
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
                    'visitDate' => '',
                    'menuId' => '',
                    'reservationId' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateは必ず指定してください。'],
                    'menuId' => [
                        'reservation idを指定しない場合は、menu idを指定してください。',
                        'メニューが存在しません。',
                    ],
                    'reservationId' => [
                        'menu idを指定しない場合は、reservation idを指定してください。',
                        '予約が存在しません。',
                    ],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-10',
                    'menuId' => null,               // テスト直前に追加関数で発行された分をセットする
                    'reservationId' =>  null,       // テスト直前に追加関数で発行された分をセットする
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
                    'visitDate' => '2099/10/01',    // Y/m/d形式
                    'menuId' => null,               // テスト直前に追加関数で発行された分をセットする
                    'reservationId' =>  null,       // テスト直前に追加関数で発行された分をセットする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notFormat2' => [
                // テスト条件
                [
                    'visitDate' => '20991001',      // Ymd形式
                    'menuId' => null,               // テスト直前に追加関数で発行された分をセットする
                    'reservationId' =>  null,       // テスト直前に追加関数で発行された分をセットする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notFormat3' => [
                // テスト条件
                [
                    'visitDate' => '２０９９／１０／０１',  // 全角
                    'menuId' => null,                   // テスト直前に追加関数で発行された分をセットする
                    'reservationId' =>  null,           // テスト直前に追加関数で発行された分をセットする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'visitDate' => ['visit dateはY-m-d形式で指定してください。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => '１２３',               // 全角数字
                    'reservationId' => '１２３',        // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => [
                        'menu idは整数で指定してください。',
                        'メニューが存在しません。',
                    ],
                    'reservationId' => [
                        'reservation idは整数で指定してください。',
                        '予約が存在しません。',
                    ],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => 'menuId',                   // 半角文字
                    'reservationId' => 'reservationId',     // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => [
                        'menu idは整数で指定してください。',
                        'メニューが存在しません。',
                    ],
                    'reservationId' => [
                        'reservation idは整数で指定してください。',
                        '予約が存在しません。',
                    ],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => 'あああああ',               // 全角文字
                    'reservationId' => 'いいいいい',        // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => [
                        'menu idは整数で指定してください。',
                        'メニューが存在しません。',
                    ],
                    'reservationId' => [
                        'reservation idは整数で指定してください。',
                        '予約が存在しません。',
                    ],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-requiredWithout' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => null,
                    'reservationId' => null,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => [
                        'menu idは整数で指定してください。',
                        'reservation idを指定しない場合は、menu idを指定してください。',
                    ],
                    'reservationId' => [
                        'reservation idは整数で指定してください。',
                        'menu idを指定しない場合は、reservation idを指定してください。',
                    ],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notMenuId' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => '',
                    'reservationId' => null,       // テスト直前に追加関数で発行された分をセットする
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menuId' => ['メニューが存在しません。'],
                ],
                // 追加呼び出し関数
                '_createData2',
            ],
            'error-withValidator-notMenuId' => [
                // テスト条件
                [
                    'visitDate' => '2099-10-01',
                    'menuId' => null,               // テスト直前に追加関数で発行された分をセットする
                    'reservationId' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'reservationId' => ['予約が存在しません。'],
                ],
                // 追加呼び出し関数
                '_createData3',
            ],
        ];
    }

    private function _createData()
    {
        $menu = new Menu();
        $menu->save();

        $reservation = new Reservation();
        $reservation->pick_up_datetime = '2099/10/01 09:00:00';
        $reservation->save();

        return [$menu, $reservation];
    }

    private function _createData2()
    {
        return $this->_createData();
    }

    private function _createData3()
    {
        return $this->_createData();
    }
}
