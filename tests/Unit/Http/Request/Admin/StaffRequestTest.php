<?php

namespace Tests\Unit\Http\Request\Admin;

use App\Http\Requests\Admin\StaffRequest;
use App\Models\SettlementCompany;
use App\Models\Staff;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffRequestTest extends TestCase
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
        $request  = new StaffRequest();
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
            list($store, $staff, $settlementCompany) = $this->{$addMethod}();
            $params['id'] = $staff->id;
            if ($addMethod == '_createData' || $addMethod == '_createData3') {
                $params['store_id'] = $store->id;
            }
            if ($addMethod == '_createData' || $addMethod == '_createData2') {
                $params['settlement_company_id'] = $settlementCompany->id;
            }
        }

        // テスト実施
        $request  = new StaffRequest($params, $params);
        $rules = $request->rules();
        $attributes = $request->attributes();
        $errorMessages = $request->messages();
        $validator = Validator::make($params, $rules, $errorMessages, $attributes);
        $request->withValidator($validator);                                // withValidator関数呼び出し
        $this->assertEquals($expected, $validator->passes());               // テスト結果
        $this->assertSame($messages, $validator->errors()->messages());     // テストエラーメッセージ
    }

    public function testAttributes()
    {
        $request  = new StaffRequest();
        $result = $request->attributes();
        $this->assertCount(5, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame('お名前', $result['name']);
        $this->assertArrayHasKey('val-password2', $result);
        $this->assertSame('パスワード', $result['val-password2']);
        $this->assertArrayHasKey('val-confirm-password2', $result);
        $this->assertSame('パスワード確認', $result['val-confirm-password2']);
        $this->assertArrayHasKey('username', $result);
        $this->assertSame('ログインID', $result['username']);
        $this->assertArrayHasKey('staff_authority_id', $result);
        $this->assertSame('権限', $result['staff_authority_id']);
    }

    public function testMessages()
    {
        $request  = new StaffRequest();
        $result = $request->messages();
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('username.regex', $result);
        $this->assertSame('ログインIDは半角英数字で入力してください', $result['username.regex']);
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
            ' error-empty' => [
                // テスト条件
                [
                    'name' => '',
                    'val-password2' => '',
                    'val-confirm-password2' => '',
                    'username' => '',
                    'staff_authority_id' => '',
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['お名前は必ず指定してください。'],
                    'val-password2' => ['パスワードは必ず指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は必ず指定してください。'],
                    'username' => ['ログインIDは必ず指定してください。'],
                    'staff_authority_id' => ['権限は必ず指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-notEmpty' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 1,                // 社内管理者
                    'settlement_company_id' => 1,
                    'store_id' => 1,
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpa',
                    'val-confirm-password2' => 'Testpa',
                    'username' => 'ｖ１２３４',                 // 全角英字数字
                    'staff_authority_id' => 1,                // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードに正しい形式を指定してください。'],
                    'username' => ['ログインIDは半角英数字で入力してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex2' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'ああああああ',          // 全角文字
                    'val-confirm-password2' => 'ああああああ',  // 全角文字
                    'username' => 'いいいいいい',               // 全角文字
                    'staff_authority_id' => 1,                // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードに正しい形式を指定してください。'],
                    'username' => ['ログインIDは半角英数字で入力してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notRegex3' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => '*Testpa',           // 記号あり
                    'val-confirm-password2' => '*Testpa',   // 記号あり
                    'username' => 'TestGourmet-tarou123**', // 記号あり
                    'staff_authority_id' => 1,              // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードに正しい形式を指定してください。'],
                    'username' => ['ログインIDは半角英数字で入力してください'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notSame' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpa123',
                    'val-confirm-password2' => 'Testpa',    // val-password2と同じ値でない
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 1,              // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-confirm-password2' => ['パスワード確認とパスワードには同じ値を指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-belowMinimum' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Tes1p',                // 5桁、min:6
                    'val-confirm-password2' => 'Tes1p',        // 5桁、min:6
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 1,                // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'val-password2' => ['パスワードは、6文字以上で指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は、6文字以上で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-minimum' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Tes1pa',                // min:6
                    'val-confirm-password2' => 'Tes1pa',        // min:6
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 1,                // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-maximum' => [
                // テスト条件
                [
                    'name' => Str::random(64),
                    'val-password2' => str_repeat('Test1', 6),          // max:30
                    'val-confirm-password2' => str_repeat('Test1', 6),  // max:30
                    'username' => Str::random(64),
                    'staff_authority_id' => 1,                          // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-overMaximum' => [
                // テスト条件
                [
                    'name' => Str::random(65),
                    'val-password2' => str_repeat('Test1', 6) . '1',            // 31桁、max:30
                    'val-confirm-password2' => str_repeat('Test1', 6) . '1',    // 31桁、max:30
                    'username' => Str::random(65),
                    'staff_authority_id' => 1,                                  // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'name' => ['お名前は、64文字以下で指定してください。'],
                    'val-password2' => ['パスワードは、30文字以下で指定してください。'],
                    'val-confirm-password2' => ['パスワード確認は、30文字以下で指定してください。'],
                    'username' => ['ログインIDは、64文字以下で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notUnique' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'goumet-tarou',
                    'staff_authority_id' => 1,                  // 社内管理者
                    'settlement_company_id' => '',
                    'store_id' => '',                           // テスト実行前にidがセットされる
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'username' => ['ログインIDの値は既に存在しています。'],
                ],
                // 追加呼び出し関数
                '_createData',
            ],
            'error-notInteger' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => '１２３４',          // 全角数値
                    'settlement_company_id' => '１２３４',       // 全角数値
                    'store_id' => '１２３４',                    // 全角数値
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'staff_authority_id' => ['権限は整数で指定してください。'],
                    'settlement_company_id' => ['settlement company idは整数で指定してください。'],
                    'store_id' => ['store idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger2' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 'aaaaaaa',          // 半角文字
                    'settlement_company_id' => 'bbbbbbb',       // 半角文字
                    'store_id' => 'ccccccc',                    // 半角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'staff_authority_id' => ['権限は整数で指定してください。'],
                    'settlement_company_id' => ['settlement company idは整数で指定してください。'],
                    'store_id' => ['store idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notInteger3' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 'ああああああ',         // 全角文字
                    'settlement_company_id' =>  'いいいいいい',     // 全角文字
                    'store_id' => 'ううううう',                    // 全角文字
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'staff_authority_id' => ['権限は整数で指定してください。'],
                    'settlement_company_id' => ['settlement company idは整数で指定してください。'],
                    'store_id' => ['store idは整数で指定してください。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'error-notExists' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 10000,              // 無効な値
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'staff_authority_id' => ['選択された権限は正しくありません。'],
                ],
                // 追加呼び出し関数
                null,
            ],
            'success-withValidator' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 2,                          // 社内一般
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-withValidator2' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 3,                      // クライアント管理者
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData',
            ],
            'success-withValidator3' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 4,                      // クライアント一般
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                '_createData',
            ],
            'success-withValidator4' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 5,                      // 社外一般権限
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'success-withValidator5' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 6,                      // 精算管理会社
                    'settlement_company_id' => '',
                    'store_id' => '',
                ],
                // テスト結果
                true,
                // エラーメッセージ
                [],
                // 追加呼び出し関数
                null,
            ],
            'error-withValidator-notStoreId' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 3,                      // クライアント管理者
                    'settlement_company_id' => '',                  // テスト実行前にidがセットされる
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store_id' => ['店舗はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData2',
            ],
            'error-withValidator-notStoreId2' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 4,                      // クライアント管理者
                    'settlement_company_id' => '',                  // テスト実行前にidがセットされる
                    'store_id' => '',
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'store_id' => ['店舗はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData2',
            ],
            'error-withValidator-notsettlementCompanyId' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 3,                      // クライアント管理者
                    'settlement_company_id' => '',
                    'store_id' => '',                               // テスト実行前にidがセットされる
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData3',
            ],
            'error-withValidator-notsettlementCompanyId2' => [
                // テスト条件
                [
                    'name' => 'テストグルメ太郎',
                    'val-password2' => 'Testpassword1',
                    'val-confirm-password2' => 'Testpassword1',
                    'username' => 'TestGourmet-tarou123',
                    'staff_authority_id' => 4,                      // クライアント管理者
                    'settlement_company_id' => '',
                    'store_id' => '',                               // テスト実行前にidがセットされる
                ],
                // テスト結果
                false,
                // エラーメッセージ
                [
                    'settlement_company_id' => ['精算会社はクライアント管理者もしくはクライアント一般の場合は必須です。'],
                ],
                // 追加呼び出し関数
                '_createData3',
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

        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmetTarouTest123');
        $staff->staff_authority_id = '2';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->store_id = $store->id;
        $staff->save();

        $settlementCompany = new SettlementCompany();
        $settlementCompany->save();

        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmetTarouTest123',
        ]); //ログインしておく

        return [$store, $staff, $settlementCompany];
    }

    private function _createData2()
    {
        return $this->_createData();
    }

    private function _createData3()
    {
        return $this->_createData();
    }
}
