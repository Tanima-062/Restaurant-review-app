<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\PaymentToken;
use App\Models\Reservation;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixPayment extends Command
{
    use BaseCommandTrait;

    private $className;
    private $paymentSkyticket;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix payment from authorized to paid through Skyticket Payment API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        PaymentSkyticket $paymentSkyticket
    ) {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->paymentSkyticket = $paymentSkyticket;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(PaymentService $paymentService, ReservationService $reservationService)
    {
        $this->start();

        $result = false;

        try {
            // 未計上かつSkyticket Payment API経由で与信した予約を取得
            $paymentTokens = PaymentToken::whereNotNull('reservation_id')
            ->whereHas('reservation', function ($q) {
                $q->where('pick_up_datetime', '<=', Carbon::now());
                $q->where('reservations.payment_status', config('code.paymentStatus.auth.key'));
                $q->where('reservations.is_close', 0);
                $q->whereNull('cancel_datetime');
                $q->where('payment_method', config('const.payment.payment_method.credit'));
            })
            ->whereNotNull('call_back_values')
            ->where('is_invalid', 0)
            ->get();

            foreach ($paymentTokens as $paymentToken) {
                // 予約情報取得
                $reservation = Reservation::find($paymentToken->reservation_id);

                if (!$reservation->store_reception_datetime) {
                    $reservation->store_reception_datetime = Carbon::now();
                }
                $reservation->reservation_status = config('code.reservationStatus.ensure.key');
                $reservation->payment_status = config('code.paymentStatus.payed.key');
                $reservation->is_close = 1;

                // skyticket payment api 計上処理
                $callBackValues = json_decode($paymentToken->call_back_values, true);
                $res = null;
                if ($this->paymentSkyticket->settlePayment($callBackValues['orderCode'], $res)) {
                    // 予約データを更新
                    $result = $reservation->save();
                }
            }
        } catch (\Exception $e) {
            if (\App::environment('production')) {
                $title = '新決済計上バッチで例外発生';
                if (isset($reservation->id)) {
                    $title = '予約ID:'.$reservation->id.' '.$title;
                }
                CommonLog::notifyToChat(
                    $title,
                    $e->getMessage()
                );
            } else {
                \Log::error($e->getMessage());
            }
        }

        $this->end();

        return $result;
    }
}
