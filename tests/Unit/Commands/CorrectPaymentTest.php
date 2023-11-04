<?php

namespace Tests\Unit\Commands;

use App\Models\PaymentToken;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorrectPaymentTest extends TestCase
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

    public function testCancelReservation()
    {
        $this->_createPaymentToken();                                           // skipテストデータ追加
        $this->_createPaymentToken('{"cartId":"","OrderCode":"to-cc-test"}');   // skipテストデータ追加

        $paymentToken = PaymentToken::find(2754);   // 既存データを利用（skipテストデータ）
        $paymentToken->reservation_id = null;
        $paymentToken->is_checked = 0;
        $paymentToken->call_back_values = '{"token":"JDJ5JDEwJDBRcUxWVmdMNS9PRENQeHdIVzdPVC5CcWpVWGczanYwUXFycHZHYUhMemFGekwwNUh3bXFl","paymentId":"34533","cartId":"1000-0011-3662-00","serviceCd":"rs","code":"success","message":"\u6210\u529f","paymentMethod":"gmo_credit","currency":"JPY","rate":"1","receiveDt":"2022-09-30 10:03:28","duetDate":"2022-09-30 10:03:28","isMember":"1","orderCode":"","totalPrice":"17000","totalOtherPrice":"17000","fee":"0","otherFee":"0","userId":"10638910","details":[{"cmApplicationId":"743534","serviceCd":"rs","price":"17000","otherPrice":"17000","point":"0","otherPoint":"0"}]}';//OrderCodeを空に
        $paymentToken->save();

        $paymentToken = PaymentToken::find(2668);   // 既存データを利用(与信データなので将来的に変更されてしまうとここの部分だけうまくテストできないかも？)
        $paymentToken->reservation_id = null;
        $paymentToken->is_checked = 0;
        $paymentToken->save();

        $paymentToken = PaymentToken::find(2755);   // 既存データを利用
        $paymentToken->reservation_id = null;
        $paymentToken->is_checked = 0;
        $paymentToken->save();

        // バッチ実行
        $this->artisan('correct:payment')
            ->expectsOutput('[CorrectPayment] ##### START #####')
            ->expectsOutput(0);

        // paymentTokenのフラグが書き変わっていないか確認
        $paymentToken = PaymentToken::find(2754);
        $this->assertSame(0, $paymentToken->is_checked);

        // paymentTokenのフラグが書き変わっているか確認
        // ドメイン（APP_URL）がローカルでは成功しない。実行の際は開発環境等のURLで行う必要あり
        $paymentToken = PaymentToken::find(2668);
        $this->assertSame(1, $paymentToken->is_checked);

        $paymentToken = PaymentToken::find(2755);
        $this->assertSame(1, $paymentToken->is_checked);
    }

    private function _createPaymentToken($callBackValues=null)
    {
        $reservation = new Reservation();
        $reservation->app_cd = 'TO';
        $reservation->save();

        $paymentToken = new PaymentToken();
        $paymentToken->reservation_id = $reservation->id;
        $paymentToken->token = 'testtokentesttoken';
        $paymentToken->is_checked = 0;
        $paymentToken->call_back_values = $callBackValues;
        $paymentToken->created_at = Carbon::now()->subHours(5);
        $paymentToken->save();
    }
}
