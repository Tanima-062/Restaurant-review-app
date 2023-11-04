スカイチケットグルメサービスをご利用いただき、ありがとうございます。

@if ($reservation->total == 0 || $reservation->total == $oldReservation->total)
「{{$store->name}}」から、ご予約内容変更の連絡がありました。
下記変更内容をご確認下さい。
@else
ご予約された「{{$store->name}}」から、予約内容変更の連絡がありました。
前回の決済はキャンセルされましたので、支払期限までに下記URLより変更後の料金をお支払いください。
期限を過ぎますと、キャンセルになる場合がございます。

~お支払い~
■お支払い金額（変更後）
￥{{number_format($reservation->total)}}
{{host_url().'mypage/detail/?reservationNo='.$reservationNo}}
■入金期限
{{ $reservation->payment_limit }}まで
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
