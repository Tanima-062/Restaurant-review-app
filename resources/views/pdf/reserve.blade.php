<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>【スカイチケットグルメ】テイクアウトのご注文</title>
<style type="text/css">
    .inner{
        text-align: center;
    }

    td, th {
        padding: 5px 10px;
    }

    table {
        font-size: 20px;
    }
</style>
</head>
<body>
<div class="inner">
    <h1>【スカイチケットグルメ】<br>テイクアウトのご注文いただきました！</h1>
    いつもお世話になっております。
    (株)アドベンチャーが運営するスカイチケットグルメです。<br>
    お客様からご注文をいただきました。
    下記のご注文内容を確認してください。<br>
    <br>
    <table border="1" align="center" width="100%">
        <thead>
            <tr>
                <th colspan="2">~ご注文内容～</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>商品のお受け取り日時</td>
                <td style="text-align:right">{{$reservation->pick_up_datetime}}</td>
            </tr>
            <tr>
                <td>お支払い金額 合計(税込)</td>
                <td style="text-align:right">&yen;{{number_format($reservation->total)}}</td>
            </tr>
            <tr>
                <td>注文内容(税込)</td>
                <td>
                    @foreach($reservation->reservationMenus as $reservationMenu)
                        <div style="clear:both;float:left">{{$reservationMenu->name}}</div><div style="text-align:right;font-size: 15px">&yen;{{number_format($reservationMenu->unit_price)}} ✕ {{$reservationMenu->count}}</div>
                        @if(isset($reservationMenu->reservationOptions))
                            <div style="font-size: 15px">
                            @foreach($reservationMenu->reservationOptions as $reservationOption)
                                @if($reservationOption->keyword)
                                    <div style="clear:both;float:left">&nbsp;&nbsp;{{$reservationOption->keyword}}: {{$reservationOption->contents}}</div><div style="text-align:right">&yen;{{number_format($reservationOption->unit_price)}} ✕ {{$reservationOption->count}}</div>
                                @else
                                    <div style="clear:both;float:left">&nbsp;&nbsp;トッピング: {{$reservationOption->contents}}</div><div style="text-align:right">&yen;{{number_format($reservationOption->unit_price)}} ✕ {{$reservationOption->count}}</div>
                                @endif
                            @endforeach
                            </div><br>
                        @endif
                    @endforeach
                </td>
            </tr>
            <tr>
                <td>要望</td>
                <td><div style="font-size: 15px">{{$reservation->request}}</div></td>
            </tr>
        </tbody>
    </table>
    <br>

    <div>
        <table border="1" align="left" width="50%" style="float:left;margin-right: 10px">
            <thead>
                <tr>
                    <th colspan="2">～お客様情報~</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>予約システム</td>
                    <td>スカイチケット</td>
                </tr>
                <tr>
                    <td>予約申込日</td>
                    <td>{{date('Y-m-d', strtotime($reservation->created_at))}}</td>
                </tr>
                <tr>
                    <td>skyticket予約番号</td>
                    <td>{{$reservation->app_cd}}{{$reservation->id}}</td>
                </tr>
                <tr>
                    <td>予約者名</td>
                    <td>{{$reservation->last_name}} {{$reservation->first_name}}<br>
                    </td>
                </tr>
                <tr>
                    <td>電話番号</td>
                    <td>{{$reservation->tel}}</td>
                </tr>
                <tr>
                    <td>メールアドレス</td>
                    <td>{{$reservation->email}}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <div style="text-align: left">
            FAX通知をご利用の店舗様は自動で受注確定となります。<br>
            ▼ご注文内容に変更が生じる場合<br>
            1.   記載されているお客様の連絡先に変更のご連絡をお願いします。<br>
            2.   管理画面から内容変更の手続きをしてください。<br>
            <br>
            ▼ご注文をキャンセルする場合<br>
            管理画面からキャンセルをお願いいたします。<br>
            お客様にはキャンセルされたことを自動メールでお知らせします。<br>
        </div>
    </div>
</div>
<br>
<br>
<br>
<br>
<br>
<br>
--------------------------------------------------------------------------<br>
スカイチケットグルメサービス<br>
〒150-6024<br>
東京都渋谷区恵比寿4-20-3 恵比寿ガーデンプレイスタワー24F<br>
mail :gourmet@skyticket.com<br>
URL :https://skyticket.jp/gourmet/<br>
営業時間　平日10：00-18：30 / 土日祝日：受付不可<br>
--------------------------------------------------------------------------<br>
</body>
</html>
