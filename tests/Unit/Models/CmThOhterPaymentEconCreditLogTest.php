<?php

namespace Tests\Unit\Models;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmThOhterPaymentEconCreditLog;
use App\Models\CmTmUser;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmThOhterPaymentEconCreditLogTest extends TestCase
{
    private $cmThOhterPaymentEconCreditLog;
    private $testCmApplicationId;
    private $testUserId;
    private $testOhterPaymentEconCreditLogId;
    private $testCmThOhterPaymentEconCreditLogId;
    private $testReservationId;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->cmThOhterPaymentEconCreditLog = new CmThOhterPaymentEconCreditLog();
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testCmThApplicationDetail()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        $testCmApplicationId = $this->testCmApplicationId;
        $result = $this->cmThOhterPaymentEconCreditLog::whereHas('cmThApplicationDetail', function ($query) use ($testCmApplicationId) {
            $query->where('cm_application_id', $testCmApplicationId);
        })->get();
        $this->assertIsObject($result);
        $this->assertSame(1, $result->count());
        $this->assertSame($this->testCmThOhterPaymentEconCreditLogId, $result[0]['id']);
    }

    public function testGetValueAttribute()
    {
        $str = '{"price_list":[{"application_id":0,"cm_application_id":1234567890,"service_cd":"gm"}],"session_id":"testtesttesttest"}';
        $result = $this->cmThOhterPaymentEconCreditLog->getValueAttribute($str);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('price_list', $result);
        $this->assertCount(1, $result['price_list']);
        $this->assertCount(3, $result['price_list'][0]);
        $this->assertSame(0, $result['price_list'][0]['application_id']);
        $this->assertSame(1234567890, $result['price_list'][0]['cm_application_id']);
        $this->assertSame('gm', $result['price_list'][0]['service_cd']);
        $this->assertArrayHasKey('session_id', $result);
        $this->assertSame('testtesttesttest', $result['session_id']);
    }

    public function testBeforeSave()
    {
        $this->_createCmThOhterPaymentEconCreditLog(false);

        session()->put('payment.user_id', $this->testUserId);
        $params = [
            'amount' => 1000,
            'fee' => 100,
            'cm_application_id' => $this->testCmApplicationId,
            'order_id' => "gm-cc-{$this->testCmApplicationId}-test",
            'session_token' => 'testsessiontoken1234567890',
            'keijou' => '',
        ];
        $this->cmThOhterPaymentEconCreditLog->beforeSave($params);

        $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->get();
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testUserId, $result[0]['user_id']);
        $this->assertSame('1000.000', $result[0]['price']);
        $this->assertSame('100.000', $result[0]['fee']);
        $this->assertSame("gm-cc-{$this->testCmApplicationId}-test", $result[0]['order_id']);
        $this->assertSame('testsessiontoken1234567890', $result[0]['session_token']);
    }

    public function testSaveResultData()
    {
        $this->_createCmThOhterPaymentEconCreditLog(false);

        $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog;
        $cmThOhterPaymentEconCreditLog->user_id = $this->testUserId;
        $cmThOhterPaymentEconCreditLog->other_payment_type_id = 13;     // const.payment.payment_type.econ.CREDIT
        $cmThOhterPaymentEconCreditLog->session_token = 'testsessiontoken1234567890';
        $cmThOhterPaymentEconCreditLog->order_id = "gm-cc-{$this->testCmApplicationId}-test";
        $cmThOhterPaymentEconCreditLog->status = 'success';
        $cmThOhterPaymentEconCreditLog->price = '1000';
        $cmThOhterPaymentEconCreditLog->fee = '100';
        $cmThOhterPaymentEconCreditLog->value = '{"price_list":[{"application_id":0,"cm_application_id":' . $this->testCmApplicationId . ',"service_cd":"gm"}],"session_id":"testtesttesttest"}';
        $this->assertTrue($cmThOhterPaymentEconCreditLog->saveResultData());
    }

    public function testGetByReservationId()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        $orderId = "gm-cc-{$this->testCmApplicationId}-test";

        // 引数orderIdなし
        $result = $this->cmThOhterPaymentEconCreditLog->getByReservationId($this->testReservationId);
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        $this->assertSame($this->testCmApplicationId, $result[0]['cm_application_id']);
        $this->assertSame($orderId, $result[0]['order_id']);

        // 引数orderIdあり
        $result = $this->cmThOhterPaymentEconCreditLog->getByReservationId($this->testReservationId, $orderId);
        $this->assertIsObject($result);
        $this->assertCount(1, $result);
        $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        $this->assertSame($this->testCmApplicationId, $result[0]['cm_application_id']);
    }

    public function testGetStatusStrAttribute()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        // 決済キャンセル
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '2022-10-01 09:00:00';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getStatusStrAttribute();
            $this->assertSame('決済キャンセル', $result);
        }

        // 決済正常完了
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 1;
            $cmThOhterPaymentEconCreditLog->info_code = '00000';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getStatusStrAttribute();
            $this->assertSame('決済正常完了', $result);
        }

        // 決済通知待ち
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 0;
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getStatusStrAttribute();
            $this->assertSame('決済通知待ち', $result);
        }

        // 仮入金?
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = '99999';
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getStatusStrAttribute();
            $this->assertNull($result);
        }

        // 決済失敗
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = '1';
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getStatusStrAttribute();
            $this->assertSame('決済失敗', $result);
        }
    }

    public function testGetKeijouStrAttribute()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        // 計上
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->keijou = '1';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getKeijouStrAttribute();
            $this->assertSame('計上', $result);
        }

        // 与信
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->keijou = '0';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getKeijouStrAttribute();
            $this->assertSame('与信', $result);
        }
    }

    public function testScopeeLogStatus()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        // 決済正常完了
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 1;
            $cmThOhterPaymentEconCreditLog->info_code = '00000';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->logStatus('success')->get();
            $this->assertIsObject($result);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        }

        // 決済通知待ち
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 0;
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->logStatus('hold')->get();
            $this->assertIsObject($result);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        }

        // 決済失敗
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = '4';
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->logStatus('error')->get();
            $this->assertIsObject($result);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        }

        // 決済キャンセル
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '2022-10-01 09:00:00';
            $cmThOhterPaymentEconCreditLog->status = 0;
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->logStatus('cancel')->get();
            $this->assertIsObject($result);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        }

        // ステータス指定なし
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '2022-10-01 09:00:00';
            $cmThOhterPaymentEconCreditLog->status = 0;
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->save();

            $result = $this->cmThOhterPaymentEconCreditLog::where('cm_application_id', $this->testCmApplicationId)->logStatus('')->get();
            $this->assertIsObject($result);
            $this->assertCount(1, $result);
            $this->assertSame($this->testOhterPaymentEconCreditLogId, $result[0]['other_payment_econ_credit_log_id']);
        }
    }

    public function testGetIsYoshinCancelAttribute()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        // 与信キャンセルである
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->keijou = 0;
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getIsYoshinCancelAttribute();
            $this->assertTrue($result);
        }

        // 与信キャンセルではない
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->keijou = 1;
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getIsYoshinCancelAttribute();
            $this->assertFalse($result);
        }
    }

    public function testGetCanCardCaptureAttribute()
    {
        $this->_createCmThOhterPaymentEconCreditLog();

        // 決済正常完了
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 1;
            $cmThOhterPaymentEconCreditLog->info_code = '00000';
            $cmThOhterPaymentEconCreditLog->keijou = 0;
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getCanCardCaptureAttribute();
            $this->assertTrue($result);
        }

        // 決済正常完了以外（決済通知待ち)
        {
            $cmThOhterPaymentEconCreditLog = $this->cmThOhterPaymentEconCreditLog::find($this->testOhterPaymentEconCreditLogId);
            $cmThOhterPaymentEconCreditLog->cancel_dt = '0000-00-00 00:00:00';
            $cmThOhterPaymentEconCreditLog->status = 0;
            $cmThOhterPaymentEconCreditLog->info_code = '';
            $cmThOhterPaymentEconCreditLog->keijou = 0;
            $cmThOhterPaymentEconCreditLog->save();

            $result = $cmThOhterPaymentEconCreditLog->getCanCardCaptureAttribute();
            $this->assertFalse($result);
        }
    }

    private function _createCmThOhterPaymentEconCreditLog($addCmThOhterPaymentEconCreditLog = true)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->save();
        $this->testReservationId = $reservation->id;

        $userId = CmTmUser::createUserForPayment();
        $this->testUserId = $userId;
        $CmThApplication = new CmThApplication();
        $CmThApplication->user_id = $this->testUserId;
        $CmThApplication->lang_id = 1;
        $CmThApplication->save();
        $this->testCmApplicationId = $CmThApplication->cm_application_id;

        $cmThApplicationDetail = new CmThApplicationDetail();
        $cmThApplicationDetail->service_cd = 'gm';
        $cmThApplicationDetail->cm_application_id = $this->testCmApplicationId;
        $cmThApplicationDetail->application_id = $this->testReservationId;
        $cmThApplicationDetail->save();
        $this->testCmThApplicationDetailId = $cmThApplicationDetail->id;

        if ($addCmThOhterPaymentEconCreditLog) {
            $cmThOhterPaymentEconCreditLog = new CmThOhterPaymentEconCreditLog();
            $cmThOhterPaymentEconCreditLog->user_id = $userId;
            $cmThOhterPaymentEconCreditLog->cm_application_id = $this->testCmApplicationId;
            $cmThOhterPaymentEconCreditLog->order_id = "gm-cc-{$this->testCmApplicationId}-test";
            $cmThOhterPaymentEconCreditLog->save();
            $this->testOhterPaymentEconCreditLogId = $cmThOhterPaymentEconCreditLog->other_payment_econ_credit_log_id;
        }
    }


}
