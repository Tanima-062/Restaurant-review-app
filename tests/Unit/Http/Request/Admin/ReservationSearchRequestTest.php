<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\ReservationSearchRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ReservationSearchRequestTest extends TestCase
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
        $request  = new ReservationSearchRequest();
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
        $request  = new ReservationSearchRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new ReservationSearchRequest();
        $result = $request->attributes();
        $this->assertCount(13, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id', $result['id']);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertSame('姓', $result['last_name']);
        $this->assertArrayHasKey('first_name', $result);
        $this->assertSame('名', $result['first_name']);
        $this->assertArrayHasKey('email', $result);
        $this->assertSame('メールアドレス', $result['email']);
        $this->assertArrayHasKey('tel', $result);
        $this->assertSame('電話番号', $result['tel']);
        $this->assertArrayHasKey('reservation_status', $result);
        $this->assertSame('予約ステータス', $result['reservation_status']);
        $this->assertArrayHasKey('payment_status', $result);
        $this->assertSame('入金ステータス', $result['payment_status']);
        $this->assertArrayHasKey('created_at_from', $result);
        $this->assertSame('申込日時from', $result['created_at_from']);
        $this->assertArrayHasKey('created_at_to', $result);
        $this->assertSame('申込日時to', $result['created_at_to']);
        $this->assertArrayHasKey('pick_up_datetime_from', $result);
        $this->assertSame('来店日時from', $result['pick_up_datetime_from']);
        $this->assertArrayHasKey('pick_up_datetime_to', $result);
        $this->assertSame('来店日時to', $result['pick_up_datetime_to']);
        $this->assertArrayHasKey('store_name', $result);
        $this->assertSame('店舗名', $result['store_name']);
        $this->assertArrayHasKey('store_tel', $result);
        $this->assertSame('店舗電話番号', $result['store_tel']);
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
                    'id' => '',
                    'last_name' => '',
                    'first_name' => '',
                    'email' => '',
                    'tel' => '',
                    'reservation_status' => '',
                    'payment_status' => '',
                    'created_at_from' => '',
                    'created_at_to' => '',
                    'pick_up_datetime_from' => '',
                    'pick_up_datetime_to' => '',
                    'store_name' => '',
                    'store_tel' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'id' => 123,
                    'last_name' => 123,
                    'first_name' => 123,
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 123,
                    'payment_status' => 123,
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 123,
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['idは文字列を指定してください。'],
                    'last_name' => ['姓は文字列を指定してください。'],
                    'first_name' => ['名は文字列を指定してください。'],
                    'reservation_status' => ['予約ステータスは文字列を指定してください。'],
                    'payment_status' => ['入金ステータスは文字列を指定してください。'],
                    'store_name' => ['店舗名は文字列を指定してください。'],
                ],
            ],
            'success-belowMinimum' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0',                                       // min:1
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0',                                 // min:1
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'tel' => ['電話番号には、1以上の数字を指定してください。'],
                    'store_tel' => ['店舗電話番号に正しい形式を指定してください。'],
                ],
            ],
            'error-minmum' => [
                // テスト条件
                [
                    'id' => '1',
                    'last_name' => '姓',
                    'first_name' => '名',
                    'email' => 'g',                             // min:1だが、mailフォーマット指定があるため1文字ではエラーになる
                    'tel' => '1',                               // min:1
                    'reservation_status' => 'R',                // min:1
                    'payment_status' => 'U',                    // min:1
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テ',                       // min:1
                    'store_tel' => '1',                         // min:1
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'email' => ['メールアドレスには、有効なメールアドレスを指定してください。'],
                    'store_tel' => ['店舗電話番号に正しい形式を指定してください。'],
                ],
            ],
            'error-notDate' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => 'yyyymmdd',
                    'created_at_to' => 'yyyymmdd',
                    'pick_up_datetime_from' => 'yyyymmdd',
                    'pick_up_datetime_to' => 'yyyymmdd',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'created_at_from' => ['申込日時fromには有効な日付を指定してください。'],
                    'created_at_to' => ['申込日時toには有効な日付を指定してください。'],
                    'pick_up_datetime_from' => ['来店日時fromには有効な日付を指定してください。'],
                    'pick_up_datetime_to' => ['来店日時toには有効な日付を指定してください。'],
                ],
            ],
            'success-setDateEnabled' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-01',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-03',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-setDateEnabled2' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-setDateDisabled' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-09-30',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-02',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0333334444',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'created_at_to' => ['申込日時toには、申込日時from以降の日付を指定してください。'],
                    'pick_up_datetime_to' => ['来店日時toには、来店日時from以降の日付を指定してください。'],
                ],
            ],
            'error-notRegex-notTel' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '０３１１１１２２２２',  // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store_tel' => ['店舗電話番号に正しい形式を指定してください。'],
                ],
            ],
            'error-notRegex-notTel2' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => 'abcdefg1234',                 // 半角英数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store_tel' => ['店舗電話番号に正しい形式を指定してください。'],
                ],
            ],
            'error-notRegex-notTel3' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => 'あいうえおアイウエオ',      // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store_tel' => ['店舗電話番号に正しい形式を指定してください。'],
                ],
            ],
            'success-Regex-Tel' => [
                // テスト条件
                [
                    'id' => '123',
                    'last_name' => 'テスト姓',
                    'first_name' => 'テスト名',
                    'email' => 'gourmet-test1@adventure-inc.co.jp',
                    'tel' => '0311112222',
                    'reservation_status' => 'RESERVE',
                    'payment_status' => 'UNPAID',
                    'created_at_from' => '2022-10-01',
                    'created_at_to' => '2022-10-02',
                    'pick_up_datetime_from' => '2022-10-03',
                    'pick_up_datetime_to' => '2022-10-04',
                    'store_name' => 'テスト店舗',
                    'store_tel' => '0120-123-4567',      // ハイフンあり
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
        ];
    }
}
