<?php

namespace Tests\Unit\Models;

use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffTest extends TestCase
{
    private $staff;
    private $testStaffId;
    private $testStoreId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->staff = new Staff();

        $this->_createStaff();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStaffAuthority()
    {
        $result = $this->staff::has('staffAuthority')->where('id', $this->testStaffId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);
    }

    public function testStore()
    {
        $staff = Staff::find($this->testStaffId);
        $staff->store_id = $this->testStoreId;
        $staff->save();

        $result = $this->staff::whereHas('store', function ($query) {
            $query->where('name', 'テスト');
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        $staff = Staff::find($this->testStaffId);
        $staff->store_id = null;
        $staff->save();
    }

    public function testSettlementCompany()
    {
        $staff = Staff::find($this->testStaffId);
        $staff->store_id = $this->testStoreId;
        $staff->save();

        $result = $this->staff::has('settlementCompany')->where('id', $this->testStaffId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        $staff = Staff::find($this->testStaffId);
        $staff->store_id = null;
        $staff->save();
    }

    public function testCheckFirstLogin()
    {
        // 初回ログイン：あり
        $result = $this->staff::find($this->testStaffId);
        $this->assertTrue($result->checkFirstLogin());

        // 初回ログイン：なし
        {
            $staff = Staff::find($this->testStaffId);
            $staff->password_modified = null;
            $staff->save();

            $result = $this->staff::find($this->testStaffId);
            $this->assertFalse($result->checkFirstLogin());
        }
    }

    public function testScopeAdminSearchFilter()
    {
        // ID検索
        $valid = [
            'id' => $this->testStaffId,
        ];
        $result = $this->staff::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // name検索
        $valid = [
            'name' => 'グルメ太郎',
        ];
        $result = $this->staff::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // username検索
        $valid = [
            'username' => 'goumet-tarou',
        ];
        $result = $this->staff::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // ID+staff_authority_id検索
        $valid = [
            'id' => $this->testStaffId,
            'staff_authority_id' => '1',
        ];
        $result = $this->staff::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // ID+店舗ID検索
        {
            $staff = Staff::find($this->testStaffId);
            $staff->store_id = 1;
            $staff->save();

            $valid = [
                'id' => $this->testStaffId,
            ];
            $storeId = 1;
            $result = $this->staff::AdminSearchFilter($valid, $storeId)->get();
            $this->assertIsObject($result);
            $this->assertSame(1, $result->count());
            $this->assertSame($this->testStaffId, $result[0]['id']);
        }
    }

    public function testScopeAdmin()
    {
        // 社内管理者に絞りこむ（対象idは含まれる）
        $result = $this->staff::admin()->where('id', $this->testStaffId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // 社内管理者に絞りこむ（対象idは含まない）
        {
            $staff = Staff::find($this->testStaffId);
            $staff->staff_authority_id = 2;
            $staff->save();

            $result = $this->staff::admin()->where('id', $this->testStaffId)->get();
            $this->assertIsObject($result);
            $this->assertSame(0, $result->count());
        }
    }

    public function testScopePublished()
    {
        // 社内管理者に絞りこむ（対象idは含まれる）
        $result = $this->staff::published()->where('id', $this->testStaffId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testStaffId, $result[0]['id']);

        // 社内管理者に絞りこむ（対象idは含まない）
        {
            $staff = Staff::find($this->testStaffId);
            $staff->published = 0;
            $staff->save();

            $result = $this->staff::published()->where('id', $this->testStaffId)->get();
            $this->assertIsObject($result);
            $this->assertSame(0, $result->count());
        }
    }

    // 認証情報によるデータの出し分確認は別途を行う
    public function testScopeList()
    {
        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'goumet-tarou',
            'password' => 'gourmettaroutest',
        ]);
        $result = $this->staff::list()->get();
        $this->assertIsObject($result);

        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'goumet-hanako',
            'password' => 'gourmethanakotest',
        ]);
        $result = $this->staff::list()->get();
        $this->assertIsObject($result);
    }

    private function _createStaff()
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->save();

        $store = new Store();
        $store->name = 'テスト';
        $store->settlement_company_id = $settlementCompany->id;
        $store->save();
        $this->testStoreId = $store->id;

        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->password = bcrypt('gourmettaroutest');
        $staff->staff_authority_id = '1';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();
        $this->testStaffId = $staff->id;

        $staff = new Staff();
        $staff->name = 'グルメ花子';
        $staff->username = 'goumet-hanako';
        $staff->password = bcrypt('gourmethanakotest');
        $staff->staff_authority_id = '3';
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();
    }
}
