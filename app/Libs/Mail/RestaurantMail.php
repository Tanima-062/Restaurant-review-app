<?php

namespace App\Libs\Mail;

use App\Models\CancelFee;
use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use App\Models\TmpAdminChangeReservation;

class RestaurantMail extends BaseMail
{
    public function __construct($reservationId)
    {
        parent::__construct($reservationId);
        $this->reservation = Reservation::where('id', $reservationId)->first();
        $this->reservationMenu = ReservationMenu::where('reservation_id', $reservationId)->get();
        $this->dt = new Carbon($this->reservation->pick_up_datetime);
        $this->cancelLimitDt = $this->dt->copy()->hour(23)->minute(59)->subDay();
        $this->reservationNo = $this->reservation->getRestaurantReservationNo($this->reservation->id);
        $this->pickUpDatetime = $this->dt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($this->reservation->pick_up_datetime).') G:i');
        $this->cancelLimit = $this->cancelLimitDt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($this->cancelLimitDt->format('Y-m-d H:i')).') G:i');
        if (\App::environment('production')) {
            $this->clientFrom = config('restaurant.mail.prdClientFrom');
        } else {
            $this->clientFrom = config('restaurant.mail.devClientFrom');
        }
    }

    public function completeReservationForUser()
    {
        $sortedCancelFees = $this->getCancelPolicy();

        // 宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantCompleteReservationForUser'));

        // 本文設定
        $body = Mail::render('email.restaurant.complete_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'cancelFees' => $sortedCancelFees,
            'cancelLimit' => $this->cancelLimit,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function completeReservationForClient($storeEmail)
    {
        //宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($storeEmail);

        // 件名設定
        if ($this->reservation->total > 0) {
            $this->setSubject(Lang::get('message.mail.restaurantCompleteReservationForClient0'));
        } else {
            $this->setSubject(Lang::get('message.mail.restaurantCompleteReservationForClient1'));
        }

        // 本文設定
        $body = Mail::render('email.restaurant.complete_reservation_for_client', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->setNonShowUserFlg();
        $this->send($this->reservation->id, $this->clientFrom);
    }

    public function userChangeReservationForClient(Reservation $oldReservation, $storeEmail)
    {
        $oldDt = new Carbon($oldReservation->pick_up_datetime);
        $oldPickUpDatetime = $oldDt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($oldReservation->pick_up_datetime).') G:i');

        // 宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($storeEmail);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantChangeReservationForClient'));

        // 本文設定
        $body = Mail::render('email.restaurant.user_change_reservation_for_client', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'oldPickUpDatetime' => $oldPickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'oldReservation' => $oldReservation,
        ]);
        $this->setMessageEnc($body);
        $this->setNonShowUserFlg();
        $this->send($this->reservation->id, $this->clientFrom);
    }

    public function userChangeReservationForUser(Reservation $oldReservation)
    {
        $sortedCancelFees = $this->getCancelPolicy();

        // 宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        $oldDt = new Carbon($oldReservation->pick_up_datetime);
        $oldPickUpDatetime = $oldDt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($oldReservation->pick_up_datetime).') G:i');
        // 件名設定
        if ($this->reservation->total == 0 || $this->reservation->total == $oldReservation->total) {
            $this->setSubject(Lang::get('message.mail.restaurantChangeReservationForUser0'));
        } else {
            $this->setSubject(Lang::get('message.mail.restaurantChangeReservationForUser1'));
        }

        // 本文設定
        $body = Mail::render('email.restaurant.user_change_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'oldPickUpDatetime' => $oldPickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'cancelFees' => $sortedCancelFees,
            'oldReservation' => $oldReservation,
            'cancelLimit' => $this->cancelLimit,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function adminChangeReservationForUser(Reservation $oldReservation)
    {
        $tmpAdminChangeReservation = TmpAdminChangeReservation::where('reservation_id', $this->reservation->id)->where('is_invalid', 0)->whereNull('status')->first();
        if (!empty($tmpAdminChangeReservation)) {
            $changeInfo = json_decode($tmpAdminChangeReservation->info, true);
            $this->reservation->persons = $changeInfo['persons'];
            $this->reservation->pick_up_datetime = $changeInfo['pick_up_datetime'];
            $this->reservation->total = $changeInfo['total'];
        }
        $sortedCancelFees = $this->getCancelPolicy();
        $oldDt = new Carbon($oldReservation->pick_up_datetime);
        $oldPickUpDatetime = $oldDt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($oldReservation->pick_up_datetime).') G:i');
        $dt = new Carbon($this->reservation->pick_up_datetime);
        $this->pickUpDatetime = $dt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($this->reservation->pick_up_datetime).') G:i');

        // 宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantAdminChangeReservationForUser'));

        // 本文設定
        $body = Mail::render('email.restaurant.admin_change_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'oldPickUpDatetime' => $oldPickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'oldReservation' => $oldReservation,
            'cancelFees' => $sortedCancelFees,
            'cancelLimit' => $this->cancelLimit,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function userCancelReservationForClient($storeEmail)
    {
        //宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($storeEmail);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantCancelReservationForClient'));
        // 本文設定
        $body = Mail::render('email.restaurant.user_cancel_reservation_for_client', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->setNonShowUserFlg();
        $this->send($this->reservation->id, $this->clientFrom);
    }

    public function userCancelReservationForUser()
    {
        //宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantCancelReservationForUser'));
        // 本文設定
        $body = Mail::render('email.restaurant.user_cancel_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function adminCancelReservationForUser()
    {
        //宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantAdminCancelReservationForUser'));
        // 本文設定
        $body = Mail::render('email.restaurant.admin_cancel_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function questionnaireForUser()
    {
        //宛先設定
        // $this->setToAddressEnc('h-tanabe@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.restaurantQuestionnaireForUser'));
        // 本文設定
        $body = Mail::render('email.restaurant.questionnaire', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('restaurant.mail.from'));
    }

    public function getCancelPolicy()
    {
        $cancelFees = (new CancelFee())->getCancelPolicy($this->reservation->reservationStore->store->id, key(config('code.appCd.rs')))
        ->sort(function ($first, $second) {
            if ($first->visit != $second->visit) {
                return $first->visit < $second->visit ? 1 : -1;
            }
            if ($first->cancel_limit_unit == $second->cancel_limit_unit) {
                return $first->cancel_limit < $second->cancel_limit ? 1 : -1;
            }

            return $first->cancel_limit_unit < $second->cancel_limit_unit ? -1 : 1;
        });

        $sortedCancelFees = [];
        if ($cancelFees->count() !== 0) {
            foreach ($cancelFees as $cancelFee) {
                if ($cancelFee->visit === config('code.cancelPolicy.visit.before')) {
                    switch ($cancelFee->cancel_limit_unit) {
                        case config('code.cancelPolicy.cancel_limit_unit.day'):
                            if ($cancelFee->cancel_limit == 0) {
                                $cancelLimit = '来店日当日・・・・・';
                                break;
                            }
                            $cancelLimit = '来店日の'.$cancelFee->cancel_limit.'日前まで・・・・・';
                            break;

                        case config('code.cancelPolicy.cancel_limit_unit.time'):
                            $cancelLimit = '来店日の'.$cancelFee->cancel_limit.'時間まで・・・・・';
                            break;
                    }
                    switch ($cancelFee->cancel_fee_unit) {
                        case config('code.cancelPolicy.cancel_fee_unit.flatRate'):
                            $cancelFee = $cancelFee->cancel_fee.'円';
                            break;

                        case config('code.cancelPolicy.cancel_fee_unit.fixedRate'):
                            $cancelFee = '予約料金の'.$cancelFee->cancel_fee.'％';
                            break;
                    }
                    $sortedCancelFees[] = $cancelLimit.$cancelFee;
                } else {
                    $sortedCancelFees[] = '来店後　　 ・・・・・予約料金の100％';
                }
            }
        }

        return $sortedCancelFees;
    }
}
