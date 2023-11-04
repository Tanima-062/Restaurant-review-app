<?php

namespace App\Libs\Fax;

use App\Libs\CommonLog;
use App\Models\FaxSendJob;
use App\Models\Reservation;
use App\Models\ReservationStore;
use App\Models\Store;
use Carbon\Carbon;

class Fax
{
    /**
     * @param $reservationId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function store($reservationId)
    {
        try {
            $reservationStore = ReservationStore::where('reservation_id', $reservationId)->first();
            $store = Store::find($reservationStore->store_id);
            if (!$store->use_fax) {
                return true;
            }

            if ($store->use_fax && empty($store->fax)) {
                throw new \Exception('not found fax no:store id = '.$reservationStore->id.', reservation id = '.$reservationId);
            }

            $reservation = Reservation::with(['reservationMenus.reservationOptions', 'reservationStore'])->find($reservationId);

            $pdf = \App::make('snappy.pdf.wrapper');

            // bladeのテンプレートを予約データ入りで読み込む
            $view = $pdf->loadView('pdf.reserve', ['reservation' => $reservation]);

            $pdfName = $reservationId.'_reserve_'.date('His').'.pdf';

            // 直接snappyのオブジェクトをgcsに入れられないので一旦ローカルに落とす
            $path = sprintf(env('GOOGLE_CLOUD_STORAGE_PDF_PATH_PREFIX','')."%s/%s", date('Ymd'), $pdfName);
            \Storage::put($path, $view->download('not_use_name.pdf'));

            // ローカルから読み込む
            $file = \Storage::get($path);

            // gcsにup
            \Storage::disk('gcs')->put($path, $file);

            // ローカルから削除
            \Storage::delete($path);

            $reservationStore = ReservationStore::where('reservation_id', $reservationId)->first();
            $store = Store::find($reservationStore->store_id);
            $faxNo = str_replace('-', '', $store->fax);

            $userkey = 'to'.$reservationId.time();

            // 予約状態を上書き保存
            $reservation->reservation_status = config('code.reservationStatus.ensure.key');
            $reservation->save();

            FaxSendJob::create([
                'status' => config('code.faxStatus.ready'),
                'reservation_id' => $reservationId,
                'to_address' => $faxNo,
                'userkey' => $userkey,
                'header' => json_encode(Faximo::makeHeader($reservationId)), // faxメタ情報作成(header)
                'body' => json_encode(Faximo::makeBody($faxNo, $userkey, $reservationId, $pdfName)), // faxメタ情報作成(body)
                'attachment_path' => $path,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            $title = 'fax送信で例外発生';
            $title = '予約ID:'.$reservationId.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );

            throw $e;
        }
    }

    public static function send(FaxSendJob $job)
    {
        $faximo = new Faximo();
        $faximo->setHeader(json_decode($job->header, true));
        $faximo->setBody(json_decode($job->body, true));

        $url = \Storage::disk('gcs')->url($job->attachment_path);
        $pdf = file_get_contents($url);
        $faximo->setAttachmentStr(base64_encode($pdf));

        $result = $faximo->send();

        if ($result) {
            $job->status = config('code.faxStatus.delivered');
            $job->idxcnt = $faximo->getResponseIdxcnt();
        } elseif (!$result && $job->status == config('code.faxStatus.ready')) {
            $job->status = config('code.faxStatus.retry');
        } else {
            $job->status = config('code.faxStatus.failed');
        }

        $job->result_code = $faximo->getResponseStatus();
        $job->response_data = $faximo->getResponse();

        $job->save();
    }
}
