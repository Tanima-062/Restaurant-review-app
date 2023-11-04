<?php

namespace App\Console\Commands;

use App\Libs\CommonLog;
use App\Models\ExternalApi;
use App\Models\Reservation;
use App\Modules\Ebica\EbicaSearch;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckEbicaStatus extends Command
{
    use BaseCommandTrait;

    private $className;
    private $ebicaSearch;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:ebica-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'notify if reservation status differs from that of Ebica';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        EbicaSearch $ebicaSearch
    ) {
        parent::__construct();
        $this->className = $this->getClassName($this);
        $this->ebicaSearch = $ebicaSearch;
    }

    /**
     * Execute the console command.
     *
     * @return true:成功 false:失敗
     */
    public function handle(PaymentService $paymentService, ReservationService $reservationService)
    {
        $this->start();

        $result = false;

        try {
            $dt = new Carbon();
            $since = $dt->copy()->format('Y-m-d');
            $until = $dt->copy()->addMonth()->format('Y-m-d');

            $externalApis = ExternalApi::where('api_cd', config('code.externalApiCd.ebica'))->get();

            // 店舗単位でAPI実行
            foreach ($externalApis as $externalApi) {
                // ebicaAPI実行
                $ebicaReservations = $this->ebicaSearch->getReservation(
                    [
                    'shop_id' => $externalApi->api_store_id,
                    'since' => $since,
                    'until' => $until,
                    ]
                );
                if (empty($ebicaReservations)) {
                    continue;
                }

                $reservationIds = [];
                array_walk_recursive($ebicaReservations['reservations'], function ($val, $key) use (&$reservationIds) {
                    if ($key == 'reservation_id') {
                        $reservationIds[] = $val;
                    }
                });

                foreach ($ebicaReservations['reservations'] as $er) {
                    $reservation = Reservation::where('external_reservation_id', $er['reservation_id'])->first();

                    //  ebicaには予約が入っているがskyticketには予約がない場合
                    if (empty($reservation)) {
                        sleep(1);
                        CommonLog::notifyToChat(
                            'Ebica予約ID:'.$er['reservation_id'].' ebica側との予約ステータス不整合',
                            sprintf('EbicaReservationId:%s  ebicaStatus:[%s] gourmet側の予約が見つかりませんでした。', $er['reservation_id'], $er['reservation_status'])
                        );
                        continue;
                    }

                    // 申込の場合
                    if ($reservation->reservation_status === config('code.reservationStatus.reserve.key')) {
                        // ebicaではreservation(予約中)であること
                        if ($er['reservation_status'] === config('restaurant.ebica.reservationStatus.reservation.key')) {
                            continue;
                        }
                        // 受注確定の場合
                    } elseif ($reservation->reservation_status === config('code.reservationStatus.ensure.key')) {
                        // ebicaではvisiting(来店中)であること
                        if ($er['reservation_status'] === config('restaurant.ebica.reservationStatus.visiting.key')) {
                            continue;
                        }
                        // 来店日過ぎた場合のみグルメ側成約とebica側予約中の組み合わせを許可する
                        $pickUpDatetime = new Carbon($reservation->pick_up_datetime);
                        if ($pickUpDatetime->lte($dt) && $er['reservation_status'] === config('restaurant.ebica.reservationStatus.reservation.key')) {
                            continue;
                        }

                        // キャンセルの場合
                    } elseif ($reservation->reservation_status === config('code.reservationStatus.cancel.key')) {
                        // ebicaのAPIでは取得できてないこと
                        if (!in_array($reservation->id, $reservationIds)) {
                            continue;
                        }
                    }
                    // chatworkAPI 秒間1リクエストの制限があるので
                    sleep(1);
                    // ステータスのズレが生じた旨を通知する
                    CommonLog::notifyToChat(
                        '予約ID:'.$reservation->id.' ebica側との予約ステータス不整合',
                        sprintf('reservationId:%s => gourmet:[%s] ≠ ebica:[%s]', $reservation->id, $reservation->reservation_status, $er['reservation_status'])
                    );
                }
            }
        } catch (\Exception $e) {
            $title = 'ebica側との予約ステータスチェック例外発生';
            if (isset($reservation->id)) {
                $title = '予約ID:'.$reservation->id.' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );

            return false;
        }

        $this->end();

        return true;
    }
}
