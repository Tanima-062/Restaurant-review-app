<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreVacancyEditCommitRequest;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreVacancyEditCommitRequestTest extends TestCase
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
        $request  = new StoreVacancyEditCommitRequest();
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

        // 追加呼出関数の指定がある場合は、呼び出す
        if (!empty($addMethod)) {
            $this->{$addMethod}();
        }

        // attribute関数でrequestを使用するため、request helperにparamをセットする
        request()->merge($params);

        // テスト実施
        // new StoreVacancyEditCommitRequestの呼び出し方法では、routeメソッド（$this->route('id')）が機能しないため、setRouteResolverを使って無理やりrouteを設定する
        // 参照：https://oki2a24.com/2021/07/06/4-ways-to-unit-test-even-if-accessing-uri-parameter-defined-in-route-in-rules-method-of-the-form-request-class-in-laravel6/
        $request = StoreVacancyEditCommitRequest::create('api/user/' . $this->testStoreId, Request::METHOD_PATCH, $params);
        $request->setRouteResolver(function () use ($request) {
            return (new Route(Request::METHOD_PATCH, 'api/user/{id}', []))
                ->bind($request);
        });
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $param = [
            'interval' => [
                '10:00:00' => [
                    'base_stock' => '',
                    'is_stop_sale' => '',
                ]
            ],
        ];
        request()->merge($param);

        $request  = new StoreVacancyEditCommitRequest();
        $result = $request->attributes();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('interval.10:00:00.base_stock', $result);
        $this->assertSame('10:00:00の在庫数', $result['interval.10:00:00.base_stock']);
        $this->assertArrayHasKey('interval.10:00:00.is_stop_sale', $result);
        $this->assertSame('10:00:00の有効/無効', $result['interval.10:00:00.is_stop_sale']);
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
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => '',
                            'is_stop_sale' => '',
                        ],
                        '10:30:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => '',
                        ],
                        '11:00:00' => [
                            'base_stock' => '',
                            'is_stop_sale' => 0,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'interval.10:00:00.base_stock' => ['10:00:00の在庫数は必ず指定してください。'],
                    'interval.11:00:00.base_stock' => ['11:00:00の在庫数は必ず指定してください。'],
                    'interval.10:00:00.is_stop_sale' => ['10:00:00の有効/無効は必ず指定してください。'],
                    'interval.10:30:00.is_stop_sale' => ['10:30:00の有効/無効は必ず指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 1,
                        ],
                        '10:30:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                        '11:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => '１',                  // 全角数字
                            'is_stop_sale' => 1,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 60,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'interval.10:00:00.base_stock' => ['10:00:00の在庫数は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => 'aaa',                  // 半角文字
                            'is_stop_sale' => 1,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'interval.10:00:00.base_stock' => ['10:00:00の在庫数は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => 'ああ',                 // 全角文字
                            'is_stop_sale' => 1,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'interval.10:00:00.base_stock' => ['10:00:00の在庫数は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-numberOfSeatsEmpty' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 1,
                        ],
                        '10:30:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                        '11:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'number_of_seats' => ['店舗の座席数が設定されていないか0のため空席を登録できません。'],
                ],
                // 追加呼び出し関数
                '_changeStore',
            ],
            'error-numberOfSeatsEmpty2' => [
                // テスト条件
                [
                    'interval' => [
                        '10:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 1,
                        ],
                        '10:30:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                        '11:00:00' => [
                            'base_stock' => 5,
                            'is_stop_sale' => 0,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'number_of_seats' => ['店舗の座席数が設定されていないか0のため空席を登録できません。'],
                ],
                // 追加呼び出し関数
                '_changeStore2',
            ],
            'error-baseStock' => [
                // テスト条件
                [
                    'interval' => [
                        '09:00:00' => [
                            'base_stock' => 4,
                            'is_stop_sale' => 0,
                        ],
                        '09:30:00' => [
                            'base_stock' => 4,      // 09：30+intervalTimeが予約時間（10：00)と重なるため、5未満だとエラーとなる
                            'is_stop_sale' => 0,
                        ],
                        '10:00:00' => [
                            'base_stock' => 4,      // 10：00予約が既に5人分あるため、5未満はエラーとなる
                            'is_stop_sale' => 1,
                        ],
                        '10:30:00' => [
                            'base_stock' => 4,
                            'is_stop_sale' => 0,
                        ],
                        '11:00:00' => [
                            'base_stock' => 4,
                            'is_stop_sale' => 0,
                        ],
                    ],
                    'date' => '2999-01-01',
                    'intervalTime' => 30,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'interval.09:30:00.base_stock' => ['09:30:00の在庫数は予約済み数(来店人数)を下回ることは不可能です。'],
                    'interval.10:00:00.base_stock' => ['10:00:00の在庫数は予約済み数(来店人数)を下回ることは不可能です。'],
                ],
                // 追加呼び出し関数
                '_createReservation',
            ],
        ];
    }

    private function _createData()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->name = 'テスト店舗1234';
        $store->number_of_seats = 50;
        $store->published = 1;
        $store->save();
        $this->testStoreId = $store->id;
    }

    private function _changeStore()
    {
        Store::find($this->testStoreId)->update(['number_of_seats' => 0]);
    }

    private function _changeStore2()
    {
        Store::find($this->testStoreId)->update(['number_of_seats' => null]);
    }

    private function _createReservation()
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->pick_up_datetime = '2999-01-01 10:00:00';
        $reservation->reservation_status = 'ENSURE';
        $reservation->persons = 5;
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->store_id = $this->testStoreId;
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->save();
    }
}
