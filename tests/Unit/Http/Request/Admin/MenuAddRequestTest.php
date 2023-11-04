<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\MenuAddRequest;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class MenuAddRequestTest extends TestCase
{
    private $testMenuId;
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
        $request  = new MenuAddRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages)
    {
        $this->_createData();

        // テスト実施
        $request  = new MenuAddRequest($params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new MenuAddRequest();
        $result = $request->attributes();
        $this->assertCount(8, $result);
        $this->assertArrayHasKey('app_cd', $result);
        $this->assertSame('利用サービス', $result['app_cd']);
        $this->assertArrayHasKey('sales_lunch_start_time', $result);
        $this->assertSame('ランチ販売開始時間', $result['sales_lunch_start_time']);
        $this->assertArrayHasKey('sales_lunch_end_time', $result);
        $this->assertSame('ランチ販売終了時間', $result['sales_lunch_end_time']);
        $this->assertArrayHasKey('sales_dinner_start_time', $result);
        $this->assertSame('ディナー販売開始時間', $result['sales_dinner_start_time']);
        $this->assertArrayHasKey('sales_dinner_end_time', $result);
        $this->assertSame('ディナー販売終了時間', $result['sales_dinner_end_time']);
        $this->assertArrayHasKey('lower_orders_time_hour', $result);
        $this->assertSame('最低注文時間(時間)', $result['lower_orders_time_hour']);
        $this->assertArrayHasKey('lower_orders_time_minute', $result);
        $this->assertSame('最低注文時間(分)', $result['lower_orders_time_minute']);
        $this->assertArrayHasKey('store_name', $result);
        $this->assertSame('店舗名', $result['store_name']);
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
                    'menu_name' => '',
                    'menu_description' => '',
                    'sales_lunch_start_time' => '',
                    'sales_lunch_end_time' => '',
                    'sales_dinner_start_time' => '',
                    'sales_dinner_end_time' => '',
                    'app_cd' => '',
                    'number_of_orders_same_time' => '',
                    'number_of_course' => '',
                    'provided_time' => '',
                    'lower_orders_time_hour' => '',
                    'lower_orders_time_minute' => '',
                    'free_drinks' => '',
                    'published' => '',
                    'plan' => '',
                    'menu_notes' => '',
                    'buffet_lp_published' => '',
                    'provided_day_of_week' => [],
                    'store_name' => 'テスト店舗1234',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu_name' => ['メニュー名は必ず指定してください。'],
                    'app_cd' => ['利用サービスは必ず指定してください。'],
                    'provided_day_of_week' => ['提供可能曜日は1つ以上設定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-requireIfTakeout' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'TO',
                    'number_of_orders_same_time' => '',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'number_of_orders_same_time' => ['テイクアウトを選択している場合は、同時間帯注文組数も入力してください。'],
                    'app_cd' => ['店舗情報に設定してある利用サービスのメニューのみ登録可能です。'],
                ],
            ],
            'error-requireIfRestraunt' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '',
                    'provided_time' => '',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'number_of_course' => [
                        'レストランを選択している場合は、コース品数も入力してください。',
                        'コース品数は数字またはハイフン(-)1つのみで指定してください。',
                    ],
                    'provided_time' => ['レストランを選択している場合は、提供時間も入力してください。'],
                    'free_drinks' => ['利用サービスがありの場合は、飲み放題も指定してください。'],
                ],
            ],
            'error-emptyStartSaleTime' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sales_lunch_start_time' => ['ランチ販売終了時間を指定する場合は、ランチ販売開始時間も指定してください。'],
                    'sales_dinner_start_time' => ['ディナー販売終了時間を指定する場合は、ディナー販売開始時間も指定してください。'],
                ],
            ],
            'error-emptyEndSaleTime' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sales_lunch_end_time' => ['ランチ販売開始時間を指定する場合は、ランチ販売終了時間も指定してください。'],
                    'sales_dinner_end_time' => ['ディナー販売開始時間を指定する場合は、ディナー販売終了時間も指定してください。'],
                ],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'menu_name' => Str::random(101),                // max:100
                    'menu_description' => Str::random(81),          // max:80
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => Str::random(2001),                    // max:2000
                    'menu_notes' => Str::random(201),               // max:200
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'menu_name' => ['メニュー名は、100文字以下で指定してください。'],
                    'menu_description' => ['メニュー説明は、80文字以下で入力して下さい。'],
                    'plan' => ['planは、2000文字以下で入力して下さい。'],
                    'menu_notes' => ['menu notesは、200文字以下で入力して下さい。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'menu_name' => Str::random(100),                // max:100
                    'menu_description' => Str::random(80),          // max:80
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => Str::random(2000),                    // max:2000
                    'menu_notes' => Str::random(200),               // max:200
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => 0,                               // min:1
                    'lower_orders_time_hour' => -1,                     // min:0
                    'lower_orders_time_minute' => -1,                   // min:0
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'provided_time' => ['提供時間には、1以上の数字を指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)には、0以上の数字を指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)には、0以上の数字を指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => 1,                               // min:1
                    'lower_orders_time_hour' => 0,                      // min:0
                    'lower_orders_time_minute' => 0,                    // min:0
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '１２３４',              // 全角数字
                    'lower_orders_time_hour' => '１２３４',     // 全角数字
                    'lower_orders_time_minute' => '１２３４',   // 全角数字
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '１２３４',        // 全角数字
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'provided_time' => ['提供時間は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'buffet_lp_published' => ['buffet lp publishedは整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => 'aaaa',                  // 半角文字
                    'lower_orders_time_hour' => 'bbbb',         // 半角文字
                    'lower_orders_time_minute' => 'cccc',       // 半角文字
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => 'aaaa',            // 半角文字
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'provided_time' => ['提供時間は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'buffet_lp_published' => ['buffet lp publishedは整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => 'あああああ',                  // 全角文字
                    'lower_orders_time_hour' => 'いいいいい',         // 全角文字
                    'lower_orders_time_minute' => 'ううううう',       // 全角文字
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => 'えええええ',            // 全角文字
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'provided_time' => ['提供時間は整数で指定してください。'],
                    'lower_orders_time_hour' => ['最低注文時間(時間)は整数で指定してください。'],
                    'lower_orders_time_minute' => ['最低注文時間(分)は整数で指定してください。'],
                    'buffet_lp_published' => ['buffet lp publishedは整数で指定してください。'],
                ],
            ],
            'error-SaleTime' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '10:00:00',
                    'sales_lunch_end_time' => '09:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '16:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'sales_lunch_start_time' => ['販売開始時間は、販売終了時間より前の時間を指定してください。'],
                    'sales_dinner_start_time' => ['販売開始時間は、販売終了時間より前の時間を指定してください。'],
                ],
            ],
            'error-availableLowerLimit' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                    'available_number_of_lower_limit' => '100',
                    'available_number_of_upper_limit' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'available_number_of_lower_limit' => ['利用可能下限人数は99以下で指定してください。'],
                ],
            ],
            'error-availableLowerLimit2' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                    'available_number_of_lower_limit' => '0',
                    'available_number_of_upper_limit' => '80',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'available_number_of_lower_limit' => ['利用可能下限人数は1以上で指定してください。'],
                ],
            ],
            'error-availableUpperLimit' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                    'available_number_of_lower_limit' => '',
                    'available_number_of_upper_limit' => '0',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'available_number_of_upper_limit' => ['利用可能上限人数は1以上で指定してください。'],
                ],
            ],
            'error-availableUpperLimit2' => [
                // テスト条件
                [
                    'menu_name' => 'テストメニュー',
                    'menu_description' => 'テスト説明',
                    'sales_lunch_start_time' => '09:00:00',
                    'sales_lunch_end_time' => '14:00:00',
                    'sales_dinner_start_time' => '17:00:00',
                    'sales_dinner_end_time' => '21:00:00',
                    'app_cd' => 'RS',
                    'number_of_orders_same_time' => '60',
                    'number_of_course' => '3',
                    'provided_time' => '120',
                    'lower_orders_time_hour' => '1',
                    'lower_orders_time_minute' => '30',
                    'free_drinks' => '1',
                    'published' => '0',
                    'plan' => '1000',
                    'menu_notes' => 'テスト',
                    'buffet_lp_published' => '0',
                    'provided_day_of_week' => ['1', 0, 0, 0, 0, 0, 0, 0],
                    'available_number_of_lower_limit' => '50',
                    'available_number_of_upper_limit' => '100',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'available_number_of_upper_limit' => ['利用可能上限人数は99以下で指定してください。'],
                ],
            ],
        ];
    }

    private function _createData()
    {
        $store = new Store();
        $store->app_cd = 'RS';
        $store->name = 'テスト店舗1234';
        $store->published = 1;
        $store->save();
        $this->testStoreId = $store->id;

        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmettaroutest');
        $staff->staff_authority_id = '3';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->store_id = $store->id;
        $staff->save();

        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmettaroutest',
        ]); //ログインしておく
    }
}
