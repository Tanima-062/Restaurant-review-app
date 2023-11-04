<?php

namespace App\Modules\Payment\Econ;

class Econtext
{

    const SESSONSTART_AUTH                  = 'v1/token_start_authorize';                 // セッション開始要求(非会員決済) ※ 与信のみ
    const SESSONSTART_CAP                   = 'v1/token_start_withcapture';               // セッション開始要求(非会員決済) ※ 与信＋計上
    const SESSONSTART_MEMBER_AUTH           = 'v1/token_start_member_authorize';          // セッション開始要求(会員決済) ※ 与信のみ
    const SESSONSTART_MEMBER_CAP            = 'v1/token_start_member_withcapture';        // セッション開始要求(会員決済) ※ 与信＋計上
    const SESSONSTART_MEMBER_ADD            = 'v1/token_start_member_add';                // セッション開始要求(会員登録更新) ※ 登録
    const SESSONSTART_MEMBER_UPDATE         = 'v1/token_start_member_update';             // セッション開始要求(会員登録更新) ※ 更新
    const ORDER_COMMIT                      = 'v1/token_charge?sessionToken=';            // 注文確定要求(sessionToken={sessionToken})
    const MEMBER_COMMIT                     = 'v1/token_member_regist?sessionToken=';     // 会員登録更新確定要求(sessionToken={sessionToken})
    const CARD_ORDER_STATUS                 = 'v1/card_order_status';                     // カード入金照会
    const CARD_CANCEL                       = 'v1/card_cancel';                           // カード注文キャンセル(非会員決済)
    const CARD_CANCEL_MEMBER                = 'v1/card_cancel_member';                    // カード注文キャンセル(会員決済)
    const CARD_CHANGE_AMOUNT                = 'v1/card_change_amount';                    // カード金額変更(非会員決済)
    const CARD_CHANGE_AMOUNT_MEMBER         = 'v1/card_change_amount_member';             // カード金額変更(会員決済)
    const CARD_MEMBER_PROPERTY              = 'v1/card_member_property';                  // カード会員参照
    const CARD_MEMBER_DELETE                = 'v1/card_member_delete';                    // カード会員削除
    const CARD_CAPTURE                      = 'v1/card_capture';                          // カード売上計上(非会員決済)
    const CARD_CAPTURE_MEMBER               = 'v1/card_capture_member';                   // カード売上計上(会員決済)
    const CARD_REAUTH                       = 'v1/card_reauthorize';                      // カード与信再取得(再与信)(非会員決済)
    const CARD_REAUTH_MEMBER                = 'v1/card_reauthorize_member';               // カード与信再取得(再与信)(会員決済)

    const MULTI_PAYMTCODE_CASH              = 'A10';                                      // マルチ決済モジュールパラメータ(現金、コンビニ、銀行、電子マネー等)
    const MULTI_FNCCODE_ORDER               = 10;                                         // マルチ決済モジュールパラメータ(注文登録)
    const MULTI_FNCCODE_CANCEL              = 20;                                         // マルチ決済モジュールパラメータ(注文取消)

    const RETURN_URL = 'payment/payment_credit_redirect.php';                             // ECONTEXTからのリダイレクトURL

    const TYPE_CARD                         = 'card';                         // 決済タイプ(card)
    const TYPE_CASH                         = 'cash';                         // 決済タイプ(cash)
    const TYPE_PAYEASY                      = 'payeasy';                      // 決済タイプ(payeasy)
    const SUB_TYPE_CASH_WITHIN_4DAYS        = 'cash_within_4days';            // 決済タイプ(cash出発日4日前)
    const RET_AUTH_OK                       = 200;                            // APIからの認証成功
    const SYSTEM_ERROR_CD                   = -100;                           // 内部エラー
    const CURL_TIMEOUT_MS                   = 60000;                          // cURLタイムアウト(ミリ秒)  ※ECONTEXTのタイムアウトは45秒

    private $site_code = '';            // サイトコード(ECONTEXTから提供)
    private $site_name = '';            // サイトネーム(ECONTEXTから提供)
    private $check_code = '';           // チェックコード(ECONTEXTから提供)
    private $auth_created_date = '';    // 基準日時(GMT)
    private $token = '';                // トークン(一度作ったら使い回す。たぶん)
    private $common_url = '';           // エンドポイント(共通部分)
    private $type = '';                 // 決済タイプ(card, cash, payeasy)
    private $ret_ok = [0,1,2,3];        // APIからの正常応答

    // エンドポイント用プロパティ
    public $is_member = false;         // true:会員, false:非会員
    public $with_cap = false;          // true:与信+計上, false:与信のみ
    public $session_action = '';        // add:登録, update:更新
    public $member_delete_flg = false; // 会員削除用フラグ

    // リクエスト用プロパティ(クレジット)
    public $end_point = '';             // エンドポイント(一つのメソッドで複数の機能を持つ場合これで切り換えられる)
    public $order_id = '';              // 申込番号
    public $use_3Dsecure = 1;           // 3Dセキュアを使用するか否か
    public $amount = 0;                 // 決済金額
    public $shipdate = '';              // 売上計上日(yyyy/mm/dd)
    public $cduser_id = '';             // サイト会員ID(クレジットカードを保持する会員IDを指定する)
    public $cduser_pw = '';             // サイト会員パスワード(オプション)
    public $return_url = '';            // ECONTEXTからのリダイレクトURL(フルパス)

    // リクエスト用プロパティ(現金系(マルチ))
    public $last_name = '';             // 姓
    public $first_name = '';            // 名
    public $email = '';                 // 顧客メールアドレス
    public $tel = '';                   // 顧客電話番号
    public $item_name = '';             // 商品名
    public $tax = 0;                    // 税金額
    public $fee = 0;                    // 手数料
    public $pay_limit_day = '';         // 支払期限(日)    yyyy/mm/dd
    public $pay_limit_time = '';        // 支払期限(時間)  hh:mm

    // レスポンス用プロパティ
    public $session_token = '';         // フロントから利用
    public $request_id = '';            // フロントから利用

    // エラー用プロパティ
    public $error_msg = '';             // エラーがあったらメッセージが入るところ(メッセージがあれば)
    public $error_code = '';            // 直前のAPIのリターンコードが入るところ(API以外が入ることもある)
    public $error_status = 0;           // 通信結果のステータスが入る
    public $request_param = '';         // 通信時のリクエストパラメータを入れておく


    public function __construct()
    {
        EcontextConfig::load();

        // 注文確定を実装している戻り先URL
        // HTTPS_ROOT_URLが定義されていたらそれをprefixに付けて使う
        // 無い場合はオブジェクトの外から指定してください
        if (defined('HTTPS_ROOT_URL')) {
            $this->return_url = HTTPS_ROOT_URL.self::RETURN_URL;
        }
    }

    /**
     * 決済タイプがトークン型APIか
     */
    private function isTokenAPI()
    {
        switch ($this->type) {
            case self::TYPE_CARD:
                return true;
            default:
                return false;
        }
    }

    /**
     * 決済タイプがマルチモジュール型APIか
     */
    private function isMultiAPI()
    {
        switch ($this->type) {
            case self::TYPE_CASH:
            case self::TYPE_PAYEASY:
                return true;
            default:
                return false;
        }
    }

    /**
     * トークン作成。一度作ったら使い回す。たぶん
     */
    private function createAuthToken()
    {
        if (!empty($this->token)) {
            return $this->token;
        }

        $timestamp = time();

        // リクエストヘッダのDateに指定するため保持しておく
        $this->auth_created_date = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);

        $key_data = $this->site_code.$this->check_code.date('YmdHis', $timestamp);

        $encode_key = base64_encode(strtoupper(hash('sha256', $key_data)));

        $this->token = base64_encode($this->site_code.':'.$encode_key);

        return $this->token;
    }

    /**
     * public リクエスト用プロパティのチェック(空チェックはしない)
     */
    private function validateParam()
    {
        if (!empty($this->return_url)) {
            $urlcom_arr = parse_url($this->return_url);
            if (!$urlcom_arr || (env('APP_ENV') != 'local' && mb_strtolower($urlcom_arr['scheme']) != 'https')) {
                $this->error_msg = 'validation error(return_url)';
                return false;
            }
        }
        if (!empty($this->end_point)) {
            if (strpos($this->end_point, 'v1') === false) {
                $this->error_msg = 'validation error(end_point)';
                return false;
            }
        }
        if (!empty($this->order_id)) {
            if (!preg_match("/^[0-9a-zA-Z_-]+$/u", $this->order_id)
                || strlen($this->order_id) < 6 || 47 < strlen($this->order_id)) {
                $this->error_msg = 'validation error(order_id)';
                return false;
            }
        }
        if ($this->use_3Dsecure !== 0 && $this->use_3Dsecure !== 1) {
            $this->error_msg = 'validation error(use_3Dsecure)';
            return false;
        }
        if (!empty($this->amount)) {
            if (!is_int($this->amount)
                || ($this->isTokenAPI() && 9999999 < $this->amount)
                || (($this->isMultiAPI()) && 9999999 < $this->amount)) {
                $this->error_msg = 'validation error(amount)';
                return false;
            }
        }
        if (!empty($this->cduser_id)) {
            if (!preg_match("/^[0-9a-zA-Z_-]+$/u", $this->cduser_id) || 36 < strlen($this->cduser_id)) {
                $this->error_msg = 'validation error(cduser_id)';
                return false;
            }
        }
        if (!empty($this->cduser_pw)) {
            if (!preg_match("/^[0-9a-zA-Z]+$/u", $this->cduser_pw) || 36 < strlen($this->cduser_pw)) {
                $this->error_msg = 'validation error(cduser_pw)';
                return false;
            }
        }
        if (!empty($this->last_name)) {
            if (10 < mb_strlen($this->last_name, 'UTF8')) {
                $this->error_msg = 'validation error(last_name)';
                return false;
            }
        }
        if (!empty($this->first_name)) {
            if (10 < mb_strlen($this->first_name, 'UTF8')) {
                $this->error_msg = 'validation error(first_name)';
                return false;
            }
        }
        if (!empty($this->email)) {
            if (!preg_match("/^[0-9a-z@_.-]+$/u", $this->email)
                || strlen($this->email) < 6 || 50 < strlen($this->email)) {
                $this->error_msg = 'validation error(email)';
                return false;
            }
        }
        if (!empty($this->tel)) {
            if (!preg_match("/^[0-9a-zA-Z]+$/u", $this->tel)
                || 11 < strlen($this->tel) || preg_match("/^0+$/u", $this->tel)) {
                $this->error_msg = 'validation error(tel)';
                return false;
            }
        }
        if (!empty($this->item_name)) {
            if (mb_strlen($this->item_name, 'UTF8') > 22) {
                $this->error_msg = 'validation error(item_name)';
                return false;
            }
        }
        if (!empty($this->tax)) {
            if (!is_int($this->tax) || $this->tax > 999999) {
                $this->error_msg = 'validation error(tax)';
                return false;
            }
        }
        if (!empty($this->fee)) {
            if (!is_int($this->fee) || $this->fee > 999999) {
                $this->error_msg = 'validation error(fee)';
                return false;
            }
        }
        if (!empty($this->pay_limit_day)) {
            if ($this->pay_limit_day !== date('Y/m/d', strtotime($this->pay_limit_day))) {
                $this->error_msg = 'validation error(pay_limit_day)';
                return false;
            }
        }
        if (!empty($this->pay_limit_time)) {
            $ret = true;
            if (strpos($this->pay_limit_time, ':') === false) {
                $ret = false;
            } else {
                $time_arr = explode(':', $this->pay_limit_time);
                if ((int)$time_arr[0] < 0 || 23 < (int)$time_arr[0]) {
                    $ret = false;
                }

                if ((int)$time_arr[1] < 0 || 59 < (int)$time_arr[1]) {
                    $ret = false;
                }
            }

            if (!$ret) {
                $this->error_msg = 'validation error(pay_limit_time)';
                return false;
            }
        }

        return true;
    }

    /**
     * リクエストヘッダ作成
     */
    private function makeRequestHeader()
    {
        $token = $this->createAuthToken();

        if ($this->isMultiAPI()) {
            $content_type = 'application/x-www-form-urlencoded';
        } else {
            $content_type = 'text/plain';
        }

        return array(
            'Content-Type: '.$content_type.';charset=UTF-8',
            'Date: '.$this->auth_created_date,
            'Authorization: Bearer '.$token
        );
    }

    private function doRequest($params)
    {
        try {
            if (!$this->validateParam()) {
                $this->error_code = self::SYSTEM_ERROR_CD;
                return false;
            }

            // body部作成
            $body = http_build_query($params);

            // リクエスト作成
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->common_url.$this->end_point);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::CURL_TIMEOUT_MS);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::CURL_TIMEOUT_MS);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->makeRequestHeader());
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            $this->request_param = $params;
            // リクエスト & レスポンス
            $response = curl_exec($ch);

            $error_no = curl_errno($ch);
            $error = curl_error($ch);

            // cURLエラー
            if ($error_no) {
                $this->error_status = $error_no;
                $this->error_msg = 'CURL_ERROR:'.$error;
                curl_close($ch);
                return false;
            }

            $info = curl_getinfo($ch);

            curl_close($ch);

            // 認証/通信エラー
            $this->error_status = $info['http_code'];
            if ($this->error_status != self::RET_AUTH_OK) {
                return false;
            }

            if ($this->isMultiAPI()) {
                $res_body = json_decode(json_encode(simplexml_load_string($response)), true);
            } else {
                $res_body = json_decode($response, true);
            }

            // 応答エラー
            if (isset($res_body['status']) && !in_array($res_body['status'], $this->ret_ok)) {
                $this->error_status = $res_body['status'];
                $this->error_code = (!empty($res_body['infoCode'])) ? $res_body['infoCode'] : '';
                $this->error_msg = (!empty($res_body['info'])) ? $res_body['info'] : '';
                return false;
            }

            return $res_body;
        } catch (\Exception $e) {
            $this->error_code = self::SYSTEM_ERROR_CD;
            $this->error_msg = $e->getMessage();
            return false;
        }
    }

    public function setType($type = self::TYPE_CARD, $sub_type = null)
    {
        $this->type = $type;
        if ($type == self::TYPE_CARD) { // クレジットカード決済用
            $this->site_code  = ECONTEXT_CREDIT_SITE_CODE;
            $this->site_name  = ECONTEXT_SITE_NAME;
            $this->check_code = ECONTEXT_CREDIT_CHECK_CODE;
            $this->common_url = ECONTEXT_PAYMENT_URL;
        } elseif ($type == self::TYPE_CASH) { // 現金系(マルチ)決済用
            if ($sub_type == self::SUB_TYPE_CASH_WITHIN_4DAYS) {
                $this->site_code  = ECONTEXT_CASH_WITHIN_4DAYS_SITE_CODE;
                $this->check_code = ECONTEXT_CASH_WITHIN_4DAYS_CHECK_CODE;
            } else {
                $this->site_code  = ECONTEXT_CASH_SITE_CODE;
                $this->check_code = ECONTEXT_CASH_CHECK_CODE;
            }
            $this->site_name  = ECONTEXT_SITE_NAME;
            $this->common_url = ECONTEXT_PAYMENT_MULTI_URL;
        } elseif ($type == self::TYPE_PAYEASY) { // Payeasy決済用
            $this->site_code  = ECONTEXT_PAYEASY_SITE_CODE;
            $this->site_name  = ECONTEXT_SITE_NAME;
            $this->check_code = ECONTEXT_PAYEASY_CHECK_CODE;
            $this->common_url = ECONTEXT_PAYMENT_MULTI_URL;
        } else {
            return false;
        }

        return true;
    }

    /**
     * 与信/与信＋計上, 会員/非会員を切り換えたい場合は、それぞれwith_cap、is_memberで行う
     * @param null $order_id
     * @return array|bool
     */
    public function startSession($order_id = null)
    {
        $this->order_id = $order_id;

        if (!empty($this->session_action)) {
            if ($this->session_action == 'add') {
                $this->end_point = self::SESSONSTART_MEMBER_ADD;
            } elseif ($this->session_action == 'update') {
                $this->end_point = self::SESSONSTART_MEMBER_UPDATE;
            }

            $params = array(
                'shopID'    => $this->site_code,
                'cd3secFlg' => $this->use_3Dsecure,
                'returnURL' => $this->return_url,
                'cduserID'  => $this->cduser_id
            );
        } else {
            if ($this->is_member) {
                if ($this->with_cap) {
                    $this->end_point = self::SESSONSTART_MEMBER_CAP;
                } else {
                    $this->end_point = self::SESSONSTART_MEMBER_AUTH;
                }

                $params = array(
                    'shopID'    => $this->site_code,
                    'orderID'   => $this->order_id,
                    'cd3secFlg' => $this->use_3Dsecure,
                    'returnURL' => $this->return_url,
                    'cduserID'  => $this->cduser_id
                );
            } else {
                if ($this->with_cap) {
                    $this->end_point = self::SESSONSTART_CAP;
                } else {
                    $this->end_point = self::SESSONSTART_AUTH;
                }
                $params = array(
                    'shopID'    => $this->site_code,
                    'orderID'   => $this->order_id,
                    'cd3secFlg' => $this->use_3Dsecure,
                    'returnURL' => $this->return_url,
                    'orderDate' => date('Y/m/d'),
                );
            }

            if ($this->amount > 0) { // 任意
                $params['ordAmount'] = $this->amount;
            }
        }

        if (empty($this->end_point)) {
            $this->error_code = self::SYSTEM_ERROR_CD;
            return false;
        }

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        $this->session_token = $res_body['authentication']['sessionToken'];
        $this->request_id = $res_body['authentication']['requestID'];

        return array('session_token' => $res_body['authentication']['sessionToken'],
            'request_id' => $res_body['authentication']['requestID']);
    }

    /**
     * session_actionで注文確定要求、会員登録更新確定要求を切り換える
     * @param $session_token
     * @return bool|mixed
     */
    public function commitSession($session_token)
    {
        if (empty($this->session_action)) {
            $params = array('ordAmount' => $this->amount); // 必須
            $this->end_point = self::ORDER_COMMIT.$session_token;
        } else {
            $params = [];
            $this->end_point = self::MEMBER_COMMIT.$session_token;
        }

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    public function orderStatus($order_id)
    {
        $this->end_point = self::CARD_ORDER_STATUS;
        $this->order_id = $order_id;

        $params = array(
            'shopID'  => $this->site_code,
            'orderID' => $this->order_id
        );

        if ($this->isMultiAPI()) {
            $params['chkCode'] = $this->check_code;
            $this->end_point = '';
            $this->common_url = ECONTEXT_PAYMENT_MULTI_ORDER_STATUS_URL;
        }

        $res_body = $this->doRequest($params);
        if ($this->isMultiAPI()) { // orderStatusは接続先が違うため、オブジェクトを連続使用した場合のために元に戻す
            $this->common_url = ECONTEXT_PAYMENT_MULTI_URL;
        }

        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    /**
     * is_memberで会員/非会員を切り換える
     * @param $order_id
     * @return bool
     */
    public function orderCancel($order_id)
    {
        $this->order_id = $order_id;

        if ($this->isTokenAPI()) {
            if ($this->is_member) {
                $this->end_point = self::CARD_CANCEL_MEMBER;
            } else {
                $this->end_point = self::CARD_CANCEL;
            }
        }

        $params = array(
            'shopID'  => $this->site_code,
            'orderID' => $this->order_id
        );

        if ($this->isMultiAPI()) {
            $params['chkCode'] = $this->check_code;
            $params['paymtCode'] = self::MULTI_PAYMTCODE_CASH;
            $params['fncCode'] = self::MULTI_FNCCODE_CANCEL;
        }

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return true;
    }

    /**
     * is_memberで会員/非会員を切り換える
     * (API仕様)減額変更は出来るけど、増額変更は出来ない。計上処理後の金額変更は1回だけ
     * 追記: ECONTEXT側の特殊設定として最大９回まで（１０回目を０円キャンセル）部分返金が行える設定をしてもらった
     * @param $order_id
     * @param $amount
     * @return bool|mixed
     */
    public function orderChangeAmount($order_id, $amount)
    {
        $this->order_id = $order_id;
        $this->amount = $amount;

        if ($this->is_member) {
            $this->end_point = self::CARD_CHANGE_AMOUNT_MEMBER;
        } else {
            $this->end_point = self::CARD_CHANGE_AMOUNT;
        }

        $params = array(
            'shopID'    => $this->site_code,
            'orderID'   => $this->order_id,
            'ordAmount' => $this->amount
        );

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    /**
     * デフォルト参照、member_delete_flg設定で削除
     * @param $cduser_id
     * @return bool|mixed
     */
    public function manageMember($cduser_id)
    {
        $this->cduser_id = $cduser_id;

        if ($this->member_delete_flg) {
            $this->end_point = self::CARD_MEMBER_DELETE;
        } else {
            $this->end_point = self::CARD_MEMBER_PROPERTY;
        }

        $params = array(
            'shopID'   => $this->site_code,
            'cduserID' => $this->cduser_id
        );

        // 任意
        if (!empty($this->cduser_pw)) {
            $params['cduserPW'] = $this->cduser_pw;
        }

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    public function order($order_id)
    {
        $this->order_id = $order_id;

        $params = array(
            'shopID'       => $this->site_code,
            'chkCode'      => $this->check_code,
            'paymtCode'    => self::MULTI_PAYMTCODE_CASH,
            'fncCode'      => self::MULTI_FNCCODE_ORDER,
            'orderID'      => $this->order_id,
            'kanjiName1_1' => $this->last_name,
            'kanjiName1_2' => $this->first_name,
            'email'        => $this->email,
            'telNo'        => $this->tel,
            'itemName1'    => $this->item_name,
            'ordAmount'    => $this->amount,
            'ordAmountTax' => $this->tax,
            'commission'   => $this->fee,
            'orderDate'    => Date('Y/m/d H:i:s'),
            'payLimitDay'  => $this->pay_limit_day,
            'payLimitTime' => $this->pay_limit_time,

        );

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    /**
     * is_memberで会員/非会員を切り換える
     * @param $order_id
     * @param $amount
     * @param $shipdate
     * @return bool
     */
    public function cardCapture($order_id, $amount, $shipdate)
    {
        $this->order_id = $order_id;
        $this->amount = $amount;
        $this->shipdate = $shipdate;

        if ($this->is_member) {
            $this->end_point = self::CARD_CAPTURE_MEMBER;
        } else {
            $this->end_point = self::CARD_CAPTURE;
        }

        $params = array(
            'shopID' => $this->site_code,
            'orderID' => $this->order_id,
            'ordAmount' => $this->amount,
            'shipDate' => $this->shipdate
        );

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return true;
    }

    /**
     * is_memberで会員/非会員を切り換える
     * @param $order_id
     * @return bool|mixed
     */
    public function cardReauth($order_id)
    {
        $this->order_id = $order_id;

        if ($this->is_member) {
            $this->end_point = self::CARD_REAUTH_MEMBER;
        } else {
            $this->end_point = self::CARD_REAUTH;
        }

        $params = array(
            'shopID' => $this->site_code,
            'orderID' => $this->order_id
        );

        $res_body = $this->doRequest($params);
        if (!$res_body) {
            return false;
        }

        return $res_body;
    }

    /**
     * トークンを空にする
     */
    public function clearToken()
    {
        $this->token = '';
    }

    public function testValidation()
    {
        return $this->validateParam();
    }

    public function test()
    {
        return 'test';
    }
}
