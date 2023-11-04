<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\RedirectIfFirstLogin;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedirectIfFirstLoginTest extends TestCase
{
    private $testStaffId;

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
        $middleware = new RedirectIfFirstLogin();
        $this->_createStaff();

        // 初回ログインの場合
        {
            // ログインする
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
            // // エラーレスポンスが返却されること
            $this->assertNotNull($response);
            // ステータスコードが302であること
            $response->assertStatus(302);
            // リダイレクト先が正しいこと
            $response->assertRedirect('/admin/staff/edit_password_first_login');

            // 念の為ログアウトしておく
            Auth::logout();
        }

        // 初回ログインではない場合
        {
            // アカウントのパスワード変更日を更新し、ログインする
            $this->_updatePasswordModified();
            Auth::attempt([
                'username' => 'test-goumet-tarou',
                'password' => 'testgourmettaroutest',
            ]);
            $request = auth();

            // middlewareを呼び出し
            $response = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            // エラーレスポンスが返却されないこと
            $this->assertNull($response);

            // 念の為ログアウトしておく
            Auth::logout();
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
        $staff->password_modified = '0000-00-00 00:00:00';
        $staff->remember_token = 'testgourmetstafflogin';
        $staff->save();
        $this->testStaffId = $staff->id;
    }

    private function _updatePasswordModified()
    {
        Staff::find($this->testStaffId)->update(['password_modified' => '2022-10-01 00:00:00']);
    }
}
