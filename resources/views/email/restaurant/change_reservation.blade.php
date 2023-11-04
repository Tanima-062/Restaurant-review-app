~ご予約内容~
■来店日時
@if ($pickUpDatetime !== $oldPickUpDatetime)
{{$oldPickUpDatetime}}　⇒　{{$pickUpDatetime}}
@else
{{$pickUpDatetime}}
@endif

■人数
@if ($reservation->persons !== $oldReservation->persons)
{{$oldReservation->persons}}名　⇒　{{$reservation->persons}}名
@else
{{$reservation->persons}}名
@endif

■お支払い金額
@if ($oldReservation->total !== $reservation->total)
合計(税込み）￥{{number_format($oldReservation->total)}}　⇒　￥{{number_format($reservation->total)}}
@else
合計(税込み）￥{{number_format($oldReservation->total)}}
@endif

■プラン
@php
$menu = $reservationMenu[0]
@endphp
{{$menu->name}}

■追加オプション
@if ($menu->reservationOptions->count() === 0)
なし
@else
@foreach ($menu->reservationOptions as $option)
{{$option->contents}} x {{$option->count}}
@endforeach
@endif

■ご要望
{{ $reservation->request }}
