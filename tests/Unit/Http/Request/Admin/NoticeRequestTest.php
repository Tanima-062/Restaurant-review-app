<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\NoticeRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class NoticeRequestTest extends TestCase
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
        $request  = new NoticeRequest();
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
        $request  = new NoticeRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new NoticeRequest();
        $result = $request->attributes();
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('タイトル', $result['title']);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('本文', $result['message']);
        $this->assertArrayHasKey('published_at', $result);
        $this->assertSame('公開日時', $result['published_at']);
        $this->assertArrayHasKey('datetime_from', $result);
        $this->assertSame('掲載開始日時', $result['datetime_from']);
        $this->assertArrayHasKey('datetime_to', $result);
        $this->assertSame('掲載終了日時', $result['datetime_to']);
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
                    'title' => '',
                    'message' => '',
                    'published_at' => '',
                    'datetime_from' => '',
                    'datetime_to' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'title' => ['タイトルは必ず指定してください。'],
                    'message' => ['本文は必ず指定してください。'],
                    'published_at' => ['公開日時は必ず指定してください。'],
                    'datetime_from' => ['掲載開始日時は必ず指定してください。'],
                    'datetime_to' => ['掲載終了日時は必ず指定してください。'],
                ],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'title' => 'テストタイトル',
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notString' => [
                // テスト条件
                [
                    'title' => 123,
                    'message' => 123,
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'title' => ['タイトルは文字列を指定してください。'],
                    'message' => ['本文は文字列を指定してください。'],
                ],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'title' => Str::random(128),                // max:128
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'title' => Str::random(129),                // max:128
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'title' => ['タイトルは、128文字以下で指定してください。'],
                ],
            ],
            'error-notDate' => [
                // テスト条件
                [
                    'title' => 'テストタイトル',
                    'message' => 'テストお知らせ',
                    'published_at' => 'yyyymmdd',
                    'datetime_from' => 'yyyymmdd',
                    'datetime_to' => 'yyyymmdd',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'published_at' => ['公開日時には有効な日付を指定してください。'],
                    'datetime_from' => ['掲載開始日時には有効な日付を指定してください。'],
                    'datetime_to' => ['掲載終了日時には有効な日付を指定してください。'],
                ],
            ],
            'success-setDateEnabled' => [
                // テスト条件
                [
                    'title' => 'テストタイトル',
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2022-10-01 09:00:00',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-setDateEnabled2' => [
                // テスト条件
                [
                    'title' => 'テストタイトル',
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2022-10-02 09:00:00',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-setDateDisabled' => [
                // テスト条件
                [
                    'title' => 'テストタイトル',
                    'message' => 'テストお知らせ',
                    'published_at' => '2022-10-01 09:00:00',
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2021-12-31 23:59:59',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'datetime_to' =>  ['掲載終了日時には、掲載開始日時以降の日付を指定してください。'],
                ],
            ],
        ];
    }
}
