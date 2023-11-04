<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\NoticeSearchRequest;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NoticeSearchRequestTest extends TestCase
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
        $request  = new NoticeSearchRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testRules(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        // 追加呼出関数の指定がある場合は、呼び出す
        if (!empty($addMethod)) {
            $result = $this->{$addMethod}();
            if ($addMethod == '_createUser') {
                $params['updated_by'] = $result->id;
            }
        }

        // テスト実施
        $request  = new NoticeSearchRequest();
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new NoticeSearchRequest();
        $result = $request->attributes();
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('datetime_from', $result);
        $this->assertSame('掲載開始日時', $result['datetime_from']);
        $this->assertArrayHasKey('datetime_to', $result);
        $this->assertSame('掲載終了日時', $result['datetime_to']);
        $this->assertArrayHasKey('updated_by', $result);
        $this->assertSame('更新者', $result['updated_by']);
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
                    'datetime_from' => '',
                    'datetime_to' => '',
                    'updated_by' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                    'updated_by' => null,                       // テスト実行前にstaffテーブルのidがセットされる
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createUser',
            ],
            'error-notDate' => [
                // テスト条件
                [
                    'datetime_from' => 'yyyymmdd',
                    'datetime_to' => 'yyyymmdd',
                    'updated_by' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'datetime_from' => ['掲載開始日時には有効な日付を指定してください。'],
                    'datetime_to' => ['掲載終了日時には有効な日付を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-setDateEnabled' => [
                // テスト条件
                [
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2022-10-01 09:00:00',
                    'updated_by' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-setDateEnabled2' => [
                // テスト条件
                [
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2022-10-02 09:00:00',
                    'updated_by' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-setDateDisabled' => [
                // テスト条件
                [
                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2021-12-31 23:59:59',
                    'updated_by' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'datetime_to' =>  ['掲載終了日時には、掲載開始日時以降の日付を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notIngeter' => [
                // テスト条件
                [

                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                    'updated_by' => 'test',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'updated_by' => ['更新者は整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-belowMinimum' => [
                // テスト条件
                [

                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                    'updated_by' => 0,                          // min:1
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'updated_by' => ['更新者には、1以上の数字を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notExists' => [
                // テスト条件
                [

                    'datetime_from' => '2022-10-01 09:00:00',
                    'datetime_to' => '2025-12-31 23:59:59',
                    'updated_by' => 1000000000000,              // staffテーブルに存在しないid
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'updated_by' => ['選択された更新者は正しくありません。'],
                ],
                // 追加呼び出し関数
                null,
            ],
        ];
    }

    private function _createUser()
    {
        $staff = new Staff();
        $staff->staff_authority_id = 1;
        $staff->username = 'テストユーザーテストユーザー';
        $staff->save();
        return $staff;
    }
}
