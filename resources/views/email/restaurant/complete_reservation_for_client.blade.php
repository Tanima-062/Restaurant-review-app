いつもお世話になっております。
(株)アドベンチャーが運営するスカイチケットグルメサービスです。

お客様から「{{$store->name}}」様へ、ご予約が入りました。
下記の予約内容をご確認ください。


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


▼ご予約内容に変更が生じる場合
1.　記載されているお客様の連絡先に直接変更のご連絡をお願いします。
2.　管理画面から内容変更の手続きをしてください。
https://gourmet.skyticket.com/admin/


▼ご予約をキャンセルする場合
管理画面からキャンセルの手続きしてください。
https://gourmet.skyticket.com/admin/
お客様にはキャンセルされたことを自動メールでお知らせします。

@include('email.restaurant.footer')
