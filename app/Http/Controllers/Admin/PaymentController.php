<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelDetailRequest;
use App\Models\CancelDetail;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Services\PaymentService;
use Auth;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentService $paymentService)
    {
        $payments = collect();
        if (count($request->query()) > 1) {
            $payments = $paymentService->getPaymentList($request->all())
                ->orderBy('create_dt', 'desc')->sortable()->paginate(30);
        }

        return view('admin.Payment.index', ['payments' => $payments]);
    }

    public function detail(PaymentService $paymentService, int $id, PaymentSkyticket $paymentSkyticket)
    {
        // 予約情報、キャンセル情報
        $reservation = Reservation::find($id);

        // 予約情報 - 入金ステータス
        [$defaultPaymentStatus, $paymentStatusSelect] =
            $this->selectPaymentStatus($reservation->payment_status, $reservation->payment_method);

        // クレジット入金情報
        $creditLogs = $paymentService->getPaymentList([
            'payment_method' => config('const.payment_method.credit'),
            'reservation_id' => $reservation->reservation_no,
            'cm_application_id' => -1
        ])->get();

        // 入金明細(搭乗者)
        $paymentDetails = PaymentDetail::where('reservation_id', $id)->get();

        // キャンセル明細
        $cancelDetails = CancelDetail::where('reservation_id', $id)->get();

        // 決済状況
        $payedPrice = 0;        // 入金済み金額
        $refundingPrice = 0;    // 返金予定額
        $refundedPrice = 0;     // 返金金額
        $remainingPrice = 0;    // 残額

        $isNewPayment = 0;

        $paymentToken = PaymentToken::where('reservation_id',$id)->first();
        $pfPaid = $pfRefund = $pfRefunded = 0;
        if(!is_null($paymentToken)){
            $isNewPayment = 1;
            // 入金情報取得
            $callBackValues = json_decode($paymentToken->call_back_values, true);
            $payment = $paymentSkyticket->getPayment(['cartId' => $callBackValues['cartId']]);
            $payedPrice = $pfPaid = $payment['paidPrice'];
            $pfRefund = $payment['refundPrice'];
            $pfRefunded = $payment['refundedPrice'];

            $this->getRefundInfo($reservation->id, $refundingPrice, $refundedPrice);
            $remainingPrice = $payedPrice - $refundedPrice;
        } else {
            $filterCreditLog = $creditLogs->filter(function ($log) {
                return $log->status == 1 && $log->info_code == '00000' && $log->keijou == 1;
            });
            if ($reservation->payment_status != config('code.paymentStatus.auth.key') &&
                $reservation->payment_method != config('code.paymentStatus.credit.key')
            ) {
                // 与信では返金できない+入金もされていない
                foreach ($filterCreditLog as $creditLog) {
                    $ret = $paymentService->inquiry($creditLog->order_id); // 残額を取得
                    if (isset($ret['data']['amount']) && ($ret['data']['status'] == 0 || $ret['data']['status'] == 1)) { // 0:与信取得済、1:計上済
                        $remainingPrice += (int)$ret['data']['amount'];
                    }
                }

                $this->getRefundInfo($reservation->id, $refundingPrice, $refundedPrice);
                $payedPrice = $filterCreditLog->sum('price');
            }

        }

        return view(
            'admin/Payment/detail',
            compact(
                'reservation',
                'creditLogs',
                'paymentDetails',
                'cancelDetails',
                'defaultPaymentStatus',
                'paymentStatusSelect',
                'payedPrice',
                'refundingPrice',
                'refundedPrice',
                'remainingPrice',
                'isNewPayment',
                'pfPaid',
                'pfRefund',
                'pfRefunded'
            )
        );
    }

    public function statusPayment(Request $request)
    {
        $reservation = Reservation::find($request->input('id'));

        $data = [
            'payment_status' => $request->input('payment_status'),
            'staff_id' => Auth::user()->id
        ];

        $reservation->payment_status = $request->input('payment_status');
        $reservation->staff_id = (Auth::user())->id;
        $reservation->save($data);

        return response()->json(['ret' => 'ok']);
    }

    public function cancelDetailAdd(CancelDetailRequest $request, PaymentService $paymentService, PaymentSkyticket $paymentSkyticket)
    {
        $data = $request->only(['reservation_id', 'account_code', 'price', 'count', 'remarks']);
        if (is_null($data['remarks'])) {
            $data['remarks'] = '';
        }

        $data['staff_id'] = (Auth::user())->id;

        try {
            \DB::transaction(function () use ($data, $paymentService, $paymentSkyticket) {

                $cancelFee = $data['price'] * $data['count'];
                $refundingPrice = -1 * $cancelFee; // 返金額はキャンセル料を-反転した額

                // キャンセル料明細、返金テーブルが未登録でpostされたキャンセル料が+のときは一旦全額返金する(ただし、指定したキャンセル料に相当する分は引いておく)
                if ($cancelFee > 0 &&
                    CancelDetail::where('reservation_id', $data['reservation_id'])->count() == 0 &&
                    Refund::where('reservation_id', $data['reservation_id'])->count() == 0
                ) {
                    $paymentToken = PaymentToken::where('reservation_id', $data['reservation_id'])->first();
                    if (is_null($paymentToken)) {
                        $remainingPrice = $paymentService->getRemainingPrice($data['reservation_id']);
                        $refundingPrice = $remainingPrice - $cancelFee;
                    }else{
                        $callBackValues = json_decode($paymentToken->call_back_values, true);
                        $payment = $paymentSkyticket->getPayment(['cartId' => $callBackValues['cartId']]);
                        $refundingPrice = $payment['paidPrice'] - $cancelFee;
                    }


                }

                CancelDetail::create($data);

                Refund::create([
                    'reservation_id' => $data['reservation_id'],
                    'price' => $refundingPrice,
                    'status' => config('code.refundStatus.scheduled.key'),
                ]);
            }, 1);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ret' => 'failure',
                'message' => '',
            ]);
        }

        return response()->json(['ret' => 'ok']);
    }

    public function execRefund(
        Request $request,
        PaymentService $paymentService,
        PaymentSkyticket $paymentSkyticket,
        Refund $refund
        ) {
        $reservationId = $request->input('id');

        $reservation = Reservation::find($reservationId);
        if ($reservation->payment_status != config('code.paymentStatus.payed.key')
        ) {
            $msg = sprintf(
                "入金ステータスを「%s」に変更してから実行してください。",
                config('code.paymentStatus.payed.value')
            );

            return response()->json(['ret' => 'error', 'msg' => $msg]);
        }

        $paymentToken = PaymentToken::where('reservation_id',$reservationId)->where('is_invalid', 0)->first();

        $cancelDetails = CancelDetail::where('reservation_id',$reservation->id)->get();

        $cancelDetailSum = 0;
        foreach($cancelDetails as $cancelDetail){
            $cancelDetailSum += $cancelDetail->price * $cancelDetail->count;
        }

        // econの場合
        if(is_null($paymentToken)){
            // クレジット入金情報
            $creditLogs = $paymentService->getPaymentList([
                'payment_method' => config('const.payment.payment_method.credit'),
                'reservation_id' => $reservation->app_cd.$reservationId,
                'log_status' => 'success',
                'keijou' => 1
            ])->get();

            $systemRemainingPrice = 0;
            $retArr = [];
            foreach ($creditLogs as $creditLog) {
                $ret = $paymentService->inquiry($creditLog->order_id);
                if (!isset($ret['data']['amount'])) {
                    return response()->json(['ret' => 'error', 'msg' => '注文を照会出来ません']);
                }

                if ($ret['data']['status'] == 2 || $ret['data']['status'] == 3) { // 2:与信取消、3:計上取消
                    continue;
                }

                $systemRemainingPrice += $ret['data']['amount'];
                $ret['order_id'] = $creditLog->order_id; // イーコン返却値にorder_idが含まれていないので追加しておく
                $retArr[] = $ret;
            }

            if ($creditLogs->sum('price') != (int) $systemRemainingPrice) {
                return response()->json(['ret' => 'error', 'msg' => '残額が一致しません']);
            }

            /* --------------- 返金可能 -----------------*/
            $refundingPrice = $systemRemainingPrice - $cancelDetailSum;

            // refundテーブルにデータがまだない場合は登録する キャンセル料ない→返金額=入金額
            if(!$refund->createRefundOnlyIfEmpty($reservationId, $refundingPrice, config('code.refundStatus.refunding.key'))){
                return response()->json(['ret' => 'error', 'msg' => 'エラーが発生しました']);
            }

            // 返金
            if (!$paymentService->refund($reservationId, $refundingPrice)) {
                return response()->json(['ret' => 'error', 'msg' => '返金出来ませんでした']);
            }

            return response()->json(['ret' => 'ok']);
        } else {

            // 新決済の場合(レストランの来店前での利用前提)
            $callBackValues = json_decode($paymentToken->call_back_values, true);
            $payment = $paymentSkyticket->getPayment(['cartId' => $callBackValues['cartId']]);
            $refund = Refund::where('reservation_id', $reservationId)->where('status', config('code.refundStatus.scheduled.key'))->first();

            // refundテーブルにデータがまだない場合
            if(empty($refund)){
                return response()->json(['ret' => 'error', 'msg' => 'エラーが発生しました']);
            }
            $paymentToken->refundPrice = $refund->price;

            // 返金は1円以上
            if($paymentToken->refundPrice < 1){
                return response()->json(['ret' => 'error', 'msg' => '返金額が1円以上でないと返金できません']);
            }

            // 画面と実際の決済状況に差分が出てないことを確認
            if(
                $request->input('pfPaid') !=  $payment['paidPrice'] ||
                $request->input('pfRefund') !=  $payment['refundPrice']  ||
                $request->input('pfRefunded') !=  $payment['refundedPrice']
            ){
                return response()->json(['ret' => 'error', 'msg' => '画面の情報が古い可能性があります。画面を更新後、再度実行してください']);
            }
            $result = null;
            if(!$paymentSkyticket->registerRefundPayment($paymentToken, $result)){
                return response()->json(['ret' => 'error', 'msg' => '返金出来ませんでした']);
            }
            if(!$refund->changeToRefunding($reservationId)){
                return response()->json(['ret' => 'error', 'msg' => '返金データが更新できませんでした']);
            }
            unset($paymentToken->refundPrice);
            $paymentToken->is_invalid = 1;
            $paymentToken->save();
            $reservation->payment_status = config('code.paymentStatus.wait_refund.key');
            $reservation->save();
            return response()->json(['ret' => 'ok']);
        }

    }

    private function selectPaymentStatus($paymentStatus, $paymentMethod)
    {
        //$paymentStatusCode = \Arr::pluck(array_values(config('code.paymentStatus')), 'key');
        //$defaultPaymentStatus = array_search($paymentStatus, $paymentStatusCode);
        $defaultPaymentStatus = config('code.paymentStatus')[strtolower($paymentStatus)];

        return [$defaultPaymentStatus, config('code.paymentStatus')];
    }

    private function getRefundInfo($reservationId, &$refundingPrice, &$refundedPrice){
        $refunds = Refund::where('reservation_id', $reservationId)->get();

        foreach ($refunds as $refund) {
            if ($refund->status == config('code.refundStatus.scheduled.key') || $refund->status == config('code.refundStatus.refunding.key')) {
                $refundingPrice += $refund->price;
            } elseif ($refund->status == config('code.refundStatus.refunded.key')) {
                $refundedPrice += $refund->price;
            }
        }
    }

    public function yoshinCancel(Request $request, PaymentService $paymentService)
    {
        $orderId = $request->input('order_id');
        $log = $paymentService->getPaymentList(['order_id' => $orderId])->first();
        if ($paymentService->creditCancel($log)) {
            return response()->json(['ret' => 'ok']);
        } else {
            return response()->json(['message' => 'キャンセル失敗']);
        }
    }

    public function cardCapture(Request $request, PaymentService $paymentService)
    {
        $orderId = $request->input('order_id');
        $log = $paymentService->getPaymentList(['order_id' => $orderId])->first();
        if ($paymentService->cardCapture($log->CmThApplicationDetail->application_id, $log->price)) {
            return response()->json(['ret' => 'ok']);
        } else {
            return response()->json(['ret' => 'ng', 'message' => '計上失敗']);
        }
    }
}
