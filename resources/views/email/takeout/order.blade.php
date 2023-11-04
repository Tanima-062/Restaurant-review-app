~ご注文内容～
■商品のお受け取り日時
{{$pickUpDatetime}}

■お支払い金額
合計(税込）￥{{number_format($priceSumIncludingTax)}}

■メニュー
@foreach ($reservationMenu as $menu)
〇{{$menu->name}}    ¥{{ number_format($menu->unit_price) }} x {{$menu->count}}
<?php
$optionTotal = 0;
foreach ($menu->reservationOptions as $option) {
    $optionTotal += $option->price;
}
?>
@foreach ($menu->reservationOptions as $option)

@if ($loop->first)
    オプション  ¥{{ $optionTotal }}
@endif
        {{$option->keyword}}:{{$option->contents}}    ¥{{ number_format($option->unit_price) }} x {{$option->count}}
@endforeach

@endforeach
