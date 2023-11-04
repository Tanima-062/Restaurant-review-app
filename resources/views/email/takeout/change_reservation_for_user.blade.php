スカイチケットグルメをご利用いただき、ありがとうございます。

ご注文された「{{$store->name}}」から、内容変更の連絡がありました。
下記をご確認ください。

【変更内容】
■商品のお受け取り日時
{{$oldPickUpDatetime}}　　→　　{{$newPickUpDatetime}}

尚、ご注文をキャンセルする場合、お手数ですが店舗へ直接ご連絡ください。


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

今後ともスカイチケットグルメサービスのご利用を何卒よろしくお願いいたします。

@include('email.takeout.aboutme')

@include('email.takeout.footer')
