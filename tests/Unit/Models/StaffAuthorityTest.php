<?php

namespace Tests\Unit\Models;

use App\Models\Staff;
use App\Models\StaffAuthority;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffAuthorityTest extends TestCase
{
    private $staffAuthority;
    private $testStaffId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->staffAuthority = new StaffAuthority();

        $this->_createStaff();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStaffAuthority()
    {
        $testStaffId = $this->testStaffId;
        $result = $this->staffAuthority::whereHas('staff' , function ($query) use ($testStaffId) {
            $query->where('id', $testStaffId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame(1, $result[0]['id']);
    }

    private function _createStaff()
    {
        $staff = new Staff();
        $staff->name = 'グルメ太郎';
        $staff->username = 'goumet-tarou';
        $staff->staff_authority_id = 1;
        $staff->published = '1';
        $staff->password_modified = '2022-10-01 10:00:00';
        $staff->save();
        $this->testStaffId = $staff->id;
    }
}
