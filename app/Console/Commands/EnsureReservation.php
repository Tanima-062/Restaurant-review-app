<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnsureReservation extends Command
{
    use BaseCommandTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ensure:reservation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'seatOnly reservation change reservation status from RESERVE to ENSURE';

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
            $reservations = Reservation::where('pick_up_datetime', '<=', Carbon::now())
                ->where('app_cd', key(config('code.appCd.rs')))
                ->where('payment_status', config('code.paymentStatus.unpaid.key'))
                ->where('is_close', 0)
                ->where('total', 0)
                ->whereNull('cancel_datetime')
                ->whereNull('payment_method')
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->reservation_status = config('code.reservationStatus.ensure.key');
                $reservation->is_close = 1;
                $reservation->save();
            }
        } catch (\Throwable $e) {
            $title = '席のみ予約ステータス受注確定バッチで例外発生';
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
