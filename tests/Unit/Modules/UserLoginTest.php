<?php

namespace Tests\Unit\Modules;

use App\Models\CmTmUser;
use App\Models\MailDBQueue;
use App\Modules\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    private $userLogin;
    private $testUser;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->userLogin = new UserLogin();
        $this->testUser = $this->_createCmTmUser();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSetMode()
    {
        // 初期はfalseのはず
        $this->assertFalse($this->userLogin::$testMode);

        // モードをセットして、書き変わっていることを確認
        $this->userLogin->setMode('testtest');
        $this->assertSame('testtest', $this->userLogin::$testMode);
    }

    public function testIsMember()
    {
        // 無効なメールアドレス
        $result = $this->userLogin->isMember('testtest@testtest.co.jp');
        $this->assertFalse($result);

        // 登録済みの有効なメールアドレス
        $result = $this->userLogin->isMember('gourmet-test1@adventure-inc.co.jp');
        $this->assertTrue($result);
    }

    public function testLogin()
    {
        // ログイン（成功）
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $result = $this->userLogin->login($request);
        $this->assertSame($this->testUser->user_id, $result['userId']);

        // ログイン（失敗）
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest12345678',
        ]);
        $result = $this->userLogin->login($request);
        $this->assertFalse($result);
    }

    public function testIsLogin()
    {
        // 未ログイン
        $this->assertFalse($this->userLogin->isLogin());

        // ログイン済み（ログインしてから関数呼び出し）
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $result = $this->userLogin->login($request);
        $this->assertTrue($this->userLogin->isLogin());
    }

    public function testSkylogout()
    {
        $response = $this->userLogin->skylogout();
        $this->assertSame(url('/', null, true) . '/user/logout.php', $response->getTargetUrl());  // 飛び先が同じか確認
    }

    public function testLogout()
    {
        // ログアウト失敗（＝ログインしていない状態)
        $this->assertFalse($this->userLogin->logout());

        // ログアウト成功(ログインしてから関数呼び出し）
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $result = $this->userLogin->login($request);
        $this->assertTrue($this->userLogin->logout());
    }

    public function testGetLoginUser()
    {
        // ユーザー情報取得できない（＝未ログイン）
        $result = $this->userLogin->getLoginUser();
        $this->assertIsArray($result);
        $this->assertCount(0, $result);

        // ユーザー情報取得できる(ログインしてから関数呼び出し）
        // 性別:女性
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $login = $this->userLogin->login($request);
        $result = $this->userLogin->getLoginUser();
        $this->assertIsArray($result);
        $this->assertSame($this->testUser->user_id, $result['userId']);
        $this->assertSame('WOMAN', $result['gender']);
        $this->userLogin->logout();

        // ユーザー情報取得できる(ログインしてから関数呼び出し）
        // 性別:男性
        $user = $this->_createCmTmUser(1, 'gourmet-test2@adventure-inc.co.jp');
        $request2 = new Request();
        $request2->merge([
            'loginId' => 'gourmet-test2@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $login = $this->userLogin->login($request2);
        $result2 = $this->userLogin->getLoginUser($user->user_id);
        $this->assertIsArray($result2);
        $this->assertSame($user->user_id, $result2['userId']);
        $this->assertSame('MAN', $result2['gender']);
    }

    public function testGetUnreadMail()
    {
        // テストデータ作成（２件）
        $this->_createMailDbQueue();
        $this->_createMailDbQueue();

        // （成功）ログインして、関数を呼び出し
        $request = new Request();
        $request->merge([
            'loginId' => 'gourmet-test1@adventure-inc.co.jp',
            'password' =>  'gourmettest123',
        ]);
        $login = $this->userLogin->login($request);
        $this->assertSame(2, $this->userLogin->getUnreadMail($this->testUser->user_id));

        // （成功） 定数変更（private method getSessionValueの確認のため）
        Config::set('session.direct_access', false);
        $this->assertSame(2, $this->userLogin->getUnreadMail($this->testUser->user_id));
        Config::set('session.direct_access', true);
    }

    private function _createCmTmUser($genderId = '2', $email = 'gourmet-test1@adventure-inc.co.jp')
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = $email;
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->gender_id = $genderId;
        $cmTmUser->save();
        return $cmTmUser;
    }

    private function _createMailDbQueue()
    {
        $mailDbQueue = new MailDBQueue();
        $mailDbQueue->user_id = $this->testUser->user_id;
        $mailDbQueue->site_cd = 'skyticket.com';
        $mailDbQueue->non_show_user_flg = 0;
        $mailDbQueue->save();
    }
}
