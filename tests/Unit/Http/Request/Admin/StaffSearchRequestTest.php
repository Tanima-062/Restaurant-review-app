<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffSearchRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSearchRequestTest extends TestCase
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
        $request  = new StaffSearchRequest();
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
        $request  = new StaffSearchRequest($params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StaffSearchRequest();
        $result = $request->attributes();
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('担当者ID', $result['id']);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('お名前', $result['name']);
        $this->assertArrayHasKey('username', $result);
        $this->assertSame('ログインID', $result['username']);
        $this->assertArrayHasKey('client_id', $result);
        $this->assertSame('運行会社', $result['client_id']);
        $this->assertArrayHasKey('staff_authority_id', $result);
        $this->assertSame('スタッフ権限', $result['staff_authority_id']);
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
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'id' => 123,
                    'name' => 'テストグルメ太郎',
                    'username' => 'Testgourmettaro123',
                    'client_id' => 123,
                    'staff_authority_id' => 2,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'id' => '１２３',                   // 全角数字
                    'name' => '',
                    'username' => '',
                    'client_id' => '１２３',            // 全角数字
                    'staff_authority_id' => '１２３',   // 全角数字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['担当者IDは整数で指定してください。'],
                    'client_id' => ['運行会社は整数で指定してください。'],
                    'staff_authority_id' => ['スタッフ権限は整数で指定してください。'],
                ],
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'id' => 'aaaaa',                   // 半角文字
                    'name' => '',
                    'username' => '',
                    'client_id' => 'bbbbb',            // 半角文字
                    'staff_authority_id' => 'ccccc',   // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['担当者IDは整数で指定してください。'],
                    'client_id' => ['運行会社は整数で指定してください。'],
                    'staff_authority_id' => ['スタッフ権限は整数で指定してください。'],
                ],
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'id' => 'ああああ',                   // 全角文字
                    'name' => '',
                    'username' => '',
                    'client_id' => 'いいいい',            // 全角文字
                    'staff_authority_id' => 'ううう',   // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['担当者IDは整数で指定してください。'],
                    'client_id' => ['運行会社は整数で指定してください。'],
                    'staff_authority_id' => ['スタッフ権限は整数で指定してください。'],
                ],
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'id' => 0,                          // min:1
                    'name' => 'テストグルメ太郎',
                    'username' => 'Testgourmettaro123',
                    'client_id' => 0,                   // min:1
                    'staff_authority_id' => 0,          // min:1 and staff_authorities table exist
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'id' => ['担当者IDには、1以上の数字を指定してください。'],
                    'client_id' => ['運行会社には、1以上の数字を指定してください。'],
                    'staff_authority_id' => ['スタッフ権限には、1以上の数字を指定してください。'],
                ],
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'id' => 1,                          // min:1
                    'name' => 'テストグルメ太郎',
                    'username' => 'Testgourmettaro123',
                    'client_id' => 1,                   // min:1
                    'staff_authority_id' => 1,          // min:1 and staff_authorities table exist
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => Str::random(64),              // max:64
                    'username' => Str::random(64),          // max:64
                    'client_id' => '',
                    'staff_authority_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => Str::random(65),              // max:64
                    'username' => Str::random(65),          // max:64
                    'client_id' => '',
                    'staff_authority_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['お名前は、64文字以下で指定してください。'],
                    'username' => ['ログインIDは、64文字以下で指定してください。'],
                ],
            ],
            'success-existsStaffAuthority' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 1,          // 社内管理者
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-existsStaffAuthority2' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 2,          // 社内一般
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-existsStaffAuthority3' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 3,          // クライアント管理者
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-existsStaffAuthority4' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 4,          // クライアント一般
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-existsStaffAuthority5' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 5,          // 社外一般権限
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'success-existsStaffAuthority6' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 6,          // 精算管理会社
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
            ],
            'errror-notExistsStaffAuthority' => [
                // テスト条件
                [
                    'id' => '',
                    'name' => '',
                    'username' => '',
                    'client_id' => '',
                    'staff_authority_id' => 7,
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'staff_authority_id' => ['選択されたスタッフ権限は正しくありません。'],
                ],
            ],
        ];
    }
}
