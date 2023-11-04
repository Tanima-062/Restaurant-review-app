スカイチケットグルメサービスをご利用いただき、ありがとうございます。
@if ($reservation->total == 0 || $reservation->total == $oldReservation->total)
ご予約の変更を承りましたので下記予約内容をご確認下さい。
@else
ご予約変更のお支払いが完了いたしましたので、下記内容でご予約承りました。
@endif



@include('email.restaurant.change_reservation', [
    'pickUpDatetime' => $pickUpDatetime,
    'oldPickUpDatetime' => $oldPickUpDatetime,
    'store' => $reservation->reservationStore->store,
    'reservationMenu' => $reservationMenu,
    'reservationNo' => $reservationNo,
    'reservation' => $reservation,
    'oldReservation' => $oldReservation,
])

@include('email.restaurant.store',[
    'store' => $reservation->reservationStore->store,
    'openingHours' => $reservation->reservationStore->store->openingHours,
    'baseUrl' => config('app.url'),
    'restaurantDetailUrl' => 'restaurant/detail/takeout/',
])


@include('email.restaurant.customer',[
    'reservationNo' => $reservationNo,
    'name' => $reservation->last_name . ' ' . $reservation->first_name,
    'tel' => $reservation->tel,
    'email' => $reservation->email
])


@include('email.restaurant.cancel_policy',[
    'reservation' => $reservation,
    'cancelFees' => $cancelFees,
    'cancelLimit' =>$cancelLimit,
])


@include('email.restaurant.aboutme')

@include('email.restaurant.footer')
