<?php

namespace App\Modules\Reservation;

use App\Libs\Mail\TakeoutMail;
use App\Models\CancelDetail;
use App\Models\Refund;
use App\Models\Reservation;
use App\Services\PaymentService;
use Carbon\Carbon;

class TakeoutCancel implements IFCancel
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    private function todayYmd()
    {
        return Carbon::now()->format('Y-m-d');
    }

    private function specifiedYmd(string $datetime)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $datetime)->format('Y-m-d');
    }

    /**
     * @param Reservation $reservation
     * @throws \Exception
     */
    public function cancel(Reservation $reservation)
    {
        \Log::debug('takeout cancel reservation id : '.$reservation->id);

        // 当日キャンセルだったら計上してしまう
        if ($this->todayYmd() == $this->specifiedYmd($reservation->pick_up_datetime) &&
            $reservation->reservation_status == config('code.reservationStatus.ensure.key') &&
            !$this->paymentService->cardCapture($reservation->id, $reservation->total)
        ) {
            throw new \Exception('cardCapture fail [reservation_id:'.$reservation->id.']');
        }

        $sumCancelFee = $this->cancelCalc($reservation);

        $refundAmount = $reservation->paymentDetails->sum('price') - $sumCancelFee;

        \Log::debug('sumCancelFee : '.$sumCancelFee.', refundAmount : '.$refundAmount.', payment_status : '.$reservation->payment_status);

        if ($refundAmount > 0 && $reservation->payment_status == config('code.paymentStatus.payed.key')) { // 計上キャンセル
            Refund::create([
                'reservation_id' => $reservation->id,
                'price' => $refundAmount,
                'status' => config('code.refundStatus.scheduled.key'),
                'created_at' => Carbon::now()
            ]);

            if (!$this->paymentService->refund($reservation->id, $refundAmount)) {
                throw new \Exception('refund fail [reservation_id:'.$reservation->id.']');
            }
        } elseif ($reservation->payment_status == config('code.paymentStatus.auth.key')) { // 与信キャンセル
            if (!$this->paymentService->cancelCreditByReservationId($reservation->id)) {
                throw new \Exception('auth cancel fail [reservation_id:'.$reservation->id.']');
            }
        }

        $reservation->reservation_status = config('code.reservationStatus.cancel.key');
        $reservation->payment_status = config('code.paymentStatus.cancel.key');
        $reservation->cancel_datetime = Carbon::now()->format('Y-m-d H:i:s');
        $reservation->save();

        $mail = new TakeoutMail($reservation->id);
        $mail->cancelReservationForUser();
    }

    private function cancelCalc(Reservation $reservation)
    {
        $sum = 0;
        foreach ($reservation->paymentDetails as $paymentDetail) {
            if ($this->todayYmd() >= $this->specifiedYmd($reservation->pick_up_datetime)) {
                $cancelFee = $paymentDetail->price * (config('takeout.cancelFeeRatio') / 100);
            } else {
                $cancelFee = $paymentDetail->price * 0;
            }

            CancelDetail::create([
                'reservation_id' => $paymentDetail->reservation_id,
                'target_id' => $paymentDetail->target_id,
                'account_code' => $paymentDetail->account_code,
                'price' => $cancelFee,
                'count' => $paymentDetail->count,
                'remarks' => '自動',
            ]);
            $sum += ($cancelFee * $paymentDetail->count);
        }

        return $sum;
    }
}
