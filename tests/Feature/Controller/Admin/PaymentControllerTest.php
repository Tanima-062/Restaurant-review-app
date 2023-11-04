<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\CancelDetail;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Menu;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class PaymentControllerTest extends TestCase
{
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

    // 下記関数は、現在使用していないサービス（Econ）への処理のため、テストコードは書かない。
    // index(),statusPayment(),yoshinCancel(),cardCapture()

    public function testDetailWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // 返金情報のステータスがSCHEDULED
        {
            $response = $this->_callDetail($store, $menu, 'SCHEDULED');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        // 返金情報のステータスがREFUNDING
        {
            $response = $this->_callDetail($store, $menu, 'REFUNDING');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        // 返金情報のステータスがREFUNDED
        {
            $response = $this->_callDetail($store, $menu, 'REFUNDED');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        $this->logout();
    }

    public function testDetailWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // 返金情報のステータスがSCHEDULED
        {
            $response = $this->_callDetail($store, $menu, 'SCHEDULED');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        // 返金情報のステータスがREFUNDING
        {
            $response = $this->_callDetail($store, $menu, 'REFUNDING');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        // 返金情報のステータスがREFUNDED
        {
            $response = $this->_callDetail($store, $menu, 'REFUNDED');
            $response->assertStatus(200);                       // アクセス確認
            $response->assertViewIs('admin.Payment.detail');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded',
            ]);               // bladeに渡している変数を確認
        }

        $this->logout();
    }

    public function testCancelDetailAddWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
        ]);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 返金データが登録されていること
        $this->assertTrue(CancelDetail::where('reservation_id', $reservationId)->exists());
        $this->assertTrue(Refund::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testCancelDetailAddWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn(['paidPrice' => 5000]);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 返金データが登録されていること
        $this->assertTrue(CancelDetail::where('reservation_id', $reservationId)->exists());
        $this->assertTrue(Refund::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testCancelDetailAddThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andThrow(new \Exception());
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(200)->assertJson(['ret' => 'failure','message' => '',]);

        // 返金データが登録されていないこと
        $this->assertFalse(CancelDetail::where('reservation_id', $reservationId)->exists());
        $this->assertFalse(Refund::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testExecRefundWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
            'refundPrice' => 5000,
            'refundedPrice' => 0,
        ]);
        $paymentSkyticket->shouldReceive('registerRefundPayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callExecRefund($reservationId);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 関連データが変更されていること
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(1, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('WAIT_REFUND', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
            'refundPrice' => 5000,
            'refundedPrice' => 0,
        ]);
        $paymentSkyticket->shouldReceive('registerRefundPayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callExecRefund($reservationId);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // 関連データが変更されていること
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(1, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('WAIT_REFUND', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundPaymentStatusError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu, true, true, 'AUTH');
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callExecRefund($reservationId);
        $response->assertStatus(200)->assertJson([
            'ret' => 'error',
            'msg' => '入金ステータスを「計上」に変更してから実行してください。',
        ]);

        // 関連データが変更されていないこと
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(0, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('AUTH', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundNotRefund()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu, true, false);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callExecRefund($reservationId);
        $response->assertStatus(200)->assertJson([
            'ret' => 'error',
            'msg' => 'エラーが発生しました',
        ]);

        // 関連データが変更されていないこと
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(0, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('PAYED', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundRefundPriceError2()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
            'refundPrice' => 2500,  // わざと期待値と異なる金額にする
            'refundedPrice' => 0,
        ]);
        $paymentSkyticket->shouldReceive('registerRefundPayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callExecRefund($reservationId);  // 返金額が1円未満
        $response->assertStatus(200)->assertJson([
            'ret' => 'error',
            'msg' => '画面の情報が古い可能性があります。画面を更新後、再度実行してください',
        ]);

        // 関連データが変更されていないこと
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(0, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('PAYED', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundRefundPriceError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu, true, true, 'PAYED', 0);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
            'refundPrice' => 5000,
            'refundedPrice' => 0,
        ]);
        $paymentSkyticket->shouldReceive('registerRefundPayment')->andReturn(true);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callExecRefund($reservationId);  // 返金額が1円未満
        $response->assertStatus(200)->assertJson([
            'ret' => 'error',
            'msg' => '返金額が1円以上でないと返金できません',
        ]);

        // 関連データが変更されていないこと
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(0, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('PAYED', $result->payment_status);

        $this->logout();
    }

    public function testExecRefundRefundError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $reservationId = $cmThApplicationDetail->application_id;

        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // モックを使って決済サービスへの返却値を指定
        $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
        $paymentSkyticket->shouldReceive('getPayment')->andReturn([
            'paidPrice' => 5000,
            'refundPrice' => 5000,
            'refundedPrice' => 0,
        ]);
        $paymentSkyticket->shouldReceive('registerRefundPayment')->andReturn(false);
        $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

        $response = $this->_callExecRefund($reservationId);  // 返金額が1円未満
        $response->assertStatus(200)->assertJson([
            'ret' => 'error',
            'msg' => '返金出来ませんでした',
        ]);

        // 関連データが変更されていないこと
        $result = PaymentToken::where('reservation_id', $reservationId)->first();
        $this->assertSame(0, $result->is_invalid);
        $result = Reservation::find($reservationId);
        $this->assertSame('PAYED', $result->payment_status);
        $this->logout();
    }

    public function testPaymentControllerWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // target method detail
        $response = $this->_callDetail($store, $menu, 'SCHEDULED');
        $response->assertStatus(404);

        // target method cancelDetailAdd
        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(404);

        // target method execRefund
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $response = $this->_callExecRefund($cmThApplicationDetail->application_id);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testPaymentControllerWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // target method detail
        $response = $this->_callDetail($store, $menu, 'SCHEDULED');
        $response->assertStatus(404);

        // target method cancelDetailAdd
        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(404);

        // target method execRefund
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $response = $this->_callExecRefund($cmThApplicationDetail->application_id);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testPaymentControllerWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method detail
        $response = $this->_callDetail($store, $menu, 'SCHEDULED');
        $response->assertStatus(404);

        // target method cancelDetailAdd
        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(404);

        // target method execRefund
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $response = $this->_callExecRefund($cmThApplicationDetail->application_id);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testPaymentControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // target method detail
        $response = $this->_callDetail($store, $menu, 'SCHEDULED');
        $response->assertStatus(404);

        // target method cancelDetailAdd
        $response = $this->_callCancelDetailAdd($store, $menu, $reservationId);
        $response->assertStatus(404);

        // target method execRefund
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $response = $this->_callExecRefund($cmThApplicationDetail->application_id);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->published = $published;
        $store->save();
        return $store;
    }

    private function _createMenu($storeId, $published = 1, $appCd = 'RS')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11111111';
        $menu->free_drinks = 0;
        $menu->published = $published;
        $menu->save();
        return $menu;
    }

    private function _createStoreMenu($published = 0, $appCd = 'RS')
    {
        $store = $this->_createStore($published);
        $menu = $this->_createMenu($store->id, $published, $appCd);
        return [$store, $menu];
    }

    private function _createReservation($store, $menu, $addCancelDetail = true, $addRefund = true, $paymentStatus = 'PAYED', $refundPrice = 5000, $refundStatus = 'SCHEDULED')
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->total = 5000;
        $reservation->reservation_status = 'RESERVE';
        $reservation->is_close = '1';
        $reservation->payment_status = $paymentStatus;
        $reservation->created_at = '2099-10-01 09:00:00';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = $store->name;
        $reservationStore->save();

        $cmThApplication = new CmThApplication();
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        $paymentToken = new PaymentToken();
        $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->is_invalid = 0;
        $paymentToken->save();

        if ($addCancelDetail) {
            $cancelDetail = new CancelDetail();
            $cancelDetail->reservation_id = $reservation->id;
            $cancelDetail->target_id = 1;
            $cancelDetail->account_code = 'MENU';
            $cancelDetail->price = $refundPrice;
            $cancelDetail->count = 1;
            $cancelDetail->save();
        }

        if ($addRefund) {
            $refund = new Refund();
            $refund->reservation_id = $reservation->id;
            $refund->price = $refundPrice;
            $refund->status = $refundStatus;
            $refund->save();
        }

        return $cmThApplicationDetail;
    }

    private function _callDetail($store, $menu, $refundStatus)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, true, true, 'PAYED', 5000, $refundStatus);
        return $this->get('/admin/newpayment/detail/' . $cmThApplicationDetail->application_id);
    }

    private function _callCancelDetailAdd($store, $menu, &$reservationId)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, false, false);
        $reservationId = $cmThApplicationDetail->application_id;
        return $this->post('/admin/payment/detail/cancel_detail_add', [
            'reservation_id' => $reservationId,
            'account_code' => 'MENU',
            'price' => '3000',
            'count' => '2',
            'remarks' => null,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callExecRefund($reservationId)
    {
        return $this->post('/admin/payment/detail/exec_refund', [
            'id' => $reservationId,
            'pfPaid' => 5000,
            'pfRefund' => 5000,
            'pfRefunded' => 0,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }
}
