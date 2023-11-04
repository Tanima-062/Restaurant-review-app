<?php

namespace Tests\Unit\Models;

use App\Models\Staff;
use App\Models\StaffAuthorityPage;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffAuthorityPageTest extends TestCase
{
    private $staffAuthorityPage;
    private $testStaffId;
    private $teststaffAuthorityPageId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->staffAuthorityPage = new StaffAuthorityPage();

        $this->_createStaffAuthorityPage();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCheckFirstLogin()
    {
        $staff = Staff::find($this->testStaffId);

        // 有効該当ページあり（有効フラグ）
        $result = $this->staffAuthorityPage->isValidStaff('test', $staff);
        $this->assertTrue($result);

        // 無効：該当ページあり（無効フラグ）
        {
            $staffAuthorityPage = $this->staffAuthorityPage::find($this->teststaffAuthorityPageId);
            $staffAuthorityPage->is_valid = 0;
            $staffAuthorityPage->save();
            $result = $this->staffAuthorityPage->isValidStaff('test', $staff);
            $this->assertNull($result);
        }

        // 無効：該当ページなし
        $result = $this->staffAuthorityPage->isValidStaff('testtest', $staff);
        $this->assertNull($result);
    }

    public function testDisplay()
    {
        $staff = Staff::find($this->testStaffId);

        // 表示
        $result = $this->staffAuthorityPage->display('test', $staff);
        $this->assertSame('block', $result);

         // 非表示
        $result = $this->staffAuthorityPage->display('testtest', $staff);
        $this->assertSame('none', $result);
    }

    private function _createStaffAuthorityPage()
    {
        $staffAuthorityPage = new StaffAuthorityPage();
        $staffAuthorityPage->url = 'test';
        $staffAuthorityPage->is_valid = 1;
        $staffAuthorityPage->staff_authority_id = '1';
        $staffAuthorityPage->save();
        $this->teststaffAuthorityPageId = $staffAuthorityPage->id;

        $staff = new Staff();
        $staff->staff_authority_id = '1';
        $staff->save();
        $this->testStaffId = $staff->id;
    }
}
