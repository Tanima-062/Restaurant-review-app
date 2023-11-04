<?php

namespace App\Services;

use App\Libs\Fax\Fax;
use App\Libs\Mail\TakeoutMail;
use App\Models\CmThApplication;
use App\Models\CmThApplicationDetail;
use App\Models\Maintenance;
use App\Models\Menu;
use App\Models\OrderInterval;
use App\Models\PaymentDetail;
use App\Models\PaymentToken;
use App\Models\Reservation;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservationGenre;
use App\Models\ReservationMenu;
use App\Models\ReservationOption;
use App\Models\ReservationStore;
use App\Models\Stock;
use App\Models\Store;
use App\Models\TmpTakeoutReservation;
use App\Modules\Payment\Skyticket\PaymentSkyticket;
use App\Modules\Reservation\TakeoutCancel;
use App\Modules\UserLogin;
use App\Models\CallReachJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class ReservationService
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
     * @var TmpTakeoutReservation
     */
    private $tmpTakeoutReservation;
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
     * @var App\Models\CallReachJob
     */
    private $callReachJob;

    private $paymentSkyticket;
    private $paymentToken;

    public function __construct(
        Reservation $reservation,
        ReservationCancelPolicy $reservationCancelPolicy,
        ReservationGenre $reservationGenre,
        ReservationMenu $reservationMenu,
        ReservationStore $reservationStore,
        ReservationOption $reservationOption,
        TmpTakeoutReservation $tmpTakeoutReservation,
        PaymentDetail $paymentDetail,
        Menu $menu,
        PaymentService $paymentService,
        OrderInterval $orderInterval,
        Stock $stock,
        PaymentSkyticket $paymentSkyticket,
        PaymentToken $paymentToken,
        CallReachJob $callReachJob
    ) {
        $this->reservation = $reservation;
        $this->reservationCancelPolicy = $reservationCancelPolicy;
        $this->reservationGenre = $reservationGenre;
        $this->reservationMenu = $reservationMenu;
        $this->reservationStore = $reservationStore;
        $this->reservationOption = $reservationOption;
        $this->tmpTakeoutReservation = $tmpTakeoutReservation;
        $this->paymentDetail = $paymentDetail;
        $this->menu = $menu;
        $this->paymentService = $paymentService;
        $this->orderInterval = $orderInterval;
        $this->stock = $stock;
        $this->paymentSkyticket = $paymentSkyticket;
        $this->paymentToken = $paymentToken;
        $this->callReachJob = $callReachJob;
    }

    /**
     * テイクアウト-予約申し込み事前処理.
     *
     * @param  array params
     * @param  array resValues
     *
     * @return bool true:成功 false:失敗
     */
    public function save(array $params, array &$resValues = []): bool
    {
        // テイクアウトラストオーダー時間チェック
        $msg = null;
        $result = [
            'status' => false,
            'message' => '',
        ];
        $resValues['result'] = $result;
        $sumPrice = PaymentService::sumPrice($params);

        if (!Maintenance::isInMaintenance(config('code.maintenances.type.stopEcon'))) {
            $result = $this->paymentService->createToken($sumPrice, $params['payment']['returnUrl']);
            $params['payment']['cm_application_id'] = $this->paymentService->getCmApplicationId();
            $params['payment']['user_id'] = $this->paymentService->getUserId();
            $result['paymentUrl'] = '';
        } else {
            unset($params['payment']); //新決済時はpaymentUrl使ってないので消す
            $result = $this->paymentSkyticket->save($params, $sumPrice, null, null);
            if (!$result['result']['status']) {
                $resValues['result']['message'] = $result['result']['message'];

                return false;
            }
            $params['payment']['cm_application_id'] = $this->paymentSkyticket->getCmApplicationId();
            $params['payment']['user_id'] = $this->paymentSkyticket->getUserId();
        }

        if (empty($result['session_token'])) {
            $isSaved = false;
            $msg = '決済タイムアウトエラー';
        } else {
            $isSaved = $this->tmpTakeoutReservation->saveSession($result['session_token'], $params, $msg);
        }

        if ($isSaved) {
            $resValues['sessionToken'] = isset($result['session_token']) ? $result['session_token'] : '';
            $resValues['request_id'] = isset($result['request_id']) ? $result['request_id'] : '';
            $resValues['paymentUrl'] = $result['paymentUrl'];
            $resValues['result']['status'] = true;
        } else {
            $resValues['result']['message'] = $msg;
        }

        return $isSaved;
    }

    /**
     * テイクアウト-予約完了.
     *
     * @param  string sessionToken
     * @param  array resValues
     * @param  int opt
     *
     * @throws
     *
     * @return bool true:成功 false:失敗
     */
    public function complete(string $sessionToken, array &$resValues = [], int $opt = 0): bool
    {
        $errMsg = '';
        $dbStatus = config('code.tmpReservationStatus.fail_reserve');

        try {
            DB::beginTransaction();

            $res = TmpTakeoutReservation::where('session_id', $sessionToken)->whereNotNull('status')->first();
            if (!is_null($res)) {
                $resValues = json_decode($res->response, true);

                return true;
            }

            $paymentToken = PaymentToken::where('token', $sessionToken)->first();

            if (is_null($paymentToken) && !$this->paymentService->complete($sessionToken, $opt)) {
                $errMsg = $this->paymentService->getErrorMsg();
                throw new \Exception();
            }
            $info = $this->tmpTakeoutReservation->getInfo($sessionToken);

            $orderInterval = new OrderInterval();
            foreach ($info['application']['menus'] as $menu) {
                // 同時間帯注文組数チェック
                $message = null;
                if (!$orderInterval->isOrderable($info['application']['pickUpDate'], $info['application']['pickUpTime'], $menu['menu']['id'], $menu['menu']['count'], $message)) {
                    $errMsg = (empty($message)) ? Lang::get('message.intervalOrderCheckFailure') : $message;
                    throw new \Exception('check isOrderable failed');
                }
            }

            $menu = $info['application']['menus'][0];
            $menuObj = Menu::find($menu['menu']['id']);

            $menuInfo = $this->menu->getMenuFromInfo($info)->keyBy('id')->toArray();
            $reservation = $this->reservation->saveTakeout($info, $menuInfo);

            $reservationMenuIdsWithMenuIdAsKey = $this->reservationMenu->saveTakeout($info, $reservation, $menuInfo, $errMsg);
            $this->reservationOption->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey);
            $reservationStoreId = $this->reservationStore->saveTakeout($info, $reservation, $menuInfo);
            $this->reservationGenre->saveTakeout($info, $reservationMenuIdsWithMenuIdAsKey, $reservationStoreId, $menuInfo);
            $this->reservationCancelPolicy->saveTakeout($reservationStoreId);
            $this->paymentDetail->saveTakeout($info, $reservation, $menuInfo);

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

            session()->forget('payment');

            $resValues['status'] = true;
            $resValues['message'] = Lang::get('message.takeoutCompleteSuccess');
            $resValues['loginForm']['reservationNo'] = $reservation->app_cd.$reservation->id;
            $resValues['loginForm']['tel'] = $reservation->tel;
            $resValues['loginForm']['isMemberUser'] = UserLogin::isMember($info['customer']['email']);
            $resValues['loginForm']['isLogin'] = UserLogin::isLogin();

            // メール送信
            $storeEmails = $this->getStoreEmails($menuObj->store_id);
            foreach ($storeEmails as $storeEmail) {
                $takeoutMailClient = new TakeoutMail($reservation->id);
                $takeoutMailClient->completeReservationForClient($storeEmail);
            }
            $takeoutMailUser = new TakeoutMail($reservation->id);
            $takeoutMailUser->completeReservationForUser();

            // fax送信
            Fax::store($reservation->id);

            //CallReach処理
            $jobCd = 'RESERVE_TO';
            $this->callReachJob->createJob($jobCd, $reservation);

            DB::commit();
            $dbStatus = config('code.tmpReservationStatus.complete');
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error(sprintf('::error=%s', $e->getTraceAsString()));

            // econ api
            if (!Maintenance::isInMaintenance(config('code.maintenances.type.stopEcon'))) {
                // 与信キャンセル
                if (!$this->paymentService->creditCancel(null, null, $sessionToken)) {
                    \Log::error(sprintf('econ与信キャンセル失敗 sessionToken=%s', $sessionToken));
                }
                // skyticket api
            } else {
                $orderCode = $this->paymentToken->getOrderCodeFromToken($sessionToken);
                // 与信キャンセル
                $result = [];
                $this->paymentSkyticket->cancelPayment($orderCode, $result);
            }

            $resValues['status'] = false;
            $resValues['message'] = !empty($errMsg) ? $errMsg : Lang::get('message.takeoutCompleteFailure');
            $resValues['loginForm']['reservationNo'] = null;
            $resValues['loginForm']['tel'] = '';
            if (isset($info['customer']['email'])) {
                $resValues['loginForm']['isMemberUser'] = UserLogin::isMember($info['customer']['email']);
            }
            $resValues['loginForm']['isLogin'] = UserLogin::isLogin();

            return false;
        } finally {
            $this->tmpTakeoutReservation->saveRes($sessionToken, $resValues, $dbStatus);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function cancel(Reservation $reservation)
    {
        if ($reservation->app_cd == key(config('code.appCd.to'))) {
            $cancelObj = new TakeoutCancel($this->paymentService);
            $cancelObj->cancel($reservation);
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
