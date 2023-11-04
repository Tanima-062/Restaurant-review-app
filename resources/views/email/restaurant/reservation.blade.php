~ご予約内容~
■来店日時
{{$pickUpDatetime}}

■人数
{{$reservation->persons}}名

■お支払い金額
合計(税込み）￥{{number_format($reservation->total)}}

@if ($reservation->total > 0)
≫≫≫事前決済済みです≪≪≪
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
