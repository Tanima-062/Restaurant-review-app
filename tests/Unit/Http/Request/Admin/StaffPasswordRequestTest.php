<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffPasswordRequest;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StaffPasswordRequestTest extends TestCase
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
        $request  = new StaffPasswordRequest();
        $this->assertTrue($request->authorize());
    }

    /**
     * バリデーションテスト
     *
     * @dataProvider dataprovider
     */
    public function testWithValidator(array $params, bool $expected, array $messages, ?string $addMethod)
    {
        // 追加呼出関数の指定がある場合は、呼び出す
        if (!empty($addMethod)) {
            $result = $this->{$addMethod}();
            $params['id'] = $result->id;
        }

        // テスト実施
        $request  = new StaffPasswordRequest($params, $params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $validator = Validator::make($params, $rules, [], $attributes);
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
            'success-empty' => [
                // テスト条件
                [
                    'val-password2' => '',
                    'val-confirm-password2' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードは必ず指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は必ず指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty2' => [
                // テスト条件
                [
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-canNotChangePassword' => [
                // テスト条件
                [
                    'val-password2' => 'gourmetTarouTest123',
                    'val-confirm-password2' => 'gourmetTarouTest123',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['二回続けて同じパスワードは設定できません。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
        ];
    }

    private function _createData()
    {
        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmetTarouTest123');
        $staff->staff_authority_id = '2';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();
        return $staff;
    }
}
