<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AdminAuthorityPage;
use App\Models\Staff;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Factory;
use Tests\TestCase;

class AdminAuthorityPageTest extends TestCase
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
        // テストユーザー登録
        $staff = $this->_createStaff();

        // middleware呼出準備
        $viewFactory = app(Factory::class);
        $middleware = new AdminAuthorityPage($viewFactory);

        // 正常（管理画面で権限のあるページにアクセスしようとする）
        {
            $request = Request::create('admin/menu', Request::METHOD_PATCH, [])
                ->setUserResolver(function () use ($staff) {
                    return $staff;
                });
            // middlewareを呼び出し
            $response = $middleware->handle($request, function () {
                $this->assertTrue(true);
            });
            // エラーレスポンスが返却されないこと
            $this->assertNull($response);
        }

        // エラー（管理画面で権限のないページにアクセスしようとする404エラー）
        {
            $request = Request::create('admin/area', Request::METHOD_PATCH, [])
                ->setUserResolver(function () use ($staff) {
                    return $staff;
                });
            try {
                // middlewareを呼び出し
                $response = $middleware->handle($request, function () {
                    $this->assertTrue(false);   // 無名関数呼出前に例外発生するため、ここは通過しない（通過したらエラーとする）
                });
            } catch (Exception $e) {
                $this->assertTrue(true);
            }
        }

        // エラー（管理画面以外のページにアクセスしようとする404エラー）
        {
            $request = Request::create('menu/index', Request::METHOD_PATCH, [])
                ->setUserResolver(function () use ($staff) {
                    return $staff;
                });
            try {
                // middlewareを呼び出し
                $response = $middleware->handle($request, function () {
                    $this->assertTrue(false);   // 無名関数呼出前に例外発生するため、ここは通過しない（通過したらエラーとする）
                });
                $this->assertTrue(false);
            } catch (Exception $e) {
                $this->assertTrue(true);
            }
        }
    }

    private function _createStaff()
    {
        $staff = new Staff();
        $staff->name = 'testグルメ太郎';
        $staff->username = 'test-goumet-tarou';
        $staff->password = bcrypt('testgourmettaroutest');
        $staff->staff_authority_id = '2';
        $staff->published = '1';
        $staff->password_modified = '0000-00-00 00:00:00';
        $staff->remember_token = 'testgourmetstafflogin';
        $staff->save();

        return $staff;
    }
}
