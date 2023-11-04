この度はスカイチケットグルメをご利用いただき誠にありがとうございました。

スカイチケットではご利用されましたお客様の貴重なご意見やご感想をいただきたく、
簡単なアンケート入力をお願いしております。

今後のサービス向上に役立ててまいりますのでぜひご協力をお願いいたします。

アンケートはこちら↓
{{host_url(). 'takeout/inquiry'}}

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
