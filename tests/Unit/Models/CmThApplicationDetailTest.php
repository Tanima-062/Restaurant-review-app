<?php

namespace Tests\Unit\Models;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmTmUser;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmThApplicationDetailTest extends TestCase
{
    private $cmThApplicationDetail;
    private $testCmApplicationId;
    private $testCmThApplicationDetailId;
    private $testReservationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cmThApplicationDetail = new CmThApplicationDetail();

    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCmThApplication()
    {
        $this->_createCmThApplicationDetail();

        $testCmApplicationId = $this->testCmApplicationId;
        $result = $this->cmThApplicationDetail::whereHas('cmThApplication', function ($query) use ($testCmApplicationId) {
            $query->where('cm_application_id', $testCmApplicationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testCmThApplicationDetailId, $result[0]['id']);
    }

    public function testReservation()
    {
        $this->_createCmThApplicationDetail();

        $testReservationId = $this->testReservationId;
        $result = $this->cmThApplicationDetail::whereHas('reservation', function ($query) use ($testReservationId) {
            $query->where('application_id', $testReservationId);
        })->where('service_cd', 'gm')->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testCmThApplicationDetailId, $result[0]['id']);
    }

    public function testGetApplicationByReservationId()
    {
        $this->_createCmThApplicationDetail();

        $result = $this->cmThApplicationDetail->getApplicationByReservationId($this->testReservationId);
        $this->assertIsObject($result);
        $this->assertSame($this->testCmThApplicationDetailId, $result->id);
    }

    public function testGetApplicationDetailByCmApplicationId()
    {
        $this->_createCmThApplicationDetail();

        $result = $this->cmThApplicationDetail->getApplicationDetailByCmApplicationId($this->testCmApplicationId);
        $this->assertIsObject($result);
        $this->assertSame($this->testCmThApplicationDetailId, $result->id);
    }

    public function testCreateEmptyApplicationDetail()
    {
        $this->_createCmThApplicationDetail(false);

        $this->cmThApplicationDetail->createEmptyApplicationDetail($this->testCmApplicationId);

        $cmThApplicationDetail = $this->cmThApplicationDetail::where('cm_application_id', $this->testCmApplicationId)->get();
        $this->assertIsObject($cmThApplicationDetail);
        $this->assertSame(1, $cmThApplicationDetail->count());
        $this->assertSame('gm', $cmThApplicationDetail[0]['service_cd']);
    }

    private function _createCmThApplicationDetail($addDetail=true)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $userId = CmTmUser::createUserForPayment();
        $CmThApplication = new CmThApplication();
        $CmThApplication->user_id = $userId;
        $CmThApplication->lang_id = 1;
        $CmThApplication->save();
        $this->testCmApplicationId = $CmThApplication->cm_application_id;

        if ($addDetail) {
            $cmThApplicationDetail = new CmThApplicationDetail();
            $cmThApplicationDetail->service_cd = 'gm';
            $cmThApplicationDetail->cm_application_id = $this->testCmApplicationId;
            $cmThApplicationDetail->application_id = $this->testReservationId;
            $cmThApplicationDetail->save();
            $this->testCmThApplicationDetailId = $cmThApplicationDetail->id;
        }
    }
}
