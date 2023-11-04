<?php

namespace App\Libs\Mail;

use App\Models\Reservation;
use App\Models\ReservationMenu;
use App\Models\ReservationStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;

class TakeoutMail extends BaseMail
{
    public function __construct($reservationId)
    {
        parent::__construct($reservationId);
        $this->reservation = Reservation::where('id', $reservationId)->first();
        $this->reservationMenu = ReservationMenu::where('reservation_id', $reservationId)->get();
        $this->dt = new Carbon($this->reservation->pick_up_datetime);
        $this->reservationNo = $this->reservation->getTakeoutReservationNo($this->reservation->id);
        $this->pickUpDatetime = $this->dt->format('n/j ('.$this->getDateOfWeekUsedInMail($this->reservation->pick_up_datetime).') G:i');
        $this->pickUpDatetimeForUser = $this->dt->format('Y年n月j日 ('.$this->getDateOfWeekUsedInMail($this->reservation->pick_up_datetime).') G:i');
        if (\App::environment('production')) {
            $this->clientFrom = config('takeout.mail.prdClientFrom');
        } else {
            $this->clientFrom = config('takeout.mail.devClientFrom');
        }
    }

    public function completeReservationForClient($storeEmail)
    {
        // 宛先設定
        //$this->setToAddressEnc('y-nakazato@adventure-inc.co.jp');
        $this->setToAddressEnc($storeEmail);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.completeReservationForClient'));

        // 本文設定
        $body = Mail::render('email.takeout.complete_reservation_for_client', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'adminUrl' => env('ADMIN_URL'),
            'dishUpUrl' => env('DISHUP_URL'),
        ]);
        $this->setMessageEnc($body);
        $this->setNonShowUserFlg();
        $this->send($this->reservation->id, $this->clientFrom);
    }

    public function completeReservationForUser()
    {
        // 宛先設定
        //$this->setToAddressEnc('y-nakazato@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.completeReservationForUser'));

        // 本文設定
        $body = Mail::render('email.takeout.complete_reservation_for_user', [
            'pickUpDatetimeForUser' => $this->pickUpDatetimeForUser,
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('takeout.mail.from'));
    }

    public function confirmReservationByClient()
    {
        //$this->setToAddressEnc('y-nakazato@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.confirmReservationByClient'));

        // 本文設定
        $body = Mail::render('email.takeout.confirm_reservation_by_client', [
            'pickUpDatetimeForUser' => $this->pickUpDatetimeForUser,
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('takeout.mail.from'));
    }

    public function closeReservation()
    {
        // 宛先設定
        //$this->setToAddressEnc('y-nakazato@adventure-inc.co.jp');
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.closeReservation'));

        // 本文設定
        $body = Mail::render('email.takeout.close_reservation', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('takeout.mail.from'));
    }

    public function changeReservationForUser(string $oldPickUpDatetime, string $newPickUpDatetime)
    {
        // 宛先設定
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.changeReservationForUser'));

        $ot = Carbon::create($oldPickUpDatetime);
        $oldPickUpDatetime = $ot->format('n/j ('.$this->getDateOfWeekUsedInMail($oldPickUpDatetime).') G:i');
        $nt = Carbon::create($newPickUpDatetime);
        $newPickUpDatetime = $nt->format('n/j ('.$this->getDateOfWeekUsedInMail($newPickUpDatetime).') G:i');

        // 本文設定
        $body = Mail::render('email.takeout.change_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'oldPickUpDatetime' => $oldPickUpDatetime,
            'newPickUpDatetime' => $newPickUpDatetime,
            'pickUpDatetime' => $newPickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('takeout.mail.from'));
    }

    public function cancelReservationForUser()
    {
        // 宛先設定
        $this->setToAddressEnc($this->reservation->email);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.cancelReservationForUser'));

        // 本文設定
        $body = Mail::render('email.takeout.cancel_reservation_for_user', [
            'store' => $this->reservation->reservationStore->store,
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservation' => $this->reservation,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
        ]);
        $this->setMessageEnc($body);
        $this->send($this->reservation->id, config('takeout.mail.from'));
    }

    public function remindReservationForClient($storeEmail)
    {
        // 宛先設定
        $this->setToAddressEnc($storeEmail);

        // 件名設定
        $this->setSubject(Lang::get('message.mail.remindReservationForClient'));

        // 本文設定
        $body = Mail::render('email.takeout.remind_reservation_for_client', [
            'pickUpDatetime' => $this->pickUpDatetime,
            'reservationMenu' => $this->reservationMenu,
            'reservationNo' => $this->reservationNo,
            'reservation' => $this->reservation,
            'adminUrl' => env('ADMIN_URL'),
            'dishUpUrl' => env('DISHUP_URL'),
        ]);
        $this->setMessageEnc($body);
        $this->setNonShowUserFlg();
        $this->send($this->reservation->id, $this->clientFrom);
    }
}
