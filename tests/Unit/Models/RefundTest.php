<?php

namespace Tests\Unit\Models;

use App\Models\Refund;
use App\Models\Reservation;
// use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundTest extends TestCase
{
    private $refund;
    private $testReservationId;
    private $testReservationId2;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->refund = new Refund();

        $this->_createRefund();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testChangeToRefunding()
    {
        // 返金予定から返金要求に変更
        {
            $result = $this->refund->changeToRefunding($this->testReservationId);
            $this->assertTrue($result);

            // REFUNDINGに変わっている確認
            $refund = Refund::where('reservation_id', $this->testReservationId)->first();
            $this->assertSame('REFUNDING', $refund->status);
        }

        // 返金予定から返金要求に変更（失敗）
        // 返却値がfalseになるよういろいろ試してみたが、断念
        // ・Configの値（config('code.refundStatus.refunding.key')）にstatus列の制限を超える値（nullや20桁を超える値）に書き換える（→保存に成功する）
        // ・Mockeryを使ってModel.phpのsave関数結果を操作（→操作できなかった）
        // {
        //     $refund = Refund::where('reservation_id', $this->testReservationId)->first();
        //     $refund->status = NULL;
        //     $refund->status = config('code.refundStatus.scheduled.key');
        //     $refund->save();

        //     Config::set('code.refundStatus.refunding.key', NULL);
        //     $result = $this->refund->changeToRefunding($this->testReservationId);
        //     $this->assertFalse($result);
        // }
    }

    public function testChangeTorefunded()
    {
        // 返金要求から返金済に変更
        {
            $refund = Refund::where('reservation_id', $this->testReservationId)->first();
            $refund->status = 'REFUNDING';
            $refund->save();

            $result = $this->refund->changeTorefunded($this->testReservationId);
            $this->assertTrue($result);

            // REFUNDEDに変わっている確認
            $refund = Refund::where('reservation_id', $this->testReservationId)->first();
            $this->assertSame('REFUNDED', $refund->status);
        }
    }

    public function testCreateRefundOnlyIfEmpty()
    {
        // 正常
        $result = $this->refund->createRefundOnlyIfEmpty($this->testReservationId2, 1000, 'SCHEDULED');
        $this->assertTrue($result);

        // Exception発生
        $result = $this->refund->createRefundOnlyIfEmpty($this->testReservationId2, 1000, NULL);
        $this->assertFalse($result);
    }

    private function _createRefund()
    {
        $reservation = new Reservation();
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $refund = new Refund();
        $refund->reservation_id = $this->testReservationId;
        $refund->status = 'SCHEDULED';                      // config('code.refundStatus.scheduled.key')
        $refund->save();

        $reservation = new Reservation();
        $reservation->save();
        $this->testReservationId2 = $reservation->id;
    }
}
