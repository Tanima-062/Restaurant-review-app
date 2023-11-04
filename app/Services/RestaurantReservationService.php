<?php

namespace App\Services;

use App\Libs\Cipher;
use App\Libs\CommonLog;
use App\Libs\Mail\RestaurantMail;
use App\Models\CancelDetail;
use App\Models\CancelFee;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Menu;
use App\Models\MessageBoard;
use App\Models\OrderInterval;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservationGenre;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Stock;
use App\Models\Store;
use App\Models\TmpRestaurantReservation;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Modules\Reservation\IFReservation;
use App\Modules\Reservation\IFStock;
use App\Modules\UserLogin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use App\Models\Option;
use App\Models\Maintenance;
use App\Models\TmpAdminChangeReservation;
use App\Models\Vacancy;
use App\Models\CallReachJob;
use Exception;

class RestaurantReservationService
{
    /**
     * @var Reservation
     */
    private $reservation;
    /**
     * @var ReservationCancelPolicy
     */
    private $reservationCancelPolicy;
    /**
     * @var ReservationMenu
     */
    private $reservationMenu;
    /**
     * @var ReservationGenre
     */
    private $reservationGenre;
    /**
     * @var ReservationStore
     */
    private $reservationStore;
    /**
     * @var ReservationOption
     */
    private $reservationOption;
    /**
     * @var TmpRestaurantReservation
     */
    private $tmpRestaurantReservation;
    /**
     * @var PaymentDetail
     */
    private $paymentDetail;
    /**
     * @var Menu
     */
    private $menu;
    /**
     * @var OrderInterval
     */
    private $orderInterval;
    /**
     * @var Stock
     */
    private $stock;
    /**
     * @var \App\Services\PaymentService
     */
    private $paymentService;
    /**
     * @var \App\Modules\Reservation\IFStock
     */
    private $restaurantStock;
    /**
     * @var \App\Modules\Reservation\IFReservation
     */
    private $restaurantReservation;
    /**
     * @var \App\Models\CancelFee
     */
    private $cancelFee;
    /**
     * @var App\Models\Refund
     */
    private $refund;
    /**
     * @var App\Models\Vacancy
     */
    private $vacancy;
    /**
     * @var App\Models\CallReachJob
     */
    private $callReachJob;


    public function __construct(
        Reservation $reservation,
        ReservationCancelPolicy $reservationCancelPolicy,
        ReservationGenre $reservationGenre,
        ReservationMenu $reservationMenu,
        ReservationStore $reservationStore,
        ReservationOption $reservationOption,
        TmpRestaurantReservation $tmpRestaurantReservation,
        PaymentDetail $paymentDetail,
        Menu $menu,
        PaymentService $paymentService,
        OrderInterval $orderInterval,
        Stock $stock,
        IFStock $restaurantStock,
        IFReservation $restaurantReservation,
        PaymentSkyticket $paymentSkyticket,
        PaymentToken $paymentToken,
        CancelFee $cancelFee,
        Refund $refund,
        Vacancy $vacancy,
        CallReachJob $callReachJob
    ) {
        $this->reservation = $reservation;
        $this->reservationCancelPolicy = $reservationCancelPolicy;
        $this->reservationGenre = $reservationGenre;
        $this->reservationMenu = $reservationMenu;
        $this->reservationStore = $reservationStore;
        $this->reservationOption = $reservationOption;
        $this->tmpRestaurantReservation = $tmpRestaurantReservation;
        $this->paymentDetail = $paymentDetail;
        $this->menu = $menu;
        $this->paymentService = $paymentService;
        $this->orderInterval = $orderInterval;
        $this->stock = $stock;
        $this->restaurantStock = $restaurantStock;
        $this->restaurantReservation = $restaurantReservation;
        $this->paymentSkyticket = $paymentSkyticket;
        $this->paymentToken = $paymentToken;
        $this->cancelFee = $cancelFee;
        $this->refund = $refund;
        $this->vacancy = $vacancy;
        $this->callReachJob = $callReachJob;
    }

    /**
     * レストラン-予約申し込み事前処理.
     *
     * @return bool true:成功 false:失敗
     */
    public function save(array $params, array &$resValues = [])
    {
        $msg = null;
        $result = [
            'status' => false,
            'message' => '',
        ];
        $resValues['result'] = $result;
        $sumPrice = PaymentService::sumPriceRestarant($params);
        if ($sumPrice === 0) {  //席のみの場合(合計金額が0円だった場合)
            //cmApplicationIdを取得
            list($this->cmApplicationId, $this->userId) =
            CmThApplication::createEmptyApplication();
            $params['payment']['cm_application_id'] = $this->cmApplicationId;
            $params['payment']['user_id'] = $this->userId;
            $result['paymentUrl'] = '';
            $hashKey = md5(uniqid(rand(), 1));  //セッションID
            $result['session_token'] = $hashKey;
        } else {
            $checkInDatetime = new Carbon($params['application']['visitDate'].' '.$params['application']['visitTime']);
            $addTime = Menu::find($params['application']['menus'][0]['menu']['id'])->provided_time;
            $checkOutDatetime = $checkInDatetime->copy()->addMinutes($addTime);
            $result = $this->paymentSkyticket->save($params, $sumPrice, $checkInDatetime->format('Y-m-d H:i:s'), $checkOutDatetime->format('Y-m-d H:i:s'));
            if (!$result['result']['status']) {
                $resValues['result']['message'] = $result['result']['message'];
                return false;
            }
            $params['payment']['cm_application_id'] = $this->paymentSkyticket->getCmApplicationId();
            $params['payment']['user_id'] = $this->paymentSkyticket->getUserId();
        }

        //データチェック
        try {
            $dt = new Carbon($params['application']['visitDate'].' '.$params['application']['visitTime']);
            $now = Carbon::now();

            //レストランは一個しかメニュー入ってこないので回さない
            $menu = $params['application']['menus'][0];
            $menuObj = Menu::find($menu['menu']['id']);

            // opening_hours store menuの曜日と祝日のチェック
            if (!$this->menu->canSale($menuObj->id, $menuObj->store_id, $dt, $msg)) {
                throw new \Exception($msg);
            }

            //空席,在庫チェック
            if (!empty($menuObj->store->external_api)) {
                //外部接続があれば外部の空席確認
                if (!$this->restaurantStock->isVacancy($dt, $params['application']['persons'], $menuObj->store_id, $msg)) {
                    throw new \Exception($msg);
                }
            } else {
                //外部接続がなければskyの在庫確認
                if (!$this->hasRestaurantStock($menuObj, $dt, $params['application']['persons'], $msg)) {
                    throw new \Exception($msg);
                }
            }

            // コースの提供時間のチェック
            if (!$this->isSalesTime($menuObj, $dt, $msg)) {
                throw new \Exception($msg);
            }

            //利用可能下限、上限人数の確認
            if (!$this->isAvailableNumber($menuObj, $params['application']['persons'], $msg)) {
                throw new \Exception($msg);
            }

        } catch (\Throwable $e) {
            \Log::error(
                sprintf(
                    '::sessionId=(%s), info=(%s), error=%s',
                    $result['session_token'],
                    json_encode($params),
                    $e
                ));

            $msg = empty($msg) ? 'check failed' : $msg;
            $resValues['result']['message'] = $msg;

            return false;
        }

        $isSaved = $this->tmpRestaurantReservation->saveSession($result['session_token'], $params, $msg);

        if ($isSaved) {
            $resValues['sessionToken'] = isset($result['session_token']) ? $result['session_token'] : '';
            $resValues['paymentUrl'] = $result['paymentUrl'];
            $resValues['result']['status'] = true;
        } else {
            $resValues['result']['message'] = $msg;
        }

        return $isSaved;
    }

    /**
     * レストラン-予約完了.
     *
     * @param array $resValues
     *
     * @throws
     *
     * @return bool true:成功 false:失敗
     */
    public function complete(string $sessionToken, &$resValues = [])
    {
        $errMsg = '';
        $dbStatus = config('code.tmpReservationStatus.fail_reserve');

        $res = TmpRestaurantReservation::where('session_id', $sessionToken)->first();
        $info = $this->tmpRestaurantReservation->getInfo($sessionToken);

        // 連続で叩かれた場合に回避する用(フロントで連打対応してくれてるが一応)
        if ($res->status == config('code.tmpReservationStatus.in_process')) {
            $resValues['status'] = false;
            $resValues['message'] = Lang::get('message.restaurantComplateInProcess');
            $resValues['loginForm']['reservationNo'] = null;
            $resValues['loginForm']['tel'] = null;
            $resValues['loginForm']['isMemberUser'] = UserLogin::isMember($info['customer']['email']);
            $resValues['loginForm']['isLogin'] = UserLogin::isLogin();
            return false;
        }

        // 予約が既に完了していたとき用(既に保存されている結果を返す)
        if (($res->status == config('code.tmpReservationStatus.complete') ||
            $res->status == config('code.tmpReservationStatus.fail_reserve'))) {
            $resValues = json_decode($res->response, true);
            return true;
        }

        $res->status = config('code.tmpReservationStatus.in_process');
        $res->save();

        try {
            DB::beginTransaction();
            $paymentToken = PaymentToken::where('token', $sessionToken)->first();

            //予約できるかの再確認
            $dt = new Carbon($info['application']['visitDate'].' '.$info['application']['visitTime']);

            //レストランは一個しかメニュー入ってこないので回さない
            $menu = $info['application']['menus'][0];
            $menuObj = Menu::find($menu['menu']['id']);

            // コースの提供時間のチェック
            if (!$this->isSalesTime($menuObj, $dt, $msg)) {
                throw new \Exception($msg);
            }

            //空席,在庫チェック
            if (!empty($menuObj->store->external_api)) {
                //外部接続があれば外部の空席確認
                if (!$this->restaurantStock->isVacancy($dt, $info['application']['persons'], $menuObj->store_id, $msg)) {
                    throw new \Exception($msg);
                }
            } else {
                //外部接続がなければskyの在庫確認
                if (!$this->hasRestaurantStock($menuObj, $dt, $info['application']['persons'], $msg)) {
                    throw new \Exception($msg);
                }
            }

            //DB登録処理
            $menuInfo = $this->menu->getRestaurantMenuFromInfo($info)->keyBy('id')->toArray();
            $reservation = $this->reservation->saveRestaurant($info, $menuInfo);
            $reservationMenuIdsWithMenuIdAsKey = $this->reservationMenu->saveRestaurant($info, $reservation, $menuInfo);
            $this->reservationOption->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey);
            $reservationStoreId = $this->reservationStore->saveRestaurant($info, $reservation, $menuInfo);
            $this->reservationGenre->saveRestaurant($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
            $this->reservationCancelPolicy->saveRestaurant($reservationStoreId);
            $this->paymentDetail->saveRestaurant($info, $reservation, $menuInfo);

            //$cmApplicationId = session('payment.cm_application_id');
            $cmApplicationId = $info['payment']['cm_application_id'];
            //$sessionUserId = session('payment.user_id');
            $sessionUserId = $info['payment']['user_id'];
            if (empty($cmApplicationId) || empty($sessionUserId)) {
                throw new \Exception('failed to get session');
            }

            $detail = CmThApplicationDetail::where('cm_application_id', $cmApplicationId)->firstOrFail();
            $detail->application_id = $reservation->id;
            $detail->save();

            $cmThApplication = CmThApplication::where('cm_application_id', $cmApplicationId)->firstOrFail();
            $cmThApplication->user_id = $sessionUserId;
            $cmThApplication->save();

            // skyticket paymnet api経由の予約の場合は予約IDをpaymentTokensテーブルへ保存
            if (!is_null($paymentToken)) {
                $paymentToken->reservation_id = $reservation->id;
                $paymentToken->save();
            }

            //外部APIの登録処理
            if (!empty($reservation->reservationStore->store->external_api)) {
                if (!$this->restaurantReservation->saveReservation($info, $reservation, $errMsg)) {
                    throw new \Exception($errMsg);
                }
            }

            // 在庫更新
            $this->vacancy->updateStock($info['application']['persons'], $menuObj, $dt);

            session()->forget('payment');
            $resValues['status'] = true;
            $resValues['message'] = Lang::get('message.restaurantCompleteSuccess');
            $resValues['loginForm']['reservationNo'] = $reservation->app_cd.$reservation->id;
            $resValues['loginForm']['tel'] = $reservation->tel;
            $resValues['loginForm']['isMemberUser'] = UserLogin::isMember($info['customer']['email']);
            $resValues['loginForm']['isLogin'] = UserLogin::isLogin();

            //メール送信
            $storeEmails = $this->getStoreEmails($menuObj->store_id);
            foreach ($storeEmails as $storeEmail) {
                $restaurantMailClient = new RestaurantMail($reservation->id);
                $restaurantMailClient->completeReservationForClient($storeEmail);
            }
            $restaurantMailUser = new RestaurantMail($reservation->id);
            $restaurantMailUser->completeReservationForUser();

            //FAX送信必要であれば処理の追加

            //CallReach処理
            $jobCd = 'RESERVE_RS';
            $this->callReachJob->createJob($jobCd, $reservation);

            DB::commit();
            $dbStatus = config('code.tmpReservationStatus.complete');
        } catch (\Throwable | \Exception $e) {
            DB::rollBack();
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getTraceAsString()
                ));
            if (!is_null($paymentToken)) {
                $orderCode = $this->paymentToken->getOrderCodeFromToken($sessionToken);
                // 与信キャンセル
                $result = [];
                $this->paymentSkyticket->cancelPayment($orderCode, $result);
            }
            $resValues['status'] = false;
            $resValues['message'] = !empty($errMsg) ? $errMsg : Lang::get('message.restaurantCompleteFailure');
            $resValues['loginForm']['reservationNo'] = null;
            $resValues['loginForm']['tel'] = '';
            if (isset($info['customer']['email'])) {
                $resValues['loginForm']['isMemberUser'] = UserLogin::isMember($info['customer']['email']);
            }
            $resValues['loginForm']['isLogin'] = UserLogin::isLogin();

            return false;
        } finally {
            $this->tmpRestaurantReservation->saveRes($sessionToken, $resValues, $dbStatus);
        }

        return true;
    }

    /**
     * レストラン - 予約変更.
     *
     * @param array $params
     * @param array $resValues
     *
     * @return bool
     */
    public function change($params, &$resValues)
    {
        $msg = '';
        $resValues = [
            'status' => false,
            'message' => '',
            'returnUrl' => '',
            'isOnlySeat' => false,
        ];

        try {
            DB::beginTransaction();
            $reservation = Reservation::find($params['reservationId']);
            $oldReservation = $reservation->replicate();

            if ($reservation->reservation_status == config('code.reservationStatus.cancel.key')) {
                $msg = \Lang::get('message.restaurantChangeFailure');
                throw new \Exception($msg);
            }

            $pickUpDatetime = !empty($params['visitDate']) || !empty($params['visitTime']) ? $params['visitDate'].' '.$params['visitTime'] : $reservation->pick_up_datetime;

            $persons = !empty($params['persons']) ? $params['persons'] : $reservation->persons;

            $request = !empty($params['request']) ? $params['request'] : $reservation->request;

            $dt = new Carbon($pickUpDatetime);
            $reservationMenu = $reservation->reservationMenus[0];
            $menu = $reservationMenu->menu;

            // 管理画面に表示する対応履歴用に変更内容を保持
            $changeDetail = [];
            if (date('Y-m-d H:i', strtotime($pickUpDatetime)) != date('Y-m-d H:i', strtotime($oldReservation->pick_up_datetime))) {
                $changeDetail[] = '来店日時';
            }
            if ($persons != $oldReservation->persons) {
                $changeDetail[] = '予約人数';
            }

            //料金の再計算
            list($unitPrice, $menuTotal, $newTotal) = PaymentService::calcPrice($reservation, $dt, $persons);

            //予約の再チェック
            // opening_hours store menuの曜日と祝日のチェック
            if (!$this->menu->canSale($menu->id, $menu->store_id, $dt, $msg)) {
                throw new \Exception($msg);
            }

            //空席,在庫チェック
            if (!empty($menu->store->external_api)) {
                //外部接続があれば外部の空席確認
                if (!$this->restaurantStock->isVacancy($dt, $params['persons'], $menu->store_id, $msg)) {
                    throw new \Exception($msg);
                }
            } else {
                //外部接続がなければskyの在庫確認
                if (!$this->hasRestaurantStock($menu, $dt, $params['persons'], $msg)) {
                    throw new \Exception($msg);
                }
            }

            // コースの提供時間のチェック
            if (!$this->isSalesTime($menu, $dt, $msg)) {
                throw new \Exception($msg);
            }

            //利用可能下限、上限人数の確認
            if (!$this->isAvailableNumber($menu, $persons, $msg)) {
                throw new \Exception($msg);
            }

            //有効なpaymentTokens取得
            $paymentTokens = PaymentToken::where('reservation_id', $params['reservationId'])->where('is_invalid', 0)->get();

            //is_invalidが0のレコードが２つあった場合は以前に予約変更を中断した際のレコードを無効化する
            if ($paymentTokens->count() >= 2) {
                $invalidPaymentToken = $paymentTokens->where('is_invalid', 0)->where('is_restaurant_change', 0)->first();
                $invalidPaymentToken->is_invalid = 1;
                $invalidPaymentToken->is_restaurant_change = 1;
                $invalidPaymentToken->save();

                $paymentToken = $paymentTokens->where('is_invalid', 0)->first();
            } else {
                $paymentToken = $paymentTokens->first();
            }

            $info = $this->createInfo($reservation, $dt, $persons);


            if ($newTotal === 0 ||
                $newTotal === $reservation->total) {    //席のみの場合
                $sessionId = md5(uniqid(rand(), 1));

                //一時テーブルに保存
                $tmpRsRsv = new TmpRestaurantReservation();
                if (!$tmpRsRsv->saveSession($sessionId, $info, $msg, true)) {
                    throw new \Exception($msg);
                }

                //予約データ更新
                //reservationMenuの更新
                $reservationMenu->count = $persons;
                $reservationMenu->unit_price = $unitPrice;
                $reservationMenu->price = $menuTotal;
                $reservationMenu->save();

                //reservationの更新
                $reservation->persons = $persons;
                $reservation->pick_up_datetime = $pickUpDatetime;
                $reservation->request = $request;
                $reservation->total = $newTotal;
                $reservation->save();

                //外部APIの予約変更処理
                if ($reservation->external_reservation_id) {
                    if (!$this->restaurantReservation->changeReservation($reservation, $msg)) {
                        throw new \Exception($msg);
                    }
                }

                // 在庫更新(自社在庫の場合は一度在庫を戻してから更新)
                if (empty($reservation->external_reservation_id)) {
                    $oldDt = new Carbon($oldReservation->pick_up_datetime);
                    $this->vacancy->updateStock(-$oldReservation->persons, $menu, $oldDt);
                }
                $this->vacancy->updateStock($persons, $menu, $dt);

                $resValues['message'] = !empty($msg) ? $msg : Lang::get('message.restaurantChangeSuccess');
                $resValues['status'] = true;
                $resValues['isOnlySeat'] = true;
                $dbStatus = config('code.tmpReservationStatus.complete');

                //一時データの更新
                $tmpRsRsvUpdate = TmpRestaurantReservation::where('session_id', $sessionId)->first();
                $response = [
                    'status' => $resValues['status'],
                    'message' => $resValues['message'],
                    'loginForm' => [
                        'reservationNo' => key(config('code.appCd.rs')).$reservation->id,
                        'tel' => $reservation->tel,
                        'isMenberUser' => UserLogin::isMember(Cipher::decrypt($reservation->email)),
                        'isLogin' => UserLogin::isLogin(),
                    ],
                ];
                $tmpRsRsvUpdate->response = $response;
                $tmpRsRsvUpdate->status = $dbStatus;
                $tmpRsRsvUpdate->save();

                //予約変更メールの送信
                $storeEmails = $this->getStoreEmails($menu->store_id);
                foreach ($storeEmails as $storeEmail) {
                    $restaurantMailClient = new RestaurantMail($reservation->id);
                    $restaurantMailClient->userChangeReservationForClient($oldReservation, $storeEmail);
                }
                $restaurantMailUser = new RestaurantMail($reservation->id);
                $restaurantMailUser->userChangeReservationForUser($oldReservation);
            } else {    //再決済処理
                $callBackValues = json_decode($paymentToken->call_back_values, true);
                $info['payment']['cartId'] = $callBackValues['cartId'];
                $info['payment']['reservationId'] = $reservation->id;
                $info['payment']['cm_application_id'] = $paymentToken->cm_application_id;
                $info['payment']['user_id'] = $callBackValues['userId'];

                //変更後料金をDB登録用に一時保存
                $info['unitPrice'] = $unitPrice;
                $info['menuTotal'] = $menuTotal;
                $info['total'] = $newTotal;

                // 再決済
                $saveResult = $this->paymentSkyticket->save($info, $newTotal, $info['application']['visitDate'], $info['application']['visitDate']);
                if (!$saveResult['result']['status']) {
                    $msg = $saveResult['result']['message'];
                    throw new \Exception($msg);
                }
                $sessionId = $saveResult['session_token'];

                $resValues['message'] = !empty($msg) ? $msg : Lang::get('message.restaurantChangeSuccess');
                $resValues['status'] = true;
                $resValues['returnUrl'] = $saveResult['paymentUrl'];

                $paymentToken->is_restaurant_change = 1;
                $paymentToken->save();

                //一時テーブルへの保存
                $tmpRsRsv = new TmpRestaurantReservation();
                if (!$tmpRsRsv->saveSession($sessionId, $info, $msg, true)) {
                    throw new \Exception($msg);
                }
            }

            // 対応履歴保存
            MessageBoard::create([
                'reservation_id' => $reservation->id,
                'message_type' => config('const.messageBoard.message_type.CUSTOMER_MYPAGE.code'),
                'message' => '予約内容変更（'.implode($changeDetail, '、').'）',
                'staff_id' => 0,
            ]);

            //CallReach処理
            $jobCd = 'CHANGE';
            $this->callReachJob->createJob($jobCd, $reservation);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getTraceAsString()
                ));

            $resValues['status'] = false;
            $resValues['message'] = !empty($msg) ? $msg : Lang::get('message.restaurantChangeFailure');

            return false;
        }

        return true;
    }

    /**
     * レストラン-管理画面から予約変更があった際に決済URLをフロントに返す
     *
     * @param integer $reservationId
     * @param array $resValues
     *
     * @return boolean
     */
    public function directPayment(int $reservationId, &$resValues)
    {
        $resValues = [
            'status' => false,
            'message' => '',
            'paymentUrl' => '',
        ];

        try {
            DB::beginTransaction();
            $reservation = Reservation::find($reservationId);
            $tmpChange = TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->first();
            $changeInfo = json_decode($tmpChange->info, true);
            $dt = new Carbon($changeInfo['pick_up_datetime']);

            //有効なpaymentTokens取得
            $paymentTokens = PaymentToken::where('reservation_id', $reservationId)->where('is_invalid', 0)->get();

            //is_invalidが0のレコードが２つあった場合は以前に予約変更を中断した際のレコードを無効化する
            if ($paymentTokens->count() >= 2) {
                $invalidPaymentToken = $paymentTokens->where('is_invalid', 0)->where('is_restaurant_change', 0)->first();
                $invalidPaymentToken->is_invalid = 1;
                $invalidPaymentToken->is_restaurant_change = 1;
                $invalidPaymentToken->save();

                $paymentToken = $paymentTokens->where('is_invalid', 0)->first();
            } else {
                $paymentToken = $paymentTokens->first();
            }

            $info = $this->createInfo($reservation, $dt, $changeInfo['persons']);
            $callBackValues = json_decode($paymentToken->call_back_values, true);
            $info['payment']['cartId'] = $callBackValues['cartId'];
            $info['payment']['reservationId'] = $reservation->id;
            $info['payment']['cm_application_id'] = $paymentToken->cm_application_id;
            $info['payment']['user_id'] = $callBackValues['userId'];

            // 再決済
            $result = $this->paymentSkyticket->save($info, $changeInfo['total'], $info['application']['visitDate'], $info['application']['visitDate']);

            $resValues['message'] = !empty($msg) ? $msg : Lang::get('message.directPaymentSuccess');
            $resValues['status'] = true;
            $resValues['paymentUrl'] = $result['paymentUrl'];

            $paymentToken->is_restaurant_change = 1;
            $paymentToken->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error(
                sprintf(
                    '::error=%s',
                    $e->getTraceAsString()
                ));

            $resValues['status'] = false;
            $resValues['message'] = !empty($msg) ? $msg : Lang::get('message.directPaymentFaimure');

            return false;
        }

        return true;
    }

    /**
     * レストラン - 予約キャンセル.
     *
     * @param array $resValues
     * @param boolean $isAdmin
     *
     * @return bool
     */
    public function cancel(int $reservationId, &$resValues, $isAdmin = false)
    {
        \Log::debug('start cancel api:[reservationId:'.$reservationId.']');
        $resValues = [
            'status' => false,
        ];
        $reservation = Reservation::find($reservationId);
        $reservationMenu = $reservation->reservationMenus[0];
        $menu = $reservationMenu->menu;
        $dt = new Carbon($reservation->pick_up_datetime);
        $refundPrice = 0;       //返金額
        $cancelPrice = [];       //キャンセル料
        $cancelFeeId = null;    //適用したキャンセルポリシー
        $settleResult = [];     //計上結果用の配列
        $refundResult = [];     //返金要求用の配列
        $msg = '';

        if ($reservation->reservation_status === config('code.reservationStatus.cancel.key')) {
            $resValues['message'] = \Lang::get('message.reservationCancelFaimure1');

            return false;
        }

        if ($reservationMenu->unit_price !== 0) {
            //有効なpaymentTokens取得(先にフラグ変更をしておかないとコールバック時にエラーが起きるため先に処理)
            $paymentTokens = PaymentToken::where('reservation_id', $reservationId)->where('is_invalid', 0)->get();

            //is_invalidが0のレコードが２つあった場合は以前に予約変更を中断した際のレコードを無効化する
            if ($paymentTokens->count() >= 2) {
                $invalidPaymentToken = $paymentTokens->where('is_invalid', 0)->where('is_restaurant_change', 0)->first();
                $invalidPaymentToken->is_invalid = 1;
                $invalidPaymentToken->is_restaurant_change = 1;
                $invalidPaymentToken->save();

                $paymentToken = $paymentTokens->where('is_invalid', 0)->first();
            } else {
                $paymentToken = $paymentTokens->first();
            }

            $callBackValues = json_decode($paymentToken->call_back_values, true);
        }

        try {
            DB::beginTransaction();

            //キャンセル料計算(席のみ以外の場合)
            if ($reservationMenu->unit_price !== 0) {
                list($refundPrice, $cancelPrice, $cancelFeeId) = $this->cancelFee->calcCancelFee($reservationId);
                if (is_null($refundPrice) || is_null($cancelPrice)) {   //キャンセル料設定がなかった場合
                    $msg = \Lang::get('message.reservationCancelFaimure0');
                    throw new \Exception($msg);
                }
            }

            //カード決済を行っており与信状態の場合
            if ($reservation->payment_method === config('const.payment.payment_method.credit')
                && ($reservation->payment_status === config('code.paymentStatus.auth.key')
                || $reservation->payment_status === config('code.paymentStatus.wait_payment.key'))) {
                // 与信状態の場合は一度計上する
                if (!$this->paymentSkyticket->settlePayment($callBackValues['orderCode'], $settleResult)) {
                    $title = 'cancelでデータ不整合発生';
                    $title = '予約ID:'.$reservationId.' '.$title;
                    $body = sprintf(
                        \Lang::get('message.reservationCancelError'),
                        '計上処理',
                        $reservationId,
                    );
                    CommonLog::notifyToChat(
                        $title,
                        $body
                    );
                    $msg = \Lang::get('message.reservationCancelFaimure0');
                    throw new \Exception($msg);
                }
            }

            //cancel_detailの登録
            if ($reservationMenu->unit_price !== 0) {

                // メニューキャンセル料
                if (isset($cancelPrice['menu'])) {
                    $menuData['reservation_id'] = $reservationId;
                    $menuData['target_id'] = $reservationMenu->menu_id;
                    $menuData['account_code'] = 'MENU';
                    $menuData['price'] = $cancelPrice['menu']['price'];
                    $menuData['count'] = $cancelPrice['menu']['count'];
                    $menuData['remarks'] = 'キャンセル料マスタID:'.$cancelFeeId;
                    CancelDetail::create($menuData);
                }

                // オプションキャンセル料
                if (isset($cancelPrice['options'])) {
                    foreach ($cancelPrice['options'] as $optionId => $option) {
                        $optionData['reservation_id'] = $reservationId;
                        $optionData['target_id'] = $optionId;
                        $optionData['account_code'] = 'OKONOMI';
                        $optionData['price'] = $option['price'];
                        $optionData['count'] = $option['count'];
                        $optionData['remarks'] = 'キャンセル料マスタID:'.$cancelFeeId;
                        CancelDetail::create($optionData);
                    }
                }
                // 差額登録
                if (isset($cancelPrice['diff'])) {
                    $optionData['reservation_id'] = $reservationId;
                    $optionData['target_id'] = $reservationMenu->menu_id;
                    $optionData['account_code'] = 'MENU';
                    $optionData['price'] = $cancelPrice['diff'];
                    $optionData['count'] = 1;
                    $optionData['remarks'] = '金額調整　キャンセル料マスタID:'.$cancelFeeId;
                    CancelDetail::create($optionData);
                }
            }

            //reservationの変更 status変更
            $reservation->cancel_datetime = Carbon::now();
            $reservation->reservation_status = config('code.reservationStatus.cancel.key');
            if ($reservation->total != 0) {
                $reservation->payment_status = $refundPrice == 0 ? config('code.paymentStatus.payed.key') : config('code.paymentStatus.wait_refund.key');
            }
            $reservation->save();

            //外部予約のキャンセル処理
            if (!empty($reservation->external_reservation_id)) {
                if (!$this->restaurantReservation->cancelReservation($reservation, $msg)) {
                    $title = '外部予約キャンセルでエラー発生';
                    $title = '予約ID:'.$reservationId.' '.$title;
                    $body = sprintf(
                            \Lang::get('message.reservationCancelError'),
                            '外部予約キャンセル',
                            $reservationId,
                        );
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    throw new \Exception($msg);
                }
            }

            // 在庫更新
            $this->vacancy->updateStock(-$reservation->persons, $menu, $dt);

            //返金金額が1円以上であれば返金処理
            if ($refundPrice >= 1 && $reservation->payment_method === config('const.payment.payment_method.credit')) {
                //refundテーブルにデータ作成(callbackで処理するのでrefundingで登録)
                Refund::create([
                    'reservation_id' => $reservationId,
                    'price' => $refundPrice,
                    'status' => config('code.refundStatus.refunding.key'),
                ]);

                //返金要求処理
                $paymentToken->refundPrice = $refundPrice;
                if (!$this->paymentSkyticket->registerRefundPayment($paymentToken, $refundResult)) {
                    $title = '返金要求処理でエラー発生';
                    $title = '予約ID:'.$reservationId.' '.$title;
                    $body = sprintf(
                            \Lang::get('message.reservationCancelError'),
                            '返金要求',
                            $reservationId,
                        );
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    \Log::debug('RestaurantReservationService.php@cancel error:'.$title.':'.$body);
                    $msg = \Lang::get('message.reservationCancelFaimure0');
                    throw new \Exception($msg);
                }

                unset($paymentToken->refundPrice);
                //フラグ変更
                $paymentToken->is_invalid = 1;
                $paymentToken->save();
            }

            $resValues['status'] = true;
            $resValues['message'] = !empty($msg) ? $msg : \Lang::get('message.reservationCancelSuccess');

            $restaurantMailUser = new RestaurantMail($reservationId);

            if ($isAdmin) {
                $restaurantMailUser->adminCancelReservationForUser();
            } else {
                $storeEmails = $this->getStoreEmails($menu->store_id);
                foreach ($storeEmails as $storeEmail) {
                    $restaurantMailClient = new RestaurantMail($reservationId);
                    $restaurantMailClient->userCancelReservationForClient($storeEmail);
                }
                $restaurantMailUser->userCancelReservationForUser();

                // 対応履歴保存
                MessageBoard::create([
                    'reservation_id' => $reservationId,
                    'message_type' => config('const.messageBoard.message_type.CUSTOMER_MYPAGE.code'),
                    'message' => '予約キャンセル',
                    'staff_id' => 0,
                ]);

                //CallReach処理
                $jobCd = 'CANCEL';
                $this->callReachJob->createJob($jobCd, $reservation);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error(
                sprintf(
                    'error=%s',
                    $e->getTraceAsString()
                ));
            $resValues['message'] = !empty($msg) ? $msg : \Lang::get('message.reservationCancelFaimure0');

            return false;
        }

        return true;
    }


    /**
     * お店都合キャンセル
     *
     * @param integer $reservationId
     * @param $msg
     *
     * @return boolean
     */
    public function adminCancel(int $reservationId, &$msg)
    {
        $refundResult = [];
        $reservation = Reservation::find($reservationId);
        $reservationMenu = $reservation->reservationMenus[0];
        $menu = $reservationMenu->menu;
        $dt = new Carbon($reservation->pick_up_datetime);
        $now = Carbon::now();
        $refundPrice = 0;

        if ($reservation->reservation_status === config('code.reservationStatus.cancel.key')) {
            $msg = \Lang::get('message.reservationCancelFaimure1');
            return false;
        }

        if ($reservationMenu->unit_price !== 0) {
            //有効なpaymentTokens取得(先にフラグ変更をしておかないとコールバック時にエラーが起きるため先に処理)
            $paymentTokens = PaymentToken::where('reservation_id', $reservationId)->where('is_invalid', 0)->get();

            //is_invalidが0のレコードが２つあった場合は以前に予約変更を中断した際のレコードを無効化する
            if ($paymentTokens->count() >= 2) {
                $invalidPaymentToken = $paymentTokens->where('is_invalid', 0)->where('is_restaurant_change', 0)->first();
                $invalidPaymentToken->is_invalid = 1;
                $invalidPaymentToken->is_restaurant_change = 1;
                $invalidPaymentToken->save();

                $paymentToken = $paymentTokens->where('is_invalid', 0)->first();
            } else {
                $paymentToken = $paymentTokens->first();
            }

            $callBackValues = json_decode($paymentToken->call_back_values, true);
        }

        try {
            DB::beginTransaction();

            //外部予約のキャンセル処理
            if (!empty($reservation->external_reservation_id)) {
                if (!$this->restaurantReservation->cancelReservation($reservation, $msg)) {
                    $title = '外部予約キャンセルでエラー発生';
                    $title = '予約ID:'.$reservationId.' '.$title;
                    $body = sprintf(
                            \Lang::get('message.reservationCancelError'),
                            '外部予約キャンセル',
                            $reservationId,
                        );
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    throw new \Exception($msg);
                }
            }

            // 在庫更新
            $this->vacancy->updateStock(-$reservation->persons, $menu, $dt);
            if ($reservation->payment_status == config('code.paymentStatus.payed.key')) {   //計上済み

                $refundPrice = $reservation->total;

                //refundテーブルにデータ作成(callbackで処理するのでrefundingで登録)
                Refund::create([
                    'reservation_id' => $reservationId,
                    'price' => $refundPrice,
                    'status' => config('code.refundStatus.refunding.key'),
                ]);

                //返金要求処理
                $paymentToken->refundPrice = $refundPrice;
                if (!$this->paymentSkyticket->registerRefundPayment($paymentToken, $refundResult)) {
                    $title = '返金要求処理でエラー発生';
                    $title = '予約ID:'.$reservationId.' '.$title;
                    $body = sprintf(
                            \Lang::get('message.reservationCancelError'),
                            '返金要求',
                            $reservationId,
                        );
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    $msg = \Lang::get('message.reservationCancelFaimure0');
                    throw new \Exception($msg);
                }

                unset($paymentToken->refundPrice);

                //フラグ変更
                $paymentToken->is_invalid = 1;
                $paymentToken->save();
            } elseif ($reservation->payment_status == config('code.paymentStatus.auth.key') ||
                $reservation->payment_status == config('code.paymentStatus.wait_payment.key')) {   //与信か決済待ち

                // 予約変更一時データがあれば無効化
                $tmpAdminChangeReservation = TmpAdminChangeReservation::where('reservation_id', $reservationId)->where('is_invalid', 0)->whereNull('status')->first();
                if (!is_null($tmpAdminChangeReservation)) {
                    $tmpAdminChangeReservation->is_invalid = 1;
                    $tmpAdminChangeReservation->save();
                }

                $result = [];
                //与信キャンセル
                if (!$this->paymentSkyticket->cancelPayment($callBackValues['orderCode'], $result)) {
                    $title = 'レストラン予約キャンセル(お店都合)処理の与信キャンセルで例外発生';
                    $title = '予約ID:'.$reservation->id.' '.$title;
                    $body = "[changeReservation] 与信のキャンセルに失敗しました。管理画面から与信の取消を行ってください。　注文番号:{$callBackValues['orderCode']}　SKYTICKET申込番号:{$paymentToken->cm_application_id}　グルメ予約番号:{$reservation->id}";
                    CommonLog::notifyToChat(
                        $title,
                        $body
                    );
                }

                $paymentToken->is_invalid = 1;
                $paymentToken->save();
            }

            //reservationの変更 status変更
            $reservation->cancel_datetime = $now;
            $reservation->reservation_status = config('code.reservationStatus.cancel.key');
            if ($reservation->total != 0) {
                $reservation->payment_status = $refundPrice == 0 ? config('code.paymentStatus.cancel.key') : config('code.paymentStatus.wait_refund.key');
            }
            $reservation->save();

            $restaurantMailUser = new RestaurantMail($reservationId);
            $restaurantMailUser->adminCancelReservationForUser();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error(
                sprintf(
                    'error=%s',
                    $e->getTraceAsString()
                ));
            return false;
        }

        return true;
    }

    /**
     * レストラン - キャンセル料金の計算.
     *
     * @param array $resValues
     *
     * @return bool
     */
    public function calcCancelFee(int $reservationId, &$resValues)
    {
        list($refundPrice, $cancelPrice, $cancelFeeId) = $this->cancelFee->calcCancelFee($reservationId);
        if (is_null($refundPrice) || is_null($cancelPrice)) {
            $resValues['status'] = false;
            $resValues['message'] = \Lang::get('message.cancelFeeCheckFaimure');
            $resValues['cancelPrice'] = $cancelPrice;
            $resValues['refundPrice'] = $refundPrice;

            return false;
        } else {
            $resValues['status'] = true;
            $resValues['message'] = '';
            $resValues['cancelPrice'] = $cancelPrice['total'];
            $resValues['refundPrice'] = $refundPrice;

            return true;
        }
    }

    /**
     * レストラン - 料金の再計算.
     *
     * @param array $resValues
     *
     * @return bool
     */
    public function calcPriceMenu(array $params, &$resValues)
    {
        $reservation = Reservation::find($params['reservationId']);

        $pickUpDatetime = !empty($params['visitDate']) || !empty($params['visitTime']) ? $params['visitDate'].' '.$params['visitTime'] : $reservation->pick_up_datetime;
        $dt = new Carbon($pickUpDatetime);

        $persons = !empty($params['persons']) ? $params['persons'] : $reservation->persons;

        list($unitPrice, $menuTotal, $newTotal) = PaymentService::calcPrice($reservation, $dt, $persons);

        if (is_null($newTotal)) {
            $resValues['status'] = false;
            $resValues['message'] = \Lang::get('message.menuPriceCheckFaimure');
            $resValues['price'] = $newTotal;

            return false;
        } else {
            $resValues['status'] = true;
            $resValues['message'] = '';
            $resValues['price'] = $newTotal;

            return true;
        }
    }

    /**
     * レストランデータチェック用 - コース提供時間の確認.
     *
     * @param string $msg
     *
     * @return bool
     */
    public function isSalesTime(Menu $menu, $dt, string &$msg = null)
    {
        $store = Store::find($menu->store_id);
        $now = Carbon::now();
        $openingHours = $store->openingHours;
        [$week, $weekName] = $menu->getWeek($dt);

        //該当する営業時間の情報を取得
        foreach ($openingHours as $openingHour) {
            if ($openingHour->week[$week] !== '1') {
                continue;
            }
            if (!(strtotime($dt->format('H:i:s')) >= strtotime($openingHour->start_at)
            && strtotime($dt->format('H:i:s')) < strtotime($openingHour->end_at))) {
                continue;
            }
            // 祝日休みの場合は今日が祝日かどうかチェック
            if ($week === '7' && $openingHour->week[7] !== '1') {
                $holiday = Holiday::where('date', $now->format('Y-m-d'))->first();
                if (!is_null($holiday)) {
                    // 祝日のため休み
                    continue;
                }
            }
            $todayOpeningHour = $openingHour;
            break;
        }

        //店舗の閉店時間から出した最終予約可能時間
        $endAt = new Carbon($openingHour->end_at);
        $skyStoreLastReservationTime = $endAt->copy()->subMinutes($menu->provided_time);

        //ラストオーダーと営業終了時間の間ではないかチェック
        if (strtotime($dt->format('H:i:s')) > strtotime($todayOpeningHour->last_order_time)
        && strtotime($dt->format('H:i:s')) <= strtotime($todayOpeningHour->end_at)) {
            $msg = \Lang::get('message.salesTimeCheckFaimure0');

            return false;
        }

        //メニュー最終予約可能時間の取得
        $skyMenuLastReservationTime = '';
        if (!empty($menu->sales_lunch_end_time) && empty($menu->sales_dinner_end_time)) {           //メニューにランチ販売時間のみ入ってる場合
            //ランチ提供時間内かチェック
            if (!(strtotime($menu->sales_lunch_start_time) <= strtotime($dt->format('H:i:s'))
            && strtotime($menu->sales_lunch_end_time) > strtotime($dt->format('H:i:s')))) {
                $msg = \Lang::get('message.lunchTimeCheckFaimure');

                return false;
            }
            $salesLunchEndTime = new Carbon($menu->sales_lunch_end_time);
            $skyMenuLastReservationTime = $salesLunchEndTime->copy()->subMinutes($menu->provided_time);
        } elseif (!empty($menu->sales_dinner_end_time) && empty($menu->sales_lunch_end_time)) {     //メニューにディナー販売時間のみ入っている場合
            //ディナー提供時間内かチェック
            if (!(strtotime($menu->sales_dinner_start_time) <= strtotime($dt->format('H:i:s'))
            && strtotime($menu->sales_dinner_end_time) > strtotime($dt->format('H:i:s')))) {
                $msg = \Lang::get('message.dinnerTimeCheckFaimure');

                return false;
            }
            $salesDinnerEndTime = new Carbon($menu->sales_dinner_end_time);
            $skyMenuLastReservationTime = $salesDinnerEndTime->copy()->subMinutes($menu->provided_time);
        } elseif (!empty($menu->sales_lunch_end_time) && !empty($menu->sales_dinner_end_time)) {    //メニューにランチ/ディナー販売時間の両方が入っている場合
            //該当する時間の確認
            if (strtotime($dt->format('H:i:s')) >= strtotime($menu->sales_lunch_start_time)
            && strtotime($dt->format('H:i:s')) < strtotime($menu->sales_lunch_end_time)) {  //予約時間がランチ販売時間に該当した場合
                $salesLunchEndTime = new Carbon($menu->sales_lunch_end_time);
                $skyMenuLastReservationTime = $salesLunchEndTime->copy()->subMinutes($menu->provided_time);
            } elseif (strtotime($dt->format('H:i:s')) >= strtotime($menu->sales_dinner_start_time)
            && strtotime($dt->format('H:i:s')) < strtotime($menu->sales_dinner_end_time)) { //予約時間がディナー販売時間に該当した場合
                $salesDinnerEndTime = new Carbon($menu->sales_dinner_end_time);
                $skyMenuLastReservationTime = $salesDinnerEndTime->copy()->subMinutes($menu->provided_time);
            } else {
                $msg = \Lang::get('message.salesTimeCheckFaimure0');

                return false;
            }
        }

        //skyMenuLastReservationTimeが入っていた場合
        if (!empty($skyMenuLastReservationTime)) {
            //外部APIの営業終了時間を取得
            if (!empty($store->external_api)) {
                $extClosingTime = $this->restaurantReservation->getClosingTime($store->external_api->api_store_id, $dt);
                if (is_null($extClosingTime)) {
                    $title = 'メニュー提供時間のチェックでエラー発生';
                    $body = \Lang::get('message.salesTimeCheckFaimure3');
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    return false;
                }
                $extClosingTime = new Carbon($extClosingTime);
            } else {
                $extClosingTime = null;
            }

            if (!empty($extClosingTime)) {
                $extLastReservationTime = $extClosingTime->copy()->subMinutes($menu->provided_time);
                //skyticketの最終予約可能時間と外部の最終予約可能時間を比較
                if (strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))
                    && strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))) {   //外部の最終予約可能時間の方が短かった場合
                    $title = 'メニュー提供時間のチェックでエラー発生';
                    $body = sprintf(
                            \Lang::get('message.salesTimeCheckFaimure1'),
                            $menu->id,
                            $skyMenuLastReservationTime->format('H:i:s'),
                            $extLastReservationTime->format('H:i:s')
                        );
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    \Log::info(sprintf('lastReservationTime(External) %s', $extLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $extLastReservationTime;
                } elseif (strtotime($extLastReservationTime->format('H:i:s')) > strtotime($skyMenuLastReservationTime->format('H:i:s'))
                    && strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($skyMenuLastReservationTime->format('H:i:s'))) {  //skyticketのメニュー最終予約可能時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyMenuLastReservationTime;
                } elseif (strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))
                    && strtotime($extLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))) {    //skyticketの店舗最終予約時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyStoreLastReservationTime;
                } else {        //どこにも一致しなかった場合はメニューの時間を使う
                    \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyMenuLastReservationTime;
                }
            } else {    //外部APIの接続がない場合
                if (strtotime($skyMenuLastReservationTime->format('H:i:s')) > strtotime($skyStoreLastReservationTime->format('H:i:s'))) {   //skyticketの店舗最終予約時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyStoreLastReservationTime;
                } else {    //skyticketのメニュー最終予約可能時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(SkyticketMenu) %s', $skyMenuLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyMenuLastReservationTime;
                }
            }
        } else {    //メニューの時間設定がない場合
            //外部APIの営業終了時間を取得
            if (!empty($store->external_api)) {
                $extClosingTime = $this->restaurantReservation->getClosingTime($store->external_api->api_store_id, $dt);
                if (is_null($extClosingTime)) {
                    $title = 'メニュー提供時間のチェックでエラー発生';
                    $body = \Lang::get('message.salesTimeCheckFaimure3');
                    CommonLog::notifyToChat(
                            $title,
                            $body
                        );
                    return false;
                }
                $extClosingTime = new Carbon($extClosingTime);
            } else {
                $extClosingTime = null;
            }

            if (!empty($extClosingTime)) {
                $extLastReservationTime = $extClosingTime->copy()->subMinutes($menu->provided_time);
                if (strtotime($skyStoreLastReservationTime->format('H:i:s')) > strtotime($extLastReservationTime->format('H:i:s'))) {     //外部の最終予約可能時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(External) %s', $extLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $extLastReservationTime;
                } else {    //skyticketの店舗最終予約時間の方が短かった場合
                    \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                    $lastReservationTime = $skyStoreLastReservationTime;
                }
            } else {    //外部APIの接続がない場合
                \Log::info(sprintf('lastReservationTime(SkyticketStore) %s', $skyStoreLastReservationTime->format('H:i:s')));
                $lastReservationTime = $skyStoreLastReservationTime;
            }
        }

        //最終予約可能時間前かチェック
        if (!(strtotime($lastReservationTime->format('H:i:s')) >= strtotime($dt->format('H:i:s')))) {
            $title = '最終予約可能時間前かチェックでエラー発生';
            $body = sprintf(
                    \Lang::get('message.salesTimeCheckFaimure2'),
                    $menu->id,
                    $lastReservationTime->format('H:i:s'),
                    $dt->format('H:i:s')
                );
            CommonLog::notifyToChat(
                    $title,
                    $body
                );
            $msg = \Lang::get('message.salesTimeCheckFaimure0');

            return false;
        }

        //過去の時間ではないかチェック
        if (strtotime($dt->format('Y-m-d H:i:s')) < strtotime($now->format('Y-m-d H:i:s'))) {
            $msg = \Lang::get('message.pastOrderCheckFaimure');

            return false;
        }

        //最低注文時間のチェック
        if (!empty($menu->lower_orders_time)) {
            $lowerOrdersTime = $now->addMinutes($menu->lower_orders_time);
            if (strtotime($dt->format('Y-m-d H:i:s')) < strtotime($lowerOrdersTime->format('Y-m-d H:i:s'))) {
                $msg = \Lang::get('message.lowerOrdersTimeCheckFaimure');

                return false;
            }
        }

        return true;
    }

    /**
     * レストランデータチェック用 - 利用可能人数の確認.
     *
     * @param string $msg
     *
     * @return bool
     */
    public function isAvailableNumber(Menu $menu, int $persons, string &$msg = null)
    {
        if (!empty($menu->available_number_of_lower_limit)
            && $persons < $menu->available_number_of_lower_limit) {
            $msg = sprintf(\Lang::get('message.headcountCheckFailure1'), $menu->available_number_of_lower_limit);

            return false;
        }
        if (!empty($menu->available_number_of_upper_limit)
            && $persons > $menu->available_number_of_upper_limit) {
            $msg = sprintf(\Lang::get('message.headcountCheckFailure0'), $menu->available_number_of_upper_limit);

            return false;
        }

        return true;
    }

    /**
     * レストランデータチェック用 - 在庫確認(外部接続なし).
     *
     * @param string $msg
     *
     * @return bool
     */
    public function hasRestaurantStock(Menu $menu, $dt, int $persons, string &$msg = null)
    {
        $store = Store::find($menu->store_id);
        $startTime = $dt->format('H:i:s');
        $endTime = $dt->copy()->addMinutes($menu->provided_time)->format('H:i:s');

        $vacancies = Vacancy::where('store_id', $store->id)
            ->whereDate('date', $dt->format('Y-m-d'))
            ->whereTime('time', '>=', $startTime)
            ->whereTime('time', '<=', $endTime)
            ->where('headcount', $persons)
            ->get();
        if ($vacancies->count() == 0) {
            $msg = \Lang::get('message.vacancyCheckFailure0');
            return false;
        }

        foreach ($vacancies as $vacancy) {
            if ($vacancy->stock === 0) {
                $msg = \Lang::get('message.vacancyCheckFailure0');
                return false;

            }
            if ($vacancy->is_stop_sale === 1) {
                $msg = \Lang::get('message.vacancyCheckFailure0');
                return false;
            }
        }

        return true;
    }

    /**
     * 再決済に必要なデータを整形して返す
     *
     * @param Reservation $reservation
     * @param Carbon $dt
     * @param integer $persons
     *
     * @return array
     */
    public function createInfo(Reservation $reservation, Carbon $dt, int $persons)
    {
        $info = [];

        $info['customer']['firstName'] = $reservation->first_name;
        $info['customer']['lastName'] = $reservation->last_name;
        $info['customer']['email'] = $reservation->email;
        $info['customer']['tel'] = $reservation->tel;
        $info['customer']['request'] = $reservation->request;
        $info['application']['persons'] = $persons;
        $info['application']['visitDate'] = $dt->format('Y-m-d');
        $info['application']['visitTime'] = $dt->format('H:i');

        foreach ($reservation->reservationMenus as $key => $menu) {
            $tmpMenu = [];
            $tmpMenu['menu']['id'] = $menu->menu_id;
            $tmpMenu['menu']['count'] = $menu->count;
            foreach ($menu->reservationOptions as $key => $reservationOption) {
                $option = $reservationOption->option;
                $tmpOption = [];
                $tmpOption['id'] = $option->id;
                $tmpOption['keywordId'] = $option->keyword_id;
                $tmpOption['contentsId'] = $option->contents_id;
                $tmpOption['count'] = $reservationOption->count;
                $tmpMenu['options'][] = $tmpOption;
            }

            $info['application']['menus'][] = $tmpMenu;
        }
        return $info;
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
