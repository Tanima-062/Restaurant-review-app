<?php

namespace Tests\Unit\Services;

use App\Models\CancelFee;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmTmUser;
use App\Models\Menu;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Store;
use App\Models\TmpAdminChangeReservation;
use App\Models\TmpRestaurantReservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UtilServiceTest extends TestCase
{
    private $utilService;

    public function setUp(): void
    {
        parent::setUp();
        $this->utilService = $this->app->make('App\Services\UtilService');
        DB::beginTransaction();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testSaveOrderCode()
    {
        list($refundCmThApplication, $cmThApplication, $reservation, $cmThApplication2, $reservation2, $paymentToken2, $paymentDetail2) = $this->_createDateForTestSaveOrderCode();

        // 返金処理データを渡し、成功が返却される
        $result = $this->utilService->saveOrderCode([
            'refundPrice' => 1500,
            'orderCode' => 'testcode',
            'details' => [
                'cmApplicationId' => $refundCmThApplication->cm_application_id
            ]
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('testtokentesttoken', $result['token']);
        $this->assertSame('success', $result['code']);
        $this->assertSame('成功', $result['message']);

        // cmApplicationIdがない返金処理データを渡し、何も返却されない
        $result = $this->utilService->saveOrderCode([
            'refundPrice' => 1500,
            'orderCode' => 'testcode',
        ]);
        $this->assertNull($result);

        // orderCodeがなく、エラーが返却される
        $result = $this->utilService->saveOrderCode([
            ['cmApplicationId' => $cmThApplication->cm_application_id]
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertNUll($result['token']);
        $this->assertSame('E1', $result['code']);
        $this->assertSame('orderCodeが取得できません', $result['message']);

        // cmApplicationIdがなく、エラーが返却される
        $result = $this->utilService->saveOrderCode([
            'orderCode' => 'testcode',
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertNUll($result['token']);
        $this->assertSame('E2', $result['code']);
        $this->assertSame('cmApplicationIdが取得できません', $result['message']);

        // 予約内容変更用データを渡し、成功が返却される
        $result = $this->utilService->saveOrderCode([
            'orderCode' => 'testcode',
            'details' => [
                [],
                ['cmApplicationId' => $cmThApplication->cm_application_id],
            ]
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('testtokentesttoken', $result['token']);
        $this->assertSame('success', $result['code']);
        $this->assertSame('成功', $result['message']);
        // 予約メニュー情報が変わっていることを確認
        $checkRreservationMenu = ReservationMenu::where('reservation_id', $reservation->id)->first();
        $this->assertSame(2, $checkRreservationMenu->count);
        $this->assertSame(1000, $checkRreservationMenu->unit_price);
        $this->assertSame(2000, $checkRreservationMenu->price);
        // tmp_restaurant_reservationsのstatusが変わっていることを確認
        $checkTmpRestaurantReservation = TmpRestaurantReservation::where('session_id', 'testtokentesttoken')->first();
        $this->assertSame('COMPLETE', $checkTmpRestaurantReservation->status);

        // 予約内容変更用データ(管理者操作？）を渡し、成功が返却される
        $result = $this->utilService->saveOrderCode([
            'orderCode' => 'testcode',
            'details' => [
                [],
                ['cmApplicationId' => $cmThApplication2->cm_application_id],
            ]
        ]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('testtokentesttoken2', $result['token']);
        $this->assertSame('success', $result['code']);
        $this->assertSame('成功', $result['message']);
        // 予約メニュー情報が変わっていることを確認
        $checkRreservationMenu = ReservationMenu::where('reservation_id', $reservation2->id)->first();
        $this->assertSame(2, $checkRreservationMenu->count);
        $this->assertSame(1000, $checkRreservationMenu->unit_price);
        $this->assertSame(2000, $checkRreservationMenu->price);
        // tmp_admin_change_reservationsのstatusが変わっていることを確認
        $checkTmpAdminChangeReservation = TmpAdminChangeReservation::where('reservation_id', $reservation2->id)->first();
        $this->assertSame('COMPLETE', $checkTmpAdminChangeReservation->status);
        // PaymentTokenの値が変わっていること
        $checkPaymntToken = PaymentToken::find($paymentToken2->id);
        $this->assertSame(1, $checkPaymntToken->is_invalid);
        // PaymentDetailが追加されていること
        $checkPaymntDetail = PaymentDetail::where('reservation_id', $reservation2->id)->get();
        $this->assertSame(2, $checkPaymntDetail->count());
        $this->assertSame($paymentDetail2->id, $checkPaymntDetail[0]['id']);                 // 予約時のデータ
        $this->assertSame($paymentDetail2->target_id, $checkPaymntDetail[1]['target_id']);   // 同じメニューIDが入っている
        $this->assertSame('MENU', $checkPaymntDetail[1]['account_code']);
        $this->assertSame(1000, $checkPaymntDetail[1]['price']);
        $this->assertSame(1, $checkPaymntDetail[1]['count']);
        $this->assertSame('自動(予約変更)', $checkPaymntDetail[1]['remarks']);
    }

    public function testAdminChangeReservation()
    {
        list($paymentToken, $paymentDetail, $tmpAdminChangeReservation, $errorTmpAdminChangeReservation) = $this->_createDataForTestAdminChangeReservation();
        $reservationId = $paymentToken->reservation_id;

        // 例外エラー用データを渡し、予約情報は変わらないこと
        $this->utilService->adminChangeReservation($paymentToken, $errorTmpAdminChangeReservation);
        $checkReservation = Reservation::find($reservationId);
        $this->assertSame(1, $checkReservation->persons);
        $this->assertSame(1000, $checkReservation->total);
        $this->assertSame('2999-10-01 10:00:00', $checkReservation->pick_up_datetime);
        // tmp_restaurant_reservationsのstatusが変わっていることを確認
        $checkTmpAdminChangeReservation = TmpAdminChangeReservation::find($errorTmpAdminChangeReservation->id);
        $this->assertSame('FAIL_RESERVE', $checkTmpAdminChangeReservation->status);

        //正常データを渡し、予約情報が変わること
        $this->utilService->adminChangeReservation($paymentToken, $tmpAdminChangeReservation);
        // 予約情報が変わっていることを確認
        $checkReservation = Reservation::find($reservationId);
        $this->assertSame(2, $checkReservation->persons);
        $this->assertSame(2000, $checkReservation->total);
        $this->assertSame('2999-10-01 12:00:00', $checkReservation->pick_up_datetime);
        // 予約メニュー情報が変わっていることを確認
        $checkRreservationMenu = ReservationMenu::where('reservation_id', $reservationId)->first();
        $this->assertSame(2, $checkRreservationMenu->count);
        $this->assertSame(1000, $checkRreservationMenu->unit_price);
        $this->assertSame(2000, $checkRreservationMenu->price);
        // tmp_restaurant_reservationsのstatusが変わっていることを確認
        $checkTmpAdminChangeReservation = TmpAdminChangeReservation::find($tmpAdminChangeReservation->id);
        $this->assertSame('COMPLETE', $checkTmpAdminChangeReservation->status);
        // PaymentTokenの値が変わっていること
        $checkPaymntToken = PaymentToken::find($paymentToken->id);
        $this->assertSame(1, $checkPaymntToken->is_invalid);
        // PaymentDetailが追加されていること
        $checkPaymntDetail = PaymentDetail::where('reservation_id', $reservationId)->get();
        $this->assertSame(2, $checkPaymntDetail->count());
        $this->assertSame($paymentDetail->id, $checkPaymntDetail[0]['id']);                 // 予約時のデータ
        $this->assertSame($paymentDetail->target_id, $checkPaymntDetail[1]['target_id']);   // 同じメニューIDが入っている
        $this->assertSame('MENU', $checkPaymntDetail[1]['account_code']);
        $this->assertSame(1000, $checkPaymntDetail[1]['price']);
        $this->assertSame(1, $checkPaymntDetail[1]['count']);
        $this->assertSame('自動(予約変更)', $checkPaymntDetail[1]['remarks']);
    }

    public function testGetStoreEmails()
    {
        $store = $this->_createStore();

        $result = $this->utilService->getStoreEmails($store->id);
        $this->assertCount(3, $result);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result[0]);
        $this->assertSame('gourmet-test2@adventure-inc.co.jp', $result[1]);
        $this->assertSame('gourmet-test3@adventure-inc.co.jp', $result[2]);
    }

    private function _createDateForTestSaveOrderCode()
    {
        $store = new Store();
        $store->email_1 = 'gourmet-teststore@adventure-inc.co.jp';
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();

        $cancelFee = new CancelFee();
        $cancelFee->store_id = $store->id;
        $cancelFee->visit = 'BEFORE';
        $cancelFee->cancel_limit_unit = 'TIME';
        $cancelFee->cancel_limit = 1;
        $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        $cancelFee->cancel_fee = 1;
        $cancelFee->save();

        $user = $this->_createCmTmUser();

        // 返金用データ
        {
            $reservation = new Reservation();
            $reservation->save();

            $user = $this->_createCmTmUser();
            $cmThApplication = new CmThApplication();
            $cmThApplication->user_id = $user->user_id;
            $cmThApplication->lang_id = 1;
            $cmThApplication->save();

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

            $refundCmThApplication = $cmThApplication;
        }

        // 予約内容変更用データ
        {
            $reservation = new Reservation();
            $reservation->email = 'gourmet-test1@adventure-inc.co.jp';
            $reservation->pick_up_datetime = '2999-10-01 10:00:00';
            $reservation->total = 1000;         // 予約変更により書き変わる予定
            $reservation->persons = 1;         // 予約変更により書き変わる予定
            $reservation->save();

            $reservationStore = new ReservationStore();
            $reservationStore->reservation_id = $reservation->id;
            $reservationStore->store_id = $store->id;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->reservation_id = $reservation->id;
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->count = 1;                        // 予約変更により書き変わる予定
            $reservationMenu->unit_price = 1000;                // 予約変更により書き変わる予定
            $reservationMenu->price = 1000;                     // 予約変更により書き変わる予定
            $reservationMenu->save();

            $cmThApplication = new CmThApplication();
            $cmThApplication->user_id = $user->user_id;
            $cmThApplication->lang_id = 1;
            $cmThApplication->save();

            $cmThApplicationDetail = new CmThApplicationDetail();
            $cmThApplicationDetail->service_cd = 'gm';
            $cmThApplicationDetail->application_id = $reservation->id;
            $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
            $cmThApplicationDetail->save();

            $paymentToken = new PaymentToken();
            $paymentToken->reservation_id = $reservation->id;
            $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
            $paymentToken->token =  'testtokentesttoken';
            $paymentToken->is_invalid = 1;
            $paymentToken->call_back_values = '{"token":"testtokentesttoken","orderCode":"testcode"}';
            $paymentToken->save();

            $paymentToken = new PaymentToken();
            $paymentToken->reservation_id = $reservation->id;
            $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
            $paymentToken->token =  'testtokentesttoken';
            $paymentToken->is_invalid = 0;
            $paymentToken->is_restaurant_change =  0;
            $paymentToken->call_back_values = null;
            $paymentToken->save();

            $tmpRestaurantReservation = new TmpRestaurantReservation();
            $tmpRestaurantReservation->session_id = 'testtokentesttoken';
            $tmpRestaurantReservation->info = json_encode([
                'application' => [
                    'visitDate' => '2999-10-01',
                    'visitTime' => '10:00',
                    'persons' => 2,
                ],
                'customer' => [
                    'request' => '卵アレルギーがあります。',
                ],
                'unitPrice' => 1000,
                'menuTotal' => 2000,
                'total' => 2000,
            ]);
            $tmpRestaurantReservation->status = 'IN_PROCESS';
            $tmpRestaurantReservation->save();
        }

        // 予約内容変更用データ（管理者操作？）
        {
            $reservation2 = new Reservation();
            $reservation2->email = 'gourmet-test1@adventure-inc.co.jp';
            $reservation2->pick_up_datetime = '2999-10-01 10:00:00';
            $reservation2->total = 1000;         // 予約変更により書き変わる予定
            $reservation2->persons = 1;         // 予約変更により書き変わる予定
            $reservation2->save();

            $reservationStore2 = new ReservationStore();
            $reservationStore2->reservation_id = $reservation2->id;
            $reservationStore2->store_id = $store->id;
            $reservationStore2->save();

            $reservationMenu2 = new ReservationMenu();
            $reservationMenu2->reservation_id = $reservation2->id;
            $reservationMenu2->menu_id = $menu->id;
            $reservationMenu2->count = 1;                        // 予約変更により書き変わる予定
            $reservationMenu2->unit_price = 1000;                // 予約変更により書き変わる予定
            $reservationMenu2->price = 1000;                     // 予約変更により書き変わる予定
            $reservationMenu2->save();

            $cmThApplication2 = new CmThApplication();
            $cmThApplication2->user_id = $user->user_id;
            $cmThApplication2->lang_id = 1;
            $cmThApplication2->save();

            $cmThApplicationDetail2 = new CmThApplicationDetail();
            $cmThApplicationDetail2->service_cd = 'gm';
            $cmThApplicationDetail2->application_id = $reservation2->id;
            $cmThApplicationDetail2->cm_application_id = $cmThApplication2->cm_application_id;
            $cmThApplicationDetail2->save();

            $paymentToken2 = new PaymentToken();
            $paymentToken2->reservation_id = $reservation2->id;
            $paymentToken2->cm_application_id = $cmThApplication2->cm_application_id;
            $paymentToken2->token =  'testtokentesttoken2';
            $paymentToken2->is_invalid = 1;
            $paymentToken2->call_back_values = '{"token":"testtokentesttoken","orderCode":"testcode"}';
            $paymentToken2->save();

            $paymentToken2 = new PaymentToken();
            $paymentToken2->reservation_id = $reservation2->id;
            $paymentToken2->cm_application_id = $cmThApplication2->cm_application_id;
            $paymentToken2->token =  'testtokentesttoken2';
            $paymentToken2->is_invalid = 0;
            $paymentToken2->is_restaurant_change =  0;
            $paymentToken2->call_back_values = null;
            $paymentToken2->save();

            $paymentDetail2 = new PaymentDetail();
            $paymentDetail2->reservation_id = $reservation2->id;
            $paymentDetail2->target_id = $menu->id;
            $paymentDetail2->account_code = 'MENU';
            $paymentDetail2->price = 1000;
            $paymentDetail2->count = 1;
            $paymentDetail2->remarks = '自動';
            $paymentDetail2->save();

            $tmpAdminChangeReservation = new TmpAdminChangeReservation();
            $tmpAdminChangeReservation->reservation_id = $reservation2->id;
            $tmpAdminChangeReservation->info = json_encode([
                'application' => [
                    'visitDate' => '2999-10-01',
                    'visitTime' => '12:00',
                    'persons' => 2,
                ],
                'customer' => [
                    'request' => '卵アレルギーがあります。',
                ],
                'unitPrice' => 1000,
                'menuTotal' => 2000,
                'total' => 2000,
                'persons' => 2,
                'pick_up_datetime' => '2999-10-01 12:00:00',
            ]);
            $tmpAdminChangeReservation->status = null;
            $tmpAdminChangeReservation->is_invalid = 0;
            $tmpAdminChangeReservation->save();
        }

        return [$refundCmThApplication, $cmThApplication, $reservation, $cmThApplication2, $reservation2, $paymentToken2, $paymentDetail2];
    }

    private function _createDataForTestAdminChangeReservation()
    {
        $store = new Store();
        $store->email_1 = 'gourmet-teststore@adventure-inc.co.jp';
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();

        $reservation = new Reservation();
        $reservation->email = 'gourmet-test1@adventure-inc.co.jp';
        $reservation->pick_up_datetime = '2999-10-01 10:00:00';
        $reservation->total = 1000;         // 予約変更により書き変わる予定
        $reservation->persons = 1;         // 予約変更により書き変わる予定
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->save();

        $reservationMenu = new ReservationMenu();
        $reservationMenu->reservation_id = $reservation->id;
        $reservationMenu->menu_id = $menu->id;
        $reservationMenu->count = 1;                        // 予約変更により書き変わる予定
        $reservationMenu->unit_price = 1000;                // 予約変更により書き変わる予定
        $reservationMenu->price = 1000;                     // 予約変更により書き変わる予定
        $reservationMenu->save();

        $user = $this->_createCmTmUser();
        $cmThApplication = new CmThApplication();
        $cmThApplication->user_id = $user->user_id;
        $cmThApplication->lang_id = 1;
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->save();

        $paymentToken = new PaymentToken();
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->cm_application_id = $cmThApplication->cm_application_id;
        $paymentToken->token =  'testtokentesttoken';
        $paymentToken->is_invalid = 0;
        $paymentToken->is_restaurant_change = 1;
        $paymentToken->call_back_values = '{"token":"testtokentesttoken","orderCode":"testcode"}';
        $paymentToken->save();

        $paymentDetail = new PaymentDetail();
        $paymentDetail->reservation_id = $reservation->id;
        $paymentDetail->target_id = $menu->id;
        $paymentDetail->account_code = 'MENU';
        $paymentDetail->price = 1000;
        $paymentDetail->count = 1;
        $paymentDetail->remarks = '自動';
        $paymentDetail->save();

        $tmpAdminChangeReservation = new TmpAdminChangeReservation();
        $tmpAdminChangeReservation->reservation_id = $reservation->id;
        $tmpAdminChangeReservation->info = json_encode([
            'application' => [
                'visitDate' => '2999-10-01',
                'visitTime' => '12:00',
                'persons' => 2,
            ],
            'customer' => [
                'request' => '卵アレルギーがあります。',
            ],
            'unitPrice' => 1000,
            'menuTotal' => 2000,
            'total' => 2000,
            'persons' => 2,
            'pick_up_datetime' => '2999-10-01 12:00:00',
        ]);
        $tmpAdminChangeReservation->status = 'IN_PROCESS';
        $tmpAdminChangeReservation->save();

        $errorTmpAdminChangeReservation = new TmpAdminChangeReservation();
        $errorTmpAdminChangeReservation->reservation_id = $reservation->id;
        $errorTmpAdminChangeReservation->info = json_encode([
            'application' => [
                'visitDate' => '2999-10-01',
                'visitTime' => '12:00',
                'persons' => 2,
            ],
            'customer' => [
                'request' => '卵アレルギーがあります。',
            ],
            'unitPrice' => 1000,
            'menuTotal' => 2000,
            'total' => 2000,
        ]);
        $errorTmpAdminChangeReservation->status = 'IN_PROCESS';
        $errorTmpAdminChangeReservation->save();

        return [$paymentToken, $paymentDetail, $tmpAdminChangeReservation, $errorTmpAdminChangeReservation];
    }

    private function _createCmTmUser()
    {
        $cmTmUser = new CmTmUser();
        $cmTmUser->email_enc = 'gourmet-test1@adventure-inc.co.jp';
        $cmTmUser->password_enc = hash('sha384', 'gourmettest123');
        $cmTmUser->member_status = 1;
        $cmTmUser->gender_id = 1;
        $cmTmUser->save();
        return $cmTmUser;
    }

    private function _createStore()
    {
        $store = new Store();
        $store->email_1 = 'gourmet-test1@adventure-inc.co.jp';
        $store->email_2 = 'gourmet-test2@adventure-inc.co.jp';
        $store->email_3 = 'gourmet-test3@adventure-inc.co.jp';
        $store->save();
        return $store;
    }
}
