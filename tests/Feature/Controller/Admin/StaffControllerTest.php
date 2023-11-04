<?php

namespace Tests\Feature\Controller\Admin;

use App\Http\Requests\Admin\StaffDetailRequest;
use App\Http\Requests\Admin\StaffPasswordRequest;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\SettlementCompany;
use App\Models\Staff;
use App\Models\StaffAuthority;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class StaffControllerTest extends TestCase
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

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.index');       // 指定bladeを確認
        $response->assertViewHasAll([
            'staffs',
            'staffAuthorities',
            'staffAuthorityCount',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staffAuthorityCount', 6);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $store = $this->_createStore($settlementCompany->id);
        $this->loginWithClientAdministrator($store->id, $settlementCompany->id);         // クライアント管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.index');       // 指定bladeを確認
        $response->assertViewHasAll([
            'staffs',
            'staffAuthorities',
            'staffAuthorityCount',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staffAuthorityCount', 6);

        $this->logout();
    }

    public function testEditFormWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditForm($store, $settlementCompanyId, $staff);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit');       // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'staffAuthorities',
            'settlementCompanies',
            'stores',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staff', $staff);
        $response->assertViewHas('staffAuthorities', StaffAuthority::all());

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callEditForm($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit');       // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'staffAuthorities',
            'settlementCompanies',
            'stores',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staff', $staff);
        $response->assertViewHas('staffAuthorities', null);

        $this->logout();
    }

    public function testEditWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEdit($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('message', 'スタッフ「testグルメ太郎ユーザー更新」を更新しました。');

        // 更新されていることを確認する
        $result = Staff::find($staff->id);
        $this->assertSame('testグルメ太郎ユーザー更新', $result->name);
        $this->assertSame(1, $result->staff_authority_id);
        $this->assertSame(1, $result->store_id);
        $this->assertSame(1, $result->settlement_company_id);

        $this->logout();
    }

    public function testEditWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callEdit($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('message', 'スタッフ「testグルメ太郎ユーザー更新」を更新しました。');

        // nameだけ更新されていることを確認する
        $result = Staff::find($staff->id);
        $this->assertSame('testグルメ太郎ユーザー更新', $result->name);
        $this->assertSame(4, $result->staff_authority_id);                          // 変更されていないこと
        $this->assertSame($store->id, $result->store_id);                           // 変更されていないこと
        $this->assertSame($settlementCompanyId, $result->settlement_company_id);    // 変更されていないこと

        $this->logout();
    }

    public function testEditThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // StaffDetailRequestのinput（'name')呼び出しで例外発生させるようにする
        $staffDetailRequest = \Mockery::mock(StaffDetailRequest::class)->makePartial();
        $staffDetailRequest->shouldReceive('input')->once()->with('name')->andThrow(new \Exception());
        $staffDetailRequest->shouldReceive('input')->andReturn('admin/staff/');                                 // input('name')以外の呼び出しの時は固定文字列を渡しておく
        $staffDetailRequest->shouldReceive('all')->andReturn(['redirect_to' => 'admin/staff/', 'name' => '']);
        $this->app->instance(StaffDetailRequest::class, $staffDetailRequest);

        $response = $this->_callEdit($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('custom_error', 'スタッフ「」を更新できませんでした。');  // モックでスタッフ名を渡せていないため空になっている

        // 更新されていないことを確認する
        $result = Staff::find($staff->id);
        $this->assertSame('testグルメ太郎ユーザー', $result->name);
        $this->assertSame(4, $result->staff_authority_id);
        $this->assertSame($store->id, $result->store_id);
        $this->assertSame($settlementCompanyId, $result->settlement_company_id);

        $this->logout();
    }

    public function testAddFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.add');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staffAuthorities',
            'settlementCompanies',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staffAuthorities', StaffAuthority::all());

        $this->logout();
    }

    public function testAddFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callAddForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.add');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staffAuthorities',
            'settlementCompanies',
        ]);                                                 // bladeに渡している変数を確認
        $response->assertViewHas('staffAuthorities', null);

        $this->logout();
    }

    public function testAddWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callAdd($storeId, $settlementCompanyId);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('message', 'スタッフ「testグルメ太郎ユーザー」を作成しました');


        // 登録されていることを確認する
        $result = Staff::where('name', 'testグルメ太郎ユーザー')->get();
        $this->assertCount(1, $result);
        $this->assertSame('testgoumet-tarou-staff', $result[0]['username']);
        $this->assertSame(4, $result[0]['staff_authority_id']);
        $this->assertSame($settlementCompanyId, $result[0]['settlement_company_id']);
        $this->assertSame($storeId, $result[0]['store_id']);

        $this->logout();
    }

    public function testAddWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientAdministrator($storeId, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callAdd($storeId, $settlementCompanyId);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('message', 'スタッフ「testグルメ太郎ユーザー」を作成しました');

        // 登録されていることを確認する
        $result = Staff::where('name', 'testグルメ太郎ユーザー')->get();
        $this->assertCount(1, $result);
        $this->assertSame('testgoumet-tarou-staff', $result[0]['username']);
        $this->assertSame(4, $result[0]['staff_authority_id']);
        $this->assertSame($settlementCompanyId, $result[0]['settlement_company_id']);
        $this->assertSame($storeId, $result[0]['store_id']);

        $this->logout();
    }

    public function testAddThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // StaffDetailRequestのinput（'published')呼び出しで例外発生させるようにする
        {
            $StaffRequest = \Mockery::mock(StaffRequest::class)->makePartial();
            $StaffRequest->shouldReceive('validated')->andReturn([
                'name' => 'testグルメ太郎ユーザー',
                'val-password2' => 'Testgourmettaroutest1234',
                'val-confirm-password2' => 'Testgourmettaroutest1234',
                'username' => 'testgoumet-tarou-staff',
                'staff_authority_id' => 4,
                'settlement_company_id' => $settlementCompanyId,
                'store_id' => $storeId
            ]);
            $StaffRequest->shouldReceive('input')->once()->with('published')->andThrow(new \Exception());
            $StaffRequest->shouldReceive('input')->andReturn('');
            $StaffRequest->shouldReceive('all')->andReturn('');
            $this->app->instance(StaffRequest::class, $StaffRequest);
        }

        $response = $this->_callAdd($storeId, $settlementCompanyId);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('custom_error', 'スタッフ「」を作成できませんでした');  // モックでスタッフ名を渡せていないため空になっている

        // 登録されていないことを確認する
        $this->assertFalse(Staff::where('name', 'testグルメ太郎ユーザー')->exists());

        $this->logout();
    }

    public function testEditPasswordWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 自身のパスワード変更する
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 登録されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        // 他のユーザーのパスワード変更する
        {
            $staff = null;
            $response = $this->_callEditPassword(1, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertSame($staff->password_modified, $result->password_modified);     // 変更前の値と同じこと（=変更されていない）
        }

        $this->logout();
    }

    public function testEditPasswordWithInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // 自身のパスワード変更する(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        $this->logout();
    }

    public function testEditPasswordWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientAdministrator($storeId, $settlementCompanyId);         // クライアント管理者としてログイン

        // 自身のパスワード変更する(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        $this->logout();
    }

    public function testEditPasswordWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // 自身のパスワード変更する(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        $this->logout();
    }

    public function testEditPasswordWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // 自身のパスワード変更する(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        $this->logout();
    }

    public function testEditPasswordWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithSettlementAdministrator($settlementCompanyId);    // 精算管理会社としてログイン

        // 自身のパスワード変更する(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $staff = $this->getLoginUserInfo();
            $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
            $response->assertStatus(302);                  // リダイレクト
            $response->assertRedirect('/admin/staff');     // リダイレクト先
            $response->assertSessionHas('message', 'パスワードを更新しました。');

            // 更新されていることを確認する
            $result = Staff::find($staff->id);
            $this->assertNotSame($staff->password, $result->password);                    // 変更前の値と異なること（=変更されている）
            $this->assertNotSame($staff->password_modified, $result->password_modified);  // 変更前の値と異なること（=変更されている）
        }

        $this->logout();
    }

    public function testEditPasswordThrowable()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // StaffDetailRequestのvalidated()呼び出しで例外発生させるようにする
        $staffPasswordRequest = \Mockery::mock(StaffPasswordRequest::class)->makePartial();
        $staffPasswordRequest->shouldReceive('validated')->andThrow(new \Exception());
        $staffPasswordRequest->shouldReceive('input')->andReturn('/admin/staff');
        $staffPasswordRequest->shouldReceive('all')->andReturn('/admin/staff');
        $this->app->instance(StaffPasswordRequest::class, $staffPasswordRequest);

        $staff = $this->getLoginUserInfo();
        $response = $this->_callEditPassword($staff->staff_authority_id, $storeId, $settlementCompanyId, $staff);
        $response->assertStatus(302);                  // リダイレクト
        $response->assertRedirect('/admin/staff');     // リダイレクト先
        $response->assertSessionHas('custom_error', 'パスワードを更新できませんでした。');

        // 更新されていないことを確認する
        $result = Staff::find($staff->id);
        $this->assertSame($staff->password, $result->password);
        $this->assertSame($staff->password_modified, $result->password_modified);

        $this->logout();
    }

    public function testEditPasswordFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientAdministrator($storeId, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFormWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFormWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $this->loginWithSettlementAdministrator($settlementCompanyId);    // 精算管理会社としてログイン

        $response = $this->_callEditPasswordForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', false);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientAdministrator($storeId, $settlementCompanyId);         // クライアント管理者としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $storeId = $store->id;
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithOutHouseGeneral()
    {
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testEditPasswordFirstLoginFormWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $this->loginWithSettlementAdministrator($settlementCompanyId);    // 精算管理会社としてログイン

        $response = $this->_callEditPasswordFirstLoginForm();
        $response->assertStatus(200);
        $response->assertViewIs('admin.Staff.edit_password');         // 指定bladeを確認
        $response->assertViewHasAll([
            'staff',
            'firstLogin',
        ]);                                                           // bladeに渡している変数を確認
        $response->assertViewHas('staff', $this->getLoginUserInfo());
        $response->assertViewHas('firstLogin', true);

        $this->logout();
    }

    public function testStoreListWithInHouseAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithInHouseAdministrator();             // 社内管理者としてログイン

        // 指定した精算会社に紐づく店舗情報のみ取得できること
        {
            $response = $this->_callStoreList($settlementCompanyId);
            $response->assertStatus(200)->assertJson(['ret' => [$store->toArray()]]);   // 指定した精算会社に紐づく店舗情報のみ取得できること
        }

        // 精算会社ID=0を渡した場合は空配列が返ること
        {
            $response = $this->_callStoreList(0);
            $response->assertStatus(200)->assertJson(['ret' => []]);
        }

        $this->logout();
    }

    public function testStoreListWithClientAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientAdministrator($store->id, $settlementCompanyId);         // クライアント管理者としてログイン

        // 指定した精算会社に紐づく店舗情報のみ取得できること
        {
            $settlementCompany2 = $this->_createSettlementCompany();
            $settlementCompanyId2 = $settlementCompany2->id;
            $store2 = $this->_createStore($settlementCompanyId2);
            $response = $this->_callStoreList($settlementCompanyId2);
            $response->assertStatus(200)->assertJson(['ret' => [$store2->toArray()]]);
        }

        // 精算会社ID=0を渡した場合は空配列が返ること
        {
            $response = $this->_callStoreList(0);
            $response->assertStatus(200)->assertJson(['ret' => []]);
        }

        $this->logout();
    }

    public function testStaffControllerInHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithInHouseGeneral();   // 社内一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store->id, $settlementCompanyId);
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store->id, $settlementCompanyId);
        $response->assertStatus(404);

        // target method storeList
        $response = $this->_callStoreList(0);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStaffControllerWithClientGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithClientGeneral($store->id, $settlementCompanyId);      // クライアント一般としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store->id, $settlementCompanyId);
        $response->assertStatus(404);

        // target method storeList
        $response = $this->_callStoreList(0);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStaffControllerWithOutHouseGeneral()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store->id, $settlementCompanyId);
        $response->assertStatus(404);

        // target method storeList
        $response = $this->_callStoreList(0);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testStaffControllerWithSettlementAdministrator()
    {
        $settlementCompany = $this->_createSettlementCompany();
        $settlementCompanyId = $settlementCompany->id;
        $store = $this->_createStore($settlementCompanyId);
        $this->loginWithSettlementAdministrator($settlementCompanyId);    // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store->id, $settlementCompanyId, $staff);
        $response->assertStatus(404);

        // target method addForm
        $response = $this->_callAddForm();
        $response->assertStatus(404);

        // target method add
        $response = $this->_callAdd($store->id, $settlementCompanyId);
        $response->assertStatus(404);

        // target method storeList
        $response = $this->_callStoreList(0);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createSettlementCompany()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->published = 1;
        $settlementCompany->save();

        return $settlementCompany;
    }

    private function _createStaff($staffAuthorityId = 1, $storeId = 0, $settlementCompanyId = 0)
    {
        $staff = new Staff();
        $staff->name = 'testグルメ太郎ユーザー';
        $staff->username = 'test-goumet-tarou-staff';
        $staff->password = bcrypt('testgourmettaroutest');
        $staff->staff_authority_id = $staffAuthorityId;
        $staff->store_id = $storeId;
        $staff->settlement_company_id = $settlementCompanyId;
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->remember_token = 'testgourmetstafflogin';
        $staff->last_login_at = '2022-10-01 10:00:00';
        $staff->save();
        return $staff;
    }

    private function _createStore($settlementCompanyId)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->published = 1;
        $store->settlement_company_id = $settlementCompanyId;
        $store->save();

        return $store;
    }

    private function _callIndex()
    {
        return $this->get('/admin/staff');
    }

    private function _callEditForm($storeId, $settlementCompanyId, &$staff = null)
    {
        if (is_null($staff)) {
            $staff = $this->_createStaff(4, $storeId, $settlementCompanyId);
        }
        return $this->get('/admin/staff/edit/' . $staff->id);
    }

    private function _callEdit($storeId, $settlementCompanyId, &$staff = null)
    {
        if (is_null($staff)) {
            $staff = $this->_createStaff(4, $storeId, $settlementCompanyId);
        }
        return $this->post('/admin/staff/edit/' . $staff->id, [
            'store_id' => 1,
            'staff_authority_id' => 1,
            'settlement_company_id' => 1,
            'name' => 'testグルメ太郎ユーザー更新',
            'redirect_to' => 'admin/staff/',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callAddForm()
    {
        return $this->get('/admin/staff/add');
    }

    private function _callAdd($storeId, $settlementCompanyId)
    {
        return $this->post('/admin/staff/add', [
            'name' => 'testグルメ太郎ユーザー',
            'val-password2' => 'Testgourmettaroutest1234',
            'val-confirm-password2' => 'Testgourmettaroutest1234',
            'username' => 'testgoumet-tarou-staff',
            'staff_authority_id' => 4,
            'settlement_company_id' => $settlementCompanyId,
            'store_id' => $storeId,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditPassword($staffAuthorityId, $storeId, $settlementCompanyId, &$staff = null)
    {
        if (is_null($staff)) {
            $staff = $this->_createStaff($staffAuthorityId, $storeId, $settlementCompanyId);
        }
        return $this->post('/admin/staff/edit_password/' . $staff->id,  [
            'val-password2' => 'Testgourmettaroutest1234',
            'val-confirm-password2' => 'Testgourmettaroutest1234',
            'redirect_to' => 'admin/staff',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callEditPasswordForm()
    {
        return $this->get('/admin/staff/edit_password');
    }

    private function _callEditPasswordFirstLoginForm()
    {
        return $this->get('/admin/staff/edit_password_first_login');
    }

    private function _callStoreList($settlementCompanyId)
    {
        return $this->get('/admin/staff/storeList/' . $settlementCompanyId);
    }
}
