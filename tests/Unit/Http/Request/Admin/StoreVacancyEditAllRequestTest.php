<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreVacancyEditAllRequest;
use App\Models\OpeningHour;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreVacancyEditAllRequestTest extends TestCase
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
        $request  = new StoreVacancyEditAllRequest();
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

        // withValidator関数でrequestを使用するため、request helperにparamをセットする
        request()->merge($params);

        // テスト実施
        // new StoreVacancyEditAllRequestの呼び出し方法では、routeメソッド（$this->route('id')）が機能しないため、setRouteResolverを使って無理やりrouteを設定する
        // 参照：https://oki2a24.com/2021/07/06/4-ways-to-unit-test-even-if-accessing-uri-parameter-defined-in-route-in-rules-method-of-the-form-request-class-in-laravel6/
        $request = StoreVacancyEditAllRequest::create('api/user/' . $this->testStoreId, Request::METHOD_PATCH, $params);
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
        $request  = new StoreVacancyEditAllRequest();
        $result = $request->attributes();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('start', $result);
        $this->assertSame('開始日', $result['start']);
        $this->assertArrayHasKey('end', $result);
        $this->assertSame('終了日', $result['end']);
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
        $now = new Carbon();
        $tomorrow = $now->copy()->addDays(1);    // 明日
        $month2 = $now->copy()->addMonths(1);    // 2ヶ月先の日付
        $month3 = $now->copy()->addMonths(2);    // 3ヶ月先の日付
        $month4 = $now->copy()->addMonths(4);    // 4ヶ月後の1日
        $month4->day = 1;

        // testRules関数で順番にテストされる
        return [
            'success-empty' => [
                // テスト条件
                [
                    'start' => '',
                    'end' => '',
                    'week' => [],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'start' => [
                        '開始日は必ず指定してください。',
                        '開始日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。',
                    ],
                    'end' => [
                        '終了日は必ず指定してください。',
                        '終了日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。',
                    ],
                    'week' => ['営業曜日は1つ以上必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'start' => $tomorrow->toDateString(),
                    'end' => $month2->toDateString(),
                    'week' => ['1', '1', '1', '1', '1', '1', '1', '1'],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            // 'error-notFormat' => [],    // withValidation関数でnew Carbon部分でエラーになるため、日付形式のテストができなかった（使用関数先では確実に日付形式で取得できそうなので問題ないとする）
            'error-withValidator-date' => [
                // テスト条件
                [
                    'start' => $now->toDateString(),                        // 本日をセット、明日以降であればPASS
                    'end' => $month4->toDateString(),                       // 4ヶ月後の1日をセット、3ヶ月後の末日以内であればPASS
                    'week' => ['1', '1', '1', '1', '1', '1', '1', '1'],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'start' => ['開始日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。'],
                    'end' => ['終了日はカレンダーから選択してください。明日から３ヶ月後の末日まで指定可能。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-withValidator-date' => [
                // テスト条件
                [
                    'start' => $tomorrow->toDateString(),                   // 明日をセット、明日以降であればPASS
                    'end' => $month3->toDateString(),                       // 3ヶ月をセット、3ヶ月後の末日以内であればPASS
                    'week' => ['1', '1', '1', '1', '1', '1', '1', '1'],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notWeek' => [
                // テスト条件
                [
                    'start' => $tomorrow->toDateString(),                   // 明日をセット、明日以降であればPASS
                    'end' => $month3->toDateString(),                       // 3ヶ月をセット、3ヶ月後の末日以内であればPASS
                    'week' => ['0', '0', '0', '0', '0', '0', '0', '0'],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'week' => ['営業曜日は1つ以上必須です。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notOpenWeek' => [
                // テスト条件
                [
                    'start' => $tomorrow->toDateString(),                   // 明日をセット、明日以降であればPASS
                    'end' => $month3->toDateString(),                       // 3ヶ月をセット、3ヶ月後の末日以内であればPASS
                    'week' => ['0', '1', '1', '0', '0', '0', '0', '0'],     // 火曜日だけ設定したい
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'week' => ['営業時間が異なる営業曜日は同時に登録できません。'],
                ],
                // 追加呼び出し関数
                '_changOpeningHourWeek',
            ],
            'success-withValidator-notOpenWeek' => [
                // テスト条件
                [
                    'start' => $tomorrow->toDateString(),                   // 本日をセット、明日以降であればPASS
                    'end' => $month3->toDateString(),                       // 4ヶ月後の1日をセット、3ヶ月後の末日以内であればPASS
                    'week' => ['1', '0', '0', '0', '0', '0', '0', '0'],     // 月曜日だけ設定したい
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_changOpeningHourWeek',
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

        $openingHour = new OpeningHour();
        $openingHour->store_id = $this->testStoreId;
        $openingHour->week = '11111111';
        $openingHour->save();
    }

    private function _changOpeningHourWeek()
    {
        OpeningHour::where('store_id', $this->testStoreId)->update(['week' => '10111111']); // 火曜以外営業日
    }
}
