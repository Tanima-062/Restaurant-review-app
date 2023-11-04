<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffDetailRequest;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StaffDetailRequestTest extends TestCase
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
        $request  = new StaffDetailRequest();
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
            $this->{$addMethod}();
            if ($addMethod == '_createData2') {
                $params['store_id'] = $this->testStoreId;
            }
        }

        // テスト実施
        $request  = new StaffDetailRequest($params);
        $validator = Validator::make($params, []);
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
        // testWithValidator関数で順番にテストされる
        return [
            'success-empty' => [
                // テスト条件
                [
                    'store_id' => '',
                    'staff_authority_id' => '',
                    'settlement_company_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notEmpty' => [
                // テスト条件
                [
                    'store_id' => '',               // テスト実行前にidがセットされる
                    'staff_authority_id' => 3,      // クライアント管理者
                    'settlement_company_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                    'store_id' => ['店舗はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notEmpty2' => [
                // テスト条件
                [
                    'store_id' => '',               // テスト実行前にidがセットされる
                    'staff_authority_id' => 4,      // クライアント一般
                    'settlement_company_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                    'store_id' => ['店舗はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'store_id' => '',
                    'staff_authority_id' => 1,      // 社内管理者
                    'settlement_company_id' => 1,
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
                    'store_id' => '',
                    'staff_authority_id' => 2,      // 社内一般
                    'settlement_company_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty3' => [
                // テスト条件
                [
                    'store_id' => '',               // テスト実行前にidがセットされる
                    'staff_authority_id' => 3,      // クライアント管理者
                    'settlement_company_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData2',
            ],
            'success-notEmpty4' => [
                // テスト条件
                [
                    'store_id' => '',               // テスト実行前にidがセットされる
                    'staff_authority_id' => 4,      // クライアント一般
                    'settlement_company_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData2',
            ],
            'success-notEmpty5' => [
                // テスト条件
                [
                    'store_id' => '',
                    'staff_authority_id' => 5,      // 社外一般権限
                    'settlement_company_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty6' => [
                // テスト条件
                [
                    'store_id' => '',
                    'staff_authority_id' => 6,      // 精算管理会社
                    'settlement_company_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
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
        $staff->staff_authority_id = '2';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->store_id = $store->id;
        $staff->save();

        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmettaroutest',
        ]); //ログインしておく
    }

    private function _createData2()
    {
        $this->_createData();
    }
}
