<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\StaffAuth;
use App\Models\Staff;
use App\Modules\StaffLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffAuthTest extends TestCase
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

    public function testHandle()
    {
        $request = new Request();
        $middleware = new StaffAuth();

        // ログイン情報がない場合
        {
            // middlewareを呼び出し
            $originalResponse = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            $response = $this->createTestResponse($originalResponse);
            // エラーレスポンスが返却されること
            $this->assertNotNull($response);
            // ステータスコードが400であること
            $response->assertStatus(401);
            // JSONレスポンス（空）が返却されること
            $response->assertExactJson([]);
        }

        // ログイン情報がある場合
        {
            // テストアカウントを作成し、ログインする
            $this->_createStaff();
            $rememberToken = '';
            $loginRequest = new Request();
            $loginRequest->merge([
                'userName' => 'test-goumet-tarou',
                'password' => 'testgourmettaroutest',
            ]);
            $staffLogin = new staffLogin();
            $result = $staffLogin->login($loginRequest, $rememberToken);

            // middlewareを呼び出し
            $request->headers->set('Authorization', 'Bearer ' . $rememberToken);    // token設定
            $response = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            // エラーレスポンスが返却されないこと
            $this->assertNull($response);

            // ログアウトしておく（ログアウトしておかないと、ログイン情報が残り他のテストに影響が出る可能性がある）
            $staffLogin->logout($rememberToken);
        }
    }

    private function _createStaff()
    {
        $staff = new Staff();
        $staff->name = 'testグルメ太郎';
        $staff->username = 'test-goumet-tarou';
        $staff->password = bcrypt('testgourmettaroutest');
        $staff->staff_authority_id = '1';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->remember_token = '';
        $staff->save();
    }
}
