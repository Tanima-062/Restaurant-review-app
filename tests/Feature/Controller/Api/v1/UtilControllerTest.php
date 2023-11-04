<?php

namespace Tests\Feature\Controller\Api\v1;

use App\Models\CmThApplication;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UtilControllerTest extends TestCase
{
    private $cmThApplication;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        // 返金用データ
        $reservation = new Reservation();
        $reservation->save();

        $cmThApplication = new CmThApplication();
        $cmThApplication->lang_id = 1;
        $cmThApplication->save();
        $this->cmThApplication = $cmThApplication;

        $paymentToken = new PaymentToken();
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
        $paymentToken->token =  'testtokentesttoken';
        $paymentToken->is_invalid = 1;
        $paymentToken->call_back_values = '{"token":"testtokentesttoken","orderCode":"testcode"}';
        $paymentToken->save();

        $refund = new Refund();
        $refund->reservation_id = $reservation->id;
        $refund->price = 1500;
        $refund->save();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveOrderCode()
    {
        $response = $this->post('/gourmet/v1/ja/payment/authCallback', [
            'refundPrice' => 1500,
            'orderCode' => 'testcode',
            'details' => [
                'cmApplicationId' => $this->cmThApplication->cm_application_id,
            ]
        ]);
        $response->assertStatus(200)->assertJson([
            'token' => 'testtokentesttoken',
            'code' => 'success',
            'message' => '成功',
        ]);
    }
}
