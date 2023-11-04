スカイチケットグルメをご利用いただき、ありがとうございました。

ご来店された店舗はいかがでしたか？

スカイチケットではご利用されましたお客様の貴重なご意見やご感想をいただきたく、
簡単なアンケート入力をお願いしております。

今後のサービス向上に役立ててまいりますのでぜひご協力をお願いいたします。

アンケートはこちら↓
{{-- @todo URLの変更 --}}
{{host_url().'mypage/login/'}}


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


今後ともスカイチケットグルメサービスのご利用を何卒よろしくお願いいたします。

その他、ご旅行に関するご予約などもお待ちしております。



@include('email.restaurant.aboutme')

@include('email.restaurant.footer')
