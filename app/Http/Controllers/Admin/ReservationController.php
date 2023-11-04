<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReservationSearchRequest;
use App\Http\Requests\Admin\UpdateReservationInfoRequest;
use App\Libs\Mail\RestaurantMail;
use App\Libs\Mail\TakeoutMail;
use App\Models\CancelFee;
use App\Models\CmThApplicationDetail;
use App\Models\CmTmUser;
use App\Models\MessageBoard;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\TmpAdminChangeReservation;
use App\Modules\Reservation\IFReservation;
use App\Services\PaymentService;
use App\Services\ReservationService;
use App\Services\RestaurantReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Modules\Reservation\IFStock;
use App\Models\CancelDetail;
use App\Models\PaymentToken;
use App\Modules\Payment\Skyticket\PaymentSkyticket;

class ReservationController extends BaseAdminController
{
    private $reservationService;
    private $restaurantReservation;
    private $restaurantReservationService;

    public function __construct(ReservationService $reservationService, IFReservation $restaurantReservation, RestaurantReservationService $restaurantReservationService, IFStock $restaurantStock)
    {
        parent::__construct();
        $this->reservationService = $reservationService;
        $this->restaurantReservation = $restaurantReservation;
        $this->restaurantReservationService = $restaurantReservationService;
        $this->restaurantStock = $restaurantStock;
    }

    /**
     * 予約一覧.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(ReservationSearchRequest $request)
    {
        $reservations = Reservation::with('reservationStore')
        ->list()->adminSearchFilter($request->validated())->sortable(['id' => 'desc']);

        // CSV出力
        if ($request->action === 'csv') {
            return $this->getCsv($reservations);
        }

        // 検索結果絞り込み
        $reservationSearchResults = $reservations->paginate(30);

        $stores = Store::all();

        return view('admin.Reservation.index', [
            'reservations' => $reservationSearchResults,
            'stores' => $stores,
            'isMobile' => $this->isMobile($request),
            ]);
    }

    /**
     * 予約編集表示.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editForm(Request $request, int $id)
    {
        $reservation = Reservation::with(
            [
                'reservationMenus.reservationOptions',
                'reservationStore.store.settlementCompany',
                'reservationStore.store.openingHours',
            ]
        )->find($id);

        $this->authorize('reservation', $reservation); //  ポリシーによる、個別に制御

        $applicationDetail = CmThApplicationDetail::getApplicationByReservationId($id);
        $tmpAdminChangeReservation = TmpAdminChangeReservation::where('reservation_id', $reservation->id)->where('is_invalid', 0)->whereNull('status')->first();

        if ($reservation->app_cd == key(config('code.appCd.rs'))) {
            $dt = new Carbon($reservation->pick_up_datetime);
            $midMonth = $dt->copy()->day(15)->hour(23)->minute(59);
            $endMonth = $dt->copy()->lastOfMonth()->hour(23)->minute(59);
            if ($dt <= $midMonth) {
                $cancelLimit = $midMonth;
            } else {
                $cancelLimit = $endMonth;
            }

            $changeLimit = $dt->copy()->addDays(6)->hour(23)->minute(59);
            if ($dt <= $midMonth && $midMonth <= $changeLimit) {
                $changeLimit = $midMonth;
            } elseif ($midMonth < $dt && $dt <= $endMonth && $endMonth <= $changeLimit) {
                $changeLimit = $endMonth;
            }
        } else {
            $cancelLimit = null;
            $changeLimit = null;
        }

        $newPayment = PaymentToken::where('reservation_id', $reservation->id)->exists();

        $code = config('code');

        return view('admin.Reservation.edit', [
            'reservation' => $reservation,
            'messages' => MessageBoard::with('staff')->adminSearchFilter($id)->oldestFirst()->get(),
            'reservationStatus' => [
                config('code.reservationStatus.reserve'),
                config('code.reservationStatus.ensure'),
            ],
            'paymentStatus' => $code['paymentStatus'],
            'skyReserveNo' => $applicationDetail ? $applicationDetail->cm_application_id : null,
            'user' => $applicationDetail ? CmTmUser::getMembershipInfo($applicationDetail->cm_application_id) : null,
            'messageType' => \Arr::pluck(array_values(config('const.messageBoard.message_type')), 'name_ja', 'code'),
            'isRepeater' => Reservation::pastReserve($reservation->email)->count(),
            'isMobile' => $this->isMobile($request),
            'adminChangeInfo' => $tmpAdminChangeReservation ? json_decode($tmpAdminChangeReservation->info, true) : null,
            'cancelLimit' => $cancelLimit,
            'changeLimit' => $changeLimit,
            'newPayment' => $newPayment,
        ]);
    }

    public function saveMessageBoard(Request $request)
    {
        try {
            MessageBoard::create([
                'reservation_id' => $request->reservation_id,
                'message_type' => config('const.messageBoard.message_type.MANUAL_INPUT.code'),
                'message' => $request->message,
                'staff_id' => \Auth::user()->id,
            ]);

            return response()->json(
                ['ret' => 'ok']
            );
        } catch (\Exception $e) {
            report($e);

            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ], 503
            );
        }
    }

    public function updateReservationInfo(UpdateReservationInfoRequest $request)
    {
        try {
            \DB::beginTransaction();
            $reservation = Reservation::find($request->reservation_id);

            if ($reservation->app_cd == key(config('code.appCd.to'))) {
                $pickUpDatetime = $reservation->pick_up_datetime;
                $reservation->pick_up_datetime = $request->pick_up_datetime;
                $mail = new TakeoutMail($request->reservation_id);
                if ($request->pick_up_datetime != $pickUpDatetime) {
                    $mail->changeReservationForUser($pickUpDatetime, $request->pick_up_datetime);
                    MessageBoard::create([
                        'reservation_id' => $request->reservation_id,
                        'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                        'message' => sprintf('来店日時変更'),
                        'staff_id' => \Auth::user()->id,
                    ]);
                }

                if (
                    $reservation->reservation_status == config('code.reservationStatus.reserve.key') &&
                    $request->reservation_status == config('code.reservationStatus.ensure.key')) {
                    $reservation->store_reception_datetime = Carbon::now();
                    $mail->confirmReservationByClient();
                    MessageBoard::create([
                        'reservation_id' => $request->reservation_id,
                        'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                        'message' => sprintf('受注確定'),
                        'staff_id' => \Auth::user()->id,
                    ]);
                }

                $reservation->reservation_status = $request->reservation_status;
                $reservation->save();

            } else {

                $oldReservation = $reservation->replicate();
                $oldPickUpDatetime = new Carbon($reservation->pick_up_datetime);
                $newPickUpDatetime = new Carbon($request->pick_up_datetime);
                $now = Carbon::now();

                if ($oldPickUpDatetime != $newPickUpDatetime ||
                    $reservation->persons != $request->persons) {

                    if ($oldPickUpDatetime < $now
                        && ($reservation->payment_status == config('code.paymentStatus.payed.key')
                        || $reservation->payment_status == config('code.paymentStatus.unpaid.key'))) {  //来店後の変更

                        //来店時間変更
                        $pickUpDatetime = $reservation->pick_up_datetime;
                        $reservation->pick_up_datetime = $request->pick_up_datetime;
                        if ($request->pick_up_datetime != $pickUpDatetime) {
                            MessageBoard::create([
                                'reservation_id' => $request->reservation_id,
                                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                                'message' => sprintf('来店日時変更'),
                                'staff_id' => \Auth::user()->id,
                            ]);
                        }

                        //来店人数変更
                        $persons = $reservation->persons;
                        $reservation->persons = (int)$request->persons;
                        if ($request->persons != $persons) {
                            MessageBoard::create([
                                'reservation_id' => $request->reservation_id,
                                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                                'message' => sprintf('人数変更'),
                                'staff_id' => \Auth::user()->id,
                            ]);
                        }
                        $reservation->save();
                    } else {    //来店前の変更
                        // 料金計算
                        list($unitPrice, $menuTotal, $newTotal) = PaymentService::calcPrice($reservation, $newPickUpDatetime, $request->persons);
                        $reservationMenu = $reservation->reservationMenus[0];
                        $menu = $reservationMenu->menu;

                        $msg = null;
                        $menuObj = new Menu();

                        //予約の再チェック
                        // opening_hours store menuの曜日と祝日のチェック
                        if (!$menuObj->canSale($menu->id, $menu->store_id, $newPickUpDatetime, $msg)) {
                            throw new \Exception('check failed in canSale');
                        }

                        //空席,在庫チェック
                        if (!empty($menu->store->external_api)) {
                            //外部接続があれば外部の空席確認
                            if (!$this->restaurantStock->isVacancy($newPickUpDatetime, $request->persons, $menu->store_id, $msg)) {
                                throw new \Exception('check failed in isVacancy');
                            }
                        } else {
                            //外部接続がなければskyの在庫確認
                            if (!$this->restaurantReservationService->hasRestaurantStock($menu, $newPickUpDatetime, $request->persons, $msg)) {
                                throw new \Exception('check failed in hasRestaurantStock');
                            }
                        }

                        // コースの提供時間のチェック
                        if (!$this->restaurantReservationService->isSalesTime($menu, $newPickUpDatetime, $msg)) {
                            throw new \Exception('check failed in isSalesTime');
                        }

                        //利用可能下限、上限人数の確認
                        if (!$this->restaurantReservationService->isAvailableNumber($menu, $request->persons, $msg)) {
                            throw new \Exception('check failed in isAvailableNumber');
                        }


                        $invalidTmp = TmpAdminChangeReservation::where('reservation_id', $request->reservation_id)->where('is_invalid', 0)->whereNull('status')->first();
                        if (!is_null($invalidTmp)) {
                            $invalidTmp->is_invalid = 1;
                            $invalidTmp->save();
                        }

                        if ($reservation->total != $newTotal
                            && $now <= $oldPickUpDatetime
                            && ($reservation->payment_status == config('code.paymentStatus.wait_payment.key')
                            || $reservation->payment_status == config('code.paymentStatus.auth.key'))) { //再決済が必要な場合

                            $info['persons'] = (int) $request->persons;
                            $info['pick_up_datetime'] = $newPickUpDatetime->format('Y-m-d H:i:s');
                            list($unitPrice, $menuTotal, $newTotal) = PaymentService::calcPrice($reservation, $newPickUpDatetime, $info['persons']);

                            $info['unitPrice'] = $unitPrice;
                            $info['menuTotal'] = $menuTotal;
                            $info['total'] = $newTotal;

                            TmpAdminChangeReservation::create([
                                'reservation_id' => $request->reservation_id,
                                'info' => json_encode($info),
                                'is_invalid' => 0,
                            ]);

                            $reservation->payment_status = config('code.paymentStatus.wait_payment.key');

                            MessageBoard::create([
                                'reservation_id' => $request->reservation_id,
                                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                                'message' => sprintf('予約変更(再決済要求)'),
                                'staff_id' => \Auth::user()->id,
                            ]);


                            if (strtotime(Carbon::now()->format('Y-m-d')) < strtotime($oldPickUpDatetime->copy()->subDays(4)->format('Y-m-d'))) {
                                $reservation->payment_limit = Carbon::now()->addDays(3)->hour(23)->minute(59)->second(0);
                            } else {
                                $reservation->payment_limit = $newPickUpDatetime;
                            }
                            $reservation->save();

                            //メール送信処理
                            $mail = new RestaurantMail($request->reservation_id);
                            $mail->adminChangeReservationForUser($oldReservation);

                        } elseif (($reservation->total == 0 || $reservation->total == $newTotal)
                            && $now <= $oldPickUpDatetime) {    //再決済なし

                            //来店時間変更
                            $pickUpDatetime = $reservation->pick_up_datetime;
                            $reservation->pick_up_datetime = $request->pick_up_datetime;
                            if ($request->pick_up_datetime != $pickUpDatetime) {
                                MessageBoard::create([
                                    'reservation_id' => $request->reservation_id,
                                    'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                                    'message' => sprintf('来店日時変更'),
                                    'staff_id' => \Auth::user()->id,
                                ]);
                            }

                            //来店人数変更
                            $persons = $reservation->persons;
                            $reservation->persons = (int)$request->persons;
                            if ($request->persons != $persons) {
                                MessageBoard::create([
                                    'reservation_id' => $request->reservation_id,
                                    'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                                    'message' => sprintf('人数変更'),
                                    'staff_id' => \Auth::user()->id,
                                ]);
                            }

                            //外部予約変更処理
                            if ($reservation->external_reservation_id) {
                                $msg = null;
                                if (!$this->restaurantReservation->changeReservation($reservation, $msg)) {
                                    throw new \Exception('Error in changeReservation');
                                }
                            }

                            if ($reservation->payment_status == config('code.paymentStatus.wait_payment.key')) {
                                $reservation->payment_status = config('code.paymentStatus.auth.key');
                            }

                            $reservation->save();
                            //メール送信処理
                            $mail = new RestaurantMail($request->reservation_id);
                            $mail->adminChangeReservationForUser($oldReservation);

                        }
                    }

                } else {    // 何も変更せずに変更ボタンを押した場合
                    $invalidTmp = TmpAdminChangeReservation::where('reservation_id', $request->reservation_id)->where('is_invalid', 0)->whereNull('status')->first();
                    if (!is_null($invalidTmp)) {
                        $invalidTmp->is_invalid = 1;
                        $invalidTmp->save();
                    }

                    if ($reservation->payment_status == config('code.paymentStatus.wait_payment.key')) {
                        $reservation->payment_status = config('code.paymentStatus.auth.key');
                        $reservation->save();
                    }
                }
            }

            \DB::commit();
            return response()->json(['ret' => 'ok']);
        } catch (\Exception $e) {
            report($e);
            \DB::rollBack();

            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $msg,
                ]
            );
        }
    }

    public function clearAdminChangeInfo(Request $request)
    {
        try {
            \DB::beginTransaction();
            $reservation = Reservation::find($request->reservationId);
            $invalidTmp = TmpAdminChangeReservation::where('reservation_id', $request->reservationId)->where('is_invalid', 0)->whereNull('status')->first();
            $invalidTmp->is_invalid = 1;
            $invalidTmp->save();

            $reservation->payment_limit = null;
            $reservation->payment_status = config('code.paymentStatus.auth.key');
            $reservation->save();

            MessageBoard::create([
                'reservation_id' => $request->reservationId,
                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                'message' => sprintf('予約変更取消'),
                'staff_id' => \Auth::user()->id,
            ]);

            \DB::commit();
            return response()->json(['ret' => 'ok']);
        } catch (\Throwable $e) {
            \DB::rollback();
            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    public function cancelReservation(Request $request, PaymentSkyticket $paymentSkyticket)
    {
        try {
            $paymentToken = PaymentToken::where('reservation_id', $request->reservation_id)->where('is_invalid', 0)->first();
            if (!empty($paymentToken)) {
                $result = null;
                $reservation = Reservation::find($request->reservation_id);
                $pickUpDatetime = new Carbon($reservation->pick_up_datetime);
                $now = Carbon::now();
                $callBackValues = json_decode($paymentToken->call_back_values, true);
                if (!isset($callBackValues['orderCode'])) {
                    throw new \Exception('orderCodeが取れません');
                }
                \DB::beginTransaction();
                if (strtotime($now->format('Y-m-d')) >= strtotime($pickUpDatetime->format('Y-m-d'))) {  // 予約日当日
                    if ($paymentSkyticket->settlePayment($callBackValues['orderCode'], $result)) {
                        // 予約情報更新
                        $reservation->cancel_datetime = new Carbon();
                        $reservation->reservation_status = config('code.reservationStatus.cancel.key');
                        $reservation->payment_status = config('code.paymentStatus.no_refund.key');
                        $reservation->save();
                        // キャンセル料登録
                        $paymentDetails = $reservation->paymentDetails;
                        foreach ($paymentDetails as $paymentDetail) {
                            $data['reservation_id'] = $reservation->id;
                            $data['target_id'] = $paymentDetail->target_id;
                            $data['account_code'] = $paymentDetail->account_code;
                            $data['price'] = $paymentDetail->price;
                            $data['count'] = $paymentDetail->count;
                            CancelDetail::create($data);
                        }
                    } else {
                        \DB::rollback();
                        return response()->json(['message' => '新決済キャンセル失敗']);
                    }
                } else {
                    // テイクアウトしか来ない。テイクアウトはPaymentTokenのレコードは１つ
                    if ($paymentSkyticket->cancelPayment($callBackValues['orderCode'], $result)) {
                        // 予約情報更新
                        $reservation->cancel_datetime = new Carbon();
                        $reservation->reservation_status = config('code.reservationStatus.cancel.key');
                        $reservation->payment_status = config('code.paymentStatus.cancel.key');
                        $reservation->save();
                    } else {
                        \DB::rollback();
                        return response()->json(['message' => '新決済キャンセル失敗']);
                    }
                }
                \DB::commit();
            } else {
                $reservation = Reservation::find($request->reservation_id);
                $this->reservationService->cancel($reservation);
                \DB::beginTransaction();
                MessageBoard::create([
                    'reservation_id' => $request->reservation_id,
                    'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                    'message' => sprintf('予約キャンセル'),
                    'staff_id' => \Auth::user()->id,
                ]);
                \DB::commit();
            }

            return response()->json(['ret' => 'ok']);
        } catch (\Exception $e) {
            report($e);
            \DB::rollBack();

            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ], 503
            );
        }
    }

    public function cancelReservationForUser(Request $request)
    {
        try {
            \Log::debug('[CancelReservationForUser start] reservation id : '.$request->reservation_id);
            $reservation = Reservation::find($request->reservation_id);
            $resValues = [];
            $cancelFee = new CancelFee();

            list($refundPrice, $cancelPrice, $cancelFeeId) = $cancelFee->calcCancelFee($request->reservation_id);
            if (is_null($refundPrice) || is_null($cancelPrice)) {   //キャンセル料の登録なければエラー
                $msg = \Lang::get('message.cancelFeeChackFaimure');
                throw new \Exception($msg);
            }

            if ($reservation->payment_status == config('code.paymentStatus.payed.key')) {    //計上後(来店後)のキャンセル処理
                $reservationMenu = $reservation->reservationMenus[0];

                // メニューキャンセル料
                if (isset($cancelPrice['menu'])) {
                    $menuData['reservation_id'] = $request->reservation_id;
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
                        $optionData['reservation_id'] = $request->reservation_id;
                        $optionData['target_id'] = $optionId;
                        $optionData['account_code'] = 'OKONOMI';
                        $optionData['price'] = $option['price'];
                        $optionData['count'] = $option['count'];
                        $optionData['remarks'] = 'キャンセル料マスタID:'.$cancelFeeId;
                        CancelDetail::create($optionData);
                    }
                }

                $reservation->reservation_status = config('code.reservationStatus.cancel.key');
                $reservation->is_close = 0;
                $reservation->cancel_datetime = Carbon::now();
                $reservation->save();
            } else {    //計上前(来店前)のキャンセル処理
                if (!$this->restaurantReservationService->cancel($request->reservation_id, $resValues, true)) {
                    throw new \Exception();
                }
            }

            MessageBoard::create([
                'reservation_id' => $request->reservation_id,
                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                'message' => sprintf('予約キャンセル(お客様都合)'),
                'staff_id' => \Auth::user()->id,
            ]);

            return response()->json(['ret' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ],
            );
        }
    }

    public function cancelReservationForAdmin(Request $request)
    {
        try {
            \Log::debug('[CancelReservationForAdmin start] reservation id : '.$request->reservation_id);
            $msg = '';
            $reservation = Reservation::find($request->reservation_id);

            if ($reservation->payment_status == config('code.paymentStatus.payed.key')) {   //計上後(来店後)のキャンセル
                $reservation->reservation_status = config('code.reservationStatus.cancel.key');
                $reservation->is_close = 0;
                $reservation->cancel_datetime = Carbon::now();
                $reservation->save();
            } else {    //計上前(来店前)のキャンセル
                if (!$this->restaurantReservationService->adminCancel($request->reservation_id, $msg)) {
                    throw new \Exception($msg);
                }
            }

            MessageBoard::create([
                'reservation_id' => $request->reservation_id,
                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                'message' => sprintf('予約キャンセル(お店都合)'),
                'staff_id' => \Auth::user()->id,
            ]);

            return response()->json(['ret' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ], 503
            );
        }
    }

    public function updateDelegateInfo(Request $request)
    {
        try {
            $reservation = Reservation::find($request->reservation_id);
            $reservation->last_name = $request->last_name;
            $reservation->first_name = $request->first_name;
            $reservation->tel = $request->tel;
            $reservation->email = $request->email;
            $reservation->save();

            MessageBoard::create([
                'reservation_id' => $request->reservation_id,
                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                'message' => '申込者情報変更',
                'staff_id' => \Auth::user()->id,
            ]);

            return response()->json(['ret' => 'ok']);
        } catch (\Exception $e) {
            report($e);

            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ], 503
            );
        }
    }

    public function sendReservationMail(Request $request)
    {
        try {
            \DB::beginTransaction();
            $reservation = Reservation::find($request->reservation_id);
            MessageBoard::create([
                'reservation_id' => $request->reservation_id,
                'message_type' => config('const.messageBoard.message_type.MANAGEMENT_TOOL.code'),
                'message' => sprintf('予約完了メール再送（%s%s）', $reservation->last_name, $reservation->first_name),
                'staff_id' => \Auth::user()->id,
            ]);

            if ($reservation->app_cd === key(config('code.appCd.rs'))) {
                $mail = new RestaurantMail($request->reservation_id);
                $mail->completeReservationForUser();
            } else {
                $mail = new TakeoutMail($request->reservation_id);
                $mail->completeReservationForUser();
            }

            \DB::commit();

            session()->flash('message', '予約完了メールを再送しました');

            return response()->json(
                ['ret' => 'ok']
            );
        } catch (\Exception $e) {
            report($e);
            \DB::rollBack();

            return response()->json(
                [
                    'ret' => 'error',
                    'message' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * 予約一覧　CSV出力
     *
     * @param [type] $reservations
     * @return void
     */
    public function getCsv($reservations) {
        $headColumns = ['予約番号', '申込者', '来店日時', '人数', '金額', '予約ステータス', '入金ステータス','店舗名', '店舗電話番号', 'メールアドレス','電話番号','申込日時'];

        // 出力する順番とデータのみにする
        $output = [];
        foreach ($reservations->get() as $reservation) {
            $tmp = [];
            $tmp[] = $reservation->getReservationNoAttribute();  // 予約番号
            $tmp[] = $reservation->full_name;   // 申込者
            $tmp[] = ($reservation->pick_up_datetime);  // 来店日
            $tmp[] = $reservation->persons; // 人数
            $tmp[] = $reservation->total;   // 金額
            $tmp[] = config('code.reservationStatus.' . mb_strtolower($reservation->reservation_status) . '.value');    // 予約ステータス
            $tmp[] = config('code.paymentStatus.' . mb_strtolower($reservation->payment_status) . '.value');    // 入金ステータス
            $tmp[] = $reservation->reservationStore->name;   // 店舗名
            $tmp[] = "'" . str_replace('-', '', $reservation->reservationStore->tel);   // 店舗電話番号
            $tmp[] = $reservation->email;   // メールアドレス
            $tmp[] = "'" . $reservation->tel;   // 電話番号
            $tmp[] = $reservation->created_at;   // 申込日時
            $output[] = $tmp;
        }

        // 書き込み用ファイルを開く
        $f = fopen('予約一覧.csv', 'w');
        if ($f) {
            // カラムの書き込み
            mb_convert_variables('SJIS', 'UTF-8', $headColumns);
            fputcsv($f, $headColumns);
            // データの書き込み
            foreach ($output as $rec) {
                mb_convert_variables('SJIS', 'UTF-8', $rec);
                fputcsv($f, $rec);
            }
        }
        // ファイルを閉じる
        fclose($f);

        // HTTPヘッダ
        header("Content-Type: application/octet-stream");
        header('Content-Length: '.filesize('予約一覧.csv'));
        header('Content-Disposition: attachment; filename=予約一覧.csv');
        readfile('予約一覧.csv');
    }

    /**
     * cm_application_idから該当する予約IDを取得し、予約詳細画面にリダイレクトする
     *
     * @param Request $request
     * @param integer $id       cm_application_id
     * @return void
     */
    public function redirectEditForm(Request $request, int $id)
    {
        $cmThApplicationDetail = CmThApplicationDetail::getApplicationDetailByCmApplicationId($id);
        if ($cmThApplicationDetail) {
            $reservationId = $cmThApplicationDetail->application_id;
            return redirect(route('admin.reservation.edit', ['id' => $reservationId]));
        } else {
            return redirect(route('admin.reservation'))->with('message', \Lang::get('message.reservationNotFound'));
        }
    }

}
