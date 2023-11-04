スカイチケットグルメをご利用いただき、ありがとうございます。

今回ご注文いただいたメニューは店舗事情により、ご注文を確定することができませんでした。
ご希望に添えず誠に申し訳ございません。


再度ご注文される場合は、お手数ですが、改めて予約手続きをお願いいたします。

※決済の手続きは完了しておりません。
自動でキャンセル手続きをいたします。キャンセル料は発生いたしませんので、
お客様に請求がいくことはありません。ご安心ください。


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
