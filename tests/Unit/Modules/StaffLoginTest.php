<?php

namespace Tests\Unit\Modules;

use App\Models\Staff;
use App\Modules\StaffLogin;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;

class StaffLoginTest extends TestCase
{
    private $testStaff;
    private $staffLogin;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->staffLogin = new StaffLogin();
        $this->testStaff = $this->_createStaff();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testLogin()
    {
        $request = new Request();

        // トークンの値を元にログインアカウント情報を取得（成功）
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutest',
        ]);
        $rememberToken = 'testgourmetstafflogin';
        $result = $this->staffLogin->login($request, $rememberToken);
        $this->assertSame($this->testStaff->id, $result['id']);
        $this->assertSame($this->testStaff->name, $result['name']);
        $this->assertSame($this->testStaff->username, $result['userName']);

        //　トークンの値を元にログイン情報を取得する（エラー）
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutest111',
        ]);
        $rememberToken = 'testgourmetstafflogin111';
        $result = $this->staffLogin->login($request, $rememberToken);
        $this->assertFalse($result);

        // リクエストの値を元にログインアカウント情報を取得（成功）
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutest',
        ]);
        $rememberToken = '';
        $result = $this->staffLogin->login($request, $rememberToken);
        $this->assertSame($this->testStaff->id, $result['id']);
        $this->assertSame($this->testStaff->name, $result['name']);
        $this->assertSame($this->testStaff->username, $result['userName']);

        // 一致するログインアカウントがない
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutestaaaaaa',
        ]);
        $rememberToken = '';
        $result = $this->staffLogin->login($request, $rememberToken);
        $this->assertFalse($result);
    }

    public function testGetUserInfo()
    {
        // 未ログインでユーザー情報取得（何も取れない）
        $info = null;
        $result = $this->staffLogin->getUserInfo('', $info);
        $this->assertFalse($result);
        $this->assertNull($info);

        // ログインしてからユーザー情報取得
        $request = new Request();
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutest',
        ]);
        $rememberToken = '';
        $user = $this->staffLogin->login($request, $rememberToken);
        $result = $this->staffLogin->getUserInfo($user['rememberToken'], $info);
        $this->assertTrue($result);
        $this->assertSame($this->testStaff->id, $info['id']);
        $this->assertSame($this->testStaff->name, $info['name']);
        $this->assertSame($this->testStaff->username, $info['userName']);
    }

    public function testLogout()
    {
        // ログインしてからログアウト
        $request = new Request();
        $request->merge([
            'userName' => $this->testStaff->username,
            'password' =>  'testgourmettaroutest',
        ]);
        $rememberToken = '';
        $user = $this->staffLogin->login($request, $rememberToken);
        $result = $this->staffLogin->logout($user['rememberToken']);
        $this->assertTrue($result);
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

        return $staff;
    }
}
