<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Libs\Mail\TakeoutMail;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemindLeaveOrder extends Command
{
    use BaseCommandTrait;

    private $className;
    const CACHE_TIME = 3600; // 1h

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remind:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notice mail of leave order';

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

        try {
            // 未成約で今から30分以上経ったら来店時間を過ぎてしまい かつ 受注確定ボタンを押してない場合
            $reservations = Reservation::stillNotClose(Carbon::now()->addMinutes(30))
                ->where('pick_up_datetime', '>=', Carbon::now()->format('Y-m-d H:i:s'))
                ->where('reservation_status', config('code.reservationStatus.reserve.key'))->get();
            foreach ($reservations as $reservation) {
                // リマインドは1度送ればいいので、キャッシュしておき一度送ったものは再送しないようにする(ただし一定時間立つとキャッシュはクリアされる)
                if (\Cache::has('leave_'.$reservation->id)) {
                    continue;
                }

                $storeEmails = [];
                $store = $reservation->reservationStore->store;

                if (!is_null($store->email_1)) {
                    $storeEmails[] = $store->email_1;
                }
                if (!is_null($store->email_2)) {
                    $storeEmails[] = $store->email_2;
                }
                if (!is_null($store->email_3)) {
                    $storeEmails[] = $store->email_3;
                }

                foreach ($storeEmails as $storeEmail) {
                    $takeoutMail = new TakeoutMail($reservation->id);
                    $takeoutMail->remindReservationForClient($storeEmail);
                }
                \Cache::put('leave_'.$reservation->id, 1, self::CACHE_TIME);
            }
        } catch (\Exception $e) {
            $title = '受注リマインドメール送信バッチで例外発生';
            if (isset($reservation->id)) {
                $title = '予約ID:'.$reservation->id.' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
        }

        $this->end();

        return;
    }
}
