<?php

namespace Tests\Unit\Commands;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmTmUser;
use App\Models\MailDBQueue;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class SendQuestionnaireMailTest extends TestCase
{
    private $testReservationId;
    private $testCmApplicationId;

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

    public function testSendQuestionnaireMail()
    {
        // テスト１（正常)
        {
            // テストデータ用意
            $email = 'gourmet-test1@adventure-inc.co.jp';
            $this->_createReservation($email);

            // 事前確認
            $this->assertFalse(MailDBQueue::where('service_cd', 'gm')
                ->where('application_id', $this->testReservationId)
                ->where('cm_application_id', $this->testCmApplicationId)
                ->exists());

            // バッチ実行
            $this->artisan('send:questionnaireMail')
                ->expectsOutput('[SendQuestionnaireMail] ##### START #####')
                ->expectsOutput(0);

            // メール送信用テーブルにセットされること
            $result = MailDBQueue::where('service_cd', 'gm')
                ->where('application_id', $this->testReservationId)
                ->where('cm_application_id', $this->testCmApplicationId)->first();
            $this->assertNotNull($result);
            $this->assertSame($email, $result->to_address_enc);
            $this->assertSame(Lang::get('message.mail.restaurantQuestionnaireForUser'), $result->subject);
        }

        // テスト2（例外エラー)
        {
            // テストデータ用意
            $email = 'gourmet-test2@adventure-inc.co.jp';
            $this->_createReservation($email, true);

            // 事前確認
            $this->assertFalse(MailDBQueue::where('service_cd', 'gm')
                ->where('application_id', $this->testReservationId)
                ->exists());

            // バッチ実行
            $this->artisan('send:questionnaireMail')
                ->expectsOutput('[SendQuestionnaireMail] ##### START #####')
                ->expectsOutput(0);

            // メール送信用テーブルにセットされていない
            $this->assertFalse(MailDBQueue::where('service_cd', 'gm')
                ->where('application_id', $this->testReservationId)
                ->exists());
        }
    }

    private function _createReservation($email, $error = false)
    {
        $store = new Store();
        $store->save();

        $menu = new Menu();
        $menu->store_id = $store->id;
        $menu->save();

        $reservation = new Reservation();
        $reservation->app_cd = 'RS';
        $reservation->reservation_status = 'ENSURE';
        $reservation->pick_up_datetime = Carbon::now()->subHours(2)->format('Y-m-d H:i');
        $reservation->is_close = 1;
        $reservation->cancel_datetime = null;
        $reservation->email = $email;
        $reservation->save();
        $this->testReservationId = $reservation->id;

        // エラー用フラグがOFF
        if (!$error) {
            $reservationStore = new ReservationStore();
            $reservationStore->store_id = $store->id;
            $reservationStore->reservation_id = $this->testReservationId;
            $reservationStore->save();

            $reservationMenu = new ReservationMenu();
            $reservationMenu->menu_id = $menu->id;
            $reservationMenu->reservation_id = $this->testReservationId;
            $reservationMenu->save();

            $userId = CmTmUser::createUserForPayment();
            $CmThApplication = new CmThApplication();
            $CmThApplication->user_id = $userId;
            $CmThApplication->lang_id = 1;
            $CmThApplication->save();
            $this->testCmApplicationId = $CmThApplication->cm_application_id;

            $cmThApplicationDetail = new CmThApplicationDetail();
            $cmThApplicationDetail->service_cd = 'gm';
            $cmThApplicationDetail->cm_application_id = $this->testCmApplicationId;
            $cmThApplicationDetail->application_id = $this->testReservationId;
            $cmThApplicationDetail->save();
        }
    }
}
