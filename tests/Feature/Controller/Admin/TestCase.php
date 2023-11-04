<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase as Base;

/**
 * feature/Controller/admin以下で必要な処理を共通関数化するためTestBase継承classを用意
 */
abstract class TestCase extends Base
{
    private $user = null;

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

    /**
     * ログイン用スタッフデータを作成し、ログインを行う
     *
     * @param integer $staffAuthorityId
     * @return void
     */
    public function login($staffAuthorityId = 1, $storeId = 0, $settlementCompanyId = 0)
    {
        $this->user = $this->_createStaff($staffAuthorityId, $storeId, $settlementCompanyId);
        Auth::attempt([
            'username' => 'test-goumet-tarou',
            'password' => 'testgourmettaroutest',
        ]);
    }

    /**
     * 社内管理者としてログイン
     *
     * @return void
     */
    public function loginWithInHouseAdministrator()
    {
        $this->login();
    }

    /**
     * 社内一般としてログイン
     *
     * @return void
     */
    public function loginWithInHouseGeneral()
    {
        $this->login(2);
    }

    /**
     * クライアント管理者としてログイン
     *
     * @return void
     */
    public function loginWithClientAdministrator($storeId = 0, $settlementCompanyId = 0)
    {
        $this->login(3, $storeId, $settlementCompanyId);
    }

    /**
     * クライアント一般としてログイン
     *
     * @return void
     */
    public function loginWithClientGeneral($storeId = 0, $settlementCompanyId = 0)
    {
        $this->login(4, $storeId, $settlementCompanyId);
    }

    /**
     * 社外一般権限としてログイン
     *
     * @return void
     */
    public function loginWithOutHouseGeneral()
    {
        $this->login(5);
    }

    /**
     * 精算管理会社としてログイン
     *
     * @return void
     */
    public function loginWithSettlementAdministrator($settlementCompanyId = 0)
    {
        $this->login(6, 0, $settlementCompanyId);
    }

    /**
     * ログアウト
     *
     * @return void
     */
    public function logout()
    {
        Auth::logout();
        $this->user = null;
    }

    /**
     * セッショントークンを発行して返す
     *
     * @return void
     */
    public function makeSessionToken()
    {
        session()->regenerateToken();
        return session()->get('_token');
    }

    /**
     * ログインユーザー情報を返す（ログインしていなけば、null)
     *
     * @return Staff
     */
    public function getLoginUserInfo()
    {
        return $this->user;
    }

    /**
     * スタッフデータの作成
     *
     * @param integer $staffAuthorityId
     * @return void
     */
    private function _createStaff($staffAuthorityId = 1, $storeId = 0, $settlementCompanyId = 0)
    {
        $staff = new Staff();
        $staff->name = 'testグルメ太郎';
        $staff->username = 'test-goumet-tarou';
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
}
