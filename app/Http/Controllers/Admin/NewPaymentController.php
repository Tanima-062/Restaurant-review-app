<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelDetailRequest;
use App\Services\PaymentService;
use App\Models\Reservation;
use App\Models\PaymentDetail;
use App\Models\CancelDetail;
use App\Models\Refund;
use Illuminate\Http\Request;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Models\PaymentToken;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Validator;

class NewPaymentController extends Controller
{
    public function index(Request $request, PaymentSkyticket $paymentSkyticket)
    {

        $result = [];
        $reservationIds = [];
        $cmApplicationIds = [];
        $payments = collect();
        $errors = [];
        if (count($request->query()) > 1) {
            $validator = $this->searchValidation($request);
            // バリデーションエラーがあればエラー内容格納し、エラーがなければ検索実行する
            if ($validator->fails()) {
                $errors['errors'] =  $validator->messages();
            } else {
                $params = $request->all();
                // 入金一覧取得
                $result = $paymentSkyticket->getPaymentList($params);
                $payments = empty($result['list']['data']) ? [] : $result['list']['data'];
            }
        }
        foreach($payments as $payment){
            if(isset($payment['cm_application_ids'][$params['serviceCd']])){
                $cmApplicationIds[$payment['id']] = $payment['cm_application_ids'][$params['serviceCd']][0];
            }
        }

        if(!empty($cmApplicationIds)){
            $paymentTokens = PaymentToken::whereIn('cm_application_id',array_values($cmApplicationIds))->get()->keyBy('cm_application_id');
        }

        // skytikcetとgourmetの情報をマージ+データ調整
        foreach($payments as $key => $payment){
            
            $reservationInfo = null;
            if(isset($cmApplicationIds[$payment['id']]) && isset($paymentTokens[$cmApplicationIds[$payment['id']]])){
                $reservationInfo = $paymentTokens[$cmApplicationIds[$payment['id']]]->reservation;
            }
            
            $payments[$key]['reservation_id'] = is_null($reservationInfo) ? '': $reservationInfo->id;
            $payments[$key]['reservation_number'] = is_null($reservationInfo) ? '': $reservationInfo->app_cd . $reservationInfo->id;
            $payments[$key]['reservation_status'] = is_null($reservationInfo) ? '': config('code.reservationStatus.'.strtolower($reservationInfo->reservation_status).'.value');
            $payments[$key]['payment_status'] = is_null($reservationInfo) ? '': config('code.paymentStatus.'.strtolower($reservationInfo->payment_status).'.value');
            $payments[$key]['name'] = is_null($reservationInfo) ? '': $reservationInfo->reservationStore->name;
            $payments[$key]['created_at'] = is_null($reservationInfo) ? '': $reservationInfo->created_at;
            $payments[$key]['cancel_datetime'] = is_null($reservationInfo) ? '': $reservationInfo->cancel_datetime;
            
        }

        if($request->action === 'csv' && count($errors) == 0){
            return $this->getCsv($params['serviceCd'], $payments);
        }
        

        $payments = collect($payments);

        $currentPage = isset($request->page) ? $request->page : 1;
        $total = empty($result['list']['total']) ? 0 : $result['list']['total'];
        $paginator = new LengthAwarePaginator(
            $payments->forPage($currentPage, $paymentSkyticket::PAYMENT_LIST_PER_PAGE),
            $total,
            $paymentSkyticket::PAYMENT_LIST_PER_PAGE,
            $currentPage,
            array('path' => str_replace('&page='.$currentPage, '', $request->fullUrl()) )
        );

        return view('admin.NewPayment.index', array_merge([
            'payments' => $payments,
            'total' => $total,
            'paginator' => $paginator
        ], $errors));
    }

    public function detail(string $reservationNumber){

        $reservationId = substr( $reservationNumber , 2 , strlen($reservationNumber) - 1 );
        $reservation = Reservation::where('id',$reservationId)->first();
        return view('admin.NewPayment.detail', [
            'reservationNumber' => $reservationNumber,
            'reservation' => $reservation,
            'messages' => [],
            ]);
    }

    public function getCsv(string $serviceCd, array $data){
        $headTop = ['入金情報','','','','','グルメ予約情報'];
        $headColumns = ['SKYTICKET申込番号', 'カート番号','注文番号','決済開始日','決済金額','決済処理結果','予約番号','予約ステータス','入金ステータス','会社名','申込日時','キャンセル申込日時','サービスコード'];

        // 出力する順番とデータのみにする
        $output = [];
        foreach($data as $rec){
            $tmp = [];
            $tmp[] = $rec['cm_application_ids'][$serviceCd][0];
            $tmp[] = $rec['cart_id'];
            $tmp[] = $rec['order_code'];
            $tmp[] = $rec['created_at'];
            $tmp[] = $rec['price'];
            $tmp[] = $rec['progress_name'];
            $tmp[] = $rec['reservation_number'];
            $tmp[] = $rec['reservation_status'];
            $tmp[] = $rec['payment_status'];
            $tmp[] = $rec['name'];
            $tmp[] = $rec['created_at'];
            $tmp[] = $rec['cancel_datetime'];
            $tmp[] = $serviceCd;
            $output[] = $tmp;
        }
        

        // 書き込み用ファイルを開く
        $f = fopen('入金一覧.csv', 'w');
        if ($f) {
            // カラムの書き込み
            mb_convert_variables('SJIS', 'UTF-8', $headTop);
            mb_convert_variables('SJIS', 'UTF-8', $headColumns);
            fputcsv($f, $headTop);
            fputcsv($f, $headColumns);
            // データの書き込み
            foreach ($output as $rec) {
                if(is_array($rec) && isset($rec['cm_application_ids'])){
                    foreach($rec['cm_application_ids'] as $val){
                        foreach($val as $id){
                            $rec['cm_application_id'] = $id;
                            unset($rec['cm_application_ids']);
                        }
                    }
                }
                mb_convert_variables('SJIS', 'UTF-8', $rec);
                fputcsv($f, $rec);
            }
        }
        // ファイルを閉じる
        fclose($f);

        // HTTPヘッダ
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.filesize('入金一覧.csv'));
        header('Content-Disposition: attachment; filename=入金一覧.csv');
        readfile('入金一覧.csv');
    }

    /**
     * 検索バリデーション
     * Request
     *
     * @param Request $request
     * @return void
     */
    private function searchValidation($request)
    {
        $rules = [
            'date_from' => ['nullable', 'required_with:date_to', 'required_without_all:date_from,date_to,id,cart_id,order_code'],
            'date_to' => ['nullable', 'required_with:date_from'],
            'id' => ['nullable'],
            'cart_id' => ['nullable'],
            'order_code' => ['nullable'],
        ];
        $messages = [
            'date_from.required_without_all' => '年月日 / skyticket申込番号 / カート番号 / 注文番号のいずれかを指定してください。'
        ];
        $attributes = [
            'date_from' => '開始年月日',
            'date_to' => '終了年月日',
            'id' => 'skyticket申込番号',
            'cart_id' => 'カート番号',
            'order_code' => '注文番号',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);
        $validator->after(function ($validator) {
            $data = request()->all();

            // 年月日以外の項目が指定されているか確認
            $isDateValidate = true;
            if (!empty($data['id']) || !empty($data['cart_id']) || !empty($data['order_code'])) {
                $isDateValidate = false;
            }

            // 検索条件が年月日のみの場合、 指定期間は90日以下か確認する
            if ($isDateValidate) {
                $startDay = new Carbon($data['date_from']);
                $endDay = new Carbon($data['date_to']);
                if ($startDay->diffInDays($endDay) > 90) {
                    $validator->errors()->add('date_from', '年月日の期間は90日以下で指定してください。');
                }
            }
        });
        return $validator;
    }
}
