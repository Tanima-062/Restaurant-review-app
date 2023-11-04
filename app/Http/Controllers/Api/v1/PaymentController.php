<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    public function yoshinCancel(Request $request, PaymentSkyticket $paymentSkyticket)
    {
        $result = null;

        if ($paymentSkyticket->cancelPayment($request->input('orderCode'), $result)) {
            if(!empty($request->input('reservationId'))){
                // 予約情報更新
                $reservation = Reservation::find($request->input('reservationId'));
                $reservation->cancel_datetime = new Carbon();
                $reservation->reservation_status = config('code.reservationStatus.cancel.key');
                $reservation->payment_status = config('code.paymentStatus.cancel.key');
                $reservation->save();

            }
            return response()->json(['ret' => 'ok']);
        } else {
            return response()->json(['message' => 'キャンセル失敗']);
        }
    }

    public function cardCapture(Request $request, PaymentSkyticket $paymentSkyticket)
    {
        $result = null;
        if ($paymentSkyticket->settlePayment($request->input('orderCode'), $result)) {
            if(!empty($request->input('reservationId'))){
                // 予約情報更新
                $reservation = Reservation::find($request->input('reservationId'));
                if (!$reservation->store_reception_datetime) {
                    $reservation->store_reception_datetime = Carbon::now();
                }
                $reservation->reservation_status = config('code.reservationStatus.ensure.key');
                $reservation->payment_status = config('code.paymentStatus.payed.key');
                $reservation->is_close = 1;
                $reservation->save();

            }
            return response()->json(['ret' => 'ok']);
        } else {
            return response()->json(['ret' => 'ng', 'message' => '計上失敗']);
        }
    }

    public function reviewPayment(Request $request, PaymentSkyticket $paymentSkyticket)
    {
        $reservation = Reservation::find($request->reservationId);
        if ($reservation->app_cd == key(config('code.appCd.to'))) {
            $serviceCd = strtolower(key(config('code.appCd.to')));
        } else {
            $serviceCd = strtolower(key(config('code.appCd.rs')));
        }
        $result = $paymentSkyticket->getPaymentList(['reservationId' => $request->input('reservationId'), 'serviceCd' => $serviceCd]);
        \Log::info($result);
        if(!empty($result)){
            return response()->json(['ret' => 'ok', 'data' => $result]);
        } else {
            return response()->json(['ret' => 'ng', 'message' => '取得失敗']);
        }
    }
}
