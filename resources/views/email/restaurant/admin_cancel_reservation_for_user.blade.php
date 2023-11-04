スカイチケットグルメサービスをご利用いただき、ありがとうございます。

ご予約いただいた店舗より、本日、予約キャンセルの処理がされました。
別途、店舗よりご連絡させていただくことになっておりますが、ご連絡がない場合やご不明な点がある場合等につきましては、直接店舗までお問合せ下さい。

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

@include('email.restaurant.footer')
