<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\RestaurantReservationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Libs\CommonLog;

class CancelReservation extends Command
{
    use BaseCommandTrait;

    private $className;
    private $restaurantReservationService;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:reservation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel expired reservation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RestaurantReservationService $restaurantReservationService)
    {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->restaurantReservationService = $restaurantReservationService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->start();

        $this->process();

        $this->end();

        return;
    }

    public function process()
    {
        try {
            $msg = '';
            $reservations = Reservation::where('payment_limit', '<=', Carbon::now())
                ->where('payment_status', config('code.paymentStatus.wait_payment.key'))
                ->whereNull('cancel_datetime')
                ->get();
            foreach ($reservations as $reservation) {
                $pickUpDatetime = new Carbon($reservation->pick_up_datetime);
                $paymentLimit = new Carbon($reservation->payment_limit);
                if ($pickUpDatetime > $paymentLimit) {
                    $this->restaurantReservationService->adminCancel($reservation->id, $msg);
                }
            }
        } catch (\Throwable $e) {
            $title = '自動キャンセルバッチで例外発生';
            if (isset($reservation->id)) {
                $title = '予約ID:'.$reservation->id.' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
        }
    }
}
