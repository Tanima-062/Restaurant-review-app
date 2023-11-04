<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\Staff;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedirectIfAuthenticatedTest extends TestCase
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
        $middleware = new RedirectIfAuthenticated();

        // 認証されている場合(リダイレクトで指定先に飛ぶ)
        {
            // テストアカウント追加し、認証する
            $this->_createStaff();
            Auth::attempt([
                'username' => 'test-goumet-tarou',
                'password' => 'testgourmettaroutest',
            ]);
            $request = auth();

            // middlewareを呼び出し
            $originalResponse = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            $response = $this->createTestResponse($originalResponse);
            // エラーレスポンスが返却されること
            $this->assertNotNull($response);
            // ステータスコードが302であること
            $response->assertStatus(302);
            // リダイレクト先が正しいこと
            $response->assertRedirect(RouteServiceProvider::HOME);

            // ログアウトする
            Auth::logout();
        }

        // 認証されていない場合
        {
            // middlewareを呼び出し
            $response = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            // エラーレスポンスが返却されないこと
            $this->assertNull($response);
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
        $staff->remember_token = 'testgourmetstafflogin';
        $staff->save();
    }
}
