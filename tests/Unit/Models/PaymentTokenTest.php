<?php

namespace Tests\Unit\Models;

use App\Models\PaymentToken;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentTokenTest extends TestCase
{
    private $paymentToken;
    private $testPaymentTokenId;
    private $testReservationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->paymentToken = new PaymentToken();

        $this->_createPaymentToken();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testReservation()
    {
        $testReservationId = $this->testReservationId;
        $result = $this->paymentToken::whereHas('reservation', function ($query) use ($testReservationId) {
            $query->where('id', $testReservationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testPaymentTokenId, $result[0]['id']);
    }

    public function testGetOrderCodeFromToken()
    {
        // 該当データなし
        $result = $this->paymentToken->getOrderCodeFromToken('testoken');
        $this->assertNull($result);

        // 該当データあり
        $result = $this->paymentToken->getOrderCodeFromToken('testtokentesttoken');
        $this->assertSame('testcode', $result);
    }

    private function _createPaymentToken()
    {
        $reservation = new Reservation();
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $paymentToken = new PaymentToken();
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->token = 'testtokentesttoken';
        $paymentToken->call_back_values = '{"token":"testtokentesttoken","orderCode":"testcode"}';
        $paymentToken->save();
        $this->testPaymentTokenId = $paymentToken->id;
    }
}
