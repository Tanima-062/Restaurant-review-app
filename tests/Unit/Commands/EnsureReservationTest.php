<?php

namespace Tests\Unit\Commands;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnsureReservationTest extends TestCase
{
    private $testReservationId;

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

    public function testEnsureReservation()
    {
        $this->_createReservation();

        // バッチ実行
        $this->artisan('ensure:reservation')
        ->expectsOutput('[EnsureReservation] ##### START #####')
        ->expectsOutput(0);

        $result = Reservation::find($this->testReservationId);
        $this->assertSame('ENSURE', $result['reservation_status']);
        $this->assertSame(1, $result['is_close']);
    }

    private function _createReservation()
    {
        // 予約情報を作成
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'RESERVE';
        $reservation->pick_up_datetime = '2022-11-04 09:00:00';
        $reservation->payment_status = 'UNPAID';
        $reservation->is_close = 0;
        $reservation->total = 0;
        $reservation->cancel_datetime = null;
        $reservation->payment_method = null;
        $reservation->save();
        $this->testReservationId = $reservation->id;
    }
}
