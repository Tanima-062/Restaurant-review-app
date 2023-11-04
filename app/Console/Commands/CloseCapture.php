<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\Reservation;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CloseCapture extends Command
{
    use BaseCommandTrait;

    private $className;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'close:capture';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'change reservation status from RESERVE to CLOSE and payment status from auto to capture';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
    }

    /**
     * Execute the console command.
     *
     * @param PaymentService paymentService
     * @param ReservationService reservationService
     *
     * @return mixed
     */
    public function handle(PaymentService $paymentService, ReservationService $reservationService)
    {
        $this->start();

        // Econ計上処理
        // TODO::現在は決済基盤を利用しているため、本バッチは不要と思われる
        try {
            $reservations = Reservation::stillNotClose(Carbon::now())->get();

            foreach ($reservations as $reservation) {
                if ($reservation->reservation_status == config('code.reservationStatus.cancel.key')) {
                    continue;
                }
                $reservation->reservation_status = config('code.reservationStatus.ensure.key');
                if (!$reservation->store_reception_datetime) {
                    $reservation->store_reception_datetime = Carbon::now();
                }
                $reservation->payment_status = config('code.paymentStatus.payed.key');
                $reservation->is_close = 1;

                $result = $paymentService->cardCapture($reservation->id, $reservation->total);
                if (!$result) {
                    throw new \Exception('reservation id['.$reservation->id.'] capture fail');
                }
                if ($paymentService->getIsEconUsed()) {
                    $reservation->save();
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            if (\App::environment('production')) {
                $title = 'Econ計上処理で例外発生';
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

        return true;
    }
}
