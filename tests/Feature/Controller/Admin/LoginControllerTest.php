<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Controllers\Admin\LoginController;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

// procted method をテストするためにオーバーライドクラスを作成
class OverrideLoginController extends LoginController
{
    public $redirectTo = 'admin/reservation';

    public function credentials(Request $request)
    {
        return parent::credentials($request);
    }

    public function authenticated(Request $request, $user)
    {
        return parent::authenticated($request, $user);
    }
}

class LoginControllerTest extends TestCase
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

    public function testIndex()
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);                   // アクセス確認
        $response->assertViewIs('admin.Auth.login');   // 指定bladeを確認
    }

    public function testLogout()
    {
        $this->loginWithInHouseAdministrator();         // 社内管理者としてログイン

        $response = $this->get('/admin/logout');
        $response->assertStatus(302);                 // リダイレクト
        $response->assertRedirect('/admin');          // リダイレクト先
    }

    public function testUsername()
    {
        $loginController = new LoginController();
        $this->assertSame('username', $loginController->username());
    }

    public function testCredentials()
    {
        $this->loginWithInHouseAdministrator();         // 社内管理者としてログイン

        $request = new Request();
        $request->merge([
            'username' => 'testusername',
            'password' => 'testgourmettaroutest',
            'column1' => 'testcolumn1',
        ]);
        $loginController = new OverrideLoginController();
        $result = $loginController->credentials($request);
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertSame('testusername', $result['username']);
        $this->assertArrayHasKey('password', $result);
        $this->assertSame('testgourmettaroutest', $result['password']);
        $this->assertArrayHasKey('published', $result);
        $this->assertSame(1, $result['published']);
        $this->assertArrayNotHasKey('column1', $result);

        $this->logout();
    }

    public function testAuthenticatedWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();     // 社内管理者としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/reservation', $loginController->redirectTo);

        $this->logout();
    }

    public function testAuthenticatedWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();     // 社内一般としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/reservation', $loginController->redirectTo);

        $this->logout();
    }

    public function testAuthenticatedWithClientAdministrator()
    {
        $this->loginWithClientAdministrator();     // クライアント管理者としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/reservation', $loginController->redirectTo);

        $this->logout();
    }

    public function testAuthenticatedWithClientGeneral()
    {
        $this->loginWithClientGeneral();     // クライアント一般としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/reservation', $loginController->redirectTo);

        $this->logout();
    }

    public function testAuthenticatedWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();     // 社外一般権限としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/settlement_company', $loginController->redirectTo);

        $this->logout();
    }

    public function testAuthenticatedWithSettlementAdministrator()
    {
        $this->loginWithSettlementAdministrator();     // 精算管理会社としてログイン

        $request = new Request();
        $staff = Staff::where('username', 'test-goumet-tarou')->first();

        $loginController = new OverrideLoginController();
        $loginController->authenticated($request, $staff);
        $this->assertSame('admin/settlement_confirm', $loginController->redirectTo);

        $this->logout();
    }
}
