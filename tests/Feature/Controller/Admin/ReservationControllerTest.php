<?php

namespace Tests\Feature\Controller\Admin;

use App\Models\CancelDetail;
use App\Models\CancelFee;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\MailDBQueue;
use App\Models\Menu;
use App\Models\MessageBoard;
use App\Models\OpeningHour;
use App\Models\Option;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Price;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\SettlementCompany;
use App\Models\Store;
use App\Models\TmpAdminChangeReservation;
use App\Models\Vacancy;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Services\RestaurantReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Controller\Admin\TestCase;

class ReservationControllerTest extends TestCase
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

    public function testIndexWithInHouseAdministrator()
    {
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                          // アクセス確認
        $response->assertViewIs('admin.Reservation.index');    // 指定bladeを確認
        $response->assertViewHas([
            'reservations',
            'stores',
            'isMobile',
        ]);                                                    // bladeに渡している変数を確認
        $response->assertViewHas('isMobile', true);

        $this->logout();
    }

    public function testIndexWithInHouseGeneral()
    {
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                          // アクセス確認
        $response->assertViewIs('admin.Reservation.index');    // 指定bladeを確認
        $response->assertViewHasAll([
            'reservations',
            'stores',
            'isMobile',
        ]);                                                    // bladeに渡している変数を確認
        $response->assertViewHas('isMobile', true);

        $this->logout();
    }

    public function testIndexWithClientAdministrator()
    {
        $store = $this->_createStore();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                          // アクセス確認
        $response->assertViewIs('admin.Reservation.index');    // 指定bladeを確認
        $response->assertViewHasAll([
            'reservations',
            'stores',
            'isMobile',
        ]);                                                    // bladeに渡している変数を確認
        $response->assertViewHas('isMobile', true);

        $this->logout();
    }

    public function testIndexWithClientGeneral()
    {
        $store = $this->_createStore();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        $response = $this->_callIndex();
        $response->assertStatus(200);                          // アクセス確認
        $response->assertViewIs('admin.Reservation.index');    // 指定bladeを確認
        $response->assertViewHasAll([
            'reservations',
            'stores',
            'isMobile',
        ]);                                                    // bladeに渡している変数を確認
        $response->assertViewHas('isMobile', true);

        $this->logout();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIndexCsv()
    {
        $store = $this->_createStore();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        $response = $this->_callIndexCsv($store);
        $response->assertStatus(200);                         // アクセス確認

        // ファイルができるはずなので、確認後削除しておく。
        if (file_exists('予約一覧.csv')) {
            $this->assertTrue(true);
            unlink('予約一覧.csv');
        }

        $this->assertFalse(file_exists('予約一覧.csv'));
    }

    public function testEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                 // 社内管理者としてログイン

        // レストラン予約＆予約日の日がその月の1〜9日の場合
        {
            $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 1);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', new Carbon('2099-10-15 23:59:00.000000'));
            $response->assertViewHas('changeLimit', new Carbon('2099-10-07 23:59:00.000000'));
            $response->assertViewHas('newPayment', true);
        }

        // レストラン予約＆予約日の日がその月の10-15日の場合
        {
            $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-10 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 2);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', new Carbon('2099-10-15 23:59:00.000000'));
            $response->assertViewHas('changeLimit', new Carbon('2099-10-15 23:59:00.000000'));
            $response->assertViewHas('newPayment', true);
        }

        // レストラン予約＆予約日の日がその月の16日以降の場合
        {
            $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-16 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 3);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', new Carbon('2099-10-31 23:59:00.000000'));
            $response->assertViewHas('changeLimit', new Carbon('2099-10-22 23:59:00.000000'));
            $response->assertViewHas('newPayment', true);
        }

        // テイクアウト予約
        {
            $response = $this->_callEditForm($store, $menu, 'TO', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 4);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', null);
            $response->assertViewHas('changeLimit', null);
            $response->assertViewHas('newPayment', true);
        }

        $this->logout();
    }

    public function testEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                 // 社内一般としてログイン

        // レストラン予約＆予約日の日がその月の1〜9日の場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
        $response->assertStatus(200);                          // アクセス確認
        $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
        $response->assertViewHasAll([
            'reservation',
            'messages',
            'reservationStatus',
            'paymentStatus',
            'skyReserveNo',
            'user',
            'messageType',
            'isRepeater',
            'isMobile',
            'adminChangeInfo',
            'cancelLimit',
            'changeLimit',
            'newPayment',
        ]);                                                    // bladeに渡している変数を確認
        // 取得値が正しいか確認
        $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
        $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
        $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
        $response->assertViewHas('isRepeater', 1);
        $response->assertViewHas('isMobile', false);
        $response->assertViewHas('cancelLimit', new Carbon('2099-10-15 23:59:00.000000'));
        $response->assertViewHas('changeLimit', new Carbon('2099-10-07 23:59:00.000000'));
        $response->assertViewHas('newPayment', true);

        $this->logout();
    }

    public function testEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);      // クライアント管理者としてログイン

        // 担当店舗の場合
        {
            // レストラン予約＆予約日の日がその月の1〜9日の場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
            $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 1);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', new Carbon('2099-10-15 23:59:00.000000'));
            $response->assertViewHas('changeLimit', new Carbon('2099-10-07 23:59:00.000000'));
            $response->assertViewHas('newPayment', true);
        }

        // 担当外店舗の場合
        {
            list($store2, $menu2) = $this->_createStoreMenu();
            $response = $this->_callEditForm($store2, $menu2, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testEditFormWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);            // クライアント一般としてログイン

        // 担当店舗の場合
        {
            // レストラン予約＆予約日の日がその月の1〜9日の場合(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
            $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(200);                          // アクセス確認
            $response->assertViewIs('admin.Reservation.edit');    // 指定bladeを確認
            $response->assertViewHasAll([
                'reservation',
                'messages',
                'reservationStatus',
                'paymentStatus',
                'skyReserveNo',
                'user',
                'messageType',
                'isRepeater',
                'isMobile',
                'adminChangeInfo',
                'cancelLimit',
                'changeLimit',
                'newPayment',
            ]);                                                    // bladeに渡している変数を確認
            // 取得値が正しいか確認
            $response->assertViewHas('reservationStatus', [config('code.reservationStatus.reserve'), config('code.reservationStatus.ensure')]);
            $response->assertViewHas('paymentStatus', config('code.paymentStatus'));
            $response->assertViewHas('skyReserveNo', $cmThApplicationDetail->cm_application_id);
            $response->assertViewHas('isRepeater', 1);
            $response->assertViewHas('isMobile', false);
            $response->assertViewHas('cancelLimit', new Carbon('2099-10-15 23:59:00.000000'));
            $response->assertViewHas('changeLimit', new Carbon('2099-10-07 23:59:00.000000'));
            $response->assertViewHas('newPayment', true);
        }

        // 担当外店舗の場合
        {
            list($store2, $menu2) = $this->_createStoreMenu();
            $response = $this->_callEditForm($store2, $menu2, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
            $response->assertStatus(403);
        }

        $this->logout();
    }

    public function testSaveMessageBoardWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // データが登録されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANUAL_INPUT', $result[0]['message_type']);
        $this->assertSame('テストメッセージ', $result[0]['message']);

        $this->logout();
    }

    public function testSaveMessageBoardWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // データが登録されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANUAL_INPUT', $result[0]['message_type']);
        $this->assertSame('テストメッセージ', $result[0]['message']);

        $this->logout();
    }

    public function testSaveMessageBoardWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // データが登録されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANUAL_INPUT', $result[0]['message_type']);
        $this->assertSame('テストメッセージ', $result[0]['message']);

        $this->logout();
    }

    public function testSaveMessageBoardWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);

        // データが登録されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANUAL_INPUT', $result[0]['message_type']);
        $this->assertSame('テストメッセージ', $result[0]['message']);

        $this->logout();
    }

    public function testSaveMessageBoardException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callSaveMessageBoardException($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(503)->assertJson(['ret' => 'error', 'message' => 'Array to string conversion']);

        // データが登録されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->exists());

        $this->logout();
    }

    public function testUpdateReservationInfoWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // テイクアウト予約＆来店日時と人数の変更
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約ステータスが変更されていることを確認する
            $this->assertSame('ENSURE', Reservation::find($reservationId)->reservation_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('受注確定', $result[1]['message']);
        }

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より前）
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'RS', '2022-10-01 09:00:00', '2099-10-01 10:00:00', 'PAYED', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約日時と人数が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertSame('2099-10-01 10:00:00', $result->pick_up_datetime);
            $this->assertSame(3, $result->persons);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('人数変更', $result[1]['message']);
        }

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から4日以上先)）
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'RS', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail, 2500, true);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // TmpAdminChangeReservationが追加・更新されていること
            $this->assertSame(2, TmpAdminChangeReservation::where('reservation_id', $reservationId)->count());
            $this->assertSame(1, TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->count());
            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $paymentLimit = Carbon::now()->addDays(3)->hour(23)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $this->assertSame($paymentLimit, $result->payment_limit);
            $this->assertSame('WAIT_PAYMENT', $result->payment_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約変更(再決済要求)', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご注文変更のお知らせです。', $result[0]['subject']);
        }

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から3日以内先)）
        {
            $oldPickUpDatetime = Carbon::now()->addDays(3)->hour(9)->minute(0)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callUpdateReservationInfo($store, $menu, 'RS', $oldPickUpDatetime, '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // TmpAdminChangeReservationが登録されていること
            $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertSame('2099-10-01 10:00:00', $result->payment_limit);
            $this->assertSame('WAIT_PAYMENT', $result->payment_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約変更(再決済要求)', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご注文変更のお知らせです。', $result[0]['subject']);
        }

        // レストラン予約（席のみ）来店＆日時と人数の変更
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'RS', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'WAIT_PAYMENT', $cmThApplicationDetail, 0);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertSame('2099-10-01 10:00:00', $result->pick_up_datetime);
            $this->assertSame(3, $result->persons);
            $this->assertSame('AUTH', $result->payment_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('人数変更', $result[1]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご注文変更のお知らせです。', $result[0]['subject']);
        }

        // レストラン予約（変更なし）
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'RS', '2099-10-01 09:00:00', '2099-10-01 09:00:00', 'WAIT_PAYMENT', $cmThApplicationDetail, 2500, true, 2);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // TmpAdminChangeReservationが変更されていること
            $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
            $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
            // 予約情報が変更されていないことを確認する
            $result = Reservation::find($reservationId);
            $this->assertSame('2099-10-01 09:00:00', $result->pick_up_datetime);
            $this->assertSame(2, $result->persons);
            $this->assertSame('AUTH', $result->payment_status);
            // 伝言板データに追加されていないことを確認する
            $this->assertFalse(MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->exists());
            // 送信メール情報が登録されていないことを確認する
            $this->assertFalse(MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->exists());
        }

        $this->logout();
    }

    public function testUpdateReservationInfoWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // テイクアウト予約＆来店日時と人数の変更(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約ステータスが変更されていることを確認する
            $this->assertSame('ENSURE', Reservation::find($reservationId)->reservation_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('受注確定', $result[1]['message']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // テイクアウト予約＆来店日時と人数の変更(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約ステータスが変更されていることを確認する
            $this->assertSame('ENSURE', Reservation::find($reservationId)->reservation_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('受注確定', $result[1]['message']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // テイクアウト予約＆来店日時と人数の変更(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約ステータスが変更されていることを確認する
            $this->assertSame('ENSURE', Reservation::find($reservationId)->reservation_status);
            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(2, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('来店日時変更', $result[0]['message']);
            $this->assertSame('MANAGEMENT_TOOL', $result[1]['message_type']);
            $this->assertSame('受注確定', $result[1]['message']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoCanSaleError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から4日以上先)）
        // 店舗の提供曜日・時間のチェック結果がfalse
        {
            $response = $this->_callUpdateReservationInfoError($store, $menu, ['canSale'], $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => '営業時間外のため注文できません。']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoNotVacancy()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から4日以上先)）
        // 空席・在庫チェック結果がfalse
        {
            $response = $this->_callUpdateReservationInfoError($store, $menu, ['notVacancy'], $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => '空席がありません。']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoIsSalesTimeError()
    {
        list($store, $menu) = $this->_createStoreMenu(0, 'RS', 1, 10, '12:00:00', '21:00:00');  // メニュー提供時間を予約時間から外れるようにする
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から4日以上先)）
        // コースの提供時間のチェック結果がfalse
        {
            $response = $this->_callUpdateReservationInfoError($store, $menu, [], $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => 'プラン提供時間外です。']);
        }

        $this->logout();
    }

    public function testUpdateReservationInfoNotAvailableNumber()
    {
        list($store, $menu) = $this->_createStoreMenu(0, 'RS', 1, 2);  // メニュー提供可能人数を予約変更人数以下に設定しておく
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // レストラン予約来店＆日時と人数の変更（予約日時が現在より後(現在日から4日以上先)）
        // コースの提供時間のチェック結果がfalse
        {
            $response = $this->_callUpdateReservationInfoError($store, $menu, [], $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => 'このコースの予約人数は2人までです。']);
        }

        $this->logout();
    }

    public function testClearAdminChangeInfoWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // TmpAdminChangeReservationが変更されていること
        $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
        $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertNull($result->payment_limit);
        $this->assertSame('AUTH', $result->payment_status);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約変更取消', $result[0]['message']);

        $this->logout();
    }

    public function testClearAdminChangeInfoWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // TmpAdminChangeReservationが変更されていること
        $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
        $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertNull($result->payment_limit);
        $this->assertSame('AUTH', $result->payment_status);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約変更取消', $result[0]['message']);

        $this->logout();
    }

    public function testClearAdminChangeInfoWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // TmpAdminChangeReservationが変更されていること
        $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
        $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertNull($result->payment_limit);
        $this->assertSame('AUTH', $result->payment_status);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約変更取消', $result[0]['message']);

        $this->logout();
    }

    public function testClearAdminChangeInfoWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // TmpAdminChangeReservationが変更されていること
        $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
        $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertNull($result->payment_limit);
        $this->assertSame('AUTH', $result->payment_status);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約変更取消', $result[0]['message']);

        $this->logout();
    }

    public function testClearAdminChangeInfoThrowable()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callClearAdminChangeInfoThrowable($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => 'Method Illuminate\\Database\\Eloquent\\Collection::save does not exist.']);
        $reservationId = $cmThApplicationDetail->application_id;

        // TmpAdminChangeReservationが変更されていないことを確認する
        $this->assertTrue(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->exists());
        $this->assertFalse(TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 1)->whereNull('status')->exists());
        // 予約情報が変更されていないことを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('PAYED', $result->payment_status);

        $this->logout();
    }

    public function testCancelReservationWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // PaymentTokenデータあり＆当日の予約キャンセル
        {
            // モックを使って決済サービスへの返却値を指定
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('settlePayment')->andReturn(true);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertNull($result->payment_limit);
            $this->assertSame('CANCEL', $result->reservation_status);
            $this->assertSame('NO_REFUND', $result->payment_status);
            $this->assertNotNull($result->cancel_datetime);
            // キャンセル明細データが登録されていることを確認する
            $result = CancelDetail::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame(1, $result[0]['target_id']);
            $this->assertSame('MENU', $result[0]['account_code']);
            $this->assertSame(2500, $result[0]['price']);
            $this->assertSame(2, $result[0]['count']);
        }

        // PaymentTokenデータあり＆当日以外の予約キャンセル
        {
            // モックを使って決済サービスへの返却値を指定
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('cancelPayment')->andReturn(true);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $response = $this->_callCancelReservation($store, $menu, 'RS', true, $callBackValues, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertNull($result->payment_limit);
            $this->assertSame('CANCEL', $result->reservation_status);
            $this->assertSame('CANCEL', $result->payment_status);
            $this->assertNotNull($result->cancel_datetime);
        }

        // PaymentTokenデータなしのキャンセル（Econ?)
        {
            $callBackValues = ['orderCode' => 'testOrderCode'];
            $response = $this->_callCancelReservation($store, $menu, 'RS', false, null, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $cmThApplicationDetail->application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約キャンセル', $result[0]['message']);
        }

        $this->logout();
    }

    public function testCancelReservationWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // PaymentTokenデータあり＆当日の予約キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            // モックを使って決済サービスへの返却値を指定
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('settlePayment')->andReturn(true);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertNull($result->payment_limit);
            $this->assertSame('CANCEL', $result->reservation_status);
            $this->assertSame('NO_REFUND', $result->payment_status);
            $this->assertNotNull($result->cancel_datetime);
            // キャンセル明細データが登録されていることを確認する
            $result = CancelDetail::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame(1, $result[0]['target_id']);
            $this->assertSame('MENU', $result[0]['account_code']);
            $this->assertSame(2500, $result[0]['price']);
            $this->assertSame(2, $result[0]['count']);
        }

        $this->logout();
    }

    public function testCancelReservationWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // PaymentTokenデータあり＆当日の予約キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            // モックを使って決済サービスへの返却値を指定
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('settlePayment')->andReturn(true);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertNull($result->payment_limit);
            $this->assertSame('CANCEL', $result->reservation_status);
            $this->assertSame('NO_REFUND', $result->payment_status);
            $this->assertNotNull($result->cancel_datetime);
            // キャンセル明細データが登録されていることを確認する
            $result = CancelDetail::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame(1, $result[0]['target_id']);
            $this->assertSame('MENU', $result[0]['account_code']);
            $this->assertSame(2500, $result[0]['price']);
            $this->assertSame(2, $result[0]['count']);
        }

        $this->logout();
    }

    public function testCancelReservationWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // PaymentTokenデータあり＆当日の予約キャンセル(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            // モックを使って決済サービスへの返却値を指定
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('settlePayment')->andReturn(true);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 予約情報が変更されていることを確認する
            $result = Reservation::find($reservationId);
            $this->assertNull($result->payment_limit);
            $this->assertSame('CANCEL', $result->reservation_status);
            $this->assertSame('NO_REFUND', $result->payment_status);
            $this->assertNotNull($result->cancel_datetime);
            // キャンセル明細データが登録されていることを確認する
            $result = CancelDetail::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame(1, $result[0]['target_id']);
            $this->assertSame('MENU', $result[0]['account_code']);
            $this->assertSame(2500, $result[0]['price']);
            $this->assertSame(2, $result[0]['count']);
        }

        $this->logout();
    }

    public function testCancelReservationNotOrderCode()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // orderCodeが未設定の場合、エラーとなることを確認
        $callBackValues = [];
        $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
        $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
        $response->assertStatus(503)->assertJson(['ret' => 'error', 'message' => 'orderCodeが取れません']);

        $this->logout();
    }

    public function testCancelReservationSettlePaymentError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // PaymentTokenデータあり＆当日の予約キャンセル
        {
            // モックを使って決済サービスへの返却値を指定(処理が失敗したことになるようfalseを返す)
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('settlePayment')->andReturn(false);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
            $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
            $response->assertStatus(200)->assertJson(['message' => '新決済キャンセル失敗']);
        }

        $this->logout();
    }

    public function testCancelReservationCancelPaymentError()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // PaymentTokenデータあり＆当日以外の予約キャンセル
        {
            // モックを使って決済サービスへの返却値を指定(処理が失敗したことになるようfalseを返す)
            $paymentSkyticket = \Mockery::mock(PaymentSkyticket::class);
            $paymentSkyticket->shouldReceive('cancelPayment')->andReturn(false);
            $this->app->instance(PaymentSkyticket::class, $paymentSkyticket);

            $callBackValues = ['orderCode' => 'testOrderCode'];
            $response = $this->_callCancelReservation($store, $menu, 'RS', true, $callBackValues, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['message' => '新決済キャンセル失敗']);
        }

        $this->logout();
    }

    public function testCancelReservationForUseWithInHouseAdministrator()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // お客様都合キャンセル（計上後）
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // キャンセル明細が登録されていることを確認する
        $result = CancelDetail::where('reservation_id', $reservationId)->get();
        $this->assertCount(2, $result);
        $this->assertSame($menu->id, $result[0]['target_id']);
        $this->assertSame('MENU', $result[0]['account_code']);
        $this->assertSame(2500, $result[0]['price']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[0]['remarks']);
        $this->assertSame($option->id, $result[1]['target_id']);
        $this->assertSame('OKONOMI', $result[1]['account_code']);
        $this->assertSame(0, $result[1]['price']);
        $this->assertSame(2, $result[1]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[1]['remarks']);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お客様都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForUserWithInHouseGeneral()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // お客様都合キャンセル（計上後）
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // キャンセル明細が登録されていることを確認する
        $result = CancelDetail::where('reservation_id', $reservationId)->get();
        $this->assertCount(2, $result);
        $this->assertSame($menu->id, $result[0]['target_id']);
        $this->assertSame('MENU', $result[0]['account_code']);
        $this->assertSame(2500, $result[0]['price']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[0]['remarks']);
        $this->assertSame($option->id, $result[1]['target_id']);
        $this->assertSame('OKONOMI', $result[1]['account_code']);
        $this->assertSame(0, $result[1]['price']);
        $this->assertSame(2, $result[1]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[1]['remarks']);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お客様都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForUserWithClientAdministrator()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // お客様都合キャンセル（計上後）
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // キャンセル明細が登録されていることを確認する
        $result = CancelDetail::where('reservation_id', $reservationId)->get();
        $this->assertCount(2, $result);
        $this->assertSame($menu->id, $result[0]['target_id']);
        $this->assertSame('MENU', $result[0]['account_code']);
        $this->assertSame(2500, $result[0]['price']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[0]['remarks']);
        $this->assertSame($option->id, $result[1]['target_id']);
        $this->assertSame('OKONOMI', $result[1]['account_code']);
        $this->assertSame(0, $result[1]['price']);
        $this->assertSame(2, $result[1]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[1]['remarks']);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お客様都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForUserWithClientGeneral()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // お客様都合キャンセル（計上後）
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // キャンセル明細が登録されていることを確認する
        $result = CancelDetail::where('reservation_id', $reservationId)->get();
        $this->assertCount(2, $result);
        $this->assertSame($menu->id, $result[0]['target_id']);
        $this->assertSame('MENU', $result[0]['account_code']);
        $this->assertSame(2500, $result[0]['price']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[0]['remarks']);
        $this->assertSame($option->id, $result[1]['target_id']);
        $this->assertSame('OKONOMI', $result[1]['account_code']);
        $this->assertSame(0, $result[1]['price']);
        $this->assertSame(2, $result[1]['count']);
        $this->assertSame('キャンセル料マスタID:' . $cancelFee->id, $result[1]['remarks']);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お客様都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForUserNoCancelFee()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // お客様都合キャンセル（キャンセル料設定が未設定の場合）
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee, false);
        $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => '適用できるキャンセル料設定がありません。設定を確認してください。']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 伝言板データに追加されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testCancelReservationForUserNotPayed()
    {
        list($store, $menu, $option) = $this->_createStoreMenu(0, 'TO');
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('cancel')->andReturn(false);
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        // お客様都合キャンセル（計上前）
        $response = $this->_callCancelReservationForUser($store, $menu, 'AUTH', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => '']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 伝言板データに追加されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testCancelReservationForAdminWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // お店都合キャンセル（計上後）
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お店都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForAdminWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // お店都合キャンセル（計上後）
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お店都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForAdminWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // お店都合キャンセル（計上後）
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お店都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForAdminWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // お店都合キャンセル（計上後）
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('CANCEL', $result->reservation_status);
        $this->assertSame(0, $result->is_close);
        $this->assertNotNull($result->cancel_datetime);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('予約キャンセル(お店都合)', $result[0]['message']);

        $this->logout();
    }

    public function testCancelReservationForAdminNotPayed()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $restaurantReservationService = \Mockery::mock(RestaurantReservationService::class);
        $restaurantReservationService->shouldReceive('adminCancel')->andReturn(false);
        $this->app->instance(RestaurantReservationService::class, $restaurantReservationService);

        // お客様都合キャンセル（計上前）
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'AUTH', $cmThApplicationDetail);
        $response->assertStatus(503)->assertJson(['ret' => 'error', 'message' => '']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 伝言板データに追加されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testUpdateDelegateInfoWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // 予約情報の更新
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('名更新', $result->first_name);
        $this->assertSame('姓更新', $result->last_name);
        $this->assertSame('0312345678', $result->tel);
        $this->assertSame('gourmet-test1234@adventure-inc.co.jp', $result->email);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('申込者情報変更', $result[0]['message']);

        $this->logout();
    }

    public function testUpdateDelegateInfoWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // 予約情報の更新
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('名更新', $result->first_name);
        $this->assertSame('姓更新', $result->last_name);
        $this->assertSame('0312345678', $result->tel);
        $this->assertSame('gourmet-test1234@adventure-inc.co.jp', $result->email);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('申込者情報変更', $result[0]['message']);

        $this->logout();
    }

    public function testUpdateDelegateInfoWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // 予約情報の更新
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('名更新', $result->first_name);
        $this->assertSame('姓更新', $result->last_name);
        $this->assertSame('0312345678', $result->tel);
        $this->assertSame('gourmet-test1234@adventure-inc.co.jp', $result->email);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('申込者情報変更', $result[0]['message']);

        $this->logout();
    }

    public function testUpdateDelegateInfoWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // 予約情報の更新
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'ok']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('名更新', $result->first_name);
        $this->assertSame('姓更新', $result->last_name);
        $this->assertSame('0312345678', $result->tel);
        $this->assertSame('gourmet-test1234@adventure-inc.co.jp', $result->email);
        // 伝言板データに追加されていることを確認する
        $result = MessageBoard::where('reservation_id', $reservationId)->get();
        $this->assertCount(1, $result);
        $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
        $this->assertSame('申込者情報変更', $result[0]['message']);

        $this->logout();
    }

    public function testUpdateDelegateInfoException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // 予約情報の更新
        $response = $this->_callUpdateDelegateInfoException($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(503)->assertJson(['ret' => 'error', 'message' => 'strlen() expects parameter 1 to be string, array given']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 予約情報が変更されていることを確認する
        $result = Reservation::find($reservationId);
        $this->assertSame('太郎', $result->first_name);
        $this->assertSame('グルメ', $result->last_name);
        $this->assertSame('0311112222', $result->tel);
        $this->assertSame('gourmet-test1@adventure-inc.co.jp', $result->email);
        // 伝言板データに追加されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $reservationId)->exists());

        $this->logout();
    }

    public function testSendReservationMailWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // レストラン予約予約完了メールの再送
        {
            $response = $this->_callSendReservationMail($store, $menu, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約完了メール再送（グルメ太郎）', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約ありがとうございました！', $result[0]['subject']);
        }

        // テイクアウト予約予約完了メールの再送
        {
            list($store2, $menu2) = $this->_createStoreMenu(0, 'TO');
            $response = $this->_callSendReservationMail($store2, $menu2, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約完了メール再送（グルメ太郎）', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご注文ありがとうございました！', $result[0]['subject']);
        }

        $this->logout();
    }

    public function testSendReservationMailWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // レストラン予約予約完了メールの再送(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callSendReservationMail($store, $menu, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約完了メール再送（グルメ太郎）', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約ありがとうございました！', $result[0]['subject']);
        }

        $this->logout();
    }

    public function testSendReservationMailWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // レストラン予約予約完了メールの再送(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callSendReservationMail($store, $menu, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約完了メール再送（グルメ太郎）', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約ありがとうございました！', $result[0]['subject']);
        }

        $this->logout();
    }

    public function testSendReservationMailWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // レストラン予約予約完了メールの再送(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $response = $this->_callSendReservationMail($store, $menu, $cmThApplicationDetail);
            $response->assertStatus(200)->assertJson(['ret' => 'ok']);
            $reservationId = $cmThApplicationDetail->application_id;

            // 伝言板データに追加されていることを確認する
            $result = MessageBoard::where('reservation_id', $reservationId)->get();
            $this->assertCount(1, $result);
            $this->assertSame('MANAGEMENT_TOOL', $result[0]['message_type']);
            $this->assertSame('予約完了メール再送（グルメ太郎）', $result[0]['message']);
            // 送信メール情報が登録されていること
            $result = MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->get();
            $this->assertCount(1, $result);
            $this->assertSame('【スカイチケットグルメ】ご予約ありがとうございました！', $result[0]['subject']);
        }

        $this->logout();
    }

    public function testSendReservationMailException()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        $response = $this->_callSendReservationMailException($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(200)->assertJson(['ret' => 'error', 'message' => 'Property [last_name] does not exist on this collection instance.']);
        $reservationId = $cmThApplicationDetail->application_id;

        // 伝言板データに追加されていないことを確認する
        $this->assertFalse(MessageBoard::where('reservation_id', $reservationId)->exists());
        // 送信メール情報が登録されていないことを確認する
        $this->assertFalse(MailDBQueue::where('cm_application_id', $cmThApplicationDetail->cm_application_id)->exists());

        $this->logout();
    }

    public function testRedirectEditFormWithInHouseAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseAdministrator();                  // 社内管理者としてログイン

        // 該当予約IDがある場合、予約詳細画面にリダイレクトすることを確認
        {
            $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
            $response = $this->_callRedirectEditForm($cmThApplicationDetail->cm_application_id);
            $response->assertStatus(302);   // リダイレクト
            $response->assertRedirect('/admin/reservation/edit/' . $cmThApplicationDetail->application_id);
        }

        // 該当予約IDがない場合、予約一覧画面にリダイレクトすることを確認
        {
            $response = $this->_callRedirectEditForm(0);
            $response->assertStatus(302);   // リダイレクト
            $response->assertRedirect('/admin/reservation');
            $response->assertSessionHas('message', '予約内容が見つかりませんでした。');
        }

        $this->logout();
    }

    public function testRedirectEditFormWithInHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithInHouseGeneral();                        // 社内一般としてログイン

        // 該当予約IDがある場合、予約詳細画面にリダイレクトすることを確認(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
            $response = $this->_callRedirectEditForm($cmThApplicationDetail->cm_application_id);
            $response->assertStatus(302);   // リダイレクト
            $response->assertRedirect('/admin/reservation/edit/' . $cmThApplicationDetail->application_id);
        }

        $this->logout();
    }

    public function testRedirectEditFormWithClientAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientAdministrator($store->id);         // クライアント管理者としてログイン

        // 該当予約IDがある場合、予約詳細画面にリダイレクトすることを確認(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
            $response = $this->_callRedirectEditForm($cmThApplicationDetail->cm_application_id);
            $response->assertStatus(302);                       // リダイレクト
            $response->assertRedirect('/admin/reservation/edit/' . $cmThApplicationDetail->application_id);
        }

        $this->logout();
    }

    public function testRedirectEditFormWithClientGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithClientGeneral($store->id);               // クライアント一般としてログイン

        // 該当予約IDがある場合、予約詳細画面にリダイレクトすることを確認(他のパターンは社内管理者ユーザーでチェック済みのため、割愛)
        {
            $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
            $response = $this->_callRedirectEditForm($cmThApplicationDetail->cm_application_id);
            $response->assertStatus(302);   // リダイレクト
            $response->assertRedirect('/admin/reservation/edit/' . $cmThApplicationDetail->application_id);
        }

        $this->logout();
    }

    public function testReservationControllerWithSettlementAdministrator()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithSettlementAdministrator();            // 精算管理会社としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method saveMessageBoard
        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method updateReservationInfo
        $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method clearAdminChangeInfo
        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method cancelReservation
        $callBackValues = ['orderCode' => 'testOrderCode'];
        $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
        $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
        $response->assertStatus(404);

        // target method cancelReservationForUser
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(404);

        // target method cancelReservationForAdmin
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method updateDelegateInfo
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method redirectEditForm
        $response = $this->_callRedirectEditForm(0);
        $response->assertStatus(404);

        $this->logout();
    }

    public function testReservationControllerWithOutHouseGeneral()
    {
        list($store, $menu) = $this->_createStoreMenu();
        $this->loginWithOutHouseGeneral();                // 社外一般権限としてログイン

        // target method index
        $response = $this->_callIndex();
        $response->assertStatus(404);

        // target method editForm
        $response = $this->_callEditForm($store, $menu, 'RS', '2099-10-01 09:00:00', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method saveMessageBoard
        $response = $this->_callSaveMessageBoard($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method updateReservationInfo
        $response = $this->_callUpdateReservationInfo($store, $menu, 'TO', '2099-10-01 09:00:00', '2099-10-01 10:00:00', 'AUTH', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method clearAdminChangeInfo
        $response = $this->_callClearAdminChangeInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method cancelReservation
        $callBackValues = ['orderCode' => 'testOrderCode'];
        $datetime = Carbon::now()->hour(10)->minute(59)->second(0)->format('Y-m-d H:i:s');
        $response = $this->_callCancelReservation($store, $menu, 'TO', true, $callBackValues, $cmThApplicationDetail, $datetime);
        $response->assertStatus(404);

        // target method cancelReservationForUser
        $response = $this->_callCancelReservationForUser($store, $menu, 'PAYED', $cmThApplicationDetail, $cancelFee);
        $response->assertStatus(404);

        // target method cancelReservationForAdmin
        $response = $this->_callCancelReservationForAdmin($store, $menu, 'PAYED', $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method updateDelegateInfo
        $response = $this->_callUpdateDelegateInfo($store, $menu, $cmThApplicationDetail);
        $response->assertStatus(404);

        // target method redirectEditForm
        $response = $this->_callRedirectEditForm(0);
        $response->assertStatus(404);

        $this->logout();
    }

    private function _createStore($published = 1)
    {
        $store = new Store();
        $store->app_cd = 'TORS';
        $store->name = 'テスト店舗';
        $store->regular_holiday = '110111111';
        $store->published = $published;
        $store->save();
        return $store;
    }

    private function _createMenu($storeId, $published = 1, $appCd = 'RS', $lowerLimit = 1, $upperLimit = 10, $startAt = '09:00:00', $endAt = '21:00:00')
    {
        $menu = new Menu();
        $menu->store_id = $storeId;
        $menu->app_cd = $appCd;
        $menu->name = 'テストメニュー';
        $menu->lower_orders_time = 90;
        $menu->provided_day_of_week = '11011111';
        $menu->free_drinks = 0;
        $menu->available_number_of_lower_limit = $lowerLimit;
        $menu->available_number_of_upper_limit = $upperLimit;
        $menu->sales_lunch_start_time = $startAt;
        $menu->sales_lunch_end_time = $endAt;
        $menu->published = $published;
        $menu->save();

        $option = null;
        if ($appCd == 'TO') {
            $option = new Option();
            $option->menu_id = $menu->id;
            $option->option_cd = 'OKONOMI';
            $option->required = 1;
            $option->keyword_id = 1;
            $option->keyword = 'ご飯の量';
            $option->contents_id = 1;
            $option->contents = '多め';
            $option->price = 50;
            $option->save();
        }

        return [$menu, $option];
    }

    private function _createStoreMenu($published = 0, $appCd = 'RS', $lowerLimit = 1, $upperLimit = 10, $startAt = '09:00:00', $endAt = '21:00:00')
    {
        $store = $this->_createStore($published);
        list($menu, $option) = $this->_createMenu($store->id, $published, $appCd, $lowerLimit, $upperLimit, $startAt, $endAt);
        return [$store, $menu, $option];
    }

    private function _createSettlementCompany($storeId)
    {
        $settlementCompany = new SettlementCompany();
        $settlementCompany->name = 'testテストtest精算会社';
        $settlementCompany->tel = '0698765432';
        $settlementCompany->postal_code = '1111123';
        $settlementCompany->save();

        Store::find($storeId)->update(['settlement_company_id' => $settlementCompany->id]);
    }

    private function _createPrice($menuId, $price)
    {
        $menuPrice = new Price();
        $menuPrice->menu_id = $menuId;
        $menuPrice->price = $price;
        $menuPrice->start_date = '2022-01-01';
        $menuPrice->end_date = '2099-12-01';
        $menuPrice->save();
    }

    private function _createOpenHour($storeId, $week = '11111111', $startAt = '09:00', $endAt = '21:00')
    {
        $openingHour = new OpeningHour();
        $openingHour->store_id = $storeId;
        $openingHour->week = $week;
        $openingHour->start_at = $startAt;
        $openingHour->end_at = $endAt;
        $openingHour->last_order_time = $endAt;
        $openingHour->save();
    }

    private function _createVacancy($storeId, $date, $time, $headCount = 1, $stock = 1)
    {
        $vacancy = new Vacancy();
        $vacancy->store_id = $storeId;
        $vacancy->date = $date;
        $vacancy->time = $time;
        $vacancy->headcount = $headCount;
        $vacancy->stock = $stock;
        $vacancy->save();
    }

    private function _createReservation($store, $menu = null, $appCd = 'RS', $pickUpDatetime = '2099-10-01 09:00:00', $paymentStaus = 'PAYED', $menuPrice = 2500)
    {
        $reservation = new Reservation();
        $reservation->app_cd = $appCd;
        $reservation->pick_up_datetime = $pickUpDatetime;
        $reservation->persons = 2;
        $reservation->total = $menuPrice * 2;
        $reservation->reservation_status = 'RESERVE';
        $reservation->is_close = '1';
        $reservation->payment_status = $paymentStaus;
        $reservation->created_at = '2099-10-01 09:00:00';
        $reservation->last_name = 'グルメ';
        $reservation->first_name = '太郎';
        $reservation->tel = '0311112222';
        $reservation->email = 'gourmet-test1@adventure-inc.co.jp';
        $reservation->save();

        $reservationStore = new ReservationStore();
        $reservationStore->reservation_id = $reservation->id;
        $reservationStore->store_id = $store->id;
        $reservationStore->name = $store->name;
        $reservationStore->save();

        if (!is_null($menu)) {
            $reservationMenu = new ReservationMenu();
            $reservationMenu->reservation_id = $reservation->id;
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->unit_price = $menuPrice;
            $reservationMenu->count = 2;
            $reservationMenu->price = $menuPrice * 2;
            $reservationMenu->save();

            $options = Option::where('menu_id', $menu->id)->get();
            if ($options->count() > 0) {
                foreach ($options as $option) {
                    $reservationOption = new ReservationOption();
                    $reservationOption->option_id = $option->id;
                    $reservationOption->reservation_menu_id = $reservationMenu->id;
                    $reservationOption->price = $option->price;
                    $reservationOption->count = 2;
                    $reservationOption->save();
                }
            }
        }

        $cmThApplication = new CmThApplication();
        $cmThApplication->save();

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->application_id = $reservation->id;
        $cmThApplicationDetail->cm_application_id = $cmThApplication->cm_application_id;
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->save();

        return $cmThApplicationDetail;
    }

    private function _createPaymentToken($cmApplicationId, $reservationId, $callBackValues = null)
    {
        $paymentToken = new PaymentToken();
        $paymentToken->cm_application_id = $cmApplicationId;
        $paymentToken->reservation_id = $reservationId;
        $paymentToken->call_back_values = json_encode($callBackValues);
        $paymentToken->save();
    }

    private function _createTmpAdminChangeReservation($reservationId)
    {
        $tmpAdminChangeReservation = new TmpAdminChangeReservation();
        $tmpAdminChangeReservation->reservation_id = $reservationId;
        $tmpAdminChangeReservation->is_invalid = 0;
        $tmpAdminChangeReservation->status = null;
        $tmpAdminChangeReservation->save();
    }

    private function _createPaymentDetail($reservationId, $price, $count)
    {
        $paymentDetail = new PaymentDetail();
        $paymentDetail->reservation_id = $reservationId;
        $paymentDetail->account_code = 'MENU';
        $paymentDetail->price = $price;
        $paymentDetail->count = $count;
        $paymentDetail->target_id = 1;
        $paymentDetail->save();
    }

    private function _createCancelFee($storeId, $appCd)
    {
        $cancelFee = new CancelFee();
        $cancelFee->store_id = $storeId;
        $cancelFee->app_cd = $appCd;
        $cancelFee->apply_term_from = '2022-09-01';
        $cancelFee->apply_term_to = '2999-09-30';
        $cancelFee->cancel_limit_unit = 'DAY';
        $cancelFee->cancel_limit = '1';
        $cancelFee->cancel_fee_unit = 'FIXED_RATE';
        $cancelFee->cancel_fee = '100';
        $cancelFee->fraction_round = 'ROUND_UP';
        $cancelFee->cancel_fee_max = '1500';
        $cancelFee->cancel_fee_min = null;
        $cancelFee->visit = 'BEFORE';
        $cancelFee->published = 1;
        $cancelFee->save();
        return $cancelFee;
    }

    private function _callIndex()
    {
        // BaseAdminController継承しているので、BaseAdminControllerのisMobile関数をチェックする（Moblieとして判定されるか）
        return $this->withHeaders([
            'User-Agent' =>  'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/85.0.4183.109 Mobile/15E148 Safari/604.1',
        ])->get('/admin/reservation');
    }

    private function _callIndexCsv($store)
    {
        $cmThApplicationDetail = $this->_createReservation($store);
        return $this->get('/admin/reservation?action=csv&id=RS' . $cmThApplicationDetail->application_id);
    }

    private function _callEditForm($store, $menu, $appCd, $pickUpDatetime, &$cmThApplicationDetail)
    {
        $this->_createSettlementCompany($store->id);
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $appCd, $pickUpDatetime);
        $this->_createPaymentToken($cmThApplicationDetail->cm_application_id, $cmThApplicationDetail->application_id);

        // BaseAdminController継承しているので、BaseAdminControllerのisMobile関数をチェックする（Moblieとして判定されないか）
        return $this->withHeaders([
            'User-Agent' =>  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
        ])->get('/admin/reservation/edit/' . $cmThApplicationDetail->application_id);
    }

    private function _callSaveMessageBoard($store, $menu, &$cmThApplicationDetail)
    {
        $this->_createSettlementCompany($store->id);
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        return $this->post('/admin/reservation/save_message_board', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'message' => 'テストメッセージ',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSaveMessageBoardException($store, $menu, &$cmThApplicationDetail)
    {
        $this->_createSettlementCompany($store->id);
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        return $this->post('/admin/reservation/save_message_board', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'message' => [1, 2, 3],                        // 文字列で配列を渡して例外発生させる
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callUpdateReservationInfo($store, $menu, $appCd, $oldPickUpDatetime, $newPickUpDatetime, $paymentStatus, &$cmThApplicationDetail, $menuPrice = 2500, $addTmpAdminChangeReservation = false, $persons = 3)
    {
        $datetime = new Carbon($newPickUpDatetime);
        $this->_createSettlementCompany($store->id);
        $this->_createOpenHour($store->id);
        $this->_createPrice($menu->id, $menuPrice);
        $this->_createVacancy($store->id, $datetime->format('Y-m-d'), $datetime->format('H:i'), 3, 3);
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $appCd, $oldPickUpDatetime, $paymentStatus, $menuPrice);
        if ($addTmpAdminChangeReservation) {
            $this->_createTmpAdminChangeReservation($cmThApplicationDetail->application_id);
        }
        return $this->post('/admin/reservation/update_reservation_info', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'reservation_status' => ($appCd == 'TO') ? 'ENSURE' : 'RESERVE',
            'pick_up_datetime' => $newPickUpDatetime,
            'persons' => $persons,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callUpdateReservationInfoError($store, $menu, $notAdd, &$cmThApplicationDetail)
    {
        // 共通設定
        $appCd = 'RS';
        $oldPickUpDatetime = '2099-10-01 09:00:00';
        $newPickUpDatetime = '2099-10-01 10:00:00';
        $paymentStatus = 'AUTH';
        $menuPrice = 2500;

        $this->_createSettlementCompany($store->id);
        $this->_createPrice($menu->id, $menuPrice);
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $appCd, $oldPickUpDatetime, $paymentStatus, $menuPrice);

        if (in_array('canSale', $notAdd)) {
            // 営業時間を変更予約時間が含まない時間帯に設定にする
            $this->_createOpenHour($store->id, '11111111', '12:00', '21:00');
        } else {
            // 営業時間を変更予約時間が含む時間帯に設定にする
            $this->_createOpenHour($store->id);
        }

        if (in_array('notVacancy', $notAdd)) {
            // 在庫登録しない
        } else {
            // 在庫登録する
            $datetime = new Carbon($newPickUpDatetime);
            $this->_createVacancy($store->id, $datetime->format('Y-m-d'), $datetime->format('H:i'), 3, 3);
        }

        return $this->post('/admin/reservation/update_reservation_info', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'reservation_status' => ($appCd == 'TO') ? 'ENSURE' : 'RESERVE',
            'pick_up_datetime' => $newPickUpDatetime,
            'persons' => '3',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callClearAdminChangeInfo($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $this->_createTmpAdminChangeReservation($cmThApplicationDetail->application_id);
        return $this->post('/admin/reservation/clear_admin_change_info', [
            'reservationId' => $cmThApplicationDetail->application_id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callClearAdminChangeInfoThrowable($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu);
        $this->_createTmpAdminChangeReservation($cmThApplicationDetail->application_id);
        return $this->post('/admin/reservation/clear_admin_change_info', [
            'reservationId' => [$cmThApplicationDetail->application_id],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCancelReservation($store, $menu, $appCd, $addPaymentToken, $callBackValues, &$cmThApplicationDetail, $datetime = '2099-10-01 09:00:00')
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $appCd, $datetime, 'AUTH', 2500);
        $this->_createPaymentDetail($cmThApplicationDetail->application_id, 2500, 2);
        if ($addPaymentToken) {
            $this->_createPaymentToken($cmThApplicationDetail->cm_application_id, $cmThApplicationDetail->application_id, $callBackValues);
        }
        return $this->post('/admin/reservation/cancel_reservation', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCancelReservationForUser($store, $menu, $paymentStaus, &$cmThApplicationDetail, &$cancelFee = null, $addCancelFee = true)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', $paymentStaus, 2500);
        if ($addCancelFee) {
            $cancelFee = $this->_createCancelFee($store->id, $menu->app_cd);
        }
        return $this->post('/admin/reservation/cancel_reservation_for_user', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callCancelReservationForAdmin($store, $menu, $paymentStaus, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', $paymentStaus, 2500);
        return $this->post('/admin/reservation/cancel_reservation_for_admin', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callUpdateDelegateInfo($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
        return $this->post('/admin/reservation/update_delegate_info', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'first_name' => '名更新',
            'last_name' => '姓更新',
            'tel' => '0312345678',
            'email' => 'gourmet-test1234@adventure-inc.co.jp',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callUpdateDelegateInfoException($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
        return $this->post('/admin/reservation/update_delegate_info', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            'first_name' => ['名更新'],
            'last_name' => '姓更新',
            'tel' => '0312345678',
            'email' => 'gourmet-test1234@adventure-inc.co.jp',
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSendReservationMail($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
        return $this->post('/admin/reservation/send_reservation_mail', [
            'reservation_id' => $cmThApplicationDetail->application_id,
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callSendReservationMailException($store, $menu, &$cmThApplicationDetail)
    {
        $cmThApplicationDetail = $this->_createReservation($store, $menu, $menu->app_cd, '2099-10-01 09:00:00', 'PAYED', 2500);
        return $this->post('/admin/reservation/send_reservation_mail', [
            'reservation_id' => [$cmThApplicationDetail->application_id],
            '_token' => $this->makeSessionToken(),       // CSRF用ミドルウェアに引っかからないようにtoken発行して設定
        ]);
    }

    private function _callRedirectEditForm($cmThApplicationDetailId)
    {
        return $this->get('/admin/reservation/edit/cm_application/' . $cmThApplicationDetailId);
    }
}
