<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Libs\Mail\RestaurantMail;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendQuestionnaireMail extends Command
{
    use BaseCommandTrait;

    private $className;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:questionnaireMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send restaurant questionnairemail';

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
            //現在時刻から2時間前の予約データを取得
            $reservations = Reservation::where('pick_up_datetime', Carbon::now()->subHours(2)->format('Y-m-d H:i'))
                ->where('reservation_status', config('code.reservationStatus.ensure.key'))
                ->where('app_cd', key(config('code.appCd.rs')))
                ->where('is_close', 1)
                ->whereNull('cancel_datetime')
                ->get();

            foreach ($reservations as $reservation) {
                $restaurantMail = new RestaurantMail($reservation->id);
                $restaurantMail->questionnaireForUser();
            }
        } catch (\Throwable $e) {
            $title = 'ご予約の御礼送信バッチで例外発生';
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
