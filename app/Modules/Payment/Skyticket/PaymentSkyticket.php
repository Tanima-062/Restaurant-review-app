<?php

namespace App\Modules\Payment\Skyticket;

use App\Libs\Cipher;
use App\Libs\CommonLog;
use App\Models\CmThApplication;
use App\Models\CmTmUser;
use App\Models\Menu;
use App\Models\Option;
use App\Models\PaymentToken;
use App\Models\Price;
use App\Models\Reservation;
use App\Modules\Payment\IFPayment;
use App\Modules\UserLogin;
use Illuminate\Support\Carbon;

class PaymentSkyticket implements IFPayment
{
    const GOURMET_RETURN_URL = 'reservation/complete';
    const GOURMET_RESTAURANT_CHANGE_URL = 'reservation/update/complete';
    const GOURMET_AUTHORIZE_URL = 'v1/ja/payment/authCallback';
    const GOURMET_CAPTURE_URL = 'v1/ja/payment/authCallback';
    const GOURMET_CANCEL_URL = 'v1/ja/payment/authCallback';
    const GOURMET_CANCEL_RETURN_URL = 'v1/ja/payment/authCallback';
    const GOURMET_RETURN_CONFIRM_URL = 'reservation/confirm/';
    // PF側の設定です
    const PAYMENT_LIST_PER_PAGE = 50;

    private $skyticket;
    private $cmApplicationId;
    private $userId;
    private $appUrl;

    public function __construct(
        Skyticket $skyticket
    ) {
        $this->skyticket = $skyticket;
        $this->appUrl = config('const.payment.app_url.'.env('APP_ENV'));
    }

    /**
     * 決済事前処理. フロントからリクエストされる想定.
     *
     * @param array info 予約情報
     * @param int price 支払い金額
     * @param string checkInDate
     * @param string checkOutDate
     *
     * @return string redirectUrl
     *
     * @throws Exception
     */
    public function save(array $info, int $price, string $checkInDate = null, string $checkOutDate = null)
    {
        try {
            $res = [
                'paymentUrl' => '',
                'result' => [
                    'status' => false,
                    'message' => '失敗',
                ],
            ];

            // 利用サービス（cartId取得で必要）
            $appCd = is_null($checkInDate) && is_null($checkOutDate) ? config('const.newPayment.apiServiceCd.to.value') : config('const.newPayment.apiServiceCd.rs.value');

            // cmApplicationIdを取得
            if (!empty($info['payment'])) {
                $this->cmApplicationId = $info['payment']['cm_application_id'];
                $this->userId = $info['payment']['user_id'];
            } else {
                list($this->cmApplicationId, $this->userId) = CmThApplication::createEmptyApplication();
            }

            $paymentDetailDetail = [];
            // ユーザデータ取得
            $user = CmTmUser::find($this->userId);
            foreach ($info['application']['menus'] as $menu) {
                $quantity = is_null($checkInDate) ? $menu['menu']['count'] : $info['application']['persons'];
                $menuInfo = Menu::find($menu['menu']['id']);
                $restaurantName = $menuInfo->store->name;
                if (isset($menu['menu'])) {
                    $tmpItem = [];
                    $tmpItem['title'] = $menuInfo->name;
                    $tmpItem['titleIndent'] = true;
                    $tmpItem['subTitle'] = '';
                    $tmpItem['currency'] = 'JPY';
                    $date = is_null($checkInDate) ? $info['application']['pickUpDate'] : $info['application']['visitDate'];
                    $tmpItem['price'] = (int) Price::available($menu['menu']['id'], $date)->first()->price;
                    $tmpItem['quantity'] = $quantity;
                    $tmpItem['order'] = 1;
                    $paymentDetailDetail[] = $tmpItem;
                }
                if (isset($menu['options'])) {
                    foreach ($menu['options'] as $option) {
                        $op = Option::find($option['id']);
                        $tmpItem = [];
                        $tmpItem['title'] = $op->keyword.' '.$op->contents;
                        $tmpItem['titleIndent'] = true;
                        $tmpItem['subTitle'] = '';
                        $tmpItem['currency'] = 'JPY';
                        $tmpItem['price'] = $op->price;
                        $tmpItem['quantity'] = $quantity;
                        $tmpItem['order'] = 1;
                        $paymentDetailDetail[] = $tmpItem;
                    }
                }
            }
            /*
            $paymentDetailDetail = [];
            // PaymentDetailDetail作成

            $paymentDetailDetail[0] = [
                'title' => 'プラン料金',
                'titleIndent' => true,
                'subTitle' => '',
                'currency' => 'JPY',
                'price' => 1000,
                'quantity' => 1,
                'order' => 1, // この配列が複数あったときの表示順 一個しかないので1にする
            ];
            $paymentDetailDetail[1] = [
                'title' => '追加オプション',
                'titleIndent' => true,
                'subTitle' => '',
                'currency' => 'JPY',
                'price' => 500,
                'quantity' => 1,
                'order' => 1, // この配列が複数あったときの表示順 一個しかないので1にする
            ];

            $checkInDate = '2021-10-25 17:00:00';
            $checkOutDate = '2021-10-25 19:00:00';
            */
            // PaymentDetail作成
            if (is_null($checkInDate)) {
                // テイクアウト
                $pickupDatetime = new Carbon($info['application']['pickUpDate'].' '.$info['application']['pickUpTime']);
                $tmpPaymentDetail = [[
                    'datetime' => $pickupDatetime->toJSON(),
                    'restaurantName' => $restaurantName,
                    'detail' => $paymentDetailDetail,
                ]];
                $paymentDetail = [
                    'to' => [
                        [
                            'subTotalPrice' => $price,
                            'currency' => 'JPY',
                            'services' => $tmpPaymentDetail
                        ]
                    ],
                ];
            } else {
                // レストラン
                $pickupDatetime = new Carbon($info['application']['visitDate'].' '.$info['application']['visitTime']);
                $tmpPaymentDetail = [[
                    'checkInDate' => $checkInDate,
                    'checkOutDate' => $checkOutDate,
                    'restaurantName' => $restaurantName,
                    'detail' => $paymentDetailDetail,
                ]];
                $paymentDetail = [
                    'rs' => [
                        [
                            'subTotalPrice' => $price,
                            'currency' => 'JPY',
                            'services' => $tmpPaymentDetail
                        ]
                    ]
                ];
            }
            //\Log::debug($paymentDetail);
            // registrationData detail作成
            $detail = [];

            $menu = $options = null;
            foreach ($info['application']['menus'] as $applicationMenu) {
                if (isset($applicationMenu['menu'])) {
                    $menu = $applicationMenu['menu'];
                }
                if (isset($applicationMenu['options'])) {
                    $options = $applicationMenu['options'];
                }
            }
            $menuData = Menu::find($menu['id']);
            $menu['name'] = $menuData->name;
            $menu['price'] = Price::available($menuData->id, $pickupDatetime->format('Y-m-d'))->first()->price;
            $detail['jp']['menus'][] = $menu;

            if (!is_null($checkInDate)) {
                $detail['jp']['type'] = config('code.gmServiceCd.rs');
                $detail['jp']['persons'] = $info['application']['persons'];
                $detail['jp']['visitDate'] = $info['application']['visitDate'];
                $detail['jp']['visitTime'] = $info['application']['visitTime'];
            } else {
                $detail['jp']['type'] = config('code.gmServiceCd.to');
                $detail['jp']['persons'] = null;
                $detail['jp']['estimatedDate'] = $info['application']['pickUpDate'];
                $detail['jp']['estimatedTime'] = $info['application']['pickUpTime'];
            }

            if (!empty($options)) {
                foreach ($options as $key => $option) {
                    $optionData = Option::find($option['id']);
                    $options[$key]['contents'] = $optionData->contents;
                    $options[$key]['keyword'] = $optionData->keyword;
                    $options[$key]['price'] = $optionData->price;
                }
                $detail['jp']['menus'][] = $options;
            }

            // registrationData作成
            $request = isset($info['customer']['request']) ? $info['customer']['request'] : '';
            $registrationCommonData = [
                'cmApplicationId' => $this->cmApplicationId,
                'currency' => 'JPY',
                'lang' => 'ja-JP',
                'totalPrice' => $price,
                'totalOtherPrice' => $price,
                'localPayment' => false,
                'basicAt' => $pickupDatetime->format('Y-m-d H:i:s'),
                'pointCd' => '',
                'bonusData' => [],
                'applicant' => [
                    'userId' => $this->userId,
                    'firstName' => Cipher::encrypt($info['customer']['firstName']),
                    'lastName' => Cipher::encrypt($info['customer']['lastName']),
                    'email' => Cipher::encrypt($info['customer']['email']),
                    'tel' => Cipher::encrypt($info['customer']['tel']),
                    'request' => $request,
                ],
                'detail' => $detail,
            ];

            $key = is_null($checkInDate) ? 'to' : 'rs';
            $registrationData = [
                'appendix' => [
                    'discount' => [],
                ],
                'reservation' => [
                    $key => [$registrationCommonData],
                ],
            ];

            $params['cartId'] = !empty($info['payment']) ? $info['payment']['cartId'] : $this->getCartId($this->cmApplicationId, $appCd);
            $params['price'] = $price;
            $params['otherPrice'] = $price;
            $params['currency'] = 'JPY';
            $params['rate'] = 1;
            $params['serviceCd'] = $key;
            $params['returnUrl'] = !empty($info['payment']) ? env('APP_URL').self::GOURMET_RESTAURANT_CHANGE_URL : env('APP_URL').self::GOURMET_RETURN_URL;
            $params['cancelReturnUrl'] = env('APP_URL').self::GOURMET_RETURN_CONFIRM_URL; //'予約確認画面のURL';
            $params['userId'] = $this->userId;
            $params['mail'] = $info['customer']['email'];
            $params['tel'] = $info['customer']['tel'];
            $params['firstName'] = $info['customer']['firstName'];
            $params['lastName'] = $info['customer']['lastName'];
            $params['lang'] = 'ja';
            $params['hold'] = 0;
            $ret = UserLogin::getLoginUser();
            $params['isLogin'] = isset($ret['userId']) ? 1 : 0;
            $params['isMember'] = $user->member_status;
            $params['isCreditSave'] = $user->credit_save;
            $params['authorizeUrl'] = env('APP_URL').self::GOURMET_AUTHORIZE_URL;
            $params['cancelUrl'] = env('APP_URL').self::GOURMET_CANCEL_URL; //キャンセル処理後に結果を送る先のURL　決済サーバから各サービスURL(API)に返します
            $params['captureUrl'] = env('APP_URL').self::GOURMET_CAPTURE_URL;
            $params['paymentDetail'] = json_encode($paymentDetail);
            $params['registrationData'] = json_encode($registrationData);
            $date = Carbon::now();
            $params['dueDate'] = $date->addMinutes(15)->format('Y-m-d H:i:s');  // 支払期限を現在時刻＋15分に設定しておく

            // 空でもキーは必須
            $params['advertisingCode'] = '';
            // 支払情報を登録し、決済APIのリダイレクト先を取得する
            $paymentToken = new PaymentToken();
            $paymentToken->cm_application_id = $this->cmApplicationId;
            $paymentToken->save();

            $result = $this->skyticket->registerPayment($params);
            // payment tokenテーブルへcm_application_id, tokenを保存
            $paymentTokenUpdate = PaymentToken::find($paymentToken->id);
            $paymentTokenUpdate->token = $result['token'];
            $paymentTokenUpdate->reservation_id = !empty($info['payment']) ? $info['payment']['reservationId'] : null;
            $paymentTokenUpdate->save();

            $res['paymentUrl'] = $result['url'];
            $res['session_token'] = $result['token'];
            $res['result']['status'] = true;
            $res['result']['message'] = '成功';
        } catch (\Throwable $e) {
            $title = 'saveで例外発生';
            if (isset($this->cmApplicationId)) {
                $title = 'cmApplicationId:'.$this->cmApplicationId.' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
            $res['result']['message'] = $e->getMessage();
        }

        return $res;
    }

    /**
     * 決済後処理.決済側からリクエストされる想定.
     *
     * @param string callBackValues
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function authCallback($callBackValues)
    {
        try {
            // 支払い確定で必要になるorderCodeを保存(payment_tokenテーブルへレスポンスを保存)
            $paymentToken = new PaymentToken();
            $paymentToken->call_back_values = $callBackValues;
            $paymentToken->save();

            // 決済結果を通知(与信止め)
        } catch (\Throwable $e) {
            $title = 'authCallbackで例外発生';
            $cmApplicationId = null;
            if (isset($callBackValues->details)) {
                foreach ($callBackValues->details as $detail) {
                    if (empty($cmApplicationId)) {
                        foreach ($detail as $k => $v) {
                            if ($k === 'cmApplicationId') {
                                $cmApplicationId = $v;
                                $title = 'cmApplicationId:'.$cmApplicationId.' '.$title;
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
        }
    }

    /**
     * 支払確定. バッチからの利用を想定.
     *
     * @param string orderCode
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function settlePayment(string $orderCode, &$result)
    {
        try {
            $params['orderCode'] = $orderCode;
            // 支払確定
            $result = $this->skyticket->settlePayment($params);

            return true;
        } catch (\Throwable $e) {
            $title = 'settlePaymentで例外発生';
            $title = '注文番号:'.$orderCode.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * 取り消し.
     *
     * @param string orderCode
     * @param string sessionToken
     * @param array result
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function cancelPayment(string $orderCode, &$result)
    {
        try {
            $params['orderCode'] = $orderCode;
            // 取り消し
            $result = $this->skyticket->cancelPayment($params);

            return true;
        } catch (\Throwable $e) {
            $title = 'cancelPaymentで例外発生';
            $title = '注文番号:'.$orderCode.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );

            return false;
        }
    }

    /**
     * 返金要求を作成または更新する。
     *
     * @param PaymentToken paymentToken
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function registerRefundPayment(PaymentToken $paymentToken, &$result)
    {
        try {
            $reservation = Reservation::find($paymentToken->reservation_id);
            $serviceCd = strtolower($reservation->app_cd);
            $callBackValues = json_decode($paymentToken->call_back_values, true);
            $params['orderCode'] = $callBackValues['orderCode'];
            $params['currency'] = 'JPY';
            $params['rate'] = 1;

            if (is_null($paymentToken->refundPrice)) {
                $price = $callBackValues['totalPrice'];
                $totalPrice = $callBackValues['totalOtherPrice'];
            } else {
                $price = $totalPrice = $paymentToken->refundPrice;
            }

            $refundInfo = [
                'cmApplicationId' => $paymentToken->cm_application_id,
                'serviceCd' => $serviceCd,
                'price' => $price,
                'otherPrice' => $totalPrice,
                'reasonId' => 8,
                'isCancel' => 1,
                'refundDt' => date('Y-m-d H:i:s'),
                'limitDt' => date('Y-m-d H:i:s', strtotime('30 days')),
                'refundUrl' => $this->appUrl.self::GOURMET_CANCEL_RETURN_URL,
            ];
            $params['refundInfo'] = [$refundInfo];
            // 返金要求を作成または更新する。
            $result = $this->skyticket->registerRefundPayment($params);

            return true;
        } catch (\Throwable $e) {
            $title = 'registerRefundPaymentで例外発生';
            // refund時点なので基本あるはず
            if (isset($paymentToken->reservation_id)) {
                $title = '予約ID:'.$paymentToken->reservation_id.' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
            \Log::debug('PaymentSkyticket.php@registerRefundPayment error'.$title.':'.$e->getMessage());

            return false;
        }
    }

    /**
     * 返金要求を削除する。
     *
     * @param string cartId
     * @param int refundId
     * @param int cmApplicationId
     *
     * @return bool true:成功 false:失敗
     *
     * @throws Exception
     */
    public function deleteRefundPayment($cartId, $refundId, $cmApplicationId)
    {
        try {
            $params['cartId'] = $cartId;
            $params['refundId'] = $refundId;
            $params['cmApplicationId'] = $cmApplicationId;
            $params['serviceCd'] = config('code.serviceCd');

            // 返金要求を削除する。
            $result = $this->skyticket->deleteRefundPayment($params);

            return $result;
        } catch (\Throwable $e) {
            $title = 'deleteRefundPaymentで例外発生';
            $title = 'cmApplicationId:'.$cmApplicationId.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
        }
    }

    /**
     * 入金情報取得.
     *
     * @param array cartId
     *
     * @return array result
     *
     * @throws Exception
     */
    public function getPayment($params)
    {
        try {
            $getParams['cartId'] = $params['cartId'];
            // 決済代行側の入金状況を取得する。
            $result = $this->skyticket->getPayment($getParams);

            return $result;
        } catch (\Throwable $e) {
            $title = 'getPaymentで例外発生';
            if (isset($params['cartId'])) {
                $title = 'cartID:'.$params['cartId'].' '.$title;
            }
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * 入金一覧情報取得.
     *
     * @param array params
     *
     * @return array result
     *
     * @throws Exception
     */
    public function getPaymentList($params)
    {
        try {
            if (isset($params['reservationId'])) {
                $paymentToken = PaymentToken::where('reservation_id', $params['reservationId'])->first();
                if (!is_null($paymentToken)) {
                    $getParams['cmApplicationId'] = $paymentToken->cm_application_id;
                }
            }

            if (isset($params['date_from'])) {
                $getParams['createdAtStart'] = $params['date_from'];
            }

            if (isset($params['serviceCd'])) {
                $getParams['serviceCd'] = $params['serviceCd'];
            }

            if (isset($params['date_to'])) {
                $getParams['createdAtEnd'] = $params['date_to'];
            }

            if (isset($params['id'])) {
                $getParams['cmApplicationId'] = $params['id'];
            }

            if (isset($params['cart_id'])) {
                $getParams['cartId'] = $params['cart_id'];
            }

            if (isset($params['order_code'])) {
                $getParams['orderCode'] = $params['order_code'];
            }

            if (isset($params['progress'])) {
                $getParams['progress'] = $params['progress'];
            }

            if (isset($params['page'])) {
                $getParams['page'] = $params['page'];
            }

            //$params['methodId'] = $params['date_from'];
            // 決済代行側の入金状況を取得する。
            $result = $this->skyticket->getPaymentList($getParams);

            return $result;
        } catch (\Throwable $e) {
            $title = 'getPaymentListで例外発生';
            if (isset($params['reservationId'])) {
                $title = '予約ID:'.$params['reservationId'].' '.$title;
            } elseif (isset($params['id'])) {
                $title = 'cmApplicationId:'.$params['id'].' '.$title;
            } elseif (isset($params['order_code'])) {
                $title = 'orderCode:'.$params['order_code'].' '.$title;
            } elseif (isset($params['cart_id'])) {
                $title = 'cartId:'.$params['cart_id'].' '.$title;
            }

            // // Chatworkにログを出す場合
            // CommonLog::notifyToChat(
            //     $title,
            //     $e->getMessage()
            // );

            // ログサーバーにログを出す場合
            \Log::debug('['.$title.']'.$e->getMessage());

            return [];
        }
    }

    public function getCartId($cmApplicationId, $appCd)
    {
        try {
            // 支払確定
            $result = $this->skyticket->getCartId($cmApplicationId, $appCd);

            // 新実装（cart_id発行）
            return $result;
        } catch (\Throwable $e) {
            $title = 'getCartIdで例外発生';
            $title = 'cmApplicationId:'.$cmApplicationId.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
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
}
