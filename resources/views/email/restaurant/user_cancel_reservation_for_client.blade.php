いつもお世話になっております。
(株)アドベンチャーが運営するスカイチケットグルメサービスです。

お客様により下記予約がキャンセルされました。


@include('email.restaurant.reservation', [
    'pickUpDatetime' => $pickUpDatetime,
    'store' => $reservation->reservationStore->store,
    'reservationMenu' => $reservationMenu,
    'reservationNo' => $reservationNo,
    'reservation' => $reservation,
])

@include('email.restaurant.customer',[
    'reservationNo' => $reservationNo,
    'name' => $reservation->last_name . ' ' . $reservation->first_name,
    'tel' => $reservation->tel,
    'email' => $reservation->email
])

@include('email.restaurant.footer')
