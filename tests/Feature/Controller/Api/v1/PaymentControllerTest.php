<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testYoshinCancel()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();
        $this->assertEmpty($reservation->cancel_datetime);                  // 未登録なことを確認しておく
        $this->assertSame('RESERVE', $reservation->reservation_status);     // RESERVEであることを確認しておく
        $this->assertSame('UNPAID', $reservation->payment_status);          // UNPAIDであることを確認しておく

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('cancelPayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->post('/admin/v1/newpayment/yoshin_cancel', [
            'orderCode' => 'testOrderCode',
            'reservationId' => $reservation->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 予約情報が更新されていることを確認する
        $result = Reservation::find($reservation->id);
        $this->assertNotEmpty($result['cancel_datetime']);                  // 登録されていることを確認
        $this->assertSame('CANCEL', $result['reservation_status']);         // CANCELに変わっていることを確認
        $this->assertSame('CANCEL', $result['payment_status']);             // CANCELに変わっていることを確認
    }

    public function testYoshinCancelError()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('cancelPayment')->andReturn(false);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->post('/admin/v1/newpayment/yoshin_cancel', [
            'orderCode' => 'testOrderCode',
            'reservationId' => $reservation->id,
        ]);
        $response->assertStatus(200)->assertJson(['message' => 'キャンセル失敗']);
    }

    public function testCardCapture()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();
        $this->assertEmpty($reservation->store_reception_datetime);         // 未登録なことを確認しておく
        $this->assertSame('RESERVE', $reservation->reservation_status);     // RESERVEであることを確認しておく
        $this->assertSame('UNPAID', $reservation->payment_status);          // UNPAIDであることを確認しておく
        $this->assertSame(0, $reservation->is_close);                       // 0であることを確認しておく

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('settlePayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->post('/admin/v1/newpayment/card_capture', [
            'orderCode' => 'testOrderCode',
            'reservationId' => $reservation->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 予約情報が更新されていることを確認する
        $result = Reservation::find($reservation->id);
        $this->assertNotEmpty($result['store_reception_datetime']);         // 登録されていることを確認
        $this->assertSame('ENSURE', $result['reservation_status']);         // CANCELに変わっていることを確認
        $this->assertSame('PAYED', $result['payment_status']);              // CANCELに変わっていることを確認
        $this->assertSame(1, $result['is_close']);                          // 1に変わっていることを確認
    }

    public function testCardCaptureError()
    {
        // テストデータ用意
        $reservation = $this->_crerateReservation();

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('settlePayment')->andReturn(false);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->post('/admin/v1/newpayment/card_capture', [
            'orderCode' => 'testOrderCode',
            'reservationId' => $reservation->id,
        ]);
        $response->assertStatus(200)->assertJson(['message' => '計上失敗']);
    }

    public function testReviewPayment()
    {
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPaymentList')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        // レストラン用のテストデータ用意
        $reservationRs = $this->_crerateReservation();
        $response = $this->post('/admin/v1/newpayment/review', [
            'reservationId' => $reservationRs->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // テイクアウト用のテストデータ用意
        $reservationTo = $this->_crerateReservation('TO');
        $response = $this->post('/admin/v1/newpayment/review', [
            'reservationId' => $reservationTo->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
    }

    public function testReviewPaymentError()
    {
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPaymentList')->andReturn(false);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        // レストラン用のテストデータ用意
        $reservationRs = $this->_crerateReservation();
        $response = $this->post('/admin/v1/newpayment/review', [
            'reservationId' => $reservationRs->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ng', 'message' => '取得失敗']);

        // テイクアウト用のテストデータ用意
        $reservationTo = $this->_crerateReservation('TO');
        $response = $this->post('/admin/v1/newpayment/review', [
            'reservationId' => $reservationTo->id,
        ]);
        $response->assertStatus(200)->assertJson(['ret' => 'ng', 'message' => '取得失敗']);
    }

    private function _crerateReservation($appCd = 'RS')
    {
        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->reservation_status = 'RESERVE';               // 申込
        $reservation->payment_status = 'UNPAID';                    // 未入金
        $reservation->pick_up_datetime = '2099-10-01 10:00:00';
        $reservation->persons = 2;
        $reservation->total = 2000;
        $reservation->is_close = 0;
        $reservation->save();

        return $reservation;
    }
}
