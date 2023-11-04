<?php

namespace Tests\Unit\Models;

use App\Models\Notice;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    private $notice;
    private $testStaffId;
    private $testNoticeId;
    private $testNoticeId2;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->notice = new Notice();

        $this->_createNotice();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testOnUpdatingHandler()
    {
        $notice = $this->notice::find($this->testNoticeId);
        $notice->title = 'テストタイトル2';
        $result = $notice->save();
        $this->assertTrue($result);
    }

    public function testCreatedBy()
    {
        $testStaffId = $this->testStaffId;
        $result = $this->notice::whereHas('createdBy', function ($query) use ($testStaffId) {
            $query->where('id', $testStaffId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());
        $this->assertSame($this->testNoticeId, $result[0]['id']);
        $this->assertSame($this->testNoticeId2, $result[1]['id']);
    }

    public function testUpdatedBy()
    {
        $testStaffId = $this->testStaffId;
        $result = $this->notice::whereHas('updatedBy', function ($query) use ($testStaffId) {
            $query->where('id', $testStaffId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());
        $this->assertSame($this->testNoticeId, $result[0]['id']);
        $this->assertSame($this->testNoticeId2, $result[1]['id']);
    }

    public function testScopeAdminSearchFilter()
    {
        $valid = [
            'datetime_from' => '2022-01-01 10:00:00',
            'datetime_to' => '2999-12-31 18:00:00',
            'updated_by' => $this->testStaffId,
        ];
        $result = $this->notice::AdminSearchFilter($valid)->get();
        $this->assertIsObject($result);
        $this->assertSame(2, $result->count());
        $this->assertSame($this->testNoticeId, $result[0]['id']);
        $this->assertSame($this->testNoticeId2, $result[1]['id']);
    }

    public function testScopeAdminNews()
    {
        $valid = [
            'updated_by' => $this->testStaffId,
        ];
        $result = $this->notice::AdminSearchFilter($valid)->AdminNews()->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testNoticeId, $result[0]['id']);
    }

    public function testScopeWebsiteNews()
    {
        $valid = [
            'updated_by' => $this->testStaffId,
        ];
        $result = $this->notice::AdminSearchFilter($valid)->WebsiteNews()->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testNoticeId, $result[0]['id']);
    }

    public function testScopeWebsiteBackNumber()
    {
        $valid = [
            'updated_by' => $this->testStaffId,
        ];
        $result = $this->notice::AdminSearchFilter($valid)->WebsiteBackNumber()->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testNoticeId2, $result[0]['id']);
    }

    public function testGetNotice()
    {
        $result = $this->notice->getNotice('TO');
        $this->assertIsArray($result);
        $this->assertTrue((count($result) > 0));    // 1件以上取得できている
    }

    private function _createNotice()
    {
        $staff = new Staff();
        $staff->settlement_company_id = 0;
        $staff->store_id = 0;
        $staff->name = 'テストユーザー';
        $staff->username = 'testuser';
        $staff->password = bcrypt('testpassword');
        $staff->staff_authority_id = 1;
        $staff->published = 1;
        $staff->created_at = '2022-10-01 10:00:00';
        $staff->save();
        $this->testStaffId = $staff->id;

        // ユーザ認証して、データ取得可能か確認
        Auth::attempt([
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $notice = new Notice();
        $notice->app_cd = 'TO';
        $notice->title = 'テストタイトル';
        $notice->message = 'テストメッセージ';
        $notice->datetime_from = '2022-10-01 10:00:00';
        $notice->datetime_to = '2999-12-31 18:00:00';
        $notice->ui_website_flg = 1;
        $notice->ui_admin_flg = 1;
        $notice->published = 1;
        $notice->published_at = '2022-10-01 18:00:00';
        $notice->created_by = $this->testStaffId;
        $notice->created_at = '2022-10-01 18:00:00';
        $notice->updated_by = $this->testStaffId;
        $notice->updated_at = '2022-10-01 18:00:00';
        $notice->save();
        $this->testNoticeId = $notice->id;

        $notice = new Notice(); // バックナンバー用
        $notice->app_cd = 'TO';
        $notice->title = 'テストタイトル2';
        $notice->message = 'テストメッセージ2';
        $notice->datetime_from = '2022-10-01 10:00:00';
        $notice->datetime_to = '2022-10-30 18:00:00';
        $notice->ui_website_flg = 1;
        $notice->ui_admin_flg = 1;
        $notice->published = 1;
        $notice->published_at = '2022-10-01 18:00:00';
        $notice->created_at = '2022-10-01 18:00:00';
        $notice->updated_at = '2022-10-01 18:00:00';
        $notice->save();
        $this->testNoticeId2 = $notice->id;
    }
}
