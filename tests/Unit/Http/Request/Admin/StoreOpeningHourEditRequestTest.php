<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StoreOpeningHourEditRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreOpeningHourEditRequestTest extends TestCase
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
        $request  = new StoreOpeningHourEditRequest();
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
        $request  = new StoreOpeningHourEditRequest($params, $params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StoreOpeningHourEditRequest();
        $result = $request->attributes();
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('store.*.opening_hour_cd', $result);
        $this->assertSame('営業時間コード', $result['store.*.opening_hour_cd']);
        $this->assertArrayHasKey('store.*.start_at', $result);
        $this->assertSame('営業開始時間', $result['store.*.start_at']);
        $this->assertArrayHasKey('store.*.end_at', $result);
        $this->assertSame('営業終了時間', $result['store.*.end_at']);
        $this->assertArrayHasKey('store.*.last_order_time', $result);
        $this->assertSame('ラストオーダー時間', $result['store.*.last_order_time']);
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
                    'store' => [[
                        'opening_hour_cd' => '',
                        'start_at' => '',
                        'end_at' => '',
                        'last_order_time' => '',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.opening_hour_cd' => ['営業時間コードは必ず指定してください。'],
                    'store.0.start_at' => ['営業開始時間は必ず指定してください。'],
                    'store.0.end_at' => ['営業終了時間は必ず指定してください。'],
                    'store.0.last_order_time' => ['ラストオーダー時間は必ず指定してください。'],
                    'sales_lunch_start_time' => ['営業開始時間は、営業終了時間より前の時間を指定してください'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '09:00',
                        'end_at' => '17:00',
                        'last_order_time' => '16:00',
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '24:60',              // 時間は23以下、分は59以下であればOK
                        'end_at' => '24:60',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '16:00',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.start_at' => ['営業開始時間に正しい形式を指定してください。'],
                    'store.0.end_at' => ['営業終了時間に正しい形式を指定してください。'],
                    'sales_lunch_start_time' => ['営業開始時間は、営業終了時間より前の時間を指定してください'],
                ],
            ],
            'error-notRegex2' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '23059',              // 時間は23以下、分は59以下であればOK
                        'end_at' => '23059',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '16:00',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.start_at' => ['営業開始時間に正しい形式を指定してください。'],
                    'store.0.end_at' => ['営業終了時間に正しい形式を指定してください。'],
                    'sales_lunch_start_time' => ['営業開始時間は、営業終了時間より前の時間を指定してください'],
                ],
            ],
            'error-notRegex3' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => 'あああああ',              // 時間は23以下、分は59以下であればOK
                        'end_at' => 'いいいいい',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '16:00',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.start_at' => ['営業開始時間に正しい形式を指定してください。'],
                    'store.0.end_at' => ['営業終了時間に正しい形式を指定してください。'],
                ],
            ],
            'error-notRegex4' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => 'aaaaa',              // 時間は23以下、分は59以下であればOK
                        'end_at' => 'bbbbb',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '16:00',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.start_at' => ['営業開始時間に正しい形式を指定してください。'],
                    'store.0.end_at' => ['営業終了時間に正しい形式を指定してください。'],
                ],
            ],
            'success-regex' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '00:00',              // 時間は23以下、分は59以下であればOK
                        'end_at' => '00:01',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '00:00',
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-regex2' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '23:58',              // 時間は23以下、分は59以下であればOK
                        'end_at' => '23:59',                // 時間は23以下、分は59以下であればOK
                        'last_order_time' => '23:58',
                    ]],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-withValidator-openingHour' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '18:00',
                        'end_at' => '18:00',
                        'last_order_time' => '18:00',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sales_lunch_start_time' => ['営業開始時間は、営業終了時間より前の時間を指定してください'],
                ],
            ],
            'error-withValidator-lastOrderTime' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '09:00',
                        'end_at' => '18:00',
                        'last_order_time' => '08:59',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.last_order_time' => ['ラストオーダーは営業開始時間と終了時間の間で設定してください。'],
                ],
            ],
            'error-withValidator-lastOrderTime2' => [
                // テスト条件
                [
                    'store' => [[
                        'opening_hour_cd' => 'ALL_DAY',
                        'start_at' => '09:00',
                        'end_at' => '18:00',
                        'last_order_time' => '18:01',
                    ]],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store.0.last_order_time' => ['ラストオーダーは営業開始時間と終了時間の間で設定してください。'],
                ],
            ],
        ];
    }
}
