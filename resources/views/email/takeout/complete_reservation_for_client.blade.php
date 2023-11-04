いつもお世話になっております。
(株)アドベンチャーが運営するスカイチケットグルメです。

お客様から「{{$store->name}}」様へ、ご注文をいただきました。
下記のご注文内容を確認してください。


@include('email.takeout.order', [
    'pickUpDatetime' => $pickUpDatetime,
    'priceSumIncludingTax' => $reservation->total,
    'reservationMenu' => $reservationMenu
])
@include('email.takeout.customer',[
    'reservationNo' => $reservationNo,
    'name' => $reservation->last_name . ' ' . $reservation->first_name,
    'tel' => $reservation->tel,
    'email' => $reservation->email
])



@include('email.takeout.remind_client',[
    'adminUrl' => $adminUrl,
    'dishUpUrl' => $dishUpUrl
])



▼ご注文内容に変更が生じる場合
1.   記載されているお客様の連絡先に変更のご連絡をお願いします。
2.   管理画面から内容変更の手続きをしてください。

▼ご注文をキャンセルする場合
管理画面からキャンセルをお願いいたします。
お客様にはキャンセルされたことを自動メールでお知らせします。


@include('email.takeout.footer')
