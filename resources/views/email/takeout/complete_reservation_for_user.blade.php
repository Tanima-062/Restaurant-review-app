スカイチケットグルメをご利用いただき、ありがとうございます。

{{$pickUpDatetimeForUser}}
「{{$store->name}}」のご注文を受け付けました。
下記のURLから詳しい注文内容をご確認ください。
{{host_url().'mypage/detail/?reservationNo='.$reservationNo}}

@include('email.takeout.order', [
    'pickUpDatetime' => $pickUpDatetime,
    'priceSumIncludingTax' => $reservation->total,
    'reservationMenu' => $reservationMenu
])

@include('email.takeout.store',[
    'store' => $store,
    'openingHours' => $reservation->reservationStore->store->openingHours
])

@include('email.takeout.customer',[
    'reservationNo' => $reservationNo,
    'name' => $reservation->last_name . ' ' . $reservation->first_name,
    'tel' => $reservation->tel,
    'email' => $reservation->email
])


▼ご注文内容に変更があった場合
一度成立した予約内容の変更はできません。恐れ入りますが、一度キャンセル（店舗へお問合せ）していただき、改めて予約手続きをお願いいたします。

▼ご注文をキャンセルする場合
お手数ですが、直接店舗へキャンセルのご連絡をお願いいたします。

@include('email.takeout.aboutme')

@include('email.takeout.footer')
