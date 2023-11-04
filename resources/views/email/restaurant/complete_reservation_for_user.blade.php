スカイチケットグルメサービスをご利用いただき、ありがとうございます。

{{$pickUpDatetime}}
「{{$store->name}}」のご予約が確定しました。
下記のURLから詳しい予約内容をご確認ください。

～予約内容の確認～
{{host_url().'mypage/detail/?reservationNo='.$reservationNo}}


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


@include('email.restaurant.cancel_policy',[
    'reservation' => $reservation,
    'cancelFees' => $cancelFees,
    'cancelLimit' =>$cancelLimit,
])


@include('email.restaurant.aboutme')

@include('email.restaurant.footer')
