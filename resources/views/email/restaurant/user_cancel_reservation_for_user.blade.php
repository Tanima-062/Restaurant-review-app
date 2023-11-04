スカイチケットグルメサービスをご利用いただき、ありがとうございます。

ご予約のキャンセルを承りましたので下記予約内容をご確認下さい。

@include('email.restaurant.reservation',[
    'store' => $reservation->reservationStore->store,
    'pickUpDatetime' => $pickUpDatetime,
    'reservationMenu' => $reservationMenu,
    'reservationNo' => $reservationNo,
    'reservation' => $reservation,
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

@include('email.restaurant.aboutme')

@include('email.restaurant.footer')
