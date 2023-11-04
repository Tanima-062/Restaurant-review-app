<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\UserAuth;
use App\Models\CmTmUser;
use App\Modules\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserAuthTest extends TestCase
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
        $middleware = new UserAuth();

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
            $this->_createCmTmUser();
            $loginRequest = new Request();
            $loginRequest->merge([
                'loginId' => 'gourmet-test1@adventure-inc.co.jp',
                'password' => 'gourmettest123',
            ]);
            $userLogin = new userLogin();
            $result = $userLogin->login($loginRequest);

            // middlewareを呼び出し
            $response = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            // エラーレスポンスが返却されないこと
            $this->assertNull($response);

            // 念の為、ログアウトしておく
            $userLogin->logout();
        }
    }

    private function _createCmTmUser()
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = 'gourmet-test1@adventure-inc.co.jp';
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->save();
    }
}
