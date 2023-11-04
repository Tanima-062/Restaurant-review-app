<?php

namespace Tests\Unit\Models;

use App\Models\MessageBoard;
use App\Models\Reservation;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MessageBoardTest extends TestCase
{
    private $messageBoard;
    private $testReservationId;
    private $testMessageBoard;
    private $testStaffId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->messageBoard = new MessageBoard();

        $this->_createMessageBoard();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testStaff()
    {
        $testStaffId = $this->testStaffId;
        $result = $this->messageBoard::whereHas('staff', function ($query) use ($testStaffId) {
            $query->where('id', $testStaffId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMessageBoard, $result[0]['id']);
    }

    public function testReservation()
    {
        $testReservationId = $this->testReservationId;
        $result = $this->messageBoard::whereHas('reservation', function ($query) use ($testReservationId) {
            $query->where('id', $testReservationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMessageBoard, $result[0]['id']);
    }

    public function testScopeAdminSearchFilter()
    {
        $result = $this->messageBoard::AdminSearchFilter($this->testReservationId)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMessageBoard, $result[0]['id']);
    }

    public function testScopeOldestFirst()
    {
        $result = $this->messageBoard::OldestFirst()->where('id', $this->testMessageBoard)->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testMessageBoard, $result[0]['id']);
    }

    private function _createMessageBoard()
    {
        $reservation = new Reservation();
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $staff = new Staff();
        $staff->save();
        $this->testStaffId = $staff->id;

        $messageBoard = new MessageBoard();
        $messageBoard->staff_id = $this->testStaffId;
        $messageBoard->reservation_id = $this->testReservationId;
        $messageBoard->save();
        $this->testMessageBoard = $messageBoard->id;
    }
}
