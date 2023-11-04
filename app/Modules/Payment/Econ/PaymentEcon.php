<?php

namespace App\Modules\Payment\Econ;

use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\CmThOhterPaymentEconCreditLog;
use App\Models\Refund;
use App\Modules\Payment\IFPayment;
use DB;
use Log;

class PaymentEcon implements IFPayment
{
    const JSF_PRO_URL = 'https://www5.econ.ne.jp/multitoken/scripts/econScone.min.js';
    const JSF_DEV_URL = 'https://test.econ.ne.jp/multitoken/scripts/econScone.min.js';

    private $econCreditLog = null;
    private $errMsg = '';
    private $cmApplicationId = 0;
    private $userId = 0;
    private $refund = null;

    public function __construct(
        CmThOhterPaymentEconCreditLog $econCreditLog = null,
        Refund $refund = null
        ) {
        $this->econCreditLog = $econCreditLog;
        $this->refund = $refund;
    }

    private function createOrderId()
    {
        try {
            DB::transaction(function () use (&$cmApplicationId) {
                list($this->cmApplicationId, $this->userId) = CmThApplication::createEmptyApplication();
            });
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }

        if (!empty($this->cmApplicationId)) {
            return config('code.serviceCd').'-cc-'.$this->cmApplicationId.'-'.date('YmdHis');
        } else {
            return '';
        }
    }

    public function getCmApplicationId()
    {
        return $this->cmApplicationId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function createToken($amount, $returnUrl = '')
    {
        $econ = new Econtext();
        $econ->setType('card');
        $econ->return_url = $returnUrl;
        $econ->use_3Dsecure = 1;
        $fee = $this->getCreditRate();
        $econ->amount = intval($amount + $fee);
        $orderId = $this->createOrderId();
        Log::info('order_id => '.$orderId);
        if (empty($orderId)) {
            return false;
        }

        $econToken = $econ->startSession($orderId);
        if (empty($econToken)) {
            Log::error($econ->error_msg);
            Log::error($econ->error_code);

            return false;
        }

        $this->saveBeforeCreditLog([
            'session_token' => $econToken['session_token'],
            'request_id' => $econToken['request_id'],
            'order_id' => $orderId,
            'amount' => $econ->amount,
            'keijou' => (int) $econ->with_cap, // 与信
            'fee' => $fee,
            'cm_application_id' => $this->cmApplicationId,
        ]);

        return $econToken;
    }

    public function getToken()
    {
        return [
            'session_token' => session('payment.econ.session_token', ''),
            'request_id' => session('payment.econ.request_id', ''),
            ];
    }

    public function getJsfUrl()
    {
        return (env('APP_ENV') == 'local' || env('APP_ENV') == 'testing') ? self::JSF_DEV_URL : self::JSF_PRO_URL;
    }

    public function getCreditRate()
    {
        if (env('APP_ENV') == 'local' || env('APP_ENV') == 'testing') {
            return config('const.payment.credit_fee.dev');
        } else {
            return config('const.payment.credit_fee.pro');
        }
    }

    public function inquiry($orderId)
    {
        $econ = new Econtext();
        $econ->setType('card');

        return $econ->orderStatus($orderId);
    }

    public function saveBeforeCreditLog(array $params)
    {
        CmThOhterPaymentEconCreditLog::beforeSave($params);
    }

    public function recvTokenCheck($sessionToken)
    {
        if (empty($sessionToken)) {
            $this->errMsg = \Lang::get('message.sessionError');

            return false;
        }

        $econCreditLog = CmThOhterPaymentEconCreditLog::where('session_token', $sessionToken)->first();
        if (!$econCreditLog) {
            $this->errMsg = \Lang::get('message.sessionError');

            return false;
        }

        if ($econCreditLog->info_code == '00000') {
            $this->errMsg = \Lang::get('message.processing');

            return false;
        }

        /*
        Log::debug(print_r($econCreditLog->value, true));
        Log::debug(print_r($econCreditLog->value['session_id'], true));
        Log::debug(print_r(session()->getId(), true));
        if (empty($econCreditLog->value['session_id']) || $econCreditLog->value['session_id'] != session()->getId()) {
            $econCreditLog->status = 500;
            $econCreditLog->info_code = 'SYSTEM';
            $econCreditLog->info = 'セッションエラー';
            $econCreditLog->save();

            $this->errMsg = \Lang::get('message.sessionError2');
            return false;
        }*/

        return $econCreditLog;
    }

    public function complete($sessionToken, $cd3secResFlg)
    {
        //$params = session('payment.econ.recv', []);
        //Log::debug(print_r($params, true));
        //$sessionToken = $params['session_token'];
        //$cd3secResFlg = $params['cd3secResFlg'];

        $econCreditLog = $this->recvTokenCheck($sessionToken);
        if (!$econCreditLog) {
            return false;
        }

        if ($cd3secResFlg < 0 || 3 < $cd3secResFlg) { // エラー(APIの仕様)
            if ($cd3secResFlg == 4) {
                $infoCode = '3DSEC';
                $info = 'パスワード判定エラー/パスワード未入力';
                $this->errMsg = \Lang::get('message.authError');
            } elseif ($cd3secResFlg == 9) {
                $infoCode = '3DSEC';
                $info = 'その他のエラー';
                $this->errMsg = \Lang::get('message.authError');
            } else {// このパターンはありえない(by ECON)ので出たら問題
                $infoCode = 'REDIRECT';
                $info = 'その他のエラー';
                $this->errMsg = \Lang::get('message.authError');
            }

            $econCreditLog->fill([
                'status' => $cd3secResFlg,
                'info_code' => $infoCode,
                'info' => $info,
            ]);

            $econCreditLog->save();
            $econCreditLog->saveResultData();

            return false;
        }

        $econ = new Econtext();
        $econ->setType('card');
        $econ->amount = (int) $econCreditLog->price; // ORDER_COMMITの場合必須

        $resultCommit = $econ->commitSession($sessionToken);

        /*if (!$resultCommit) {
            $resultCommit['status'] = 1;
            $resultCommit['infoCode'] = '00000';
            $resultCommit['info'] = '正常';
            $resultCommit['data']['econNo'] = 1111;
            $resultCommit['data']['econCardno4'] = 1111;
            $resultCommit['data']['shoninCD'] = '12345';
            $resultCommit['data']['shimukeCD'] = '12345';
        }*/
        if (!$resultCommit) {
            $econCreditLog->fill([
                'status' => $econ->error_status,
                'info_code' => $econ->error_code,
                'info' => $econ->error_msg,
            ]);

            $econCreditLog->save();
            $econCreditLog->saveResultData();

            $this->errMsg = $this->getMessageErrorCode($econ->error_code);

            return false;
        }

        $ret = $econCreditLog->fill([
            'status' => $resultCommit['status'],
            'info_code' => $resultCommit['infoCode'],
            'info' => $resultCommit['info'],
            'econ_no' => $resultCommit['data']['econNo'],
            'econ_cardno4' => $resultCommit['data']['econCardno4'],
            'shonin_cd' => $resultCommit['data']['shoninCD'],
            'shimuke_cd' => $resultCommit['data']['shimukeCD'],
        ])->save();

        if (!$ret) {
            $this->errMsg = \Lang::get('message.sessionError');

            $this->creditCancel($econCreditLog, $econ);

            return false;
        }

        $this->econCreditLog = $econCreditLog;

        if (!$this->saveResultData()) {
            return false;
        }

        return true;
    }

    public function saveResultData()
    {
        if (!$this->econCreditLog || !$this->econCreditLog->saveResultData()) {
            $title = sprintf('econ注文番号:%s', $this->econCreditLog->order_id);
            $this->notifyToChat($title, 'ECON決済情報更新失敗');

            return false;
        }

        return true;
    }

    /**
     * エラーコードに応じたメッセージを返す.
     *
     * @param $errorCode
     *
     * @return string
     */
    private function getMessageErrorCode($errorCode)
    {
        switch ($errorCode) {
            case 'C1470': // 決済失敗系エラー
                return \Lang::get('message.sysError');
            case 'C1444': // 入力内容系エラー1
                return \Lang::get('message.secError');
            case 'C1465': // 入力内容系エラー2
                return \Lang::get('message.numError');
            case 'C1483': // 入力内容系エラー3
                return \Lang::get('message.termError');
            case 'C1490': // カード会社接続系エラー
            case 'C1499':
                return \Lang::get('message.conError');
            case 'C1455': // カード状態系エラー
                return \Lang::get('message.overError');
            case 'C1401': // カード使用不可系エラー
            case 'C1412':
            case 'C1430':
            case 'C1454':
            case 'C1491':
            case 'C1492':
            case 'C1493':
            case 'C1494':
            case 'C1495':
            case 'C1496':
            case 'C1497':
            case 'C1498':
                return \Lang::get('message.canNotUse');
            case 'E1112': // イーコン会員更新回数制限到達
                return \Lang::get('message.canNotUse2');
            case '3DSEC': // 3Dセキュアエラー
                return \Lang::get('message.authError');
            default:      // その他
                return \Lang::get('message.other');
        }
    }

    /**
     * 与信から計上にする.
     *
     * @param $reservationId
     * @param int $amount
     *
     * @return bool
     */
    public function cardCapture($reservationId, $amount = 0)
    {
        $orderId = session('payment.econ.order_id', '');
        $econCreditLog = CmThOhterPaymentEconCreditLog::getByReservationId($reservationId, $orderId)->first();
        $this->isEconUsed = empty($econCreditLog) ? false : true;

        // 新決済経由の予約であれば何もしない
        if (empty($econCreditLog)) {
            return true;
        }
        $title = sprintf('econ注文番号:%s', $econCreditLog->order_id);

        $econ = new Econtext();
        $econ->setType('card');
        $result = $econ->cardCapture($econCreditLog->order_id, (int) $amount, date('Y/m/d'));
        if ($result) {
            if (!$econCreditLog->fill(['keijou' => 1])->save()) {
                $this->notifyToChat($title, 'ECON与信->計上失敗');

                return false;
            }

            return true;
        }

        $msg = $econ->error_msg.'['.$econ->error_code.']';
        $this->notifyToChat($title, $msg);

        return false;
    }

    public function cancelCreditByReservationId($reservationId)
    {
        $econCreditLog = CmThOhterPaymentEconCreditLog::getByReservationId($reservationId);
        if ($econCreditLog->count() == 0) {
            return false;
        }

        return $this->creditCancel($econCreditLog->first());
    }

    /**
     * @param CmThOhterPaymentEconCreditLog $econCreditLog
     * @param null                          $econ
     *
     * @return bool
     */
    public function creditCancel($econCreditLog = null, $econ = null, $token = null)
    {
        if (!$econ) {
            $econ = new Econtext();
        }

        $econ->setType('card');
        $econ->is_member = false;

        if (!is_null($token) && is_null($econCreditLog)) {
            $econCreditLog = CmThOhterPaymentEconCreditLog::where('session_token', $token)->first();
            if (is_null($econCreditLog)) {
                return false;
            }
        }
        if ($econCreditLog->keijou != 0 || !$econ->orderCancel($econCreditLog->order_id)) {
            $title = sprintf('econ注文番号:%s', $econCreditLog->order_id);
            $this->notifyToChat($title, 'ECON決済自動キャンセル失敗');

            return false;
        }

        $econCreditLog->fill([
            'cancel_dt' => date('Y-m-d H:i:s', time()),
        ])->save();

        return true;
    }

    public function getRemainingPrice($reservationId)
    {
        $paymentList = CmThOhterPaymentEconCreditLog::getByReservationId($reservationId);

        return $paymentList->where('keijou', 1)->sum('price');
    }

    /**
     * 返金する
     * (フェリー、レンタカーと違って残金を指定するのではなく、返金予定額を指定しているので注意).
     *
     * @param $reservationId
     * @param $refundingPrice
     *
     * @return bool
     */
    public function refund($reservationId, $refundingPrice)
    {
        $paymentList = CmThOhterPaymentEconCreditLog::getByReservationId($reservationId);

        if ($paymentList->count() == 0) {
            return false;
        }

        foreach ($paymentList as $payment) {
            if ($payment->status != 1 || $payment->info_code != '00000' || $payment->keijou != 1) {
                continue;
            }

            $ret = $this->inquiry($payment->order_id);
            $title = sprintf('econ注文番号:%s', $payment->order_id);
            $body = 'ECON決済自動返金失敗';

            if (!isset($ret['data']['amount'])) {
                $this->notifyToChat($title, $body.'[該当データなし]');

                return false;
            }

            $econ = new Econtext();
            $econ->setType('card');
            $econ->is_member = false; // ECONカード会員登録する場合はtrueだが、今は固定
            $action = ($payment->is_member) ? 'card_change_amount_member' : 'card_change_amount';

            $econResult = null;
            if ($ret['data']['amount'] <= $refundingPrice) { // 追加した決済の場合1つのorderを返金額が超える可能性がある
                //$econResult = $econ->orderChangeAmount($payment['order_id'], (int)$ret['data']['amount']);
                $econResult = $econ->orderChangeAmount($payment['order_id'], 0);
                if ($econResult) {
                    //$payment->fill(['action' => $action, 'price' => $ret['data']['amount']])->save();
                    $payment->fill(['action' => $action, 'price' => 0])->save();
                    $refundingPrice -= $ret['data']['amount'];
                }
            } else {
                //$econResult = $econ->orderChangeAmount($payment['order_id'], (int)$refundingPrice);
                $balance = $ret['data']['amount'] - (int) $refundingPrice;
                $econResult = $econ->orderChangeAmount($payment['order_id'], $balance);
                if ($econResult) {
                    //$payment->fill(['action' => $action, 'price' => $refundingPrice])->save();
                    $payment->fill(['action' => $action, 'price' => $balance])->save();
                }
            }

            if (!$econResult) {
                $addStr = sprintf(
                    'err_status:%d,err_code:%s,err_msg:%s',
                    $econ->error_status,
                    $econ->error_code,
                    $econ->error_msg
                );
                $this->notifyToChat($title, $body.'::'.$addStr);

                return false;
            }

            // gourmetDBのrefundテーブルのデータもREFUNDEDに変更する
            if (!$this->refund->changeTorefunded($reservationId)) {
                $this->notifyToChat('Econ返金処理refundテーブルの更新だけ失敗しました。reservationId = ', $reservationId);

                return false;
            }
        }

        return true;
    }

    public function getPaymentList($params = [])
    {
        $prefix = config('code.serviceCd').'-';
        if (!isset($params['payment_method']) ||
            $params['payment_method'] == config('const.payment.payment_method.credit')) {
            $paymentLog = new CmThOhterPaymentEconCreditLog();
            $prefix .= 'cc-';
        }

        // TODO:条件はmodelへ移動しよう
        $sqlObj = $paymentLog::with(['CmThApplicationDetail.reservation']);

        if (!empty($params['reservation_id'])) {
            $application = CmThApplicationDetail::getApplicationByReservationId(substr($params['reservation_id'], 2));
            if ($application) {
                $params['cm_application_id'] = $application->cm_application_id;
            }
        }

        if (!empty($params['cm_application_id'])) {
            $sqlObj->where('cm_application_id', $params['cm_application_id']);
        }

        if (!empty($params['order_id'])) {
            $sqlObj->where('order_id', $params['order_id']);
        } else {
            $sqlObj->where('order_id', 'like', $prefix.'%');
        }

        if (!empty($param['keijou'])) {
            $sqlObj->where('keijou', 1);
        }

        if (!empty($params['date_filter'])) {
            $dataFilter = $params['date_filter'];
            $sqlObj->whereBetween('create_dt', [$dataFilter.' 00:00:00', $dataFilter.' 23:59:59']);
        } else {
            $dataFilter = date('Y-m-d');
            // ピンポイントで指定されてないものはデフォルトの日付を入れる
            if (empty($params['cm_application_id']) &&
                empty($params['order_id']) &&
                empty($params['reservation_id'])
            ) {
                $sqlObj->whereBetween('create_dt', [$dataFilter.' 00:00:00', $dataFilter.' 23:59:59']);
            }
        }

        if (!empty($params['log_status'])) {
            /* @noinspection PhpUndefinedMethodInspection */
            $sqlObj->logStatus($params['log_status']);
        }

        /* @noinspection PhpUndefinedMethodInspection */
        return $sqlObj;
    }

    public function notifyToChat(string $title, string $body)
    {
        Log::critical('['.$title.'] '.$body);
    }

    public function getErrorMsg()
    {
        return $this->errMsg;
    }

    public function getIsEconUsed()
    {
        return $this->isEconUsed;
    }
}
