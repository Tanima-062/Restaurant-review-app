<?php

namespace App\Services;

use App\Libs\Cipher;
use App\Libs\CommonLog;
use App\Libs\Mail\RestaurantMail;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\TmpAdminChangeReservation;
use App\Models\TmpRestaurantReservation;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Modules\Reservation\IFReservation;
use App\Modules\UserLogin;
use Illuminate\Support\Carbon;
use App\Models\Vacancy;
use App\Models\Store;

class UtilService
{
    public function __construct(
        PaymentToken $paymentToken,
        IFReservation $restaurantReservation,
        PaymentSkyticket $paymentSkyticket,
        Vacancy $vacancy
    ) {
        $this->paymentToken = $paymentToken;
        $this->restaurantReservation = $restaurantReservation;
        $this->paymentSkyticket = $paymentSkyticket;
        $this->vacancy = $vacancy;
    }

    /**
     * OrderCodeを受け取り保存して、決済側へ通知する.
     *
     * @return array
     */
    public function saveOrderCode(array $params)
    {
        try {
            // 返金処理のコールバックの場合
            if (isset($params['refundPrice'])) {
                return $this->refund($params);
            }

            $result = [
                'token' => null,
                'code' => 'E0',
                'message' => '',
            ];
            $cmApplicationIds = [];

            // orderCdチェック
            if (!isset($params['orderCode'])) {
                $result['code'] = 'E1';
                $result['message'] = 'orderCodeが取得できません';

                $title = '決済からのコールバック処理でエラー発生';
                CommonLog::notifyToChat(
                    $title,
                    $result['message'].' '.json_encode($params, true)
                );

                return $result;
            }

            // cmApplicationId取得
            if (isset($params['details'])) {
                foreach ($params['details'] as $detail) {
                    if (!isset($detail['cmApplicationId'])) {
                        continue;
                    }
                    $cmApplicationIds[] = $detail['cmApplicationId'];
                }
            }
            // cmApplicationIdチェック
            if (empty($cmApplicationIds)) {
                $result['code'] = 'E2';
                $result['message'] = 'cmApplicationIdが取得できません';

                $title = '決済からのコールバック処理でエラー発生';
                if (isset($params['orderCode'])) {
                    $title = '注文番号:'.$params['orderCode'].' '.$title;
                }
                CommonLog::notifyToChat(
                    $title,
                    $result['message'].' '.json_encode($params, true)
                );

                return $result;
            }

            // orderCd保存
            $token = null;
            $paymentTokens = PaymentToken::whereIn('cm_application_id', $cmApplicationIds)
            ->get();
            // 1回目のコールバックのみレスポンスを保存
            foreach ($paymentTokens as $paymentToken) {
                if (empty($paymentToken->call_back_values)) {
                    $paymentToken->call_back_values = $params;
                    if (!$paymentToken->save()) {
                        $result['code'] = 'E3';
                        $result['message'] = 'call_back_valuesがsaveができません';

                        $title = '決済からのコールバック処理でエラー発生';
                        if (!isset($cmApplicationIds)) {
                            $title = 'cmApplicationIds:'.$cmApplicationIds.' '.$title;
                        }
                        CommonLog::notifyToChat(
                            $title,
                            $result['message'].' '.json_encode($params, true)
                        );

                        return $result;
                    }
                    $token = $paymentToken->token;
                }
            }

            // レストラン予約変更時
            if ($paymentTokens->count() > 1) {
                foreach ($paymentTokens as $paymentToken) {
                    if ($paymentToken->is_invalid == 0 && $paymentToken->is_restaurant_change == 0) {
                        $paymentToken->is_restaurant_change = 1;
                        $paymentToken->save();
                        $tmpAdminChangeReservation = TmpAdminChangeReservation::where('reservation_id', $paymentToken->reservation_id)->where('is_invalid', 0)->whereNull('status')->first();
                        if (is_null($tmpAdminChangeReservation)) {
                            $this->changeReservation($paymentToken);
                        } else {
                            $this->adminChangeReservation($paymentToken, $tmpAdminChangeReservation);
                        }
                    }
                }
            }

            // レスポンス成型
            $result['token'] = is_null($token) ? $params['token'] : $token;
            $result['code'] = 'success';
            $result['message'] = '成功';
        } catch (\Throwable $e) {
            $title = '決済からのコールバック処理で例外発生';
            if (!empty($paymentToken)) {
                if (isset($paymentToken->reservation_id)) {
                    $title = '予約ID:'.$paymentToken->reservation_id.' '.$title;
                } elseif (isset($paymentToken->cm_application_id)) {
                    $title = 'cmApplicationId:'.$paymentToken->cm_application_id.' '.$title;
                }
            } elseif (!empty($cmApplicationIds)) {
                $title = 'cmApplicationIds:'.json_encode($cmApplicationIds, true).' '.$title;
            }
            $body = 'error'.$e->getTraceAsString().'[params] '.json_encode($params, true);
            CommonLog::notifyToChat(
                $title,
                $body
            );
            $result['message'] = $e->message;
        }

        return $result;
    }

    /**
     * レストラン - 予約変更処理(再決済).
     *
     * @param  $paymentToken
     *
     * @return void
     */
    private function changeReservation($paymentToken)
    {
        $msg = '';
        $response = [
            'status' => false,
        ];
        $dbStatus = config('code.tmpReservationStatus.fail_reserve');
        try {
            \DB::beginTransaction();
            $tmpRestaurantReservation = new TmpRestaurantReservation();
            $reservation = Reservation::find($paymentToken->reservation_id);
            $oldReservation = $reservation->replicate();
            $reservationMenu = $reservation->reservationMenus[0];
            $menu = $reservationMenu->menu;
            $info = $tmpRestaurantReservation->getInfo($paymentToken->token);
            $dt = new Carbon($info['application']['visitDate'].' '.$info['application']['visitTime']);
            $oldMenuTotal = $reservationMenu->price;
            $oldPersons = $reservation->persons;

            //paymentDetailの作成(差分のみ作成する)
            if ($reservation->total != $info['total']) {
                $personsDiff = $info['application']['persons'] - $oldPersons;
                PaymentDetail::create([
                    'reservation_id' => $reservation->id,
                    'target_id' => $menu->id,
                    'account_code' => 'MENU',
                    'price' => $info['unitPrice'],
                    'count' => $personsDiff,
                    'remarks' => '自動(予約変更)',
                ]);
            }

            //予約データ更新
            //reservationMenuの更新
            $reservationMenu->count = $info['application']['persons'];
            $reservationMenu->unit_price = $info['unitPrice'];
            $reservationMenu->price = $info['menuTotal'];
            $reservationMenu->save();

            //reservationの更新
            $reservation->persons = $info['application']['persons'];
            $reservation->pick_up_datetime = $dt;
            if (isset($info['customer']['request'])) {
                $reservation->request = Cipher::decrypt($info['customer']['request']);
            }
            $reservation->total = $info['total'];
            $reservation->save();

            //外部APIの予約変更処理
            if ($reservation->external_reservation_id) {
                if (!$this->restaurantReservation->changeReservation($reservation, $msg)) {
                    $callBackValue = $paymentToken->call_back_values;

                    $title = '外部APIの予約変更処理でエラー発生';
                    $title = '予約ID:'.$reservation->id.' '.$title;
                    $body = "[changeReservation] 外部APIの接続に失敗しました。管理画面から与信の取消を行ってください。　注文番号:{$callBackValue['orderCode']}　SKYTICKET申込番号:{$paymentToken->cm_application_id}　グルメ予約番号:{$reservation->id}";
                    CommonLog::notifyToChat(
                        $title,
                        $body
                    );
                    throw new \Exception($msg);
                }
            }

            // 在庫更新(自社在庫の場合は一度在庫を戻してから更新)
            if (empty($reservation->external_reservation_id)) {
                $oldDt = new Carbon($oldReservation->pick_up_datetime);
                $this->vacancy->updateStock(-$oldReservation->persons, $menu, $oldDt);
            }
            $this->vacancy->updateStock($info['application']['persons'], $menu, $dt);

            $response = [
                'status' => true,
                'message' => '',
                'loginForm' => [
                    'reservationNo' => key(config('code.appCd.rs')).$reservation->id,
                    'tel' => $reservation->tel,
                    'isMenberUser' => UserLogin::isMember(Cipher::decrypt($reservation->email)),
                    'isLogin' => UserLogin::isLogin(),
                ],
            ];
            $dbStatus = config('code.tmpReservationStatus.complete');

            //予約変更メールの送信
            $storeEmails = $this->getStoreEmails($menu->store_id);
            foreach ($storeEmails as $storeEmail) {
                $restaurantMailClient = new RestaurantMail($reservation->id);
                $restaurantMailClient->userChangeReservationForClient($oldReservation, $storeEmail);
            }
            $restaurantMailUser = new RestaurantMail($reservation->id);
            $restaurantMailUser->userChangeReservationForUser($oldReservation);

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollback();
            $title = 'レストラン予約変更処理で例外発生';
            $title = '予約ID:'.$reservation->id.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
            $paymentToken->is_invalid = 1;
            $paymentToken->save();
            $response = [
                'status' => false,
                'message' => !empty($msg) ? $msg : \Lang::get('message.restaurantChangeFailure'),
                'loginForm' => [
                    'reservationNo' => key(config('code.appCd.rs')).$reservation->id,
                    'tel' => $reservation->tel,
                    'isMenberUser' => UserLogin::isMember(Cipher::decrypt($reservation->email)),
                    'isLogin' => UserLogin::isLogin(),
                ],
            ];
        } finally {
            $tmpRsRsv = TmpRestaurantReservation::where('session_id', $paymentToken->token)->first();
            $tmpRsRsv->response = $response;
            $tmpRsRsv->status = $dbStatus;
            $tmpRsRsv->save();
        }

        //与信キャンセル処理
        if ($response['status']) {
            $paymentTokens = PaymentToken::where('reservation_id', $reservation->id)->where('is_restaurant_change', 1)->where('is_invalid', 0)->get();
            $oldPaymentToken = $paymentTokens->sortBy('created_at')->first();
            $oldCallBackValue = json_decode($oldPaymentToken->call_back_values, true);
            $result = [];
            if (!$this->paymentSkyticket->cancelPayment($oldCallBackValue['orderCode'], $result)) {
                $title = 'レストラン予約変更処理の与信キャンセルで例外発生';
                $title = '予約ID:'.$reservation->id.' '.$title;
                $body = "[changeReservation] 古い与信のキャンセルに失敗しました。管理画面から与信の取消を行ってください。　注文番号:{$oldCallBackValue['orderCode']}　SKYTICKET申込番号:{$oldPaymentToken->cm_application_id}　グルメ予約番号:{$reservation->id}";
                CommonLog::notifyToChat(
                    $title,
                    $body
                );
            }

            $oldPaymentToken->is_invalid = 1;
            $oldPaymentToken->save();
        }
    }

    public function adminChangeReservation($paymentToken, $tmpAdminChangeReservation)
    {
        $msg = '';
        $dbStatus = config('code.tmpReservationStatus.fail_reserve');

        try {
            \DB::beginTransaction();
            $reservation = Reservation::find($paymentToken->reservation_id);
            $oldReservation = $reservation->replicate();
            $changeInfo = json_decode($tmpAdminChangeReservation->info, true);
            $reservationMenu = $reservation->reservationMenus[0];
            $menu = $reservationMenu->menu;
            $dt = new Carbon($changeInfo['pick_up_datetime']);
            $oldMenuTotal = $reservationMenu->price;
            $oldPersons = $reservation->persons;

            //paymentDetailの作成(金額に変更があった場合に差分のみ作成する)
            if ($reservation->total != $changeInfo['total']) {
                $personsDiff = $changeInfo['persons'] - $oldPersons;
                PaymentDetail::create([
                    'reservation_id' => $reservation->id,
                    'target_id' => $menu->id,
                    'account_code' => 'MENU',
                    'price' => $changeInfo['unitPrice'],
                    'count' => $personsDiff,
                    'remarks' => '自動(予約変更)',
                ]);
            }

            //予約データ更新
            //reservationMenuの更新
            $reservationMenu->count = $changeInfo['persons'];
            $reservationMenu->unit_price = $changeInfo['unitPrice'];
            $reservationMenu->price = $changeInfo['menuTotal'];
            $reservationMenu->save();

            //reservationの更新
            $reservation->persons = $changeInfo['persons'];
            $reservation->pick_up_datetime = $dt;
            $reservation->total = $changeInfo['total'];
            $reservation->payment_status = config('code.paymentStatus.auth.key');
            $reservation->payment_limit = null;
            $reservation->save();

            //外部APIの予約変更処理
            if ($reservation->external_reservation_id) {
                if (!$this->restaurantReservation->changeReservation($reservation, $msg)) {
                    $callBackValue = $paymentToken->call_back_values;

                    $title = '外部APIの予約変更(管理画面)処理でエラー発生';
                    $title = '予約ID:'.$reservation->id.' '.$title;
                    $body = "[changeReservation] 外部APIの接続に失敗しました。管理画面から与信の取消を行ってください。　注文番号:{$callBackValue['orderCode']}　SKYTICKET申込番号:{$paymentToken->cm_application_id}　グルメ予約番号:{$reservation->id}";
                    CommonLog::notifyToChat(
                        $title,
                        $body
                    );
                    throw new \Exception($msg);
                }
            }

            // 在庫更新(自社在庫の場合は一度在庫を戻してから更新)
            if (empty($reservation->external_reservation_id)) {
                $oldDt = new Carbon($oldReservation->pick_up_datetime);
                $this->vacancy->updateStock(-$oldReservation->persons, $menu, $oldDt);
            }
            $this->vacancy->updateStock($changeInfo['persons'], $menu, $dt);

            $dbStatus = config('code.tmpReservationStatus.complete');

            //@todo メール送信

            \DB::commit();
        } catch (\Throwable $e) {
            \DB::rollback();
            $title = 'レストラン予約変更(管理画面)処理で例外発生';
            $title = '予約ID:'.$reservation->id.' '.$title;
            CommonLog::notifyToChat(
                $title,
                $e->getMessage()
            );
        } finally {
            $tmpAdminChangeReservation->status = $dbStatus;
            $tmpAdminChangeReservation->save();
        }

        //与信キャンセル処理
        if ($dbStatus == config('code.tmpReservationStatus.complete')) {
            $paymentTokens = PaymentToken::where('reservation_id', $reservation->id)->where('is_restaurant_change', 1)->where('is_invalid', 0)->get();
            $oldPaymentToken = $paymentTokens->sortBy('created_at')->first();
            $oldCallBackValue = json_decode($oldPaymentToken->call_back_values, true);
            $result = [];
            if (!$this->paymentSkyticket->cancelPayment($oldCallBackValue['orderCode'], $result)) {
                $title = 'レストラン予約変更(管理画面)処理の与信キャンセルで例外発生';
                $title = '予約ID:'.$reservation->id.' '.$title;
                $body = "[changeReservation] 古い与信のキャンセルに失敗しました。管理画面から与信の取消を行ってください。　注文番号:{$oldCallBackValue['orderCode']}　SKYTICKET申込番号:{$oldPaymentToken->cm_application_id}　グルメ予約番号:{$reservation->id}";
                CommonLog::notifyToChat(
                    $title,
                    $body
                );
            }

            $oldPaymentToken->is_invalid = 1;
            $oldPaymentToken->save();
        }

    }

    private function refund($params)
    {
        foreach ($params as $data) {
            if (isset($data['cmApplicationId'])) {
                $paymentTokens = PaymentToken::where('cm_application_id', $data['cmApplicationId'])
                ->where('is_invalid', 1)
                ->get();
                $paymentToken = $paymentTokens->sortByDesc('created_at')->first();

                // ステータスを返金済に変更
                $refunds = Refund::where('reservation_id', $paymentToken->reservation_id)->get();
                foreach ($refunds as $refund) {
                    $refund->status = config('code.refundStatus.refunded.key');
                    $refund->save();
                }

                // paymentStatusも変更
                $reservation = Reservation::find($paymentToken->reservation_id);
                $reservation->payment_status = config('code.paymentStatus.refunded.key');
                $reservation->save();

                // レスポンス成型
                $result = [];
                $result['token'] = $paymentToken->token;
                $result['code'] = 'success';
                $result['message'] = '成功';

                return $result;
            }
        }
    }

    public function getStoreEmails($storeId)
    {
        $storeEmails = [];
        $store = Store::find($storeId);

        if (!is_null($store->email_1)) {
            $storeEmails[] = $store->email_1;
        }
        if (!is_null($store->email_2)) {
            $storeEmails[] = $store->email_2;
        }
        if (!is_null($store->email_3)) {
            $storeEmails[] = $store->email_3;
        }

        return $storeEmails;
    }
}
