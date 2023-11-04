<?php

namespace App\Modules\Payment\Skyticket;

use App\Modules\BaseApi;
use App\Modules\Payment\Gateway;

class Skyticket extends BaseApi
{
    // 支払確定[PUT]、取り消し[DELETE]、入金情報取得[GET]
    const PAYMENT = '/payments';
    // 支払情報を登録し、決済APIのリダイレクト先を取得する[POST]
    const PAYMENT_REGISTER = '/payments/register';
    // 返金要求を作成または更新する[PUT]、返金要求を削除する[DELETE]
    const PAYMENT_REFUND = '/payments/refund';
    // 入金情報一覧取得[GET]
    const PAYMENT_LIST = '/payments/list';

    const RETURN_URL = 'payment/payment_credit_redirect.php';

    // cURLタイムアウト(秒)
    const CURLOPT_CONNECTTIMEOUT = 20;
    const TIMEOUT = 40;

    // 内部エラー
    const SYSTEM_ERROR_CD = -100;

    // エラー用プロパティ
    public $errorMsg = '';             // エラーがあったらメッセージが入るところ(メッセージがあれば)
    public $errorCode = '';            // 直前のAPIのリターンコードが入るところ(API以外が入ることもある)
    public $errorStatus = 0;           // 通信結果のステータスが入る
    public $requestParam = '';         // 通信時のリクエストパラメータを入れておく

    private $gateway;

    public function __construct()
    {
        parent::__construct();

        $this->setCurlTimeout(self::CURLOPT_CONNECTTIMEOUT + self::TIMEOUT);
        $this->setCurlConnectTimeout(self::CURLOPT_CONNECTTIMEOUT);
        $this->gateway = new Gateway();
    }

    /**
     * 決済事前処理. フロントからリクエストされる想定.
     *
     * @param array params
     *
     * @return string redirectUrl
     *
     * @throws Exception
     */
    public function registerPayment($params)
    {
        // 支払情報を登録し、決済APIのリダイレクト先を取得する
        $this->setEndpoint(env('SKYTICKET_PAYMENT_URL').self::PAYMENT_REGISTER);

        $resBody = $this->doRequest($params, 'POST');

        return $resBody;
    }

    /**
     * 返金要求を作成または更新する。
     * 同じ(申込番号+サービスコード+返金識別番号)に対して複数回呼び出した場合は、以前の内容を更新する。
     *
     * @param array params
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function registerRefundPayment($params)
    {
        // 返金要求を作成または更新する。
        $this->setEndpoint(env('SKYTICKET_PAYMENT_URL').self::PAYMENT_REFUND);

        $resBody = $this->doRequest($params, 'PUT');

        return $resBody;
    }

    /**
     * 返金要求を削除する。
     *
     * @param array params
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function deleteRefundPayment($params)
    {
        // 返金要求を作成または更新する。
        $this->setEndpoint(env('SKYTICKET_PAYMENT_URL').self::PAYMENT_REFUND);

        $resBody = $this->doRequest($params, 'DELETE');

        return $resBody;
    }

    /**
     * 入金情報取得.
     *
     * @param array params
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function getPayment($params)
    {
        // 決済代行側の入金状況を取得する。
        $url = sprintf(env('SKYTICKET_PAYMENT_URL').self::PAYMENT);
        $params = http_build_query($params);
        $this->setEndpoint($url.'?'.$params);

        $resBody = $this->doRequest($params, 'GET');

        return $resBody;
    }

    /**
     * 入金情報一覧取得.
     *
     * @param array params
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function getPaymentList($params)
    {
        // 決済代行側の入金状況を取得する。
        $url = sprintf(env('SKYTICKET_PAYMENT_URL').self::PAYMENT_LIST);
        $params = http_build_query($params);
        $this->setEndpoint($url.'?'.$params);

        $resBody = $this->doRequest($params, 'GET');

        return $resBody;
    }

    /**
     * 支払確定. バッチからの利用を想定.
     *
     * @param array param
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function settlePayment($params)
    {
        // 支払確定
        $this->setEndpoint(env('SKYTICKET_PAYMENT_URL').self::PAYMENT);

        $resBody = $this->doRequest($params, 'PUT');

        return $resBody;
    }

    /**
     * 取り消し.
     *
     * @param array param
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function cancelPayment($params)
    {
        // 取り消し
        $url = sprintf(env('SKYTICKET_PAYMENT_URL').self::PAYMENT);
        $params = http_build_query($params);
        $this->setEndpoint($url.'?'.$params);

        $resBody = $this->doRequest($params, 'DELETE');

        return $resBody;
    }

    /**
     * curl実行.
     *
     * @param array params
     * @param string method
     *
     * @return array result
     *
     * @throws Exception
     */
    private function doRequest($params, $method)
    {
        $result = null;
        switch ($method) {
            case 'GET':
                $result = $this->get();
                break;
            case 'POST':
                $result = $this->post($params);
                break;
            case 'PUT':
                $result = $this->put($params);
                break;
            case 'DELETE':
                $result = $this->delete($params);
                break;
            default:
        }

        if (!empty($result['errors'])) {
            throw new \Exception('['.$result['message'].']'.json_encode($result), $result['code']);
        }

        return $result;
    }

    public function getCartId($cmApplicationId, $appCd)
    {
        return $this->gateway->externalRegistration($cmApplicationId, $appCd);
    }
}
